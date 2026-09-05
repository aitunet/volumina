<?php
/**
 * Front-end asset registration.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Frontend;

use TUNET\Volumina\Player\Player;
use TUNET\Volumina\Support\Registrable;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the front-end handles, and enqueues none of them.
 *
 * Registration and enqueueing are separated because a book page is no longer
 * the only place this plugin draws: a block can put a player or a chapter list
 * on any page on the site, and it can only ask for a handle that already
 * exists. Whoever draws decides what to load — the content filter on a book
 * page, and `block.json` everywhere else.
 */
final class Assets implements Registrable {

	/**
	 * The book stylesheet: cover, details, chapter list.
	 */
	public const BOOK = 'volumina-book';

	/**
	 * The player, script and stylesheet under one handle each.
	 */
	public const PLAYER = 'volumina-player';

	/**
	 * The Continue listening block's script.
	 */
	public const CONTINUE = 'volumina-continue';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		// `init` rather than `wp_enqueue_scripts`: the editor asks the REST API
		// to render these blocks, and that request never reaches a front-end
		// enqueue hook. Registering is cheap and enqueues nothing.
		add_action( 'init', array( $this, 'register_assets' ) );
	}

	/**
	 * Registers every front-end handle the plugin owns.
	 */
	public function register_assets(): void {
		wp_register_style(
			self::BOOK,
			plugins_url( 'assets/css/book.css', PLUGIN_FILE ),
			array(),
			VERSION
		);

		wp_register_style(
			self::PLAYER,
			plugins_url( 'assets/css/player.css', PLUGIN_FILE ),
			array(),
			VERSION
		);

		wp_register_script(
			self::PLAYER,
			plugins_url( 'assets/js/player.js', PLUGIN_FILE ),
			array(),
			VERSION,
			true
		);

		wp_localize_script( self::PLAYER, 'voluminaPlayer', Player::settings() );

		wp_register_script(
			self::CONTINUE,
			plugins_url( 'assets/js/continue.js', PLUGIN_FILE ),
			array(),
			VERSION,
			true
		);

		wp_localize_script( self::CONTINUE, 'voluminaContinue', self::continue_settings() );
	}

	/**
	 * What the Continue listening script needs to draw a guest's list.
	 *
	 * @return array<string, mixed>
	 */
	private static function continue_settings(): array {
		return array(
			// A signed-in listener's list is rendered server side from the
			// progress table, so the script leaves it alone.
			'active'  => ! is_user_logged_in(),
			'strings' => array(
				/* translators: %s: chapter title. */
				'inChapter' => __( 'In %s', 'volumina' ),
				/* translators: %s: a position in a chapter, as h:mm:ss. */
				'atTime'    => __( 'at %s', 'volumina' ),
				'resume'    => __( 'Carry on listening', 'volumina' ),
				'empty'     => __( 'Nothing started yet. Books you begin will appear here.', 'volumina' ),
			),
		);
	}
}
