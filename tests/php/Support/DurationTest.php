<?php
/**
 * Tests for the duration formatter.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Support;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Support\Duration;

/**
 * Seconds in, something a person can read out.
 */
final class DurationTest extends TestCase {

	/**
	 * Every duration a listener will actually see.
	 *
	 * @dataProvider readable_cases
	 *
	 * @param int    $seconds  Input.
	 * @param string $expected What a listener should see.
	 */
	public function test_format( int $seconds, string $expected ): void {
		$this->assertSame( $expected, Duration::format( $seconds ) );
	}

	/**
	 * Readable forms, with the awkward boundaries included.
	 *
	 * @return array<string, array{int, string}>
	 */
	public function readable_cases(): array {
		return array(
			'zero'                    => array( 0, '0:00' ),
			'negative is not a time'  => array( -5, '0:00' ),
			'single digit seconds'    => array( 9, '0:09' ),
			'under a minute'          => array( 59, '0:59' ),
			'exactly a minute'        => array( 60, '1:00' ),
			'minutes and seconds'     => array( 95, '1:35' ),
			'no hour to show'         => array( 3599, '59:59' ),
			'exactly an hour'         => array( 3600, '1:00:00' ),
			'hours pad the minutes'   => array( 3725, '1:02:05' ),
			'a nine hour audiobook'   => array( 32400, '9:00:00' ),
			'past a day, still hours' => array( 90000, '25:00:00' ),
		);
	}

	/**
	 * The same durations, in the form a <time> element wants.
	 *
	 * @dataProvider iso_cases
	 *
	 * @param int    $seconds  Input.
	 * @param string $expected The machine-readable form.
	 */
	public function test_iso8601( int $seconds, string $expected ): void {
		$this->assertSame( $expected, Duration::iso8601( $seconds ) );
	}

	/**
	 * ISO 8601 forms, including the parts that are dropped when empty.
	 *
	 * @return array<string, array{int, string}>
	 */
	public function iso_cases(): array {
		return array(
			'zero still needs a unit'        => array( 0, 'PT0S' ),
			'negative is not a time'         => array( -5, 'PT0S' ),
			'seconds only'                   => array( 9, 'PT9S' ),
			'whole minutes drop the seconds' => array( 600, 'PT10M' ),
			'minutes and seconds'            => array( 95, 'PT1M35S' ),
			'whole hours'                    => array( 3600, 'PT1H' ),
			'all three parts'                => array( 3725, 'PT1H2M5S' ),
			'an hour and a second'           => array( 3601, 'PT1H1S' ),
		);
	}

	/**
	 * What a person types is read whichever way they said it.
	 *
	 * @dataProvider typed
	 *
	 * @param string $typed    What was typed.
	 * @param int    $expected Seconds.
	 */
	public function test_parse( string $typed, int $expected ): void {
		$this->assertSame( $expected, Duration::parse( $typed ) );
	}

	/**
	 * Typed running times and what they mean.
	 *
	 * @return array<string, array{0: string, 1: int}>
	 */
	public static function typed(): array {
		return array(
			'seconds'         => array( '1800', 1800 ),
			'zero'            => array( '0', 0 ),
			'm:ss'            => array( '1:42', 102 ),
			'mm:ss'           => array( '30:00', 1800 ),
			'many minutes'    => array( '90:00', 5400 ),
			'h:mm:ss'         => array( '1:05:09', 3909 ),
			'padded'          => array( '  1:15:00 ', 4500 ),
			'h:m:s unpadded'  => array( '2:5:9', 7509 ),
			'empty'           => array( '', 0 ),
			'words'           => array( 'half an hour', 0 ),
			'negative'        => array( '-30', 0 ),
			'seconds over 59' => array( '1:75', 0 ),
			'decimal'         => array( '1800.5', 0 ),
			'markup'          => array( '<b>30:00</b>', 0 ),
		);
	}

	/**
	 * The two formats have to agree about how long the thing is.
	 */
	public function test_the_two_formats_describe_the_same_duration(): void {
		foreach ( array( 0, 9, 95, 600, 3600, 3725, 32400 ) as $seconds ) {
			$interval = new \DateInterval( Duration::iso8601( $seconds ) );

			$total = ( $interval->d * 86400 ) + ( $interval->h * 3600 ) + ( $interval->i * 60 ) + $interval->s;

			$this->assertSame( $seconds, $total, "ISO form of {$seconds} seconds" );
		}
	}
}
