<?php
/**
 * Database abstraction layer.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_DB
 *
 * Thin static wrapper around $wpdb providing consistent error handling,
 * table name resolution, and a unified interface for all plugin DB operations.
 */
class Mauriel_DB {

	/**
	 * Resolve a plugin table name from its short name.
	 *
	 * Example: Mauriel_DB::table('packages') → 'wp_mauriel_packages'
	 *
	 * @param string $name Short table name (e.g. 'packages', 'leads').
	 * @return string Full table name including WP and plugin prefixes.
	 */
	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'mauriel_' . $name;
	}

	/**
	 * Insert a row into a plugin table.
	 *
	 * @param string      $table  Short table name (passed through self::table()).
	 * @param array       $data   Column => value pairs.
	 * @param string[]|null $format Optional sprintf format strings for each value.
	 * @return int|WP_Error Insert ID on success, WP_Error on failure.
	 */
	public static function insert( string $table, array $data, ?array $format = null ) {
		global $wpdb;

		$full_table = self::table( $table );
		$result     = $wpdb->insert( $full_table, $data, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error(
				'mauriel_db_insert_failed',
				sprintf(
					/* translators: 1: table name, 2: database error */
					__( 'Failed to insert into %1$s: %2$s', 'mauriel-service-directory' ),
					$full_table,
					$wpdb->last_error
				)
			);
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update rows in a plugin table.
	 *
	 * @param string        $table        Short table name.
	 * @param array         $data         Column => value pairs to set.
	 * @param array         $where        Column => value pairs for WHERE clause.
	 * @param string[]|null $format       Format strings for $data values.
	 * @param string[]|null $where_format Format strings for $where values.
	 * @return int|false|WP_Error Number of rows updated, 0 if nothing changed, false/WP_Error on failure.
	 */
	public static function update( string $table, array $data, array $where, ?array $format = null, ?array $where_format = null ) {
		global $wpdb;

		$full_table = self::table( $table );
		$result     = $wpdb->update( $full_table, $data, $where, $format, $where_format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error(
				'mauriel_db_update_failed',
				sprintf(
					/* translators: 1: table name, 2: database error */
					__( 'Failed to update %1$s: %2$s', 'mauriel-service-directory' ),
					$full_table,
					$wpdb->last_error
				)
			);
		}

		return $result;
	}

	/**
	 * Delete rows from a plugin table.
	 *
	 * @param string        $table        Short table name.
	 * @param array         $where        Column => value pairs for WHERE clause.
	 * @param string[]|null $where_format Format strings for $where values.
	 * @return int|false|WP_Error Number of rows deleted, false/WP_Error on failure.
	 */
	public static function delete( string $table, array $where, ?array $where_format = null ) {
		global $wpdb;

		$full_table = self::table( $table );
		$result     = $wpdb->delete( $full_table, $where, $where_format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		if ( false === $result ) {
			return new WP_Error(
				'mauriel_db_delete_failed',
				sprintf(
					/* translators: 1: table name, 2: database error */
					__( 'Failed to delete from %1$s: %2$s', 'mauriel-service-directory' ),
					$full_table,
					$wpdb->last_error
				)
			);
		}

		return $result;
	}

	/**
	 * Fetch a single row from a plugin table by an arbitrary WHERE map.
	 *
	 * @param string $table Short table name.
	 * @param array  $where Column => value pairs (all joined with AND).
	 * @return object|null Row object or null if not found.
	 */
	public static function get_row( string $table, array $where ): ?object {
		global $wpdb;

		$full_table = self::table( $table );

		$conditions = [];
		$values     = [];

		foreach ( $where as $column => $value ) {
			if ( is_int( $value ) || is_bool( $value ) ) {
				$conditions[] = '`' . esc_sql( $column ) . '` = %d';
				$values[]     = (int) $value;
			} elseif ( is_float( $value ) ) {
				$conditions[] = '`' . esc_sql( $column ) . '` = %f';
				$values[]     = $value;
			} else {
				$conditions[] = '`' . esc_sql( $column ) . '` = %s';
				$values[]     = $value;
			}
		}

		$where_sql = implode( ' AND ', $conditions );
		$sql       = "SELECT * FROM `{$full_table}` WHERE {$where_sql} LIMIT 1";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_row( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * Execute a fully-prepared SQL query and return all result rows.
	 *
	 * The caller is responsible for preparing the SQL safely before passing it.
	 *
	 * @param string $sql Prepared SQL query string.
	 * @return array Array of row objects (may be empty).
	 */
	public static function get_results( string $sql ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return (array) $wpdb->get_results( $sql );
	}

	/**
	 * Execute a fully-prepared SQL query and return a single scalar value.
	 *
	 * The caller is responsible for preparing the SQL safely before passing it.
	 *
	 * @param string $sql Prepared SQL query string.
	 * @return string|null The scalar value, or null if not found.
	 */
	public static function get_var( string $sql ): ?string {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_var( $sql );
		return null !== $result ? (string) $result : null;
	}
}
