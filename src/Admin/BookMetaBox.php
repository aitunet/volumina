<?php
/**
 * The audiobook details meta box.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Support\Registrable;
use WP_Post;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Puts the book's own metadata on the book editing screen.
 *
 * The field list is not written out here. It is read from `Book::meta_keys()`,
 * so a key cannot exist in the schema and be quietly missing from the editor.
 */
final class BookMetaBox implements Registrable {

	/**
	 * Nonce action.
	 */
	private const NONCE_ACTION = 'volumina_save_book_meta';

	/**
	 * Nonce field name.
	 */
	private const NONCE_NAME = 'volumina_book_meta_nonce';

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . Book::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . Book::POST_TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Registers the meta box.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'volumina-book-details',
			__( 'Audiobook details', 'volumina' ),
			array( $this, 'render' ),
			Book::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Loads the media frame and the cover picker on the book screen only.
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

		wp_enqueue_media();

		wp_enqueue_script(
			'volumina-admin-book',
			plugins_url( 'assets/js/admin-book.js', PLUGIN_FILE ),
			array(),
			VERSION,
			true
		);
	}

	/**
	 * Renders the meta box.
	 *
	 * @param WP_Post $post The book being edited.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$copy = self::field_copy();

		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( Book::meta_keys() as $key => $type ) {
			$label       = isset( $copy[ $key ]['label'] ) ? $copy[ $key ]['label'] : $key;
			$description = isset( $copy[ $key ]['description'] ) ? $copy[ $key ]['description'] : '';
			$value       = get_post_meta( $post->ID, $key, true );

			echo '<tr>';
			printf(
				'<th scope="row"><label for="%1$s">%2$s</label></th>',
				esc_attr( $key ),
				esc_html( $label )
			);
			echo '<td>';

			if ( 'volumina_cover_id' === $key ) {
				$this->render_cover_field( $key, (int) $value );
			} elseif ( 'integer' === $type ) {
				printf(
					'<input type="number" min="0" step="1" class="small-text" id="%1$s" name="%1$s" value="%2$s" />',
					esc_attr( $key ),
					esc_attr( (string) (int) $value )
				);
			} else {
				printf(
					'<input type="text" class="regular-text" id="%1$s" name="%1$s" value="%2$s" />',
					esc_attr( $key ),
					esc_attr( (string) $value )
				);
			}

			if ( '' !== $description ) {
				printf( '<p class="description">%s</p>', esc_html( $description ) );
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Renders the cover picker.
	 *
	 * @param string $key      Meta key.
	 * @param int    $cover_id Currently chosen attachment, or zero.
	 */
	private function render_cover_field( string $key, int $cover_id ): void {
		printf(
			'<div id="volumina-cover-field" data-frame-title="%1$s" data-frame-button="%2$s">',
			esc_attr__( 'Choose a cover', 'volumina' ),
			esc_attr__( 'Use this cover', 'volumina' )
		);

		echo '<div data-volumina-cover-preview>';

		if ( $cover_id > 0 ) {
			echo wp_kses_post( wp_get_attachment_image( $cover_id, 'medium' ) );
		}

		echo '</div>';

		printf(
			'<input type="hidden" id="%1$s" name="%1$s" value="%2$d" data-volumina-cover-input />',
			esc_attr( $key ),
			(int) $cover_id
		);

		printf(
			'<p><button type="button" class="button" data-volumina-cover-select>%1$s</button> <button type="button" class="button-link" data-volumina-cover-clear>%2$s</button></p>',
			esc_html__( 'Choose cover', 'volumina' ),
			esc_html__( 'Remove cover', 'volumina' )
		);

		echo '</div>';
	}

	/**
	 * Saves the submitted meta.
	 *
	 * @param int $post_id The book being saved.
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

		foreach ( Book::meta_keys() as $key => $type ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			// Sanitised at the point of access: the value is never held raw.
			if ( 'integer' === $type ) {
				update_post_meta( $post_id, $key, absint( wp_unslash( $_POST[ $key ] ) ) );
				continue;
			}

			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}

	/**
	 * Admin-facing label and help text per field.
	 *
	 * Separate from the REST descriptions on purpose: one explains an API, the
	 * other talks to a person filling in a form.
	 *
	 * @return array<string, array{label: string, description: string}>
	 */
	private static function field_copy(): array {
		return array(
			'volumina_narrator'       => array(
				'label'       => __( 'Narrator', 'volumina' ),
				'description' => __( 'Who reads the book aloud.', 'volumina' ),
			),
			'volumina_publisher'      => array(
				'label'       => __( 'Publisher', 'volumina' ),
				'description' => __( 'Who published this recording.', 'volumina' ),
			),
			'volumina_isbn'           => array(
				'label'       => __( 'ISBN', 'volumina' ),
				'description' => __( 'Of this edition, if it has one.', 'volumina' ),
			),
			'volumina_language'       => array(
				'label'       => __( 'Language', 'volumina' ),
				'description' => __( 'The language it is read in, as a locale code such as es_ES.', 'volumina' ),
			),
			'volumina_total_duration' => array(
				'label'       => __( 'Total duration', 'volumina' ),
				'description' => __( 'In whole seconds. Leave at zero and the chapters will answer for it.', 'volumina' ),
			),
			'volumina_cover_id'       => array(
				'label'       => __( 'Cover', 'volumina' ),
				'description' => __( 'Chosen from the media library.', 'volumina' ),
			),
		);
	}
}
