<?php
/**
 * The chapter details meta box.
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
 * Puts a chapter's book, audio file and running time on the chapter screen.
 *
 * Unlike the book's details, this box does not iterate the schema. Each of the
 * four keys is a different kind of control — a book chooser, a media picker, a
 * number read out of the file, and a position owned by another screen — so a
 * loop would have four exceptions and no rule.
 */
final class ChapterMetaBox implements Registrable {

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'volumina_save_chapter_meta';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'volumina_chapter_meta_nonce';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . Chapter::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . Chapter::POST_TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Registers the meta box.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'volumina-chapter-details',
			__( 'Chapter details', 'volumina' ),
			array( $this, 'render' ),
			Chapter::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Loads the media frame and the audio picker on the chapter screen only.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();

		if ( null === $screen || Chapter::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'volumina-admin-chapter-audio',
			plugins_url( 'assets/js/admin-chapter-audio.js', PLUGIN_FILE ),
			array(),
			VERSION,
			true
		);

		wp_localize_script(
			'volumina-admin-chapter-audio',
			'voluminaChapterAudio',
			array(
				'frameTitle'  => __( 'Choose an audio file', 'volumina' ),
				'frameButton' => __( 'Use this file', 'volumina' ),
				'noFile'      => __( 'No audio file chosen yet.', 'volumina' ),
			)
		);
	}

	/**
	 * Renders the meta box.
	 *
	 * @param WP_Post $post The chapter being edited.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<table class="form-table" role="presentation"><tbody>';

		$this->render_book_row( (int) get_post_meta( $post->ID, 'volumina_book_id', true ) );
		$this->render_audio_row( (int) get_post_meta( $post->ID, 'volumina_attachment_id', true ) );
		$this->render_duration_row( (int) get_post_meta( $post->ID, 'volumina_duration', true ) );
		$this->render_position_row( $post );

		echo '</tbody></table>';
	}

	/**
	 * Renders the book chooser.
	 *
	 * @param int $book_id Currently chosen book, or zero.
	 */
	private function render_book_row( int $book_id ): void {
		$books = get_posts(
			array(
				'post_type'   => Book::POST_TYPE,
				// A library has tens of books, not thousands.
				'numberposts' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts
				'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);

		echo '<tr><th scope="row"><label for="volumina_book_id">';
		echo esc_html__( 'Book', 'volumina' );
		echo '</label></th><td>';

		echo '<select id="volumina_book_id" name="volumina_book_id">';

		printf(
			'<option value="0">%s</option>',
			esc_html__( '— Not part of a book yet —', 'volumina' )
		);

		foreach ( $books as $book ) {
			$title = '' !== $book->post_title ? $book->post_title : __( '(untitled book)', 'volumina' );

			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $book->ID,
				selected( $book_id, $book->ID, false ),
				esc_html( $title )
			);
		}

		echo '</select>';

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'A chapter belongs to one book. Choose it and the chapter joins the end of that book, ready to be dragged into place from the book screen.', 'volumina' )
		);

		echo '</td></tr>';
	}

	/**
	 * Renders the audio picker.
	 *
	 * @param int $attachment_id Currently chosen attachment, or zero.
	 */
	private function render_audio_row( int $attachment_id ): void {
		echo '<tr><th scope="row">';
		echo esc_html__( 'Audio file', 'volumina' );
		echo '</th><td><div id="volumina-audio-field">';

		printf(
			'<p data-volumina-audio-name>%s</p>',
			esc_html( $this->file_label( $attachment_id ) )
		);

		printf(
			'<input type="hidden" id="volumina_attachment_id" name="volumina_attachment_id" value="%d" data-volumina-audio-input />',
			(int) $attachment_id
		);

		printf(
			'<p><button type="button" class="button" data-volumina-audio-select>%1$s</button> <button type="button" class="button-link" data-volumina-audio-clear>%2$s</button></p>',
			esc_html__( 'Choose audio file', 'volumina' ),
			esc_html__( 'Remove audio file', 'volumina' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Only audio files can be attached. The running time is read from the file.', 'volumina' )
		);

		echo '</div></td></tr>';
	}

	/**
	 * Renders the running time.
	 *
	 * @param int $duration Whole seconds.
	 */
	private function render_duration_row( int $duration ): void {
		echo '<tr><th scope="row"><label for="volumina_duration">';
		echo esc_html__( 'Running time', 'volumina' );
		echo '</label></th><td>';

		printf(
			'<input type="number" min="0" step="1" class="small-text" id="volumina_duration" name="volumina_duration" value="%1$d" data-volumina-audio-duration /> <span data-volumina-audio-readable>%2$s</span>',
			(int) $duration,
			esc_html( $duration > 0 ? Duration::format( $duration ) : '' )
		);

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'In whole seconds. Filled in from the audio file; correct it here if the file is wrong.', 'volumina' )
		);

		echo '</td></tr>';
	}

	/**
	 * Renders the chapter's position, which belongs to the book screen.
	 *
	 * It is shown and not edited on purpose: two screens that both set the
	 * position would be two sources of truth waiting to disagree.
	 *
	 * @param WP_Post $chapter The chapter being edited.
	 */
	private function render_position_row( WP_Post $chapter ): void {
		$order   = (int) get_post_meta( $chapter->ID, 'volumina_order', true );
		$book_id = (int) get_post_meta( $chapter->ID, 'volumina_book_id', true );

		echo '<tr><th scope="row">';
		echo esc_html__( 'Position', 'volumina' );
		echo '</th><td>';

		if ( $order > 0 ) {
			printf( '<strong>%d</strong>', (int) $order );
		} else {
			printf( '<em>%s</em>', esc_html__( 'Not placed yet', 'volumina' ) );
		}

		$edit_book = $book_id > 0 ? get_edit_post_link( $book_id ) : null;

		if ( is_string( $edit_book ) ) {
			printf(
				'<p class="description"><a href="%1$s">%2$s</a></p>',
				esc_url( $edit_book ),
				esc_html__( 'Reorder the chapters on the book screen.', 'volumina' )
			);
		} else {
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'Chapters are ordered on the book screen.', 'volumina' )
			);
		}

		echo '</td></tr>';
	}

	/**
	 * Saves the submitted meta.
	 *
	 * @param int $post_id The chapter being saved.
	 */
	public function save( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$was_in_book = (int) get_post_meta( $post_id, 'volumina_book_id', true );

		$book_id = isset( $_POST['volumina_book_id'] ) ? absint( wp_unslash( $_POST['volumina_book_id'] ) ) : 0;

		if ( Book::POST_TYPE !== get_post_type( $book_id ) ) {
			$book_id = 0;
		}

		$attachment_id = isset( $_POST['volumina_attachment_id'] ) ? absint( wp_unslash( $_POST['volumina_attachment_id'] ) ) : 0;

		if ( ! self::is_audio( $attachment_id ) ) {
			$attachment_id = 0;
		}

		$duration = isset( $_POST['volumina_duration'] ) ? absint( wp_unslash( $_POST['volumina_duration'] ) ) : 0;

		if ( 0 === $duration ) {
			$duration = self::duration_of( $attachment_id );
		}

		update_post_meta( $post_id, 'volumina_book_id', $book_id );
		update_post_meta( $post_id, 'volumina_attachment_id', $attachment_id );
		update_post_meta( $post_id, 'volumina_duration', $duration );

		$this->place_in_book( $post_id, $book_id, $was_in_book );
	}

	/**
	 * Gives the chapter a position at the end of its book.
	 *
	 * A chapter keeps the position it already has. It is placed only when it
	 * has none, or when it has just been moved to a different book, where its
	 * old number would mean nothing.
	 *
	 * @param int $chapter_id  The chapter being saved.
	 * @param int $book_id     The book it belongs to now, or zero.
	 * @param int $was_in_book The book it belonged to before this save.
	 */
	private function place_in_book( int $chapter_id, int $book_id, int $was_in_book ): void {
		if ( 0 === $book_id ) {
			update_post_meta( $chapter_id, 'volumina_order', 0 );

			return;
		}

		$order = (int) get_post_meta( $chapter_id, 'volumina_order', true );

		if ( $order > 0 && $book_id === $was_in_book ) {
			return;
		}

		update_post_meta( $chapter_id, 'volumina_order', Chapter::next_order( $book_id, $chapter_id ) );
	}

	/**
	 * A readable name for the chosen file.
	 *
	 * @param int $attachment_id Attachment to describe, or zero.
	 */
	private function file_label( int $attachment_id ): string {
		if ( ! self::is_audio( $attachment_id ) ) {
			return __( 'No audio file chosen yet.', 'volumina' );
		}

		$file = get_attached_file( $attachment_id );
		$name = is_string( $file ) ? basename( $file ) : (string) get_the_title( $attachment_id );

		$seconds = self::duration_of( $attachment_id );

		if ( $seconds > 0 ) {
			return sprintf(
				/* translators: 1: audio file name, 2: running time such as 1:02:05. */
				__( '%1$s (%2$s)', 'volumina' ),
				$name,
				Duration::format( $seconds )
			);
		}

		return $name;
	}

	/**
	 * Whether an attachment exists and actually holds audio.
	 *
	 * The MIME type is read from the attachment, never from anything the
	 * browser sent: a hidden field carries an ID, and an ID proves nothing.
	 *
	 * @param int $attachment_id Attachment to check.
	 */
	private static function is_audio( int $attachment_id ): bool {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return false;
		}

		$mime = get_post_mime_type( $attachment_id );

		return is_string( $mime ) && str_starts_with( $mime, 'audio/' );
	}

	/**
	 * The running time WordPress read out of the file, in whole seconds.
	 *
	 * @param int $attachment_id Attachment to measure.
	 */
	private static function duration_of( int $attachment_id ): int {
		if ( ! self::is_audio( $attachment_id ) ) {
			return 0;
		}

		$meta = wp_get_attachment_metadata( $attachment_id );

		if ( ! is_array( $meta ) || ! isset( $meta['length'] ) ) {
			return 0;
		}

		return max( 0, (int) $meta['length'] );
	}
}
