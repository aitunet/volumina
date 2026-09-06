<?php
/**
 * Help tabs on the book and chapter screens.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\PostTypes\Chapter;
use TUNET\Volumina\Support\Help;
use TUNET\Volumina\Support\Registrable;

defined( 'ABSPATH' ) || exit;

/**
 * Fills in the Help tab on the screens WordPress made for us.
 *
 * The screens the registry adds carry their own help. These two do not exist as
 * classes of ours at all — they are the post type screens WordPress generates —
 * so their help is added here, from the outside, on `current_screen`.
 */
final class PostScreenHelp implements Registrable {

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'current_screen', array( $this, 'add_help' ) );
	}

	/**
	 * Adds help to whichever of our screens is being drawn.
	 */
	public function add_help(): void {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		// The registry's own screens live under the same post type and carry
		// their own help. Only the two screens WordPress generates are ours to
		// fill in from out here.
		if ( 'post' !== $screen->base && 'edit' !== $screen->base ) {
			return;
		}

		if ( Book::POST_TYPE === $screen->post_type ) {
			$this->book_help( 'post' === $screen->base );
			return;
		}

		if ( Chapter::POST_TYPE === $screen->post_type ) {
			$this->chapter_help();
		}
	}

	/**
	 * Help for the book screens.
	 *
	 * @param bool $editing Whether this is one book rather than the list.
	 */
	private function book_help( bool $editing ): void {
		$tabs = array(
			array(
				'id'      => 'volumina-book-overview',
				'title'   => __( 'Audiobooks', 'volumina' ),
				'content' => Help::p( __( 'An audiobook is a book with chapters. The book holds the cover, the narrator and everything a listener reads; the chapters hold the audio.', 'volumina' ) )
					. Help::p( __( 'Publish the book when you want it to appear on your site. Its chapters do not need publishing separately.', 'volumina' ) ),
			),
		);

		if ( $editing ) {
			$tabs[] = array(
				'id'      => 'volumina-book-chapters',
				'title'   => __( 'Chapters', 'volumina' ),
				'content' => Help::p( __( 'Add chapters in the Chapters box below, then drag them into the order they should be heard. The order you see is the order a listener gets.', 'volumina' ) )
					. Help::p( __( 'Each chapter takes one audio file from your media library. A chapter with no file stays in the list on the page and is skipped by the player.', 'volumina' ) ),
			);

			$tabs[] = array(
				'id'      => 'volumina-book-access',
				'title'   => __( 'Who can listen', 'volumina' ),
				'content' => Help::p( __( 'A public audiobook can be heard by anyone who finds it. A restricted one still appears on your site with its cover, its details and its chapter list, and only the audio is held back.', 'volumina' ) )
					. Help::p( __( 'You can always hear your own books, whatever this says, so that you can check them.', 'volumina' ) ),
			);

			$tabs[] = array(
				'id'      => 'volumina-book-duration',
				'title'   => __( 'Running time', 'volumina' ),
				'content' => Help::p( __( 'Leave the total at zero and the chapters answer for it: the book shows however long its chapters add up to. Fill it in only when you want to say something different.', 'volumina' ) ),
			);
		}

		Help::add( $tabs );
	}

	/**
	 * Help for the chapter screens.
	 */
	private function chapter_help(): void {
		Help::add(
			array(
				array(
					'id'      => 'volumina-chapter-overview',
					'title'   => __( 'Chapters', 'volumina' ),
					'content' => Help::p( __( 'A chapter is a title, an audio file and a place in a book. It is easier to add chapters from the book they belong to, where you can also drag them into order.', 'volumina' ) )
						. Help::p( __( 'The running time is in whole seconds. Leave it at zero if you do not know it; the player still works, and the chapter simply shows no time next to it.', 'volumina' ) ),
				),
			)
		);
	}
}
