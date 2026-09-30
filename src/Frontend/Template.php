<?php
/**
 * One presentation template.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Frontend;

use InvalidArgumentException;
use TUNET\Volumina\Support\Color;

defined( 'ABSPATH' ) || exit;

/**
 * A template: an id, a label, a stylesheet, and what it prefers by default.
 *
 * A template does not render anything. The audiobook is always drawn by the
 * same markup; a template is the stylesheet that dresses it — scoped by the
 * `volumina-template-{id}` class the wrapper carries — plus its own defaults
 * for the two options every template shares: where the cover goes and which
 * palette colour is the accent. A site's own choices override the defaults.
 *
 * This class is public API. It will not change without a major version.
 */
final class Template {

	/**
	 * Cover positions a template may prefer and a site may choose.
	 */
	public const COVER_SIDE = 'side';
	public const COVER_TOP  = 'top';
	public const COVER_NONE = 'none';

	/**
	 * The id, a slug.
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * Defaults, holding only the keys a template may decide.
	 *
	 * @var array{cover?: string, accent?: string}
	 */
	private array $defaults;

	/**
	 * A picture of the template, for choosing it: an http(s) URL, or ''.
	 *
	 * @var string
	 */
	private string $preview;

	/**
	 * Describes a template.
	 *
	 * @param string                $id       A slug: lower-case letters, digits and single dashes. Prefix it with your plugin's.
	 * @param string                $label    Its name, already translated.
	 * @param string                $style    A registered stylesheet handle, or empty for no stylesheet of its own.
	 * @param array<string, string> $defaults `cover` (side, top or none), `accent` (a palette slug), and
	 *                                        `panel_start` and `panel_end` (`#rrggbb`): the colours its
	 *                                        panel is painted in when a site chooses only one of them.
	 * @param string                $preview  A picture of it, an http(s) URL, for choosing it; empty for none.
	 *
	 * @throws InvalidArgumentException When the id is not a slug.
	 */
	public function __construct(
		string $id,
		private string $label,
		private string $style = '',
		array $defaults = array(),
		string $preview = ''
	) {
		$id = strtolower( trim( $id ) );

		if ( 1 !== preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id ) ) {
			throw new InvalidArgumentException( 'A template id is a slug: letters, digits and single dashes.' );
		}

		$this->id       = $id;
		$this->defaults = self::filter( $defaults );
		$this->preview  = self::url( $preview );
	}

	/**
	 * The id.
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * The name.
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * The stylesheet handle, or empty.
	 */
	public function style(): string {
		return $this->style;
	}

	/**
	 * What it prefers by default, for the keys it set validly.
	 *
	 * @return array{cover?: string, accent?: string, panel_start?: string, panel_end?: string}
	 */
	public function defaults(): array {
		return $this->defaults;
	}

	/**
	 * The picture used to choose this template, or ''.
	 */
	public function preview(): string {
		return $this->preview;
	}

	/**
	 * The cover positions.
	 *
	 * @return array<int, string>
	 */
	public static function covers(): array {
		return array( self::COVER_SIDE, self::COVER_TOP, self::COVER_NONE );
	}

	/**
	 * An http(s) URL as given, anything else ''. Plain PHP, so it can be tested
	 * without WordPress; the markup escapes it again with `esc_url()`.
	 *
	 * @param string $url What was given.
	 */
	private static function url( string $url ): string {
		$url = trim( $url );

		if ( 1 !== preg_match( '#^https?://#i', $url ) || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Keeps only what a template may decide, and only when it is valid.
	 *
	 * @param array<string, mixed> $defaults As given.
	 * @return array{cover?: string, accent?: string, panel_start?: string, panel_end?: string}
	 */
	private static function filter( array $defaults ): array {
		$kept = array();

		if ( isset( $defaults['cover'] ) && in_array( $defaults['cover'], self::covers(), true ) ) {
			$kept['cover'] = (string) $defaults['cover'];
		}

		if ( isset( $defaults['accent'] ) && is_string( $defaults['accent'] )
			&& 1 === preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $defaults['accent'] ) ) {
			$kept['accent'] = $defaults['accent'];
		}

		foreach ( array( 'panel_start', 'panel_end' ) as $key ) {
			$color = is_string( $defaults[ $key ] ?? null ) ? Color::normalize( $defaults[ $key ] ) : '';

			if ( '' !== $color ) {
				$kept[ $key ] = $color;
			}
		}

		return $kept;
	}
}
