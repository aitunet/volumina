<?php
/**
 * Duration formatting.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a count of seconds into something a person can read.
 *
 * Knows nothing about audiobooks: seconds are seconds. Part of the scaffolding
 * that leaves with `Support/` into the next TUNET plugin.
 */
final class Duration {

	/**
	 * Seconds in an hour.
	 */
	private const HOUR = 3600;

	/**
	 * Seconds in a minute.
	 */
	private const MINUTE = 60;

	/**
	 * Formats seconds as an ISO 8601 duration, for a <time> element.
	 *
	 * @param int $seconds Whole seconds. Negative input is treated as zero.
	 */
	public static function iso8601( int $seconds ): string {
		$seconds = max( 0, $seconds );

		$hours   = intdiv( $seconds, self::HOUR );
		$minutes = intdiv( $seconds % self::HOUR, self::MINUTE );
		$rest    = $seconds % self::MINUTE;

		$out = 'PT';

		if ( $hours > 0 ) {
			$out .= $hours . 'H';
		}

		if ( $minutes > 0 ) {
			$out .= $minutes . 'M';
		}

		if ( $rest > 0 || 'PT' === $out ) {
			$out .= $rest . 'S';
		}

		return $out;
	}

	/**
	 * Reads a running time typed by a person: whole seconds, or `h:mm:ss`,
	 * or `m:ss`, or `mm:ss` with any number of minutes. Anything else is 0.
	 *
	 * A field that asked for seconds alone made a thirty-minute chapter read
	 * as "1800" to whoever typed it and as "30" to whoever guessed; both are
	 * fair ways of saying it, so both are read.
	 *
	 * @param string $typed What was typed.
	 */
	public static function parse( string $typed ): int {
		$typed = trim( $typed );

		if ( 1 === preg_match( '/^\d+$/', $typed ) ) {
			return (int) $typed;
		}

		if ( 1 !== preg_match( '/^(?:(\d+):)?(\d+):(\d{1,2})$/', $typed, $m ) ) {
			return 0;
		}

		$hours   = '' === $m[1] ? 0 : (int) $m[1];
		$minutes = (int) $m[2];
		$seconds = (int) $m[3];

		if ( $seconds >= self::MINUTE ) {
			return 0;
		}

		return $hours * self::HOUR + $minutes * self::MINUTE + $seconds;
	}

	/**
	 * Formats seconds as `h:mm:ss`, or `m:ss` when there is no hour to show.
	 *
	 * @param int $seconds Whole seconds. Negative input is treated as zero.
	 */
	public static function format( int $seconds ): string {
		$seconds = max( 0, $seconds );

		$hours   = intdiv( $seconds, self::HOUR );
		$minutes = intdiv( $seconds % self::HOUR, self::MINUTE );
		$rest    = $seconds % self::MINUTE;

		if ( $hours > 0 ) {
			return sprintf( '%d:%02d:%02d', $hours, $minutes, $rest );
		}

		return sprintf( '%d:%02d', $minutes, $rest );
	}
}
