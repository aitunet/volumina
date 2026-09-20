#!/usr/bin/env php
<?php
/**
 * Build the release zip.
 *
 * Usage:
 *   php bin/build-release.php [--composer="php C:/path/to/composer.phar"]
 *
 * Copies the plugin into dist/volumina/, leaving out everything `.distignore`
 * names; rebuilds vendor/ there with `composer install --no-dev`, so that only
 * the autoloader ships; and zips the result as dist/volumina-<version>.zip
 * with a volumina/ top-level directory, which is what the plugin directory and
 * the WordPress uploader both expect. It then prints every file that went in:
 * the list is checked by reading it, not by trusting it.
 *
 * This is what `wp dist-archive` does. Use that where it can be installed;
 * this exists because the machine the plugin was built on could not.
 *
 * It runs outside WordPress, on the command line, which is why the sniffs
 * about WordPress's filesystem wrappers, shell calls and escaped output do not
 * apply to it.
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable WordPress.PHP.DiscouragedPHPFunctions
 * phpcs:disable WordPress.Security.EscapeOutput
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Build;

if ( 'cli' !== PHP_SAPI ) {
	exit( 'This script runs from the command line only.' );
}

const SLUG = 'volumina';

/**
 * Everything that must never appear in the zip, whatever `.distignore` says.
 *
 * A second, deliberately short list, so that a mistake in the rules — or in
 * how this script reads them — is caught rather than shipped.
 */
const NEVER_SHIPS = array(
	'node_modules/',
	'vendor/bin/',
	'tests/',
	'docs/',
	'blocks/',
	'.git/',
	'.github/',
	'.claude/',
	'.playwright-mcp/',
	'.wordpress-org/',
	'bin/',
	'CLAUDE.md',
	'composer.lock',
	'package.json',
	'package-lock.json',
	'phpunit.xml.dist',
	'phpcs.xml',
	'phpstan.neon',
	'.distignore',
);

/**
 * Print a line and, when told to, stop.
 *
 * @param string $message What to say.
 * @param bool   $fatal   Whether the build cannot go on.
 * @return void
 */
function say( string $message, bool $fatal = false ): void {
	fwrite( $fatal ? STDERR : STDOUT, $message . PHP_EOL );
	if ( $fatal ) {
		exit( 1 );
	}
}

/**
 * Read the plugin version from the header in volumina.php.
 *
 * @param string $root Plugin root.
 * @return string
 */
function plugin_version( string $root ): string {
	$header = (string) file_get_contents( $root . '/' . SLUG . '.php' );
	if ( ! preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $header, $found ) ) {
		say( 'Could not read the version from ' . SLUG . '.php.', true );
	}
	return $found[1];
}

/**
 * Parse `.distignore` into rules.
 *
 * Each rule is the pattern with its meaning made explicit: whether it is
 * anchored to the plugin root (a leading slash, or a slash anywhere inside),
 * and whether it matches directories only (a trailing slash).
 *
 * @param string $root Plugin root.
 * @return array<int, array{pattern: string, anchored: bool, dir_only: bool}>
 */
function ignore_rules( string $root ): array {
	$rules = array();
	$lines = file( $root . '/.distignore', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	foreach ( (array) $lines as $line ) {
		$line = trim( (string) $line );
		if ( '' === $line || str_starts_with( $line, '#' ) ) {
			continue;
		}
		$dir_only = str_ends_with( $line, '/' );
		$line     = rtrim( $line, '/' );
		$anchored = str_starts_with( $line, '/' ) || str_contains( $line, '/' );
		$rules[]  = array(
			'pattern'  => ltrim( $line, '/' ),
			'anchored' => $anchored,
			'dir_only' => $dir_only,
		);
	}
	return $rules;
}

/**
 * Whether a path, relative to the plugin root, is kept out of the zip.
 *
 * @param string                                                             $relative Forward-slash path from the root.
 * @param bool                                                               $is_dir   Whether it is a directory.
 * @param array<int, array{pattern: string, anchored: bool, dir_only: bool}> $rules    From ignore_rules().
 * @return bool
 */
function is_ignored( string $relative, bool $is_dir, array $rules ): bool {
	$name = basename( $relative );
	foreach ( $rules as $rule ) {
		if ( $rule['dir_only'] && ! $is_dir ) {
			continue;
		}
		$subject = $rule['anchored'] ? $relative : $name;
		if ( fnmatch( $rule['pattern'], $subject, FNM_PATHNAME ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Copy the plugin tree into the staging directory, minus what is ignored.
 *
 * @param string                                                             $from     Source directory.
 * @param string                                                             $to       Staging directory.
 * @param string                                                             $relative Path of $from relative to the root, '' at the top.
 * @param array<int, array{pattern: string, anchored: bool, dir_only: bool}> $rules    From ignore_rules().
 * @return void
 */
function copy_tree( string $from, string $to, string $relative, array $rules ): void {
	$entries = scandir( $from );
	if ( false === $entries ) {
		say( "Cannot read $from.", true );
	}
	foreach ( $entries as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}
		$source = $from . '/' . $entry;
		$path   = '' === $relative ? $entry : $relative . '/' . $entry;
		$is_dir = is_dir( $source );
		if ( is_ignored( $path, $is_dir, $rules ) ) {
			continue;
		}
		if ( $is_dir ) {
			if ( ! mkdir( $to . '/' . $entry, 0755, true ) && ! is_dir( $to . '/' . $entry ) ) {
				say( "Cannot create $to/$entry.", true );
			}
			copy_tree( $source, $to . '/' . $entry, $path, $rules );
		} elseif ( ! copy( $source, $to . '/' . $entry ) ) {
			say( "Cannot copy $path.", true );
		}
	}
}

/**
 * Delete a directory and everything in it.
 *
 * @param string $dir Directory to remove.
 * @return void
 */
function remove_tree( string $dir ): void {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	foreach ( (array) scandir( $dir ) as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}
		$path = $dir . '/' . $entry;
		if ( is_dir( $path ) && ! is_link( $path ) ) {
			remove_tree( $path );
		} else {
			unlink( $path );
		}
	}
	rmdir( $dir );
}

/**
 * Newest modification time anywhere under a directory.
 *
 * @param string $dir Directory to walk.
 * @return int
 */
function newest_mtime( string $dir ): int {
	$newest   = 0;
	$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) );
	foreach ( $iterator as $file ) {
		$newest = max( $newest, (int) $file->getMTime() );
	}
	return $newest;
}

/**
 * Run a command in a directory and return its exit code.
 *
 * @param string $command Shell command.
 * @param string $cwd     Working directory.
 * @return int
 */
function run( string $command, string $cwd ): int {
	$process = proc_open( $command, array( STDIN, STDOUT, STDERR ), $pipes, $cwd );
	if ( ! is_resource( $process ) ) {
		say( "Could not start: $command", true );
	}
	return proc_close( $process );
}

/**
 * Every file under a directory, as forward-slash paths relative to it, sorted.
 *
 * @param string $dir Directory to walk.
 * @return string[]
 */
function list_files( string $dir ): array {
	$files    = array();
	$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ) );
	foreach ( $iterator as $file ) {
		$files[] = str_replace( '\\', '/', substr( (string) $file->getPathname(), strlen( $dir ) + 1 ) );
	}
	sort( $files );
	return $files;
}

/**
 * Build the zip.
 *
 * @return void
 */
function build(): void {

	$root     = dirname( __DIR__ );
	$options  = getopt( '', array( 'composer::' ) );
	$composer = is_string( $options['composer'] ?? null ) && '' !== $options['composer'] ? $options['composer'] : 'composer';

	$version = plugin_version( $root );
	$dist    = $root . '/dist';
	$staging = $dist . '/' . SLUG;
	$zip     = $dist . '/' . SLUG . '-' . $version . '.zip';

	say( 'Building ' . SLUG . ' ' . $version );

	// The blocks ship compiled, and a stale build/ is the easiest way to ship
	// yesterday's editor. Refuse rather than guess.
	if ( ! is_dir( $root . '/build' ) || array() === glob( $root . '/build/*/block.json' ) ) {
		say( 'build/ is missing or empty. Run `npm run build` first.', true );
	}
	if ( newest_mtime( $root . '/blocks' ) > newest_mtime( $root . '/build' ) ) {
		say( 'blocks/ is newer than build/. Run `npm run build` first.', true );
	}

	remove_tree( $staging );
	if ( ! mkdir( $staging, 0755, true ) ) {
		say( "Cannot create $staging.", true );
	}

	copy_tree( $root, $staging, '', ignore_rules( $root ) );

	// vendor/ was copied as it is in the working tree, dev dependencies and all.
	// Rebuild it in the staging copy so what ships is the autoloader alone.
	remove_tree( $staging . '/vendor' );
	copy( $root . '/composer.json', $staging . '/composer.json' );
	copy( $root . '/composer.lock', $staging . '/composer.lock' );
	$status = run( $composer . ' install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet', $staging );
	if ( 0 !== $status ) {
		say( "composer install failed with status $status. Pass --composer=\"php /path/to/composer.phar\" if it is not on the PATH.", true );
	}
	// composer.json stays: the directory asks to see the manifest of whatever
	// vendor/ holds. The lock is the workshop's and goes.
	unlink( $staging . '/composer.lock' );
	remove_tree( $staging . '/vendor/bin' );

	$files = list_files( $staging );

	foreach ( $files as $file ) {
		foreach ( NEVER_SHIPS as $forbidden ) {
			if ( $file === $forbidden || str_starts_with( $file, $forbidden ) ) {
				say( "Refusing to ship $file.", true );
			}
		}
	}

	if ( file_exists( $zip ) ) {
		unlink( $zip );
	}
	$archive = new \ZipArchive();
	if ( true !== $archive->open( $zip, \ZipArchive::CREATE ) ) {
		say( "Cannot create $zip.", true );
	}
	foreach ( $files as $file ) {
		$archive->addFile( $staging . '/' . $file, SLUG . '/' . $file );
	}
	$archive->close();

	foreach ( $files as $file ) {
		say( '  ' . SLUG . '/' . $file );
	}
	say( '' );
	say( sprintf( '%d files, %s bytes: %s', count( $files ), number_format( (int) filesize( $zip ) ), $zip ) );
}

build();
