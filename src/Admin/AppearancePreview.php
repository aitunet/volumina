<?php
/**
 * The Appearance tab's preview.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Frontend\Audiobook;
use TUNET\Volumina\PostTypes\Book;

defined( 'ABSPATH' ) || exit;

/**
 * The site's newest published audiobook, drawn by the same code as the front
 * end, so what the preview shows is what a listener will get. Its player's
 * script is not loaded: nothing plays, and the play button only shows what the
 * player looks like while it plays. `appearance-preview.js` restyles it as the
 * form changes.
 */
final class AppearancePreview {

	/**
	 * Draws the preview card.
	 */
	public static function render(): void {
		echo '<section class="volumina-card volumina-appearance-preview-card">';
		printf( '<h2>%s</h2>', esc_html__( 'Preview', 'volumina' ) );

		$books = get_posts(
			array(
				'post_type'      => Book::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if ( array() === $books ) {
			printf( '<p>%s</p>', esc_html__( 'Publish an audiobook to see it here.', 'volumina' ) );
			echo '</section>';
			return;
		}

		echo '<div class="volumina-appearance-preview" inert>';
		// Built by Audiobook::render(), which escaped it. It is markup by intent.
		echo Audiobook::render( $books[0], array( 'heading' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';

		echo '<p class="volumina-appearance-preview-play"><button type="button" class="button" data-volumina-preview-toggle aria-pressed="false">' . esc_html__( 'Show it playing', 'volumina' ) . '</button></p>';

		echo '</section>';
	}
}
