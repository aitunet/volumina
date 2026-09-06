<?php
/**
 * The manual access grants table.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the schema of `{prefix}volumina_grants`.
 *
 * A grant is a relation between a listener and a book, and both directions of
 * it get asked in earnest: the audio endpoint asks whether this person may hear
 * this book, and an admin screen asks who may hear it at all. A serialised
 * array in post meta answers the first question and scans the whole table for
 * the second, so this is a table with an index on each side.
 *
 * `(user_id, book_id)` is the primary key rather than a surrogate: a listener
 * either has a grant for a book or does not, and a second row would mean
 * nothing.
 */
final class GrantsTable {

	/**
	 * Table name, without the site prefix.
	 */
	public const TABLE = 'volumina_grants';

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
			granted_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			granted_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (user_id,book_id),
			KEY book_id (book_id),
			KEY granted_at (granted_at)
		) {$collate};";

		dbDelta( $sql );
	}
}
