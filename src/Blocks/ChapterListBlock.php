<?php
/**
 * The Chapter list block.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Blocks;

use TUNET\Volumina\Frontend\Audiobook;
use WP_Block;

defined( 'ABSPATH' ) || exit;

/**
 * A book's chapters, on their own.
 *
 * The names are plain text until a player on the same page claims them. A
 * chapter list can perfectly well appear on a page with nothing to play it,
 * and a button that plays nothing is worse than no button at all.
 */
final class ChapterListBlock {

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
			return ChosenBook::placeholder( __( 'Choose an audiobook to list the chapters of.', 'volumina' ) );
		}

		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes(),
			Audiobook::chapter_list( $book, ! empty( $attributes['showDurations'] ) )
		);
	}
}
