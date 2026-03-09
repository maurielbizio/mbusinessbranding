<?php
/**
 * Plugin deactivation routines.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Deactivator
 *
 * Handles cleanup tasks performed when the plugin is deactivated.
 * Note: destructive actions (table drops, data deletion) belong in
 * uninstall.php — deactivation should be non-destructive.
 */
class Mauriel_Deactivator {

	/**
	 * Run all deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear any scheduled cron events registered by the plugin.
		$cron_hooks = [
			'mauriel_daily_cleanup',
			'mauriel_subscription_renewal_check',
			'mauriel_analytics_aggregate',
			'mauriel_review_digest',
		];

		foreach ( $cron_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}

		// Flush rewrite rules so our CPT slugs are removed from the cache.
		flush_rewrite_rules();
	}
}
