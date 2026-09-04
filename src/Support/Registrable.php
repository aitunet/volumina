<?php
/**
 * Registrable component contract.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Something that attaches itself to WordPress hooks.
 *
 * Deliberately knows nothing about audiobooks. This interface is part of the
 * scaffolding the next TUNET plugin inherits unchanged.
 */
interface Registrable {

	/**
	 * Adds the component's hooks.
	 *
	 * Called once, early, before `init`. Implementations register hooks here
	 * and do no work of their own.
	 */
	public function register(): void;
}
