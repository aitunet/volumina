<?php
/**
 * The Continue listening block.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Blocks;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Storage\Progress;
use TUNET\Volumina\Support\Duration;
use WP_Post;

use const TUNET\Volumina\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * The books this listener has started, and where they got to.
 *
 * A signed-in listener's list is rendered here, from the progress table. A
 * guest's cannot be: their position never leaves their browser, and a page
 * that renders one guest's list is a page that cannot be cached for the next
 * one. So a guest gets an empty list and the script fills it in.
 */
final class ContinueListeningBlock {

	/**
	 * How many books to offer when the block does not say.
	 */
	private const DEFAULT_COUNT = 3;

	/**
	 * Renders the block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public static function render( array $attributes = array() ): string {
		$count  = isset( $attributes['count'] ) ? (int) $attributes['count'] : self::DEFAULT_COUNT;
		$count  = max( 1, min( 10, $count ) );
		$covers = ! empty( $attributes['showCovers'] );
		$items  = is_user_logged_in() ? self::items( get_current_user_id(), $count ) : array();

		// A guest's list is written by the script; an empty one here is not the
		// same as "nothing started", so the empty notice waits for the script.
		$guest = ! is_user_logged_in();

		// An editor previewing the block should see what it is, not an empty
		// space; a reader with nothing started should see nothing at all.
		$editing = wp_is_serving_rest_request() && current_user_can( 'edit_posts' );

		if ( ! $guest && array() === $items && ! $editing ) {
			return '';
		}

		ob_start();

		require plugin_dir_path( PLUGIN_FILE ) . 'templates/continue.php';

		return (string) ob_get_clean();
	}

	/**
	 * The listener's places, as much of them as still resolve to a book.
	 *
	 * @param int $user_id Listener.
	 * @param int $count   How many to show.
	 * @return array<int, array{book: WP_Post, chapter: string, position: string}>
	 */
	private static function items( int $user_id, int $count ): array {
		$items = array();

		// More rows than places: a row whose book has been deleted or
		// unpublished should not cost the listener a line in the list.
		foreach ( Progress::recent( $user_id, $count * 2 + 5 ) as $row ) {
			if ( count( $items ) >= $count ) {
				break;
			}

			$book = get_post( $row['book_id'] );

			if ( ! $book instanceof WP_Post || Book::POST_TYPE !== $book->post_type || 'publish' !== $book->post_status ) {
				continue;
			}

			$chapter = get_post( $row['chapter_id'] );

			$items[] = array(
				'book'     => $book,
				'chapter'  => $chapter instanceof WP_Post ? get_the_title( $chapter ) : '',
				'position' => Duration::format( $row['position_seconds'] ),
			);
		}

		return $items;
	}
}
