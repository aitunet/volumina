<?php
/**
 * The listening experience.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

namespace TUNET\Volumina\Player;

use TUNET\Volumina\Frontend\Assets;
use TUNET\Volumina\PostTypes\Book;
use TUNET\Volumina\PostTypes\Chapter;
use TUNET\Volumina\Storage\Progress;
use TUNET\Volumina\Support\Duration;
use TUNET\Volumina\Support\Registrable;
use WP_Post;

use const TUNET\Volumina\PLUGIN_FILE;

defined( 'ABSPATH' ) || exit;

/**
 * Assembles the player: its data, its assets and its markup.
 *
 * Everything a listener needs is in the page before any script runs. The
 * chapter list is real links to real chapters, the transport is real buttons,
 * and the audio element is a real audio element. Script makes it pleasant; its
 * absence makes it plain, not broken.
 */
final class Player implements Registrable {

	/**
	 * Seconds the back button jumps.
	 */
	public const SKIP_BACK = 15;

	/**
	 * Seconds the forward button jumps.
	 */
	public const SKIP_FORWARD = 30;

	/**
	 * Adds the hooks.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Loads the player's assets on a single book page.
	 */
	public function enqueue(): void {
		if ( ! is_singular( Book::POST_TYPE ) ) {
			return;
		}

		wp_enqueue_style( Assets::PLAYER );
		wp_enqueue_script( Assets::PLAYER );
	}

	/**
	 * Everything the script needs that is not specific to one book.
	 *
	 * Public because `Frontend\Assets` registers the handle these belong to.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings(): array {
		return array(
			'restUrl'     => rest_url( 'volumina/v1/progress/' ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			// A guest's position is kept by their own browser. There is no
			// honest way to store it server side without inventing an
			// identifier for someone who never asked to be identified.
			'canSync'     => is_user_logged_in(),
			'skipBack'    => self::SKIP_BACK,
			'skipForward' => self::SKIP_FORWARD,
			'strings'     => array(
				'play'          => __( 'Play', 'volumina' ),
				'pause'         => __( 'Pause', 'volumina' ),
				/* translators: %s: chapter title. */
				'nowPlaying'    => __( 'Playing %s', 'volumina' ),
				/* translators: %s: chapter title. */
				'paused'        => __( 'Paused in %s', 'volumina' ),
				/* translators: 1: elapsed time, 2: total time, both as h:mm:ss. */
				'position'      => __( '%1$s of %2$s', 'volumina' ),
				/* translators: %s: playback speed, such as 1.5. */
				'speedSet'      => __( 'Speed %s times normal', 'volumina' ),
				/* translators: %s: a length of time, such as 30 minutes. */
				'sleepSet'      => __( 'Sleep timer set for %s', 'volumina' ),
				'sleepChapter'  => __( 'Playback will stop at the end of this chapter', 'volumina' ),
				'sleepOff'      => __( 'Sleep timer off', 'volumina' ),
				'sleepFired'    => __( 'Sleep timer finished. Playback stopped.', 'volumina' ),
				'finished'      => __( 'End of the audiobook', 'volumina' ),
				'resumeBlocked' => __( 'Ready to carry on where you left off. Press play.', 'volumina' ),
				'saveFailed'    => __( 'Your place could not be saved to your account. It is kept in this browser.', 'volumina' ),
			),
		);
	}

	/**
	 * Renders the player for a book, or nothing if there is nothing to play.
	 *
	 * @param WP_Post             $book     The book.
	 * @param array<int, WP_Post> $chapters Its chapters, in order.
	 */
	public static function render( WP_Post $book, array $chapters ): string {
		$playable = self::playable( $chapters );

		if ( array() === $playable ) {
			return '';
		}

		$data = array(
			'book'     => (int) $book->ID,
			'title'    => get_the_title( $book ),
			'url'      => (string) get_permalink( $book ),
			'cover'    => self::cover_url( (int) $book->ID ),
			'chapters' => $playable,
			'resume'   => self::resume( (int) $book->ID ),
		);

		ob_start();

		require plugin_dir_path( PLUGIN_FILE ) . 'templates/player.php';

		return (string) ob_get_clean();
	}

	/**
	 * The book's cover, small, for a guest's Continue listening list.
	 *
	 * The account list reads the cover from the book. A guest's list is built
	 * by their own browser, which has no way to ask, so the player carries it.
	 *
	 * @param int $book_id The book.
	 */
	private static function cover_url( int $book_id ): string {
		$cover = (int) get_post_meta( $book_id, 'volumina_cover_id', true );

		if ( $cover <= 0 ) {
			return '';
		}

		$image = wp_get_attachment_image_url( $cover, 'thumbnail' );

		return is_string( $image ) ? $image : '';
	}

	/**
	 * The chapters that actually have audio behind them.
	 *
	 * A chapter with no file is still a chapter — it stays in the list on the
	 * page — but it is not something the player can move to, and offering it
	 * as one would be a button that does nothing.
	 *
	 * @param array<int, WP_Post> $chapters Chapters in order.
	 * @return array<int, array<string, mixed>>
	 */
	public static function playable( array $chapters ): array {
		$out = array();

		foreach ( $chapters as $chapter ) {
			$attachment_id = (int) get_post_meta( $chapter->ID, 'volumina_attachment_id', true );

			if ( $attachment_id <= 0 ) {
				continue;
			}

			$mime = get_post_mime_type( $attachment_id );

			if ( ! is_string( $mime ) || ! str_starts_with( $mime, 'audio/' ) ) {
				continue;
			}

			$duration = (int) get_post_meta( $chapter->ID, 'volumina_duration', true );

			$out[] = array(
				'id'       => (int) $chapter->ID,
				'title'    => get_the_title( $chapter ),
				'duration' => $duration,
				'readable' => $duration > 0 ? Duration::format( $duration ) : '',
				'mime'     => $mime,
				'url'      => Stream::url( (int) $chapter->ID ),
			);
		}

		return $out;
	}

	/**
	 * Where this listener had got to, for the page to start from.
	 *
	 * Only for people who are signed in. Everyone else is restored by their
	 * own browser once the script runs.
	 *
	 * @param int $book_id The book.
	 * @return array{chapter: int, position: int}
	 */
	private static function resume( int $book_id ): array {
		$empty = array(
			'chapter'  => 0,
			'position' => 0,
		);

		if ( ! is_user_logged_in() ) {
			return $empty;
		}

		$progress = Progress::get( get_current_user_id(), $book_id );

		if ( null === $progress ) {
			return $empty;
		}

		return array(
			'chapter'  => $progress['chapter_id'],
			'position' => $progress['position_seconds'],
		);
	}

	/**
	 * The playback speeds on offer.
	 *
	 * @return array<int, string>
	 */
	public static function speeds(): array {
		return array( '0.75', '1', '1.25', '1.5', '1.75', '2' );
	}

	/**
	 * The sleep timer's choices, in the order they are offered.
	 *
	 * Built here rather than in the script so that `_n()` can do its job: the
	 * number is known at this point, and plural rules are not something to
	 * reimplement in JavaScript.
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	public static function sleep_options(): array {
		$options = array(
			array(
				'value' => 'off',
				'label' => __( 'Off', 'volumina' ),
			),
		);

		foreach ( array( 5, 10, 15, 30, 45, 60 ) as $minutes ) {
			$options[] = array(
				'value' => (string) $minutes,
				'label' => sprintf(
					/* translators: %s: a number of minutes. */
					_n( '%s minute', '%s minutes', $minutes, 'volumina' ),
					number_format_i18n( $minutes )
				),
			);
		}

		$options[] = array(
			'value' => 'chapter',
			'label' => __( 'End of chapter', 'volumina' ),
		);

		return $options;
	}

	/**
	 * Whether a chapter is the one the listener should start from.
	 *
	 * @param array<string, mixed> $chapter A playable chapter.
	 * @param int                  $resume  Chapter ID to resume, or zero.
	 * @param int                  $index   Position in the list.
	 */
	public static function is_current( array $chapter, int $resume, int $index ): bool {
		if ( $resume > 0 ) {
			return (int) $chapter['id'] === $resume;
		}

		return 0 === $index;
	}
}
