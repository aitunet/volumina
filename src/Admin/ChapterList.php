<?php
/**
 * The chapter list on the book editing screen.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\PostTypes\Chapter;
use TUNET\Volumina\Support\Duration;
use TUNET\Volumina\Support\Registrable;
use WP_Post;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Lists a book's chapters in order and lets an editor rearrange them.
 *
 * Dragging is not the only way to move a chapter. Every row also carries move
 * up and move down buttons, because an ordering tool that needs a mouse is an
 * ordering tool that some people cannot use at all.
 */
final class ChapterList implements Registrable {

	/**
	 * Nonce action, shared with the AJAX endpoint.
	 */
	private const NONCE_ACTION = 'volumina_reorder_chapters';

	/**
	 * AJAX action name. Internal to the admin, not part of the public API.
	 */
	private const AJAX_ACTION = 'volumina_reorder_chapters';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . Book::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'handle_reorder' ) );
	}

	/**
	 * Registers the meta box.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'volumina-chapter-list',
			__( 'Chapters', 'volumina' ),
			array( $this, 'render' ),
			Book::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Loads the reordering assets on the book screen only.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();

		if ( null === $screen || Book::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'volumina-admin-chapters',
			plugins_url( 'assets/css/admin-chapters.css', PLUGIN_FILE ),
			array(),
			VERSION
		);

		wp_enqueue_script(
			'volumina-admin-chapters',
			plugins_url( 'assets/js/admin-chapters.js', PLUGIN_FILE ),
			array(),
			VERSION,
			true
		);

		wp_localize_script(
			'volumina-admin-chapters',
			'voluminaChapters',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'error'   => __( 'The order could not be saved.', 'volumina' ),
				/* translators: 1: new position of the chapter, 2: total number of chapters. */
				'moved'   => __( 'Moved to position %1$d of %2$d.', 'volumina' ),
			)
		);
	}

	/**
	 * Renders the chapter list.
	 *
	 * @param WP_Post $post The book being edited.
	 */
	public function render( WP_Post $post ): void {
		$chapters = Chapter::for_book( $post->ID );

		printf(
			'<div id="volumina-chapter-list" data-book="%1$d" data-nonce="%2$s" data-ajax-action="%3$s">',
			(int) $post->ID,
			esc_attr( wp_create_nonce( self::NONCE_ACTION ) ),
			esc_attr( self::AJAX_ACTION )
		);

		if ( array() === $chapters ) {
			printf(
				'<p>%s</p>',
				esc_html__( 'This book has no chapters yet. A chapter is a title and an audio file; add them in the order they should be heard, and drag them afterwards if you change your mind.', 'volumina' )
			);
		} else {
			echo '<ol class="volumina-chapters">';

			foreach ( $chapters as $chapter ) {
				$this->render_row( $chapter );
			}

			echo '</ol>';

			printf(
				'<p class="description">%s</p>',
				esc_html__( 'Drag a chapter to move it, or use the arrows. The order is saved as soon as you change it.', 'volumina' )
			);
		}

		echo '<p class="volumina-chapters-status" role="status" aria-live="polite"></p>';

		$this->render_add_link( $post );

		echo '</div>';
	}

	/**
	 * The way out of an empty chapter list.
	 *
	 * A book that has never been saved has no ID for a chapter to belong to,
	 * so there is nothing to link to yet and saying so is better than a link
	 * that would make an orphan. Once the book exists, this is the only thing
	 * on the screen that tells somebody what to do next, which is why it is
	 * here rather than in the documentation nobody is going to read.
	 *
	 * @param WP_Post $post The book being edited.
	 */
	private function render_add_link( WP_Post $post ): void {
		if ( 'auto-draft' === $post->post_status ) {
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'Save this audiobook first, and you can add its chapters here.', 'volumina' )
			);

			return;
		}

		printf(
			'<p><a class="button" href="%1$s">%2$s</a></p>',
			esc_url(
				add_query_arg(
					array(
						'post_type'        => Chapter::POST_TYPE,
						'volumina_book_id' => (int) $post->ID,
					),
					admin_url( 'post-new.php' )
				)
			),
			esc_html__( 'Add a chapter', 'volumina' )
		);
	}

	/**
	 * Renders one row.
	 *
	 * @param WP_Post $chapter The chapter.
	 */
	private function render_row( WP_Post $chapter ): void {
		$duration = (int) get_post_meta( $chapter->ID, 'volumina_duration', true );
		$title    = '' !== $chapter->post_title ? $chapter->post_title : __( '(untitled chapter)', 'volumina' );
		$edit     = get_edit_post_link( $chapter->ID );

		printf(
			'<li class="volumina-chapter" draggable="true" data-id="%d">',
			(int) $chapter->ID
		);

		echo '<span class="volumina-chapter-handle" aria-hidden="true"></span>';

		echo '<span class="volumina-chapter-title">';

		if ( is_string( $edit ) ) {
			printf( '<a href="%1$s">%2$s</a>', esc_url( $edit ), esc_html( $title ) );
		} else {
			echo esc_html( $title );
		}

		echo '</span>';

		printf(
			'<span class="volumina-chapter-duration">%s</span>',
			esc_html( $duration > 0 ? Duration::format( $duration ) : '-' )
		);

		echo '<span class="volumina-chapter-move">';

		printf(
			'<button type="button" class="button" data-move="up" aria-label="%1$s">%2$s</button>',
			/* translators: %s: chapter title. */
			esc_attr( sprintf( __( 'Move %s up', 'volumina' ), $title ) ),
			esc_html__( 'Up', 'volumina' )
		);

		printf(
			'<button type="button" class="button" data-move="down" aria-label="%1$s">%2$s</button>',
			/* translators: %s: chapter title. */
			esc_attr( sprintf( __( 'Move %s down', 'volumina' ), $title ) ),
			esc_html__( 'Down', 'volumina' )
		);

		echo '</span></li>';
	}

	/**
	 * Saves a new chapter order.
	 *
	 * Every submitted ID is checked against the book it claims to belong to
	 * before anything is written, so this endpoint cannot be used to renumber
	 * posts that are none of its business.
	 */
	public function handle_reorder(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$book_id = isset( $_POST['book_id'] ) ? absint( wp_unslash( $_POST['book_id'] ) ) : 0;

		if ( 0 === $book_id || ! current_user_can( 'edit_post', $book_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You cannot edit this book.', 'volumina' ) ), 403 );
		}

		$submitted = isset( $_POST['order'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['order'] ) ) : array();
		$position  = 0;
		$saved     = 0;

		foreach ( $submitted as $chapter_id ) {
			if ( 0 === $chapter_id ) {
				continue;
			}

			if ( Chapter::POST_TYPE !== get_post_type( $chapter_id ) ) {
				continue;
			}

			if ( (int) get_post_meta( $chapter_id, 'volumina_book_id', true ) !== $book_id ) {
				continue;
			}

			++$position;

			update_post_meta( $chapter_id, 'volumina_order', $position );
			++$saved;
		}

		wp_send_json_success(
			array(
				'saved'   => $saved,
				'message' => __( 'Order saved.', 'volumina' ),
			)
		);
	}
}
