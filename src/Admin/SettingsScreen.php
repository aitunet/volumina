<?php
/**
 * The settings screen.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Frontend\Assets;
use TUNET\Volumina\Frontend\Palette;
use TUNET\Volumina\Frontend\Templates;
use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Support\AssetVersion;
use TUNET\Volumina\Support\Help;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Volumina's settings in two tabs, General and Appearance (with a live
 * preview), each its own form with its own Save button. An extension may add
 * more tabs.
 *
 * The form posts to `options.php`, so the nonce, the capability check and the
 * redirect are WordPress's own. A settings screen is not the place to reinvent
 * any of them.
 */
final class SettingsScreen implements Screen, HasHelp, HasAssets {

	/**
	 * The page slug.
	 */
	public const SLUG = 'volumina-settings';

	/**
	 * The id of the Appearance tab.
	 */
	public const APPEARANCE = 'appearance';

	/**
	 * The page slug.
	 */
	public function slug(): string {
		return self::SLUG;
	}

	/**
	 * The page's address.
	 *
	 * @param string $tab A tab id, or '' for the page itself (`general`).
	 */
	public static function url( string $tab = '' ): string {
		$url = admin_url( 'edit.php?post_type=' . Book::POST_TYPE . '&page=' . self::SLUG );

		return '' === $tab || 'general' === $tab ? $url : add_query_arg( 'tab', $tab, $url );
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
	 * The presentation stylesheet, shared with the welcome and Pro screens.
	 */
	public function enqueue(): void {
		wp_enqueue_style(
			'volumina-admin-presentation',
			plugins_url( 'assets/css/admin-presentation.css', PLUGIN_FILE ),
			array(),
			AssetVersion::of( 'assets/css/admin-presentation.css', PLUGIN_FILE, VERSION )
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Chooses what to load; nothing is written.
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( self::APPEARANCE !== $tab ) {
			return;
		}

		wp_enqueue_style( Assets::BOOK );
		wp_enqueue_style( Assets::PLAYER );

		// The colour fields: WordPress's own picker, turned on by color-field.js.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'volumina-color-field', plugins_url( 'assets/js/color-field.js', PLUGIN_FILE ), array( 'wp-color-picker' ), AssetVersion::of( 'assets/js/color-field.js', PLUGIN_FILE, VERSION ), true );

		$defaults = array();

		foreach ( Templates::registry()->all() as $id => $template ) {
			if ( '' !== $template->style() ) {
				wp_enqueue_style( $template->style() );
			}
			$defaults[ $id ] = $template->defaults();
		}

		$palette = array();

		foreach ( Palette::colors() as $slug => $color ) {
			$palette[ $slug ] = $color['color'];
		}

		wp_enqueue_script( 'volumina-appearance-preview', plugins_url( 'assets/js/appearance-preview.js', PLUGIN_FILE ), array(), AssetVersion::of( 'assets/js/appearance-preview.js', PLUGIN_FILE, VERSION ), true );
		wp_localize_script(
			'volumina-appearance-preview',
			'voluminaAppearance',
			array(
				'palette'  => $palette,
				'defaults' => $defaults,
			)
		);
	}

	/**
	 * Draws the screen: a tab bar when there is more than one tab, then the
	 * current tab. Volumina's own settings are the `general` tab, always
	 * first; an extension adds tabs of its own through `volumina_settings_tabs`.
	 */
	public function render(): void {
		$group = Settings::group();

		// Read again rather than trusting what was read before the save.
		$group->forget();

		$tabs = new Sections();
		$tabs->add( 'general', __( 'General', 'volumina' ), array( $this, 'render_general' ) );
		$tabs->add( self::APPEARANCE, __( 'Appearance', 'volumina' ), array( $this, 'render_appearance' ) );

		/**
		 * Fires while the settings screen is being drawn, with its tabs.
		 *
		 * Add a tab and it is drawn after the plugin's own. See docs/api.md.
		 *
		 * @param Sections $tabs The tabs.
		 */
		do_action( 'volumina_settings_tabs', $tabs );

		// Ours again, in the places they already hold: an extension may add
		// tabs, not take over General or Appearance.
		$tabs->add( 'general', __( 'General', 'volumina' ), array( $this, 'render_general' ) );
		$tabs->add( self::APPEARANCE, __( 'Appearance', 'volumina' ), array( $this, 'render_appearance' ) );

		$all = $tabs->all();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Chooses which tab to draw; nothing is written.
		$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$current   = Tabs::current( array_column( $all, 'id' ), $requested );

		echo '<div class="wrap volumina-presentation volumina-settings">';
		printf( '<h1>%s</h1>', esc_html( $this->title() ) );
		// Admin notices are inserted after this marker.
		echo '<hr class="wp-header-end">';

		// Core prints "Settings saved." on its own pages and not on ours, and
		// a save with no confirmation is a save the person does not trust.
		// Reading the flag decides what to say and nothing else, which is why
		// it needs no nonce of its own.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( $group->option(), 'volumina_settings_saved', __( 'Settings saved.', 'volumina' ), 'success' );
		}

		settings_errors( $group->option() );

		if ( count( $all ) > 1 ) {
			printf( '<nav class="nav-tab-wrapper volumina-settings-tabs" aria-label="%s">', esc_attr__( 'Settings sections', 'volumina' ) );
			foreach ( $all as $tab ) {
				$is_current = $tab['id'] === $current;
				printf(
					'<a href="%1$s" class="nav-tab%2$s"%3$s>%4$s</a>',
					esc_url( self::url( $tab['id'] ) ),
					$is_current ? ' nav-tab-active' : '',
					$is_current ? ' aria-current="page"' : '',
					esc_html( $tab['title'] )
				);
			}
			echo '</nav>';
		}

		foreach ( $all as $tab ) {
			if ( $tab['id'] !== $current ) {
				continue;
			}
			printf( '<div class="volumina-settings-tab" id="%s">', esc_attr( 'volumina-settings-tab-' . $tab['id'] ) );
			call_user_func( $tab['render'] );
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * The `general` tab: Volumina's own settings, one card per section.
	 */
	public function render_general(): void {
		$group = Settings::group();

		printf(
			'<p>%s</p>',
			esc_html__( 'Volumina works without changing any of this. These are here for when the defaults are not what you want.', 'volumina' )
		);

		echo '<form action="options.php" method="post">';

		settings_fields( $group->option() );
		$group->render_marker( array( 'listening', 'housekeeping' ) );

		$sections = Settings::sections();

		foreach ( array( 'listening', 'housekeeping' ) as $section ) {
			printf(
				'<section class="volumina-card volumina-settings-section"><h2>%1$s</h2><p class="volumina-settings-lede">%2$s</p>',
				esc_html( $sections[ $section ]['title'] ?? $section ),
				esc_html( $sections[ $section ]['description'] ?? '' )
			);
			$group->render_table( $section );
			echo '</section>';
		}

		submit_button();

		echo '</form>';
	}

	/**
	 * The `appearance` tab: how an audiobook looks. One form, which saves
	 * Volumina's option and every extension option registered in
	 * `Settings::APPEARANCE_GROUP`; the extensions' cards come after
	 * Presentation, through `volumina_appearance_sections`. A preview beside it.
	 */
	public function render_appearance(): void {
		$group    = Settings::group();
		$sections = Settings::sections();
		$cards    = new Sections();

		/**
		 * Fires while the Appearance tab is drawn, with the cards to add inside
		 * its form, after Presentation. See docs/api.md.
		 *
		 * @param Sections $cards The cards.
		 */
		do_action( 'volumina_appearance_sections', $cards );

		echo '<div class="volumina-appearance">';
		echo '<form class="volumina-appearance-form" action="options.php" method="post">';

		settings_fields( Settings::APPEARANCE_GROUP );
		$group->render_marker( array( 'presentation' ) );

		printf(
			'<section class="volumina-card volumina-settings-section"><h2>%1$s</h2><p class="volumina-settings-lede">%2$s</p>',
			esc_html( $sections['presentation']['title'] ),
			esc_html( $sections['presentation']['description'] )
		);
		// With one template there is nothing to choose.
		$group->render_table( 'presentation', count( Templates::cards() ) > 1 ? array() : array( 'template' ) );
		echo '</section>';

		foreach ( $cards->all() as $card ) {
			call_user_func( $card['render'] );
		}

		submit_button();
		echo '</form>';

		echo '<aside class="volumina-appearance-aside" aria-label="' . esc_attr__( 'Preview', 'volumina' ) . '">';
		AppearancePreview::render();
		echo '</aside>';

		echo '</div>';
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
