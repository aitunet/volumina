<?php
/**
 * Tests for a settings field.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Support\Settings;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Support\Settings\Field;

/**
 * What a field accepts, and what it refuses to store.
 *
 * Only the types that need no WordPress are covered here: the text types lean
 * on `sanitize_text_field`, which belongs to a running install. The types that
 * are checked are the ones where the field itself is the safeguard.
 */
final class FieldTest extends TestCase {

	/**
	 * A checkbox holds true or false, whatever arrives.
	 *
	 * @dataProvider checkbox_cases
	 *
	 * @param mixed $input    What arrives.
	 * @param bool  $expected What is stored.
	 */
	public function test_checkbox( $input, bool $expected ): void {
		$field = new Field(
			array(
				'key'  => 'logging',
				'type' => 'checkbox',
			)
		);

		$this->assertSame( $expected, $field->sanitize( $input ) );
	}

	/**
	 * Everything a browser or a mistake can send to a checkbox.
	 *
	 * @return array<string, array{mixed, bool}>
	 */
	public function checkbox_cases(): array {
		return array(
			'ticked'           => array( '1', true ),
			'unticked'         => array( false, false ),
			'absent'           => array( '', false ),
			'zero as a string' => array( '0', false ),
			'nonsense'         => array( 'yes please', true ),
		);
	}

	/**
	 * A number is a whole number.
	 */
	public function test_number(): void {
		$field = new Field(
			array(
				'key'  => 'keep',
				'type' => 'number',
			)
		);

		$this->assertSame( 12, $field->sanitize( '12' ) );
		$this->assertSame( 12, $field->sanitize( 12.9 ) );
		$this->assertSame( 0, $field->sanitize( 'twelve' ) );
	}

	/**
	 * A choice field can only ever hold one of its choices.
	 *
	 * This is the one that matters: it is what stops a posted value nobody
	 * offered from being stored and then acted on later.
	 */
	public function test_a_choice_field_refuses_anything_else(): void {
		$field = new Field(
			array(
				'key'     => 'default_access',
				'type'    => 'radio',
				'default' => 'public',
				'choices' => array(
					'public'     => 'Public',
					'restricted' => 'Restricted',
				),
			)
		);

		$this->assertSame( 'restricted', $field->sanitize( 'restricted' ) );
		$this->assertSame( 'public', $field->sanitize( 'public' ) );
		$this->assertSame( 'public', $field->sanitize( 'something else' ) );
		$this->assertSame( 'public', $field->sanitize( '' ) );
	}

	/**
	 * A field may bring its own rule, and then it is the only one applied.
	 */
	public function test_a_custom_rule_wins(): void {
		$field = new Field(
			array(
				'key'      => 'shout',
				'sanitize' => static fn( $value ) => strtoupper( (string) $value ),
			)
		);

		$this->assertSame( 'QUIET', $field->sanitize( 'quiet' ) );
	}

	/**
	 * A field knows what it is called and what it falls back to.
	 */
	public function test_it_reports_itself(): void {
		$field = new Field(
			array(
				'key'         => 'logging',
				'label'       => 'Log',
				'default'     => false,
				'description' => 'Record notable events.',
			)
		);

		$this->assertSame( 'logging', $field->key() );
		$this->assertSame( 'Log', $field->label() );
		$this->assertSame( 'Record notable events.', $field->description() );
		$this->assertFalse( $field->default_value() );
	}
}
