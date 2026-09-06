<?php
/**
 * Help tabs.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Puts help where WordPress already keeps it.
 *
 * The Help tab at the top of an admin screen is where somebody who is stuck
 * looks, and it costs nothing to fill in. A plugin that instead scatters its
 * explanations through the page is a plugin that is shouting at people who
 * already know what they are doing.
 *
 * Scaffolding. It knows nothing about audiobooks.
 */
final class Help {

	/**
	 * Adds tabs to whichever screen is being drawn.
	 *
	 * Content is trusted markup written by the plugin, never anything a user
	 * typed, and is passed to WordPress as it stands.
	 *
	 * A tab missing any of `id`, `title` or `content` is skipped rather than
	 * half-added. The type here says what the keys are worth, not that they are
	 * guaranteed: this is scaffolding, and the next plugin to call it will get
	 * one of them wrong one day.
	 *
	 * @param array<int, array<string, string>> $tabs    The tabs: `id`, `title`, `content`.
	 * @param string                            $sidebar Optional sidebar markup.
	 */
	public static function add( array $tabs, string $sidebar = '' ): void {
		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		foreach ( $tabs as $tab ) {
			if ( ! isset( $tab['id'], $tab['title'], $tab['content'] ) ) {
				continue;
			}

			$screen->add_help_tab(
				array(
					'id'      => $tab['id'],
					'title'   => $tab['title'],
					'content' => $tab['content'],
				)
			);
		}

		if ( '' !== $sidebar ) {
			$screen->set_help_sidebar( $sidebar );
		}
	}

	/**
	 * Wraps a paragraph of help text.
	 *
	 * @param string $text Already-translated text. Escaped here.
	 */
	public static function p( string $text ): string {
		return '<p>' . esc_html( $text ) . '</p>';
	}

	/**
	 * Wraps a list of points.
	 *
	 * @param array<int, string> $items Already-translated text. Escaped here.
	 */
	public static function ul( array $items ): string {
		$out = '<ul>';

		foreach ( $items as $item ) {
			$out .= '<li>' . esc_html( $item ) . '</li>';
		}

		return $out . '</ul>';
	}
}
