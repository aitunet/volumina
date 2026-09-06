<?php
/**
 * The admin screen registry.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Puts screens under the Audiobooks menu, its own and anybody else's.
 *
 * An extension that had to call `add_submenu_page` itself would have to know
 * this plugin's menu slug, its position, and the order everything is added in —
 * three things that are not promises. It registers a `Screen` instead.
 *
 * A screen that says it does not apply, or that the person looking may not use,
 * is never registered at all. That is the difference between a plugin that
 * grows quietly and one that fills the sidebar with pages nobody can open.
 */
final class ScreenRegistry implements Registrable {

	/**
	 * The menu these screens hang from: the book post type's own.
	 */
	public const PARENT = 'edit.php?post_type=' . Book::POST_TYPE;

	/**
	 * Registered screens, keyed by slug.
	 *
	 * @var array<string, Screen>
	 */
	private array $screens = array();

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
	}

	/**
	 * Adds a screen.
	 *
	 * @param Screen $screen The screen.
	 */
	public function add( Screen $screen ): void {
		$this->screens[ $screen->slug() ] = $screen;
	}

	/**
	 * Every registered screen, keyed by slug.
	 *
	 * @return array<string, Screen>
	 */
	public function screens(): array {
		return $this->screens;
	}

	/**
	 * Collects the screens and registers the ones that apply.
	 */
	public function add_pages(): void {
		/**
		 * Fires so extensions can add their own admin screens.
		 *
		 * @param ScreenRegistry $registry The registry to add screens to.
		 */
		do_action( 'volumina_register_admin_screens', $this );

		foreach ( $this->screens as $screen ) {
			if ( ! $screen->applies() || ! current_user_can( $screen->capability() ) ) {
				continue;
			}

			add_submenu_page(
				self::PARENT,
				$screen->title(),
				$screen->menu_title(),
				$screen->capability(),
				$screen->slug(),
				function () use ( $screen ) {
					$this->draw( $screen );
				}
			);
		}
	}

	/**
	 * Draws one screen, on the request that asks for it.
	 *
	 * The capability is checked here as well as above, and the repetition is
	 * the point: the check above decided whether to put an entry in a menu, on
	 * some earlier request. This one runs on the request that is about to see
	 * the page, which is the only one that can protect it.
	 *
	 * @param Screen $screen The screen to draw.
	 */
	private function draw( Screen $screen ): void {
		if ( ! current_user_can( $screen->capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'volumina' ), 403 );
		}

		$screen->render();
	}
}
