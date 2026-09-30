<?php
/**
 * The version a script or stylesheet is enqueued with.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Support;

defined( 'ABSPATH' ) || exit;

/**
 * The plugin's version followed by the file's modification time, so the
 * `?ver=` in its URL changes whenever the file does. With the bare plugin
 * version a CDN or a browser keeps serving the old file after an update
 * that did not bump it, for as long as its cache lasts.
 *
 * Takes the same two paths as `plugins_url()`.
 */
final class AssetVersion {

	/**
	 * The version for one asset.
	 *
	 * @param string $path    The asset, relative to the plugin's folder, as given to `plugins_url()`.
	 * @param string $plugin  The plugin's main file (or any file in its folder).
	 * @param string $version The plugin's version; all that is returned for a file that is not there.
	 */
	public static function of( string $path, string $plugin, string $version ): string {
		$root = realpath( dirname( $plugin ) );
		$file = realpath( dirname( $plugin ) . '/' . ltrim( $path, '/' ) );

		if ( false === $root || false === $file || ! str_starts_with( $file, $root . DIRECTORY_SEPARATOR ) || ! is_file( $file ) ) {
			return $version;
		}

		$time = filemtime( $file );

		return false === $time ? $version : $version . '.' . $time;
	}
}
