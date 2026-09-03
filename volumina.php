<?php
/**
 * Plugin Name:       Volumina
 * Plugin URI:        https://tunetdesign.com/volumina
 * Description:       Audiobook player and library. Publish audiobooks with chapters, remembered position, playback speed and a sleep timer.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            TUNET
 * Author URI:        https://tunetdesign.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       volumina
 * Domain Path:       /languages
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin version. Kept in step with the header above and with readme.txt.
 */
const VERSION = '0.1.0';

/**
 * Absolute path to this file, so other code never guesses it.
 */
const PLUGIN_FILE = __FILE__;

/**
 * Composer autoloader. Absent in a source checkout that has not run
 * `composer install`; the plugin must not fatal in that case.
 */
$volumina_autoloader = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $volumina_autoloader ) ) {
	return;
}

require_once $volumina_autoloader;

unset( $volumina_autoloader );
