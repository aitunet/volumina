<?php
/**
 * Tests for a group of settings.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Support\Settings;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Support\Settings\Field;
use TUNET\Volumina\Support\Settings\Group;

/**
 * What comes back from a form, and what is allowed into the option.
 *
 * Only fields that need no WordPress are used, so the suite stays a plain unit
 * suite: what is being tested is the group's own rules, not `sanitize_text_field`.
 */
final class GroupTest extends TestCase {

	/**
	 * A group with one of each interesting kind of field.
	 */
	private function group(): Group {
		return new Group(
			'volumina_test_settings',
			array(
				new Field(
					array(
						'key'     => 'logging',
						'type'    => 'checkbox',
						'default' => false,
					)
				),
				new Field(
					array(
						'key'     => 'keep',
						'type'    => 'number',
						'default' => 200,
					)
				),
				new Field(
					array(
						'key'     => 'default_access',
						'type'    => 'radio',
						'default' => 'public',
						'choices' => array(
							'public'     => 'Public',
							'restricted' => 'Restricted',
						),
					)
				),
			)
		);
	}

	/**
	 * The defaults are whatever the fields say they are.
	 */
	public function test_defaults(): void {
		$this->assertSame(
			array(
				'logging'        => false,
				'keep'           => 200,
				'default_access' => 'public',
			),
			$this->group()->defaults()
		);
	}

	/**
	 * A key nobody declared does not get into the option.
	 *
	 * An option that stores whatever arrives is an option that grows things
	 * nobody put there, and this is the only place that can stop it.
	 */
	public function test_undeclared_keys_are_dropped(): void {
		$clean = $this->group()->sanitize(
			array(
				'logging'   => '1',
				'is_admin'  => true,
				'something' => 'else',
			)
		);

		$this->assertArrayNotHasKey( 'is_admin', $clean );
		$this->assertArrayNotHasKey( 'something', $clean );
		$this->assertTrue( $clean['logging'] );
	}

	/**
	 * A checkbox nobody ticked is absent from the post, and that means false.
	 */
	public function test_an_absent_checkbox_is_false(): void {
		$clean = $this->group()->sanitize( array( 'keep' => '50' ) );

		$this->assertFalse( $clean['logging'] );
		$this->assertSame( 50, $clean['keep'] );
	}

	/**
	 * A field that was not on the form at all keeps what it had.
	 *
	 * Absence means "no" for a checkbox and nothing at all for everything
	 * else. Reading it as an empty value would let a form that shows three
	 * settings quietly reset the fourth.
	 */
	public function test_an_absent_number_keeps_its_default(): void {
		$clean = $this->group()->sanitize( array( 'logging' => '1' ) );

		$this->assertSame( 200, $clean['keep'] );
		$this->assertSame( 'public', $clean['default_access'] );
	}

	/**
	 * Every declared key comes back, whether it was posted or not.
	 */
	public function test_every_key_comes_back(): void {
		$clean = $this->group()->sanitize( array() );

		$this->assertSame(
			array( 'logging', 'keep', 'default_access' ),
			array_keys( $clean )
		);
	}

	/**
	 * A choice that was never offered falls back to the default.
	 */
	public function test_an_unoffered_choice_falls_back(): void {
		$clean = $this->group()->sanitize( array( 'default_access' => 'free_for_all' ) );

		$this->assertSame( 'public', $clean['default_access'] );
	}

	/**
	 * Nonsense in place of an array is an empty form, not a fatal.
	 */
	public function test_nonsense_input_is_survivable(): void {
		$clean = $this->group()->sanitize( 'not an array' );

		$this->assertSame( $this->group()->defaults(), $clean );
	}
}
