<?php defined( 'ABSPATH' ) || exit;
/**
 * Template: Booking Widget
 * Variables: $booking_url, $listing_id
 */
if ( empty( $booking_url ) ) return;
$embed_code = Mauriel_Booking::get_embed_code( $booking_url );
?>
<section class="mauriel-booking-section" id="booking">
	<h2 class="mauriel-section-title"><?php esc_html_e( 'Book an Appointment', 'mauriel-service-directory' ); ?></h2>
	<div class="mauriel-booking-widget">
		<?php echo $embed_code; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</section>
