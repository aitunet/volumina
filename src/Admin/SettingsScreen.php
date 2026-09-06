<?php
/**
 * The settings screen.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Support\Help;

defined( 'ABSPATH' ) || exit;

/**
 * Four settings and a Save button.
 *
 * The form posts to `options.php`, so the nonce, the capability check and the
 * redirect are WordPress's own. A settings screen is not the place to reinvent
 * any of them.
 */
final class SettingsScreen implements Screen, HasHelp {

	/**
	 * The page slug.
	 */
	public function slug(): string {
		return 'volumina-settings';
	}

	/**
	 * The page title.
	 */
	public function title(): string {
		return __( 'Volumina Settings', 'volumina' );
	}

	/**
	 * The menu entry.
	 */
	public function menu_title(): string {
		return __( 'Settings', 'volumina' );
	}

	/**
	 * Who may see it.
	 */
	public function capability(): string {
		return 'manage_options';
	}

	/**
	 * Always: every site has settings.
	 */
	public function applies(): bool {
		return true;
	}

	/**
	 * Draws the screen.
	 */
	public function render(): void {
		$group = Settings::group();

		// Read again rather than trusting what was read before the save.
		$group->forget();

		echo '<div class="wrap">';
		printf( '<h1>%s</h1>', esc_html( $this->title() ) );

		// Core prints "Settings saved." on its own pages and not on ours, and
		// a save with no confirmation is a save the person does not trust.
		// Reading the flag decides what to say and nothing else, which is why
		// it needs no nonce of its own.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				$group->option(),
				'volumina_settings_saved',
				__( 'Settings saved.', 'volumina' ),
				'success'
			);
		}

		settings_errors( $group->option() );

		printf(
			'<p>%s</p>',
			esc_html__( 'Volumina works without changing any of this. These are here for when the defaults are not what you want.', 'volumina' )
		);

		echo '<form action="options.php" method="post">';

		settings_fields( $group->option() );
		$group->render_table();
		submit_button();

		echo '</form></div>';
	}

	/**
	 * The help tabs for this screen.
	 */
	public function help(): void {
		Help::add(
			array(
				array(
					'id'      => 'volumina-settings-overview',
					'title'   => __( 'Overview', 'volumina' ),
					'content' => Help::p( __( 'Nothing here needs to be changed for Volumina to work. Each setting says what it does underneath it.', 'volumina' ) )
						. Help::ul(
							array(
								__( 'New audiobooks are: what a book you add from now on starts as. It never changes a book that already exists.', 'volumina' ),
								__( 'Book pages: turn this off if you place the Audiobook block yourself and do not want it added a second time.', 'volumina' ),
								__( 'Log: records notable events. Turn it on while something is wrong and off again afterwards.', 'volumina' ),
							)
						),
				),
				array(
					'id'      => 'volumina-settings-uninstall',
					'title'   => __( 'Uninstalling', 'volumina' ),
					'content' => Help::p( __( 'Deactivating the plugin never deletes anything. Deleting it removes your books, chapters and settings only if you have ticked the uninstall setting; otherwise everything is left where it is, including every listener\'s place in every book.', 'volumina' ) )
						. Help::p( __( 'Books and chapters are ordinary posts, so they go to the trash like any other post and can be restored from it.', 'volumina' ) ),
				),
			),
			'<p><strong>' . esc_html__( 'More', 'volumina' ) . '</strong></p>'
			. '<p><a href="https://tunetdesign.com/volumina/">' . esc_html__( 'Volumina documentation', 'volumina' ) . '</a></p>'
		);
	}
}
