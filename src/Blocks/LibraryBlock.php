<?php
/**
 * The Audiobook list block.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Blocks;

use TUNET\Volumina\Frontend\Audiobook;
use TUNET\Volumina\Frontend\Templates;
use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\PostTypes\Chapter;
use TUNET\Volumina\PostTypes\Taxonomies;
use TUNET\Volumina\Support\Duration;
use WP_Post;
use WP_Query;

use const TUNET\Volumina\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * The library: the published books in rows, each a way to its own page.
 *
 * The other blocks show one book, or the books one listener has started.
 * This one shows what there is to listen to.
 */
final class LibraryBlock {

	/**
	 * Books shown when the block does not say.
	 */
	private const DEFAULT_COUNT = 12;

	/**
	 * The most a block will list. A library page with more wants pagination,
	 * which is in the backlog, not a longer page.
	 */
	private const MAX_COUNT = 50;

	/**
	 * Renders the block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public static function render( array $attributes = array() ): string {
		$args = self::query_args( $attributes );

		/**
		 * Filters the query the library block runs.
		 *
		 * The arguments of the `WP_Query` that fetches the books, after the
		 * block's own attributes have been applied: post type, status, count,
		 * order and any genre or series. An extension can change the order,
		 * exclude books, or add its own clauses.
		 *
		 * @param array<string, mixed> $args       `WP_Query` arguments.
		 * @param array<string, mixed> $attributes The block's attributes.
		 */
		$args = (array) apply_filters( 'volumina_library_query', $args, $attributes );

		$items = array();

		foreach ( ( new WP_Query( $args ) )->posts as $book ) {
			if ( $book instanceof WP_Post && Book::POST_TYPE === $book->post_type ) {
				$items[] = self::item( $book, $attributes );
			}
		}

		// An editor previewing the block should see what it is, not an empty
		// space; a reader with an empty library should see nothing at all.
		$editing = wp_is_serving_rest_request() && current_user_can( 'edit_posts' );

		if ( array() === $items && ! $editing ) {
			return '';
		}

		$covers = ! empty( $attributes['showCovers'] );
		$wrap   = Templates::attributes(
			Templates::resolve(
				array(
					'template' => (string) ( $attributes['template'] ?? '' ),
					'accent'   => (string) ( $attributes['accent'] ?? '' ),
				)
			),
			false
		);

		ob_start();

		require plugin_dir_path( PLUGIN_FILE ) . 'templates/library.php';

		return (string) ob_get_clean();
	}

	/**
	 * The `WP_Query` arguments the block's attributes describe.
	 *
	 * Pure on purpose: it reads attributes and writes arguments, touching
	 * nothing else, so it can be tested without a WordPress.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return array<string, mixed>
	 */
	public static function query_args( array $attributes ): array {
		$count = self::DEFAULT_COUNT;

		if ( isset( $attributes['count'] ) && is_numeric( $attributes['count'] ) ) {
			$count = max( 1, min( self::MAX_COUNT, (int) $attributes['count'] ) );
		}

		$newest = isset( $attributes['orderBy'] ) && 'date' === $attributes['orderBy'];

		$args = array(
			'post_type'           => Book::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => $count,
			'orderby'             => $newest ? 'date' : 'title',
			'order'               => $newest ? 'DESC' : 'ASC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		$tax_query = array();

		foreach ( array(
			'genre'  => Taxonomies::GENRE,
			'series' => Taxonomies::SERIES,
		) as $key => $taxonomy ) {
			$term = self::term_id( $attributes[ $key ] ?? null );

			if ( $term > 0 ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term,
				);
			}
		}

		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		if ( array() !== $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- A library has tens of books; the join is nothing.
		}

		return $args;
	}

	/**
	 * A positive term id, or zero for anything that is not one.
	 *
	 * @param mixed $value The attribute's value.
	 */
	private static function term_id( $value ): int {
		if ( ! is_int( $value ) && ! ( is_string( $value ) && ctype_digit( $value ) ) ) {
			return 0;
		}

		return max( 0, (int) $value );
	}

	/**
	 * What one row shows for a book.
	 *
	 * @param WP_Post              $book       The book.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return array{book: WP_Post, title: string, url: string, cover: int, lines: array<int, string>}
	 */
	private static function item( WP_Post $book, array $attributes ): array {
		$lines = array();

		if ( ! empty( $attributes['showNarrator'] ) ) {
			$narrator = (string) get_post_meta( $book->ID, 'volumina_narrator', true );

			if ( '' !== $narrator ) {
				/* translators: %s: the narrator's name. */
				$lines[] = sprintf( __( 'Narrated by %s', 'volumina' ), $narrator );
			}
		}

		if ( ! empty( $attributes['showDuration'] ) ) {
			// The stated total when there is one; the chapters added up otherwise.
			// Chapters are fetched only when the running time is wanted.
			$total = Audiobook::total_duration( $book, self::chapters_if_needed( $book ) );

			if ( $total > 0 ) {
				$lines[] = Duration::format( $total );
			}
		}

		$item = array(
			'book'  => $book,
			'title' => get_the_title( $book ),
			'url'   => (string) get_permalink( $book ),
			'cover' => ! empty( $attributes['showCovers'] ) ? Book::cover_id( $book->ID ) : 0,
			'lines' => $lines,
		);

		/**
		 * Filters what a row of the library block shows for a book.
		 *
		 * `lines` is what appears under the title, in order; each entry is
		 * plain text and is escaped when printed. An extension can add a
		 * line — a price, "Yours", a badge — change the title or the URL, or
		 * drop the cover by setting it to zero.
		 *
		 * @param array{book: WP_Post, title: string, url: string, cover: int, lines: array<int, string>} $item       The row.
		 * @param WP_Post                                                                                   $book       The book.
		 * @param array<string, mixed>                                                                      $attributes The block's attributes.
		 */
		// Merged over the original, so a filter that returns less than a whole
		// row — or nothing — still leaves a row.
		return array_merge( $item, (array) apply_filters( 'volumina_library_item', $item, $book, $attributes ) );
	}

	/**
	 * The book's chapters, unless its own total makes them unnecessary.
	 *
	 * @param WP_Post $book The book.
	 * @return array<int, WP_Post>
	 */
	private static function chapters_if_needed( WP_Post $book ): array {
		if ( (int) get_post_meta( $book->ID, 'volumina_total_duration', true ) > 0 ) {
			return array();
		}

		return Chapter::for_listener( $book->ID );
	}
}
