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
 * Filled in when the settings screen exists (S5) and the progress table exists
 * (S1). Until then there is nothing to remove, and the opt-in is the only gate
 * that matters:
 *
 *   if ( '1' !== get_option( 'volumina_delete_data_on_uninstall' ) ) {
 *       return;
 *   }
 *
 * Then drop wp_volumina_progress and delete every volumina_* option.
 */
