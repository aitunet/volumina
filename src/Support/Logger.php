<?php
/**
 * A small log of notable events.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the last few hundred things worth knowing about, in one option.
 *
 * A log of **notable events** — a listener turned away, a file that has gone
 * missing, a schema brought up to date — and deliberately not a request log.
 * Two events logged in the same instant can lose one of each other, because
 * this reads the option, adds a line and writes it back. That is the trade for
 * needing no table, and it is the right trade at this volume. If this ever
 * wants to record something that happens on every request, it wants a table,
 * and this class should not be the thing that grows into one.
 *
 * The option is not autoloaded: nothing on the front end ever reads it.
 *
 * Scaffolding. It knows nothing about audiobooks, and is configured by whoever
 * boots the plugin.
 */
final class Logger {

	/**
	 * Levels, quietest first.
	 */
	public const LEVELS = array( 'info', 'warning', 'error' );

	/**
	 * The option entries are kept in.
	 *
	 * @var string
	 */
	private static string $option = '';

	/**
	 * Whether anything is being recorded at all.
	 *
	 * @var bool
	 */
	private static bool $enabled = false;

	/**
	 * How many entries to keep.
	 *
	 * @var int
	 */
	private static int $max = 200;

	/**
	 * Tells the logger where to write and whether to write at all.
	 *
	 * @param string $option  Option name.
	 * @param bool   $enabled Whether to record.
	 * @param int    $max     How many entries to keep.
	 */
	public static function configure( string $option, bool $enabled, int $max = 200 ): void {
		self::$option  = $option;
		self::$enabled = $enabled;
		self::$max     = max( 10, min( 1000, $max ) );
	}

	/**
	 * Whether anything is being recorded.
	 */
	public static function enabled(): bool {
		return self::$enabled && '' !== self::$option;
	}

	/**
	 * Records something worth knowing about.
	 *
	 * @param string               $level   One of `LEVELS`.
	 * @param string               $message What happened, in one line.
	 * @param array<string, mixed> $context Anything scalar worth keeping with it.
	 */
	public static function log( string $level, string $message, array $context = array() ): void {
		if ( ! self::enabled() ) {
			return;
		}

		$level = in_array( $level, self::LEVELS, true ) ? $level : 'info';

		$entries = self::entries();

		array_unshift(
			$entries,
			array(
				'time'    => current_time( 'mysql', true ),
				'level'   => $level,
				'message' => $message,
				'context' => self::flatten( $context ),
			)
		);

		update_option( self::$option, array_slice( $entries, 0, self::$max ), false );
	}

	/**
	 * Records something that went as it should.
	 *
	 * @param string               $message What happened.
	 * @param array<string, mixed> $context Anything worth keeping with it.
	 */
	public static function info( string $message, array $context = array() ): void {
		self::log( 'info', $message, $context );
	}

	/**
	 * Records something that did not, but carried on.
	 *
	 * @param string               $message What happened.
	 * @param array<string, mixed> $context Anything worth keeping with it.
	 */
	public static function warning( string $message, array $context = array() ): void {
		self::log( 'warning', $message, $context );
	}

	/**
	 * Records something that failed.
	 *
	 * @param string               $message What happened.
	 * @param array<string, mixed> $context Anything worth keeping with it.
	 */
	public static function error( string $message, array $context = array() ): void {
		self::log( 'error', $message, $context );
	}

	/**
	 * Everything recorded, newest first.
	 *
	 * Entries are written with `time`, `level`, `message` and `context`, and
	 * the type below promises less than that on purpose: these come back out
	 * of an option, which may hold what an older version of this plugin wrote
	 * or what somebody put there by hand. Whoever reads an entry checks it.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function entries(): array {
		if ( '' === self::$option ) {
			return array();
		}

		$stored = get_option( self::$option, array() );

		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Empties the log.
	 */
	public static function clear(): void {
		if ( '' === self::$option ) {
			return;
		}

		update_option( self::$option, array(), false );
	}

	/**
	 * Context down to strings, because that is all a log line can show.
	 *
	 * @param array<string, mixed> $context Anything.
	 * @return array<string, string>
	 */
	private static function flatten( array $context ): array {
		$flat = array();

		foreach ( $context as $key => $value ) {
			if ( is_scalar( $value ) || null === $value ) {
				$flat[ (string) $key ] = (string) $value;
				continue;
			}

			$encoded = wp_json_encode( $value );

			$flat[ (string) $key ] = is_string( $encoded ) ? $encoded : '';
		}

		return $flat;
	}

	/**
	 * Forgets its configuration. For tests, and for nothing else.
	 */
	public static function reset(): void {
		self::$option  = '';
		self::$enabled = false;
		self::$max     = 200;
	}
}
