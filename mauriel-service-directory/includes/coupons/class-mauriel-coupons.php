<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Coupons
 *
 * Manages promotional coupons / special offers attached to directory listings.
 * All DB operations are delegated to Mauriel_DB_Coupons.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_Coupons {

	/**
	 * Allowed discount type slugs.
	 *
	 * @var string[]
	 */
	private static $discount_types = array(
		'percentage',
		'fixed',
		'free_service',
		'bogo',
	);

	// -------------------------------------------------------------------------
	// CRUD
	// -------------------------------------------------------------------------

	/**
	 * Creates a new coupon for a listing.
	 *
	 * @param  int   $listing_id  Post ID of the owner listing.
	 * @param  array $data        Coupon fields (see sanitize_coupon_data()).
	 * @param  int   $user_id     User performing the action.
	 * @return int|WP_Error       New coupon ID or WP_Error.
	 */
	public static function create( $listing_id, array $data, $user_id ) {
		$listing_id = absint( $listing_id );
		$user_id    = absint( $user_id );

		$ownership_check = self::verify_listing_ownership( $listing_id, $user_id );
		if ( is_wp_error( $ownership_check ) ) {
			return $ownership_check;
		}

		if ( ! class_exists( 'Mauriel_DB_Coupons' ) ) {
			return new WP_Error(
				'db_class_missing',
				__( 'Mauriel_DB_Coupons class is not available.', 'mauriel-service-directory' )
			);
		}

		$clean = self::sanitize_coupon_data( $data );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}

		$clean['listing_id']  = $listing_id;
		$clean['created_by']  = $user_id;
		$clean['created_at']  = current_time( 'mysql' );

		return Mauriel_DB_Coupons::create( $clean );
	}

	/**
	 * Updates an existing coupon.
	 *
	 * @param  int   $coupon_id
	 * @param  array $data
	 * @param  int   $user_id
	 * @return bool|WP_Error  True on success or WP_Error.
	 */
	public static function update( $coupon_id, array $data, $user_id ) {
		$coupon_id = absint( $coupon_id );
		$user_id   = absint( $user_id );

		if ( ! class_exists( 'Mauriel_DB_Coupons' ) ) {
			return new WP_Error(
				'db_class_missing',
				__( 'Mauriel_DB_Coupons class is not available.', 'mauriel-service-directory' )
			);
		}

		$coupon = Mauriel_DB_Coupons::get( $coupon_id );
		if ( ! $coupon ) {
			return new WP_Error(
				'not_found',
				__( 'Coupon not found.', 'mauriel-service-directory' )
			);
		}

		$ownership_check = self::verify_listing_ownership( (int) $coupon->listing_id, $user_id );
		if ( is_wp_error( $ownership_check ) ) {
			return $ownership_check;
		}

		$clean = self::sanitize_coupon_data( $data );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}

		$clean['updated_at'] = current_time( 'mysql' );

		return Mauriel_DB_Coupons::update( $coupon_id, $clean );
	}

	/**
	 * Deletes a coupon.
	 *
	 * @param  int $coupon_id
	 * @param  int $user_id
	 * @return bool|WP_Error
	 */
	public static function delete( $coupon_id, $user_id ) {
		$coupon_id = absint( $coupon_id );
		$user_id   = absint( $user_id );

		if ( ! class_exists( 'Mauriel_DB_Coupons' ) ) {
			return new WP_Error(
				'db_class_missing',
				__( 'Mauriel_DB_Coupons class is not available.', 'mauriel-service-directory' )
			);
		}

		$coupon = Mauriel_DB_Coupons::get( $coupon_id );
		if ( ! $coupon ) {
			return new WP_Error(
				'not_found',
				__( 'Coupon not found.', 'mauriel-service-directory' )
			);
		}

		$ownership_check = self::verify_listing_ownership( (int) $coupon->listing_id, $user_id );
		if ( is_wp_error( $ownership_check ) ) {
			return $ownership_check;
		}

		return Mauriel_DB_Coupons::delete_coupon( $coupon_id );
	}

	/**
	 * Returns all active (non-expired) coupons for a listing.
	 *
	 * @param  int $listing_id
	 * @return object[]|WP_Error
	 */
	public static function get_active( $listing_id ) {
		$listing_id = absint( $listing_id );

		if ( ! class_exists( 'Mauriel_DB_Coupons' ) ) {
			return new WP_Error(
				'db_class_missing',
				__( 'Mauriel_DB_Coupons class is not available.', 'mauriel-service-directory' )
			);
		}

		return Mauriel_DB_Coupons::get_active_for_listing( $listing_id );
	}

	// -------------------------------------------------------------------------
	// Sanitisation
	// -------------------------------------------------------------------------

	/**
	 * Sanitises and validates coupon field values from raw POST-like input.
	 *
	 * @param  array $data  Raw input.
	 * @return array|WP_Error  Sanitised array or WP_Error on validation failure.
	 */
	public static function sanitize_coupon_data( array $data ) {
		$title          = isset( $data['title'] )          ? sanitize_text_field( $data['title'] )          : '';
		$description    = isset( $data['description'] )    ? sanitize_textarea_field( $data['description'] ): '';
		$coupon_code    = isset( $data['coupon_code'] )    ? strtoupper( sanitize_text_field( $data['coupon_code'] ) ) : '';
		$discount_type  = isset( $data['discount_type'] )  ? sanitize_key( $data['discount_type'] )          : 'percentage';
		$discount_value = isset( $data['discount_value'] ) ? (float) $data['discount_value']                 : 0.0;
		$starts_at      = isset( $data['starts_at'] )      ? sanitize_text_field( $data['starts_at'] )       : '';
		$expires_at     = isset( $data['expires_at'] )     ? sanitize_text_field( $data['expires_at'] )      : '';
		$max_uses       = isset( $data['max_uses'] )        ? absint( $data['max_uses'] )                     : 0;

		// Required.
		if ( '' === $title ) {
			return new WP_Error(
				'missing_title',
				__( 'Coupon title is required.', 'mauriel-service-directory' )
			);
		}

		if ( ! in_array( $discount_type, self::$discount_types, true ) ) {
			return new WP_Error(
				'invalid_discount_type',
				sprintf(
					/* translators: %s: comma-separated list of valid types */
					__( 'Invalid discount type. Must be one of: %s', 'mauriel-service-directory' ),
					implode( ', ', self::$discount_types )
				)
			);
		}

		if ( 'percentage' === $discount_type && ( $discount_value < 0 || $discount_value > 100 ) ) {
			return new WP_Error(
				'invalid_discount_value',
				__( 'Percentage discount must be between 0 and 100.', 'mauriel-service-directory' )
			);
		}

		if ( 'fixed' === $discount_type && $discount_value < 0 ) {
			return new WP_Error(
				'invalid_discount_value',
				__( 'Fixed discount value cannot be negative.', 'mauriel-service-directory' )
			);
		}

		// Validate date formats (YYYY-MM-DD HH:MM:SS or YYYY-MM-DD).
		if ( '' !== $starts_at && ! self::is_valid_date( $starts_at ) ) {
			return new WP_Error(
				'invalid_starts_at',
				__( 'Invalid starts_at date format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.', 'mauriel-service-directory' )
			);
		}

		if ( '' !== $expires_at && ! self::is_valid_date( $expires_at ) ) {
			return new WP_Error(
				'invalid_expires_at',
				__( 'Invalid expires_at date format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.', 'mauriel-service-directory' )
			);
		}

		if ( '' !== $starts_at && '' !== $expires_at && strtotime( $starts_at ) >= strtotime( $expires_at ) ) {
			return new WP_Error(
				'invalid_date_range',
				__( 'Expiry date must be after the start date.', 'mauriel-service-directory' )
			);
		}

		return array(
			'title'          => $title,
			'description'    => $description,
			'coupon_code'    => $coupon_code,
			'discount_type'  => $discount_type,
			'discount_value' => $discount_value,
			'starts_at'      => $starts_at,
			'expires_at'     => $expires_at,
			'max_uses'       => $max_uses,
		);
	}

	// -------------------------------------------------------------------------
	// Ownership verification
	// -------------------------------------------------------------------------

	/**
	 * Verifies that $user_id is the owner of $listing_id (or an admin).
	 *
	 * @param  int $listing_id
	 * @param  int $user_id
	 * @return true|WP_Error
	 */
	public static function verify_listing_ownership( $listing_id, $user_id ) {
		$listing_id      = absint( $listing_id );
		$user_id         = absint( $user_id );
		$stored_owner_id = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );

		$is_admin = user_can( $user_id, 'manage_options' )
			|| user_can( $user_id, 'mauriel_admin' );

		if ( $stored_owner_id !== $user_id && ! $is_admin ) {
			return new WP_Error(
				'unauthorized',
				__( 'You do not have permission to manage coupons for this listing.', 'mauriel-service-directory' )
			);
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Validates a date string (accepts YYYY-MM-DD or YYYY-MM-DD HH:MM:SS).
	 *
	 * @param  string $date
	 * @return bool
	 */
	private static function is_valid_date( $date ) {
		// Try both formats.
		$formats = array( 'Y-m-d H:i:s', 'Y-m-d' );
		foreach ( $formats as $format ) {
			$dt = DateTime::createFromFormat( $format, $date );
			if ( $dt && $dt->format( $format ) === $date ) {
				return true;
			}
		}
		return false;
	}
}
