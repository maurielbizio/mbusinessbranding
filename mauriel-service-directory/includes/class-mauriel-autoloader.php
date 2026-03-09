<?php
/**
 * PSR-0-style autoloader for all Mauriel_ prefixed classes.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Autoloader
 *
 * Resolves class names that begin with "Mauriel_" to PHP files living
 * under MAURIEL_PATH . 'includes/'.  Sub-namespacing is handled by
 * checking each registered sub-directory in priority order.
 */
class Mauriel_Autoloader {

	/**
	 * Sub-directories to search, in priority order.
	 * The autoloader probes each one until it finds the file.
	 *
	 * @var string[]
	 */
	private $subdirs = [
		'db',
		'stripe',
		'ai',
		'search',
		'reviews',
		'leads',
		'analytics',
		'seo',
		'media',
		'coupons',
		'booking',
		'claim',
		'registration',
		'dashboard',
		'shortcodes',
		'rest',
		'admin',
		'post-types',
		'roles',
	];

	/**
	 * Constructor — registers the autoload callback.
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );
	}

	/**
	 * Autoload callback.
	 *
	 * Converts a class name like "Mauriel_DB_Packages" into the file path
	 * "includes/db/class-mauriel-db-packages.php".
	 *
	 * Algorithm:
	 *  1. Bail if the class does not start with "Mauriel_".
	 *  2. Strip the "Mauriel_" prefix.
	 *  3. Lowercase the remainder and replace underscores with hyphens.
	 *  4. Build the filename: "class-mauriel-{slug}.php".
	 *  5. Try the top-level includes/ directory first.
	 *  6. Then try every registered sub-directory.
	 *
	 * @param string $class_name Fully-qualified class name to load.
	 */
	public function autoload( string $class_name ): void {
		// Only handle our own classes.
		if ( strpos( $class_name, 'Mauriel_' ) !== 0 ) {
			return;
		}

		$file = $this->class_name_to_filename( $class_name );

		// 1. Top-level includes/ directory.
		$path = MAURIEL_PATH . 'includes/' . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
			return;
		}

		// 2. Sub-directories.
		foreach ( $this->subdirs as $subdir ) {
			$path = MAURIEL_PATH . 'includes/' . $subdir . '/' . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}

	/**
	 * Convert a class name to its expected filename.
	 *
	 * Examples:
	 *   Mauriel_Core              → class-mauriel-core.php
	 *   Mauriel_DB_Packages       → class-mauriel-db-packages.php
	 *   Mauriel_REST_Listings     → class-mauriel-rest-listings.php
	 *   Mauriel_Post_Type_Listing → class-mauriel-post-type-listing.php
	 *
	 * @param string $class_name Class name starting with "Mauriel_".
	 * @return string Expected filename.
	 */
	private function class_name_to_filename( string $class_name ): string {
		// Strip the leading "Mauriel_" prefix.
		$without_prefix = substr( $class_name, strlen( 'Mauriel_' ) );

		// Lowercase and replace underscores with hyphens.
		$slug = strtolower( str_replace( '_', '-', $without_prefix ) );

		return 'class-mauriel-' . $slug . '.php';
	}
}
