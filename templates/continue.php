<?php
/**
 * The Continue listening list.
 *
 * A signed-in listener arrives with their list already in `$items`. A guest
 * arrives with none, and the script fills the same markup from their own
 * browser — which is why the item markup is described once here and mirrored
 * in `assets/js/continue.js` rather than being built twice from scratch.
 *
 * @package TUNET\Volumina
 *
 * @var array<int, array{book: WP_Post, chapter: string, position: string}> $items  What to show.
 * @var bool                                                                $guest  Whether the script will fill this in.
 * @var bool                                                                $covers Whether to show cover images.
 * @var int                                                                 $count  How many books at most.
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<div
	<?php
	// Built by core from the block supports; it is attributes, not content.
	echo get_block_wrapper_attributes( array( 'class' => 'volumina-continue' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
	data-volumina-continue="<?php echo esc_attr( $guest ? 'guest' : 'account' ); ?>"
	data-volumina-count="<?php echo esc_attr( (string) $count ); ?>"
	data-volumina-covers="<?php echo esc_attr( $covers ? '1' : '0' ); ?>"
>
	<ul class="volumina-continue-list" data-volumina-continue-list>
		<?php foreach ( $items as $volumina_item ) : ?>
			<?php
			$volumina_book  = $volumina_item['book'];
			$volumina_cover = $covers ? (int) get_post_meta( $volumina_book->ID, 'volumina_cover_id', true ) : 0;
			?>
			<li class="volumina-continue-item">
				<?php if ( $volumina_cover > 0 ) : ?>
					<span class="volumina-continue-cover">
						<?php
						echo wp_kses_post(
							wp_get_attachment_image( $volumina_cover, 'thumbnail', false, array( 'alt' => '' ) )
						);
						?>
					</span>
				<?php endif; ?>

				<span class="volumina-continue-text">
					<a class="volumina-continue-title" href="<?php echo esc_url( (string) get_permalink( $volumina_book ) ); ?>">
						<?php echo esc_html( get_the_title( $volumina_book ) ); ?>
					</a>

					<span class="volumina-continue-place">
						<?php
						if ( '' !== $volumina_item['chapter'] ) {
							printf(
								/* translators: 1: chapter title, 2: a position in that chapter, as h:mm:ss. */
								esc_html__( 'In %1$s at %2$s', 'volumina' ),
								esc_html( $volumina_item['chapter'] ),
								esc_html( $volumina_item['position'] )
							);
						} else {
							printf(
								/* translators: %s: a position, as h:mm:ss. */
								esc_html__( 'at %s', 'volumina' ),
								esc_html( $volumina_item['position'] )
							);
						}
						?>
					</span>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( ! $guest && array() === $items ) : ?>
		<p class="volumina-continue-empty">
			<?php esc_html_e( 'Nothing started yet. Books you begin will appear here.', 'volumina' ); ?>
		</p>
	<?php endif; ?>
</div>
