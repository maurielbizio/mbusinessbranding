<?php
/**
 * Uninstall script — runs when the plugin is deleted from the WordPress admin.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// ---------------------------------------------------------------------------
// 1. Drop all custom tables
// ---------------------------------------------------------------------------
$tables = [
	'mauriel_coupons',
	'mauriel_business_hours',
	'mauriel_analytics',
	'mauriel_leads',
	'mauriel_subscriptions',
	'mauriel_packages',
];

foreach ( $tables as $table ) {
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare( 'DROP TABLE IF EXISTS `%1s`', $wpdb->prefix . $table )
	);
}

// ---------------------------------------------------------------------------
// 2. Delete all plugin options
// ---------------------------------------------------------------------------
$option_keys = [
	'mauriel_db_version',
	'mauriel_stripe_publishable_key',
	'mauriel_stripe_secret_key',
	'mauriel_stripe_webhook_secret',
	'mauriel_stripe_test_mode',
	'mauriel_google_places_api_key',
	'mauriel_openai_api_key',
	'mauriel_directory_page_id',
	'mauriel_dashboard_page_id',
	'mauriel_register_page_id',
	'mauriel_listings_per_page',
	'mauriel_default_radius_miles',
	'mauriel_currency',
	'mauriel_currency_symbol',
	'mauriel_email_admin',
	'mauriel_email_from_name',
	'mauriel_email_from_address',
	'mauriel_recaptcha_site_key',
	'mauriel_recaptcha_secret_key',
	'mauriel_seo_title_template',
	'mauriel_seo_description_template',
	'mauriel_smtp_host',
	'mauriel_smtp_port',
	'mauriel_smtp_user',
	'mauriel_smtp_pass',
	'mauriel_smtp_encryption',
	'mauriel_enable_ai_descriptions',
	'mauriel_enable_coupons',
	'mauriel_enable_reviews',
	'mauriel_enable_analytics',
	'mauriel_enable_booking',
	'mauriel_social_facebook',
	'mauriel_social_instagram',
	'mauriel_social_linkedin',
	'mauriel_active_tab',
];

foreach ( $option_keys as $key ) {
	delete_option( $key );
}

// Also wipe any dynamically-named mauriel_ options stored via autoload
$dynamic_options = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'mauriel_%'"
);
foreach ( (array) $dynamic_options as $opt ) {
	delete_option( $opt );
}

// ---------------------------------------------------------------------------
// 3. Remove custom user roles
// ---------------------------------------------------------------------------
remove_role( 'mauriel_business_owner' );
remove_role( 'mauriel_directory_admin' );

// ---------------------------------------------------------------------------
// 4. Delete all post meta with _mauriel_ prefix
// ---------------------------------------------------------------------------
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\_mauriel\_%'"
);

// ---------------------------------------------------------------------------
// 5. Delete all posts of type mauriel_listing (and their meta / term relationships)
// ---------------------------------------------------------------------------
$listing_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mauriel_listing'"
);

if ( ! empty( $listing_ids ) ) {
	$ids_placeholder = implode( ',', array_map( 'intval', $listing_ids ) );

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$ids_placeholder})"
	);

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ({$ids_placeholder})"
	);

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"DELETE FROM {$wpdb->posts} WHERE ID IN ({$ids_placeholder})"
	);
}

// ---------------------------------------------------------------------------
// 6. Clean up orphaned term taxonomy entries for mauriel_category
// ---------------------------------------------------------------------------
$term_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	"SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'mauriel_category'"
);

if ( ! empty( $term_ids ) ) {
	$ids_placeholder = implode( ',', array_map( 'intval', $term_ids ) );

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'mauriel_category'"
	);

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"DELETE FROM {$wpdb->terms} WHERE term_id IN ({$ids_placeholder})"
	);

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"DELETE FROM {$wpdb->termmeta} WHERE term_id IN ({$ids_placeholder})"
	);
}
