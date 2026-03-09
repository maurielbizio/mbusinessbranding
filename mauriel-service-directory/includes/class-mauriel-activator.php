<?php
/**
 * Plugin activation routines.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Activator
 *
 * Handles all one-time setup tasks that run when the plugin is activated:
 * table creation, role registration, page creation, and default data seeding.
 */
class Mauriel_Activator {

	/**
	 * Run all activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::register_roles();
		self::create_pages();
		self::seed_default_packages();

		update_option( 'mauriel_db_version', '1.0.0' );
		flush_rewrite_rules( false );
	}

	// -----------------------------------------------------------------------
	// Table creation
	// -----------------------------------------------------------------------

	/**
	 * Create (or upgrade) all six custom database tables using dbDelta().
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$p               = $wpdb->prefix; // shorthand for table prefix

		// ------------------------------------------------------------------
		// 1. mauriel_packages
		// ------------------------------------------------------------------
		$sql_packages = "CREATE TABLE {$p}mauriel_packages (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			slug VARCHAR(100) NOT NULL,
			description TEXT,
			price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			price_yearly DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			stripe_price_id_monthly VARCHAR(100) DEFAULT NULL,
			stripe_price_id_yearly VARCHAR(100) DEFAULT NULL,
			stripe_product_id VARCHAR(100) DEFAULT NULL,
			features LONGTEXT,
			photo_limit TINYINT NOT NULL DEFAULT 3,
			is_featured TINYINT(1) NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			sort_order TINYINT NOT NULL DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uq_slug (slug)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 2. mauriel_subscriptions
		// ------------------------------------------------------------------
		$sql_subscriptions = "CREATE TABLE {$p}mauriel_subscriptions (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			listing_id BIGINT UNSIGNED NOT NULL,
			package_id BIGINT UNSIGNED NOT NULL,
			stripe_customer_id VARCHAR(100) DEFAULT NULL,
			stripe_subscription_id VARCHAR(100) DEFAULT NULL,
			status ENUM('active','trialing','past_due','canceled','paused','free') NOT NULL DEFAULT 'free',
			billing_interval ENUM('monthly','yearly','none') NOT NULL DEFAULT 'none',
			current_period_start DATETIME DEFAULT NULL,
			current_period_end DATETIME DEFAULT NULL,
			cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME DEFAULT NULL,
			updated_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uq_stripe_sub (stripe_subscription_id),
			KEY idx_user_id (user_id),
			KEY idx_listing_id (listing_id)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 3. mauriel_leads
		// ------------------------------------------------------------------
		$sql_leads = "CREATE TABLE {$p}mauriel_leads (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			listing_id BIGINT UNSIGNED NOT NULL,
			lead_type ENUM('contact','quote','phone_click','email_click') NOT NULL,
			name VARCHAR(150) DEFAULT NULL,
			email VARCHAR(200) DEFAULT NULL,
			phone VARCHAR(30) DEFAULT NULL,
			message TEXT,
			ip_hash VARCHAR(64) DEFAULT NULL,
			status ENUM('new','read','archived') NOT NULL DEFAULT 'new',
			created_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY idx_listing_id (listing_id)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 4. mauriel_analytics
		// ------------------------------------------------------------------
		$sql_analytics = "CREATE TABLE {$p}mauriel_analytics (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			listing_id BIGINT UNSIGNED NOT NULL,
			event_type ENUM('view','impression','click','phone_click','direction_click','website_click','lead_submit') NOT NULL,
			session_hash VARCHAR(64) DEFAULT NULL,
			referrer VARCHAR(500) DEFAULT NULL,
			search_term VARCHAR(255) DEFAULT NULL,
			recorded_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY idx_listing_event (listing_id, event_type),
			UNIQUE KEY idx_session_dedup (session_hash, event_type, listing_id)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 5. mauriel_business_hours
		// ------------------------------------------------------------------
		$sql_hours = "CREATE TABLE {$p}mauriel_business_hours (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			listing_id BIGINT UNSIGNED NOT NULL,
			day_of_week TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
			is_open TINYINT(1) NOT NULL DEFAULT 1,
			open_time TIME DEFAULT NULL,
			close_time TIME DEFAULT NULL,
			is_24_hours TINYINT(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			UNIQUE KEY idx_listing_day (listing_id, day_of_week)
		) $charset_collate;";

		// ------------------------------------------------------------------
		// 6. mauriel_coupons
		// ------------------------------------------------------------------
		$sql_coupons = "CREATE TABLE {$p}mauriel_coupons (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			listing_id BIGINT UNSIGNED NOT NULL,
			title VARCHAR(200) NOT NULL,
			description TEXT,
			coupon_code VARCHAR(50) DEFAULT NULL,
			discount_type ENUM('percent','fixed','free_service','other') NOT NULL DEFAULT 'percent',
			discount_value DECIMAL(10,2) DEFAULT NULL,
			starts_at DATETIME DEFAULT NULL,
			expires_at DATETIME DEFAULT NULL,
			max_uses INT DEFAULT NULL,
			use_count INT NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY idx_listing_id (listing_id),
			KEY idx_expires_at (expires_at)
		) $charset_collate;";

		dbDelta( $sql_packages );
		dbDelta( $sql_subscriptions );
		dbDelta( $sql_leads );
		dbDelta( $sql_analytics );
		dbDelta( $sql_hours );
		dbDelta( $sql_coupons );
	}

	// -----------------------------------------------------------------------
	// Roles
	// -----------------------------------------------------------------------

	/**
	 * Register custom user roles.
	 *
	 * @return void
	 */
	private static function register_roles(): void {
		// Business owner — can manage their own listing.
		if ( null === get_role( 'mauriel_business_owner' ) ) {
			add_role(
				'mauriel_business_owner',
				__( 'Business Owner', 'mauriel-service-directory' ),
				[
					'read'                 => true,
					'edit_posts'           => true,
					'upload_files'         => true,
					'edit_mauriel_listing' => true,
				]
			);
		}

		// Directory admin — can manage the whole directory.
		if ( null === get_role( 'mauriel_directory_admin' ) ) {
			add_role(
				'mauriel_directory_admin',
				__( 'Directory Admin', 'mauriel-service-directory' ),
				[
					// WordPress core.
					'read'                         => true,
					'edit_posts'                   => true,
					'upload_files'                 => true,
					'manage_options'               => true,
					// Business owner capabilities.
					'edit_mauriel_listing'         => true,
					// Directory-level capabilities.
					'manage_mauriel_listings'      => true,
					'approve_mauriel_listings'     => true,
					'manage_mauriel_packages'      => true,
					'manage_mauriel_reviews'       => true,
				]
			);
		}
	}

	// -----------------------------------------------------------------------
	// Pages
	// -----------------------------------------------------------------------

	/**
	 * Create required plugin pages if they do not already exist.
	 *
	 * @return void
	 */
	private static function create_pages(): void {
		$pages = [
			[
				'option'    => 'mauriel_directory_page_id',
				'title'     => __( 'Service Directory', 'mauriel-service-directory' ),
				'slug'      => 'directory',
				'content'   => '[mauriel_directory]',
			],
			[
				'option'    => 'mauriel_dashboard_page_id',
				'title'     => __( 'Business Dashboard', 'mauriel-service-directory' ),
				'slug'      => 'business-dashboard',
				'content'   => '[mauriel_dashboard]',
			],
			[
				'option'    => 'mauriel_register_page_id',
				'title'     => __( 'Business Register', 'mauriel-service-directory' ),
				'slug'      => 'business-register',
				'content'   => '[mauriel_dashboard]',
			],
		];

		foreach ( $pages as $page ) {
			$existing_id = (int) get_option( $page['option'], 0 );

			// If we already have a valid stored page ID, skip.
			if ( $existing_id > 0 && get_post( $existing_id ) ) {
				continue;
			}

			// Check whether a page with this slug already exists.
			$existing_page = get_page_by_path( $page['slug'] );
			if ( $existing_page ) {
				update_option( $page['option'], $existing_page->ID );
				continue;
			}

			// Create the page.
			$page_id = wp_insert_post(
				[
					'post_title'     => $page['title'],
					'post_name'      => $page['slug'],
					'post_content'   => $page['content'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				],
				true
			);

			if ( ! is_wp_error( $page_id ) ) {
				update_option( $page['option'], $page_id );
			}
		}
	}

	// -----------------------------------------------------------------------
	// Default data
	// -----------------------------------------------------------------------

	/**
	 * Seed the four default listing packages if the table is empty.
	 *
	 * @return void
	 */
	private static function seed_default_packages(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'mauriel_packages';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		if ( $count > 0 ) {
			return;
		}

		$packages = [
			[
				'name'          => 'Free',
				'slug'          => 'free',
				'description'   => 'Get your business listed at no cost.',
				'price_monthly' => 0.00,
				'price_yearly'  => 0.00,
				'features'      => wp_json_encode( [
					'Basic listing',
					'Up to 3 photos',
					'Contact form',
					'Business hours',
				] ),
				'photo_limit'   => 3,
				'is_featured'   => 0,
				'is_active'     => 1,
				'sort_order'    => 0,
			],
			[
				'name'          => 'Basic',
				'slug'          => 'basic',
				'description'   => 'Everything in Free plus enhanced visibility.',
				'price_monthly' => 29.00,
				'price_yearly'  => 290.00,
				'features'      => wp_json_encode( [
					'Everything in Free',
					'Up to 3 photos',
					'Priority listing placement',
					'Lead notifications via email',
					'Coupon builder',
					'Analytics dashboard',
				] ),
				'photo_limit'   => 3,
				'is_featured'   => 0,
				'is_active'     => 1,
				'sort_order'    => 1,
			],
			[
				'name'          => 'Pro',
				'slug'          => 'pro',
				'description'   => 'Featured placement and advanced tools.',
				'price_monthly' => 59.00,
				'price_yearly'  => 590.00,
				'features'      => wp_json_encode( [
					'Everything in Basic',
					'Up to 20 photos',
					'Featured badge',
					'AI-generated description',
					'Review management',
					'Advanced analytics',
					'Social media links',
				] ),
				'photo_limit'   => 20,
				'is_featured'   => 1,
				'is_active'     => 1,
				'sort_order'    => 2,
			],
			[
				'name'          => 'Premium',
				'slug'          => 'premium',
				'description'   => 'Maximum exposure and full-featured tools.',
				'price_monthly' => 99.00,
				'price_yearly'  => 990.00,
				'features'      => wp_json_encode( [
					'Everything in Pro',
					'Unlimited photos',
					'Top-of-page featured placement',
					'AI receptionist (24/7 booking)',
					'Reputation management',
					'Social media creation & posting',
					'Dedicated account manager',
				] ),
				'photo_limit'   => 99,
				'is_featured'   => 1,
				'is_active'     => 1,
				'sort_order'    => 3,
			],
		];

		$now = current_time( 'mysql' );

		foreach ( $packages as $package ) {
			$package['created_at'] = $now;
			$package['updated_at'] = $now;

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				$package,
				[
					'%s', // name
					'%s', // slug
					'%s', // description
					'%f', // price_monthly
					'%f', // price_yearly
					'%s', // features
					'%d', // photo_limit
					'%d', // is_featured
					'%d', // is_active
					'%d', // sort_order
					'%s', // created_at
					'%s', // updated_at
				]
			);
		}
	}
}
