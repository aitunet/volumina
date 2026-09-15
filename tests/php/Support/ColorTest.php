<?php
/**
 * Tests for the colour helper.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Support;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Support\Color;

/**
 * A hex colour is recognised in its usual spellings, and the ink that goes on
 * it is whichever of black and white contrasts more.
 */
final class ColorTest extends TestCase {

	/**
	 * Six-digit, three-digit, with or without the hash, any case: all become
	 * lower-case six digits with the hash. Anything else is nothing.
	 *
	 * @dataProvider hex_cases
	 *
	 * @param string $given    What arrived.
	 * @param string $expected What it normalises to, or '' when it is not a colour.
	 */
	public function test_normalise( string $given, string $expected ): void {
		$this->assertSame( $expected, Color::normalize( $given ) );
	}

	/**
	 * Spellings and what they normalise to.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function hex_cases(): array {
		return array(
			'six digits'     => array( '#2f6b4f', '#2f6b4f' ),
			'upper case'     => array( '#2F6B4F', '#2f6b4f' ),
			'no hash'        => array( '2f6b4f', '#2f6b4f' ),
			'three digits'   => array( '#fff', '#ffffff' ),
			'three, no hash' => array( 'abc', '#aabbcc' ),
			'padded'         => array( '  #2f6b4f ', '#2f6b4f' ),
			'eight digits'   => array( '#2f6b4f80', '' ),
			'named'          => array( 'red', '' ),
			'rgb()'          => array( 'rgb(1, 2, 3)', '' ),
			'css variable'   => array( 'var(--wp--preset--color--base)', '' ),
			'empty'          => array( '', '' ),
			'injection'      => array( '#2f6b4f;background:url(x)', '' ),
		);
	}

	/**
	 * Relative luminance, per WCAG: black is 0, white is 1, and the
	 * reference mid-grey lands where the formula says.
	 */
	public function test_luminance(): void {
		$this->assertSame( 0.0, Color::luminance( '#000000' ) );
		$this->assertSame( 1.0, Color::luminance( '#ffffff' ) );
		$this->assertEqualsWithDelta( 0.2159, Color::luminance( '#808080' ), 0.001 );
	}

	/**
	 * Contrast ratio, per WCAG: 21 between black and white, 1 between a colour
	 * and itself, and the same whichever way round.
	 */
	public function test_contrast(): void {
		$this->assertEqualsWithDelta( 21.0, Color::contrast( '#000000', '#ffffff' ), 0.01 );
		$this->assertEqualsWithDelta( 1.0, Color::contrast( '#2f6b4f', '#2f6b4f' ), 0.001 );
		$this->assertEqualsWithDelta( Color::contrast( '#2f6b4f', '#ffffff' ), Color::contrast( '#ffffff', '#2f6b4f' ), 0.0001 );
	}

	/**
	 * The ink on a colour is black or white, whichever contrasts more — and
	 * one of the two always reaches 4.5:1, even on the worst mid-grey.
	 *
	 * @dataProvider ink_cases
	 *
	 * @param string $on  The background.
	 * @param string $ink The expected ink.
	 */
	public function test_ink( string $on, string $ink ): void {
		$this->assertSame( $ink, Color::ink( $on ) );
		$this->assertGreaterThanOrEqual( 4.5, Color::contrast( $on, $ink ) );
	}

	/**
	 * Backgrounds and the ink that goes on each.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function ink_cases(): array {
		return array(
			'dark green'  => array( '#2f6b4f', '#ffffff' ),
			'amber'       => array( '#e0a83a', '#000000' ),
			'white'       => array( '#ffffff', '#000000' ),
			'black'       => array( '#000000', '#ffffff' ),
			'worst grey'  => array( '#767676', '#000000' ),
			'wp blue'     => array( '#2271b1', '#ffffff' ),
			'pale yellow' => array( '#fff59d', '#000000' ),
		);
	}

	/**
	 * Ink on something that is not a colour is nothing: the caller has no
	 * accent to paint, so it must paint no ink either.
	 */
	public function test_ink_on_nothing(): void {
		$this->assertSame( '', Color::ink( 'red' ) );
		$this->assertSame( '', Color::ink( '' ) );
	}
}
