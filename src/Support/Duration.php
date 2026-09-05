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
