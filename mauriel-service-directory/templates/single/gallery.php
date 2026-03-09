<?php defined( 'ABSPATH' ) || exit;
/**
 * Template: Image Gallery
 * Variables: $gallery_urls (array), $listing_id
 */
if ( empty( $gallery_urls ) ) return;
?>
<div class="mauriel-gallery-wrap">
	<div class="mauriel-gallery-grid" id="mauriel-gallery-<?php echo esc_attr( $listing_id ); ?>">
		<?php foreach ( $gallery_urls as $index => $img_data ) :
			$url      = is_array( $img_data ) ? ( $img_data['url'] ?? '' ) : $img_data;
			$full_url = is_array( $img_data ) ? ( $img_data['full'] ?? $url ) : $url;
			if ( empty( $url ) ) continue;
		?>
		<div class="mauriel-gallery-item <?php echo 0 === $index ? 'mauriel-gallery-main' : ''; ?>">
			<a href="<?php echo esc_url( $full_url ); ?>"
			   class="mauriel-lightbox-trigger"
			   data-gallery="listing-<?php echo esc_attr( $listing_id ); ?>"
			   data-index="<?php echo esc_attr( $index ); ?>">
				<img src="<?php echo esc_url( $url ); ?>"
				     alt="<?php echo esc_attr( get_the_title( $listing_id ) . ' photo ' . ( $index + 1 ) ); ?>"
				     loading="lazy">
				<div class="mauriel-gallery-overlay"><span>&#128269;</span></div>
			</a>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Lightbox -->
	<div class="mauriel-lightbox" id="mauriel-lightbox" style="display:none;" role="dialog" aria-modal="true">
		<button class="mauriel-lightbox-close" aria-label="<?php esc_attr_e( 'Close', 'mauriel-service-directory' ); ?>">&times;</button>
		<button class="mauriel-lightbox-prev" aria-label="<?php esc_attr_e( 'Previous', 'mauriel-service-directory' ); ?>">&#10094;</button>
		<button class="mauriel-lightbox-next" aria-label="<?php esc_attr_e( 'Next', 'mauriel-service-directory' ); ?>">&#10095;</button>
		<div class="mauriel-lightbox-content">
			<img src="" alt="" id="mauriel-lightbox-img">
		</div>
	</div>
</div>
