<?php
/**
 * The sections of a presentation screen, in order.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * A list of sections, each an id, a title and something that draws it.
 *
 * The welcome page fills one with its own sections and then hands it to
 * `volumina_welcome_sections`, so Pro can add a section of its own without
 * touching the page. Plain PHP with no WordPress in it, which is what makes
 * it testable and what keeps its rules simple: sections come back in the
 * order they were added, and an id names one section only — adding it again
 * replaces that section where it stands.
 *
 * This class is public API. It will not change without a major version.
 */
final class Sections {

	/**
	 * The sections, keyed by id, in insertion order.
	 *
	 * @var array<string, array{id: string, title: string, render: callable}>
	 */
	private array $sections = array();

	/**
	 * Adds a section, or replaces the one already under that id.
	 *
	 * @param string   $id     A key for the section: a slug, used in the markup.
	 * @param string   $title  The section's heading, already translated, not yet escaped.
	 * @param callable $render Echoes the section's body. Everything it echoes must be escaped.
	 *
	 * @throws InvalidArgumentException When the id is empty.
	 */
	public function add( string $id, string $title, callable $render ): void {
		$id = trim( $id );

		if ( '' === $id ) {
			throw new InvalidArgumentException( 'A section needs an id.' );
		}

		$this->sections[ $id ] = array(
			'id'     => $id,
			'title'  => $title,
			'render' => $render,
		);
	}

	/**
	 * Whether a section is registered under this id.
	 *
	 * @param string $id The id.
	 */
	public function has( string $id ): bool {
		return isset( $this->sections[ trim( $id ) ] );
	}

	/**
	 * Every section, in the order it was added.
	 *
	 * @return array<int, array{id: string, title: string, render: callable}>
	 */
	public function all(): array {
		return array_values( $this->sections );
	}
}
