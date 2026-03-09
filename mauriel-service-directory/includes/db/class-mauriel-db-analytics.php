<?php
/**
 * DB operations for the mauriel_analytics table.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_DB_Analytics
 *
 * Records listing view/click events and provides summary and trend queries.
 * The table uses a UNIQUE KEY on (session_hash, event_type, listing_id) so
 * INSERT IGNORE provides natural de-duplication per session.
 */
class Mauriel_DB_Analytics {

	/**
	 * Short table identifier.
	 *
	 * @var string
	 */
	private const TABLE = 'analytics';

	/**
	 * Record an analytics event.
	 *
	 * Uses INSERT IGNORE so duplicate session events are silently skipped.
	 *
	 * @param int    $listing_id   Listing (post) ID.
	 * @param string $event_type   One of: view, impression, click, phone_click,
	 *                             direction_click, website_click, lead_submit.
	 * @param string $session_hash Pre-built session hash (use build_session_hash()).
	 * @param string $referrer     Optional referring URL (max 500 chars).
	 * @param string $search_term  Optional search term that led to the listing.
	 * @return int|false           Insert ID (or 0 on IGNORE), false on error.
	 */
	public static function record(
		int $listing_id,
		string $event_type,
		string $session_hash,
		string $referrer = '',
		string $search_term = ''
	) {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO `{$table}` (listing_id, event_type, session_hash, referrer, search_term, recorded_at) VALUES (%d, %s, %s, %s, %s, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id,
				$event_type,
				$session_hash,
				substr( $referrer, 0, 500 ),
				substr( $search_term, 0, 255 ),
				current_time( 'mysql' )
			)
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Get event counts grouped by event_type for a listing over a date range.
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @param int $days       Number of past days to include (default 30).
	 * @return array<string,int> Map of event_type => count.
	 */
	public static function get_summary( int $listing_id, int $days = 30 ): array {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, COUNT(*) AS cnt FROM `{$table}` WHERE listing_id = %d AND recorded_at >= %s GROUP BY event_type", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id,
				$since
			)
		);

		$summary = [];
		foreach ( (array) $rows as $row ) {
			$summary[ $row->event_type ] = (int) $row->cnt;
		}

		return $summary;
	}

	/**
	 * Get a daily count trend for a specific event type over a date range.
	 *
	 * @param int    $listing_id Listing (post) ID.
	 * @param string $event_type Event type to trend.
	 * @param int    $days       Number of past days to include (default 30).
	 * @return array<string,int> Map of 'YYYY-MM-DD' => count, ordered by date ASC.
	 */
	public static function get_trend( int $listing_id, string $event_type, int $days = 30 ): array {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );
		$since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(recorded_at) AS day, COUNT(*) AS cnt FROM `{$table}` WHERE listing_id = %d AND event_type = %s AND recorded_at >= %s GROUP BY DATE(recorded_at) ORDER BY DATE(recorded_at) ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id,
				$event_type,
				$since
			)
		);

		$trend = [];
		foreach ( (array) $rows as $row ) {
			$trend[ $row->day ] = (int) $row->cnt;
		}

		return $trend;
	}

	/**
	 * Get all-time totals for every event type for a listing.
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @return array<string,int> Map of event_type => all-time count.
	 */
	public static function get_totals( int $listing_id ): array {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, COUNT(*) AS cnt FROM `{$table}` WHERE listing_id = %d GROUP BY event_type", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id
			)
		);

		$totals = [];
		foreach ( (array) $rows as $row ) {
			$totals[ $row->event_type ] = (int) $row->cnt;
		}

		return $totals;
	}

	/**
	 * Build a session-scoped de-duplication hash.
	 *
	 * The hash is based on listing ID, event type, the current calendar date,
	 * and an anonymised (last-octet-zeroed) IP address so that one visitor
	 * produces one event record per day without storing PII.
	 *
	 * @param int    $listing_id Listing (post) ID.
	 * @param string $event_type Event type string.
	 * @return string 64-character hex SHA-256 hash.
	 */
	public static function build_session_hash( int $listing_id, string $event_type ): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '0.0.0.0';

		// Anonymise: zero the last octet for IPv4, or the last 80 bits for IPv6.
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$ip_anon = preg_replace( '/\.\d+$/', '.0', $ip );
		} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			// Zero last 80 bits (last 5 groups of IPv6).
			$parts    = explode( ':', inet_ntop( inet_pton( $ip ) ) );
			$parts    = array_slice( $parts, 0, 3 );
			$ip_anon  = implode( ':', $parts ) . '::';
		} else {
			$ip_anon = '0.0.0.0';
		}

		$components = implode( '|', [
			$listing_id,
			$event_type,
			gmdate( 'Y-m-d' ),
			$ip_anon,
			wp_salt( 'auth' ),
		] );

		return hash( 'sha256', $components );
	}
}
