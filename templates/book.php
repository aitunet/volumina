<?php
/**
 * The audiobook on its single page.
 *
 * Internal to the plugin. Not a theme template and not an override point: the
 * public extension API is written in S4, and the blocks in S3 are the real
 * presentation layer. Nothing here is a promise.
 *
 * @package TUNET\Volumina
 *
 * @var WP_Post             $book     The book being shown.
 * @var array<int, WP_Post> $chapters Its chapters, in order.
 * @var array<string, string> $details  Labelled details, already formatted.
 * @var string                $player   The player markup, or an empty string.
 * @var array<int, int>       $playable IDs of chapters the player can reach.
 */

declare( strict_types = 1 );

use TUNET\Volumina\Support\Duration;

defined( 'ABSPATH' ) || exit;

$volumina_cover = (int) get_post_meta( $book->ID, 'volumina_cover_id', true );

?>
<div class="volumina-book">

	<?php if ( $volumina_cover > 0 ) : ?>
		<div class="volumina-book-cover">
			<?php
			echo wp_kses_post(
				wp_get_attachment_image(
					$volumina_cover,
					'medium',
					false,
					array(
						/* translators: %s: book title. */
						'alt' => sprintf( __( 'Cover of %s', 'volumina' ), get_the_title( $book ) ),
					)
				)
			);
			?>
		</div>
	<?php endif; ?>

	<?php if ( array() !== $details ) : ?>
		<dl class="volumina-book-details">
			<?php foreach ( $details as $volumina_label => $volumina_value ) : ?>
				<div class="volumina-book-detail">
					<dt><?php echo esc_html( $volumina_label ); ?></dt>
					<dd><?php echo esc_html( $volumina_value ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>

	<?php
	// Built by Player::render(), which escaped it. It is markup by intent.
	echo $player; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>

	<h2 class="volumina-book-heading"><?php esc_html_e( 'Chapters', 'volumina' ); ?></h2>

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
						<span class="volumina-chapter-name">
							<?php if ( in_array( (int) $volumina_chapter->ID, $playable, true ) ) : ?>
								<button
									type="button"
									class="volumina-chapter-play"
									data-volumina-play="<?php echo esc_attr( (string) (int) $volumina_chapter->ID ); ?>"
								><?php echo esc_html( get_the_title( $volumina_chapter ) ); ?></button>
							<?php else : ?>
								<?php echo esc_html( get_the_title( $volumina_chapter ) ); ?>
							<?php endif; ?>
						</span>
						<?php if ( $volumina_seconds > 0 ) : ?>
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

</div>
