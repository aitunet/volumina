<?php
/**
 * Tests for the asset version.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Support;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Support\AssetVersion;

// A real folder on disk is the thing under test, and WP_Filesystem does not
// exist in these plain unit tests.
// phpcs:disable WordPress.WP.AlternativeFunctions

/**
 * A file's `?ver=` changes when the file does, so no cache keeps an old copy.
 */
final class AssetVersionTest extends TestCase {

	/**
	 * A plugin folder with one asset in it.
	 *
	 * @var string
	 */
	private string $dir;

	/**
	 * Makes the folder: a main file and `assets/css/book.css`.
	 */
	protected function setUp(): void {
		$this->dir = sys_get_temp_dir() . '/volumina-asset-version-' . uniqid();
		mkdir( $this->dir . '/assets/css', 0777, true );
		file_put_contents( $this->dir . '/plugin.php', '<?php' );
		file_put_contents( $this->dir . '/assets/css/book.css', 'a{}' );
	}

	/**
	 * Removes the folder.
	 */
	protected function tearDown(): void {
		unlink( $this->dir . '/assets/css/book.css' );
		unlink( $this->dir . '/plugin.php' );
		rmdir( $this->dir . '/assets/css' );
		rmdir( $this->dir . '/assets' );
		rmdir( $this->dir );
	}

	/**
	 * The plugin's version, a dot, the file's modification time.
	 */
	public function test_the_plugin_version_then_the_file_time(): void {
		touch( $this->dir . '/assets/css/book.css', 1790000000 );
		clearstatcache();

		$this->assertSame( '1.0.0.1790000000', AssetVersion::of( 'assets/css/book.css', $this->dir . '/plugin.php', '1.0.0' ) );
	}

	/**
	 * Editing the file is enough; the plugin's version need not move.
	 */
	public function test_an_edited_file_gets_a_new_version(): void {
		touch( $this->dir . '/assets/css/book.css', 1790000000 );
		clearstatcache();
		$before = AssetVersion::of( 'assets/css/book.css', $this->dir . '/plugin.php', '1.0.0' );

		touch( $this->dir . '/assets/css/book.css', 1790000060 );
		clearstatcache();

		$this->assertNotSame( $before, AssetVersion::of( 'assets/css/book.css', $this->dir . '/plugin.php', '1.0.0' ) );
	}

	/**
	 * No file, no time to add.
	 */
	public function test_a_missing_file_keeps_the_plugin_version(): void {
		$this->assertSame( '1.0.0', AssetVersion::of( 'assets/css/none.css', $this->dir . '/plugin.php', '1.0.0' ) );
	}

	/**
	 * Only files inside the plugin's folder are looked at.
	 */
	public function test_a_path_leaving_the_plugin_keeps_the_plugin_version(): void {
		$this->assertSame( '1.0.0', AssetVersion::of( '../plugin.php', $this->dir . '/assets/css/book.css', '1.0.0' ) );
	}
}
