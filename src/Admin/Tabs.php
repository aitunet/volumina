<?php
/**
 * Which settings tab is current.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * The tab a request asked for when it exists, the first tab otherwise. Plain
 * PHP, so the rule can be tested without WordPress.
 */
final class Tabs {

	/**
	 * The current tab.
	 *
	 * @param string[] $ids       Every tab id, in order.
	 * @param string   $requested The id the request asked for; '' for none.
	 */
	public static function current( array $ids, string $requested ): string {
		$requested = trim( $requested );

		if ( '' !== $requested && in_array( $requested, $ids, true ) ) {
			return $requested;
		}

		return $ids[0] ?? 'general';
	}
}
