<?php
/**
 * The one-time setup screen.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Access\Mode;
use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Support\Help;

defined( 'ABSPATH' ) || exit;

/**
 * The first two minutes, once.
 *
 * It asks the two questions whose answers are hard to change later in spirit if
 * not in fact, and then gets out of the way for good: once it is finished it
 * stops applying, and the menu entry goes with it. A setup wizard that stays in
 * the menu forever is a plugin that never finishes installing.
 *
 * Everything it sets can be changed afterwards in Settings, and it says so.
 */
final class SetupScreen implements Screen, HasHelp {

	/**
	 * The option that remembers this is over.
	 */
	public const DONE_OPTION = 'volumina_setup_done';

	/**
	 * The nonce action for the form.
	 */
	private const ACTION = 'volumina_setup';

	/**
	 * The page slug.
	 */
	public function slug(): string {
		return 'volumina-setup';
	}

	/**
	 * The page title.
	 */
	public function title(): string {
		return __( 'Set up Volumina', 'volumina' );
	}

	/**
	 * The menu entry.
	 */
	public function menu_title(): string {
		return __( 'Setup', 'volumina' );
	}

	/**
	 * Who may see it.
	 */
	public function capability(): string {
		return 'manage_options';
	}

	/**
	 * Until it has been finished, and then never again.
	 */
	public function applies(): bool {
		return ! self::finished();
	}

	/**
	 * Whether setup is over.
	 */
	public static function finished(): bool {
		return (bool) get_option( self::DONE_OPTION, false );
	}

	/**
	 * The URL of this screen.
	 */
	public static function url(): string {
		return admin_url( 'edit.php?post_type=' . Book::POST_TYPE . '&page=volumina-setup' );
	}

	/**
	 * Draws the screen.
	 */
	public function render(): void {
		if ( $this->handle_submission() ) {
			$this->render_finished();
			return;
		}

		echo '<div class="wrap">';
		printf( '<h1>%s</h1>', esc_html( $this->title() ) );
		printf(
			'<p>%s</p>',
			esc_html__( 'Two questions, and then you can add your first audiobook. Both can be changed later in Settings.', 'volumina' )
		);

		echo '<form method="post">';
		wp_nonce_field( self::ACTION );

		echo '<table class="form-table" role="presentation"><tbody>';

		echo '<tr><th scope="row">';
		printf( '<span>%s</span>', esc_html__( 'New audiobooks are', 'volumina' ) );
		echo '</th><td><fieldset>';

		$default = Mode::sanitize( Settings::get( 'default_access' ) );

		foreach ( Mode::labels() as $volumina_mode => $volumina_label ) {
			printf(
				'<label style="display:block"><input type="radio" name="default_access" value="%1$s"%2$s /> %3$s</label>',
				esc_attr( (string) $volumina_mode ),
				checked( $default, $volumina_mode, false ),
				esc_html( (string) $volumina_label )
			);
		}

		printf(
			'</fieldset><p class="description">%s</p></td></tr>',
			esc_html__( 'Restricted books still appear on your site with their cover and chapters. Only the audio is held back.', 'volumina' )
		);

		echo '<tr><th scope="row">';
		printf( '<span>%s</span>', esc_html__( 'Book pages', 'volumina' ) );
		echo '</th><td>';
		printf(
			'<label><input type="checkbox" name="append_to_content" value="1"%1$s /> %2$s</label>',
			checked( (bool) Settings::get( 'append_to_content' ), true, false ),
			esc_html__( 'Show the player and the chapters on an audiobook page automatically.', 'volumina' )
		);
		printf(
			'<p class="description">%s</p></td></tr>',
			esc_html__( 'Leave this on unless you would rather place the Audiobook block yourself.', 'volumina' )
		);

		echo '</tbody></table>';

		echo '<p class="submit">';
		submit_button( __( 'Save and finish', 'volumina' ), 'primary', 'volumina-setup-save', false );
		echo ' ';
		submit_button( __( 'Skip', 'volumina' ), 'link', 'volumina-setup-skip', false );
		echo '</p>';

		echo '</form></div>';
	}

	/**
	 * Saves the answers, if this request carried any it is allowed to save.
	 *
	 * @return bool Whether setup is now over.
	 */
	private function handle_submission(): bool {
		$saving  = isset( $_POST['volumina-setup-save'] );
		$skipped = isset( $_POST['volumina-setup-skip'] );

		if ( ! $saving && ! $skipped ) {
			return false;
		}

		check_admin_referer( self::ACTION );

		if ( ! current_user_can( $this->capability() ) ) {
			return false;
		}

		if ( $saving ) {
			$group   = Settings::group();
			$current = $group->all();

			$current['default_access'] = isset( $_POST['default_access'] )
				? Mode::sanitize( sanitize_text_field( wp_unslash( $_POST['default_access'] ) ) )
				: Mode::PUBLIC;

			$current['append_to_content'] = isset( $_POST['append_to_content'] );

			update_option( Settings::OPTION, $group->sanitize( $current ) );
			$group->forget();
		}

		update_option( self::DONE_OPTION, '1' );

		return true;
	}

	/**
	 * Draws the last panel: what to do next, and where.
	 */
	private function render_finished(): void {
		echo '<div class="wrap">';
		printf( '<h1>%s</h1>', esc_html__( 'Volumina is ready', 'volumina' ) );
		printf(
			'<p>%s</p>',
			esc_html__( 'An audiobook is a book with chapters, and a chapter is a title with an audio file attached. Add the book first, then its chapters in the order they should be heard.', 'volumina' )
		);

		printf(
			'<p><a class="button button-primary" href="%1$s">%2$s</a> <a class="button" href="%3$s">%4$s</a></p>',
			esc_url( admin_url( 'post-new.php?post_type=' . Book::POST_TYPE ) ),
			esc_html__( 'Add your first audiobook', 'volumina' ),
			esc_url( admin_url( 'edit.php?post_type=' . Book::POST_TYPE . '&page=volumina-settings' ) ),
			esc_html__( 'Settings', 'volumina' )
		);

		printf(
			'<p class="description">%s</p></div>',
			esc_html__( 'This page has done its job and will not appear again.', 'volumina' )
		);
	}

	/**
	 * The help tabs for this screen.
	 */
	public function help(): void {
		Help::add(
			array(
				array(
					'id'      => 'volumina-setup-overview',
					'title'   => __( 'Overview', 'volumina' ),
					'content' => Help::p( __( 'Nothing here is permanent. Both answers live in Settings afterwards, and this page disappears once you have finished or skipped it.', 'volumina' ) )
						. Help::p( __( 'Skipping is a real option: the defaults are the ones most sites want.', 'volumina' ) ),
				),
			)
		);
	}
}
