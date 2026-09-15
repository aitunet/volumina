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
	 */
	public function register(): void {
		register_setting(
			$this->option,
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
	 * Anything posted in, only known settings out.
	 *
	 * A key nobody declared is dropped rather than stored: an option that
	 * accepts whatever arrives is an option that grows things nobody put there.
	 * An absent checkbox is false, because that is what a browser does with an
	 * unticked box. Anything else absent keeps its default rather than being
	 * coerced from nothing, so a form that does not show a setting cannot
	 * quietly reset it.
	 *
	 * @param mixed $input What arrived.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$clean = array();

		foreach ( $this->fields as $field ) {
			$key = $field->key();

			$clean[ $key ] = array_key_exists( $key, $input )
				? $field->sanitize( $input[ $key ] )
				: $field->absent();
		}

		return $clean;
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
	 * @param string $section A section, or empty for every field.
	 */
	public function render_table( string $section = '' ): void {
		$values = $this->all();

		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $this->fields as $field ) {
			if ( '' !== $section && $section !== $field->section() ) {
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
