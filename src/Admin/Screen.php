<?php
/**
 * The admin screen contract.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * A screen under the Audiobooks menu.
 *
 * Implement this and register it on `volumina_register_admin_screens`. The
 * registry adds the submenu page, checks the capability and calls `render()`;
 * nobody patches a menu and nobody adds a page twice.
 *
 * This interface is public API. It will not change without a major version.
 */
interface Screen {

	/**
	 * The page slug, unique among screens.
	 *
	 * It ends up in the URL as `admin.php?page=<slug>`, so prefix it with your
	 * own plugin's slug.
	 */
	public function slug(): string;

	/**
	 * The title of the page itself.
	 */
	public function title(): string;

	/**
	 * The name in the menu, which is usually shorter than the title.
	 */
	public function menu_title(): string;

	/**
	 * The capability required to see it at all.
	 *
	 * A screen nobody present may use is never registered, so it costs nothing
	 * and appears nowhere.
	 */
	public function capability(): string;

	/**
	 * Whether this screen applies to this site at all.
	 *
	 * Return false and the page is not registered: no menu entry, no route, no
	 * empty screen explaining that there is nothing here.
	 */
	public function applies(): bool;

	/**
	 * Draws the screen. Everything it echoes must be escaped.
	 */
	public function render(): void;
}
