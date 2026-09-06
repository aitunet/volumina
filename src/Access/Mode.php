<?php
/**
 * A book's access mode.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Access;

defined( 'ABSPATH' ) || exit;

/**
 * Whether a book is open to everyone or kept for listeners who have been given it.
 *
 * The mode is a statement about the book, not a decision about a listener: it
 * says what the publisher intends, and `AccessManager` works out what follows
 * for the person asking. Pro reads the same meta rather than inventing one of
 * its own, which is why this is part of the public API.
 */
final class Mode {

	/**
	 * The meta key holding a book's mode.
	 */
	public const META_KEY = 'volumina_access';

	/**
	 * Anyone may listen.
	 */
	public const PUBLIC = 'public';

	/**
	 * Only listeners a provider vouches for may listen.
	 */
	public const RESTRICTED = 'restricted';

	/**
	 * The modes a book may be in.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array( self::PUBLIC, self::RESTRICTED );
	}

	/**
	 * A mode's name, for a person choosing one.
	 *
	 * @return array<string, string>
	 */
	public static function labels(): array {
		return array(
			self::PUBLIC     => __( 'Public — anyone can listen', 'volumina' ),
			self::RESTRICTED => __( 'Restricted — only listeners who have been given it', 'volumina' ),
		);
	}

	/**
	 * The mode a book is in.
	 *
	 * A book with nothing stored is public. Every book that existed before this
	 * meta did is therefore public, which is what it already was.
	 *
	 * @param int $book_id The book.
	 */
	public static function of( int $book_id ): string {
		return self::sanitize( (string) get_post_meta( $book_id, self::META_KEY, true ) );
	}

	/**
	 * Anything in, a mode out.
	 *
	 * Anything unrecognised becomes public rather than restricted: a typo in a
	 * mode should not silently take a published book away from its listeners.
	 *
	 * @param mixed $value Candidate mode.
	 */
	public static function sanitize( $value ): string {
		return self::RESTRICTED === $value ? self::RESTRICTED : self::PUBLIC;
	}
}
