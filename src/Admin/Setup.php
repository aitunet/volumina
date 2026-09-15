<?php
/**
 * Whether the site has been set up.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * "Set up" means the settings have been looked at once: saved, or kept as
 * they are on purpose.
 *
 * There used to be a wizard asking the two questions that matter — who may
 * listen by default, and whether book pages show the player — but they are
 * the first two settings, and asking them twice in two places was one place
 * too many. The notice and the welcome page now send people to the settings,
 * and either saving them or choosing to keep the defaults ends the notice.
 */
final class Setup {

	/**
	 * The option that records it. The name predates the wizard's removal and
	 * stays, so a site that finished the wizard stays set up.
	 */
	public const DONE_OPTION = 'volumina_setup_done';

	/**
	 * Whether the site has been set up.
	 */
	public static function finished(): bool {
		return (bool) get_option( self::DONE_OPTION, false );
	}

	/**
	 * Records that it has.
	 */
	public static function finish(): void {
		update_option( self::DONE_OPTION, '1' );
	}
}
