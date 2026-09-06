<?php
/**
 * The access provider contract.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Access;

defined( 'ABSPATH' ) || exit;

/**
 * Something that has an opinion about whether a listener may hear a book.
 *
 * Implement this and register it on `volumina_register_access_providers`. The
 * plugin asks every registered provider and combines the answers; a provider
 * never has to know that any other one exists.
 *
 * This interface is public API. It will not change without a major version.
 */
interface AccessProvider {

	/**
	 * A short machine name, unique among providers.
	 *
	 * Registering a second provider under a name already taken replaces the
	 * first, which is how an extension deliberately supersedes another. Prefix
	 * it with your own plugin's slug unless you mean to do that.
	 */
	public function id(): string;

	/**
	 * What to call this provider where a person can see it.
	 */
	public function label(): string;

	/**
	 * Whether this listener may hear this book.
	 *
	 * Three answers, and the difference between them matters:
	 *
	 * - `true`  — grant. This provider vouches for the listener.
	 * - `false` — refusal. This provider objects, and its objection outranks
	 *             every grant. Reserve it for a real reason to keep somebody
	 *             out, never for "I have nothing to say about this".
	 * - `null`  — no opinion. Almost always the right answer when your own
	 *             condition is not met.
	 *
	 * @param int $user_id Listener, or 0 for somebody not signed in.
	 * @param int $book_id The book.
	 */
	public function can_listen( int $user_id, int $book_id ): ?bool;
}
