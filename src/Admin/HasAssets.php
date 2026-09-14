<?php
/**
 * The optional assets contract.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * A screen that brings its own stylesheet or script.
 *
 * Additive, like `HasHelp`: a screen without it keeps working as it did.
 * The registry calls `enqueue()` on `load-{page}`, early enough for a style
 * to print in the head rather than trail in the footer after the page has
 * already been drawn unstyled. Enqueue here; never echo a tag.
 *
 * This interface is public API. It will not change without a major version.
 */
interface HasAssets {

	/**
	 * Enqueues what this screen, and only this screen, needs.
	 */
	public function enqueue(): void;
}
