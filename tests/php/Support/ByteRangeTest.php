<?php
/**
 * Tests for the byte range parser.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Support;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Support\ByteRange;

/**
 * A `Range` header against a file size, resolved to two offsets.
 */
final class ByteRangeTest extends TestCase {

	/**
	 * Size used throughout, so the expectations read as themselves.
	 */
	private const SIZE = 1000;

	/**
	 * Ranges that resolve to bytes.
	 *
	 * @dataProvider satisfiable_cases
	 *
	 * @param string $header   The header a client sent.
	 * @param int    $start    First byte expected.
	 * @param int    $end      Last byte expected.
	 */
	public function test_satisfiable( string $header, int $start, int $end ): void {
		$this->assertSame( array( $start, $end ), ByteRange::parse( $header, self::SIZE ) );
	}

	/**
	 * Headers with an answer, including the ones that mean "all of it".
	 *
	 * @return array<string, array{string, int, int}>
	 */
	public function satisfiable_cases(): array {
		return array(
			'no header at all'              => array( '', 0, 999 ),
			'whitespace only'               => array( '   ', 0, 999 ),
			'the first hundred'             => array( 'bytes=0-99', 0, 99 ),
			'a single byte'                 => array( 'bytes=0-0', 0, 0 ),
			'the last byte'                 => array( 'bytes=999-999', 999, 999 ),
			'open ended, a seek'            => array( 'bytes=500-', 500, 999 ),
			'from zero, open ended'         => array( 'bytes=0-', 0, 999 ),
			'a suffix'                      => array( 'bytes=-100', 900, 999 ),
			'a suffix longer than the file' => array( 'bytes=-5000', 0, 999 ),
			'an end past the file'          => array( 'bytes=900-99999', 900, 999 ),
			'surrounding whitespace'        => array( '  bytes=10-20  ', 10, 20 ),
			'only the first of many'        => array( 'bytes=0-49,100-149', 0, 49 ),
			'nonsense is ignored'           => array( 'kilobytes=0-99', 0, 999 ),
			'no unit'                       => array( '0-99', 0, 999 ),
			'garbage'                       => array( 'bytes=abc', 0, 999 ),
		);
	}

	/**
	 * Ranges that cannot be answered and have to become a 416.
	 *
	 * @dataProvider unsatisfiable_cases
	 *
	 * @param string $header The header a client sent.
	 */
	public function test_unsatisfiable( string $header ): void {
		$this->assertNull( ByteRange::parse( $header, self::SIZE ) );
	}

	/**
	 * Headers with no honest answer inside the file.
	 *
	 * @return array<string, array{string}>
	 */
	public function unsatisfiable_cases(): array {
		return array(
			'starts past the end' => array( 'bytes=1000-' ),
			'well past the end'   => array( 'bytes=999999-' ),
			'end before start'    => array( 'bytes=500-100' ),
			'a suffix of nothing' => array( 'bytes=-0' ),
			'neither end given'   => array( 'bytes=-' ),
		);
	}

	/**
	 * An empty file has no bytes to slice, so every range is the whole of it.
	 */
	public function test_an_empty_file_is_never_a_partial_response(): void {
		$this->assertSame( array( 0, 0 ), ByteRange::parse( 'bytes=0-99', 0 ) );
		$this->assertSame( array( 0, 0 ), ByteRange::parse( '', 0 ) );
	}

	/**
	 * A negative size is not a file. It must not produce negative offsets.
	 */
	public function test_a_nonsense_size_produces_no_nonsense_offsets(): void {
		$this->assertSame( array( 0, 0 ), ByteRange::parse( 'bytes=0-10', -1 ) );
	}

	/**
	 * Whatever comes back must be a slice that actually exists in the file.
	 */
	public function test_every_answer_lies_inside_the_file(): void {
		$headers = array(
			'',
			'bytes=0-',
			'bytes=-1',
			'bytes=-1000',
			'bytes=0-0',
			'bytes=999-',
			'bytes=0-99999',
			'bytes=250-750',
		);

		foreach ( $headers as $header ) {
			$range = ByteRange::parse( $header, self::SIZE );

			$this->assertNotNull( $range, $header );
			$this->assertGreaterThanOrEqual( 0, $range[0], $header );
			$this->assertLessThan( self::SIZE, $range[1], $header );
			$this->assertLessThanOrEqual( $range[1], $range[0], $header );
		}
	}
}
