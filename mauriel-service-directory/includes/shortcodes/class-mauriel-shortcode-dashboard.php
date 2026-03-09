<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_Shortcode_Dashboard {

	public function __construct() {
		add_shortcode( 'mauriel_dashboard', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		wp_enqueue_style( 'mauriel-dashboard', MAURIEL_URL . 'assets/css/mauriel-dashboard.css', array( 'mauriel-public' ), MAURIEL_VERSION );
		wp_enqueue_script( 'mauriel-dashboard', MAURIEL_URL . 'assets/js/mauriel-dashboard.js', array( 'jquery', 'wp-api-request' ), MAURIEL_VERSION, true );
		wp_localize_script( 'mauriel-dashboard', 'maurielDashboardData', array(
			'restUrl'   => rest_url( 'mauriel/v1/' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'pluginUrl' => MAURIEL_URL,
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		) );

		ob_start();
		$dashboard = new Mauriel_Dashboard();
		echo $dashboard->render( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
		return ob_get_clean();
	}
}
