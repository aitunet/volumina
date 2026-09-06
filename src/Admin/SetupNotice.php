<?php
/**
 * The prompt to finish setting up.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Points at the setup screen, until setup is over.
 *
 * It needs no dismiss button of its own, because the thing it points at ends
 * it: finishing the wizard stops the notice, and so does skipping. A notice
 * that can be dismissed without answering the question it asks is a notice
 * that comes back.
 */
final class SetupNotice implements Registrable {

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'maybe_show' ) );
	}

	/**
	 * Shows the prompt where it belongs and nowhere else.
	 */
	public function maybe_show(): void {
		if ( SetupScreen::finished() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = get_current_screen();

		// Not on the setup screen itself, and not on every screen in the
		// admin: this plugin's own pages and the dashboard are enough.
		if ( null === $screen || ! $this->belongs_on( $screen->id ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'Volumina is installed and has not been set up yet. It takes two questions.', 'volumina' ),
			esc_url( SetupScreen::url() ),
			esc_html__( 'Set up Volumina', 'volumina' )
		);
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

		return str_contains( $screen_id, 'volumina' ) && ! str_contains( $screen_id, 'volumina-setup' );
	}
}
