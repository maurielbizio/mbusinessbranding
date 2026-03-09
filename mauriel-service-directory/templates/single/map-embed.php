<?php defined( 'ABSPATH' ) || exit;
/**
 * Template: Single Listing Map Embed
 * Variables: $lat, $lng, $address, $business_name, $listing_id
 */
if ( empty( $lat ) || empty( $lng ) ) return;
$directions_url = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode( $address );
?>
<div class="mauriel-map-section">
	<div id="mauriel-single-map"
	     class="mauriel-single-map-container"
	     data-lat="<?php echo esc_attr( $lat ); ?>"
	     data-lng="<?php echo esc_attr( $lng ); ?>"
	     data-title="<?php echo esc_attr( $business_name ); ?>"
	     style="height:300px;width:100%;"></div>
	<div class="mauriel-map-footer">
		<address class="mauriel-listing-address">
			<span class="dashicons dashicons-location"></span>
			<?php echo esc_html( $address ); ?>
		</address>
		<a href="<?php echo esc_url( $directions_url ); ?>"
		   target="_blank"
		   rel="noopener noreferrer"
		   class="mauriel-btn mauriel-btn-outline mauriel-btn-sm mauriel-btn-directions"
		   data-listing-id="<?php echo esc_attr( $listing_id ?? '' ); ?>"
		   data-event="direction_click">
			&#128205; <?php esc_html_e( 'Get Directions', 'mauriel-service-directory' ); ?>
		</a>
	</div>
</div>
