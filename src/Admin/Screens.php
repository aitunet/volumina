<?php
/**
 * The screens the plugin ships.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the built-in screens, through the same door everyone else uses.
 *
 * The same arrangement as the access providers, for the same reason: if
 * `volumina_register_admin_screens` were not enough to add a screen, this class
 * would be the first thing to break.
 */
final class Screens implements Registrable {

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'volumina_register_admin_screens', array( $this, 'add' ) );
	}

	/**
	 * Adds the screens the plugin ships.
	 *
	 * Each one decides for itself whether it applies. A log screen with logging
	 * turned off is not a page saying so; it is no page at all.
	 *
	 * @param ScreenRegistry $registry The registry to add screens to.
	 */
	public function add( ScreenRegistry $registry ): void {
		$registry->add( new SettingsScreen() );
		$registry->add( new LogScreen() );
		$registry->add( new SetupScreen() );
		$registry->add( new ProScreen() );
	}
}
