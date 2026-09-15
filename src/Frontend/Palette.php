<?php
/**
 * The active theme's colour palette.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Frontend;

use TUNET\Volumina\Support\Color;

defined( 'ABSPATH' ) || exit;

/**
 * The colours a theme offers, by slug.
 *
 * A block theme declares them in `theme.json`; a classic theme through
 * `editor-color-palette`; WordPress itself brings a default set when the
 * theme brings none. `wp_get_global_settings()` folds all three into one
 * answer, so this reads that and nothing else. Only hex colours are kept: a
 * palette entry that is a gradient, a variable or a name cannot be turned
 * into an ink, so it is not offered as an accent.
 */
final class Palette {

	/**
	 * The palette: slug => name and colour, in the theme's order.
	 *
	 * @return array<string, array{name: string, color: string}>
	 */
	public static function colors(): array {
		$palette = array();

		foreach ( array( 'theme', 'custom', 'default' ) as $origin ) {
			$entries = wp_get_global_settings( array( 'color', 'palette', $origin ) );

			if ( ! is_array( $entries ) || array() === $entries ) {
				continue;
			}

			foreach ( $entries as $entry ) {
				if ( ! is_array( $entry ) || empty( $entry['slug'] ) || ! is_string( $entry['slug'] ) ) {
					continue;
				}

				$color = Color::normalize( (string) ( $entry['color'] ?? '' ) );

				if ( '' === $color ) {
					continue;
				}

				$palette[ sanitize_key( $entry['slug'] ) ] = array(
					'name'  => (string) ( $entry['name'] ?? $entry['slug'] ),
					'color' => $color,
				);
			}

			// The theme's own palette is the one its pages are painted with;
			// the default set only stands in when the theme has none.
			if ( array() !== $palette ) {
				break;
			}
		}

		return $palette;
	}

	/**
	 * The hex colour under a slug, or empty when the theme has no such colour.
	 *
	 * @param string $slug The palette slug.
	 */
	public static function color( string $slug ): string {
		$slug = sanitize_key( $slug );

		if ( '' === $slug ) {
			return '';
		}

		return self::colors()[ $slug ]['color'] ?? '';
	}
}
