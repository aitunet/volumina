<?php
/**
 * Book taxonomies.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\PostTypes;

use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the two ways a library is browsed: by genre and by series.
 *
 * Both are hierarchical. Genre because "Fiction > Science fiction" is a real
 * relationship, series because a checkbox list makes an editor pick the series
 * that already exists instead of typing a near-duplicate of it.
 */
final class Taxonomies implements Registrable {

	/**
	 * Genre taxonomy name.
	 */
	public const GENRE = 'volumina_genre';

	/**
	 * Series taxonomy name.
	 */
	public const SERIES = 'volumina_series';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Registers both taxonomies against the book post type.
	 */
	public function register_taxonomies(): void {
		register_taxonomy(
			self::GENRE,
			array( Book::POST_TYPE ),
			array(
				'labels'            => $this->genre_labels(),
				'description'       => __( 'What kind of book it is.', 'volumina' ),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'         => 'genre',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);

		register_taxonomy(
			self::SERIES,
			array( Book::POST_TYPE ),
			array(
				'labels'            => $this->series_labels(),
				'description'       => __( 'The series the book belongs to, if any.', 'volumina' ),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'         => 'series',
					'with_front'   => false,
					'hierarchical' => false,
				),
			)
		);
	}

	/**
	 * Labels for the genre taxonomy.
	 *
	 * @return array<string, string>
	 */
	private function genre_labels(): array {
		return array(
			'name'              => _x( 'Genres', 'taxonomy general name', 'volumina' ),
			'singular_name'     => _x( 'Genre', 'taxonomy singular name', 'volumina' ),
			'search_items'      => __( 'Search Genres', 'volumina' ),
			'all_items'         => __( 'All Genres', 'volumina' ),
			'parent_item'       => __( 'Parent Genre', 'volumina' ),
			'parent_item_colon' => __( 'Parent Genre:', 'volumina' ),
			'edit_item'         => __( 'Edit Genre', 'volumina' ),
			'update_item'       => __( 'Update Genre', 'volumina' ),
			'add_new_item'      => __( 'Add New Genre', 'volumina' ),
			'new_item_name'     => __( 'New Genre Name', 'volumina' ),
			'not_found'         => __( 'No genres found.', 'volumina' ),
			'menu_name'         => __( 'Genres', 'volumina' ),
		);
	}

	/**
	 * Labels for the series taxonomy.
	 *
	 * @return array<string, string>
	 */
	private function series_labels(): array {
		return array(
			'name'              => _x( 'Series', 'taxonomy general name', 'volumina' ),
			'singular_name'     => _x( 'Series', 'taxonomy singular name', 'volumina' ),
			'search_items'      => __( 'Search Series', 'volumina' ),
			'all_items'         => __( 'All Series', 'volumina' ),
			'parent_item'       => __( 'Parent Series', 'volumina' ),
			'parent_item_colon' => __( 'Parent Series:', 'volumina' ),
			'edit_item'         => __( 'Edit Series', 'volumina' ),
			'update_item'       => __( 'Update Series', 'volumina' ),
			'add_new_item'      => __( 'Add New Series', 'volumina' ),
			'new_item_name'     => __( 'New Series Name', 'volumina' ),
			'not_found'         => __( 'No series found.', 'volumina' ),
			'menu_name'         => __( 'Series', 'volumina' ),
		);
	}
}
