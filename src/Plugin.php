<?php
/**
 * Plugin wiring.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina;

use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's components to WordPress.
 *
 * Holds no behaviour of its own. If logic starts appearing here, it belongs in
 * a component instead.
 */
final class Plugin {

	/**
	 * Components to register, in order.
	 *
	 * @var array<int, class-string<Registrable>>
	 */
	private const COMPONENTS = array(
		PostTypes\Book::class,
		PostTypes\Chapter::class,
		PostTypes\Taxonomies::class,
		Storage\Migrator::class,
		Access\Providers::class,
		Admin\Settings::class,
		Admin\ScreenRegistry::class,
		Admin\Screens::class,
		Admin\SetupNotice::class,
		Admin\PostScreenHelp::class,
		Admin\BookMetaBox::class,
		Admin\ChapterList::class,
		Admin\ChapterMetaBox::class,
		Player\Stream::class,
		Player\Player::class,
		Api\ProgressRoute::class,
		Frontend\Assets::class,
		Frontend\BookContent::class,
		Blocks\Registry::class,
	);

	/**
	 * Boots the plugin. Safe to call more than once.
	 */
	public static function boot(): void {
		static $booted = false;

		if ( $booted ) {
			return;
		}

		$booted = true;

		foreach ( self::COMPONENTS as $component ) {
			$instance = new $component();
			$instance->register();
		}
	}
}
