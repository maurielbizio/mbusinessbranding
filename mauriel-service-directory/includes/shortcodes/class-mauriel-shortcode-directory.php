<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_Shortcode_Directory {

	public function __construct() {
		add_shortcode( 'mauriel_directory', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts( array(
			'category'      => '',
			'per_page'      => get_option( 'mauriel_listings_per_page', 12 ),
			'view'          => 'grid',
			'featured_only' => 0,
			'show_filters'  => 1,
		), $atts, 'mauriel_directory' );

		// Merge GET params
		$params = array(
			'category_slug' => sanitize_key( $_GET['category'] ?? $atts['category'] ),
			'per_page'      => absint( $_GET['per_page'] ?? $atts['per_page'] ),
			'view'          => sanitize_key( $_GET['view'] ?? $atts['view'] ),
			'featured_only' => (bool) ( $_GET['featured_only'] ?? $atts['featured_only'] ),
			'show_filters'  => (bool) $atts['show_filters'],
			'keyword'       => sanitize_text_field( $_GET['keyword'] ?? '' ),
			'zip'           => sanitize_text_field( $_GET['zip'] ?? '' ),
			'radius_miles'  => absint( $_GET['radius'] ?? 25 ),
			'rating_min'    => (int) ( $_GET['rating'] ?? 0 ),
			'open_now'      => ! empty( $_GET['open_now'] ),
			'sort_by'       => sanitize_key( $_GET['sort'] ?? get_option( 'mauriel_default_sort', 'featured' ) ),
			'page'          => max( 1, absint( $_GET['paged'] ?? 1 ) ),
		);

		// Enqueue Google Maps
		$maps_key = get_option( 'mauriel_google_maps_key', '' );
		if ( $maps_key && ! wp_script_is( 'google-maps-api', 'enqueued' ) ) {
			wp_enqueue_script(
				'google-maps-api',
				'https://maps.googleapis.com/maps/api/js?key=' . urlencode( $maps_key ) . '&libraries=places&callback=maurielMapInit',
				array( 'mauriel-maps' ),
				null,
				true
			);
		}

		wp_localize_script( 'mauriel-public', 'maurielDirectoryData', array(
			'restUrl'       => rest_url( 'mauriel/v1/' ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'defaultLat'    => (float) get_option( 'mauriel_map_default_lat', 39.5 ),
			'defaultLng'    => (float) get_option( 'mauriel_map_default_lng', -98.35 ),
			'defaultZoom'   => (int) get_option( 'mauriel_map_default_zoom', 5 ),
			'perPage'       => $params['per_page'],
			'view'          => $params['view'],
			'showFilters'   => $params['show_filters'],
			'featuredOnly'  => $params['featured_only'],
			'pluginUrl'     => MAURIEL_URL,
			'params'        => $params,
		) );

		// Run initial search
		$search_params = Mauriel_Search_Filters::sanitize( $params );
		if ( ! empty( $search_params['zip'] ) && empty( $search_params['lat'] ) ) {
			$geo = Mauriel_Geocoder::geocode_zip( $search_params['zip'] );
			if ( ! is_wp_error( $geo ) ) {
				$search_params['lat'] = $geo['lat'];
				$search_params['lng'] = $geo['lng'];
			}
		}
		$results = Mauriel_Search::run( $search_params );
		$listings  = $results['listings'] ?? array();
		$total     = $results['total'] ?? 0;
		$total_pages = $results['pages'] ?? 1;
		$map_data  = $results['map_data'] ?? array();

		ob_start();
		echo '<div class="mauriel-directory-wrap" id="mauriel-directory">';
		if ( $params['show_filters'] ) {
			$this->load_template( 'directory/filters.php', array( 'params' => $params ) );
		}
		echo '<div class="mauriel-directory-main">';
		$this->load_template( 'directory/archive.php', array(
			'listings'    => $listings,
			'total'       => $total,
			'params'      => $params,
			'view'        => $params['view'],
		) );
		if ( 'map' === $params['view'] ) {
			$this->load_template( 'directory/map-view.php', array( 'map_data' => $map_data ) );
		}
		$this->load_template( 'directory/pagination.php', array(
			'current_page' => $params['page'],
			'total_pages'  => $total_pages,
		) );
		echo '</div></div>';
		return ob_get_clean();
	}

	private function load_template( $template_name, $vars = array() ) {
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract
		$path = Mauriel_Core::get_instance()->locate_template( $template_name );
		if ( $path && file_exists( $path ) ) {
			include $path;
		}
	}
}
