<?php
/**
 * Tests for the access decision.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Access;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Access\AccessManager;

/**
 * A refusal denies, otherwise a grant allows, otherwise nobody vouched.
 */
final class AccessManagerTest extends TestCase {

	/**
	 * Nobody registered, nobody vouches: the answer is no.
	 */
	public function test_no_providers_means_no(): void {
		$this->assertFalse( AccessManager::decide( array(), 7, 26 ) );
	}

	/**
	 * Silence is not consent.
	 */
	public function test_abstaining_is_not_a_grant(): void {
		$providers = array(
			'a' => new FakeProvider( 'a', null ),
			'b' => new FakeProvider( 'b', null ),
		);

		$this->assertFalse( AccessManager::decide( $providers, 7, 26 ) );
	}

	/**
	 * One grant is enough, whoever else says nothing.
	 */
	public function test_one_grant_allows(): void {
		$providers = array(
			'a' => new FakeProvider( 'a', null ),
			'b' => new FakeProvider( 'b', true ),
			'c' => new FakeProvider( 'c', null ),
		);

		$this->assertTrue( AccessManager::decide( $providers, 7, 26 ) );
	}

	/**
	 * A refusal outranks a grant, whichever order they come in.
	 */
	public function test_refusal_beats_a_grant(): void {
		$grant_first = array(
			'a' => new FakeProvider( 'a', true ),
			'b' => new FakeProvider( 'b', false ),
		);

		$refusal_first = array(
			'a' => new FakeProvider( 'a', false ),
			'b' => new FakeProvider( 'b', true ),
		);

		$this->assertFalse( AccessManager::decide( $grant_first, 7, 26 ) );
		$this->assertFalse( AccessManager::decide( $refusal_first, 7, 26 ) );
	}

	/**
	 * A refusal stops the questioning: nobody after it can change the answer.
	 */
	public function test_refusal_stops_asking(): void {
		$after = new FakeProvider( 'after', true );

		$providers = array(
			'refuses' => new FakeProvider( 'refuses', false ),
			'after'   => $after,
		);

		$this->assertFalse( AccessManager::decide( $providers, 7, 26 ) );
		$this->assertSame( 0, $after->asked );
	}

	/**
	 * Somebody not signed in is user 0, and a provider may still vouch for them.
	 */
	public function test_a_guest_can_be_granted(): void {
		$providers = array( 'a' => new FakeProvider( 'a', true ) );

		$this->assertTrue( AccessManager::decide( $providers, 0, 26 ) );
	}
}
