<?php
/**
 * Main Admin Controller
 *
 * Registers the WordPress admin menu and enqueues all admin-side
 * assets for the Mauriel Service Directory plugin.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Admin
 */
class Mauriel_Admin {

	/**
	 * Slug prefix used for admin menu pages.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'mauriel-directory';

	/**
	 * Settings instance — must be created early so its admin_init hook fires.
	 *
	 * @var Mauriel_Admin_Settings
	 */
	private $settings;

	/**
	 * Constructor — wires up WordPress hooks.
	 */
	public function __construct() {
		$this->settings = new Mauriel_Admin_Settings();
		add_action( 'admin_menu',            array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	// -----------------------------------------------------------------------
	// Admin Menu
	// -----------------------------------------------------------------------

	/**
	 * Register top-level menu and all sub-menus.
	 */
	public function admin_menu() {

		// ---- Top-level menu ------------------------------------------------
		add_menu_page(
			__( 'Mauriel Directory', 'mauriel-service-directory' ),
			__( 'Mauriel Directory', 'mauriel-service-directory' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'page_listings' ),
			'dashicons-store',
			30
		);

		// ---- Listings (duplicate of top-level so label reads "Listings") ---
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Listings', 'mauriel-service-directory' ),
			__( 'Listings', 'mauriel-service-directory' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'page_listings' )
		);

		// ---- Add New -------------------------------------------------------
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Add New Listing', 'mauriel-service-directory' ),
			__( 'Add New', 'mauriel-service-directory' ),
			'manage_options',
			'mauriel-add-listing',
			array( $this, 'page_add_listing' )
		);

		// ---- Packages ------------------------------------------------------
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Packages', 'mauriel-service-directory' ),
			__( 'Packages', 'mauriel-service-directory' ),
			'manage_options',
			'mauriel-packages',
			array( $this, 'page_packages' )
		);

		// ---- Subscriptions -------------------------------------------------
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Subscriptions', 'mauriel-service-directory' ),
			__( 'Subscriptions', 'mauriel-service-directory' ),
			'manage_options',
			'mauriel-subscriptions',
			array( $this, 'page_subscriptions' )
		);

		// ---- Reviews -------------------------------------------------------
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Reviews', 'mauriel-service-directory' ),
			__( 'Reviews', 'mauriel-service-directory' ),
			'manage_options',
			'mauriel-reviews',
			array( $this, 'page_reviews' )
		);

		// ---- Categories ----------------------------------------------------
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Categories', 'mauriel-service-directory' ),
			__( 'Categories', 'mauriel-service-directory' ),
			'manage_options',
			'mauriel-categories',
			array( $this, 'page_categories' )
		);

		// ---- Analytics -----------------------------------------------------
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Analytics', 'mauriel-service-directory' ),
			__( 'Analytics', 'mauriel-service-directory' ),
			'manage_options',
			'mauriel-analytics',
			array( $this, 'page_analytics' )
		);

		// ---- Settings ------------------------------------------------------
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'mauriel-service-directory' ),
			__( 'Settings', 'mauriel-service-directory' ),
			'manage_options',
			'mauriel-settings',
			array( $this, 'page_settings' )
		);
	}

	// -----------------------------------------------------------------------
	// Asset Enqueuing
	// -----------------------------------------------------------------------

	/**
	 * Enqueue admin CSS and JS only on plugin admin pages.
	 *
	 * @param string $hook The current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {

		// Build a list of page hook suffixes that belong to this plugin.
		$plugin_hooks = array(
			'toplevel_page_mauriel-directory',
			'mauriel-directory_page_mauriel-add-listing',
			'mauriel-directory_page_mauriel-packages',
			'mauriel-directory_page_mauriel-subscriptions',
			'mauriel-directory_page_mauriel-reviews',
			'mauriel-directory_page_mauriel-categories',
			'mauriel-directory_page_mauriel-analytics',
			'mauriel-directory_page_mauriel-settings',
		);

		if ( ! in_array( $hook, $plugin_hooks, true ) ) {
			return;
		}

		// ---- CSS -----------------------------------------------------------
		wp_enqueue_style(
			'mauriel-admin',
			MAURIEL_URL . 'assets/css/mauriel-admin.css',
			array(),
			MAURIEL_VERSION
		);

		// ---- JS ------------------------------------------------------------
		wp_enqueue_script(
			'mauriel-admin',
			MAURIEL_URL . 'assets/js/mauriel-admin.js',
			array( 'jquery', 'wp-util' ),
			MAURIEL_VERSION,
			true
		);

		// ---- Localize -------------------------------------------------------
		wp_localize_script(
			'mauriel-admin',
			'mauriel_admin_data',
			array(
				'rest_url'  => esc_url_raw( rest_url( 'mauriel/v1/' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'ajax_url'  => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'ajax_nonce'=> wp_create_nonce( 'mauriel_admin_ajax' ),
				'strings'   => array(
					'confirm_delete' => __( 'Are you sure you want to delete this item? This cannot be undone.', 'mauriel-service-directory' ),
					'saving'         => __( 'Saving…', 'mauriel-service-directory' ),
					'saved'          => __( 'Saved!', 'mauriel-service-directory' ),
					'error'          => __( 'An error occurred. Please try again.', 'mauriel-service-directory' ),
				),
			)
		);

		// Enqueue Chart.js for Analytics page.
		if ( 'mauriel-directory_page_mauriel-analytics' === $hook ) {
			wp_enqueue_script(
				'chartjs',
				'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
				array(),
				'4.4.0',
				true
			);
		}
	}

	// -----------------------------------------------------------------------
	// Page Callbacks
	// -----------------------------------------------------------------------

	/**
	 * Render the Listings admin page.
	 */
	public function page_listings() {
		$instance = new Mauriel_Admin_Listings();
		$instance->render_page();
	}

	/**
	 * Render the Add New Listing admin page.
	 * Redirects to the native WP new-post screen for the mauriel_listing CPT.
	 */
	public function page_add_listing() {
		$new_url = admin_url( 'post-new.php?post_type=mauriel_listing' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Add New Listing', 'mauriel-service-directory' ); ?></h1>
			<p>
				<?php
				printf(
					/* translators: %s: URL to new listing page */
					esc_html__( 'Click %s to create a new listing.', 'mauriel-service-directory' ),
					'<a class="button button-primary" href="' . esc_url( $new_url ) . '">' . esc_html__( 'Add New Listing', 'mauriel-service-directory' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the Packages admin page.
	 */
	public function page_packages() {
		$instance = new Mauriel_Admin_Packages();
		$instance->render();
	}

	/**
	 * Render the Subscriptions admin page.
	 */
	public function page_subscriptions() {
		$instance = new Mauriel_Admin_Payments();
		$instance->render();
	}

	/**
	 * Render the Reviews admin page.
	 */
	public function page_reviews() {
		$instance = new Mauriel_Admin_Reviews();
		$instance->render_page();
	}

	/**
	 * Render the Categories admin page.
	 */
	public function page_categories() {
		$instance = new Mauriel_Admin_Categories();
		$instance->render();
	}

	/**
	 * Render the Analytics admin page.
	 */
	public function page_analytics() {
		$instance = new Mauriel_Admin_Analytics();
		$instance->render();
	}

	/**
	 * Render the Settings admin page.
	 */
	public function page_settings() {
		$this->settings->render();
	}
}
