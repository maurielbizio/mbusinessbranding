<?php
/**
 * Role management helper.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Roles
 *
 * Provides static helpers to register and remove the plugin's custom
 * WordPress user roles without duplicating the capability definitions.
 */
class Mauriel_Roles {

	/**
	 * Register both custom roles if they do not already exist.
	 *
	 * Safe to call multiple times — add_role() is a no-op when the role
	 * is already present.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( null === get_role( 'mauriel_business_owner' ) ) {
			add_role(
				'mauriel_business_owner',
				__( 'Business Owner', 'mauriel-service-directory' ),
				self::get_business_owner_caps()
			);
		}

		if ( null === get_role( 'mauriel_directory_admin' ) ) {
			add_role(
				'mauriel_directory_admin',
				__( 'Directory Admin', 'mauriel-service-directory' ),
				self::get_directory_admin_caps()
			);
		}
	}

	/**
	 * Remove both custom roles.
	 *
	 * Called during uninstall.  Users assigned to these roles will fall back
	 * to the 'subscriber' role automatically.
	 *
	 * @return void
	 */
	public static function remove(): void {
		remove_role( 'mauriel_business_owner' );
		remove_role( 'mauriel_directory_admin' );
	}

	/**
	 * Return the capability map for the Business Owner role.
	 *
	 * @return bool[] Map of capability => true.
	 */
	public static function get_business_owner_caps(): array {
		return [
			// WordPress core.
			'read'                          => true,
			'edit_posts'                    => true,
			'upload_files'                  => true,

			// Plugin-specific — own listing only (map_meta_cap handles ownership checks).
			'edit_mauriel_listing'          => true,
			'read_mauriel_listing'          => true,
			'delete_mauriel_listing'        => false,

			// Lead / analytics access for own listing.
			'view_mauriel_leads'            => true,
			'view_mauriel_analytics'        => true,

			// Coupons / hours / media for own listing.
			'manage_mauriel_coupons'        => true,
			'manage_mauriel_hours'          => true,
			'manage_mauriel_media'          => true,

			// Reviews — can respond, cannot moderate.
			'respond_mauriel_reviews'       => true,
		];
	}

	/**
	 * Return the capability map for the Directory Admin role.
	 *
	 * Inherits all Business Owner caps and adds site-wide admin capabilities.
	 *
	 * @return bool[] Map of capability => true.
	 */
	public static function get_directory_admin_caps(): array {
		$base = self::get_business_owner_caps();

		$admin_caps = [
			// WordPress core admin.
			'manage_options'                => true,
			'edit_others_posts'             => true,
			'publish_posts'                 => true,
			'delete_posts'                  => true,
			'delete_others_posts'           => true,
			'read_private_posts'            => true,
			'edit_private_posts'            => true,
			'manage_categories'             => true,

			// Plugin-specific admin.
			'manage_mauriel_listings'       => true,
			'approve_mauriel_listings'      => true,
			'reject_mauriel_listings'       => true,
			'manage_mauriel_packages'       => true,
			'manage_mauriel_subscriptions'  => true,
			'manage_mauriel_reviews'        => true,
			'view_all_mauriel_leads'        => true,
			'view_all_mauriel_analytics'    => true,
			'manage_mauriel_settings'       => true,

			// Overrides from business owner that should be true for admin.
			'delete_mauriel_listing'        => true,
		];

		return array_merge( $base, $admin_caps );
	}
}
