<?php
/**
 * Uninstall routine.
 *
 * Removes the plugin's tables and options, but only when the site owner has
 * explicitly opted in. Deleting a listener's progress by accident is not
 * recoverable, so silence means keep.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina;

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/*
 * The opt-in itself arrives with the settings screen in S5. Until that setting
 * can be turned on, this file removes nothing, which is the safe half of the
 * behaviour and the half worth having first:
 *
 *   if ( '1' !== get_option( 'volumina_delete_data_on_uninstall' ) ) {
 *       return;
 *   }
 *
 * Then drop both tables — wp_volumina_progress and wp_volumina_grants — and
 * delete every volumina_* option. Whenever a table joins the schema it joins
 * this list too; a table left behind by an uninstall is a table nobody knows
 * to look for.
 */
