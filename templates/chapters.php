<?php
/**
 * A book's chapter list.
 *
 * Shared by the single book page and by the Chapter list block, so the two
 * cannot drift apart. Internal to the plugin; not an override point.
 *
 * Chapter names are plain text here, not buttons. A button that plays a
 * chapter is only honest when there is a player on the page to play it, and
 * this partial cannot know that — the player upgrades the names it can
 * actually reach once it has started. Without JavaScript the list stays a
 * readable list rather than becoming a row of buttons that do nothing.
 *
 * @package TUNET\Volumina
 *
 * @var array<int, WP_Post> $chapters           The chapters, in order.
 * @var bool                $volumina_durations Whether to show running times.
 */

declare( strict_types = 1 );

use TUNET\Volumina\Support\Duration;

defined( 'ABSPATH' ) || exit;

?>
<?php if ( array() === $chapters ) : ?>

	<p><?php esc_html_e( 'This audiobook has no chapters yet.', 'volumina' ); ?></p>

<?php else : ?>

	<ol class="volumina-chapter-list">
		<?php
		foreach ( $chapters as $volumina_chapter ) :
			$volumina_seconds = (int) get_post_meta( $volumina_chapter->ID, 'volumina_duration', true );
			?>
			<li class="volumina-chapter-item">
				<span class="volumina-chapter-row">
					<span
						class="volumina-chapter-name"
						data-volumina-chapter="<?php echo esc_attr( (string) (int) $volumina_chapter->ID ); ?>"
					><?php echo esc_html( get_the_title( $volumina_chapter ) ); ?></span>
					<?php if ( $volumina_durations && $volumina_seconds > 0 ) : ?>
						<time
							class="volumina-chapter-time"
							datetime="<?php echo esc_attr( Duration::iso8601( $volumina_seconds ) ); ?>"
						><?php echo esc_html( Duration::format( $volumina_seconds ) ); ?></time>
					<?php endif; ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ol>

<?php endif; ?>
