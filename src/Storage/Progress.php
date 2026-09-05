<?php
/**
 * Reading and writing a listener's position in a book.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * The only code that touches the progress table.
 *
 * One row per listener per book, which is what the primary key already
 * guarantees. Where a listener is inside the book is the chapter plus the
 * offset into it; the table holds no history, because a bookmark is not a log.
 */
final class Progress {

	/**
	 * Where a listener had got to, or null if they have never started.
	 *
	 * @param int $user_id Listener.
	 * @param int $book_id Book.
	 * @return array{chapter_id: int, position_seconds: int, updated_at: string}|null
	 */
	public static function get( int $user_id, int $book_id ): ?array {
		global $wpdb;

		if ( $user_id <= 0 || $book_id <= 0 ) {
			return null;
		}

		$table = ProgressTable::table_name();

		// The table name is built from $wpdb->prefix and a constant, never from
		// input, which is why it can be interpolated; every value is prepared.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT chapter_id, position_seconds, updated_at FROM {$table} WHERE user_id = %d AND book_id = %d",
				$user_id,
				$book_id
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return array(
			'chapter_id'       => (int) $row['chapter_id'],
			'position_seconds' => (int) $row['position_seconds'],
			'updated_at'       => (string) $row['updated_at'],
		);
	}

	/**
	 * Records where a listener has got to.
	 *
	 * `REPLACE` rather than a read then a write: the primary key is
	 * `(user_id, book_id)`, so the database can decide whether this is a first
	 * bookmark or a moved one, and two tabs saving at once cannot produce two
	 * rows.
	 *
	 * @param int $user_id    Listener.
	 * @param int $book_id    Book.
	 * @param int $chapter_id Chapter they are in.
	 * @param int $position   Offset into that chapter, in whole seconds.
	 */
	public static function save( int $user_id, int $book_id, int $chapter_id, int $position ): bool {
		global $wpdb;

		if ( $user_id <= 0 || $book_id <= 0 ) {
			return false;
		}

		// A custom table has no WordPress API to go through, and a position
		// that moves every ten seconds is not cache material.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->replace(
			ProgressTable::table_name(),
			array(
				'user_id'          => $user_id,
				'book_id'          => $book_id,
				'chapter_id'       => max( 0, $chapter_id ),
				'position_seconds' => max( 0, $position ),
				'updated_at'       => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%d', '%d', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Forgets a listener's place in one book.
	 *
	 * @param int $user_id Listener.
	 * @param int $book_id Book.
	 */
	public static function forget( int $user_id, int $book_id ): bool {
		global $wpdb;

		if ( $user_id <= 0 || $book_id <= 0 ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			ProgressTable::table_name(),
			array(
				'user_id' => $user_id,
				'book_id' => $book_id,
			),
			array( '%d', '%d' )
		);

		return false !== $result;
	}
}
