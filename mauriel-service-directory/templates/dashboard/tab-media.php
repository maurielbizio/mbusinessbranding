<?php defined( 'ABSPATH' ) || exit;
$listing_id  = $listing->ID;
$logo_id     = (int) get_post_meta( $listing_id, '_mauriel_logo_id', true );
$cover_id    = (int) get_post_meta( $listing_id, '_mauriel_cover_id', true );
$gallery_raw = get_post_meta( $listing_id, '_mauriel_gallery_ids', true );
$gallery_ids = $gallery_raw ? json_decode( $gallery_raw, true ) : array();
$logo_url    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : MAURIEL_URL . 'assets/images/placeholder-listing.png';
$cover_url   = $cover_id ? wp_get_attachment_image_url( $cover_id, 'medium' ) : '';
$video_raw   = get_post_meta( $listing_id, '_mauriel_video_embeds', true );
$videos      = $video_raw ? json_decode( $video_raw, true ) : array();
?>
<div class="mauriel-tab-panel mauriel-tab-media">
	<h2><?php esc_html_e( 'Photos & Media', 'mauriel-service-directory' ); ?></h2>

	<div class="mauriel-media-section">
		<h3><?php esc_html_e( 'Business Logo', 'mauriel-service-directory' ); ?></h3>
		<div class="mauriel-media-upload-area">
			<img src="<?php echo esc_url( $logo_url ); ?>" id="mauriel-logo-preview" class="mauriel-logo-preview" alt="<?php esc_attr_e( 'Logo', 'mauriel-service-directory' ); ?>">
			<div>
				<input type="hidden" id="mauriel-logo-id" name="logo_id" value="<?php echo esc_attr( $logo_id ); ?>">
				<button type="button" class="mauriel-btn mauriel-btn-outline mauriel-media-upload-btn"
				        data-target="logo" data-preview="mauriel-logo-preview" data-input="mauriel-logo-id"
				        data-title="<?php esc_attr_e( 'Select Logo', 'mauriel-service-directory' ); ?>">
					<?php esc_html_e( 'Change Logo', 'mauriel-service-directory' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Recommended: 400×400px, PNG or JPG.', 'mauriel-service-directory' ); ?></p>
			</div>
		</div>
	</div>

	<div class="mauriel-media-section">
		<h3><?php esc_html_e( 'Cover Image', 'mauriel-service-directory' ); ?></h3>
		<div class="mauriel-media-upload-area">
			<?php if ( $cover_url ) : ?>
			<img src="<?php echo esc_url( $cover_url ); ?>" id="mauriel-cover-preview" class="mauriel-cover-preview" alt="<?php esc_attr_e( 'Cover', 'mauriel-service-directory' ); ?>">
			<?php else : ?>
			<div class="mauriel-cover-placeholder" id="mauriel-cover-preview"><?php esc_html_e( 'No cover image', 'mauriel-service-directory' ); ?></div>
			<?php endif; ?>
			<div>
				<input type="hidden" id="mauriel-cover-id" name="cover_id" value="<?php echo esc_attr( $cover_id ); ?>">
				<button type="button" class="mauriel-btn mauriel-btn-outline mauriel-media-upload-btn"
				        data-target="cover" data-preview="mauriel-cover-preview" data-input="mauriel-cover-id"
				        data-title="<?php esc_attr_e( 'Select Cover Image', 'mauriel-service-directory' ); ?>">
					<?php esc_html_e( 'Change Cover', 'mauriel-service-directory' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Recommended: 1200×400px.', 'mauriel-service-directory' ); ?></p>
			</div>
		</div>
	</div>

	<div class="mauriel-media-section">
		<h3><?php esc_html_e( 'Photo Gallery', 'mauriel-service-directory' ); ?></h3>
		<div class="mauriel-gallery-manage" id="mauriel-gallery-manage">
			<?php foreach ( $gallery_ids as $att_id ) :
				$att_id  = absint( $att_id );
				$img_url = wp_get_attachment_image_url( $att_id, 'thumbnail' );
				if ( ! $img_url ) continue;
			?>
			<div class="mauriel-gallery-thumb" data-attachment-id="<?php echo esc_attr( $att_id ); ?>">
				<img src="<?php echo esc_url( $img_url ); ?>" alt="">
				<button type="button" class="mauriel-remove-gallery-img" data-attachment-id="<?php echo esc_attr( $att_id ); ?>" data-listing-id="<?php echo esc_attr( $listing_id ); ?>" aria-label="<?php esc_attr_e( 'Remove', 'mauriel-service-directory' ); ?>">&times;</button>
			</div>
			<?php endforeach; ?>
		</div>
		<button type="button" class="mauriel-btn mauriel-btn-outline" id="mauriel-add-gallery">
			+ <?php esc_html_e( 'Add Photos', 'mauriel-service-directory' ); ?>
		</button>
		<input type="hidden" id="mauriel-gallery-ids" value="<?php echo esc_attr( wp_json_encode( $gallery_ids ) ); ?>">
	</div>

	<div class="mauriel-media-section">
		<h3><?php esc_html_e( 'Video Embeds', 'mauriel-service-directory' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Add YouTube or Vimeo video URLs.', 'mauriel-service-directory' ); ?></p>
		<div class="mauriel-videos-list" id="mauriel-videos-list">
			<?php foreach ( $videos as $video_url ) : ?>
			<div class="mauriel-video-row">
				<input type="text" class="mauriel-form-control mauriel-video-url" value="<?php echo esc_url( $video_url ); ?>" readonly>
				<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-danger mauriel-remove-video">&times;</button>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="mauriel-add-video-row">
			<input type="url" class="mauriel-form-control" id="mauriel-new-video-url" placeholder="https://www.youtube.com/watch?v=...">
			<button type="button" class="mauriel-btn mauriel-btn-outline" id="mauriel-add-video">
				+ <?php esc_html_e( 'Add Video', 'mauriel-service-directory' ); ?>
			</button>
		</div>
	</div>

	<div class="mauriel-notice mauriel-media-success" style="display:none;"></div>
	<div class="mauriel-form-actions">
		<button type="button" class="mauriel-btn mauriel-btn-primary" id="mauriel-save-media"
		        data-listing-id="<?php echo esc_attr( $listing_id ); ?>">
			<?php esc_html_e( 'Save Media', 'mauriel-service-directory' ); ?>
		</button>
	</div>
</div>
