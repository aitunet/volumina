<?php
/**
 * The audiobook player.
 *
 * Internal to the plugin, like `book.php`. Not a theme template and not an
 * override point; the public extension API is written in S4.
 *
 * @package TUNET\Volumina
 *
 * @var WP_Post              $book The book being played.
 * @var array<string, mixed> $data Book ID, playable chapters and the resume point.
 */

declare( strict_types = 1 );

use TUNET\Volumina\Player\Player;

defined( 'ABSPATH' ) || exit;

/**
 * The chapters this player can move between.
 *
 * @var array<int, array<string, mixed>> $volumina_chapters
 */
$volumina_chapters = $data['chapters'];
$volumina_resume   = (int) $data['resume']['chapter'];
$volumina_first    = $volumina_chapters[0];

foreach ( $volumina_chapters as $volumina_index => $volumina_candidate ) {
	if ( Player::is_current( $volumina_candidate, $volumina_resume, (int) $volumina_index ) ) {
		$volumina_first = $volumina_candidate;
		break;
	}
}

?>
<section
	class="volumina-player"
	data-volumina-player
	data-book="<?php echo esc_attr( (string) (int) $data['book'] ); ?>"
	aria-label="<?php echo esc_attr__( 'Audiobook player', 'volumina' ); ?>"
>
	<script type="application/json" data-volumina-chapters>
		<?php
		// JSON, not markup. The HEX flags mean a chapter title containing
		// `</script>` closes nothing it should not. Slashes stay slashes: a
		// URL written as `http:\/\/host` is invisible to anything that rewrites
		// the site's address in the page — Local's Live Link, a proxy, a
		// search-and-replace on migration — and the audio then points at a
		// host the listener cannot reach.
		echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES );
		?>
	</script>

	<p class="volumina-player-now">
		<span class="volumina-player-eyebrow"><?php esc_html_e( 'Now playing', 'volumina' ); ?></span>
		<span class="volumina-player-chapter" data-volumina-now>
			<?php echo esc_html( (string) $volumina_first['title'] ); ?>
		</span>
	</p>

	<audio
		class="volumina-player-audio"
		data-volumina-audio
		preload="metadata"
		src="<?php echo esc_url( (string) $volumina_first['url'] ); ?>"
		controls
	></audio>

	<div class="volumina-player-transport" data-volumina-transport hidden>
		<button type="button" class="volumina-button" data-volumina-action="previous">
			<svg class="volumina-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6h2v12H6zM18 6l-9 6 9 6z"/></svg>
			<span class="volumina-sr"><?php esc_html_e( 'Previous chapter', 'volumina' ); ?></span>
		</button>

		<button type="button" class="volumina-button" data-volumina-action="back">
			<svg class="volumina-icon volumina-icon-skip" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/><text x="12" y="16" text-anchor="middle"><?php echo esc_html( (string) Player::SKIP_BACK ); ?></text></svg>
			<span class="volumina-sr">
				<?php
				printf(
					/* translators: %d: a number of seconds. */
					esc_html( _n( 'Back %d second', 'Back %d seconds', Player::SKIP_BACK, 'volumina' ) ),
					(int) Player::SKIP_BACK
				);
				?>
			</span>
		</button>

		<button
			type="button"
			class="volumina-button volumina-button-primary"
			data-volumina-action="toggle"
		>
			<span class="volumina-toggle-icon" aria-hidden="true" data-volumina-toggle-icon>
				<svg class="volumina-icon" viewBox="0 0 24 24" focusable="false" data-volumina-icon="play"><path d="M8 5v14l11-7z"/></svg>
				<svg class="volumina-icon" viewBox="0 0 24 24" focusable="false" data-volumina-icon="pause" hidden><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>
			</span>
			<span class="volumina-sr" data-volumina-toggle-label><?php esc_html_e( 'Play', 'volumina' ); ?></span>
		</button>

		<button type="button" class="volumina-button" data-volumina-action="forward">
			<svg class="volumina-icon volumina-icon-skip" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5V1l5 5-5 5V7c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6h2c0 4.42-3.58 8-8 8s-8-3.58-8-8 3.58-8 8-8z"/><text x="12" y="16" text-anchor="middle"><?php echo esc_html( (string) Player::SKIP_FORWARD ); ?></text></svg>
			<span class="volumina-sr">
				<?php
				printf(
					/* translators: %d: a number of seconds. */
					esc_html( _n( 'Forward %d second', 'Forward %d seconds', Player::SKIP_FORWARD, 'volumina' ) ),
					(int) Player::SKIP_FORWARD
				);
				?>
			</span>
		</button>

		<button type="button" class="volumina-button" data-volumina-action="next">
			<svg class="volumina-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16 6h2v12h-2zM6 6l9 6-9 6z"/></svg>
			<span class="volumina-sr"><?php esc_html_e( 'Next chapter', 'volumina' ); ?></span>
		</button>
	</div>

	<div class="volumina-player-seek" data-volumina-seek-row hidden>
		<label class="volumina-sr" for="volumina-seek-<?php echo esc_attr( (string) (int) $data['book'] ); ?>">
			<?php esc_html_e( 'Position in this chapter', 'volumina' ); ?>
		</label>
		<input
			type="range"
			id="volumina-seek-<?php echo esc_attr( (string) (int) $data['book'] ); ?>"
			class="volumina-player-range"
			data-volumina-seek
			min="0"
			max="<?php echo esc_attr( (string) max( 1, (int) $volumina_first['duration'] ) ); ?>"
			step="1"
			value="0"
		/>
		<p class="volumina-player-times">
			<span data-volumina-elapsed>0:00</span>
			<span data-volumina-total><?php echo esc_html( (string) $volumina_first['readable'] ); ?></span>
		</p>
	</div>

	<div class="volumina-player-options" data-volumina-options hidden>
		<p class="volumina-player-option">
			<label for="volumina-speed-<?php echo esc_attr( (string) (int) $data['book'] ); ?>">
				<?php esc_html_e( 'Speed', 'volumina' ); ?>
			</label>
			<select
				id="volumina-speed-<?php echo esc_attr( (string) (int) $data['book'] ); ?>"
				data-volumina-speed
			>
				<?php foreach ( Player::speeds() as $volumina_speed ) : ?>
					<option value="<?php echo esc_attr( $volumina_speed ); ?>" <?php selected( '1', $volumina_speed ); ?>>
						<?php
						printf(
							/* translators: %s: a playback speed, such as 1.5. */
							esc_html__( '%s×', 'volumina' ),
							esc_html( $volumina_speed )
						);
						?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p class="volumina-player-option">
			<label for="volumina-sleep-<?php echo esc_attr( (string) (int) $data['book'] ); ?>">
				<?php esc_html_e( 'Sleep timer', 'volumina' ); ?>
			</label>
			<select
				id="volumina-sleep-<?php echo esc_attr( (string) (int) $data['book'] ); ?>"
				data-volumina-sleep
			>
				<?php foreach ( Player::sleep_options() as $volumina_option ) : ?>
					<option value="<?php echo esc_attr( $volumina_option['value'] ); ?>">
						<?php echo esc_html( $volumina_option['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p class="volumina-player-option volumina-player-volume" data-volumina-volume-row>
			<button type="button" class="volumina-button" data-volumina-action="mute" aria-pressed="false">
				<span aria-hidden="true" data-volumina-mute-icon>
					<svg class="volumina-icon" viewBox="0 0 24 24" focusable="false" data-volumina-icon="sound"><path d="M4 9v6h4l5 4V5L8 9H4zm11.5 3a3.5 3.5 0 0 0-2-3.15v6.3a3.5 3.5 0 0 0 2-3.15zM13.5 4.2v2.1a6 6 0 0 1 0 11.4v2.1a8 8 0 0 0 0-15.6z"/></svg>
					<svg class="volumina-icon" viewBox="0 0 24 24" focusable="false" data-volumina-icon="muted" hidden><path d="M4 9v6h4l5 4V5L8 9H4zm12.6 3 2.7-2.7-1.4-1.4-2.7 2.7-2.7-2.7-1.4 1.4 2.7 2.7-2.7 2.7 1.4 1.4 2.7-2.7 2.7 2.7 1.4-1.4z"/></svg>
				</span>
				<span class="volumina-sr" data-volumina-mute-label><?php esc_html_e( 'Mute', 'volumina' ); ?></span>
			</button>
			<label class="volumina-sr" for="volumina-volume-<?php echo esc_attr( (string) (int) $data['book'] ); ?>">
				<?php esc_html_e( 'Volume', 'volumina' ); ?>
			</label>
			<input
				type="range"
				id="volumina-volume-<?php echo esc_attr( (string) (int) $data['book'] ); ?>"
				class="volumina-player-range volumina-player-volume-range"
				data-volumina-volume
				min="0"
				max="100"
				step="1"
				value="100"
			/>
		</p>
	</div>

	<p class="volumina-player-notice" data-volumina-notice hidden></p>
	<p class="volumina-player-status" role="status" aria-live="polite"></p>
</section>
