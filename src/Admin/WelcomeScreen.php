<?php
/**
 * The welcome page.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Support\AssetVersion;

use const TUNET\Volumina\PLUGIN_FILE;
use const TUNET\Volumina\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Where a new site lands after activation, and what Dashboard calls "Welcome to Volumina".
 *
 * It explains; the settings ask. What the plugin is and is not, the
 * three steps to a first audiobook, and where to go next. Nothing here is a
 * form: every control is a link to a screen that already exists.
 *
 * Its sections go through `Sections` and `volumina_welcome_sections`, so an
 * extension can add one of its own — Pro says what it added — without
 * touching this page.
 */
final class WelcomeScreen implements Screen, HasAssets, HasParent {

	/**
	 * The page slug.
	 */
	public const SLUG = 'volumina-welcome';

	/**
	 * Where "Read the guide" points. A page the account holder publishes.
	 */
	private const GUIDE_URL = 'https://tunetdesign.com/volumina/';

	/**
	 * The page slug.
	 */
	public function slug(): string {
		return self::SLUG;
	}

	/**
	 * The page title.
	 */
	public function title(): string {
		return __( 'Welcome to Volumina', 'volumina' );
	}

	/**
	 * The menu entry, under Dashboard, so it says whose welcome it is.
	 */
	public function menu_title(): string {
		return __( 'Welcome to Volumina', 'volumina' );
	}

	/**
	 * Under Dashboard: where a welcome is expected, and out of the menu
	 * people work in.
	 */
	public function parent(): string {
		return 'index.php';
	}

	/**
	 * Who may see it: the same people the settings admit.
	 */
	public function capability(): string {
		return 'manage_options';
	}

	/**
	 * Always. Reachable from the menu long after the first visit.
	 */
	public function applies(): bool {
		return true;
	}

	/**
	 * The page's address.
	 */
	public static function url(): string {
		return admin_url( 'index.php?page=' . self::SLUG );
	}

	/**
	 * The presentation stylesheet, shared with the Pro screen.
	 */
	public function enqueue(): void {
		wp_enqueue_style(
			'volumina-admin-presentation',
			plugins_url( 'assets/css/admin-presentation.css', PLUGIN_FILE ),
			array(),
			AssetVersion::of( 'assets/css/admin-presentation.css', PLUGIN_FILE, VERSION )
		);
	}

	/**
	 * Draws the page: the hero, then every section in order.
	 */
	public function render(): void {
		$sections = new Sections();
		$sections->add( 'steps', __( 'Three steps to your first audiobook', 'volumina' ), array( $this, 'steps' ) );
		$sections->add( 'is', __( 'What it is, and what it is not', 'volumina' ), array( $this, 'is_and_is_not' ) );
		$sections->add( 'next', __( 'Where to go next', 'volumina' ), array( $this, 'next' ) );

		/**
		 * Fires so extensions can add a section to the welcome page.
		 *
		 * @param Sections $sections The page's sections, in order. Add yours.
		 */
		do_action( 'volumina_welcome_sections', $sections );

		echo '<div class="wrap volumina-presentation volumina-welcome">';
		// Admin notices are inserted after this marker, above the hero.
		echo '<hr class="wp-header-end">';

		$this->hero();

		foreach ( $sections->all() as $section ) {
			printf(
				'<section class="volumina-section" id="%1$s"><h2>%2$s</h2>',
				esc_attr( 'volumina-welcome-' . sanitize_key( $section['id'] ) ),
				esc_html( $section['title'] )
			);
			call_user_func( $section['render'] );
			echo '</section>';
		}

		echo '</div>';
	}

	/**
	 * The hero: the mark, the title, one line, and the one thing to do next.
	 */
	private function hero(): void {
		echo '<div class="volumina-hero">';
		printf( '<img src="%s" alt="" width="96" height="96">', esc_url( plugins_url( 'assets/images/pro/mark.svg', PLUGIN_FILE ) ) );
		echo '<div>';
		printf( '<h1>%s</h1>', esc_html( $this->title() ) );
		printf(
			'<p>%s</p>',
			esc_html__( 'Publish audiobooks on your site and give listeners a real listening experience: chapters, a position that is remembered, playback speed, a sleep timer.', 'volumina' )
		);

		if ( Setup::finished() ) {
			$this->link( 'volumina-cta', admin_url( 'post-new.php?post_type=' . Book::POST_TYPE ), __( 'Add an audiobook', 'volumina' ) );
		} else {
			$this->link( 'volumina-cta', SettingsScreen::url(), __( 'Open the settings — two of them matter', 'volumina' ) );
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * The three steps, as numbered cards. The first one knows when it is done.
	 */
	public function steps(): void {
		echo '<ol class="volumina-cards volumina-steps">';

		$this->step(
			1,
			__( 'Set it up', 'volumina' ),
			__( 'Two settings decide how it behaves: who may listen by default, and whether book pages show the player. Save the settings once, or keep the defaults, and this step is done.', 'volumina' ),
			Setup::finished() ? '' : SettingsScreen::url(),
			Setup::finished() ? __( 'Done.', 'volumina' ) : __( 'Open the settings', 'volumina' )
		);
		$this->step(
			2,
			__( 'Add an audiobook and its chapters', 'volumina' ),
			__( 'Title, cover, narrator and the rest on one screen. Each chapter is one audio file from the media library; drag the list to put them in order.', 'volumina' ),
			admin_url( 'post-new.php?post_type=' . Book::POST_TYPE ),
			__( 'Add an audiobook', 'volumina' )
		);
		$this->step(
			3,
			__( 'Put it on a page', 'volumina' ),
			__( 'Every audiobook has its own page, player included. To place one anywhere else, use the Audiobook, Chapter list and Continue listening blocks in the editor.', 'volumina' ),
			admin_url( 'edit.php?post_type=' . Book::POST_TYPE ),
			__( 'All audiobooks', 'volumina' )
		);

		echo '</ol>';
	}

	/**
	 * One step.
	 *
	 * @param int    $number The step's number.
	 * @param string $title  Its heading.
	 * @param string $text   What to do.
	 * @param string $url    Where to do it, or empty when there is nothing left to do.
	 * @param string $label  The link's text, or the note that stands in for it.
	 */
	private function step( int $number, string $title, string $text, string $url, string $label ): void {
		printf(
			'<li class="volumina-card"><span class="volumina-step-number" aria-hidden="true">%1$d</span><h3>%2$s</h3><p>%3$s</p>',
			absint( $number ),
			esc_html( $title ),
			esc_html( $text )
		);

		if ( '' === $url ) {
			printf( '<p class="volumina-step-done">%s</p>', esc_html( $label ) );
		} else {
			$this->link( 'volumina-step-link', $url, $label );
		}

		echo '</li>';
	}

	/**
	 * What it is and what it is not, side by side.
	 */
	public function is_and_is_not(): void {
		echo '<div class="volumina-columns">';
		printf(
			'<div class="volumina-card"><h3>%1$s</h3><p>%2$s</p></div>',
			esc_html__( 'It is', 'volumina' ),
			esc_html__( 'Publishing and playback. A long-form listening experience that music players do not give: chapters, a position remembered for each listener, playback speed, a sleep timer, and blocks for any page.', 'volumina' )
		);
		printf(
			'<div class="volumina-card"><h3>%1$s</h3><p>%2$s</p><p>%3$s</p></div>',
			esc_html__( 'It is not', 'volumina' ),
			esc_html__( 'A store. There is no cart, no checkout and no coupons; selling lives in Volumina Pro, a separate plugin.', 'volumina' ),
			esc_html__( 'Nor is it copy protection. A browser has to be able to play a file to play it, so no plugin can stop a file from being saved. Anyone who promises otherwise is selling something that does not exist.', 'volumina' )
		);
		echo '</div>';
	}

	/**
	 * Where to go next: the guide, the help tabs, the settings.
	 */
	public function next(): void {
		echo '<ul class="volumina-cards volumina-next">';

		printf(
			'<li class="volumina-card"><h3>%1$s</h3><p>%2$s</p><a class="volumina-step-link" href="%3$s" target="_blank" rel="noopener noreferrer">%4$s<span class="screen-reader-text"> %5$s</span></a></li>',
			esc_html__( 'The guide', 'volumina' ),
			esc_html__( 'Every screen, every field and the player from the listener\'s side, step by step.', 'volumina' ),
			esc_url( self::GUIDE_URL ),
			esc_html__( 'Read the guide', 'volumina' ),
			esc_html__( '(opens tunetdesign.com in a new tab)', 'volumina' )
		);
		printf(
			'<li class="volumina-card"><h3>%1$s</h3><p>%2$s</p></li>',
			esc_html__( 'Help, where you are', 'volumina' ),
			esc_html__( 'Every Volumina screen has a Help tab at the top right that explains that screen.', 'volumina' )
		);
		printf(
			'<li class="volumina-card"><h3>%1$s</h3><p>%2$s</p>',
			esc_html__( 'Settings', 'volumina' ),
			esc_html__( 'Who may listen by default, whether to keep a log, and what to remove on uninstall.', 'volumina' )
		);
		$this->link( 'volumina-step-link', SettingsScreen::url(), __( 'Open the settings', 'volumina' ) );
		echo '</li>';

		echo '</ul>';
	}

	/**
	 * A link inside the admin.
	 *
	 * @param string $classes CSS classes.
	 * @param string $url     Where it goes.
	 * @param string $label   Its text.
	 */
	private function link( string $classes, string $url, string $label ): void {
		printf(
			'<a class="%1$s" href="%2$s">%3$s</a>',
			esc_attr( $classes ),
			esc_url( $url ),
			esc_html( $label )
		);
	}
}
