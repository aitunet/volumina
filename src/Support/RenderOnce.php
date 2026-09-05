<?php
/**
 * Rendering something at most once per request.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Remembers what has already been rendered during this request.
 *
 * Two things can want to render the same thing on one page — a filter and a
 * block, say — and neither can know which of them runs first. Whichever claims
 * a key first renders; the other stands down. Knows nothing about audiobooks;
 * part of the scaffolding that leaves with `Support/`.
 */
final class RenderOnce {

	/**
	 * Keys claimed so far in this request.
	 *
	 * @var array<string, true>
	 */
	private static array $claimed = array();

	/**
	 * Claims a key, returning whether the caller is the one that should render.
	 *
	 * @param string $key Anything that identifies the thing being rendered.
	 */
	public static function claim( string $key ): bool {
		if ( isset( self::$claimed[ $key ] ) ) {
			return false;
		}

		self::$claimed[ $key ] = true;

		return true;
	}

	/**
	 * Whether a key has been claimed, without claiming it.
	 *
	 * @param string $key The key.
	 */
	public static function taken( string $key ): bool {
		return isset( self::$claimed[ $key ] );
	}

	/**
	 * Forgets everything. For tests, and for nothing else.
	 */
	public static function reset(): void {
		self::$claimed = array();
	}
}
