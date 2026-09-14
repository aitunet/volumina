<?php
/**
 * The one-time redirect to the welcome page.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Support\Registrable;

use const TUNET\Volumina\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Sends whoever activated the plugin to the welcome page, once.
 *
 * Activation remembers who did it, for a minute, in a transient; the next
 * admin request by that same person consumes it and redirects. Nobody else is
 * sent anywhere, and nothing is left behind if the request never comes.
 *
 * It stands down when nobody is waiting to be welcomed: a bulk activation, a
 * network-wide one, WP-CLI, and a site that has already been set up — so a
 * deactivate-and-reactivate on a working site interrupts no one.
 */
final class WelcomeRedirect implements Registrable {

	/**
	 * The transient that carries the activating user's id.
	 */
	public const TRANSIENT = 'volumina_welcome_redirect';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		register_activation_hook( PLUGIN_FILE, array( $this, 'remember' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
	}

	/**
	 * On activation: remembers who activated, when it was one person
	 * activating one plugin on a site that has not been set up yet.
	 *
	 * @param bool $network_wide Whether the activation is for the whole network.
	 */
	public function remember( bool $network_wide = false ): void {
		if ( $network_wide || ( defined( 'WP_CLI' ) && WP_CLI ) || SetupScreen::finished() ) {
			return;
		}

		set_transient( self::TRANSIENT, get_current_user_id(), MINUTE_IN_SECONDS );
	}

	/**
	 * On the next admin request: redirects the person who activated, once.
	 */
	public function maybe_redirect(): void {
		$user_id = get_transient( self::TRANSIENT );

		if ( false === $user_id ) {
			return;
		}

		// Not a page anyone is looking at, and not somebody else's activation.
		if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( get_current_user_id() !== (int) $user_id ) {
			return;
		}

		delete_transient( self::TRANSIENT );

		// Several plugins activated at once: the list is where they expect to
		// land, so leave them there. The parameter is only read, never acted on.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_safe_redirect( WelcomeScreen::url() );
		exit;
	}
}
