<?php
/**
 * The chapter post type.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\PostTypes;

use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `volumina_chapter`: one chapter of one book.
 *
 * A chapter is a post type rather than a repeated field on the book because it
 * needs an identity of its own. Listening progress, and later bookmarks, point
 * at a chapter ID; a row in a repeater has nothing to point at.
 *
 * Chapters have no front end of their own. They are reached through their book
 * and played by the player, so the post type is not publicly queryable.
 */
final class Chapter implements Registrable {

	/**
	 * Post type name.
	 */
	public const POST_TYPE = 'volumina_chapter';

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
			'name'                  => _x( 'Chapters', 'post type general name', 'volumina' ),
			'singular_name'         => _x( 'Chapter', 'post type singular name', 'volumina' ),
			'add_new'               => __( 'Add Chapter', 'volumina' ),
			'add_new_item'          => __( 'Add New Chapter', 'volumina' ),
			'edit_item'             => __( 'Edit Chapter', 'volumina' ),
			'new_item'              => __( 'New Chapter', 'volumina' ),
			'view_item'             => __( 'View Chapter', 'volumina' ),
			'search_items'          => __( 'Search Chapters', 'volumina' ),
			'not_found'             => __( 'No chapters found.', 'volumina' ),
			'not_found_in_trash'    => __( 'No chapters found in Trash.', 'volumina' ),
			'all_items'             => __( 'Chapters', 'volumina' ),
			'insert_into_item'      => __( 'Insert into chapter', 'volumina' ),
			'uploaded_to_this_item' => __( 'Uploaded to this chapter', 'volumina' ),
			'item_published'        => __( 'Chapter published.', 'volumina' ),
			'item_updated'          => __( 'Chapter updated.', 'volumina' ),
			'menu_name'             => _x( 'Chapters', 'admin menu', 'volumina' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'description'        => __( 'One chapter of an audiobook.', 'volumina' ),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => 'edit.php?post_type=' . Book::POST_TYPE,
				'show_in_rest'       => true,
				'has_archive'        => false,
				'rewrite'            => false,
				'supports'           => array( 'title' ),
				'capability_type'    => 'post',
				'map_meta_cap'       => true,
				'hierarchical'       => false,
			)
		);
	}

	/**
	 * Registers the chapter's meta.
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
	 * Durations are integers in seconds, never floats.
	 *
	 * @return array<string, array{type: string, description: string, default: mixed, sanitize_callback: callable}>
	 */
	private static function meta_schema(): array {
		return array(
			'volumina_book_id'       => array(
				'type'              => 'integer',
				'description'       => __( 'ID of the book this chapter belongs to.', 'volumina' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'volumina_attachment_id' => array(
				'type'              => 'integer',
				'description'       => __( 'Attachment ID of the audio file.', 'volumina' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'volumina_duration'      => array(
				'type'              => 'integer',
				'description'       => __( 'Running time in whole seconds.', 'volumina' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'volumina_order'         => array(
				'type'              => 'integer',
				'description'       => __( 'Position within the book, counting from one.', 'volumina' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
		);
	}
}
