<?php
/**
 * The audiobook's own content on its single page.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Frontend;

use TUNET\Volumina\Admin\Settings;
use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Support\Registrable;
use TUNET\Volumina\Support\RenderOnce;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Appends the audiobook to a book's content, for themes that do nothing else.
 *
 * This renders through `the_content` rather than through a template file of
 * its own, so it lands inside whatever wrapper the active theme provides and
 * needs no opinion about the page around it. That is what makes it work the
 * same in a block theme and in a classic one — and it is why it stays now that
 * the blocks exist: a classic theme has no block template for a custom post
 * type, so without this a book page in one would be empty.
 *
 * Where an Audiobook block is present the block wins, whichever of the two
 * happens to render first. See `Support\RenderOnce`.
 */
final class BookContent implements Registrable {

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'append' ) );
		add_filter( 'post_thumbnail_html', array( $this, 'withhold_theme_cover' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * The key that keeps the filter and the block from both rendering.
	 *
	 * @param int $book_id The book.
	 */
	public static function key( int $book_id ): string {
		return 'audiobook:' . $book_id;
	}

	/**
	 * Loads the stylesheet on a single book page only.
	 */
	public function enqueue(): void {
		if ( ! is_singular( Book::POST_TYPE ) ) {
			return;
		}

		// Nothing to style when nothing is appended. A site placing the block
		// itself gets the same stylesheet from the block, where it belongs.
		if ( ! Settings::get( 'append_to_content' ) ) {
			return;
		}

		wp_enqueue_style( Assets::BOOK );
	}

	/**
	 * Keeps the theme from showing the cover a second time.
	 *
	 * The cover is the featured image, so a theme prints it at the top of the
	 * page, large, and the audiobook appended below prints it again, small,
	 * beside the details. The small one is the book's own; the theme's is
	 * withheld — only on the book's page, only for the book itself, and only
	 * while the audiobook is being appended. A page whose author placed the
	 * Audiobook block by hand is left alone: the block's own settings decide
	 * whether a cover shows, and the theme's may be the one they kept.
	 *
	 * @param string $html    The image the theme is about to print.
	 * @param int    $post_id The post it belongs to.
	 */
	public function withhold_theme_cover( string $html, int $post_id ): string {
		if ( ! is_singular( Book::POST_TYPE ) || get_queried_object_id() !== $post_id ) {
			return $html;
		}

		if ( ! Settings::get( 'append_to_content' ) ) {
			return $html;
		}

		$book = get_post( $post_id );

		if ( $book instanceof WP_Post && has_block( 'volumina/audiobook', $book ) ) {
			return $html;
		}

		return '';
	}

	/**
	 * Appends the audiobook to the post content.
	 *
	 * @param string $content The content WordPress is about to show.
	 */
	public function append( string $content ): string {
		if ( ! is_singular( Book::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		// A site that places the Audiobook block itself can turn this off, and
		// then a book page is whatever its author put on it.
		if ( ! Settings::get( 'append_to_content' ) ) {
			return $content;
		}

		$book = get_post();

		if ( ! $book instanceof WP_Post ) {
			return $content;
		}

		// An Audiobook block in the post's own content is a deliberate choice
		// by whoever wrote the post, and it says where the book should appear.
		// Appending as well would show it twice.
		if ( has_block( 'volumina/audiobook', $book ) ) {
			return $content;
		}

		if ( ! RenderOnce::claim( self::key( (int) $book->ID ) ) ) {
			return $content;
		}

		return $content . Audiobook::render( $book );
	}
}
