<?php defined( 'ABSPATH' ) || exit;
$listing_id   = $listing->ID;
$meta_keys    = array( '_mauriel_phone', '_mauriel_email', '_mauriel_website', '_mauriel_address', '_mauriel_city', '_mauriel_state', '_mauriel_zip', '_mauriel_tagline', '_mauriel_description', '_mauriel_service_area', '_mauriel_place_id', '_mauriel_booking_url', '_mauriel_seo_title', '_mauriel_seo_desc' );
$meta = array();
foreach ( $meta_keys as $key ) { $meta[ str_replace('_mauriel_', '', $key) ] = get_post_meta( $listing_id, $key, true ); }
$social_raw  = get_post_meta( $listing_id, '_mauriel_social_links', true );
$social      = $social_raw ? json_decode( $social_raw, true ) : array();
$categories  = get_the_terms( $listing_id, 'mauriel_category' );
$selected_cats = $categories ? array_map( function($t){ return $t->term_id; }, $categories ) : array();
$ai_enabled  = Mauriel_AI::is_enabled();
?>
<div class="mauriel-tab-panel mauriel-tab-listing">
	<h2><?php esc_html_e( 'Edit My Listing', 'mauriel-service-directory' ); ?></h2>
	<form method="post" action="" class="mauriel-dashboard-form">
		<?php wp_nonce_field( 'mauriel_save_listing' ); ?>
		<input type="hidden" name="mauriel_dashboard_action" value="save_listing">
		<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">

		<div class="mauriel-form-section">
			<h3><?php esc_html_e( 'Basic Info', 'mauriel-service-directory' ); ?></h3>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Business Name', 'mauriel-service-directory' ); ?></label>
				<input type="text" name="business_name" class="mauriel-form-control" value="<?php echo esc_attr( $listing->post_title ); ?>" required>
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Tagline', 'mauriel-service-directory' ); ?></label>
				<input type="text" name="tagline" class="mauriel-form-control" value="<?php echo esc_attr( $meta['tagline'] ); ?>" placeholder="<?php esc_attr_e( 'A short, catchy tagline', 'mauriel-service-directory' ); ?>">
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Category', 'mauriel-service-directory' ); ?></label>
				<?php wp_dropdown_categories( array( 'taxonomy' => 'mauriel_category', 'name' => 'category_ids[]', 'orderby' => 'name', 'selected' => $selected_cats[0] ?? 0, 'show_option_none' => __( '— Select Category —', 'mauriel-service-directory' ), 'hide_empty' => false, 'class' => 'mauriel-form-control' ) ); ?>
			</div>
			<div class="mauriel-form-group">
				<label>
					<?php esc_html_e( 'Description', 'mauriel-service-directory' ); ?>
					<?php if ( $ai_enabled ) : ?>
					<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-outline mauriel-ai-gen-desc"
					        data-listing-id="<?php echo esc_attr( $listing_id ); ?>">
						&#129302; <?php esc_html_e( 'Generate with AI', 'mauriel-service-directory' ); ?>
					</button>
					<?php endif; ?>
				</label>
				<textarea name="description" class="mauriel-form-control" rows="8" id="mauriel-description"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
			</div>
		</div>

		<div class="mauriel-form-section">
			<h3><?php esc_html_e( 'Contact & Location', 'mauriel-service-directory' ); ?></h3>
			<div class="mauriel-form-row mauriel-form-row-2">
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Phone', 'mauriel-service-directory' ); ?></label>
					<input type="tel" name="phone" class="mauriel-form-control" value="<?php echo esc_attr( $meta['phone'] ); ?>">
				</div>
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Email', 'mauriel-service-directory' ); ?></label>
					<input type="email" name="email" class="mauriel-form-control" value="<?php echo esc_attr( $meta['email'] ); ?>">
				</div>
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Website', 'mauriel-service-directory' ); ?></label>
				<input type="url" name="website" class="mauriel-form-control" value="<?php echo esc_url( $meta['website'] ); ?>">
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Address', 'mauriel-service-directory' ); ?></label>
				<input type="text" name="address" class="mauriel-form-control" value="<?php echo esc_attr( $meta['address'] ); ?>">
			</div>
			<div class="mauriel-form-row mauriel-form-row-3">
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'City', 'mauriel-service-directory' ); ?></label>
					<input type="text" name="city" class="mauriel-form-control" value="<?php echo esc_attr( $meta['city'] ); ?>">
				</div>
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'State', 'mauriel-service-directory' ); ?></label>
					<input type="text" name="state" class="mauriel-form-control" value="<?php echo esc_attr( $meta['state'] ); ?>" maxlength="2">
				</div>
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'ZIP', 'mauriel-service-directory' ); ?></label>
					<input type="text" name="zip" class="mauriel-form-control" value="<?php echo esc_attr( $meta['zip'] ); ?>">
				</div>
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Service Area', 'mauriel-service-directory' ); ?></label>
				<textarea name="service_area" class="mauriel-form-control" rows="2"><?php echo esc_textarea( $meta['service_area'] ); ?></textarea>
			</div>
		</div>

		<div class="mauriel-form-section">
			<h3><?php esc_html_e( 'Social Media', 'mauriel-service-directory' ); ?></h3>
			<?php foreach ( array( 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'Twitter / X', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'youtube' => 'YouTube' ) as $key => $label ) : ?>
			<div class="mauriel-form-group">
				<label><?php echo esc_html( $label ); ?></label>
				<input type="url" name="social_<?php echo esc_attr( $key ); ?>" class="mauriel-form-control" value="<?php echo esc_url( $social[ $key ] ?? '' ); ?>">
			</div>
			<?php endforeach; ?>
		</div>

		<div class="mauriel-form-section">
			<h3><?php esc_html_e( 'Integrations', 'mauriel-service-directory' ); ?></h3>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Google Place ID', 'mauriel-service-directory' ); ?></label>
				<input type="text" name="place_id" class="mauriel-form-control" value="<?php echo esc_attr( $meta['place_id'] ); ?>">
				<p class="description"><?php esc_html_e( 'Used to import Google Reviews.', 'mauriel-service-directory' ); ?></p>
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Booking URL', 'mauriel-service-directory' ); ?></label>
				<input type="url" name="booking_url" class="mauriel-form-control" value="<?php echo esc_url( $meta['booking_url'] ); ?>">
				<p class="description"><?php esc_html_e( 'Calendly, Square, or other booking link.', 'mauriel-service-directory' ); ?></p>
			</div>
		</div>

		<div class="mauriel-form-section">
			<h3><?php esc_html_e( 'SEO', 'mauriel-service-directory' ); ?></h3>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'SEO Title', 'mauriel-service-directory' ); ?></label>
				<input type="text" name="seo_title" class="mauriel-form-control" value="<?php echo esc_attr( $meta['seo_title'] ); ?>">
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Meta Description', 'mauriel-service-directory' ); ?></label>
				<textarea name="seo_desc" class="mauriel-form-control" rows="3" maxlength="160"><?php echo esc_textarea( $meta['seo_desc'] ); ?></textarea>
			</div>
		</div>

		<div class="mauriel-form-actions">
			<button type="submit" class="mauriel-btn mauriel-btn-primary"><?php esc_html_e( 'Save Changes', 'mauriel-service-directory' ); ?></button>
		</div>
	</form>
</div>
