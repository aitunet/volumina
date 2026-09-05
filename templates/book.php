<?php
/**
 * The audiobook: cover, details, player and chapters.
 *
 * Shared by the single book page and by the Audiobook block, so the two cannot
 * drift apart. Internal to the plugin. Not a theme template and not an override
 * point: the public extension API is written in S4.
 *
 * @package TUNET\Volumina
 *
 * @var WP_Post               $book     The book being shown.
 * @var array<int, WP_Post>   $chapters Its chapters, in order.
 * @var array<string, string> $details  Labelled details, already formatted.
 * @var string                $player   The player markup, or an empty string.
 * @var array<string, bool>   $show     Which parts to render.
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$volumina_cover = $show['cover'] ? (int) get_post_meta( $book->ID, 'volumina_cover_id', true ) : 0;

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

	<?php if ( $show['details'] && array() !== $details ) : ?>
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
	if ( $show['player'] ) {
		// Built by Player::render(), which escaped it. It is markup by intent.
		echo $player; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

	<?php if ( $show['chapters'] ) : ?>
		<?php if ( $show['heading'] ) : ?>
			<h2 class="volumina-book-heading"><?php esc_html_e( 'Chapters', 'volumina' ); ?></h2>
		<?php endif; ?>

		<?php
		$volumina_durations = true;
		require __DIR__ . '/chapters.php';
		?>
	<?php endif; ?>

</div>
