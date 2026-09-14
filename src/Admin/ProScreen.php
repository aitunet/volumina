<?php
/**
 * The Pro screen.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * What Volumina Pro does, described and nothing more.
 *
 * The plugin directory's Detailed Plugin Guidelines forbid shipping
 * functionality that is locked and unlocked by paying, so there is no real code
 * on this page sitting disabled behind a licence field. It is a description of
 * a separate plugin, the way any page describing any other plugin would be.
 *
 * It does not apply once Pro is installed: a site that has it does not need to
 * be told about it, and Pro brings its own screens.
 */
final class ProScreen implements Screen, HasAssets {

	/**
	 * Where the call to action points. A link, never a form: the screen asks
	 * for nothing and sends nothing.
	 */
	private const URL = 'https://tunetdesign.com/volumina/pro/';

	/**
	 * The page slug.
	 */
	public function slug(): string {
		return 'volumina-pro';
	}

	/**
	 * The page title.
	 */
	public function title(): string {
		return __( 'Volumina Pro', 'volumina' );
	}

	/**
	 * The menu entry.
	 */
	public function menu_title(): string {
		return __( 'Pro', 'volumina' );
	}

	/**
	 * Who may see it.
	 */
	public function capability(): string {
		return 'manage_options';
	}

	/**
	 * Only where Pro is not already installed.
	 */
	public function applies(): bool {
		return ! defined( 'VOLUMINA_PRO_VERSION' );
	}

	/**
	 * The screen's own stylesheet, on this screen only.
	 */
	public function enqueue(): void {
		wp_enqueue_style(
			'volumina-admin-presentation',
			plugins_url( 'assets/css/admin-presentation.css', PLUGIN_FILE ),
			array(),
			VERSION
		);
	}

	/**
	 * Draws the screen: a hero, a card for each thing Pro will add, and the
	 * note about what it will not do. Zero inputs, zero disabled controls.
	 */
	public function render(): void {
		echo '<div class="wrap volumina-presentation">';
		// Admin notices are inserted after this marker, above the hero.
		echo '<hr class="wp-header-end">';

		$this->hero();

		echo '<ul class="volumina-cards">';

		foreach ( $this->cards() as $card ) {
			printf(
				'<li class="volumina-card"><img src="%1$s" alt="" width="64" height="64"><h2>%2$s</h2><p>%3$s</p></li>',
				esc_url( $this->image( $card[0] ) ),
				esc_html( $card[1] ),
				esc_html( $card[2] )
			);
		}

		echo '</ul>';

		$this->note();

		echo '</div>';
	}

	/**
	 * The hero: the mark, the name, "coming soon", one promise, the button.
	 */
	private function hero(): void {
		echo '<div class="volumina-hero">';
		printf( '<img src="%s" alt="" width="96" height="96">', esc_url( $this->image( 'mark' ) ) );
		echo '<div>';
		printf(
			'<h1>%1$s <span class="volumina-badge">%2$s</span></h1>',
			esc_html( $this->title() ),
			esc_html__( 'Coming soon', 'volumina' )
		);
		printf(
			'<p>%s</p>',
			esc_html__( 'Sell your audiobooks with the player you already have. A separate plugin; Volumina stays free.', 'volumina' )
		);
		$this->cta( 'volumina-cta' );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * What Pro will not do, said plainly, and the button once more.
	 */
	private function note(): void {
		echo '<div class="volumina-note">';
		printf(
			'<div><h2>%1$s</h2><p>%2$s</p></div>',
			esc_html__( 'What it does not do', 'volumina' ),
			esc_html__( 'It does not protect audio from being copied, and neither does anything else. A browser has to be able to play a file to play it, and a file a browser can play can be saved. Pro signs its URLs so they expire, which discourages passing a link around; anyone who tells you they can do more than that is selling you something that does not exist.', 'volumina' )
		);
		$this->cta( 'button button-primary' );
		echo '</div>';
	}

	/**
	 * The call to action: an external link that says so.
	 *
	 * @param string $classes CSS classes for the link.
	 */
	private function cta( string $classes ): void {
		printf(
			'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer">%3$s<span class="screen-reader-text"> %4$s</span></a>',
			esc_attr( $classes ),
			esc_url( self::URL ),
			esc_html__( 'Tell me when it is ready', 'volumina' ),
			esc_html__( '(opens tunetdesign.com in a new tab)', 'volumina' )
		);
	}

	/**
	 * URL of one of the screen's illustrations.
	 *
	 * @param string $name File name without the extension.
	 */
	private function image( string $name ): string {
		return plugins_url( 'assets/images/pro/' . $name . '.svg', PLUGIN_FILE );
	}

	/**
	 * What Pro will add: an illustration, a title and a line each.
	 *
	 * @return array<int, array{0: string, 1: string, 2: string}>
	 */
	private function cards(): array {
		return array(
			array(
				'sell',
				__( 'Sell with WooCommerce and EDD', 'volumina' ),
				__( 'Whoever buys, listens. Nothing more to set up.', 'volumina' ),
			),
			array(
				'link',
				__( 'Audio links that expire', 'volumina' ),
				__( 'Signed URLs: a shared link stops working within hours.', 'volumina' ),
			),
			array(
				'series',
				__( 'Series and bundles', 'volumina' ),
				__( 'Sell a whole series in one purchase.', 'volumina' ),
			),
			array(
				'style',
				__( 'Templates and styles', 'volumina' ),
				__( 'Featured templates, colours, typography and layouts for the block and the player.', 'volumina' ),
			),
			array(
				'report',
				__( 'Listening reports', 'volumina' ),
				__( 'Which books get finished, which get abandoned, and at which chapter.', 'volumina' ),
			),
		);
	}
}
