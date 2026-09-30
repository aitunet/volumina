<?php
/**
 * Tests for the panel colours a rendering uses.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Tests\Frontend;

use PHPUnit\Framework\TestCase;
use TUNET\Volumina\Frontend\Template;
use TUNET\Volumina\Frontend\Templates;

/**
 * Two chosen colours make the panel; one chosen takes the other from the
 * template, or, when the template has none, is used for both; none chosen
 * leaves the template to its own.
 */
final class TemplatesPanelTest extends TestCase {

	/**
	 * Stage's colours, as a Pro template would register them.
	 */
	private const STAGE = array(
		'panel_start' => '#5b3fd0',
		'panel_end'   => '#0e1014',
	);

	/**
	 * Nothing chosen: no panel, the template draws its own.
	 */
	public function test_nothing_chosen_is_no_panel(): void {
		$this->assertNull( Templates::panel( '', '', self::STAGE ) );
		$this->assertNull( Templates::panel( '', '', array() ) );
	}

	/**
	 * Both chosen: both used.
	 */
	public function test_both_chosen(): void {
		$panel = Templates::panel( '#ff0000', '#000000', self::STAGE );

		$this->assertSame( '#000000', $panel['end'] );
		$this->assertStringStartsWith( '#', $panel['start'] );
	}

	/**
	 * One chosen: the other is the template's.
	 */
	public function test_one_chosen_takes_the_other_from_the_template(): void {
		$this->assertSame( '#5b3fd0', Templates::panel( '', '#101010', self::STAGE )['start'] );
		$this->assertSame( '#0e1014', Templates::panel( '#7a2cf0', '', self::STAGE )['end'] );
	}

	/**
	 * One chosen and a template with no colours of its own, as Basic: a
	 * plain panel of that colour.
	 */
	public function test_one_chosen_alone_is_a_plain_panel(): void {
		$panel = Templates::panel( '', '#f4ecd8', array() );

		$this->assertSame( '#f4ecd8', $panel['start'] );
		$this->assertSame( '#f4ecd8', $panel['end'] );
	}

	/**
	 * The wrapper carries the panel as custom properties and a class, next to the accent's.
	 */
	public function test_attributes_carry_the_panel(): void {
		$template = new Template( 'basic', 'Basic', '', array( 'cover' => Template::COVER_SIDE ) );
		$wrap     = Templates::attributes(
			array(
				'template' => $template,
				'cover'    => Template::COVER_SIDE,
				'accent'   => '#ff6b35',
				'ink'      => '#000000',
				'panel'    => Templates::panel( '#5b3fd0', '#0e1014', array() ),
			)
		);

		$this->assertStringContainsString( 'volumina-has-panel', $wrap['class'] );
		$this->assertStringContainsString( '--volumina-panel-start:#5b3fd0', $wrap['style'] );
		$this->assertStringContainsString( '--volumina-panel-end:#0e1014', $wrap['style'] );
		$this->assertStringContainsString( '--volumina-panel-ink:#f7f7f5', $wrap['style'] );
		$this->assertStringContainsString( '--volumina-panel-soft:', $wrap['style'] );
		$this->assertStringContainsString( '--volumina-panel-line:', $wrap['style'] );
		$this->assertStringContainsString( '--volumina-accent:#ff6b35', $wrap['style'] );
	}

	/**
	 * No panel, no panel class and no panel properties.
	 */
	public function test_attributes_without_a_panel(): void {
		$template = new Template( 'basic', 'Basic', '', array() );
		$wrap     = Templates::attributes(
			array(
				'template' => $template,
				'cover'    => Template::COVER_SIDE,
				'accent'   => '',
				'ink'      => '',
				'panel'    => null,
			)
		);

		$this->assertStringNotContainsString( 'panel', $wrap['class'] . $wrap['style'] );
	}
}
