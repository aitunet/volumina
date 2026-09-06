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
	 */
	public const DB_VERSION = '2';

	/**
	 * Option holding the version currently installed.
	 */
	public const OPTION = 'volumina_db_version';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'plugins_loaded', array( $this, 'maybe_migrate' ) );
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
