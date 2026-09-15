<?php
/**
 * Block registration.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Blocks;

use TUNET\Volumina\Frontend\Templates;
use TUNET\Volumina\Support\Registrable;

use const TUNET\Volumina\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's blocks from their built metadata.
 *
 * Each block is registered from the `block.json` that `wp-scripts` copies into
 * `build/`, so the editor and the server read one description of the block
 * rather than two that can disagree. The render callbacks live in their own
 * classes; nothing here decides what a block looks like.
 */
final class Registry implements Registrable {

	/**
	 * Block directory name mapped to the class that renders it.
	 *
	 * @var array<string, class-string>
	 */
	private const BLOCKS = array(
		'audiobook'          => AudiobookBlock::class,
		'chapter-list'       => ChapterListBlock::class,
		'continue-listening' => ContinueListeningBlock::class,
	);

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'hand_over_templates' ) );
	}

	/**
	 * Tells the editor which templates exist, for the presentation panel.
	 *
	 * Printed before each block's editor script rather than added to the
	 * editor's settings: the post editor passes only a fixed list of settings
	 * on to the block editor, and this would not be in it. Typed JSON, as the
	 * player's settings are — never `wp_localize_script()`.
	 */
	public function hand_over_templates(): void {
		$templates = array();

		foreach ( Templates::registry()->all() as $id => $template ) {
			$templates[] = array(
				'id'    => $id,
				'label' => $template->label(),
			);
		}

		$json = wp_json_encode( $templates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES );

		foreach ( array_keys( self::BLOCKS ) as $directory ) {
			wp_add_inline_script(
				generate_block_asset_handle( 'volumina/' . $directory, 'editorScript' ),
				'window.voluminaTemplates = ' . $json . ';',
				'before'
			);
		}
	}

	/**
	 * Registers every block that has been built.
	 *
	 * A missing `build/` means the assets were never compiled — a checkout
	 * without `npm run build`. That is a broken editor, not a fatal page, so
	 * the block is skipped rather than registered against nothing.
	 */
	public function register_blocks(): void {
		$build = plugin_dir_path( PLUGIN_FILE ) . 'build/';

		foreach ( self::BLOCKS as $directory => $renderer ) {
			$metadata = $build . $directory;

			if ( ! is_readable( $metadata . '/block.json' ) ) {
				continue;
			}

			register_block_type(
				$metadata,
				array( 'render_callback' => array( $renderer, 'render' ) )
			);
		}
	}
}
