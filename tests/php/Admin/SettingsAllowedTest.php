<?php
/**
 * The Appearance form's share of the allowed options.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Admin;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Admin\Settings;

/**
 * Volumina's option is allowed in the Appearance group, once.
 */
final class SettingsAllowedTest extends TestCase {

	/**
	 * An empty list gains the option.
	 */
	public function test_it_adds_the_option_to_an_empty_list(): void {
		$this->assertSame(
			array( Settings::APPEARANCE_GROUP => array( Settings::OPTION ) ),
			( new Settings() )->allow_in_appearance( array() )
		);
	}

	/**
	 * Other groups are left alone.
	 */
	public function test_it_keeps_other_groups(): void {
		$result = ( new Settings() )->allow_in_appearance( array( 'general' => array( 'x' ) ) );

		$this->assertSame( array( 'x' ), $result['general'] );
	}

	/**
	 * Running twice does not duplicate.
	 */
	public function test_it_does_not_duplicate(): void {
		$settings = new Settings();
		$result   = $settings->allow_in_appearance( $settings->allow_in_appearance( array() ) );

		$this->assertSame( array( Settings::OPTION ), $result[ Settings::APPEARANCE_GROUP ] );
	}

	/**
	 * Something that is not an array becomes one.
	 */
	public function test_it_survives_a_non_array(): void {
		$this->assertSame(
			array( Settings::APPEARANCE_GROUP => array( Settings::OPTION ) ),
			( new Settings() )->allow_in_appearance( null )
		);
	}
}
