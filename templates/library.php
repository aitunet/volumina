<?php
/**
 * The library: the books in rows.
 *
 * Rendered by `Blocks\LibraryBlock::render()`, which sets:
 *
 * @var array<int, array{book: WP_Post, title: string, url: string, cover: int, lines: array<int, string>}> $items  The rows.
 * @var bool                                                                                               $covers Whether covers were asked for.
 * @var array{class: string, style: string}                                                                $wrap   The wrapper's presentation.
 *
 * @package TUNET\Volumina
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<div
	<?php
	// Built by core from the block supports; it is attributes, not content.
	echo get_block_wrapper_attributes( array( 'class' => 'volumina-library ' . $wrap['class'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
	<?php if ( '' !== $wrap['style'] ) : ?>
		style="<?php echo esc_attr( $wrap['style'] ); ?>"
	<?php endif; ?>
>
	<?php if ( array() === $items ) : ?>
		<p class="volumina-library-empty">
			<?php esc_html_e( 'No audiobooks published yet. Published ones will be listed here.', 'volumina' ); ?>
		</p>
	<?php else : ?>
		<ul class="volumina-library-list">
			<?php foreach ( $items as $volumina_item ) : ?>
				<li class="volumina-library-item">
					<?php if ( $covers && $volumina_item['cover'] > 0 ) : ?>
						<a class="volumina-library-cover" href="<?php echo esc_url( $volumina_item['url'] ); ?>" tabindex="-1" aria-hidden="true">
							<?php
							// The title beside it is the link that speaks; this one is for the finger.
							echo wp_kses_post(
								wp_get_attachment_image( $volumina_item['cover'], 'thumbnail', false, array( 'alt' => '' ) )
							);
							?>
						</a>
					<?php endif; ?>

					<span class="volumina-library-text">
						<a class="volumina-library-title" href="<?php echo esc_url( $volumina_item['url'] ); ?>">
							<?php echo esc_html( $volumina_item['title'] ); ?>
						</a>

						<?php if ( array() !== $volumina_item['lines'] ) : ?>
							<span class="volumina-library-lines">
								<?php echo esc_html( implode( ' · ', array_map( 'strval', $volumina_item['lines'] ) ) ); ?>
							</span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
