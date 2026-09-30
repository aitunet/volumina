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

	/**
	 * Sections come back in the order the fields first name them, once each,
	 * and a field with no section names none.
	 */
	public function test_sections_in_first_appearance_order(): void {
		$group = new Group(
			'volumina_test',
			array(
				new Field(
					array(
						'key'     => 'a',
						'section' => 'listening',
					)
				),
				new Field(
					array(
						'key'     => 'b',
						'section' => 'housekeeping',
					)
				),
				new Field(
					array(
						'key'     => 'c',
						'section' => 'listening',
					)
				),
				new Field( array( 'key' => 'd' ) ),
			)
		);

		$this->assertSame( array( 'listening', 'housekeeping' ), $group->sections() );
		$this->assertSame( array(), $this->group()->sections() );
	}

	/**
	 * A group whose fields sit in two sections.
	 */
	private function sectioned(): Group {
		return new Group(
			'volumina_test_settings',
			array(
				new Field(
					array(
						'key'     => 'logging',
						'section' => 'general',
						'type'    => 'checkbox',
						'default' => false,
					)
				),
				new Field(
					array(
						'key'     => 'keep',
						'section' => 'general',
						'type'    => 'number',
						'default' => 200,
					)
				),
				new Field(
					array(
						'key'     => 'template',
						'section' => 'look',
						'type'    => 'radio',
						'default' => 'basic',
						'choices' => array(
							'basic' => 'Basic',
							'pulse' => 'Pulse',
						),
					)
				),
				new Field(
					array(
						'key'     => 'bars',
						'section' => 'look',
						'type'    => 'checkbox',
						'default' => true,
					)
				),
			)
		);
	}

	/**
	 * A form that drew one section leaves the other section as it was stored.
	 */
	public function test_an_undrawn_section_keeps_what_was_stored(): void {
		$stored = array(
			'logging'  => true,
			'keep'     => 50,
			'template' => 'pulse',
			'bars'     => false,
		);
		$input  = array(
			Group::MARKER => array( 'general' ),
			'keep'        => '75',
		);

		$this->assertSame(
			array(
				'logging'  => false,
				'keep'     => 75,
				'template' => 'pulse',
				'bars'     => false,
			),
			$this->sectioned()->merge( $input, $stored )
		);
	}

	/**
	 * An unticked checkbox in an undrawn section is not read as "unticked".
	 */
	public function test_a_checkbox_in_an_undrawn_section_keeps_true(): void {
		$stored = array(
			'logging'  => false,
			'keep'     => 200,
			'template' => 'basic',
			'bars'     => true,
		);
		$input  = array( Group::MARKER => array( 'general' ) );

		$this->assertTrue( $this->sectioned()->merge( $input, $stored )['bars'] );
	}

	/**
	 * With nothing stored yet, an undrawn section gets its defaults.
	 */
	public function test_an_undrawn_section_with_nothing_stored_gets_defaults(): void {
		$input = array(
			Group::MARKER => array( 'look' ),
			'template'    => 'pulse',
			'bars'        => '1',
		);

		$this->assertSame(
			array(
				'logging'  => false,
				'keep'     => 200,
				'template' => 'pulse',
				'bars'     => true,
			),
			$this->sectioned()->merge( $input, array() )
		);
	}

	/**
	 * Without the marker, the old rule holds: absent checkbox false, other absent default.
	 */
	public function test_no_marker_behaves_as_before(): void {
		$stored = array(
			'logging'  => true,
			'keep'     => 50,
			'template' => 'pulse',
			'bars'     => true,
		);

		$this->assertSame(
			array(
				'logging'  => false,
				'keep'     => 200,
				'template' => 'basic',
				'bars'     => false,
			),
			$this->sectioned()->merge( array(), $stored )
		);
	}

	/**
	 * Already-clean data with no marker — WordPress's second sanitize pass on add_option — is kept.
	 */
	public function test_clean_data_passes_through_unchanged(): void {
		$clean = array(
			'logging'  => true,
			'keep'     => 50,
			'template' => 'pulse',
			'bars'     => false,
		);

		$this->assertSame( $clean, $this->sectioned()->merge( $clean, array() ) );
	}

	/**
	 * A marker that is present but names no real section — a string, a list of
	 * lists, an unknown name — draws nothing: every field keeps what was
	 * stored, and the marker itself is never stored.
	 */
	public function test_a_crafted_marker_keeps_what_was_stored(): void {
		$stored = array(
			'logging'  => true,
			'keep'     => 50,
			'template' => 'pulse',
			'bars'     => false,
		);

		foreach ( array( 'general', array( array( 'general' ) ), array( 'nope' ) ) as $marker ) {
			$out = $this->sectioned()->merge( array( Group::MARKER => $marker ), $stored );

			$this->assertArrayNotHasKey( Group::MARKER, $out );
			$this->assertSame( $stored, $out );
		}
	}
}
