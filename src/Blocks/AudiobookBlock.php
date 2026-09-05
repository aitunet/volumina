<?php
/**
 * The Audiobook block.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Blocks;

use TUNET\Volumina\Frontend\Audiobook;
use TUNET\Volumina\Frontend\BookContent;
use TUNET\Volumina\Support\RenderOnce;
use WP_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Puts a whole audiobook — cover, details, player and chapters — on a page.
 *
 * Renders through `Frontend\Audiobook`, the same code the book page itself
 * uses, so the block cannot drift away from what a listener sees elsewhere.
 */
final class AudiobookBlock {

	/**
	 * Renders the block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Inner content, unused.
	 * @param WP_Block|null        $block      The block instance.
	 */
	public static function render( array $attributes, string $content = '', ?WP_Block $block = null ): string {
		$book = ChosenBook::resolve( $attributes, $block );

		if ( null === $book ) {
			return ChosenBook::placeholder( __( 'Choose an audiobook to show.', 'volumina' ) );
		}

		// The content filter appends the same audiobook to a book page. In a
		// block theme this block can be in the template as well, and neither
		// side can know which of them runs first. Whoever claims it, renders.
		if ( ! RenderOnce::claim( BookContent::key( (int) $book->ID ) ) ) {
			return '';
		}

		$markup = Audiobook::render(
			$book,
			array(
				'cover'    => ! empty( $attributes['showCover'] ),
				'details'  => ! empty( $attributes['showDetails'] ),
				'player'   => ! empty( $attributes['showPlayer'] ),
				'chapters' => ! empty( $attributes['showChapters'] ),
				'heading'  => ! empty( $attributes['showHeading'] ),
			)
		);

		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes(),
			$markup
		);
	}
}
