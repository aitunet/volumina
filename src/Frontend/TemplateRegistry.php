<?php
/**
 * The template registry.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Every template a site may choose from, in the order they were registered.
 *
 * The free plugin adds "Basic" first, so it is what a site starts on; an
 * extension adds its own through `volumina_register_templates`. One template
 * per id: adding an id again replaces that template where it stands. Plain
 * PHP, so it can be tested; the WordPress side — firing the action, reading
 * the site's choice — is `Templates`.
 *
 * This class is public API. It will not change without a major version.
 */
final class TemplateRegistry {

	/**
	 * Templates keyed by id, in insertion order.
	 *
	 * @var array<string, Template>
	 */
	private array $templates = array();

	/**
	 * Adds a template, or replaces the one already under its id.
	 *
	 * @param Template $template The template.
	 */
	public function add( Template $template ): void {
		$this->templates[ $template->id() ] = $template;
	}

	/**
	 * Whether an id is registered.
	 *
	 * @param string $id The id.
	 */
	public function has( string $id ): bool {
		return isset( $this->templates[ $id ] );
	}

	/**
	 * The template under an id, if any.
	 *
	 * @param string $id The id.
	 */
	public function get( string $id ): ?Template {
		return $this->templates[ $id ] ?? null;
	}

	/**
	 * The first template registered: what a site starts on.
	 */
	public function first(): ?Template {
		foreach ( $this->templates as $template ) {
			return $template;
		}

		return null;
	}

	/**
	 * Every template, keyed by id, in the order registered.
	 *
	 * @return array<string, Template>
	 */
	public function all(): array {
		return $this->templates;
	}
}
