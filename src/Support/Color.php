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

	/**
	 * The inks a panel tries, in pairs: softer than pure black and white
	 * first, the pure ones when the soft ones cannot read.
	 */
	private const PANEL_INKS = array( array( '#16181d', '#f7f7f5' ), array( '#000000', '#ffffff' ) );

	/**
	 * The ratio every text on a panel must reach: WCAG AA.
	 */
	private const AA = 4.5;

	/**
	 * A panel painted from one colour to another, and the inks that read on
	 * all of it: the text's, a softer one for labels, and a line.
	 *
	 * Every text reaches 4.5:1 on both colours and on every mix of the two,
	 * so a template may spread them as a gradient, a glow or anything
	 * between. When no ink can read on both as chosen, the first colour is
	 * moved toward the second, step by step, until one does; the second,
	 * the panel's ground, is always kept. Null when either is not a colour.
	 *
	 * @param string $start The first colour.
	 * @param string $end   The second, the ground.
	 * @return array{start: string, end: string, ink: string, soft: string, line: string}|null
	 */
	public static function panel( string $start, string $end ): ?array {
		$start = self::normalize( $start );
		$end   = self::normalize( $end );

		if ( '' === $start || '' === $end ) {
			return null;
		}

		for ( $step = 20; $step >= 0; $step-- ) {
			$first  = self::mix( $start, $end, $step / 20 );
			$points = self::points( $first, $end );
			$ink    = self::best_ink( $points );

			if ( self::worst( $ink, $points ) >= self::AA ) {
				break;
			}
		}

		$soft = $ink;

		for ( $fade = 7; $fade > 0; $fade-- ) {
			$candidate = self::mix( $end, $ink, $fade / 20 );

			if ( self::worst( $candidate, $points ) >= self::AA ) {
				$soft = $candidate;
				break;
			}
		}

		return array(
			'start' => $first,
			'end'   => $end,
			'ink'   => $ink,
			'soft'  => $soft,
			'line'  => self::mix( $end, $ink, 0.78 ),
		);
	}

	/**
	 * Two colours mixed in sRGB, as CSS `color-mix(in srgb, …)` does.
	 *
	 * @param string $one   A colour `normalize()` accepts.
	 * @param string $two   Another.
	 * @param float  $share How much of `$one`, 0 to 1.
	 */
	public static function mix( string $one, string $two, float $share ): string {
		$share = max( 0.0, min( 1.0, $share ) );
		$a     = self::channels( $one );
		$b     = self::channels( $two );
		$out   = '#';

		for ( $i = 0; $i < 3; $i++ ) {
			$out .= str_pad( dechex( (int) round( $a[ $i ] * $share + $b[ $i ] * ( 1 - $share ) ) ), 2, '0', STR_PAD_LEFT );
		}

		return $out;
	}

	/**
	 * Both colours and nineteen mixes between them: where text may sit.
	 *
	 * @param string $one A `#rrggbb`.
	 * @param string $two Another.
	 * @return array<int, string>
	 */
	private static function points( string $one, string $two ): array {
		$points = array();

		for ( $i = 0; $i <= 20; $i++ ) {
			$points[] = self::mix( $one, $two, $i / 20 );
		}

		return $points;
	}

	/**
	 * The ink that reads best on every point: the softer pair when one of
	 * them reaches AA, the pure pair otherwise.
	 *
	 * @param array<int, string> $points From `points()`.
	 */
	private static function best_ink( array $points ): string {
		$best = '';

		foreach ( self::PANEL_INKS as $pair ) {
			$best = self::worst( $pair[0], $points ) >= self::worst( $pair[1], $points ) ? $pair[0] : $pair[1];

			if ( self::worst( $best, $points ) >= self::AA ) {
				return $best;
			}
		}

		return $best;
	}

	/**
	 * The lowest contrast an ink has on any of the points.
	 *
	 * @param string             $ink    The ink.
	 * @param array<int, string> $points From `points()`.
	 */
	private static function worst( string $ink, array $points ): float {
		$worst = 21.0;

		foreach ( $points as $point ) {
			$worst = min( $worst, self::contrast( $ink, $point ) );
		}

		return $worst;
	}

	/**
	 * A colour as its three channels, 0 to 255; black for anything else.
	 *
	 * @param string $hex The colour.
	 * @return array{int, int, int}
	 */
	private static function channels( string $hex ): array {
		$hex = ltrim( self::normalize( $hex ), '#' );

		if ( '' === $hex ) {
			return array( 0, 0, 0 );
		}

		return array( (int) hexdec( substr( $hex, 0, 2 ) ), (int) hexdec( substr( $hex, 2, 2 ) ), (int) hexdec( substr( $hex, 4, 2 ) ) );
	}
}
