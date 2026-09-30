<?php
/**
 * One setting.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support\Settings;

use TUNET\Volumina\Support\Color;

defined( 'ABSPATH' ) || exit;

/**
 * A single setting: what it is called, what it holds, and how to draw it.
 *
 * Scaffolding. It knows nothing about what any particular setting means, which
 * is what lets the next plugin take this directory unchanged.
 */
final class Field {

	/**
	 * The select's value for "a colour of my own" in a color_select field.
	 */
	public const CUSTOM = 'custom';

	/**
	 * The field's configuration.
	 *
	 * @var array<string, mixed>
	 */
	private array $args;

	/**
	 * Builds a field.
	 *
	 * @param array<string, mixed> $args {
	 *     Field configuration.
	 *
	 *     @type string   $key         Required. Key within the option array.
	 *     @type string   $label       Required. What to call it.
	 *     @type string   $type        text, textarea, number, checkbox, select, radio, swatches, cards
	 *                                 or color_select (a select of named colours plus "Custom…", which
	 *                                 stores a `#rrggbb` picked with WordPress's colour picker).
	 *     @type mixed    $default     What it holds when nobody has chosen.
	 *     @type string   $description Shown under the control. Every field gets one.
	 *     @type array    $choices     value => label, for select and radio; value => { label, color }
	 *                                 for swatches and color_select. A callable returning that array is
	 *                                 resolved when the choices are first needed, for lists that depend
	 *                                 on the theme.
	 *     @type callable $sanitize    Overrides the default for the type.
	 *     @type string   $section     Which section of the screen it belongs to; empty for none.
	 * }
	 */
	public function __construct( array $args ) {
		$this->args = array_merge(
			array(
				'key'         => '',
				'label'       => '',
				'type'        => 'text',
				'default'     => '',
				'description' => '',
				'choices'     => array(),
				'sanitize'    => null,
				'section'     => '',
			),
			$args
		);
	}

	/**
	 * The key this field is stored under.
	 */
	public function key(): string {
		return (string) $this->args['key'];
	}

	/**
	 * What to call it.
	 */
	public function label(): string {
		return (string) $this->args['label'];
	}

	/**
	 * What it says under the control.
	 */
	public function description(): string {
		return (string) $this->args['description'];
	}

	/**
	 * Whether the control carries its own label, as a checkbox does with the
	 * text beside it, or a fieldset of radios with each choice.
	 */
	public function labels_itself(): bool {
		return in_array( $this->args['type'], array( 'checkbox', 'radio', 'swatches', 'cards' ), true );
	}

	/**
	 * The section of the screen it belongs to, or empty.
	 */
	public function section(): string {
		return (string) $this->args['section'];
	}

	/**
	 * What it holds when nobody has chosen.
	 *
	 * @return mixed
	 */
	public function default_value() {
		return $this->args['default'];
	}

	/**
	 * The choices, resolved: a callable is called once and its answer kept.
	 *
	 * @return array<string, mixed>
	 */
	public function choices(): array {
		if ( is_callable( $this->args['choices'] ) ) {
			$this->args['choices'] = (array) call_user_func( $this->args['choices'] );
		}

		return (array) $this->args['choices'];
	}

	/**
	 * What it means for this field to be missing from a form.
	 *
	 * A browser sends nothing at all for an unticked checkbox, so absence is
	 * how "no" is spelled — but only for a checkbox. Every other kind of field
	 * that is absent was simply not on the form, and coercing that to an empty
	 * value would quietly reset a setting nobody touched.
	 *
	 * @return mixed
	 */
	public function absent() {
		return 'checkbox' === $this->args['type'] ? false : $this->default_value();
	}

	/**
	 * Anything in, something safe to store out.
	 *
	 * A choice field can only ever hold one of its choices, whatever arrives.
	 *
	 * @param mixed $value Candidate value.
	 * @return mixed
	 */
	public function sanitize( $value ) {
		if ( is_callable( $this->args['sanitize'] ) ) {
			return call_user_func( $this->args['sanitize'], $value );
		}

		if ( 'color_select' === $this->args['type'] ) {
			return $this->sanitize_color( $value );
		}

		// A form field arrives as a string or not at all. An array in its
		// place — `name[]` typed into a crafted form — is nothing to store,
		// and casting it to a string would only raise a warning.
		if ( ! is_scalar( $value ) && null !== $value ) {
			return $this->absent();
		}

		switch ( $this->args['type'] ) {
			case 'checkbox':
				return (bool) $value;

			case 'number':
				return (int) $value;

			case 'select':
			case 'radio':
			case 'swatches':
			case 'cards':
				return array_key_exists( (string) $value, $this->choices() )
					? (string) $value
					: (string) $this->default_value();

			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * A colour field's value: one of its choices, or a colour of one's own.
	 *
	 * From the form it arrives as `{ choice, custom }`: the select, and the
	 * picker's hex, read only when the select says "custom". Once stored it is
	 * a plain string — a choice's key or a `#rrggbb` — and comes back through
	 * here as one. Anything else is the default.
	 *
	 * @param mixed $value Candidate value.
	 */
	private function sanitize_color( $value ): string {
		$default = (string) $this->default_value();

		if ( is_array( $value ) ) {
			$choice = $value['choice'] ?? '';

			if ( ! is_string( $choice ) ) {
				return $default;
			}

			if ( self::CUSTOM === $choice ) {
				$custom = Color::normalize( is_string( $value['custom'] ?? null ) ? $value['custom'] : '' );

				return '' === $custom ? $default : $custom;
			}

			$value = $choice;
		}

		if ( ! is_string( $value ) ) {
			return $default;
		}

		if ( array_key_exists( $value, $this->choices() ) ) {
			return $value;
		}

		$hex = Color::normalize( $value );

		// Only a value spelled as a hex colour is one; a palette slug such as
		// `bad` or `ace` must not turn into a colour by accident.
		return '' !== $hex && 1 === preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? $hex : $default;
	}

	/**
	 * A select of named colours plus "Custom…", a swatch showing the colour,
	 * and a hex field for the custom one. Without JavaScript the hex field is
	 * a plain text box that works; `color-select.js` turns it into WordPress's
	 * colour picker, shows it only for "Custom…" and keeps the swatch in step.
	 * The swatch is decoration: the option's name carries the meaning.
	 *
	 * @param string $id    The select's id.
	 * @param string $name  The name attribute, already namespaced by the group.
	 * @param mixed  $value What it currently holds: a choice's key or a colour.
	 */
	private function render_color( string $id, string $name, $value ): void {
		$value   = is_string( $value ) ? $value : '';
		$choices = $this->choices();
		$custom  = ! array_key_exists( $value, $choices ) ? Color::normalize( $value ) : '';
		$current = '' !== $custom ? self::CUSTOM : $value;
		$shown   = $custom;

		printf( '<span class="volumina-color-select" data-volumina-color-select><select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name . '[choice]' ) );

		foreach ( $choices as $choice => $swatch ) {
			$color = is_array( $swatch ) ? (string) ( $swatch['color'] ?? '' ) : '';
			$label = is_array( $swatch ) ? (string) ( $swatch['label'] ?? $choice ) : (string) $swatch;

			if ( (string) $choice === $current ) {
				$shown = $color;
			}

			printf(
				'<option value="%1$s" data-color="%2$s"%3$s>%4$s</option>',
				esc_attr( (string) $choice ),
				esc_attr( Color::normalize( $color ) ),
				selected( $current, (string) $choice, false ),
				esc_html( $label )
			);
		}

		printf(
			'<option value="%1$s" data-color="%2$s"%3$s>%4$s</option></select>',
			esc_attr( self::CUSTOM ),
			esc_attr( $custom ),
			selected( $current, self::CUSTOM, false ),
			esc_html__( 'Custom…', 'volumina' )
		);

		printf(
			'<span class="volumina-color-swatch" aria-hidden="true" style="background:%s"></span>',
			esc_attr( '' === Color::normalize( $shown ) ? 'transparent' : Color::normalize( $shown ) )
		);

		printf(
			'<span class="volumina-color-custom"><label class="screen-reader-text" for="%1$s-custom">%2$s</label><input type="text" id="%1$s-custom" name="%3$s" value="%4$s" class="volumina-color-picker" maxlength="7" size="8" placeholder="#rrggbb" /></span></span>',
			esc_attr( $id ),
			esc_html__( 'Custom colour', 'volumina' ),
			esc_attr( $name . '[custom]' ),
			esc_attr( $custom )
		);
	}

	/**
	 * Draws the control.
	 *
	 * @param string $name  The name attribute, already namespaced by the group.
	 * @param mixed  $value What it currently holds.
	 */
	public function render( string $name, $value ): void {
		$id = 'volumina-setting-' . $this->key();

		switch ( $this->args['type'] ) {
			case 'checkbox':
				printf(
					'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s /> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (bool) $value, true, false ),
					esc_html( $this->description() )
				);

				// The description is the checkbox's own label; it is not
				// repeated underneath, where it would read as an echo.
				return;

			case 'number':
				printf(
					'<input type="number" step="1" class="small-text" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) (int) $value )
				);
				break;

			case 'textarea':
				printf(
					'<textarea class="large-text" rows="4" id="%1$s" name="%2$s">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( (string) $value )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );

				foreach ( $this->choices() as $choice => $label ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( (string) $choice ),
						selected( (string) $value, (string) $choice, false ),
						esc_html( (string) $label )
					);
				}

				echo '</select>';
				break;

			case 'radio':
				// The row heading is plain text for a fieldset, so the group is named
				// here, for screen readers, and only here.
				printf( '<fieldset><legend class="screen-reader-text">%s</legend>', esc_html( $this->label() ) );

				foreach ( $this->choices() as $choice => $label ) {
					printf(
						'<label style="display:block"><input type="radio" name="%1$s" value="%2$s"%3$s /> %4$s</label>',
						esc_attr( $name ),
						esc_attr( (string) $choice ),
						checked( (string) $value, (string) $choice, false ),
						esc_html( (string) $label )
					);
				}

				echo '</fieldset>';
				break;

			case 'swatches':
				// Radios with a colour beside each label. The colour is decoration:
				// the label carries the meaning, so nothing depends on seeing it.
				// The row heading is plain text for a fieldset, so the group is named
				// here, for screen readers, and only here.
				printf( '<fieldset><legend class="screen-reader-text">%s</legend>', esc_html( $this->label() ) );

				foreach ( $this->choices() as $choice => $swatch ) {
					$color = is_array( $swatch ) ? (string) ( $swatch['color'] ?? '' ) : '';
					$label = is_array( $swatch ) ? (string) ( $swatch['label'] ?? $choice ) : (string) $swatch;

					printf(
						'<label style="display:block;margin-bottom:4px"><input type="radio" name="%1$s" value="%2$s"%3$s /> <span aria-hidden="true" style="display:inline-block;width:1em;height:1em;vertical-align:-0.15em;border-radius:50%%;border:1px solid rgba(0,0,0,.25);background:%4$s"></span> %5$s</label>',
						esc_attr( $name ),
						esc_attr( (string) $choice ),
						checked( (string) $value, (string) $choice, false ),
						esc_attr( '' === $color ? 'transparent' : $color ),
						esc_html( $label )
					);
				}

				echo '</fieldset>';
				break;

			case 'color_select':
				$this->render_color( $id, $name, $value );
				break;

			case 'cards':
				// Radios drawn as cards, each with a picture of what it chooses.
				// The picture is decoration (empty alt): the name under it is the
				// label, so nothing depends on seeing it. Arrow keys move between
				// the choices, as they do for any radio group.
				printf( '<fieldset class="volumina-card-choices"><legend class="screen-reader-text">%s</legend>', esc_html( $this->label() ) );

				foreach ( $this->choices() as $choice => $card ) {
					$label = is_array( $card ) ? (string) ( $card['label'] ?? $choice ) : (string) $card;
					$image = is_array( $card ) ? (string) ( $card['image'] ?? '' ) : '';

					printf(
						'<label class="volumina-card-choice"><span class="volumina-card-choice-picture">%4$s</span><span class="volumina-card-choice-name"><input type="radio" name="%1$s" value="%2$s"%3$s /> %5$s</span></label>',
						esc_attr( $name ),
						esc_attr( (string) $choice ),
						checked( (string) $value, (string) $choice, false ),
						'' !== $image ? sprintf( '<img src="%s" alt="" width="240" height="150" loading="lazy" decoding="async" />', esc_url( $image ) ) : '',
						esc_html( $label )
					);
				}

				echo '</fieldset>';
				break;

			default:
				printf(
					'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
		}

		if ( '' !== $this->description() ) {
			printf( '<p class="description">%s</p>', esc_html( $this->description() ) );
		}
	}
}
