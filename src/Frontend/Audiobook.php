<?php
/**
 * Rendering an audiobook.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Frontend;

use TUNET\Volumina\Player\Player;
use TUNET\Volumina\PostTypes\Chapter;
use TUNET\Volumina\Support\Duration;
use WP_Post;

use const TUNET\Volumina\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * The one place an audiobook turns into markup.
 *
 * The single book page reaches it through a content filter and the Audiobook
 * block reaches it directly. Both get the same thing, which is the point: two
 * renderers for one object drift apart, and the first anyone hears about it is
 * a bug report about the block looking wrong.
 */
final class Audiobook {

	/**
	 * Renders a book.
	 *
	 * @param WP_Post             $book    The book.
	 * @param array<string, bool> $options Which parts to include.
	 */
	public static function render( WP_Post $book, array $options = array() ): string {
		$show = array_merge(
			array(
				'cover'    => true,
				'details'  => true,
				'player'   => true,
				'chapters' => true,
				'heading'  => true,
			),
			$options
		);

		$chapters = Chapter::for_book( $book->ID );
		$details  = $show['details'] ? self::details( $book, $chapters ) : array();
		$player   = $show['player'] ? Player::render( $book, $chapters ) : '';

		ob_start();

		require plugin_dir_path( PLUGIN_FILE ) . 'templates/book.php';

		return (string) ob_get_clean();
	}

	/**
	 * Renders a book's chapter list on its own.
	 *
	 * @param WP_Post $book      The book.
	 * @param bool    $durations Whether to show running times.
	 */
	public static function chapter_list( WP_Post $book, bool $durations = true ): string {
		$chapters           = Chapter::for_book( $book->ID );
		$volumina_durations = $durations;

		ob_start();

		require plugin_dir_path( PLUGIN_FILE ) . 'templates/chapters.php';

		return (string) ob_get_clean();
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
	public static function details( WP_Post $book, array $chapters ): array {
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
	public static function total_duration( WP_Post $book, array $chapters ): int {
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
