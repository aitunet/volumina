<?php
/**
 * The audiobook post type.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\PostTypes;

use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `volumina_book`: one audiobook, and the metadata describing it.
 *
 * The book itself carries no audio. Its chapters do.
 */
final class Book implements Registrable {

	/**
	 * Post type name.
	 */
	public const POST_TYPE = 'volumina_book';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Registers the post type.
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'                  => _x( 'Audiobooks', 'post type general name', 'volumina' ),
			'singular_name'         => _x( 'Audiobook', 'post type singular name', 'volumina' ),
			'add_new'               => __( 'Add Audiobook', 'volumina' ),
			'add_new_item'          => __( 'Add New Audiobook', 'volumina' ),
			'edit_item'             => __( 'Edit Audiobook', 'volumina' ),
			'new_item'              => __( 'New Audiobook', 'volumina' ),
			'view_item'             => __( 'View Audiobook', 'volumina' ),
			'view_items'            => __( 'View Audiobooks', 'volumina' ),
			'search_items'          => __( 'Search Audiobooks', 'volumina' ),
			'not_found'             => __( 'No audiobooks found.', 'volumina' ),
			'not_found_in_trash'    => __( 'No audiobooks found in Trash.', 'volumina' ),
			'all_items'             => __( 'All Audiobooks', 'volumina' ),
			'archives'              => __( 'Audiobook Archives', 'volumina' ),
			'attributes'            => __( 'Audiobook Attributes', 'volumina' ),
			'insert_into_item'      => __( 'Insert into audiobook', 'volumina' ),
			'uploaded_to_this_item' => __( 'Uploaded to this audiobook', 'volumina' ),
			'featured_image'        => __( 'Cover', 'volumina' ),
			'set_featured_image'    => __( 'Set cover', 'volumina' ),
			'remove_featured_image' => __( 'Remove cover', 'volumina' ),
			'use_featured_image'    => __( 'Use as cover', 'volumina' ),
			'item_published'        => __( 'Audiobook published.', 'volumina' ),
			'item_updated'          => __( 'Audiobook updated.', 'volumina' ),
			'menu_name'             => _x( 'Audiobooks', 'admin menu', 'volumina' ),
			'item_link'             => __( 'Audiobook Link', 'volumina' ),
			'item_link_description' => __( 'A link to an audiobook.', 'volumina' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => $labels,
				'description'     => __( 'An audiobook and the metadata that describes it.', 'volumina' ),
				'public'          => true,
				'show_in_rest'    => true,
				'has_archive'     => 'audiobooks',
				'rewrite'         => array(
					'slug'       => 'audiobook',
					'with_front' => false,
				),
				'menu_icon'       => 'dashicons-book-alt',
				'menu_position'   => 20,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'hierarchical'    => false,
			)
		);
	}

	/**
	 * Registers the book's meta.
	 *
	 * Everything is exposed to REST so the block editor can read and write it
	 * without a second, parallel save path.
	 */
	public function register_meta(): void {
		foreach ( self::meta_schema() as $key => $args ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'              => $args['type'],
					'description'       => $args['description'],
					'single'            => true,
					'default'           => $args['default'],
					'show_in_rest'      => true,
					'sanitize_callback' => $args['sanitize_callback'],
					'auth_callback'     => static function ( bool $allowed, string $meta_key, int $post_id ): bool {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}

	/**
	 * The meta this post type owns.
	 *
	 * Durations are integers in seconds, never floats: float seconds accumulate
	 * rounding error across resume cycles and compare badly.
	 *
	 * @return array<string, array{type: string, description: string, default: mixed, sanitize_callback: callable}>
	 */
	private static function meta_schema(): array {
		return array(
			'volumina_narrator'       => array(
				'type'              => 'string',
				'description'       => __( 'Who reads the book.', 'volumina' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'volumina_publisher'      => array(
				'type'              => 'string',
				'description'       => __( 'Who published the recording.', 'volumina' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'volumina_isbn'           => array(
				'type'              => 'string',
				'description'       => __( 'ISBN of the edition, if it has one.', 'volumina' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'volumina_language'       => array(
				'type'              => 'string',
				'description'       => __( 'Language the book is read in, as a locale code such as es_ES.', 'volumina' ),
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'volumina_total_duration' => array(
				'type'              => 'integer',
				'description'       => __( 'Total running time in whole seconds.', 'volumina' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'volumina_cover_id'       => array(
				'type'              => 'integer',
				'description'       => __( 'Attachment ID of the cover image.', 'volumina' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
		);
	}
}
