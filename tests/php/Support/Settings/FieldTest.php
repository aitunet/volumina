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
	 * A crafted form can send `name[]` where a string was expected. That is
	 * not a value; it is treated as the field having been left out.
	 */
	public function test_an_array_where_a_value_belongs_is_absence(): void {
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

		$this->assertSame( $field->absent(), $field->sanitize( array( 'restricted' ) ) );
		$this->assertSame( $field->absent(), $field->sanitize( new \stdClass() ) );
	}

	/**
	 * Swatches are a choice field too: the value is a key, never a colour,
	 * and a key nobody offered falls back to the default.
	 */
	public function test_swatches_hold_a_key_not_a_colour(): void {
		$field = new Field(
			array(
				'key'     => 'accent',
				'type'    => 'swatches',
				'default' => '',
				'choices' => array(
					''      => array( 'label' => 'None' ),
					'amber' => array(
						'label' => 'Amber',
						'color' => '#e0a83a',
					),
				),
			)
		);

		$this->assertSame( 'amber', $field->sanitize( 'amber' ) );
		$this->assertSame( '', $field->sanitize( '#e0a83a' ) );
		$this->assertSame( '', $field->sanitize( 'red' ) );
	}

	/**
	 * Choices may be a callable, for lists that depend on the theme; it is
	 * called once, when first needed, and its answer kept.
	 */
	public function test_choices_may_be_resolved_late(): void {
		$calls = 0;
		$field = new Field(
			array(
				'key'     => 'template',
				'type'    => 'radio',
				'default' => 'basic',
				'choices' => static function () use ( &$calls ): array {
					++$calls;

					return array(
						'basic' => 'Basic',
						'paper' => 'Paper',
					);
				},
			)
		);

		$this->assertSame( 0, $calls );
		$this->assertSame( 'paper', $field->sanitize( 'paper' ) );
		$this->assertSame( 'basic', $field->sanitize( 'night' ) );
		$this->assertSame( array( 'basic', 'paper' ), array_keys( $field->choices() ) );
		$this->assertSame( 1, $calls );
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

	/**
	 * A cards field keeps a known choice and falls back to its default otherwise.
	 */
	public function test_cards_keep_a_known_choice(): void {
		$field = new Field(
			array(
				'key'     => 'template',
				'type'    => 'cards',
				'default' => 'basic',
				'choices' => array(
					'basic' => array(
						'label' => 'Basic',
						'image' => '',
					),
					'pulse' => array(
						'label' => 'Pulse',
						'image' => 'https://example.com/p.png',
					),
				),
			)
		);

		$this->assertSame( 'pulse', $field->sanitize( 'pulse' ) );
		$this->assertSame( 'basic', $field->sanitize( 'nope' ) );
		$this->assertTrue( $field->labels_itself() );
	}

	/**
	 * A colour field offering two named colours and a custom one.
	 */
	private function colour_field(): Field {
		return new Field(
			array(
				'key'     => 'accent',
				'type'    => 'color_select',
				'default' => '',
				'choices' => array(
					''      => array( 'label' => 'Template' ),
					'amber' => array(
						'label' => 'Amber',
						'color' => '#e0a83a',
					),
				),
			)
		);
	}

	/**
	 * From the form: a named choice is kept as its key.
	 */
	public function test_a_colour_field_keeps_a_named_choice(): void {
		$field = $this->colour_field();

		$this->assertSame(
			'amber',
			$field->sanitize(
				array(
					'choice' => 'amber',
					'custom' => '#123456',
				)
			)
		);
	}

	/**
	 * From the form: Custom stores the picked colour, spelled one way.
	 */
	public function test_a_colour_field_stores_a_custom_colour(): void {
		$field = $this->colour_field();

		$this->assertSame(
			'#aabbcc',
			$field->sanitize(
				array(
					'choice' => 'custom',
					'custom' => 'ABC',
				)
			)
		);
	}

	/**
	 * Custom with no colour, or with something that is not one, stores the default.
	 */
	public function test_a_colour_field_refuses_a_custom_non_colour(): void {
		$field = $this->colour_field();

		$this->assertSame(
			'',
			$field->sanitize(
				array(
					'choice' => 'custom',
					'custom' => 'red;background:url(x)',
				)
			)
		);
		$this->assertSame( '', $field->sanitize( array( 'choice' => 'custom' ) ) );
		$this->assertSame( '', $field->sanitize( array( 'choice' => 'nope' ) ) );
	}

	/**
	 * What was stored comes back through as it is: a key, or a colour.
	 */
	public function test_a_colour_field_keeps_what_it_stored(): void {
		$field = $this->colour_field();

		$this->assertSame( 'amber', $field->sanitize( 'amber' ) );
		$this->assertSame( '#123456', $field->sanitize( '#123456' ) );
		$this->assertSame( '', $field->sanitize( 'custom' ) );
		$this->assertSame( '', $field->sanitize( 'nope' ) );
		$this->assertFalse( $field->labels_itself() );
	}
}
