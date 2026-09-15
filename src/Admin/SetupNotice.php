<?php
/**
 * The notice that points a new site at its settings.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Points at the settings, until the site is set up.
 *
 * Two things end it, and both answer the question it asks: saving the
 * settings, or choosing to keep the defaults. A notice that can be dismissed
 * without answering is a notice that comes back, so there is no plain
 * dismiss.
 */
final class SetupNotice implements Registrable {

	/**
	 * The admin-post action that keeps the defaults.
	 */
	private const ACTION = 'volumina_keep_defaults';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'maybe_show' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'keep_defaults' ) );
	}

	/**
	 * Shows the prompt where it belongs and nowhere else.
	 */
	public function maybe_show(): void {
		if ( Setup::finished() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();

		// Not on every screen in the admin: this plugin's own pages and the
		// dashboard are enough.
		if ( null === $screen || ! $this->belongs_on( $screen->id ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p>%1$s <a href="%2$s">%3$s</a> · <a href="%4$s">%5$s</a></p></div>',
			esc_html__( 'Volumina is installed. Two settings decide how it behaves: who may listen by default, and whether book pages show the player.', 'volumina' ),
			esc_url( SettingsScreen::url() ),
			esc_html__( 'Open the settings', 'volumina' ),
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION ), self::ACTION ) ),
			esc_html__( 'Keep the defaults', 'volumina' )
		);
	}

	/**
	 * Keeps the defaults: the site is set up, and the notice ends.
	 *
	 * Capability and nonce, like every write; then back to where the link was.
	 */
	public function keep_defaults(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'volumina' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::ACTION );

		Setup::finish();

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Whether this screen is one of the few worth interrupting.
	 *
	 * @param string $screen_id The current screen's id.
	 */
	private function belongs_on( string $screen_id ): bool {
		if ( 'dashboard' === $screen_id || 'plugins' === $screen_id ) {
			return true;
		}

		// Not on the welcome page, whose hero already says this.
		return str_contains( $screen_id, 'volumina' ) && ! str_contains( $screen_id, WelcomeScreen::SLUG );
	}
}
