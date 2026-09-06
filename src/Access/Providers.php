<?php
/**
 * The providers the free plugin ships.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Access;

use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the built-in providers, through the same door everyone else uses.
 *
 * The free plugin has no privileged way in. If `volumina_register_access_providers`
 * were not enough to add a provider, this class would be the first thing to
 * break, which is the point of registering this way rather than in a constructor.
 */
final class Providers implements Registrable {

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'volumina_register_access_providers', array( $this, 'add' ) );
	}

	/**
	 * Adds the two providers the free plugin ships.
	 *
	 * @param AccessManager $manager The manager to register with.
	 */
	public function add( AccessManager $manager ): void {
		$manager->register( new PublicProvider() );
		$manager->register( new ManualProvider() );
	}
}
