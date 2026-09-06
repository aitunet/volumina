<?php
/**
 * Access from the book itself.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Access;

use TUNET\Volumina\PostTypes\Book;

defined( 'ABSPATH' ) || exit;

/**
 * Answers from what WordPress already knows about the book.
 *
 * A published public book is for everybody. A book that is not published yet is
 * for whoever may read it in the admin, which is what makes a draft audible to
 * the person recording it. And whoever may edit a book may hear it, whatever
 * its mode: an editor who cannot play the file cannot check it.
 *
 * On a restricted book it otherwise has no opinion. Saying no there would
 * outrank every other provider, and refusing what Pro was about to allow is
 * exactly the bug this whole arrangement exists to avoid.
 */
final class PublicProvider implements AccessProvider {

	/**
	 * A short machine name.
	 */
	public function id(): string {
		return 'public';
	}

	/**
	 * What to call it where a person can see it.
	 */
	public function label(): string {
		return __( 'Public books', 'volumina' );
	}

	/**
	 * Whether this listener may hear this book.
	 *
	 * @param int $user_id Listener, or 0 for somebody not signed in.
	 * @param int $book_id The book.
	 */
	public function can_listen( int $user_id, int $book_id ): ?bool {
		if ( Book::POST_TYPE !== get_post_type( $book_id ) ) {
			return null;
		}

		if ( $user_id > 0 && user_can( $user_id, 'edit_post', $book_id ) ) {
			return true;
		}

		if ( Mode::PUBLIC !== Mode::of( $book_id ) ) {
			return null;
		}

		if ( 'publish' === get_post_status( $book_id ) ) {
			return true;
		}

		return ( $user_id > 0 && user_can( $user_id, 'read_post', $book_id ) ) ? true : null;
	}
}
