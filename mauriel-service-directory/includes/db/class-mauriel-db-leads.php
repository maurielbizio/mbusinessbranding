<?php
/**
 * DB operations for the mauriel_leads table.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_DB_Leads
 *
 * Stores and retrieves contact/quote leads submitted through listing pages.
 */
class Mauriel_DB_Leads {

	/**
	 * Short table identifier.
	 *
	 * @var string
	 */
	private const TABLE = 'leads';

	/**
	 * Insert a new lead record.
	 *
	 * @param array $data Column => value pairs. Required: listing_id, lead_type.
	 * @return int|WP_Error New lead ID on success, WP_Error on failure.
	 */
	public static function create( array $data ) {
		$defaults = [
			'status'     => 'new',
			'created_at' => current_time( 'mysql' ),
		];

		$data = wp_parse_args( $data, $defaults );

		// Hash the IP for GDPR-friendly storage.
		if ( empty( $data['ip_hash'] ) ) {
			$ip             = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$data['ip_hash'] = $ip ? hash( 'sha256', $ip . wp_salt() ) : '';
		}

		return Mauriel_DB::insert( self::TABLE, $data );
	}

	/**
	 * Retrieve a single lead by its primary key.
	 *
	 * @param int $id Lead ID.
	 * @return object|null Lead row object, or null if not found.
	 */
	public static function get( int $id ): ?object {
		return Mauriel_DB::get_row( self::TABLE, [ 'id' => $id ] ) ?: null;
	}

	/**
	 * Retrieve paginated leads for a listing, optionally filtered by status.
	 *
	 * @param int    $listing_id Listing (post) ID.
	 * @param string $status     Optional status filter: 'new', 'read', 'archived', or '' for all.
	 * @param int    $limit      Number of leads to return (default 20).
	 * @param int    $offset     Row offset for pagination (default 0).
	 * @return object[] Array of lead row objects.
	 */
	public static function get_for_listing( int $listing_id, string $status = '', int $limit = 20, int $offset = 0 ): array {
		global $wpdb;

		$table  = Mauriel_DB::table( self::TABLE );
		$values = [ $listing_id ];

		$status_sql = '';
		if ( '' !== $status ) {
			$status_sql = 'AND status = %s ';
			$values[]   = $status;
		}

		$values[] = max( 1, $limit );
		$values[] = max( 0, $offset );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE listing_id = %d {$status_sql}ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$values
			)
		);
	}

	/**
	 * Count leads for a listing, optionally filtered by status.
	 *
	 * @param int    $listing_id Listing (post) ID.
	 * @param string $status     Optional status filter.
	 * @return int Lead count.
	 */
	public static function count_for_listing( int $listing_id, string $status = '' ): int {
		global $wpdb;

		$table  = Mauriel_DB::table( self::TABLE );
		$values = [ $listing_id ];

		$status_sql = '';
		if ( '' !== $status ) {
			$status_sql = 'AND status = %s';
			$values[]   = $status;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE listing_id = %d {$status_sql}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$values
			)
		);
	}

	/**
	 * Mark a lead as read.
	 *
	 * @param int $id Lead ID.
	 * @return int|false|WP_Error Result of the update.
	 */
	public static function mark_read( int $id ) {
		return Mauriel_DB::update( self::TABLE, [ 'status' => 'read' ], [ 'id' => $id ] );
	}

	/**
	 * Archive a lead.
	 *
	 * @param int $id Lead ID.
	 * @return int|false|WP_Error Result of the update.
	 */
	public static function archive( int $id ) {
		return Mauriel_DB::update( self::TABLE, [ 'status' => 'archived' ], [ 'id' => $id ] );
	}

	/**
	 * Retrieve recent leads (last N days) for a listing.
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @param int $days       Number of days to look back (default 30).
	 * @return object[] Array of lead row objects.
	 */
	public static function get_recent( int $listing_id, int $days = 30 ): array {
		global $wpdb;

		$table     = Mauriel_DB::table( self::TABLE );
		$since     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE listing_id = %d AND created_at >= %s ORDER BY created_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id,
				$since
			)
		);
	}
}
