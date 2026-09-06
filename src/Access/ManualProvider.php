<?php
/**
 * Access from a grant made by hand.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Access;

use TUNET\Volumina\Storage\Grants;

defined( 'ABSPATH' ) || exit;

/**
 * Answers from the grants table: somebody was given this book.
 *
 * It is the whole of the free plugin's answer to "how do I let one person hear
 * a restricted book" — a librarian, a proof listener, a backer. Selling one is
 * Pro's business, and Pro registers its own provider for it rather than
 * writing rows here.
 *
 * No grant means no opinion, never a refusal: this provider knows nothing about
 * why anyone else might let the listener in.
 */
final class ManualProvider implements AccessProvider {

	/**
	 * A short machine name.
	 */
	public function id(): string {
		return 'manual';
	}

	/**
	 * What to call it where a person can see it.
	 */
	public function label(): string {
		return __( 'Granted by hand', 'volumina' );
	}

	/**
	 * Whether this listener may hear this book.
	 *
	 * @param int $user_id Listener, or 0 for somebody not signed in.
	 * @param int $book_id The book.
	 */
	public function can_listen( int $user_id, int $book_id ): ?bool {
		if ( $user_id <= 0 ) {
			// A grant is made to an account. Somebody who is not signed in
			// cannot be the person it was made to.
			return null;
		}

		return Grants::has( $user_id, $book_id ) ? true : null;
	}
}
