<?php
/**
 * Which settings tab is current.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Admin;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Admin\Tabs;

/**
 * The requested tab when it is known, the first tab otherwise.
 */
final class TabsTest extends TestCase {

	/**
	 * A known id is returned as is.
	 */
	public function test_the_requested_tab_when_it_exists(): void {
		$this->assertSame( 'volumina-pro-access', Tabs::current( array( 'general', 'volumina-pro-access' ), 'volumina-pro-access' ) );
	}

	/**
	 * An empty request falls back to the first tab.
	 */
	public function test_the_first_tab_when_nothing_is_requested(): void {
		$this->assertSame( 'general', Tabs::current( array( 'general', 'x' ), '' ) );
	}

	/**
	 * A request for an id that is not among the tabs falls back to the first.
	 */
	public function test_the_first_tab_when_the_request_is_unknown(): void {
		$this->assertSame( 'general', Tabs::current( array( 'general', 'x' ), 'nope' ) );
	}

	/**
	 * Surrounding whitespace is not part of the requested id.
	 */
	public function test_the_request_is_compared_after_trimming(): void {
		$this->assertSame( 'x', Tabs::current( array( 'general', 'x' ), '  x ' ) );
	}

	/**
	 * With no tabs at all, 'general' is the fallback even though it is not listed.
	 */
	public function test_general_when_there_are_no_tabs_at_all(): void {
		$this->assertSame( 'general', Tabs::current( array(), 'x' ) );
	}
}
