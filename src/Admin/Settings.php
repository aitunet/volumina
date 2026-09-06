<?php
/**
 * The plugin's settings.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Access\Mode;
use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Support\Logger;
use TUNET\Volumina\Support\Registrable;
use TUNET\Volumina\Support\Settings\Field;
use TUNET\Volumina\Support\Settings\Group;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * What the site has chosen, and what follows from it.
 *
 * Four settings, and every one of them has to earn its place: a setting is a
 * question asked of somebody who wanted to publish an audiobook. Anything with
 * an obviously right answer is not a setting, it is a decision this plugin
 * should have made.
 */
final class Settings implements Registrable {

	/**
	 * The option the settings live in.
	 */
	public const OPTION = 'volumina_settings';

	/**
	 * The option the log lives in.
	 */
	public const LOG_OPTION = 'volumina_log';

	/**
	 * The group, built once.
	 *
	 * @var Group|null
	 */
	private static ?Group $group = null;

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'init', array( $this, 'configure_logger' ) );
		add_action( 'save_post_' . Book::POST_TYPE, array( $this, 'apply_default_mode' ), 10, 2 );
	}

	/**
	 * Registers the option, so `options.php` will accept a form posting to it.
	 */
	public function register_setting(): void {
		self::group()->register();
	}

	/**
	 * Points the logger at its option and tells it whether to record.
	 */
	public function configure_logger(): void {
		Logger::configure( self::LOG_OPTION, (bool) self::get( 'logging' ) );
	}

	/**
	 * Gives a new book the site's default access mode.
	 *
	 * Only when the book has none of its own. The setting decides what a new
	 * book starts as and nothing else: changing it later must not reach back
	 * and quietly take an existing book away from its listeners.
	 *
	 * @param int     $post_id The book.
	 * @param WP_Post $post    The book.
	 */
	public function apply_default_mode( int $post_id, WP_Post $post ): void {
		if ( Book::POST_TYPE !== $post->post_type ) {
			return;
		}

		// `metadata_exists`, not `get_post_meta`: the meta is registered with a
		// default, so reading it never comes back empty and a check on its
		// value would decide this book already had one. Only the existence of
		// the row says whether anybody has actually chosen.
		if ( metadata_exists( 'post', $post_id, Mode::META_KEY ) ) {
			return;
		}

		update_post_meta( $post_id, Mode::META_KEY, Mode::sanitize( self::get( 'default_access' ) ) );
	}

	/**
	 * The settings group.
	 */
	public static function group(): Group {
		if ( null === self::$group ) {
			self::$group = new Group( self::OPTION, self::fields() );
		}

		return self::$group;
	}

	/**
	 * One setting's value.
	 *
	 * @param string $key Which one.
	 * @return mixed
	 */
	public static function get( string $key ) {
		return self::group()->get( $key );
	}

	/**
	 * The settings themselves.
	 *
	 * @return array<int, Field>
	 */
	private static function fields(): array {
		return array(
			new Field(
				array(
					'key'         => 'default_access',
					'label'       => __( 'New audiobooks are', 'volumina' ),
					'type'        => 'radio',
					'default'     => Mode::PUBLIC,
					'choices'     => Mode::labels(),
					'description' => __( 'What a book you add from now on starts as. Every book can be changed on its own screen, and changing this never changes a book that already exists.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'append_to_content',
					'label'       => __( 'Book pages', 'volumina' ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Show the player and the chapters on an audiobook page automatically.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'logging',
					'label'       => __( 'Log', 'volumina' ),
					'type'        => 'checkbox',
					'default'     => false,
					'description' => __( 'Record notable events, such as a listener being turned away or an audio file that has gone missing. Useful when something is wrong and nobody can say quite what.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'delete_data_on_uninstall',
					'label'       => __( 'On uninstall', 'volumina' ),
					'type'        => 'checkbox',
					'default'     => false,
					'description' => __( 'Delete this plugin\'s tables and settings when it is uninstalled. Off means your listeners keep their places if you ever reinstall.', 'volumina' ),
				)
			),
		);
	}
}
