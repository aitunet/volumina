<?php
/**
 * Tests for the library block's query.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Blocks;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Blocks\LibraryBlock;

/**
 * The block's attributes become a `WP_Query`, and only a sound one: counts
 * within bounds, an order it knows, terms it was really given.
 */
final class LibraryBlockTest extends TestCase {

	/**
	 * Nothing chosen: twelve published books by title, no taxonomy at all.
	 */
	public function test_defaults(): void {
		$args = LibraryBlock::query_args( array() );

		$this->assertSame( 'volumina_book', $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 12, $args['posts_per_page'] );
		$this->assertSame( 'title', $args['orderby'] );
		$this->assertSame( 'ASC', $args['order'] );
		$this->assertTrue( $args['no_found_rows'] );
		$this->assertArrayNotHasKey( 'tax_query', $args );
	}

	/**
	 * The count stays between one and fifty, whatever arrives.
	 *
	 * @dataProvider count_cases
	 *
	 * @param mixed $input    What the attribute holds.
	 * @param int   $expected What is queried.
	 */
	public function test_count_is_bounded( $input, int $expected ): void {
		$args = LibraryBlock::query_args( array( 'count' => $input ) );

		$this->assertSame( $expected, $args['posts_per_page'] );
	}

	/**
	 * Counts, in and out of range.
	 *
	 * @return array<string, array{0: mixed, 1: int}>
	 */
	public static function count_cases(): array {
		return array(
			'in range'    => array( 5, 5 ),
			'zero'        => array( 0, 1 ),
			'negative'    => array( -3, 1 ),
			'too many'    => array( 500, 50 ),
			'a string'    => array( '7', 7 ),
			'not a count' => array( 'many', 12 ),
		);
	}

	/**
	 * Newest first is the other order; anything else is by title.
	 */
	public function test_order(): void {
		$newest = LibraryBlock::query_args( array( 'orderBy' => 'date' ) );
		$this->assertSame( 'date', $newest['orderby'] );
		$this->assertSame( 'DESC', $newest['order'] );

		$unknown = LibraryBlock::query_args( array( 'orderBy' => 'menu_order' ) );
		$this->assertSame( 'title', $unknown['orderby'] );
		$this->assertSame( 'ASC', $unknown['order'] );
	}

	/**
	 * A genre, a series, or both, by term id; both together must both hold.
	 */
	public function test_taxonomies(): void {
		$genre = LibraryBlock::query_args( array( 'genre' => 5 ) );
		$this->assertSame(
			array(
				array(
					'taxonomy' => 'volumina_genre',
					'field'    => 'term_id',
					'terms'    => 5,
				),
			),
			$genre['tax_query']
		);

		$both = LibraryBlock::query_args(
			array(
				'genre'  => 5,
				'series' => '7',
			)
		);
		$this->assertSame( 'AND', $both['tax_query']['relation'] );
		$this->assertCount( 3, $both['tax_query'] );
		$this->assertSame( 'volumina_series', $both['tax_query'][1]['taxonomy'] );
		$this->assertSame( 7, $both['tax_query'][1]['terms'] );
	}

	/**
	 * The editor's preview sends attributes through REST, where a `number`
	 * comes back as a float. A whole float is the same id as the integer.
	 */
	public function test_a_term_id_may_arrive_as_a_float(): void {
		$args = LibraryBlock::query_args(
			array(
				'genre'  => 2.0,
				'series' => 7.0,
			)
		);

		$this->assertSame( 2, $args['tax_query'][0]['terms'] );
		$this->assertSame( 7, $args['tax_query'][1]['terms'] );

		// A fraction is no id at all.
		$this->assertArrayNotHasKey( 'tax_query', LibraryBlock::query_args( array( 'genre' => 2.5 ) ) );
	}

	/**
	 * A term that is not a positive id is no term.
	 */
	public function test_a_bad_term_is_ignored(): void {
		foreach ( array( 0, -1, 'fiction', null, array( 5 ) ) as $case => $bad ) {
			$args = LibraryBlock::query_args(
				array(
					'genre'  => $bad,
					'series' => $bad,
				)
			);
			$this->assertArrayNotHasKey( 'tax_query', $args, "case $case" );
		}
	}
}
