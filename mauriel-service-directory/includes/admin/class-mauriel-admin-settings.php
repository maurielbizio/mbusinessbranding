<?php
/**
 * Admin Settings Page
 *
 * Full WordPress Settings API implementation with tabbed interface
 * covering General, Stripe, Google, AI, SEO, and Email configuration.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Admin_Settings
 */
class Mauriel_Admin_Settings {

	/**
	 * Available tabs.
	 *
	 * @var array
	 */
	private $tabs = array();

	/**
	 * Currently active tab slug.
	 *
	 * @var string
	 */
	private $current_tab = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->tabs = array(
			'general' => __( 'General', 'mauriel-service-directory' ),
			'stripe'  => __( 'Stripe', 'mauriel-service-directory' ),
			'google'  => __( 'Google', 'mauriel-service-directory' ),
			'ai'      => __( 'AI', 'mauriel-service-directory' ),
			'seo'     => __( 'SEO', 'mauriel-service-directory' ),
			'email'   => __( 'Email', 'mauriel-service-directory' ),
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->current_tab = isset( $_GET['tab'] ) && array_key_exists( sanitize_key( $_GET['tab'] ), $this->tabs )
			? sanitize_key( $_GET['tab'] )
			: 'general';

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	// -----------------------------------------------------------------------
	// Settings Registration
	// -----------------------------------------------------------------------

	/**
	 * Register all settings, sections, and fields via the Settings API.
	 */
	public function register_settings() {
		$this->register_general_settings();
		$this->register_stripe_settings();
		$this->register_google_settings();
		$this->register_ai_settings();
		$this->register_seo_settings();
		$this->register_email_settings();
	}

	// ---- General ----------------------------------------------------------

	/**
	 * Register general tab settings.
	 */
	private function register_general_settings() {
		$section = 'mauriel_general_section';
		$page    = 'mauriel_settings_general';

		add_settings_section( $section, '', '__return_false', $page );

		// Directory Page
		register_setting( $page, 'mauriel_directory_page_id', array( 'sanitize_callback' => 'absint' ) );
		add_settings_field(
			'mauriel_directory_page_id',
			__( 'Directory Page', 'mauriel-service-directory' ),
			array( $this, 'field_page_select' ),
			$page,
			$section,
			array( 'option' => 'mauriel_directory_page_id', 'description' => __( 'Page displaying the business directory listing.', 'mauriel-service-directory' ) )
		);

		// Dashboard Page
		register_setting( $page, 'mauriel_dashboard_page_id', array( 'sanitize_callback' => 'absint' ) );
		add_settings_field(
			'mauriel_dashboard_page_id',
			__( 'Business Dashboard Page', 'mauriel-service-directory' ),
			array( $this, 'field_page_select' ),
			$page,
			$section,
			array( 'option' => 'mauriel_dashboard_page_id', 'description' => __( 'Page for the business owner dashboard.', 'mauriel-service-directory' ) )
		);

		// Register Page
		register_setting( $page, 'mauriel_register_page_id', array( 'sanitize_callback' => 'absint' ) );
		add_settings_field(
			'mauriel_register_page_id',
			__( 'Register Page', 'mauriel-service-directory' ),
			array( $this, 'field_page_select' ),
			$page,
			$section,
			array( 'option' => 'mauriel_register_page_id', 'description' => __( 'Page for new business owner registration.', 'mauriel-service-directory' ) )
		);

		// Listings Per Page
		register_setting( $page, 'mauriel_listings_per_page', array( 'sanitize_callback' => array( $this, 'sanitize_positive_int' ), 'default' => 12 ) );
		add_settings_field(
			'mauriel_listings_per_page',
			__( 'Listings Per Page', 'mauriel-service-directory' ),
			array( $this, 'field_number' ),
			$page,
			$section,
			array( 'option' => 'mauriel_listings_per_page', 'default' => 12, 'min' => 1, 'max' => 100 )
		);

		// Auto-approve Listings
		register_setting( $page, 'mauriel_auto_approve_listings', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );
		add_settings_field(
			'mauriel_auto_approve_listings',
			__( 'Auto-Approve Listings', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_auto_approve_listings', 'description' => __( 'Publish new listings without admin review.', 'mauriel-service-directory' ) )
		);

		// Auto-approve Reviews
		register_setting( $page, 'mauriel_auto_approve_reviews', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );
		add_settings_field(
			'mauriel_auto_approve_reviews',
			__( 'Auto-Approve Reviews', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_auto_approve_reviews', 'description' => __( 'Publish reviews without moderation.', 'mauriel-service-directory' ) )
		);

		// Guest Reviews
		register_setting( $page, 'mauriel_guest_reviews', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ), 'default' => 1 ) );
		add_settings_field(
			'mauriel_guest_reviews',
			__( 'Allow Guest Reviews', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_guest_reviews', 'default' => 1, 'description' => __( 'Allow non-logged-in users to submit reviews.', 'mauriel-service-directory' ) )
		);

		// Default Sort
		register_setting( $page, 'mauriel_default_sort', array( 'sanitize_callback' => array( $this, 'sanitize_sort' ) ) );
		add_settings_field(
			'mauriel_default_sort',
			__( 'Default Sort Order', 'mauriel-service-directory' ),
			array( $this, 'field_select' ),
			$page,
			$section,
			array(
				'option'  => 'mauriel_default_sort',
				'default' => 'featured',
				'options' => array(
					'featured' => __( 'Featured', 'mauriel-service-directory' ),
					'rating'   => __( 'Top Rated', 'mauriel-service-directory' ),
					'newest'   => __( 'Newest', 'mauriel-service-directory' ),
					'az'       => __( 'A–Z', 'mauriel-service-directory' ),
				),
			)
		);

		// Currency
		register_setting( $page, 'mauriel_currency', array( 'sanitize_callback' => array( $this, 'sanitize_currency' ), 'default' => 'USD' ) );
		add_settings_field(
			'mauriel_currency',
			__( 'Currency', 'mauriel-service-directory' ),
			array( $this, 'field_select' ),
			$page,
			$section,
			array(
				'option'  => 'mauriel_currency',
				'default' => 'USD',
				'options' => array(
					'USD' => __( 'USD — US Dollar', 'mauriel-service-directory' ),
					'CAD' => __( 'CAD — Canadian Dollar', 'mauriel-service-directory' ),
					'GBP' => __( 'GBP — British Pound', 'mauriel-service-directory' ),
					'EUR' => __( 'EUR — Euro', 'mauriel-service-directory' ),
				),
			)
		);

		// Distance Unit
		register_setting( $page, 'mauriel_distance_unit', array( 'sanitize_callback' => array( $this, 'sanitize_distance_unit' ), 'default' => 'miles' ) );
		add_settings_field(
			'mauriel_distance_unit',
			__( 'Distance Unit', 'mauriel-service-directory' ),
			array( $this, 'field_radio' ),
			$page,
			$section,
			array(
				'option'  => 'mauriel_distance_unit',
				'default' => 'miles',
				'options' => array(
					'miles' => __( 'Miles', 'mauriel-service-directory' ),
					'km'    => __( 'Kilometers', 'mauriel-service-directory' ),
				),
			)
		);

		// Admin Email
		register_setting( $page, 'mauriel_admin_email', array( 'sanitize_callback' => 'sanitize_email' ) );
		add_settings_field(
			'mauriel_admin_email',
			__( 'Admin Notification Email', 'mauriel-service-directory' ),
			array( $this, 'field_email' ),
			$page,
			$section,
			array( 'option' => 'mauriel_admin_email', 'description' => __( 'Email address to receive admin notifications.', 'mauriel-service-directory' ) )
		);
	}

	// ---- Stripe -----------------------------------------------------------

	/**
	 * Register Stripe tab settings.
	 */
	private function register_stripe_settings() {
		$section = 'mauriel_stripe_section';
		$page    = 'mauriel_settings_stripe';

		add_settings_section( $section, '', '__return_false', $page );

		register_setting( $page, 'mauriel_stripe_mode', array( 'sanitize_callback' => array( $this, 'sanitize_stripe_mode' ), 'default' => 'test' ) );
		add_settings_field(
			'mauriel_stripe_mode',
			__( 'Stripe Mode', 'mauriel-service-directory' ),
			array( $this, 'field_radio' ),
			$page,
			$section,
			array(
				'option'  => 'mauriel_stripe_mode',
				'default' => 'test',
				'options' => array(
					'test' => __( 'Test', 'mauriel-service-directory' ),
					'live' => __( 'Live', 'mauriel-service-directory' ),
				),
			)
		);

		$stripe_text_fields = array(
			'mauriel_stripe_pub_key_test'    => __( 'Test Publishable Key', 'mauriel-service-directory' ),
			'mauriel_stripe_secret_key_test' => __( 'Test Secret Key', 'mauriel-service-directory' ),
			'mauriel_stripe_pub_key_live'    => __( 'Live Publishable Key', 'mauriel-service-directory' ),
			'mauriel_stripe_secret_key_live' => __( 'Live Secret Key', 'mauriel-service-directory' ),
			'mauriel_stripe_webhook_secret'  => __( 'Webhook Signing Secret', 'mauriel-service-directory' ),
		);

		foreach ( $stripe_text_fields as $option => $label ) {
			register_setting( $page, $option, array( 'sanitize_callback' => 'sanitize_text_field' ) );
			add_settings_field(
				$option,
				$label,
				array( $this, 'field_password' ),
				$page,
				$section,
				array( 'option' => $option )
			);
		}

		register_setting( $page, 'mauriel_stripe_customer_portal', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );
		add_settings_field(
			'mauriel_stripe_customer_portal',
			__( 'Customer Portal', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_stripe_customer_portal', 'description' => __( 'Enable Stripe customer portal link in the business dashboard.', 'mauriel-service-directory' ) )
		);
	}

	// ---- Google -----------------------------------------------------------

	/**
	 * Register Google tab settings.
	 */
	private function register_google_settings() {
		$section = 'mauriel_google_section';
		$page    = 'mauriel_settings_google';

		add_settings_section( $section, '', '__return_false', $page );

		$google_text_fields = array(
			'mauriel_google_maps_key'      => __( 'Maps JavaScript API Key', 'mauriel-service-directory' ),
			'mauriel_google_geocoding_key' => __( 'Geocoding API Key', 'mauriel-service-directory' ),
			'mauriel_google_places_key'    => __( 'Places API Key', 'mauriel-service-directory' ),
		);

		foreach ( $google_text_fields as $option => $label ) {
			register_setting( $page, $option, array( 'sanitize_callback' => 'sanitize_text_field' ) );
			add_settings_field(
				$option,
				$label,
				array( $this, 'field_text' ),
				$page,
				$section,
				array( 'option' => $option )
			);
		}

		// Map defaults
		register_setting( $page, 'mauriel_map_default_lat', array( 'sanitize_callback' => array( $this, 'sanitize_float' ), 'default' => 39.5 ) );
		add_settings_field(
			'mauriel_map_default_lat',
			__( 'Default Map Latitude', 'mauriel-service-directory' ),
			array( $this, 'field_number' ),
			$page,
			$section,
			array( 'option' => 'mauriel_map_default_lat', 'default' => 39.5, 'step' => 0.0001, 'min' => -90, 'max' => 90 )
		);

		register_setting( $page, 'mauriel_map_default_lng', array( 'sanitize_callback' => array( $this, 'sanitize_float' ), 'default' => -98.35 ) );
		add_settings_field(
			'mauriel_map_default_lng',
			__( 'Default Map Longitude', 'mauriel-service-directory' ),
			array( $this, 'field_number' ),
			$page,
			$section,
			array( 'option' => 'mauriel_map_default_lng', 'default' => -98.35, 'step' => 0.0001, 'min' => -180, 'max' => 180 )
		);

		register_setting( $page, 'mauriel_map_default_zoom', array( 'sanitize_callback' => array( $this, 'sanitize_positive_int' ), 'default' => 5 ) );
		add_settings_field(
			'mauriel_map_default_zoom',
			__( 'Default Map Zoom', 'mauriel-service-directory' ),
			array( $this, 'field_number' ),
			$page,
			$section,
			array( 'option' => 'mauriel_map_default_zoom', 'default' => 5, 'min' => 1, 'max' => 20 )
		);

		register_setting( $page, 'mauriel_auto_geocode', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ), 'default' => 1 ) );
		add_settings_field(
			'mauriel_auto_geocode',
			__( 'Auto-Geocode Listings', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_auto_geocode', 'default' => 1, 'description' => __( 'Automatically geocode listing addresses on save.', 'mauriel-service-directory' ) )
		);
	}

	// ---- AI ---------------------------------------------------------------

	/**
	 * Register AI tab settings.
	 */
	private function register_ai_settings() {
		$section = 'mauriel_ai_section';
		$page    = 'mauriel_settings_ai';

		add_settings_section( $section, '', '__return_false', $page );

		register_setting( $page, 'mauriel_ai_enabled', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ) ) );
		add_settings_field(
			'mauriel_ai_enabled',
			__( 'Enable AI Features', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_ai_enabled', 'description' => __( 'Enable AI-powered description and review-response generation.', 'mauriel-service-directory' ) )
		);

		register_setting( $page, 'mauriel_openai_api_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field(
			'mauriel_openai_api_key',
			__( 'OpenAI API Key', 'mauriel-service-directory' ),
			array( $this, 'field_password' ),
			$page,
			$section,
			array( 'option' => 'mauriel_openai_api_key' )
		);

		register_setting( $page, 'mauriel_openai_model', array( 'sanitize_callback' => array( $this, 'sanitize_openai_model' ), 'default' => 'gpt-4o-mini' ) );
		add_settings_field(
			'mauriel_openai_model',
			__( 'OpenAI Model', 'mauriel-service-directory' ),
			array( $this, 'field_select' ),
			$page,
			$section,
			array(
				'option'  => 'mauriel_openai_model',
				'default' => 'gpt-4o-mini',
				'options' => array(
					'gpt-4o-mini'    => 'GPT-4o Mini (recommended)',
					'gpt-4o'         => 'GPT-4o',
					'gpt-3.5-turbo'  => 'GPT-3.5 Turbo',
				),
			)
		);

		register_setting( $page, 'mauriel_ai_desc_max_tokens', array( 'sanitize_callback' => array( $this, 'sanitize_positive_int' ), 'default' => 400 ) );
		add_settings_field(
			'mauriel_ai_desc_max_tokens',
			__( 'Description Max Tokens', 'mauriel-service-directory' ),
			array( $this, 'field_number' ),
			$page,
			$section,
			array( 'option' => 'mauriel_ai_desc_max_tokens', 'default' => 400, 'min' => 50, 'max' => 2000 )
		);

		register_setting( $page, 'mauriel_ai_resp_max_tokens', array( 'sanitize_callback' => array( $this, 'sanitize_positive_int' ), 'default' => 300 ) );
		add_settings_field(
			'mauriel_ai_resp_max_tokens',
			__( 'Review Response Max Tokens', 'mauriel-service-directory' ),
			array( $this, 'field_number' ),
			$page,
			$section,
			array( 'option' => 'mauriel_ai_resp_max_tokens', 'default' => 300, 'min' => 50, 'max' => 1000 )
		);

		register_setting( $page, 'mauriel_ai_prompt_prefix', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
		add_settings_field(
			'mauriel_ai_prompt_prefix',
			__( 'System Prompt Prefix', 'mauriel-service-directory' ),
			array( $this, 'field_textarea' ),
			$page,
			$section,
			array( 'option' => 'mauriel_ai_prompt_prefix', 'description' => __( 'Prefix added to every AI prompt to set brand tone.', 'mauriel-service-directory' ), 'rows' => 4 )
		);
	}

	// ---- SEO --------------------------------------------------------------

	/**
	 * Register SEO tab settings.
	 */
	private function register_seo_settings() {
		$section = 'mauriel_seo_section';
		$page    = 'mauriel_settings_seo';

		add_settings_section( $section, '', '__return_false', $page );

		register_setting( $page, 'mauriel_schema_enabled', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ), 'default' => 1 ) );
		add_settings_field(
			'mauriel_schema_enabled',
			__( 'Output Schema Markup', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_schema_enabled', 'default' => 1, 'description' => __( 'Output LocalBusiness JSON-LD schema on listing pages.', 'mauriel-service-directory' ) )
		);

		register_setting( $page, 'mauriel_seo_title_pattern', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field(
			'mauriel_seo_title_pattern',
			__( 'SEO Title Pattern', 'mauriel-service-directory' ),
			array( $this, 'field_text' ),
			$page,
			$section,
			array( 'option' => 'mauriel_seo_title_pattern', 'default' => '{business_name} - {category} in {city}', 'description' => __( 'Available tokens: {business_name}, {category}, {city}, {state}', 'mauriel-service-directory' ) )
		);

		register_setting( $page, 'mauriel_seo_meta_pattern', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field(
			'mauriel_seo_meta_pattern',
			__( 'Meta Description Pattern', 'mauriel-service-directory' ),
			array( $this, 'field_text' ),
			$page,
			$section,
			array( 'option' => 'mauriel_seo_meta_pattern', 'default' => '{tagline}. Serving {city}, {state}.', 'description' => __( 'Available tokens: {tagline}, {city}, {state}, {business_name}', 'mauriel-service-directory' ) )
		);

		register_setting( $page, 'mauriel_og_tags', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ), 'default' => 1 ) );
		add_settings_field(
			'mauriel_og_tags',
			__( 'Output Open Graph Tags', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_og_tags', 'default' => 1, 'description' => __( 'Adds og:title, og:description, og:image meta tags.', 'mauriel-service-directory' ) )
		);

		register_setting( $page, 'mauriel_noindex_pending', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ), 'default' => 1 ) );
		add_settings_field(
			'mauriel_noindex_pending',
			__( 'No-Index Pending Listings', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_noindex_pending', 'default' => 1, 'description' => __( 'Add noindex meta to listings pending approval.', 'mauriel-service-directory' ) )
		);
	}

	// ---- Email ------------------------------------------------------------

	/**
	 * Register Email tab settings.
	 */
	private function register_email_settings() {
		$section = 'mauriel_email_section';
		$page    = 'mauriel_settings_email';

		add_settings_section( $section, '', '__return_false', $page );

		register_setting( $page, 'mauriel_email_from_name', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		add_settings_field(
			'mauriel_email_from_name',
			__( '"From" Name', 'mauriel-service-directory' ),
			array( $this, 'field_text' ),
			$page,
			$section,
			array( 'option' => 'mauriel_email_from_name' )
		);

		register_setting( $page, 'mauriel_email_from', array( 'sanitize_callback' => 'sanitize_email' ) );
		add_settings_field(
			'mauriel_email_from',
			__( '"From" Email Address', 'mauriel-service-directory' ),
			array( $this, 'field_email' ),
			$page,
			$section,
			array( 'option' => 'mauriel_email_from' )
		);

		register_setting( $page, 'mauriel_email_logo_id', array( 'sanitize_callback' => 'absint' ) );
		add_settings_field(
			'mauriel_email_logo_id',
			__( 'Email Logo', 'mauriel-service-directory' ),
			array( $this, 'field_media' ),
			$page,
			$section,
			array( 'option' => 'mauriel_email_logo_id', 'description' => __( 'Logo displayed in transactional email headers.', 'mauriel-service-directory' ) )
		);

		register_setting( $page, 'mauriel_email_lead_notify', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ), 'default' => 1 ) );
		add_settings_field(
			'mauriel_email_lead_notify',
			__( 'New Lead Notification', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_email_lead_notify', 'default' => 1, 'description' => __( 'Email business owner when a new lead is submitted.', 'mauriel-service-directory' ) )
		);

		register_setting( $page, 'mauriel_email_review_notify', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ), 'default' => 1 ) );
		add_settings_field(
			'mauriel_email_review_notify',
			__( 'New Review Notification', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_email_review_notify', 'default' => 1, 'description' => __( 'Email business owner when a new review is posted.', 'mauriel-service-directory' ) )
		);

		register_setting( $page, 'mauriel_email_approval_notify', array( 'sanitize_callback' => array( $this, 'sanitize_checkbox' ), 'default' => 1 ) );
		add_settings_field(
			'mauriel_email_approval_notify',
			__( 'Listing Approval Notification', 'mauriel-service-directory' ),
			array( $this, 'field_checkbox' ),
			$page,
			$section,
			array( 'option' => 'mauriel_email_approval_notify', 'default' => 1, 'description' => __( 'Email business owner when their listing is approved or rejected.', 'mauriel-service-directory' ) )
		);
	}

	// -----------------------------------------------------------------------
	// Sanitize Callbacks
	// -----------------------------------------------------------------------

	/**
	 * Sanitize a checkbox value to 0 or 1.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_checkbox( $value ) {
		return ( isset( $value ) && ( '1' === $value || true === $value || 1 === (int) $value ) ) ? 1 : 0;
	}

	/**
	 * Sanitize a positive integer.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	public function sanitize_positive_int( $value ) {
		$int = absint( $value );
		return max( 1, $int );
	}

	/**
	 * Sanitize a float value.
	 *
	 * @param mixed $value Raw value.
	 * @return float
	 */
	public function sanitize_float( $value ) {
		return (float) $value;
	}

	/**
	 * Sanitize sort option.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_sort( $value ) {
		$allowed = array( 'featured', 'rating', 'newest', 'az' );
		return in_array( $value, $allowed, true ) ? $value : 'featured';
	}

	/**
	 * Sanitize currency code.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_currency( $value ) {
		$allowed = array( 'USD', 'CAD', 'GBP', 'EUR' );
		return in_array( $value, $allowed, true ) ? $value : 'USD';
	}

	/**
	 * Sanitize distance unit.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_distance_unit( $value ) {
		return ( 'km' === $value ) ? 'km' : 'miles';
	}

	/**
	 * Sanitize Stripe mode.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_stripe_mode( $value ) {
		return ( 'live' === $value ) ? 'live' : 'test';
	}

	/**
	 * Sanitize OpenAI model selection.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public function sanitize_openai_model( $value ) {
		$allowed = array( 'gpt-4o-mini', 'gpt-4o', 'gpt-3.5-turbo' );
		return in_array( $value, $allowed, true ) ? $value : 'gpt-4o-mini';
	}

	// -----------------------------------------------------------------------
	// Field Renderers
	// -----------------------------------------------------------------------

	/**
	 * Render a text input field.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_text( $args ) {
		$option  = $args['option'];
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value   = get_option( $option, $default );
		?>
		<input
			type="text"
			id="<?php echo esc_attr( $option ); ?>"
			name="<?php echo esc_attr( $option ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a password / obfuscated text input.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_password( $args ) {
		$option = $args['option'];
		$value  = get_option( $option, '' );
		?>
		<input
			type="password"
			id="<?php echo esc_attr( $option ); ?>"
			name="<?php echo esc_attr( $option ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="new-password"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render an email input field.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_email( $args ) {
		$option  = $args['option'];
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value   = get_option( $option, $default );
		?>
		<input
			type="email"
			id="<?php echo esc_attr( $option ); ?>"
			name="<?php echo esc_attr( $option ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a number input field.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_number( $args ) {
		$option  = $args['option'];
		$default = isset( $args['default'] ) ? $args['default'] : 0;
		$value   = get_option( $option, $default );
		$min     = isset( $args['min'] ) ? $args['min'] : '';
		$max     = isset( $args['max'] ) ? $args['max'] : '';
		$step    = isset( $args['step'] ) ? $args['step'] : 1;
		?>
		<input
			type="number"
			id="<?php echo esc_attr( $option ); ?>"
			name="<?php echo esc_attr( $option ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="small-text"
			<?php if ( '' !== $min ) echo 'min="' . esc_attr( $min ) . '"'; ?>
			<?php if ( '' !== $max ) echo 'max="' . esc_attr( $max ) . '"'; ?>
			step="<?php echo esc_attr( $step ); ?>"
		/>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a checkbox input field.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_checkbox( $args ) {
		$option  = $args['option'];
		$default = isset( $args['default'] ) ? $args['default'] : 0;
		$value   = get_option( $option, $default );
		?>
		<label>
			<input
				type="checkbox"
				id="<?php echo esc_attr( $option ); ?>"
				name="<?php echo esc_attr( $option ); ?>"
				value="1"
				<?php checked( 1, (int) $value ); ?>
			/>
			<?php if ( ! empty( $args['description'] ) ) : ?>
				<?php echo esc_html( $args['description'] ); ?>
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render a select dropdown field.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_select( $args ) {
		$option  = $args['option'];
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value   = get_option( $option, $default );
		$options = isset( $args['options'] ) ? $args['options'] : array();
		?>
		<select
			id="<?php echo esc_attr( $option ); ?>"
			name="<?php echo esc_attr( $option ); ?>"
		>
			<?php foreach ( $options as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render radio button group.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_radio( $args ) {
		$option  = $args['option'];
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value   = get_option( $option, $default );
		$options = isset( $args['options'] ) ? $args['options'] : array();
		foreach ( $options as $key => $label ) :
			?>
			<label style="margin-right:15px;">
				<input
					type="radio"
					name="<?php echo esc_attr( $option ); ?>"
					value="<?php echo esc_attr( $key ); ?>"
					<?php checked( $value, $key ); ?>
				/>
				<?php echo esc_html( $label ); ?>
			</label>
		<?php endforeach;
		if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_textarea( $args ) {
		$option  = $args['option'];
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value   = get_option( $option, $default );
		$rows    = isset( $args['rows'] ) ? (int) $args['rows'] : 5;
		?>
		<textarea
			id="<?php echo esc_attr( $option ); ?>"
			name="<?php echo esc_attr( $option ); ?>"
			rows="<?php echo esc_attr( $rows ); ?>"
			class="large-text"
		><?php echo esc_textarea( $value ); ?></textarea>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a WordPress page select dropdown.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_page_select( $args ) {
		$option = $args['option'];
		$value  = get_option( $option, 0 );
		?>
		<?php
		wp_dropdown_pages(
			array(
				'name'              => esc_attr( $option ),
				'id'                => esc_attr( $option ),
				'selected'          => absint( $value ),
				'show_option_none'  => __( '— Select a Page —', 'mauriel-service-directory' ),
				'option_none_value' => '0',
			)
		);
		?>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	/**
	 * Render a media upload field.
	 *
	 * @param array $args Field arguments.
	 */
	public function field_media( $args ) {
		$option     = $args['option'];
		$attachment = absint( get_option( $option, 0 ) );
		$img_url    = $attachment ? wp_get_attachment_image_url( $attachment, 'thumbnail' ) : '';
		?>
		<div class="mauriel-media-field">
			<input
				type="hidden"
				id="<?php echo esc_attr( $option ); ?>"
				name="<?php echo esc_attr( $option ); ?>"
				value="<?php echo esc_attr( $attachment ); ?>"
			/>
			<?php if ( $img_url ) : ?>
				<img
					src="<?php echo esc_url( $img_url ); ?>"
					alt=""
					style="max-width:150px;display:block;margin-bottom:8px;"
				/>
			<?php endif; ?>
			<button
				type="button"
				class="button mauriel-media-select"
				data-target="<?php echo esc_attr( $option ); ?>"
				data-preview="<?php echo esc_attr( $option ); ?>-preview"
			>
				<?php esc_html_e( 'Select Image', 'mauriel-service-directory' ); ?>
			</button>
			<?php if ( $attachment ) : ?>
				<button type="button" class="button mauriel-media-remove" data-target="<?php echo esc_attr( $option ); ?>">
					<?php esc_html_e( 'Remove', 'mauriel-service-directory' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif;
	}

	// -----------------------------------------------------------------------
	// Render
	// -----------------------------------------------------------------------

	/**
	 * Render the full tabbed settings page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mauriel-service-directory' ) );
		}

		$current_tab = $this->current_tab;
		$page_slug   = 'mauriel_settings_' . $current_tab;
		$settings_url = admin_url( 'admin.php?page=mauriel-settings' );
		?>
		<div class="wrap mauriel-settings-wrap">
			<h1><?php esc_html_e( 'Mauriel Directory — Settings', 'mauriel-service-directory' ); ?></h1>

			<?php settings_errors( 'mauriel_settings' ); ?>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $this->tabs as $slug => $label ) : ?>
					<a
						href="<?php echo esc_url( add_query_arg( 'tab', $slug, $settings_url ) ); ?>"
						class="nav-tab <?php echo ( $current_tab === $slug ) ? 'nav-tab-active' : ''; ?>"
					>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" class="mauriel-settings-form">
				<?php
				settings_fields( $page_slug );
				do_settings_sections( $page_slug );
				submit_button( __( 'Save Settings', 'mauriel-service-directory' ) );
				?>
			</form>
		</div>
		<?php
	}
}
