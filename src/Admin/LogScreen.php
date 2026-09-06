<?php
/**
 * The log screen.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Support\Help;
use TUNET\Volumina\Support\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * The last few hundred notable events, newest first.
 *
 * It is not registered at all unless logging is on, so nobody ever opens a page
 * to be told there is nothing here and no way to change that from this screen.
 */
final class LogScreen implements Screen, HasHelp {

	/**
	 * The action name for emptying the log.
	 */
	private const CLEAR_ACTION = 'volumina_clear_log';

	/**
	 * The page slug.
	 */
	public function slug(): string {
		return 'volumina-log';
	}

	/**
	 * The page title.
	 */
	public function title(): string {
		return __( 'Volumina Log', 'volumina' );
	}

	/**
	 * The menu entry.
	 */
	public function menu_title(): string {
		return __( 'Log', 'volumina' );
	}

	/**
	 * Who may see it.
	 */
	public function capability(): string {
		return 'manage_options';
	}

	/**
	 * Only when there is logging to look at.
	 */
	public function applies(): bool {
		return (bool) Settings::get( 'logging' );
	}

	/**
	 * Draws the screen.
	 */
	public function render(): void {
		$this->maybe_clear();

		$entries = Logger::entries();

		echo '<div class="wrap">';
		printf( '<h1>%s</h1>', esc_html( $this->title() ) );

		if ( array() === $entries ) {
			printf(
				'<p>%s</p></div>',
				esc_html__( 'Nothing recorded yet. Notable events will appear here as they happen.', 'volumina' )
			);
			return;
		}

		echo '<form method="post">';
		wp_nonce_field( self::CLEAR_ACTION );
		submit_button( __( 'Empty the log', 'volumina' ), 'delete', 'volumina-clear-log' );
		echo '</form>';

		echo '<table class="widefat striped"><thead><tr>';
		printf( '<th scope="col">%s</th>', esc_html__( 'When', 'volumina' ) );
		printf( '<th scope="col">%s</th>', esc_html__( 'Level', 'volumina' ) );
		printf( '<th scope="col">%s</th>', esc_html__( 'What happened', 'volumina' ) );
		echo '</tr></thead><tbody>';

		foreach ( $entries as $entry ) {
			$context = array();

			foreach ( (array) ( $entry['context'] ?? array() ) as $key => $value ) {
				$context[] = $key . ': ' . $value;
			}

			echo '<tr>';
			printf( '<td>%s</td>', esc_html( (string) ( $entry['time'] ?? '' ) ) );
			printf( '<td>%s</td>', esc_html( $this->level_label( (string) ( $entry['level'] ?? 'info' ) ) ) );
			printf(
				'<td>%s%s</td>',
				esc_html( (string) ( $entry['message'] ?? '' ) ),
				array() === $context
					? ''
					: '<br /><code>' . esc_html( implode( ' · ', $context ) ) . '</code>'
			);
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}

	/**
	 * Empties the log when this request asked to, and was allowed to.
	 */
	private function maybe_clear(): void {
		if ( ! isset( $_POST['volumina-clear-log'] ) ) {
			return;
		}

		check_admin_referer( self::CLEAR_ACTION );

		if ( ! current_user_can( $this->capability() ) ) {
			return;
		}

		Logger::clear();

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'The log has been emptied.', 'volumina' )
		);
	}

	/**
	 * A level, in words.
	 *
	 * @param string $level One of the logger's levels.
	 */
	private function level_label( string $level ): string {
		switch ( $level ) {
			case 'error':
				return __( 'Error', 'volumina' );

			case 'warning':
				return __( 'Warning', 'volumina' );

			default:
				return __( 'Note', 'volumina' );
		}
	}

	/**
	 * The help tabs for this screen.
	 */
	public function help(): void {
		Help::add(
			array(
				array(
					'id'      => 'volumina-log-overview',
					'title'   => __( 'Overview', 'volumina' ),
					'content' => Help::p( __( 'This is a log of notable events, not of every request. It holds the last two hundred and keeps nothing about who was listening beyond the account involved.', 'volumina' ) )
						. Help::ul(
							array(
								__( 'A listener turned away from an audiobook they may not hear.', 'volumina' ),
								__( 'An audio file that could not be found where the chapter says it is.', 'volumina' ),
								__( 'The database being brought up to date after an update.', 'volumina' ),
							)
						)
						. Help::p( __( 'Turn logging off in Settings when you are done, and this screen goes away with it.', 'volumina' ) ),
				),
			)
		);
	}
}
