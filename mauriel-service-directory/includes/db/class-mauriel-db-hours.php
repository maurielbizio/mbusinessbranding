<?php
/**
 * DB operations for the mauriel_business_hours table.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_DB_Hours
 *
 * Manages business hours records with bulk upsert support and
 * real-time open/closed status checks.
 */
class Mauriel_DB_Hours {

	/**
	 * Short table identifier.
	 *
	 * @var string
	 */
	private const TABLE = 'business_hours';

	/**
	 * Bulk-upsert business hours for a listing.
	 *
	 * Accepts an array of 7 day entries (index 0 = Sunday .. 6 = Saturday).
	 * Uses INSERT ... ON DUPLICATE KEY UPDATE because the table has a UNIQUE
	 * KEY on (listing_id, day_of_week).
	 *
	 * @param int     $listing_id  Listing (post) ID.
	 * @param array[] $hours_array Array of hour maps, each with keys:
	 *                             day_of_week (int 0-6), is_open (bool),
	 *                             open_time (string 'HH:MM:SS'|null),
	 *                             close_time (string 'HH:MM:SS'|null),
	 *                             is_24_hours (bool).
	 * @return bool True on success, false if nothing was provided.
	 */
	public static function save_hours( int $listing_id, array $hours_array ): bool {
		global $wpdb;

		if ( empty( $hours_array ) ) {
			return false;
		}

		$table = Mauriel_DB::table( self::TABLE );

		// Build a multi-row INSERT statement.
		$value_clauses = [];
		$values        = [];

		foreach ( $hours_array as $day ) {
			$day_of_week = (int) ( $day['day_of_week'] ?? 0 );
			$is_open     = isset( $day['is_open'] ) ? ( $day['is_open'] ? 1 : 0 ) : 1;
			$open_time   = ! empty( $day['open_time'] )  ? $day['open_time']  : null;
			$close_time  = ! empty( $day['close_time'] ) ? $day['close_time'] : null;
			$is_24       = isset( $day['is_24_hours'] ) ? ( $day['is_24_hours'] ? 1 : 0 ) : 0;

			$value_clauses[] = '(%d, %d, %d, %s, %s, %d)';
			$values[]        = $listing_id;
			$values[]        = $day_of_week;
			$values[]        = $is_open;
			$values[]        = $open_time;
			$values[]        = $close_time;
			$values[]        = $is_24;
		}

		$placeholders = implode( ', ', $value_clauses );

		$sql = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			"INSERT INTO `{$table}` (listing_id, day_of_week, is_open, open_time, close_time, is_24_hours) VALUES {$placeholders} ON DUPLICATE KEY UPDATE is_open = VALUES(is_open), open_time = VALUES(open_time), close_time = VALUES(close_time), is_24_hours = VALUES(is_24_hours)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$values
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return false !== $wpdb->query( $sql );
	}

	/**
	 * Retrieve all business hours for a listing, indexed by day_of_week.
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @return array<int,object> Map of day_of_week (0-6) => row object.
	 */
	public static function get_hours( int $listing_id ): array {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE listing_id = %d ORDER BY day_of_week ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id
			)
		);

		$indexed = [];
		foreach ( (array) $rows as $row ) {
			$indexed[ (int) $row->day_of_week ] = $row;
		}

		return $indexed;
	}

	/**
	 * Determine whether the business is currently open based on stored hours.
	 *
	 * Uses the site's configured timezone (wp_timezone()).
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @return bool True if currently open, false otherwise.
	 */
	public static function is_open_now( int $listing_id ): bool {
		$hours = self::get_hours( $listing_id );

		if ( empty( $hours ) ) {
			return false;
		}

		try {
			$tz  = wp_timezone();
			$now = new DateTimeImmutable( 'now', $tz );
		} catch ( Exception $e ) {
			return false;
		}

		// PHP: 0 = Sunday, 1 = Monday … 6 = Saturday.
		$day_of_week = (int) $now->format( 'w' );

		if ( ! isset( $hours[ $day_of_week ] ) ) {
			return false;
		}

		$day = $hours[ $day_of_week ];

		if ( ! (int) $day->is_open ) {
			return false;
		}

		if ( (int) $day->is_24_hours ) {
			return true;
		}

		if ( empty( $day->open_time ) || empty( $day->close_time ) ) {
			return false;
		}

		try {
			$current_time = $now->format( 'H:i:s' );
			return ( $current_time >= $day->open_time && $current_time <= $day->close_time );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Bulk-fetch business hours for multiple listings in a single query.
	 *
	 * @param int[] $listing_ids Array of listing (post) IDs.
	 * @return array<int,array<int,object>> Map of listing_id => (day_of_week => row).
	 */
	public static function get_hours_for_listings( array $listing_ids ): array {
		global $wpdb;

		if ( empty( $listing_ids ) ) {
			return [];
		}

		$listing_ids = array_map( 'intval', $listing_ids );
		$table       = Mauriel_DB::table( self::TABLE );
		$id_list     = implode( ',', $listing_ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT * FROM `{$table}` WHERE listing_id IN ({$id_list}) ORDER BY listing_id ASC, day_of_week ASC"
		);

		$result = [];
		foreach ( (array) $rows as $row ) {
			$lid = (int) $row->listing_id;
			$day = (int) $row->day_of_week;

			if ( ! isset( $result[ $lid ] ) ) {
				$result[ $lid ] = [];
			}

			$result[ $lid ][ $day ] = $row;
		}

		return $result;
	}
}
