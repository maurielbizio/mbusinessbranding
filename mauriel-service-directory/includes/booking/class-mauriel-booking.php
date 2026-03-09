<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_Booking {

	/**
	 * Allowed booking URL domains.
	 */
	private static $allowed_domains = array(
		'calendly.com',
		'square.site',
		'squareup.com',
		'acuityscheduling.com',
		'setmore.com',
		'appointlet.com',
		'vcita.com',
		'simplybook.me',
		'booksy.com',
		'vagaro.com',
	);

	/**
	 * Sanitize and validate a booking URL.
	 */
	public static function sanitize_booking_url( $url ) {
		$url = esc_url_raw( trim( $url ) );
		if ( empty( $url ) ) {
			return '';
		}
		$parsed = wp_parse_url( $url );
		if ( empty( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'https', 'http' ), true ) ) {
			return '';
		}
		if ( empty( $parsed['host'] ) ) {
			return '';
		}
		// Allow any HTTPS URL — validate scheme only
		if ( 'https' !== $parsed['scheme'] ) {
			return ''; // require HTTPS
		}
		return $url;
	}

	/**
	 * Save booking URL for a listing.
	 */
	public static function save_booking_url( $listing_id, $url, $user_id ) {
		$listing_id = absint( $listing_id );
		$owner_id   = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );
		if ( $owner_id !== (int) $user_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'not_authorized', __( 'You are not authorized to edit this listing.', 'mauriel-service-directory' ) );
		}
		$clean_url = self::sanitize_booking_url( $url );
		update_post_meta( $listing_id, '_mauriel_booking_url', $clean_url );
		return true;
	}

	/**
	 * Get booking URL for a listing.
	 */
	public static function get_booking_url( $listing_id ) {
		return get_post_meta( absint( $listing_id ), '_mauriel_booking_url', true );
	}

	/**
	 * Get embed code for a booking URL.
	 */
	public static function get_embed_code( $url ) {
		if ( empty( $url ) ) {
			return '';
		}
		$parsed = wp_parse_url( $url );
		$host   = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';

		if ( false !== strpos( $host, 'calendly.com' ) ) {
			return sprintf(
				'<div class="calendly-inline-widget" data-url="%s" style="min-width:320px;height:630px;"></div>
				<script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js" async></script>',
				esc_attr( $url )
			);
		}

		if ( false !== strpos( $host, 'square.site' ) || false !== strpos( $host, 'squareup.com' ) ) {
			return sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" class="mauriel-btn mauriel-btn-primary">%s</a>',
				esc_url( $url ),
				esc_html__( 'Book an Appointment', 'mauriel-service-directory' )
			);
		}

		// Generic booking link button
		return sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" class="mauriel-btn mauriel-btn-primary">%s</a>',
			esc_url( $url ),
			esc_html__( 'Book Now', 'mauriel-service-directory' )
		);
	}

	/**
	 * Check if a domain is in the allowed list.
	 */
	public static function is_allowed_domain( $url ) {
		$parsed = wp_parse_url( $url );
		$host   = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';
		foreach ( self::$allowed_domains as $domain ) {
			if ( false !== strpos( $host, $domain ) ) {
				return true;
			}
		}
		return false;
	}
}
