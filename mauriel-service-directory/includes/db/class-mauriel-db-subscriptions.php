<?php
/**
 * DB operations for the mauriel_subscriptions table.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_DB_Subscriptions
 *
 * Manages subscription records that link users, listings, packages, and
 * Stripe billing data.
 */
class Mauriel_DB_Subscriptions {

	/**
	 * Short table identifier.
	 *
	 * @var string
	 */
	private const TABLE = 'subscriptions';

	/**
	 * Insert a new subscription record.
	 *
	 * @param array $data Column => value pairs. Required: user_id, listing_id, package_id.
	 * @return int|WP_Error New subscription ID on success, WP_Error on failure.
	 */
	public static function create( array $data ) {
		$now = current_time( 'mysql' );

		$defaults = [
			'status'           => 'free',
			'billing_interval' => 'none',
			'created_at'       => $now,
			'updated_at'       => $now,
		];

		$data = wp_parse_args( $data, $defaults );

		return Mauriel_DB::insert( self::TABLE, $data );
	}

	/**
	 * Retrieve a subscription by its primary key.
	 *
	 * @param int $id Subscription ID.
	 * @return object|null Subscription row, or null if not found.
	 */
	public static function get( int $id ): ?object {
		return Mauriel_DB::get_row( self::TABLE, [ 'id' => $id ] ) ?: null;
	}

	/**
	 * Retrieve the active subscription for a given listing.
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @return object|null Most recent subscription for the listing, or null.
	 */
	public static function get_by_listing( int $listing_id ): ?object {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE listing_id = %d ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$listing_id
			)
		) ?: null;
	}

	/**
	 * Retrieve all subscriptions for a given WordPress user.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return object[] Array of subscription rows.
	 */
	public static function get_by_user( int $user_id ): array {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE user_id = %d ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);
	}

	/**
	 * Retrieve a subscription by its Stripe subscription ID.
	 *
	 * @param string $stripe_sub_id Stripe subscription ID (e.g. 'sub_xxxxx').
	 * @return object|null Subscription row, or null if not found.
	 */
	public static function get_by_stripe_subscription( string $stripe_sub_id ): ?object {
		return Mauriel_DB::get_row( self::TABLE, [ 'stripe_subscription_id' => $stripe_sub_id ] ) ?: null;
	}

	/**
	 * Update a subscription by its primary key.
	 *
	 * @param int   $id   Subscription ID.
	 * @param array $data Column => value pairs to update.
	 * @return int|false|WP_Error Rows affected, 0 if unchanged, false/WP_Error on failure.
	 */
	public static function update_subscription( int $id, array $data ) {
		$data['updated_at'] = current_time( 'mysql' );
		return Mauriel_DB::update( self::TABLE, $data, [ 'id' => $id ] );
	}

	/**
	 * Update a subscription identified by its Stripe subscription ID.
	 *
	 * Primarily used in Stripe webhook handlers.
	 *
	 * @param string $stripe_sub_id Stripe subscription ID.
	 * @param array  $data          Column => value pairs to update.
	 * @return int|false|WP_Error Rows affected, 0 if unchanged, false/WP_Error on failure.
	 */
	public static function update_by_stripe_subscription( string $stripe_sub_id, array $data ) {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql' );
		$table              = Mauriel_DB::table( self::TABLE );

		// Build SET clause dynamically.
		$set_parts  = [];
		$set_values = [];

		foreach ( $data as $column => $value ) {
			if ( is_int( $value ) ) {
				$set_parts[]  = '`' . esc_sql( $column ) . '` = %d';
				$set_values[] = $value;
			} elseif ( is_float( $value ) ) {
				$set_parts[]  = '`' . esc_sql( $column ) . '` = %f';
				$set_values[] = $value;
			} else {
				$set_parts[]  = '`' . esc_sql( $column ) . '` = %s';
				$set_values[] = $value;
			}
		}

		$set_values[] = $stripe_sub_id;
		$set_sql      = implode( ', ', $set_parts );
		$sql          = "UPDATE `{$table}` SET {$set_sql} WHERE stripe_subscription_id = %s";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $wpdb->prepare( $sql, $set_values ) );

		if ( false === $result ) {
			return new WP_Error(
				'mauriel_db_update_failed',
				$wpdb->last_error
			);
		}

		return $result;
	}

	/**
	 * Cancel a subscription by setting its status to 'canceled'.
	 *
	 * @param int $id Subscription ID.
	 * @return int|false|WP_Error Result of the update operation.
	 */
	public static function cancel( int $id ) {
		return self::update_subscription(
			$id,
			[
				'status'              => 'canceled',
				'cancel_at_period_end'=> 0,
			]
		);
	}

	/**
	 * Check whether a listing has an active (or free) subscription.
	 *
	 * @param int $listing_id Listing (post) ID.
	 * @return bool True if the listing's subscription status is 'active' or 'free'.
	 */
	public static function is_active( int $listing_id ): bool {
		$subscription = self::get_by_listing( $listing_id );

		if ( ! $subscription ) {
			return false;
		}

		return in_array( $subscription->status, [ 'active', 'free', 'trialing' ], true );
	}
}
