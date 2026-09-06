<?php
/**
 * A provider that says whatever a test tells it to.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Access;

use TUNET\Volumina\Access\AccessProvider;

/**
 * Stands in for a real provider, and counts how often it was asked.
 *
 * The counting is the interesting part: it is how a test can tell that the
 * decision stopped at a refusal rather than merely agreeing with one.
 */
final class FakeProvider implements AccessProvider {

	/**
	 * How many times it was asked.
	 *
	 * @var int
	 */
	public int $asked = 0;

	/**
	 * What this provider answers.
	 *
	 * @var bool|null
	 */
	private ?bool $answer;

	/**
	 * Its name.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * Builds a provider with its mind already made up.
	 *
	 * @param string    $name   Provider id.
	 * @param bool|null $answer What to answer, `null` for no opinion.
	 */
	public function __construct( string $name, ?bool $answer ) {
		$this->name   = $name;
		$this->answer = $answer;
	}

	/**
	 * A short machine name.
	 */
	public function id(): string {
		return $this->name;
	}

	/**
	 * What to call it where a person can see it.
	 */
	public function label(): string {
		return $this->name;
	}

	/**
	 * Whether this listener may hear this book.
	 *
	 * @param int $user_id Listener, or 0 for somebody not signed in.
	 * @param int $book_id The book.
	 */
	public function can_listen( int $user_id, int $book_id ): ?bool {
		++$this->asked;

		return $this->answer;
	}
}
