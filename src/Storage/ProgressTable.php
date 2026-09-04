<?php
/**
 * The listening progress table.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the schema of `{prefix}volumina_progress`.
 *
 * Progress lives in a table of its own rather than in post meta because it is
 * high-write, per-user, machine-generated data with no editorial life. In
 * `wp_postmeta` it would grow without bound and slow every meta query on the
 * site down with it.
 *
 * There is no surrogate key. A listener has exactly one position per book, so
 * `(user_id, book_id)` is the primary key and the uniqueness constraint at the
 * same time: a duplicate row is not a thing that should be possible.
 */
final class ProgressTable {

	/**
	 * Table name, without the site prefix.
	 */
	public const TABLE = 'volumina_progress';

	/**
	 * The full table name for this site.
	 */
	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Creates or updates the table.
	 *
	 * `dbDelta` compares the statement below against what exists and issues the
	 * difference, so this is safe to run repeatedly.
	 */
	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table_name();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			user_id bigint(20) unsigned NOT NULL,
			book_id bigint(20) unsigned NOT NULL,
			chapter_id bigint(20) unsigned NOT NULL DEFAULT 0,
			position_seconds int(10) unsigned NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (user_id,book_id),
			KEY book_id (book_id),
			KEY updated_at (updated_at)
		) {$collate};";

		dbDelta( $sql );
	}
}
