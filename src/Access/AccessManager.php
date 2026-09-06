<?php
/**
 * Access resolution.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Access;

defined( 'ABSPATH' ) || exit;

/**
 * The one place that answers "may this person listen to this book?".
 *
 * It holds a registry of providers and asks all of them. The rule is short
 * enough to state in a sentence: a single refusal denies, otherwise a single
 * grant allows, otherwise nobody vouched and the answer is no.
 *
 * Default deny is deliberate. A book marked restricted whose provider fails to
 * load should be silent, not open.
 */
final class AccessManager {

	/**
	 * The single instance the plugin and its extensions share.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Registered providers, keyed by id.
	 *
	 * @var array<string, AccessProvider>
	 */
	private array $providers = array();

	/**
	 * Whether the registration action has already run.
	 *
	 * @var bool
	 */
	private bool $collected = false;

	/**
	 * Answers already worked out during this request.
	 *
	 * @var array<string, bool>
	 */
	private array $resolved = array();

	/**
	 * The shared instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Adds a provider.
	 *
	 * @param AccessProvider $provider The provider.
	 */
	public function register( AccessProvider $provider ): void {
		$this->providers[ $provider->id() ] = $provider;

		// A provider registered after an answer was cached would otherwise be
		// ignored for the rest of the request.
		$this->resolved = array();
	}

	/**
	 * Removes a provider by id, if it is there.
	 *
	 * @param string $id The provider's id.
	 */
	public function unregister( string $id ): void {
		unset( $this->providers[ $id ] );

		$this->resolved = array();
	}

	/**
	 * Every registered provider, keyed by id.
	 *
	 * @return array<string, AccessProvider>
	 */
	public function providers(): array {
		$this->collect();

		return $this->providers;
	}

	/**
	 * Whether a listener may hear a book.
	 *
	 * @param int      $book_id The book.
	 * @param int|null $user_id Listener, or null for whoever is asking.
	 */
	public function can_listen( int $book_id, ?int $user_id = null ): bool {
		$user_id = null === $user_id ? get_current_user_id() : $user_id;
		$user_id = max( 0, $user_id );

		if ( $book_id <= 0 ) {
			return false;
		}

		$key = $user_id . ':' . $book_id;

		if ( isset( $this->resolved[ $key ] ) ) {
			return $this->resolved[ $key ];
		}

		$allowed = self::decide( $this->providers(), $user_id, $book_id );

		/**
		 * Filters the final answer.
		 *
		 * The last word, after every provider has spoken. Prefer a provider:
		 * it says why it answered and it shows up in the admin. This is for
		 * the cases a provider cannot express.
		 *
		 * @param bool $allowed Whether the listener may hear the book.
		 * @param int  $book_id The book.
		 * @param int  $user_id Listener, or 0 for somebody not signed in.
		 */
		$allowed = (bool) apply_filters( 'volumina_can_listen', $allowed, $book_id, $user_id );

		$this->resolved[ $key ] = $allowed;

		return $allowed;
	}

	/**
	 * Combines what the providers said.
	 *
	 * Pure on purpose: it takes providers rather than finding them, so the rule
	 * can be tested without a database or a WordPress.
	 *
	 * @param array<string, AccessProvider> $providers Providers to ask.
	 * @param int                           $user_id   Listener, or 0.
	 * @param int                           $book_id   The book.
	 */
	public static function decide( array $providers, int $user_id, int $book_id ): bool {
		$granted = false;

		foreach ( $providers as $provider ) {
			$answer = $provider->can_listen( $user_id, $book_id );

			if ( false === $answer ) {
				// A refusal outranks every grant, including one already given.
				return false;
			}

			if ( true === $answer ) {
				$granted = true;
			}
		}

		return $granted;
	}

	/**
	 * Fires the registration action, once.
	 */
	private function collect(): void {
		if ( $this->collected ) {
			return;
		}

		$this->collected = true;

		/**
		 * Fires once, the first time access is resolved, so extensions can add
		 * their own providers.
		 *
		 * Register your hook no later than `init`: this fires on the first
		 * request for a book, and a hook added after it has fired is ignored
		 * for that request.
		 *
		 * @param AccessManager $manager The manager to register with.
		 */
		do_action( 'volumina_register_access_providers', $this );
	}

	/**
	 * Forgets everything. For tests, and for nothing else.
	 */
	public static function reset(): void {
		self::$instance = null;
	}
}
