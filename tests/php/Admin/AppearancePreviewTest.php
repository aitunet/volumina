<?php
/**
 * The Appearance preview fetches no audio.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Admin;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Admin\AppearancePreview;

/**
 * The player's audio element loses its source, so loading the tab never asks
 * for the stream.
 */
final class AppearancePreviewTest extends TestCase {

	/**
	 * The src goes, preload becomes none, the other attributes stay.
	 */
	public function test_the_audio_tag_loses_its_source(): void {
		$html = '<div><audio
		class="volumina-player-audio"
		data-volumina-audio
		preload="metadata"
		src="http://example.test/?volumina_stream=12&amp;x=1"
		controls
	></audio></div>';

		$out = AppearancePreview::without_audio_source( $html );

		$this->assertStringNotContainsString( 'src=', $out );
		$this->assertStringNotContainsString( 'volumina_stream', $out );
		$this->assertStringNotContainsString( 'preload="metadata"', $out );
		$this->assertStringContainsString( 'preload="none"', $out );
		$this->assertStringContainsString( 'class="volumina-player-audio"', $out );
		$this->assertStringContainsString( 'data-volumina-audio', $out );
	}

	/**
	 * A source element inside the audio goes too.
	 */
	public function test_source_elements_are_removed(): void {
		$out = AppearancePreview::without_audio_source( '<audio><source src="a.mp3" type="audio/mpeg" /></audio>' );

		$this->assertSame( '<audio preload="none"></audio>', $out );
	}

	/**
	 * Only the audio element is touched: an image keeps its src, and a data
	 * attribute that merely ends in "src" is not an src.
	 */
	public function test_everything_else_is_left_alone(): void {
		$html = '<img src="cover.jpg" alt="" /><span data-volumina-src="x"></span>';

		$this->assertSame( $html, AppearancePreview::without_audio_source( $html ) );
	}
}
