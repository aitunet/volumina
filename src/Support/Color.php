<?php
/**
 * Colour arithmetic.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Hex colours, and the little of WCAG needed to put ink on one.
 *
 * Knows nothing about themes, palettes or players: it takes a hex string and
 * answers. Everything here is arithmetic, which is what makes it testable.
 */
final class Color {

	/**
	 * A hex colour in its canonical spelling: lower case, six digits, a hash.
	 *
	 * Three-digit and hashless spellings are accepted; anything else — named
	 * colours, `rgb()`, CSS variables, eight-digit hex, anything with extra
	 * characters — comes back as an empty string, so a value that reached the
	 * page unfiltered could never carry more than a colour.
	 *
	 * @param string $hex The candidate.
	 */
	public static function normalize( string $hex ): string {
		$hex = strtolower( ltrim( trim( $hex ), '#' ) );

		if ( 1 === preg_match( '/^[0-9a-f]{3}$/', $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return 1 === preg_match( '/^[0-9a-f]{6}$/', $hex ) ? '#' . $hex : '';
	}

	/**
	 * Relative luminance, 0 for black to 1 for white, per WCAG 2.
	 *
	 * @param string $hex A colour `normalize()` accepts.
	 */
	public static function luminance( string $hex ): float {
		$hex = self::normalize( $hex );

		if ( '' === $hex ) {
			return 0.0;
		}

		$channel = static function ( string $pair ): float {
			$c = hexdec( $pair ) / 255;

			return $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
		};

		return 0.2126 * $channel( substr( $hex, 1, 2 ) )
			+ 0.7152 * $channel( substr( $hex, 3, 2 ) )
			+ 0.0722 * $channel( substr( $hex, 5, 2 ) );
	}

	/**
	 * Contrast ratio between two colours, 1 to 21, per WCAG 2.
	 *
	 * @param string $one The first colour.
	 * @param string $two The second.
	 */
	public static function contrast( string $one, string $two ): float {
		$a = self::luminance( $one );
		$b = self::luminance( $two );

		return ( max( $a, $b ) + 0.05 ) / ( min( $a, $b ) + 0.05 );
	}

	/**
	 * The ink to write on a colour: black or white, whichever contrasts more.
	 *
	 * One of the two always reaches 4.5:1 — the worst case is a mid-grey,
	 * where both sit just above it — so anything drawn in this ink on that
	 * colour meets AA without knowing the colour in advance.
	 *
	 * @param string $on The background. Not a colour, and there is no ink.
	 */
	public static function ink( string $on ): string {
		if ( '' === self::normalize( $on ) ) {
			return '';
		}

		return self::contrast( $on, '#000000' ) >= self::contrast( $on, '#ffffff' ) ? '#000000' : '#ffffff';
	}
}
