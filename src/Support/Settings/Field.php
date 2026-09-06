<?php
/**
 * One setting.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * A single setting: what it is called, what it holds, and how to draw it.
 *
 * Scaffolding. It knows nothing about what any particular setting means, which
 * is what lets the next plugin take this directory unchanged.
 */
final class Field {

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
	 *     @type string   $type        text, textarea, number, checkbox, select or radio.
	 *     @type mixed    $default     What it holds when nobody has chosen.
	 *     @type string   $description Shown under the control. Every field gets one.
	 *     @type array    $choices     value => label, for select and radio.
	 *     @type callable $sanitize    Overrides the default for the type.
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
	 * What it holds when nobody has chosen.
	 *
	 * @return mixed
	 */
	public function default_value() {
		return $this->args['default'];
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

		switch ( $this->args['type'] ) {
			case 'checkbox':
				return (bool) $value;

			case 'number':
				return (int) $value;

			case 'select':
			case 'radio':
				return array_key_exists( (string) $value, $this->args['choices'] )
					? (string) $value
					: (string) $this->default_value();

			case 'textarea':
				return sanitize_textarea_field( (string) $value );

			default:
				return sanitize_text_field( (string) $value );
		}
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

				foreach ( $this->args['choices'] as $choice => $label ) {
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
				echo '<fieldset>';

				foreach ( $this->args['choices'] as $choice => $label ) {
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
