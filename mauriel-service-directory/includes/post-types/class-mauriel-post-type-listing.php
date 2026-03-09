<?php
/**
 * Custom Post Type: mauriel_listing
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Post_Type_Listing
 *
 * Registers the mauriel_listing CPT, its post meta keys (REST-exposed),
 * and custom rewrite handling for city/category URL segments.
 */
class Mauriel_Post_Type_Listing {

	/**
	 * Constructor — hooks registration to WordPress 'init'.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
		add_filter( 'post_type_link', [ $this, 'filter_post_link' ], 10, 2 );
	}

	/**
	 * Register the CPT and all _mauriel_* post meta keys.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = [
			'name'                  => _x( 'Listings', 'post type general name', 'mauriel-service-directory' ),
			'singular_name'         => _x( 'Listing', 'post type singular name', 'mauriel-service-directory' ),
			'menu_name'             => __( 'Service Directory', 'mauriel-service-directory' ),
			'name_admin_bar'        => __( 'Service Listing', 'mauriel-service-directory' ),
			'add_new'               => __( 'Add New', 'mauriel-service-directory' ),
			'add_new_item'          => __( 'Add New Listing', 'mauriel-service-directory' ),
			'new_item'              => __( 'New Listing', 'mauriel-service-directory' ),
			'edit_item'             => __( 'Edit Listing', 'mauriel-service-directory' ),
			'view_item'             => __( 'View Listing', 'mauriel-service-directory' ),
			'all_items'             => __( 'All Listings', 'mauriel-service-directory' ),
			'search_items'          => __( 'Search Listings', 'mauriel-service-directory' ),
			'parent_item_colon'     => __( 'Parent Listing:', 'mauriel-service-directory' ),
			'not_found'             => __( 'No listings found.', 'mauriel-service-directory' ),
			'not_found_in_trash'    => __( 'No listings found in Trash.', 'mauriel-service-directory' ),
			'featured_image'        => __( 'Business Logo', 'mauriel-service-directory' ),
			'set_featured_image'    => __( 'Set business logo', 'mauriel-service-directory' ),
			'remove_featured_image' => __( 'Remove business logo', 'mauriel-service-directory' ),
			'use_featured_image'    => __( 'Use as business logo', 'mauriel-service-directory' ),
			'archives'              => __( 'Listing archives', 'mauriel-service-directory' ),
			'insert_into_item'      => __( 'Insert into listing', 'mauriel-service-directory' ),
			'uploaded_to_this_item' => __( 'Uploaded to this listing', 'mauriel-service-directory' ),
			'items_list'            => __( 'Listings list', 'mauriel-service-directory' ),
			'items_list_navigation' => __( 'Listings list navigation', 'mauriel-service-directory' ),
			'filter_items_list'     => __( 'Filter listings list', 'mauriel-service-directory' ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Local service business listings.', 'mauriel-service-directory' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => [
				'slug'       => 'directory',
				'with_front' => false,
			],
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-store',
			'supports'            => [ 'title', 'editor', 'thumbnail', 'custom-fields', 'excerpt' ],
			'show_in_rest'        => true,
			'rest_base'           => 'mauriel-listings',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'taxonomies'          => [ 'mauriel_category' ],
		];

		register_post_type( 'mauriel_listing', $args );

		// Register all post meta keys so they're available via REST and
		// can be managed via update_post_meta() with full sanitization.
		$this->register_meta_keys();
	}

	/**
	 * Register all _mauriel_* post meta keys with proper schemas.
	 *
	 * @return void
	 */
	private function register_meta_keys(): void {
		$meta_keys = [
			// Contact info.
			'_mauriel_phone'              => [ 'type' => 'string', 'description' => 'Business phone number' ],
			'_mauriel_email'              => [ 'type' => 'string', 'description' => 'Business email address' ],
			'_mauriel_website'            => [ 'type' => 'string', 'description' => 'Business website URL' ],
			// Location.
			'_mauriel_address'            => [ 'type' => 'string', 'description' => 'Street address' ],
			'_mauriel_city'               => [ 'type' => 'string', 'description' => 'City' ],
			'_mauriel_state'              => [ 'type' => 'string', 'description' => 'State / province' ],
			'_mauriel_zip'                => [ 'type' => 'string', 'description' => 'ZIP / postal code' ],
			'_mauriel_country'            => [ 'type' => 'string', 'description' => 'Country code (ISO 3166-1 alpha-2)' ],
			'_mauriel_latitude'           => [ 'type' => 'number', 'description' => 'Latitude coordinate' ],
			'_mauriel_longitude'          => [ 'type' => 'number', 'description' => 'Longitude coordinate' ],
			'_mauriel_service_area'       => [ 'type' => 'string', 'description' => 'Service area description' ],
			'_mauriel_service_area_miles' => [ 'type' => 'integer', 'description' => 'Service radius in miles' ],
			// Social media.
			'_mauriel_facebook'           => [ 'type' => 'string', 'description' => 'Facebook URL' ],
			'_mauriel_instagram'          => [ 'type' => 'string', 'description' => 'Instagram URL' ],
			'_mauriel_twitter'            => [ 'type' => 'string', 'description' => 'Twitter/X URL' ],
			'_mauriel_linkedin'           => [ 'type' => 'string', 'description' => 'LinkedIn URL' ],
			'_mauriel_youtube'            => [ 'type' => 'string', 'description' => 'YouTube URL' ],
			'_mauriel_tiktok'             => [ 'type' => 'string', 'description' => 'TikTok URL' ],
			// Gallery.
			'_mauriel_gallery_ids'        => [ 'type' => 'string', 'description' => 'Comma-separated attachment IDs' ],
			// Listing status / meta.
			'_mauriel_owner_user_id'      => [ 'type' => 'integer', 'description' => 'WordPress user ID of listing owner' ],
			'_mauriel_claimed'            => [ 'type' => 'boolean', 'description' => 'Whether listing has been claimed' ],
			'_mauriel_verified'           => [ 'type' => 'boolean', 'description' => 'Whether listing is verified' ],
			'_mauriel_featured'           => [ 'type' => 'boolean', 'description' => 'Whether listing is featured' ],
			'_mauriel_status'             => [ 'type' => 'string', 'description' => 'Listing status: pending, active, suspended' ],
			'_mauriel_package_id'         => [ 'type' => 'integer', 'description' => 'Active package ID' ],
			// Reviews aggregate (cached).
			'_mauriel_avg_rating'         => [ 'type' => 'number', 'description' => 'Cached average star rating' ],
			'_mauriel_review_count'       => [ 'type' => 'integer', 'description' => 'Cached review count' ],
			// Google.
			'_mauriel_google_place_id'    => [ 'type' => 'string', 'description' => 'Google Places ID' ],
			'_mauriel_google_rating'      => [ 'type' => 'number', 'description' => 'Google rating' ],
			'_mauriel_google_review_count'=> [ 'type' => 'integer', 'description' => 'Google review count' ],
			// AI / SEO.
			'_mauriel_ai_description'     => [ 'type' => 'string', 'description' => 'AI-generated business description' ],
			'_mauriel_seo_title'          => [ 'type' => 'string', 'description' => 'Custom SEO title' ],
			'_mauriel_seo_description'    => [ 'type' => 'string', 'description' => 'Custom SEO meta description' ],
			// Stripe (cached on post for fast reads).
			'_mauriel_stripe_customer_id' => [ 'type' => 'string', 'description' => 'Stripe customer ID' ],
			// Booking.
			'_mauriel_booking_url'        => [ 'type' => 'string', 'description' => 'External booking URL' ],
			'_mauriel_booking_enabled'    => [ 'type' => 'boolean', 'description' => 'Whether booking is enabled' ],
			// Years in business.
			'_mauriel_founded_year'       => [ 'type' => 'integer', 'description' => 'Year business was founded' ],
			// License / insurance.
			'_mauriel_licensed'           => [ 'type' => 'boolean', 'description' => 'Licensed status' ],
			'_mauriel_insured'            => [ 'type' => 'boolean', 'description' => 'Insured status' ],
		];

		foreach ( $meta_keys as $key => $schema ) {
			register_post_meta(
				'mauriel_listing',
				$key,
				[
					'type'              => $schema['type'],
					'description'       => $schema['description'],
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => $this->get_sanitize_callback( $schema['type'] ),
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}
	}

	/**
	 * Return an appropriate sanitize callback for a given meta type.
	 *
	 * @param string $type Meta type (string|integer|number|boolean).
	 * @return callable
	 */
	private function get_sanitize_callback( string $type ): callable {
		switch ( $type ) {
			case 'integer':
				return 'absint';
			case 'number':
				return 'floatval';
			case 'boolean':
				return 'rest_sanitize_boolean';
			default:
				return 'sanitize_text_field';
		}
	}

	/**
	 * Filter the post permalink to include city and category slugs.
	 *
	 * Example output: /directory/{category-slug}/{city-slug}/{post-slug}/
	 *
	 * @param string  $url  Current post URL.
	 * @param WP_Post $post Post object.
	 * @return string Modified URL.
	 */
	public function filter_post_link( string $url, $post ): string {
		if ( 'mauriel_listing' !== $post->post_type ) {
			return $url;
		}

		// Resolve primary category slug.
		$terms = get_the_terms( $post->ID, 'mauriel_category' );
		$category_slug = '';
		if ( $terms && ! is_wp_error( $terms ) ) {
			// Use the first (primary) term.
			$category_slug = $terms[0]->slug;
		}

		// Resolve city slug from post meta.
		$city = (string) get_post_meta( $post->ID, '_mauriel_city', true );
		$city_slug = $city ? sanitize_title( $city ) : '';

		if ( $category_slug && $city_slug ) {
			// Build the enriched URL structure.
			$base_url    = home_url( '/directory/' );
			$url         = $base_url . $category_slug . '/' . $city_slug . '/' . $post->post_name . '/';
		}

		return $url;
	}
}
