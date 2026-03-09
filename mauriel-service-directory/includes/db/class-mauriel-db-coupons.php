<?php
/**
 * DB operations for the mauriel_coupons table.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_DB_Coupons
 *
 * Manages discount coupons that business owners attach to their listings.
 */
class Mauriel_DB_Coupons {

	/**
	 * Short table identifier.
	 *
	 * @var string
	 */
	private const TABLE = 'coupons';

	/**
	 * Insert a new coupon record.
	 *
	 * @param array $data Column => value pairs. Required: listing_id, title.
	 * @return int|WP_Error New coupon ID on success, WP_Error on failure.
	 */
	public static function create( array $data ) {
		$defaults = [
			'discount_type' => 'percent',
			'use_count'     => 0,
			'is_active'     => 1,
			'created_at'    => current_time( 'mysql' ),
		];

		$data = wp_parse_args( $data, $defaults );

		return Mauriel_DB::insert( self::TABLE, $data );
	}

	/**
	 * Retrieve a single coupon by its primary key.
	 *
	 * @param int $id Coupon ID.
	 * @return object|null Coupon row object, or null if not found.
	 */
	public static function get( int $id ): ?object {
		return Mauriel_DB::get_row( self::TABLE, [ 'id' => $id ] ) ?: null;
	}

	/**
	 * Retrieve all active, non-expired, non-maxed coupons for a listing.
	 *
	 * A coupon is considered valid if:
	 *  - is_active = 1
	 *  - expires_at IS NULL OR expires_at > NOW()
	 *  - max_uses IS NULL OR use_count < max_uses
	 *  - starts_at IS NULL OR starts_at <= NOW()
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @return object[] Array of valid coupon row objects.
	 */
	public static function get_active_for_listing( int $listing_id ): array {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );
		$now   = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}`
				WHERE listing_id = %d
				  AND is_active = 1
				  AND (starts_at IS NULL OR starts_at <= %s)
				  AND (expires_at IS NULL OR expires_at > %s)
				  AND (max_uses IS NULL OR use_count < max_uses)
				ORDER BY created_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id,
				$now,
				$now
			)
		);
	}

	/**
	 * Retrieve all coupons (regardless of status) for a listing.
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @return object[] Array of all coupon row objects, newest first.
	 */
	public static function get_for_listing( int $listing_id ): array {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE listing_id = %d ORDER BY created_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id
			)
		);
	}

	/**
	 * Update a coupon by its primary key.
	 *
	 * @param int   $id   Coupon ID.
	 * @param array $data Column => value pairs to update.
	 * @return int|false|WP_Error Rows affected, 0 if unchanged, false/WP_Error on failure.
	 */
	public static function update_coupon( int $id, array $data ) {
		return Mauriel_DB::update( self::TABLE, $data, [ 'id' => $id ] );
	}

	/**
	 * Delete a coupon by its primary key.
	 *
	 * @param int $id Coupon ID.
	 * @return int|false|WP_Error Rows deleted, false/WP_Error on failure.
	 */
	public static function delete_coupon( int $id ) {
		return Mauriel_DB::delete( self::TABLE, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Atomically increment the use_count for a coupon.
	 *
	 * Uses a direct SQL expression to avoid race conditions.
	 *
	 * @param int $id Coupon ID.
	 * @return int|false Rows affected, or false on failure.
	 */
	public static function increment_use_count( int $id ) {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$table}` SET use_count = use_count + 1 WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			)
		);
	}

	/**
	 * Check whether a coupon is expired or has exceeded its usage limit.
	 *
	 * Accepts either a coupon row object (as returned by self::get()) or an
	 * associative array with the same keys.
	 *
	 * @param object|array $coupon Coupon row or associative array.
	 * @return bool True if the coupon is expired/maxed, false if still valid.
	 */
	public static function is_expired( $coupon ): bool {
		// Normalise to array for consistent access.
		if ( is_object( $coupon ) ) {
			$coupon = (array) $coupon;
		}

		$now = current_time( 'mysql' );

		// Check expiry date.
		if ( ! empty( $coupon['expires_at'] ) && $coupon['expires_at'] <= $now ) {
			return true;
		}

		// Check max usage.
		if ( ! empty( $coupon['max_uses'] ) && (int) $coupon['use_count'] >= (int) $coupon['max_uses'] ) {
			return true;
		}

		// Check if coupon has not yet started.
		if ( ! empty( $coupon['starts_at'] ) && $coupon['starts_at'] > $now ) {
			return true;
		}

		return false;
	}
}
