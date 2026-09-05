<?php
/**
 * The chapter post type.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\PostTypes;

use TUNET\Volumina\Support\Registrable;
use WP_Post;
use WP_Query;

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
	 * The chapters of a book, in reading order.
	 *
	 * Sorted on `volumina_order`, with the post ID as the tie-break so that two
	 * chapters sharing a number still come back in a stable sequence instead of
	 * whatever order the database felt like.
	 *
	 * The sort happens in PHP rather than in SQL on purpose. Ordering by a meta
	 * key inside `WP_Query` joins on that key, and a chapter that has never been
	 * given a position has no row to join to, so it would drop out of its own
	 * book without a sound. A book has tens of chapters and the query has already
	 * primed the meta cache, so sorting here costs nothing and cannot lose one.
	 *
	 * @param int $book_id Book to fetch the chapters of.
	 * @return array<int, WP_Post>
	 */
	public static function for_book( int $book_id ): array {
		if ( $book_id <= 0 ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				// A book has tens of chapters, not thousands.
				'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'volumina_book_id',
						'value'   => $book_id,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
				),
				'no_found_rows'  => true,
			)
		);

		/**
		 * WP_Query returns posts when no `fields` argument narrows the result.
		 *
		 * @var array<int, WP_Post> $posts
		 */
		$posts = $query->posts;

		usort(
			$posts,
			static function ( WP_Post $first, WP_Post $second ): int {
				$first_order  = (int) get_post_meta( $first->ID, 'volumina_order', true );
				$second_order = (int) get_post_meta( $second->ID, 'volumina_order', true );

				// Position zero means not placed yet. Those belong at the end, where
				// an editor will find them, not at the top pretending to be chapter one.
				$first_order  = $first_order > 0 ? $first_order : PHP_INT_MAX;
				$second_order = $second_order > 0 ? $second_order : PHP_INT_MAX;

				return $first_order !== $second_order ? $first_order <=> $second_order : $first->ID <=> $second->ID;
			}
		);

		return $posts;
	}

	/**
	 * The position a chapter would take at the end of a book.
	 *
	 * Counted from the highest position already in use rather than from the
	 * number of chapters, so a book whose numbering has gaps does not hand the
	 * same position to two chapters.
	 *
	 * @param int $book_id Book to append to.
	 * @param int $ignore   Chapter to leave out of the count, normally the one
	 *                      being saved.
	 */
	public static function next_order( int $book_id, int $ignore = 0 ): int {
		$highest = 0;

		foreach ( self::for_book( $book_id ) as $chapter ) {
			if ( $chapter->ID === $ignore ) {
				continue;
			}

			$highest = max( $highest, (int) get_post_meta( $chapter->ID, 'volumina_order', true ) );
		}

		return $highest + 1;
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
