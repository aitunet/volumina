<?php
/**
 * Tests for the render-once claim.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Support;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Support\RenderOnce;

/**
 * Whoever claims a key first renders; everyone after it stands down.
 */
final class RenderOnceTest extends TestCase {

	/**
	 * The claims live for a request, so each test starts with none.
	 */
	protected function setUp(): void {
		parent::setUp();

		RenderOnce::reset();
	}

	/**
	 * Leaves nothing behind for the next test, or the next request.
	 */
	protected function tearDown(): void {
		RenderOnce::reset();

		parent::tearDown();
	}

	/**
	 * The first caller renders.
	 */
	public function test_first_claim_wins(): void {
		$this->assertTrue( RenderOnce::claim( 'audiobook:12' ) );
	}

	/**
	 * The second caller stands down, however many times it asks.
	 */
	public function test_later_claims_stand_down(): void {
		RenderOnce::claim( 'audiobook:12' );

		$this->assertFalse( RenderOnce::claim( 'audiobook:12' ) );
		$this->assertFalse( RenderOnce::claim( 'audiobook:12' ) );
	}

	/**
	 * Two books on one page are two different things to render.
	 */
	public function test_keys_are_independent(): void {
		RenderOnce::claim( 'audiobook:12' );

		$this->assertTrue( RenderOnce::claim( 'audiobook:13' ) );
	}

	/**
	 * Asking does not claim: a caller can check without taking the turn.
	 */
	public function test_taken_does_not_claim(): void {
		$this->assertFalse( RenderOnce::taken( 'audiobook:12' ) );
		$this->assertTrue( RenderOnce::claim( 'audiobook:12' ) );
		$this->assertTrue( RenderOnce::taken( 'audiobook:12' ) );
	}

	/**
	 * A reset returns the request to its beginning.
	 */
	public function test_reset_forgets_everything(): void {
		RenderOnce::claim( 'audiobook:12' );
		RenderOnce::reset();

		$this->assertFalse( RenderOnce::taken( 'audiobook:12' ) );
		$this->assertTrue( RenderOnce::claim( 'audiobook:12' ) );
	}
}
