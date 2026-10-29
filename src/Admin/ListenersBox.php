<?php
/**
 * The listeners box on the book editing screen.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Storage\Grants;
use TUNET\Volumina\Support\AssetVersion;
use TUNET\Volumina\Support\Logger;
use TUNET\Volumina\Support\Registrable;
use WP_Post;
use WP_User;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Gives a restricted book to listeners by hand, and takes it back.
 *
 * Saved at once, like the chapter order, so a forgotten Update button never
 * leaves somebody without the book they were promised. Only people who may
 * list users see it: finding an account by its e-mail address is managing
 * people, not editing a book.
 */
final class ListenersBox implements Registrable {

	/**
	 * Capability needed on top of editing the book.
	 */
	public const CAPABILITY = 'list_users';

	/**
	 * Nonce action, shared by both AJAX endpoints.
	 */
	private const NONCE_ACTION = 'volumina_listeners';

	/**
	 * AJAX action that gives access. Internal to the admin, not public API.
	 */
	private const AJAX_GIVE = 'volumina_give_listener';

	/**
	 * AJAX action that takes access away. Internal to the admin, not public API.
	 */
	private const AJAX_TAKE = 'volumina_take_listener';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . Book::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_' . self::AJAX_GIVE, array( $this, 'handle_give' ) );
		add_action( 'wp_ajax_' . self::AJAX_TAKE, array( $this, 'handle_take' ) );
	}

	/**
	 * Registers the meta box, for those who may use it only.
	 */
	public function add_meta_box(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		add_meta_box(
			'volumina-listeners',
			__( 'Listeners', 'volumina' ),
			array( $this, 'render' ),
			Book::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Loads the box's script on the book screen only.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();

		if ( null === $screen || Book::POST_TYPE !== $screen->post_type || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		wp_enqueue_script(
			'volumina-admin-listeners',
			plugins_url( 'assets/js/admin-listeners.js', PLUGIN_FILE ),
			array(),
			AssetVersion::of( 'assets/js/admin-listeners.js', PLUGIN_FILE, VERSION ),
			true
		);

		wp_localize_script(
			'volumina-admin-listeners',
			'voluminaListeners',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'error'   => __( 'That could not be saved. Reload the page and try again.', 'volumina' ),
			)
		);
	}

	/**
	 * Renders the box.
	 *
	 * @param WP_Post $post The book being edited.
	 */
	public function render( WP_Post $post ): void {
		if ( 'auto-draft' === $post->post_status ) {
			printf(
				'<p>%s</p>',
				esc_html__( 'Save the book first. Then you can give it to listeners here.', 'volumina' )
			);
			return;
		}

		printf(
			'<div id="volumina-listeners-app" data-book="%1$d" data-nonce="%2$s" data-give="%3$s" data-take="%4$s">',
			(int) $post->ID,
			esc_attr( wp_create_nonce( self::NONCE_ACTION ) ),
			esc_attr( self::AJAX_GIVE ),
			esc_attr( self::AJAX_TAKE )
		);

		printf(
			'<p>%s</p>',
			esc_html__( 'The people who may listen to this book while it is restricted. Each one needs an account on this site and listens signed in. While the book is public, everybody can listen and this list simply waits.', 'volumina' )
		);

		$users = array();

		foreach ( Grants::for_book( $post->ID ) as $user_id ) {
			$user = get_userdata( $user_id );

			if ( $user instanceof WP_User ) {
				$users[] = $user;
			}
		}

		printf(
			'<p class="volumina-listeners-empty"%1$s>%2$s</p>',
			array() === $users ? '' : ' hidden',
			esc_html__( 'Nobody has been given this book yet.', 'volumina' )
		);

		echo '<ul class="volumina-listeners">';

		foreach ( $users as $user ) {
			echo self::row( $user ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside row().
		}

		echo '</ul>';

		printf(
			'<p class="volumina-listeners-add"><label for="volumina-listener">%1$s</label><br /><input type="text" id="volumina-listener" class="regular-text" autocomplete="off" aria-describedby="volumina-listener-help" /> <button type="button" class="button volumina-listeners-give">%2$s</button></p>',
			esc_html__( 'E-mail address or username', 'volumina' ),
			esc_html__( 'Give access', 'volumina' )
		);

		printf(
			'<p class="description" id="volumina-listener-help">%s</p>',
			esc_html__( 'Saved at once; there is no need to update the book. Nobody is sent an e-mail.', 'volumina' )
		);

		echo '<p class="volumina-listeners-status" role="status" aria-live="polite"></p>';
		echo '</div>';
	}

	/**
	 * One listener's line in the list.
	 *
	 * @param WP_User $user The listener.
	 * @return string Escaped HTML.
	 */
	public static function row( WP_User $user ): string {
		return sprintf(
			'<li class="volumina-listener" data-user="%1$d"><span class="volumina-listener-name">%2$s</span> <span class="volumina-listener-email">%3$s</span> <button type="button" class="button-link volumina-listener-remove" aria-label="%4$s">%5$s</button></li>',
			(int) $user->ID,
			esc_html( $user->display_name ),
			esc_html( $user->user_email ),
			/* translators: %s: the listener's name. */
			esc_attr( sprintf( __( 'Remove %s', 'volumina' ), $user->display_name ) ),
			esc_html__( 'Remove', 'volumina' )
		);
	}

	/**
	 * Gives a book to the account an e-mail address or username names.
	 *
	 * The caller has already checked who is asking; this only does the work.
	 *
	 * @param int    $book_id The book.
	 * @param string $who     An e-mail address or a username, as typed.
	 * @return array{ok: bool, message: string, user: WP_User|null, added: bool}
	 */
	public static function give( int $book_id, string $who ): array {
		$who  = trim( $who );
		$user = null;

		if ( '' === $who ) {
			return self::result( false, __( 'Type an e-mail address or a username.', 'volumina' ) );
		}

		if ( is_email( $who ) ) {
			$user = get_user_by( 'email', $who );
		}

		// A username may itself contain an @, so the login is always tried.
		if ( ! $user instanceof WP_User ) {
			$user = get_user_by( 'login', $who );
		}

		if ( ! $user instanceof WP_User ) {
			return self::result( false, __( 'No account on this site has that e-mail address or username. The listener needs an account first.', 'volumina' ) );
		}

		if ( Grants::has( (int) $user->ID, $book_id ) ) {
			/* translators: %s: the listener's name. */
			return self::result( true, sprintf( __( '%s already had access.', 'volumina' ), $user->display_name ), $user );
		}

		if ( ! Grants::grant( (int) $user->ID, $book_id, get_current_user_id() ) ) {
			return self::result( false, __( 'That could not be saved. Reload the page and try again.', 'volumina' ) );
		}

		Logger::info(
			__( 'A listener was given an audiobook by hand.', 'volumina' ),
			array(
				'book' => $book_id,
				'user' => (int) $user->ID,
				'by'   => get_current_user_id(),
			)
		);

		/* translators: %s: the listener's name. */
		return self::result( true, sprintf( __( '%s can now listen.', 'volumina' ), $user->display_name ), $user, true );
	}

	/**
	 * Takes a book back from a listener.
	 *
	 * @param int $book_id The book.
	 * @param int $user_id The listener.
	 * @return array{ok: bool, message: string, user: WP_User|null, added: bool}
	 */
	public static function take( int $book_id, int $user_id ): array {
		if ( ! Grants::revoke( $user_id, $book_id ) ) {
			return self::result( false, __( 'That could not be saved. Reload the page and try again.', 'volumina' ) );
		}

		Logger::info(
			__( 'A listener lost an audiobook given by hand.', 'volumina' ),
			array(
				'book' => $book_id,
				'user' => $user_id,
				'by'   => get_current_user_id(),
			)
		);

		$user = get_userdata( $user_id );
		$name = $user instanceof WP_User ? $user->display_name : '#' . $user_id;

		/* translators: %s: the listener's name. */
		return self::result( true, sprintf( __( '%s can no longer listen.', 'volumina' ), $name ) );
	}

	/**
	 * AJAX: give access.
	 */
	public function handle_give(): void {
		$book_id = $this->authorise();
		$who     = isset( $_POST['who'] ) ? sanitize_text_field( wp_unslash( $_POST['who'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in authorise().
		$result  = self::give( $book_id, $who );

		if ( ! $result['ok'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => $result['message'],
				'row'     => $result['added'] && null !== $result['user'] ? self::row( $result['user'] ) : '',
			)
		);
	}

	/**
	 * AJAX: take access away.
	 */
	public function handle_take(): void {
		$book_id = $this->authorise();
		$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in authorise().
		$result  = self::take( $book_id, $user_id );

		if ( ! $result['ok'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ), 400 );
		}

		wp_send_json_success( array( 'message' => $result['message'] ) );
	}

	/**
	 * Checks the nonce, the book and who is asking; ends the request if any fails.
	 *
	 * @return int The book's ID.
	 */
	private function authorise(): int {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$book_id = isset( $_POST['book_id'] ) ? absint( wp_unslash( $_POST['book_id'] ) ) : 0;

		if (
			0 === $book_id
			|| Book::POST_TYPE !== get_post_type( $book_id )
			|| ! current_user_can( 'edit_post', $book_id )
			|| ! current_user_can( self::CAPABILITY )
		) {
			wp_send_json_error( array( 'message' => __( 'You cannot give this book to listeners.', 'volumina' ) ), 403 );
		}

		return $book_id;
	}

	/**
	 * Builds a result.
	 *
	 * @param bool         $ok      Whether it worked.
	 * @param string       $message What to tell the person.
	 * @param WP_User|null $user    The listener, when there is one.
	 * @param bool         $added   Whether a new line joins the list.
	 * @return array{ok: bool, message: string, user: WP_User|null, added: bool}
	 */
	private static function result( bool $ok, string $message, ?WP_User $user = null, bool $added = false ): array {
		return array(
			'ok'      => $ok,
			'message' => $message,
			'user'    => $user,
			'added'   => $added,
		);
	}
}
