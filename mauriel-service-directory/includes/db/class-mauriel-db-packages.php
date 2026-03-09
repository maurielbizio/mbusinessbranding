<?php
/**
 * DB operations for the mauriel_packages table.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_DB_Packages
 *
 * Provides all CRUD operations for listing subscription packages.
 */
class Mauriel_DB_Packages {

	/**
	 * Short table identifier used with Mauriel_DB::table().
	 *
	 * @var string
	 */
	private const TABLE = 'packages';

	/**
	 * Retrieve all packages, optionally filtered to active only.
	 *
	 * @param bool $active_only When true, only returns rows where is_active = 1.
	 * @return object[] Array of package row objects.
	 */
	public static function get_all( bool $active_only = true ): array {
		global $wpdb;

		$table = Mauriel_DB::table( self::TABLE );
		$where = $active_only ? 'WHERE is_active = 1 ' : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results( "SELECT * FROM `{$table}` {$where}ORDER BY sort_order ASC, id ASC" );
	}

	/**
	 * Retrieve a single package by its primary key.
	 *
	 * @param int $id Package ID.
	 * @return object|null Package row object, or null if not found.
	 */
	public static function get( int $id ): ?object {
		return Mauriel_DB::get_row( self::TABLE, [ 'id' => $id ] ) ?: null;
	}

	/**
	 * Retrieve a single package by its unique slug.
	 *
	 * @param string $slug Package slug (e.g. 'free', 'pro').
	 * @return object|null Package row object, or null if not found.
	 */
	public static function get_by_slug( string $slug ): ?object {
		return Mauriel_DB::get_row( self::TABLE, [ 'slug' => sanitize_key( $slug ) ] ) ?: null;
	}

	/**
	 * Insert a new package row.
	 *
	 * @param array $data Column => value pairs. Required: name, slug, price_monthly.
	 * @return int|WP_Error New package ID on success, WP_Error on failure.
	 */
	public static function create( array $data ) {
		$now = current_time( 'mysql' );

		$defaults = [
			'price_monthly' => 0.00,
			'price_yearly'  => 0.00,
			'photo_limit'   => 3,
			'is_featured'   => 0,
			'is_active'     => 1,
			'sort_order'    => 0,
			'created_at'    => $now,
			'updated_at'    => $now,
		];

		$data = wp_parse_args( $data, $defaults );

		// Ensure slug is URL-safe.
		if ( isset( $data['slug'] ) ) {
			$data['slug'] = sanitize_key( $data['slug'] );
		}

		return Mauriel_DB::insert( self::TABLE, $data );
	}

	/**
	 * Update an existing package.
	 *
	 * @param int   $id   Package ID to update.
	 * @param array $data Column => value pairs to set.
	 * @return int|false|WP_Error Rows affected, 0 if unchanged, false/WP_Error on failure.
	 */
	public static function update_package( int $id, array $data ) {
		$data['updated_at'] = current_time( 'mysql' );

		if ( isset( $data['slug'] ) ) {
			$data['slug'] = sanitize_key( $data['slug'] );
		}

		return Mauriel_DB::update( self::TABLE, $data, [ 'id' => $id ] );
	}

	/**
	 * Delete a package by ID.
	 *
	 * Note: consider soft-deleting (is_active = 0) rather than hard-deleting
	 * packages that have existing subscriptions.
	 *
	 * @param int $id Package ID to delete.
	 * @return int|false|WP_Error Rows deleted, false/WP_Error on failure.
	 */
	public static function delete_package( int $id ) {
		return Mauriel_DB::delete( self::TABLE, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Return all active packages ordered by sort_order for frontend display.
	 *
	 * Alias for get_all(true) with an explicit sort guarantee.
	 *
	 * @return object[] Array of active package row objects ordered by sort_order ASC.
	 */
	public static function get_active_packages(): array {
		return self::get_all( true );
	}
}
