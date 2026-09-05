<?php
/**
 * Which book a block is about.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Blocks;

use TUNET\Volumina\PostTypes\Book;
use WP_Block;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Answers the one question every book block has to ask first.
 *
 * A block either names a book or takes the one the page is already about, and
 * both blocks answer it the same way. It is here rather than in a base class
 * because it is a question, not a kind of block.
 */
final class ChosenBook {

	/**
	 * The book a block means, or null if it does not mean a readable one.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param WP_Block|null        $block      The block instance, for context.
	 */
	public static function resolve( array $attributes, ?WP_Block $block = null ): ?WP_Post {
		$chosen = isset( $attributes['bookId'] ) ? (int) $attributes['bookId'] : 0;

		if ( $chosen > 0 ) {
			return self::readable( $chosen );
		}

		// Nothing chosen means "the book this page is about", which is what an
		// Audiobook block dropped into a single book template should show.
		$context = ( null !== $block && isset( $block->context['postId'] ) )
			? (int) $block->context['postId']
			: 0;

		if ( $context > 0 ) {
			return self::readable( $context );
		}

		$current = get_post();

		return $current instanceof WP_Post ? self::readable( (int) $current->ID ) : null;
	}

	/**
	 * A book this viewer is allowed to see, or null.
	 *
	 * Drafts resolve for whoever can edit them, so that the editor previews a
	 * book that is not published yet. Nobody else gets one.
	 *
	 * @param int $post_id Candidate post.
	 */
	private static function readable( int $post_id ): ?WP_Post {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post || Book::POST_TYPE !== $post->post_type ) {
			return null;
		}

		if ( 'publish' === $post->post_status ) {
			return $post;
		}

		return current_user_can( 'edit_post', $post->ID ) ? $post : null;
	}

	/**
	 * A note for whoever is building the page, and nothing for a visitor.
	 *
	 * An unfinished block should tell an editor what it needs and leave a
	 * reader's page alone.
	 *
	 * @param string $message What the block is waiting for.
	 */
	public static function placeholder( string $message ): string {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		return sprintf(
			'<p %s>%s</p>',
			get_block_wrapper_attributes( array( 'class' => 'volumina-block-empty' ) ),
			esc_html( $message )
		);
	}
}
