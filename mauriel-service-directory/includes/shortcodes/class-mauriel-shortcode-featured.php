<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_Shortcode_Featured {

	public function __construct() {
		add_shortcode( 'mauriel_featured', array( $this, 'render' ) );
	}

	public function render( $atts ) {
		$atts = shortcode_atts( array(
			'count'    => 6,
			'category' => '',
			'orderby'  => 'rating',
		), $atts, 'mauriel_featured' );

		$count   = max( 1, min( 24, absint( $atts['count'] ) ) );
		$orderby = in_array( $atts['orderby'], array( 'featured', 'rating', 'newest', 'random' ), true ) ? $atts['orderby'] : 'rating';

		$query_args = array(
			'post_type'      => 'mauriel_listing',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'meta_query'     => array(
				array(
					'key'     => '_mauriel_featured',
					'value'   => '1',
					'compare' => '=',
				),
				array(
					'key'     => '_mauriel_approval_status',
					'value'   => 'approved',
					'compare' => '=',
				),
			),
		);

		if ( ! empty( $atts['category'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'mauriel_category',
					'field'    => 'slug',
					'terms'    => sanitize_key( $atts['category'] ),
				),
			);
		}

		switch ( $orderby ) {
			case 'random':
				$query_args['orderby'] = 'rand';
				break;
			case 'rating':
				$query_args['meta_key'] = '_mauriel_avg_rating';
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = 'DESC';
				break;
			case 'newest':
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
			default:
				$query_args['orderby'] = 'date';
		}

		$query    = new WP_Query( $query_args );
		$listings = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();
				// Record impressions
				Mauriel_Analytics::record( $post_id, 'impression' );
				$listings[] = Mauriel_Search::format_listing_static( get_post() );
			}
			wp_reset_postdata();
		}

		if ( empty( $listings ) ) {
			return '';
		}

		ob_start();
		echo '<div class="mauriel-featured-listings">';
		echo '<div class="mauriel-featured-grid">';
		foreach ( $listings as $listing_data ) {
			$listing = $listing_data;
			$featured = true;
			$template_path = Mauriel_Core::get_instance()->locate_template( 'directory/listing-card.php' );
			if ( $template_path && file_exists( $template_path ) ) {
				include $template_path;
			}
		}
		echo '</div></div>';
		return ob_get_clean();
	}
}
