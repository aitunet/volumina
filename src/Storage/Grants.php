<?php
/**
 * Manual access grants.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes who has been given a book by hand.
 *
 * Deliberately dumb: it records and reports grants and decides nothing. Whether
 * a grant is what lets somebody listen is `Access\AccessManager`'s question,
 * and the answer can come from somewhere else entirely.
 */
final class Grants {

	/**
	 * Whether this listener has been given this book.
	 *
	 * @param int $user_id Listener.
	 * @param int $book_id Book.
	 */
	public static function has( int $user_id, int $book_id ): bool {
		global $wpdb;

		if ( $user_id <= 0 || $book_id <= 0 ) {
			return false;
		}

		$table = GrantsTable::table_name();

		// The table name is built from $wpdb->prefix and a constant, never from
		// input, which is why it can be interpolated; every value is prepared.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT 1 FROM {$table} WHERE user_id = %d AND book_id = %d",
				$user_id,
				$book_id
			)
		);

		return null !== $found;
	}

	/**
	 * Gives a listener a book.
	 *
	 * `REPLACE` rather than a read and then a write: the primary key is
	 * `(user_id, book_id)`, so granting twice is granting once.
	 *
	 * @param int $user_id    Listener.
	 * @param int $book_id    Book.
	 * @param int $granted_by Who granted it, or zero when nobody did.
	 */
	public static function grant( int $user_id, int $book_id, int $granted_by = 0 ): bool {
		global $wpdb;

		if ( $user_id <= 0 || $book_id <= 0 ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->replace(
			GrantsTable::table_name(),
			array(
				'user_id'    => $user_id,
				'book_id'    => $book_id,
				'granted_at' => current_time( 'mysql', true ),
				'granted_by' => max( 0, $granted_by ),
			),
			array( '%d', '%d', '%s', '%d' )
		);

		return false !== $result;
	}

	/**
	 * Takes a book back.
	 *
	 * @param int $user_id Listener.
	 * @param int $book_id Book.
	 */
	public static function revoke( int $user_id, int $book_id ): bool {
		global $wpdb;

		if ( $user_id <= 0 || $book_id <= 0 ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			GrantsTable::table_name(),
			array(
				'user_id' => $user_id,
				'book_id' => $book_id,
			),
			array( '%d', '%d' )
		);

		return false !== $result;
	}

	/**
	 * Everyone who has been given a book, most recently granted first.
	 *
	 * @param int $book_id Book.
	 * @param int $limit   How many at most.
	 * @return array<int, int> Listener IDs.
	 */
	public static function for_book( int $book_id, int $limit = 200 ): array {
		return self::column( 'user_id', 'book_id', $book_id, $limit );
	}

	/**
	 * Every book a listener has been given, most recently granted first.
	 *
	 * @param int $user_id Listener.
	 * @param int $limit   How many at most.
	 * @return array<int, int> Book IDs.
	 */
	public static function for_user( int $user_id, int $limit = 200 ): array {
		return self::column( 'book_id', 'user_id', $user_id, $limit );
	}

	/**
	 * One side of the relation, given the other.
	 *
	 * Both column names come from this file and never from a caller, which is
	 * what makes it safe to put them in the statement.
	 *
	 * @param string $wanted Column to return: `user_id` or `book_id`.
	 * @param string $known  Column to match on: the other one.
	 * @param int    $id     Value to match.
	 * @param int    $limit  How many at most.
	 * @return array<int, int>
	 */
	private static function column( string $wanted, string $known, int $id, int $limit ): array {
		global $wpdb;

		$limit = max( 1, min( 1000, $limit ) );

		if ( $id <= 0 ) {
			return array();
		}

		$table = GrantsTable::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT {$wanted} FROM {$table} WHERE {$known} = %d ORDER BY granted_at DESC LIMIT %d",
				$id,
				$limit
			)
		);

		return is_array( $rows ) ? array_map( 'intval', $rows ) : array();
	}
}
