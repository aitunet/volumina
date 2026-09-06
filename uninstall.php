<?php
/**
 * Uninstall routine.
 *
 * Removes the plugin's tables and options, but only when the site owner has
 * explicitly opted in. Deleting a listener's progress by accident is not
 * recoverable, so silence means keep.
 *
 * This file runs on its own, with no plugin loaded and no autoloader, which is
 * why it names its tables and options in full rather than asking a class for
 * them. Whenever a table or an option joins the plugin, it joins this file too.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$volumina_settings = get_option( 'volumina_settings', array() );

if ( ! is_array( $volumina_settings ) || empty( $volumina_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// Books and chapters are ordinary posts and go with their post type, meta and
// all. Deleting them here rather than leaving orphans behind is the whole point
// of having asked.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$volumina_post_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( %s, %s )",
		'volumina_book',
		'volumina_chapter'
	)
);

foreach ( $volumina_post_ids as $volumina_post_id ) {
	// Not `force_delete`: this is the point of no return the site owner asked
	// for, and the trash would only leave the rows behind under another name.
	wp_delete_post( (int) $volumina_post_id, true );
}

// The taxonomies go with the posts, but their terms do not.
foreach ( array( 'volumina_genre', 'volumina_series' ) as $volumina_taxonomy ) {
	$volumina_terms = get_terms(
		array(
			'taxonomy'   => $volumina_taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( is_array( $volumina_terms ) ) {
		foreach ( $volumina_terms as $volumina_term_id ) {
			wp_delete_term( (int) $volumina_term_id, $volumina_taxonomy );
		}
	}
}

// Both tables. The names are built from the site prefix and a literal, never
// from input, which is what makes interpolating them safe.
foreach ( array( 'volumina_progress', 'volumina_grants' ) as $volumina_table ) {
	$volumina_full = $wpdb->prefix . $volumina_table;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$volumina_full}" );
}

foreach (
	array(
		'volumina_settings',
		'volumina_log',
		'volumina_db_version',
		'volumina_setup_done',
	) as $volumina_option
) {
	delete_option( $volumina_option );
}
