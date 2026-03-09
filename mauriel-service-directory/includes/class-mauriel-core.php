<?php
/**
 * Core bootstrap class — singleton that wires all subsystems together.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Core
 */
class Mauriel_Core {

	// -----------------------------------------------------------------------
	// Singleton
	// -----------------------------------------------------------------------

	/** @var Mauriel_Core|null */
	private static $instance = null;

	/**
	 * Get (or create) the single instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// -----------------------------------------------------------------------
	// Properties
	// -----------------------------------------------------------------------

	/** @var string Plugin version. */
	public string $version;

	// -----------------------------------------------------------------------
	// Constructor (private — use get_instance())
	// -----------------------------------------------------------------------

	private function __construct() {
		$this->version = MAURIEL_VERSION;
	}

	// -----------------------------------------------------------------------
	// Initialisation
	// -----------------------------------------------------------------------

	/**
	 * Bootstrap all subsystems.  Called on 'plugins_loaded'.
	 */
	public function init(): void {
		// Load text domain.
		load_plugin_textdomain(
			'mauriel-service-directory',
			false,
			dirname( plugin_basename( MAURIEL_PLUGIN_FILE ) ) . '/languages'
		);

		// Post type & taxonomy objects (they self-register on 'init').
		new Mauriel_Post_Type_Listing();
		new Mauriel_Taxonomy_Category();

		// Shortcodes.
		new Mauriel_Shortcode_Directory();
		new Mauriel_Shortcode_Dashboard();
		new Mauriel_Shortcode_Featured();

		// SEO.
		new Mauriel_SEO();

		// WordPress init hook — register CPT + taxonomy.
		add_action( 'init', [ $this, 'register_post_types' ] );

		// REST API.
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Public assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );

		// Admin.
		if ( is_admin() ) {
			$this->load_admin();
		}

		// Custom rewrite / template handling.
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
	}

	/**
	 * Trigger CPT + taxonomy registration (hooked to 'init').
	 * The individual classes also hook themselves, but this explicit call
	 * ensures registration even if the object was created after 'init' fired.
	 */
	public function register_post_types(): void {
		// Post types and taxonomies register themselves via their constructors,
		// so nothing extra is needed here.  This method exists as a clear
		// extension point for future registrations.
		do_action( 'mauriel_register_post_types' );
	}

	/**
	 * Register all REST API routes.
	 */
	public function register_rest_routes(): void {
		( new Mauriel_REST_Listings() )->register_routes();
		( new Mauriel_REST_Search() )->register_routes();
		( new Mauriel_REST_Leads() )->register_routes();
		( new Mauriel_REST_Reviews() )->register_routes();
		( new Mauriel_REST_Analytics() )->register_routes();
		( new Mauriel_REST_Coupons() )->register_routes();
		( new Mauriel_REST_AI() )->register_routes();
	}

	/**
	 * Load admin-only classes.
	 */
	public function load_admin(): void {
		new Mauriel_Admin();
	}

	/**
	 * Enqueue public-facing CSS and JS.
	 */
	public function enqueue_public_assets(): void {
		// Only enqueue on pages that actually contain a plugin shortcode
		// or on singular mauriel_listing posts.
		if ( ! $this->is_plugin_page() ) {
			return;
		}

		wp_enqueue_style(
			'mauriel-public',
			MAURIEL_URL . 'assets/css/mauriel-public.css',
			[],
			MAURIEL_VERSION
		);

		wp_enqueue_script(
			'mauriel-public',
			MAURIEL_URL . 'assets/js/mauriel-public.js',
			[ 'jquery' ],
			MAURIEL_VERSION,
			true
		);

		wp_localize_script(
			'mauriel-public',
			'mauriel_public_data',
			[
				'rest_url'   => esc_url_raw( rest_url( 'mauriel/v1/' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'plugin_url' => MAURIEL_URL,
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'i18n'       => [
					'loading'       => __( 'Loading…', 'mauriel-service-directory' ),
					'error'         => __( 'Something went wrong. Please try again.', 'mauriel-service-directory' ),
					'saved'         => __( 'Saved!', 'mauriel-service-directory' ),
					'confirm_delete'=> __( 'Are you sure you want to delete this?', 'mauriel-service-directory' ),
				],
			]
		);
	}

	// -----------------------------------------------------------------------
	// Template handling
	// -----------------------------------------------------------------------

	/**
	 * Handle template overrides for single mauriel_listing posts.
	 * Themes can place a template at {theme}/mauriel-service-directory/single-listing.php
	 * to override the plugin default.
	 */
	public function template_redirect(): void {
		if ( is_singular( 'mauriel_listing' ) ) {
			$template = $this->get_template( 'single-listing.php' );
			if ( $template ) {
				include $template;
				exit;
			}
		}

		if ( is_tax( 'mauriel_category' ) ) {
			$template = $this->get_template( 'archive-listing.php' );
			if ( $template ) {
				include $template;
				exit;
			}
		}

		if ( is_post_type_archive( 'mauriel_listing' ) ) {
			$template = $this->get_template( 'archive-listing.php' );
			if ( $template ) {
				include $template;
				exit;
			}
		}
	}

	/**
	 * Locate a plugin template, allowing theme overrides.
	 *
	 * Checks (in order):
	 *  1. {active-theme}/mauriel-service-directory/{$template_name}
	 *  2. {parent-theme}/mauriel-service-directory/{$template_name}
	 *  3. {plugin}/templates/{$template_name}
	 *
	 * @param string  $template_name Relative template filename (e.g. 'single-listing.php').
	 * @param mixed[] $args          Variables to extract into the template scope.
	 * @return string|false Absolute path on success, false if not found.
	 */
	public function get_template( string $template_name, array $args = [] ) {
		// Allow theme overrides.
		$theme_override = locate_template(
			[
				'mauriel-service-directory/' . $template_name,
			]
		);

		if ( $theme_override ) {
			if ( ! empty( $args ) ) {
				extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
			}
			return $theme_override;
		}

		// Plugin default template.
		$plugin_template = MAURIEL_PATH . 'templates/' . $template_name;
		if ( file_exists( $plugin_template ) ) {
			if ( ! empty( $args ) ) {
				extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
			}
			return $plugin_template;
		}

		return false;
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Check whether the current page is one that should load plugin assets.
	 *
	 * @return bool
	 */
	private function is_plugin_page(): bool {
		if ( is_singular( 'mauriel_listing' ) || is_tax( 'mauriel_category' ) || is_post_type_archive( 'mauriel_listing' ) ) {
			return true;
		}

		// Check if the current page contains a known plugin shortcode.
		global $post;
		if ( $post instanceof WP_Post ) {
			$shortcodes = [
				'mauriel_directory',
				'mauriel_dashboard',
				'mauriel_featured',
			];
			foreach ( $shortcodes as $sc ) {
				if ( has_shortcode( $post->post_content, $sc ) ) {
					return true;
				}
			}
		}

		// Check option-stored page IDs.
		$page_ids = [
			(int) get_option( 'mauriel_directory_page_id', 0 ),
			(int) get_option( 'mauriel_dashboard_page_id', 0 ),
			(int) get_option( 'mauriel_register_page_id', 0 ),
		];

		if ( $post && in_array( (int) $post->ID, array_filter( $page_ids ), true ) ) {
			return true;
		}

		return false;
	}
}
