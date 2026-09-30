<?php
/**
 * Templates, on the WordPress side.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Frontend;

use TUNET\Volumina\Admin\Settings;
use TUNET\Volumina\Support\Color;

use const TUNET\Volumina\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the registry, adds "Basic" to it, lets extensions add theirs, and
 * works out what one rendering should use.
 *
 * Resolution, lowest to highest: the template's own defaults, then the site's
 * settings, then whatever the caller passes — a block's attributes, say. The
 * outcome is a template, a cover position, and an accent as a hex colour with
 * the ink that reads on it, ready for the markup.
 */
final class Templates {

	/**
	 * The free template's id.
	 */
	public const BASIC = 'basic';

	/**
	 * The registry, once built.
	 *
	 * @var TemplateRegistry|null
	 */
	private static ?TemplateRegistry $registry = null;

	/**
	 * Template id => name and picture, for the Appearance tab's cards.
	 *
	 * @return array<string, array{label: string, image: string}>
	 */
	public static function cards(): array {
		$cards = array();

		foreach ( self::registry()->all() as $id => $template ) {
			$cards[ $id ] = array(
				'label' => $template->label(),
				'image' => $template->preview(),
			);
		}

		return $cards;
	}

	/**
	 * The registry, with Basic and every extension's templates in it.
	 */
	public static function registry(): TemplateRegistry {
		if ( null === self::$registry ) {
			self::$registry = new TemplateRegistry();
			self::$registry->add(
				new Template(
					self::BASIC,
					__( 'Basic', 'volumina' ),
					Assets::BOOK,
					array( 'cover' => Template::COVER_SIDE ),
					plugins_url( 'assets/images/templates/basic.png', PLUGIN_FILE )
				)
			);

			/**
			 * Fires so extensions can register presentation templates.
			 *
			 * @param TemplateRegistry $registry The registry to add templates to.
			 */
			do_action( 'volumina_register_templates', self::$registry );
		}

		return self::$registry;
	}

	/**
	 * Forgets the registry, so the next request builds it again. For tests.
	 */
	public static function reset(): void {
		self::$registry = null;
	}

	/**
	 * Template id => label, for a settings field or a block control.
	 *
	 * @return array<string, string>
	 */
	public static function choices(): array {
		$choices = array();

		foreach ( self::registry()->all() as $id => $template ) {
			$choices[ $id ] = $template->label();
		}

		return $choices;
	}

	/**
	 * An accent as stored — `none`, a palette slug, or a colour of the site's
	 * own as `#rrggbb` — as a hex colour, or empty for none. A slug the active
	 * theme does not have is none.
	 *
	 * @param string $accent The stored value.
	 */
	public static function accent( string $accent ): string {
		if ( 'none' === $accent || '' === $accent ) {
			return '';
		}

		if ( '#' === $accent[0] ) {
			return Color::normalize( $accent );
		}

		return Palette::color( $accent );
	}

	/**
	 * What one rendering should use.
	 *
	 * @param array<string, string> $overrides `template`, `cover`, `accent`, `panel_start` and `panel_end`; an empty value defers.
	 * @return array{template: Template, cover: string, accent: string, ink: string, panel: array{start: string, end: string, ink: string, soft: string, line: string}|null}
	 */
	public static function resolve( array $overrides = array() ): array {
		$registry = self::registry();
		$wanted   = (string) ( $overrides['template'] ?? '' );

		if ( '' === $wanted ) {
			$wanted = (string) Settings::get( 'template' );
		}

		$template = $registry->get( $wanted ) ?? $registry->first() ?? $registry->get( self::BASIC );
		$defaults = $template->defaults();

		$cover = (string) ( $overrides['cover'] ?? '' );

		if ( '' === $cover ) {
			$cover = (string) Settings::get( 'cover_position' );
		}

		if ( '' === $cover || ! in_array( $cover, Template::covers(), true ) ) {
			$cover = $defaults['cover'] ?? Template::COVER_SIDE;
		}

		$slug = (string) ( $overrides['accent'] ?? '' );

		if ( '' === $slug ) {
			$slug = (string) Settings::get( 'accent' );
		}

		if ( '' === $slug ) {
			$slug = $defaults['accent'] ?? '';
		}

		$accent = self::accent( $slug );
		$chosen = array();

		foreach ( array( 'panel_start', 'panel_end' ) as $key ) {
			$chosen[ $key ] = (string) ( $overrides[ $key ] ?? '' );

			if ( '' === $chosen[ $key ] ) {
				$chosen[ $key ] = (string) Settings::get( $key );
			}
		}

		return array(
			'template' => $template,
			'cover'    => $cover,
			'accent'   => $accent,
			'ink'      => Color::ink( $accent ),
			'panel'    => self::panel( $chosen['panel_start'], $chosen['panel_end'], $defaults ),
		);
	}

	/**
	 * The panel a rendering is painted on, from the two colours chosen.
	 *
	 * None chosen is no panel: the template draws its own. One chosen takes
	 * the other from the template's defaults, or, for a template with none of
	 * its own such as Basic, is used for both: a plain panel.
	 *
	 * @param string                $start    The first colour chosen, or ''.
	 * @param string                $end      The second, or ''.
	 * @param array<string, string> $defaults The template's defaults.
	 * @return array{start: string, end: string, ink: string, soft: string, line: string}|null
	 */
	public static function panel( string $start, string $end, array $defaults ): ?array {
		$start = Color::normalize( $start );
		$end   = Color::normalize( $end );

		if ( '' === $start && '' === $end ) {
			return null;
		}

		if ( '' === $start ) {
			$start = $defaults['panel_start'] ?? $end;
		}

		if ( '' === $end ) {
			$end = $defaults['panel_end'] ?? $start;
		}

		return Color::panel( $start, $end );
	}

	/**
	 * The wrapper's classes and inline style for a resolution.
	 *
	 * The style carries custom properties and nothing else, every one a colour
	 * from `Color::normalize()` or `Color::panel()`, so no value that reaches
	 * here can be more than a colour.
	 *
	 * @param array{template: Template, cover: string, accent: string, ink: string, panel?: array{start: string, end: string, ink: string, soft: string, line: string}|null} $resolved   From `resolve()`.
	 * @param bool                                                                                                                                                           $with_cover Whether this wrapper places a cover; a chapter list alone does not.
	 * @return array{class: string, style: string}
	 */
	public static function attributes( array $resolved, bool $with_cover = true ): array {
		$classes = array( 'volumina-template-' . $resolved['template']->id() );
		$style   = '';

		if ( $with_cover ) {
			$classes[] = 'volumina-cover-' . $resolved['cover'];
		}

		if ( '' !== $resolved['accent'] ) {
			$classes[] = 'volumina-has-accent';
			$style     = sprintf( '--volumina-accent:%s;--volumina-accent-ink:%s', $resolved['accent'], $resolved['ink'] );
		}

		$panel = $resolved['panel'] ?? null;

		if ( null !== $panel ) {
			$classes[] = 'volumina-has-panel';
			$style    .= ( '' === $style ? '' : ';' ) . sprintf(
				'--volumina-panel-start:%s;--volumina-panel-end:%s;--volumina-panel-ink:%s;--volumina-panel-soft:%s;--volumina-panel-line:%s',
				$panel['start'],
				$panel['end'],
				$panel['ink'],
				$panel['soft'],
				$panel['line']
			);
		}

		return array(
			'class' => implode( ' ', $classes ),
			'style' => $style,
		);
	}
}
