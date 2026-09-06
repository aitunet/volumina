<?php
/**
 * The optional help contract.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * A screen that has something to say in its Help tab.
 *
 * Separate from `Screen` on purpose, and additive: a screen that does not
 * implement this keeps working exactly as it did. Making `help()` part of
 * `Screen` would have been a breaking change to a published contract for the
 * sake of a method most screens want but none of them need.
 *
 * The registry calls `help()` on `load-{page}`, which is the only moment
 * `get_current_screen()` is both available and still accepting tabs.
 *
 * This interface is public API. It will not change without a major version.
 */
interface HasHelp {

	/**
	 * Adds this screen's help tabs. Use `Support\Help::add()`.
	 */
	public function help(): void;
}
