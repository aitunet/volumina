<?php
/**
 * Database migrations.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Storage;

use TUNET\Volumina\Support\Logger;
use TUNET\Volumina\Support\Registrable;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Brings the database up to the version this code expects.
 *
 * The check runs on `plugins_loaded` rather than only on activation. Plugins
 * are frequently updated by replacing files, without ever being deactivated, so
 * an activation hook alone would leave those sites on an old schema. The check
 * itself is one read of an autoloaded option.
 */
final class Migrator implements Registrable {

	/**
	 * Schema version this code expects.
	 *
	 * Bump it whenever the schema changes, and record why in
	 * `docs/decisions.md`.
	 *
	 * 1: the progress table.
	 * 2: the grants table.
	 * 3: covers moved from `volumina_cover_id` to the featured image.
	 */
	public const DB_VERSION = '3';

	/**
	 * Option holding the version currently installed.
	 */
	public const OPTION = 'volumina_db_version';

	/**
	 * Option holding the plugin version whose rewrite rules are in place.
	 */
	public const REWRITE_OPTION = 'volumina_rewrite_version';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_migrate' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrites' ), 99 );

		// Deactivating and activating again leaves the version in place but may
		// well have rebuilt the rules without this plugin in between. Forget
		// the version on activation so the next request rebuilds them.
		register_activation_hook(
			PLUGIN_FILE,
			static function (): void {
				delete_option( self::REWRITE_OPTION );
			}
		);
	}

	/**
	 * Rebuilds the rewrite rules once per plugin version.
	 *
	 * The book post type brings permalinks with it, and WordPress only learns
	 * of them when the rules are rebuilt. Without this, a fresh install answers
	 * 404 for every audiobook until somebody saves the permalink settings. It
	 * runs late on `init`, after the post types are registered, because the
	 * rules are rebuilt from whatever is registered at that moment. Keyed on
	 * the plugin version for the same reason the schema check is not tied to
	 * activation: files get replaced without the plugin ever being deactivated.
	 * Activation itself forgets the version, so that case is covered too.
	 */
	public function maybe_flush_rewrites(): void {
		if ( VERSION === get_option( self::REWRITE_OPTION, '' ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::REWRITE_OPTION, VERSION, true );
	}

	/**
	 * Moves each book's cover from `volumina_cover_id` to the featured image.
	 *
	 * Idempotent: a book that already has a featured image keeps it, and the
	 * old meta is removed either way so this never runs twice for one book.
	 * A direct query rather than WP_Query, because this runs on
	 * `plugins_loaded`, before the post type is registered.
	 */
	private static function move_covers_to_featured_image(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				'volumina_cover_id'
			)
		);

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$book_id = (int) $row->post_id;
			$cover   = (int) $row->meta_value;

			if ( $cover > 0 && (int) get_post_thumbnail_id( $book_id ) <= 0 ) {
				update_post_meta( $book_id, '_thumbnail_id', $cover );
			}

			delete_post_meta( $book_id, 'volumina_cover_id' );
		}
	}

	/**
	 * Runs the migrations if the installed version is behind.
	 */
	public function maybe_migrate(): void {
		$installed = get_option( self::OPTION, '' );

		if ( self::DB_VERSION === $installed ) {
			return;
		}

		ProgressTable::install();
		GrantsTable::install();
		self::move_covers_to_featured_image();

		update_option( self::OPTION, self::DB_VERSION, true );

		// Recorded on `init` rather than here. The logger is configured from a
		// setting on `init`, and asking WordPress for a translated string
		// before then earns a notice of its own.
		add_action(
			'init',
			static function () use ( $installed ) {
				Logger::info(
					__( 'The database was brought up to date.', 'volumina' ),
					array(
						'from' => '' === $installed ? 'none' : $installed,
						'to'   => self::DB_VERSION,
					)
				);
			},
			20
		);
	}
}
