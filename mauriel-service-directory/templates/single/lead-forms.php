<?php defined( 'ABSPATH' ) || exit;
/**
 * Template: Lead Forms Sidebar
 * Variables: $listing_id, $listing_data (array of meta)
 */
$phone   = esc_attr( $listing_data['phone'] ?? get_post_meta( $listing_id, '_mauriel_phone', true ) );
$email   = esc_attr( $listing_data['email'] ?? get_post_meta( $listing_id, '_mauriel_email', true ) );
$website = esc_url( $listing_data['website'] ?? get_post_meta( $listing_id, '_mauriel_website', true ) );
?>
<div class="mauriel-lead-forms">

	<?php if ( $phone ) : ?>
	<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"
	   class="mauriel-btn mauriel-btn-primary mauriel-btn-block mauriel-btn-phone"
	   data-listing-id="<?php echo esc_attr( $listing_id ); ?>"
	   data-event="phone_click">
		&#128222; <?php echo esc_html( $phone ); ?>
	</a>
	<?php endif; ?>

	<?php if ( $website ) : ?>
	<a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"
	   class="mauriel-btn mauriel-btn-outline mauriel-btn-block mauriel-btn-website"
	   data-listing-id="<?php echo esc_attr( $listing_id ); ?>"
	   data-event="website_click">
		&#127758; <?php esc_html_e( 'Visit Website', 'mauriel-service-directory' ); ?>
	</a>
	<?php endif; ?>

	<div class="mauriel-lead-tabs">
		<button class="mauriel-lead-tab-btn active" data-tab="contact"><?php esc_html_e( 'Contact', 'mauriel-service-directory' ); ?></button>
		<button class="mauriel-lead-tab-btn" data-tab="quote"><?php esc_html_e( 'Get Quote', 'mauriel-service-directory' ); ?></button>
	</div>

	<div class="mauriel-notice mauriel-lead-success" style="display:none;"></div>
	<div class="mauriel-notice mauriel-notice-error mauriel-lead-error" style="display:none;"></div>

	<form class="mauriel-lead-form mauriel-contact-form" id="mauriel-contact-form" data-listing-id="<?php echo esc_attr( $listing_id ); ?>" data-lead-type="contact">
		<?php wp_nonce_field( 'mauriel_lead_' . $listing_id, 'mauriel_lead_nonce' ); ?>
		<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">
		<input type="hidden" name="lead_type" value="contact">
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Your Name', 'mauriel-service-directory' ); ?></label>
			<input type="text" name="name" class="mauriel-form-control" required placeholder="<?php esc_attr_e( 'John Smith', 'mauriel-service-directory' ); ?>">
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Email', 'mauriel-service-directory' ); ?></label>
			<input type="email" name="email" class="mauriel-form-control" required placeholder="<?php esc_attr_e( 'you@example.com', 'mauriel-service-directory' ); ?>">
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Phone', 'mauriel-service-directory' ); ?></label>
			<input type="tel" name="phone" class="mauriel-form-control" placeholder="<?php esc_attr_e( '(555) 000-0000', 'mauriel-service-directory' ); ?>">
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Message', 'mauriel-service-directory' ); ?></label>
			<textarea name="message" class="mauriel-form-control" rows="4" placeholder="<?php esc_attr_e( 'How can we help you?', 'mauriel-service-directory' ); ?>"></textarea>
		</div>
		<button type="submit" class="mauriel-btn mauriel-btn-primary mauriel-btn-block"><?php esc_html_e( 'Send Message', 'mauriel-service-directory' ); ?></button>
	</form>

	<form class="mauriel-lead-form mauriel-quote-form" id="mauriel-quote-form" data-listing-id="<?php echo esc_attr( $listing_id ); ?>" data-lead-type="quote" style="display:none;">
		<?php wp_nonce_field( 'mauriel_lead_' . $listing_id, 'mauriel_lead_nonce_quote' ); ?>
		<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">
		<input type="hidden" name="lead_type" value="quote">
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Your Name', 'mauriel-service-directory' ); ?></label>
			<input type="text" name="name" class="mauriel-form-control" required>
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Email', 'mauriel-service-directory' ); ?></label>
			<input type="email" name="email" class="mauriel-form-control" required>
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Phone', 'mauriel-service-directory' ); ?></label>
			<input type="tel" name="phone" class="mauriel-form-control">
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Describe Your Project', 'mauriel-service-directory' ); ?></label>
			<textarea name="message" class="mauriel-form-control" rows="5" placeholder="<?php esc_attr_e( 'Tell us about your project and budget...', 'mauriel-service-directory' ); ?>" required></textarea>
		</div>
		<button type="submit" class="mauriel-btn mauriel-btn-primary mauriel-btn-block"><?php esc_html_e( 'Request Quote', 'mauriel-service-directory' ); ?></button>
	</form>
</div>
