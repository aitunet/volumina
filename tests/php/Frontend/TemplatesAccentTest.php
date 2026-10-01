<?php
/**
 * Tests for how a stored accent becomes a colour.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Frontend\Templates;

/**
 * A site's own colour is used as it is; none is none. Palette slugs need a
 * running theme and are covered on the test sites.
 */
final class TemplatesAccentTest extends TestCase {

	/**
	 * A colour of the site's own comes out in one spelling.
	 */
	public function test_a_custom_colour_is_used_as_it_is(): void {
		$this->assertSame( '#5a1414', Templates::accent( '#5A1414' ) );
		$this->assertSame( '#aabbcc', Templates::accent( '#abc' ) );
	}

	/**
	 * None, nothing, or a broken colour: no accent.
	 */
	public function test_none_is_no_accent(): void {
		$this->assertSame( '', Templates::accent( 'none' ) );
		$this->assertSame( '', Templates::accent( '' ) );
		$this->assertSame( '', Templates::accent( '#zzzzzz' ) );
	}
}
