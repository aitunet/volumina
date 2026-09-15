<?php
/**
 * The optional parent-menu contract.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * A screen that lives under a menu other than Audiobooks.
 *
 * Additive, like `HasHelp`: a screen without it goes under Audiobooks, as
 * every screen did before. The welcome page uses it to sit under Dashboard,
 * where a "welcome" is expected and where it does not lengthen the menu
 * people work in.
 *
 * This interface is public API. It will not change without a major version.
 */
interface HasParent {

	/**
	 * The parent menu's slug, as `add_submenu_page()` takes it: `index.php`
	 * for Dashboard, `tools.php` for Tools, and so on.
	 */
	public function parent(): string;
}
