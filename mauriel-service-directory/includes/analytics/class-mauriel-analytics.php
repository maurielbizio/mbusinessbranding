<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Analytics
 *
 * Records and retrieves per-listing analytics events (views, impressions,
 * clicks, leads) with session-level deduplication so a single visitor only
 * increments each counter once per day.
 *
 * All heavy DB work is delegated to Mauriel_DB_Analytics so this class
 * stays as a clean, thin facade.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_Analytics {

	// -------------------------------------------------------------------------
	// Allowed event types
	// -------------------------------------------------------------------------

	/**
	 * Canonical list of recordable event types.
	 *
	 * @var string[]
	 */
	private static $event_types = array(
		'view',
		'impression',
		'lead_submit',
		'phone_click',
		'website_click',
		'coupon_view',
		'booking_click',
	);

	// -------------------------------------------------------------------------
	// Core record method
	// -------------------------------------------------------------------------

	/**
	 * Records a single analytics event for a listing.
	 *
	 * A session hash is derived from the listing ID, event type, current date,
	 * and a truncated IP hash so repeated requests from the same visitor on the
	 * same day do not inflate counts (INSERT IGNORE in the DB layer enforces
	 * this deduplication).
	 *
	 * @param  int    $listing_id   Post ID of the mauriel_listing.
	 * @param  string $event_type   One of self::$event_types.
	 * @param  string $search_term  Search query that surfaced this listing (optional).
	 * @param  string $referrer     Referrer URL (optional).
	 * @return bool                 True if recorded, false if skipped or failed.
	 */
	public static function record( $listing_id, $event_type, $search_term = '', $referrer = '' ) {
		$listing_id = absint( $listing_id );
		$event_type = sanitize_key( $event_type );

		if ( ! $listing_id ) {
			return false;
		}

		if ( ! in_array( $event_type, self::$event_types, true ) ) {
			return false;
		}

		// Do not record events by super-admins or directory admins (reduces noise
		// when admins are browsing the directory).
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Build a deterministic session hash.
		$remote_ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		$ip_fragment = substr( hash( 'sha256', $remote_ip . NONCE_SALT ), 0, 16 );

		$session_hash = hash(
			'sha256',
			$listing_id . '_' . $event_type . '_' . gmdate( 'Y-m-d' ) . '_' . $ip_fragment
		);

		// Sanitise optional strings.
		$search_term = sanitize_text_field( (string) $search_term );
		$referrer    = esc_url_raw( (string) $referrer );

		if ( ! class_exists( 'Mauriel_DB_Analytics' ) ) {
			return false;
		}

		return Mauriel_DB_Analytics::record( array(
			'listing_id'   => $listing_id,
			'event_type'   => $event_type,
			'session_hash' => $session_hash,
			'search_term'  => $search_term,
			'referrer'     => $referrer,
			'recorded_at'  => gmdate( 'Y-m-d H:i:s' ),
		) );
	}

	// -------------------------------------------------------------------------
	// Shorthand helpers
	// -------------------------------------------------------------------------

	/**
	 * Records a listing page view.
	 *
	 * @param  int $listing_id
	 * @return bool
	 */
	public static function record_view( $listing_id ) {
		return self::record( $listing_id, 'view' );
	}

	/**
	 * Records an impression (listing appeared in search results).
	 *
	 * @param  int    $listing_id
	 * @param  string $search_term  The query that produced the impression.
	 * @return bool
	 */
	public static function record_impression( $listing_id, $search_term = '' ) {
		return self::record( $listing_id, 'impression', $search_term );
	}

	/**
	 * Records a phone number click on a listing.
	 *
	 * @param  int $listing_id
	 * @return bool
	 */
	public static function record_phone_click( $listing_id ) {
		return self::record( $listing_id, 'phone_click' );
	}

	/**
	 * Records a website URL click on a listing.
	 *
	 * @param  int $listing_id
	 * @return bool
	 */
	public static function record_website_click( $listing_id ) {
		return self::record( $listing_id, 'website_click' );
	}

	// -------------------------------------------------------------------------
	// Dashboard data
	// -------------------------------------------------------------------------

	/**
	 * Aggregates analytics data for the owner dashboard.
	 *
	 * Returns a structured array containing both summary totals for the period
	 * and day-by-day trend arrays suitable for charting.
	 *
	 * @param  int $listing_id  Post ID of the listing.
	 * @param  int $days        Number of days to look back (default 30).
	 * @return array{
	 *     summary: array{views: int, impressions: int, leads: int, phone_clicks: int, website_clicks: int},
	 *     trend:   array{dates: string[], views: int[], leads: int[]}
	 * }
	 */
	public static function get_dashboard_data( $listing_id, $days = 30 ) {
		global $wpdb;

		$listing_id = absint( $listing_id );
		$days       = max( 1, absint( $days ) );

		$table     = $wpdb->prefix . 'mauriel_analytics';
		$date_from = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// ------------------------------------------------------------------
		// Summary totals — one row per event type.
		// ------------------------------------------------------------------
		$raw_summary = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, COUNT(*) AS total
				 FROM {$table}
				 WHERE listing_id = %d
				   AND DATE(recorded_at) >= %s
				 GROUP BY event_type",
				$listing_id,
				$date_from
			),
			ARRAY_A
		);

		$summary = array(
			'views'          => 0,
			'impressions'    => 0,
			'leads'          => 0,
			'phone_clicks'   => 0,
			'website_clicks' => 0,
		);

		if ( is_array( $raw_summary ) ) {
			foreach ( $raw_summary as $row ) {
				switch ( $row['event_type'] ) {
					case 'view':
						$summary['views'] = (int) $row['total'];
						break;
					case 'impression':
						$summary['impressions'] = (int) $row['total'];
						break;
					case 'lead_submit':
						$summary['leads'] = (int) $row['total'];
						break;
					case 'phone_click':
						$summary['phone_clicks'] = (int) $row['total'];
						break;
					case 'website_click':
						$summary['website_clicks'] = (int) $row['total'];
						break;
				}
			}
		}

		// ------------------------------------------------------------------
		// Daily trend — views and leads per day for the chart.
		// ------------------------------------------------------------------
		$raw_trend = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(recorded_at) AS day, event_type, COUNT(*) AS total
				 FROM {$table}
				 WHERE listing_id = %d
				   AND DATE(recorded_at) >= %s
				   AND event_type IN ('view', 'lead_submit')
				 GROUP BY DATE(recorded_at), event_type
				 ORDER BY DATE(recorded_at) ASC",
				$listing_id,
				$date_from
			),
			ARRAY_A
		);

		// Build a complete date range so missing days show as 0.
		$dates_range = array();
		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$dates_range[ gmdate( 'Y-m-d', strtotime( "-{$i} days" ) ) ] = array(
				'views' => 0,
				'leads' => 0,
			);
		}

		if ( is_array( $raw_trend ) ) {
			foreach ( $raw_trend as $row ) {
				$day = $row['day'];
				if ( ! isset( $dates_range[ $day ] ) ) {
					$dates_range[ $day ] = array( 'views' => 0, 'leads' => 0 );
				}
				if ( 'view' === $row['event_type'] ) {
					$dates_range[ $day ]['views'] = (int) $row['total'];
				} elseif ( 'lead_submit' === $row['event_type'] ) {
					$dates_range[ $day ]['leads'] = (int) $row['total'];
				}
			}
		}

		$trend = array(
			'dates' => array(),
			'views' => array(),
			'leads' => array(),
		);

		foreach ( $dates_range as $date => $values ) {
			$trend['dates'][] = $date;
			$trend['views'][] = $values['views'];
			$trend['leads'][] = $values['leads'];
		}

		return array(
			'summary' => $summary,
			'trend'   => $trend,
		);
	}
}
