<?php
/**
 * The plugin's settings.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Admin;

use TUNET\Volumina\Access\Mode;
use TUNET\Volumina\Frontend\Palette;
use TUNET\Volumina\Frontend\Template;
use TUNET\Volumina\Frontend\Templates;
use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\Support\Logger;
use TUNET\Volumina\Support\Registrable;
use TUNET\Volumina\Support\Settings\Field;
use TUNET\Volumina\Support\Settings\Group;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * What the site has chosen, and what follows from it.
 *
 * Seven settings, and every one of them has to earn its place: a setting is a
 * question asked of somebody who wanted to publish an audiobook. Anything with
 * an obviously right answer is not a setting, it is a decision this plugin
 * should have made.
 */
final class Settings implements Registrable {

	/**
	 * The option the settings live in.
	 */
	public const OPTION = 'volumina_settings';

	/**
	 * The option group the Appearance tab's form posts as. Volumina's own
	 * option is in it, and an extension adds its own with `register_setting()`.
	 */
	public const APPEARANCE_GROUP = 'volumina_appearance';

	/**
	 * The option the log lives in.
	 */
	public const LOG_OPTION = 'volumina_log';

	/**
	 * The group, built once.
	 *
	 * @var Group|null
	 */
	private static ?Group $group = null;

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'init', array( $this, 'configure_logger' ) );
		add_action( 'save_post_' . Book::POST_TYPE, array( $this, 'apply_default_mode' ), 10, 2 );

		// Saving the settings once is what sets the site up. The `pre_update`
		// filter, not the `update_option` action: the action only fires when the
		// value changed, and saving the form as it stands is a decision too.
		add_filter( 'pre_update_option_' . self::OPTION, array( $this, 'saved' ) );
		add_filter( 'allowed_options', array( $this, 'allow_in_appearance' ) );
	}

	/**
	 * Lets the Appearance form save Volumina's option too, without a second
	 * `register_setting()` — which would attach the sanitize callback twice.
	 *
	 * @param mixed $allowed Option group => option names.
	 * @return array<string, array<int, string>>
	 */
	public function allow_in_appearance( $allowed ): array {
		$allowed = is_array( $allowed ) ? $allowed : array();

		$list = isset( $allowed[ self::APPEARANCE_GROUP ] ) && is_array( $allowed[ self::APPEARANCE_GROUP ] ) ? $allowed[ self::APPEARANCE_GROUP ] : array();

		if ( ! in_array( self::OPTION, $list, true ) ) {
			$list[] = self::OPTION;
		}

		$allowed[ self::APPEARANCE_GROUP ] = $list;

		return $allowed;
	}

	/**
	 * Marks the site as set up whenever the settings are saved, changed or
	 * not, and hands the value on untouched.
	 *
	 * @param mixed $value What is being saved.
	 * @return mixed
	 */
	public function saved( $value ) {
		Setup::finish();

		return $value;
	}

	/**
	 * Registers the option, so `options.php` will accept a form posting to it.
	 */
	public function register_setting(): void {
		self::group()->register();
	}

	/**
	 * Points the logger at its option and tells it whether to record.
	 */
	public function configure_logger(): void {
		Logger::configure( self::LOG_OPTION, (bool) self::get( 'logging' ) );
	}

	/**
	 * Gives a new book the site's default access mode.
	 *
	 * Only when the book has none of its own. The setting decides what a new
	 * book starts as and nothing else: changing it later must not reach back
	 * and quietly take an existing book away from its listeners.
	 *
	 * @param int     $post_id The book.
	 * @param WP_Post $post    The book.
	 */
	public function apply_default_mode( int $post_id, WP_Post $post ): void {
		if ( Book::POST_TYPE !== $post->post_type ) {
			return;
		}

		// `metadata_exists`, not `get_post_meta`: the meta is registered with a
		// default, so reading it never comes back empty and a check on its
		// value would decide this book already had one. Only the existence of
		// the row says whether anybody has actually chosen.
		if ( metadata_exists( 'post', $post_id, Mode::META_KEY ) ) {
			return;
		}

		update_post_meta( $post_id, Mode::META_KEY, Mode::sanitize( self::get( 'default_access' ) ) );
	}

	/**
	 * The settings group.
	 */
	public static function group(): Group {
		if ( null === self::$group ) {
			self::$group = new Group( self::OPTION, self::fields() );
		}

		return self::$group;
	}

	/**
	 * One setting's value.
	 *
	 * @param string $key Which one.
	 * @return mixed
	 */
	public static function get( string $key ) {
		return self::group()->get( $key );
	}

	/**
	 * The sections of the settings screen: id => title and one line about it.
	 *
	 * @return array<string, array{title: string, description: string}>
	 */
	public static function sections(): array {
		return array(
			'listening'    => array(
				'title'       => __( 'Listening', 'volumina' ),
				'description' => __( 'Who may listen, and where the player appears.', 'volumina' ),
			),
			'presentation' => array(
				'title'       => __( 'Presentation', 'volumina' ),
				'description' => __( 'How an audiobook looks on the page. A block can differ from these.', 'volumina' ),
			),
			'housekeeping' => array(
				'title'       => __( 'Housekeeping', 'volumina' ),
				'description' => __( 'For when something goes wrong, and for when the plugin goes.', 'volumina' ),
			),
		);
	}

	/**
	 * The accent choices: none, what the template prefers, then the theme's palette.
	 *
	 * @return array<string, array{label: string, color?: string}>
	 */
	public static function accent_choices(): array {
		$choices = array(
			''     => array( 'label' => __( 'What the template prefers', 'volumina' ) ),
			'none' => array( 'label' => __( 'None', 'volumina' ) ),
		);

		foreach ( Palette::colors() as $slug => $color ) {
			$choices[ $slug ] = array(
				'label' => $color['name'],
				'color' => $color['color'],
			);
		}

		return $choices;
	}

	/**
	 * The settings themselves.
	 *
	 * @return array<int, Field>
	 */
	private static function fields(): array {
		return array(
			new Field(
				array(
					'key'         => 'default_access',
					'section'     => 'listening',
					'label'       => __( 'New audiobooks are', 'volumina' ),
					'type'        => 'radio',
					'default'     => Mode::PUBLIC,
					'choices'     => Mode::labels(),
					'description' => __( 'What a book you add from now on starts as. Every book can be changed on its own screen, and changing this never changes a book that already exists.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'append_to_content',
					'section'     => 'listening',
					'label'       => __( 'Book pages', 'volumina' ),
					'type'        => 'checkbox',
					'default'     => true,
					'description' => __( 'Show the player and the chapters on an audiobook page automatically.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'template',
					'section'     => 'presentation',
					'label'       => __( 'Template', 'volumina' ),
					'type'        => 'cards',
					'default'     => Templates::BASIC,
					'choices'     => array( Templates::class, 'cards' ),
					'description' => __( 'How an audiobook is laid out and dressed: its cover, details, player and chapters. A block can choose its own.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'cover_position',
					'section'     => 'presentation',
					'label'       => __( 'Cover', 'volumina' ),
					'type'        => 'radio',
					'default'     => '',
					'choices'     => array(
						''                   => __( 'Where the template puts it', 'volumina' ),
						Template::COVER_SIDE => __( 'Beside the details', 'volumina' ),
						Template::COVER_TOP  => __( 'Above the details', 'volumina' ),
						Template::COVER_NONE => __( 'Not shown', 'volumina' ),
					),
					'description' => __( 'Where the cover goes on an audiobook page. On a narrow screen, beside becomes above once there is no room.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'accent',
					'section'     => 'presentation',
					'label'       => __( 'Accent colour', 'volumina' ),
					'type'        => 'swatches',
					'default'     => '',
					'choices'     => array( self::class, 'accent_choices' ),
					'description' => __( 'One colour from your theme\'s palette for the play button, the progress bar and the current chapter. Never for text, so the theme\'s contrast is never at risk. With none, the player draws itself in the text colour, as it does out of the box.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'logging',
					'section'     => 'housekeeping',
					'label'       => __( 'Log', 'volumina' ),
					'type'        => 'checkbox',
					'default'     => false,
					'description' => __( 'Record notable events, such as a listener being turned away or an audio file that has gone missing. Useful when something is wrong and nobody can say quite what.', 'volumina' ),
				)
			),
			new Field(
				array(
					'key'         => 'delete_data_on_uninstall',
					'section'     => 'housekeeping',
					'label'       => __( 'On uninstall', 'volumina' ),
					'type'        => 'checkbox',
					'default'     => false,
					'description' => __( 'Delete this plugin\'s tables and settings when it is uninstalled. Off means your listeners keep their places if you ever reinstall.', 'volumina' ),
				)
			),
		);
	}
}
