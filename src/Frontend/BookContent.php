<?php
/**
 * The audiobook's own content on its single page.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Frontend;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Player\Player;
use TUNET\Volumina\PostTypes\Chapter;
use TUNET\Volumina\Support\Duration;
use TUNET\Volumina\Support\Registrable;
use WP_Post;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Appends the cover, the details and the chapter list to a book's content.
 *
 * This renders through `the_content` rather than through a template file of
 * its own, so it lands inside whatever wrapper the active theme provides and
 * needs no opinion about the page around it. That is what makes it work the
 * same in a block theme and in a classic one.
 *
 * It is the minimum that proves the data model end to end. The blocks in S3
 * are the real presentation layer; nothing here is a contract, and none of it
 * is filterable on purpose — the public API is written in S4, deliberately,
 * and an extension point invented early is a promise made by accident.
 */
final class BookContent implements Registrable {

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'append' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Loads the stylesheet on a single book page only.
	 */
	public function enqueue(): void {
		if ( ! is_singular( Book::POST_TYPE ) ) {
			return;
		}

		wp_enqueue_style(
			'volumina-book',
			plugins_url( 'assets/css/book.css', PLUGIN_FILE ),
			array(),
			VERSION
		);
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

		$book = get_post();

		if ( ! $book instanceof WP_Post ) {
			return $content;
		}

		return $content . self::render( $book );
	}

	/**
	 * Renders the audiobook.
	 *
	 * @param WP_Post $book The book.
	 */
	private static function render( WP_Post $book ): string {
		$chapters = Chapter::for_book( $book->ID );
		$details  = self::details( $book, $chapters );
		$player   = Player::render( $book, $chapters );
		$playable = self::playable_ids( $chapters );

		ob_start();

		require plugin_dir_path( PLUGIN_FILE ) . 'templates/book.php';

		return (string) ob_get_clean();
	}

	/**
	 * The IDs of the chapters the player can actually move to.
	 *
	 * @param array<int, WP_Post> $chapters Chapters in order.
	 * @return array<int, int>
	 */
	private static function playable_ids( array $chapters ): array {
		$ids = array();

		foreach ( Player::playable( $chapters ) as $chapter ) {
			$ids[] = (int) $chapter['id'];
		}

		return $ids;
	}

	/**
	 * The details worth showing, already labelled and already formatted.
	 *
	 * Empty fields are left out rather than shown blank: a row reading
	 * "ISBN —" tells a listener nothing they wanted to know.
	 *
	 * @param WP_Post             $book     The book.
	 * @param array<int, WP_Post> $chapters Its chapters, in order.
	 * @return array<string, string>
	 */
	private static function details( WP_Post $book, array $chapters ): array {
		$details = array();

		$narrator = (string) get_post_meta( $book->ID, 'volumina_narrator', true );

		if ( '' !== $narrator ) {
			$details[ __( 'Narrated by', 'volumina' ) ] = $narrator;
		}

		$total = self::total_duration( $book, $chapters );

		if ( $total > 0 ) {
			$details[ __( 'Running time', 'volumina' ) ] = Duration::format( $total );
		}

		if ( array() !== $chapters ) {
			$details[ __( 'Chapters', 'volumina' ) ] = number_format_i18n( count( $chapters ) );
		}

		$publisher = (string) get_post_meta( $book->ID, 'volumina_publisher', true );

		if ( '' !== $publisher ) {
			$details[ __( 'Publisher', 'volumina' ) ] = $publisher;
		}

		$isbn = (string) get_post_meta( $book->ID, 'volumina_isbn', true );

		if ( '' !== $isbn ) {
			$details[ __( 'ISBN', 'volumina' ) ] = $isbn;
		}

		return $details;
	}

	/**
	 * How long the whole book runs, in whole seconds.
	 *
	 * The book's own total wins when it has one. Otherwise the chapters answer
	 * for it, which is exactly what the editor was promised on the book screen.
	 *
	 * @param WP_Post             $book     The book.
	 * @param array<int, WP_Post> $chapters Its chapters.
	 */
	private static function total_duration( WP_Post $book, array $chapters ): int {
		$stated = (int) get_post_meta( $book->ID, 'volumina_total_duration', true );

		if ( $stated > 0 ) {
			return $stated;
		}

		$total = 0;

		foreach ( $chapters as $chapter ) {
			$total += (int) get_post_meta( $chapter->ID, 'volumina_duration', true );
		}

		return $total;
	}
}
