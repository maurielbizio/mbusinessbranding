<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Google_Places
 *
 * Integrates with the Google Places API to import reviews and retrieve
 * business place details for directory listings.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_Google_Places {

	/**
	 * Google Places Details API endpoint.
	 */
	const API_DETAILS_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

	/**
	 * Google Places Text Search API endpoint.
	 */
	const API_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/textsearch/json';

	// -------------------------------------------------------------------------
	// API key
	// -------------------------------------------------------------------------

	/**
	 * Returns the stored Google Places API key.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return (string) get_option( 'mauriel_google_places_key', '' );
	}

	// -------------------------------------------------------------------------
	// Import reviews
	// -------------------------------------------------------------------------

	/**
	 * Imports Google reviews for a listing and stores them as comments.
	 *
	 * Reviews that have already been imported are skipped (identified by
	 * checking the _mauriel_google_imported flag and matching reviewer name +
	 * comment date to avoid duplicates on repeated imports).
	 *
	 * @param  int    $listing_id  Post ID of the mauriel_listing.
	 * @param  string $place_id    Google Place ID string.
	 * @return array{imported_count: int, errors: array}  Result summary.
	 */
	public static function import_reviews( $listing_id, $place_id ) {
		$listing_id = absint( $listing_id );
		$place_id   = sanitize_text_field( $place_id );

		$result = array(
			'imported_count' => 0,
			'errors'         => array(),
		);

		// Validate listing.
		$listing = get_post( $listing_id );
		if ( ! $listing || 'mauriel_listing' !== $listing->post_type ) {
			$result['errors'][] = __( 'Invalid listing ID.', 'mauriel-service-directory' );
			return $result;
		}

		if ( '' === $place_id ) {
			$result['errors'][] = __( 'A Google Place ID is required.', 'mauriel-service-directory' );
			return $result;
		}

		$api_key = self::get_api_key();
		if ( '' === $api_key ) {
			$result['errors'][] = __( 'Google Places API key is not configured.', 'mauriel-service-directory' );
			return $result;
		}

		// -----------------------------------------------------------------------
		// Fetch place details from Google.
		// -----------------------------------------------------------------------
		$url = add_query_arg(
			array(
				'place_id' => rawurlencode( $place_id ),
				'fields'   => 'reviews,rating,user_ratings_total',
				'key'      => $api_key,
			),
			self::API_DETAILS_URL
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			$result['errors'][] = sprintf(
				/* translators: %s: error message */
				__( 'Google API request failed: %s', 'mauriel-service-directory' ),
				$response->get_error_message()
			);
			return $result;
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $http_code ) {
			$result['errors'][] = sprintf(
				/* translators: %d: HTTP status code */
				__( 'Google API returned HTTP %d.', 'mauriel-service-directory' ),
				$http_code
			);
			return $result;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['result']['reviews'] ) ) {
			$result['errors'][] = __( 'No reviews found for this Place ID, or the response was malformed.', 'mauriel-service-directory' );
			// Still save the place_id so the link is recorded.
			update_post_meta( $listing_id, '_mauriel_place_id', $place_id );
			return $result;
		}

		// Save the Place ID on the listing.
		update_post_meta( $listing_id, '_mauriel_place_id', $place_id );

		// -----------------------------------------------------------------------
		// Build a lookup of already-imported review fingerprints to skip dupes.
		// Fingerprint = sha256( reviewer_name + relative_time_description ).
		// -----------------------------------------------------------------------
		$existing_fingerprints = self::get_existing_fingerprints( $listing_id );

		// -----------------------------------------------------------------------
		// Process each review.
		// -----------------------------------------------------------------------
		$reviews = $body['result']['reviews'];

		foreach ( $reviews as $review ) {
			$reviewer_name  = isset( $review['author_name'] )             ? sanitize_text_field( $review['author_name'] )             : '';
			$review_text    = isset( $review['text'] )                    ? sanitize_textarea_field( $review['text'] )                : '';
			$rating         = isset( $review['rating'] )                  ? (int) $review['rating']                                   : 0;
			$time           = isset( $review['time'] )                    ? (int) $review['time']                                     : 0;
			$relative_time  = isset( $review['relative_time_description'] ) ? $review['relative_time_description']                   : '';

			// Build a dedup fingerprint.
			$fingerprint = hash( 'sha256', $reviewer_name . '|' . $relative_time . '|' . $rating );

			if ( in_array( $fingerprint, $existing_fingerprints, true ) ) {
				// Already imported — skip.
				continue;
			}

			// Ratings must be 1–5.
			if ( $rating < 1 || $rating > 5 ) {
				$result['errors'][] = sprintf(
					/* translators: %s: reviewer name */
					__( 'Skipped review from %s — invalid rating.', 'mauriel-service-directory' ),
					$reviewer_name
				);
				continue;
			}

			// Build data array for Mauriel_Reviews::submit().
			$data = array(
				'author_name'  => $reviewer_name,
				'author_email' => '',   // Google doesn't expose reviewer emails.
				'content'      => $review_text,
				'rating'       => $rating,
				'user_id'      => 0,
			);

			$comment_id = Mauriel_Reviews::submit( $listing_id, $data, true /* auto_approved */ );

			if ( is_wp_error( $comment_id ) ) {
				$result['errors'][] = sprintf(
					/* translators: 1: reviewer name 2: error message */
					__( 'Failed to import review from %1$s: %2$s', 'mauriel-service-directory' ),
					$reviewer_name,
					$comment_id->get_error_message()
				);
				continue;
			}

			// Mark the comment as an imported Google review.
			add_comment_meta( $comment_id, '_mauriel_google_imported',    1,            true );
			add_comment_meta( $comment_id, '_mauriel_google_fingerprint', $fingerprint, true );

			// Store original Unix timestamp so we can display the real date.
			if ( $time > 0 ) {
				add_comment_meta( $comment_id, '_mauriel_google_review_time', $time, true );
			}

			$result['imported_count']++;
		}

		return $result;
	}

	// -------------------------------------------------------------------------
	// Place details
	// -------------------------------------------------------------------------

	/**
	 * Returns full place details for a given Google Place ID.
	 *
	 * @param  string $place_id  Google Place ID.
	 * @return array|WP_Error    Decoded result array or WP_Error on failure.
	 */
	public static function get_place_details( $place_id ) {
		$place_id = sanitize_text_field( $place_id );

		if ( '' === $place_id ) {
			return new WP_Error(
				'missing_place_id',
				__( 'A Place ID is required.', 'mauriel-service-directory' )
			);
		}

		$api_key = self::get_api_key();
		if ( '' === $api_key ) {
			return new WP_Error(
				'no_api_key',
				__( 'Google Places API key is not configured.', 'mauriel-service-directory' )
			);
		}

		$url = add_query_arg(
			array(
				'place_id' => rawurlencode( $place_id ),
				'fields'   => 'name,formatted_address,formatted_phone_number,website,rating,'
					. 'user_ratings_total,opening_hours,geometry,photos,types,business_status',
				'key'      => $api_key,
			),
			self::API_DETAILS_URL
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Google API request failed: %s', 'mauriel-service-directory' ),
					$response->get_error_message()
				)
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $http_code ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Google API returned HTTP %d.', 'mauriel-service-directory' ),
					$http_code
				)
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['result'] ) ) {
			return new WP_Error(
				'invalid_response',
				__( 'Google API returned an invalid or empty response.', 'mauriel-service-directory' )
			);
		}

		if ( isset( $body['status'] ) && 'OK' !== $body['status'] ) {
			return new WP_Error(
				'places_error',
				sprintf(
					/* translators: %s: Google API status string */
					__( 'Google Places API error: %s', 'mauriel-service-directory' ),
					$body['status']
				)
			);
		}

		return $body['result'];
	}

	// -------------------------------------------------------------------------
	// Search
	// -------------------------------------------------------------------------

	/**
	 * Searches Google Places for a business by name and address and returns
	 * an array of candidate place_id values with display labels.
	 *
	 * @param  string $business_name  Business name to search.
	 * @param  string $address        Address or city/state string to bias results.
	 * @return array|WP_Error         Array of ['place_id'=>string, 'name'=>string, 'address'=>string]
	 *                                or WP_Error on failure.
	 */
	public static function search_place( $business_name, $address ) {
		$business_name = sanitize_text_field( $business_name );
		$address       = sanitize_text_field( $address );

		if ( '' === $business_name ) {
			return new WP_Error(
				'missing_query',
				__( 'A business name is required to search Google Places.', 'mauriel-service-directory' )
			);
		}

		$api_key = self::get_api_key();
		if ( '' === $api_key ) {
			return new WP_Error(
				'no_api_key',
				__( 'Google Places API key is not configured.', 'mauriel-service-directory' )
			);
		}

		$query = $business_name;
		if ( '' !== $address ) {
			$query .= ' ' . $address;
		}

		$url = add_query_arg(
			array(
				'query' => rawurlencode( $query ),
				'key'   => $api_key,
			),
			self::API_SEARCH_URL
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'request_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Google Places search failed: %s', 'mauriel-service-directory' ),
					$response->get_error_message()
				)
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $http_code ) {
			return new WP_Error(
				'api_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Google Places search returned HTTP %d.', 'mauriel-service-directory' ),
					$http_code
				)
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			return new WP_Error(
				'invalid_response',
				__( 'Google Places returned an invalid response.', 'mauriel-service-directory' )
			);
		}

		if ( isset( $body['status'] ) && 'OK' !== $body['status'] && 'ZERO_RESULTS' !== $body['status'] ) {
			return new WP_Error(
				'places_error',
				sprintf(
					/* translators: %s: Google API status string */
					__( 'Google Places API error: %s', 'mauriel-service-directory' ),
					$body['status']
				)
			);
		}

		$candidates = array();

		if ( ! empty( $body['results'] ) ) {
			foreach ( $body['results'] as $result ) {
				$candidates[] = array(
					'place_id' => isset( $result['place_id'] )            ? $result['place_id']            : '',
					'name'     => isset( $result['name'] )                ? sanitize_text_field( $result['name'] ) : '',
					'address'  => isset( $result['formatted_address'] )   ? sanitize_text_field( $result['formatted_address'] ) : '',
					'rating'   => isset( $result['rating'] )              ? (float) $result['rating']      : 0.0,
				);
			}
		}

		return $candidates;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns an array of already-imported review fingerprints for a listing
	 * to prevent duplicate imports.
	 *
	 * @param  int $listing_id
	 * @return string[]  Array of sha256 fingerprint strings.
	 */
	private static function get_existing_fingerprints( $listing_id ) {
		global $wpdb;

		$listing_id = absint( $listing_id );

		$fingerprints = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT cm.meta_value
				 FROM {$wpdb->commentmeta} cm
				 INNER JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
				 WHERE c.comment_post_ID = %d
				   AND c.comment_type   = 'mauriel_review'
				   AND cm.meta_key      = '_mauriel_google_fingerprint'",
				$listing_id
			)
		);

		return is_array( $fingerprints ) ? $fingerprints : array();
	}
}
