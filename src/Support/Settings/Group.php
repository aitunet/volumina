<?php
/**
 * A group of settings stored in one option.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings that live together in a single option, and the form that edits them.
 *
 * One option holding an array rather than one option per setting: a screenful
 * of settings is read on nearly every request, and that should be one row and
 * one sanitize callback, not fifteen of each.
 *
 * The form posts to `options.php`, so WordPress does the nonce, the capability
 * and the redirect. A settings screen is not the place to reinvent those.
 *
 * Scaffolding. It knows nothing about what any of its settings mean.
 */
final class Group {

	/**
	 * The key a form uses to say which sections it drew. Never stored.
	 */
	public const MARKER = '_sections';

	/**
	 * The option this group is stored in.
	 *
	 * @var string
	 */
	private string $option;

	/**
	 * The fields, in the order they are shown.
	 *
	 * @var array<int, Field>
	 */
	private array $fields;

	/**
	 * Values read for this request.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $cache = null;

	/**
	 * Builds a group.
	 *
	 * @param string            $option The option name.
	 * @param array<int, Field> $fields Its fields.
	 */
	public function __construct( string $option, array $fields ) {
		$this->option = $option;
		$this->fields = $fields;
	}

	/**
	 * The option name.
	 */
	public function option(): string {
		return $this->option;
	}

	/**
	 * Registers the option with WordPress, so `options.php` will accept it.
	 *
	 * @param string $group The option group a form posts as `option_page`; the option's own name when empty.
	 */
	public function register( string $group = '' ): void {
		register_setting(
			'' !== $group ? $group : $this->option,
			$this->option,
			array(
				'type'              => 'object',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * What every field holds when nobody has chosen.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		$defaults = array();

		foreach ( $this->fields as $field ) {
			$defaults[ $field->key() ] = $field->default_value();
		}

		return $defaults;
	}

	/**
	 * Every value, with defaults filling the gaps.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		if ( null === $this->cache ) {
			$stored = get_option( $this->option, array() );

			$this->cache = array_merge(
				$this->defaults(),
				is_array( $stored ) ? $stored : array()
			);
		}

		return $this->cache;
	}

	/**
	 * One value.
	 *
	 * @param string $key The field key.
	 * @return mixed
	 */
	public function get( string $key ) {
		$all = $this->all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : null;
	}

	/**
	 * Forgets what was read, so the next read goes to the database.
	 */
	public function forget(): void {
		$this->cache = null;
	}

	/**
	 * Anything posted in, only known settings out. See merge().
	 *
	 * @param mixed $input What arrived.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input  = is_array( $input ) ? $input : array();
		$stored = array();

		// Only a form that drew some sections needs to know what was stored.
		if ( array_key_exists( self::MARKER, $input ) ) {
			$stored = get_option( $this->option, array() );
			$stored = is_array( $stored ) ? $stored : array();
		}

		return $this->merge( $input, $stored );
	}

	/**
	 * What to store, from what arrived and what was stored.
	 *
	 * A key nobody declared is dropped. When the form says which sections it
	 * drew (`MARKER`), a field from any other section keeps its stored value:
	 * one option can then be edited by several forms, and saving one never
	 * resets what another shows. A marker that is present but is not a list
	 * counts as an empty list, and only its string entries count as sections,
	 * so a crafted marker draws nothing and every field keeps what was stored.
	 * For a drawn section — or when there is no marker at all — an absent
	 * checkbox is false, because that is what a browser does with an unticked
	 * box, and anything else absent keeps its default.
	 *
	 * @param array<string, mixed> $input  What arrived.
	 * @param array<string, mixed> $stored What is stored now.
	 * @return array<string, mixed>
	 */
	public function merge( array $input, array $stored ): array {
		$drawn = null;

		if ( array_key_exists( self::MARKER, $input ) ) {
			$drawn = is_array( $input[ self::MARKER ] )
				? array_values( array_filter( $input[ self::MARKER ], 'is_string' ) )
				: array();
		}

		$clean = array();

		foreach ( $this->fields as $field ) {
			$key = $field->key();

			if ( null !== $drawn && ! in_array( $field->section(), $drawn, true ) ) {
				$clean[ $key ] = array_key_exists( $key, $stored )
					? $field->sanitize( $stored[ $key ] )
					: $field->default_value();
				continue;
			}

			$clean[ $key ] = array_key_exists( $key, $input )
				? $field->sanitize( $input[ $key ] )
				: $field->absent();
		}

		return $clean;
	}

	/**
	 * Tells `merge()` which sections this form drew. Print it inside the form.
	 *
	 * @param array<int, string> $sections The sections drawn.
	 */
	public function render_marker( array $sections ): void {
		foreach ( $sections as $section ) {
			printf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $this->option . '[' . self::MARKER . '][]' ),
				esc_attr( $section )
			);
		}
	}

	/**
	 * The sections the fields name, in the order they first appear.
	 *
	 * @return array<int, string>
	 */
	public function sections(): array {
		$sections = array();

		foreach ( $this->fields as $field ) {
			if ( '' !== $field->section() && ! in_array( $field->section(), $sections, true ) ) {
				$sections[] = $field->section();
			}
		}

		return $sections;
	}

	/**
	 * Draws the fields as a form table: all of them, or one section's.
	 *
	 * @param string             $section A section, or empty for every field.
	 * @param array<int, string> $except  Keys not to draw.
	 */
	public function render_table( string $section = '', array $except = array() ): void {
		$values = $this->all();

		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $this->fields as $field ) {
			if ( ( '' !== $section && $section !== $field->section() ) || in_array( $field->key(), $except, true ) ) {
				continue;
			}

			$key = $field->key();

			echo '<tr>';

			// A control that labels itself — a checkbox with its text, radios with
			// their choices — gets a plain row heading: a second <label> pointed at
			// it would be read out twice, and pointed at a fieldset would name
			// nothing.
			if ( $field->labels_itself() ) {
				printf( '<th scope="row">%s</th>', esc_html( $field->label() ) );
			} else {
				printf(
					'<th scope="row"><label for="%1$s">%2$s</label></th>',
					esc_attr( 'volumina-setting-' . $key ),
					esc_html( $field->label() )
				);
			}
			echo '<td>';

			$field->render(
				$this->option . '[' . $key . ']',
				array_key_exists( $key, $values ) ? $values[ $key ] : $field->default_value()
			);

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}
}
