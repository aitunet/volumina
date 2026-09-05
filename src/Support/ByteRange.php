<?php
/**
 * HTTP byte range arithmetic.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Works out which bytes of a file a `Range` header is asking for.
 *
 * Knows nothing about audiobooks, or about audio, or about WordPress: a range
 * over a byte count is a range over a byte count. Part of the scaffolding that
 * leaves with `Support/` into the next TUNET plugin.
 */
final class ByteRange {

	/**
	 * Resolves a `Range` header against a known file size.
	 *
	 * Only a single range is honoured. Answering a multi-range request with
	 * one range is explicitly allowed by RFC 9110, and a multipart body buys a
	 * media player nothing at all.
	 *
	 * A header that cannot be parsed is treated as no header rather than as an
	 * error, which is what the specification asks for: a malformed `Range` is
	 * ignored and the whole representation is sent.
	 *
	 * @param string $header The raw header value, or an empty string.
	 * @param int    $size   Size of the file in bytes.
	 * @return array{0: int, 1: int}|null First and last byte offsets, both
	 *                                    inclusive, or null when the range is
	 *                                    unsatisfiable and the answer is 416.
	 */
	public static function parse( string $header, int $size ): ?array {
		$size = max( 0, $size );
		$last = max( 0, $size - 1 );

		if ( '' === trim( $header ) || 0 === $size ) {
			return array( 0, $last );
		}

		if ( 1 !== preg_match( '/^bytes=(\d*)-(\d*)(?:\s*,.*)?$/', trim( $header ), $matches ) ) {
			return array( 0, $last );
		}

		$from = $matches[1];
		$to   = $matches[2];

		if ( '' === $from && '' === $to ) {
			return null;
		}

		if ( '' === $from ) {
			// A suffix range: the last N bytes. Asking for the last nothing is
			// not a request for the whole file, it is a request for nothing.
			$length = (int) $to;

			if ( $length <= 0 ) {
				return null;
			}

			return array( max( 0, $size - $length ), $last );
		}

		$start = (int) $from;

		if ( $start > $last ) {
			return null;
		}

		$end = '' === $to ? $last : min( (int) $to, $last );

		if ( $end < $start ) {
			return null;
		}

		return array( $start, $end );
	}
}
