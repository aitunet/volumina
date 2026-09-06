<?php
/**
 * The Pro screen.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

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
final class ProScreen implements Screen {

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
	 * Draws the screen.
	 */
	public function render(): void {
		echo '<div class="wrap">';
		printf( '<h1>%s</h1>', esc_html( $this->title() ) );

		printf(
			'<p>%s</p>',
			esc_html__( 'Volumina publishes audiobooks and plays them. Volumina Pro is a separate plugin, for selling them.', 'volumina' )
		);

		echo '<ul class="ul-disc">';

		foreach ( $this->points() as $point ) {
			printf( '<li><strong>%1$s</strong> — %2$s</li>', esc_html( $point[0] ), esc_html( $point[1] ) );
		}

		echo '</ul>';

		printf(
			'<h2>%s</h2><p>%s</p>',
			esc_html__( 'What it does not do', 'volumina' ),
			esc_html__( 'It does not protect audio from being copied, and neither does anything else. A browser has to be able to play a file to play it, and a file a browser can play can be saved. Pro signs its URLs so they expire, which discourages passing a link around; anyone who tells you they can do more than that is selling you something that does not exist.', 'volumina' )
		);

		printf(
			'<p><a class="button button-primary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
			esc_url( 'https://tunetdesign.com/volumina/pro/' ),
			esc_html__( 'Read more at tunetdesign.com', 'volumina' )
		);

		echo '</div>';
	}

	/**
	 * What Pro adds, in a line each.
	 *
	 * @return array<int, array{0: string, 1: string}>
	 */
	private function points(): array {
		return array(
			array(
				__( 'Selling', 'volumina' ),
				__( 'WooCommerce and Easy Digital Downloads decide who may listen, through the access providers this plugin already asks.', 'volumina' ),
			),
			array(
				__( 'Signed audio URLs', 'volumina' ),
				__( 'Links that expire, so one shared address does not become a public library.', 'volumina' ),
			),
			array(
				__( 'Series and bundles', 'volumina' ),
				__( 'Sell a series as one purchase, and give listeners everything in it.', 'volumina' ),
			),
			array(
				__( 'Listening reports', 'volumina' ),
				__( 'Which books are finished, which are abandoned, and where.', 'volumina' ),
			),
		);
	}
}
