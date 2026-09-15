<?php
/**
 * Tests for the template registry and the template value.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Frontend;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Frontend\Template;
use TUNET\Volumina\Frontend\TemplateRegistry;

/**
 * A template is an id, a label, a stylesheet handle and defaults; the
 * registry keeps them in order and one per id.
 */
final class TemplateRegistryTest extends TestCase {

	/**
	 * What a template carries comes back as given, and the id is a slug.
	 */
	public function test_template_carries_its_parts(): void {
		$template = new Template(
			' Papel ',
			'Paper',
			'my-plugin-paper',
			array(
				'cover'  => 'top',
				'accent' => 'amber',
			)
		);

		$this->assertSame( 'papel', $template->id() );
		$this->assertSame( 'Paper', $template->label() );
		$this->assertSame( 'my-plugin-paper', $template->style() );
		$this->assertSame(
			array(
				'cover'  => 'top',
				'accent' => 'amber',
			),
			$template->defaults()
		);
	}

	/**
	 * Defaults only hold what a template may decide: a known cover position
	 * and a palette slug. Anything else is dropped, not stored.
	 */
	public function test_defaults_are_filtered(): void {
		$template = new Template(
			'noche',
			'Night',
			'',
			array(
				'cover'  => 'floating',
				'accent' => 'Not A Slug!',
				'player' => 'giant',
			)
		);

		$this->assertSame( array(), $template->defaults() );
		$this->assertSame( '', $template->style() );
	}

	/**
	 * Ids that are not slugs are refused, empty ones included.
	 *
	 * @dataProvider bad_ids
	 *
	 * @param string $id The id.
	 */
	public function test_bad_ids_are_refused( string $id ): void {
		$this->expectException( InvalidArgumentException::class );
		new Template( $id, 'Whatever' );
	}

	/**
	 * Ids the constructor must refuse.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function bad_ids(): array {
		return array(
			'empty'        => array( '' ),
			'spaces only'  => array( '   ' ),
			'inner space'  => array( 'my template' ),
			'underscore'   => array( 'my_template' ),
			'leading dash' => array( '-paper' ),
			'markup'       => array( '<b>paper</b>' ),
		);
	}

	/**
	 * Templates come back in the order they were added, keyed by id, and the
	 * first one added is the one a site starts on.
	 */
	public function test_registry_keeps_order(): void {
		$registry = new TemplateRegistry();
		$basic    = new Template( 'basic', 'Basic', 'volumina-book' );
		$paper    = new Template( 'paper', 'Paper', 'my-paper' );

		$this->assertNull( $registry->first() );
		$this->assertSame( array(), $registry->all() );

		$registry->add( $basic );
		$registry->add( $paper );

		$this->assertSame( array( 'basic', 'paper' ), array_keys( $registry->all() ) );
		$this->assertSame( $basic, $registry->first() );
		$this->assertSame( $paper, $registry->get( 'paper' ) );
		$this->assertTrue( $registry->has( 'paper' ) );
		$this->assertFalse( $registry->has( 'night' ) );
		$this->assertNull( $registry->get( 'night' ) );
	}

	/**
	 * Adding an id again replaces that template where it stands.
	 */
	public function test_same_id_replaces_in_place(): void {
		$registry = new TemplateRegistry();
		$registry->add( new Template( 'basic', 'Basic' ) );
		$registry->add( new Template( 'paper', 'Paper' ) );
		$registry->add( new Template( 'basic', 'Basic, revised' ) );

		$this->assertSame( array( 'basic', 'paper' ), array_keys( $registry->all() ) );
		$this->assertSame( 'Basic, revised', $registry->get( 'basic' )?->label() );
	}
}
