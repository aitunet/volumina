<?php
/**
 * Tests for the welcome page's sections.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Admin;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Admin\Sections;

/**
 * Sections keep the order they were added in, and an id names one section only.
 */
final class SectionsTest extends TestCase {

	/**
	 * A fresh container has nothing in it.
	 */
	public function test_starts_empty(): void {
		$this->assertSame( array(), ( new Sections() )->all() );
	}

	/**
	 * Sections come back in the order they were added, with what was given.
	 */
	public function test_keeps_insertion_order(): void {
		$sections = new Sections();
		$first    = static function (): void {};
		$second   = static function (): void {};

		$sections->add( 'steps', 'Three steps', $first );
		$sections->add( 'pro', 'Pro', $second );

		$this->assertSame(
			array(
				array(
					'id'     => 'steps',
					'title'  => 'Three steps',
					'render' => $first,
				),
				array(
					'id'     => 'pro',
					'title'  => 'Pro',
					'render' => $second,
				),
			),
			$sections->all()
		);
	}

	/**
	 * Adding an id again replaces that section where it stands, rather than
	 * appending a second one under the same name.
	 */
	public function test_same_id_replaces_in_place(): void {
		$sections = new Sections();
		$render   = static function (): void {};

		$sections->add( 'steps', 'Three steps', $render );
		$sections->add( 'pro', 'Pro', $render );
		$sections->add( 'steps', 'Four steps', $render );

		$this->assertSame( array( 'steps', 'pro' ), array_column( $sections->all(), 'id' ) );
		$this->assertSame( 'Four steps', $sections->all()[0]['title'] );
	}

	/**
	 * Whether an id is taken.
	 */
	public function test_has(): void {
		$sections = new Sections();
		$sections->add( 'steps', 'Three steps', static function (): void {} );

		$this->assertTrue( $sections->has( 'steps' ) );
		$this->assertFalse( $sections->has( 'pro' ) );
	}

	/**
	 * An id is a key: it cannot be empty, and surrounding whitespace is not
	 * part of it.
	 */
	public function test_id_is_trimmed_and_never_empty(): void {
		$sections = new Sections();
		$sections->add( ' steps ', 'Three steps', static function (): void {} );

		$this->assertTrue( $sections->has( 'steps' ) );

		$this->expectException( InvalidArgumentException::class );
		$sections->add( '  ', 'Nameless', static function (): void {} );
	}
}
