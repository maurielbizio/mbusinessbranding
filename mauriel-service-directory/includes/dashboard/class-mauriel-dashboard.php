<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_Dashboard {

	private $listing    = null;
	private $subscription = null;
	private $package    = null;
	private $active_tab = 'listing';

	public function render( $atts = array() ) {
		if ( ! is_user_logged_in() ) {
			wp_redirect( wp_login_url( get_permalink() ) );
			exit;
		}

		$user = wp_get_current_user();
		if ( ! $user->has_cap( 'mauriel_business_owner' ) && ! $user->has_cap( 'manage_options' ) ) {
			return '<div class="mauriel-notice mauriel-notice-error"><p>' . esc_html__( 'Access denied. This page is for business owners only.', 'mauriel-service-directory' ) . '</p></div>';
		}

		// Get user's listing
		$listings = get_posts( array(
			'post_type'      => 'mauriel_listing',
			'posts_per_page' => 1,
			'meta_key'       => '_mauriel_owner_id',
			'meta_value'     => get_current_user_id(),
			'post_status'    => array( 'publish', 'pending', 'draft' ),
		) );

		if ( empty( $listings ) ) {
			$register_url = get_permalink( get_option( 'mauriel_register_page_id' ) );
			return '<div class="mauriel-notice mauriel-notice-info"><p>' .
				sprintf(
					__( 'You do not have a listing yet. <a href="%s">Create your listing</a>.', 'mauriel-service-directory' ),
					esc_url( $register_url )
				) .
				'</p></div>';
		}

		$this->listing      = $listings[0];
		$this->subscription = Mauriel_DB_Subscriptions::get_by_listing( $this->listing->ID );
		$package_id         = $this->subscription ? $this->subscription->package_id : 0;
		$this->package      = $package_id ? Mauriel_DB_Packages::get( $package_id ) : null;

		// Validate active tab
		$allowed_tabs     = $this->get_allowed_tabs();
		$requested_tab    = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'listing';
		$this->active_tab = in_array( $requested_tab, array_keys( $allowed_tabs ), true ) ? $requested_tab : 'listing';

		// Handle form submissions
		if ( isset( $_POST['mauriel_dashboard_action'] ) ) {
			$this->handle_save();
		}

		ob_start();
		$this->render_dashboard();
		return ob_get_clean();
	}

	private function render_dashboard() {
		$listing      = $this->listing;
		$subscription = $this->subscription;
		$package      = $this->package;
		$active_tab   = $this->active_tab;
		$allowed_tabs = $this->get_allowed_tabs();
		$all_tabs     = $this->get_all_tabs();

		include Mauriel_Core::get_instance()->locate_template( 'dashboard/dashboard-wrapper.php' );
	}

	private function handle_save() {
		$action = sanitize_key( $_POST['mauriel_dashboard_action'] );

		switch ( $action ) {
			case 'save_listing':
				$this->save_listing_info();
				break;
			case 'save_hours':
				$this->save_hours();
				break;
			case 'delete_coupon':
				$this->handle_delete_coupon();
				break;
		}
	}

	private function save_listing_info() {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'mauriel_save_listing' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'mauriel-service-directory' ) );
		}

		$listing_id = absint( $_POST['listing_id'] ?? 0 );
		$owner_id   = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );

		if ( get_current_user_id() !== $owner_id && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not authorized.', 'mauriel-service-directory' ) );
		}

		// Sanitize fields
		$fields = array(
			'_mauriel_phone'        => array( 'raw' => $_POST['phone'] ?? '', 'fn' => 'sanitize_text_field' ),
			'_mauriel_email'        => array( 'raw' => $_POST['email'] ?? '', 'fn' => 'sanitize_email' ),
			'_mauriel_website'      => array( 'raw' => $_POST['website'] ?? '', 'fn' => 'esc_url_raw' ),
			'_mauriel_address'      => array( 'raw' => $_POST['address'] ?? '', 'fn' => 'sanitize_text_field' ),
			'_mauriel_city'         => array( 'raw' => $_POST['city'] ?? '', 'fn' => 'sanitize_text_field' ),
			'_mauriel_state'        => array( 'raw' => $_POST['state'] ?? '', 'fn' => 'sanitize_text_field' ),
			'_mauriel_zip'          => array( 'raw' => $_POST['zip'] ?? '', 'fn' => 'sanitize_text_field' ),
			'_mauriel_tagline'      => array( 'raw' => $_POST['tagline'] ?? '', 'fn' => 'sanitize_text_field' ),
			'_mauriel_description'  => array( 'raw' => $_POST['description'] ?? '', 'fn' => 'wp_kses_post' ),
			'_mauriel_service_area' => array( 'raw' => $_POST['service_area'] ?? '', 'fn' => 'sanitize_textarea_field' ),
			'_mauriel_place_id'     => array( 'raw' => $_POST['place_id'] ?? '', 'fn' => 'sanitize_text_field' ),
			'_mauriel_booking_url'  => array( 'raw' => $_POST['booking_url'] ?? '', 'fn' => 'esc_url_raw' ),
			'_mauriel_seo_title'    => array( 'raw' => $_POST['seo_title'] ?? '', 'fn' => 'sanitize_text_field' ),
			'_mauriel_seo_desc'     => array( 'raw' => $_POST['seo_desc'] ?? '', 'fn' => 'sanitize_textarea_field' ),
		);

		foreach ( $fields as $meta_key => $field ) {
			$value = call_user_func( $field['fn'], $field['raw'] );
			update_post_meta( $listing_id, $meta_key, $value );
		}

		// Social links (JSON)
		$social_keys = array( 'facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'youtube' );
		$social_data = array();
		foreach ( $social_keys as $key ) {
			$social_data[ $key ] = esc_url_raw( $_POST[ 'social_' . $key ] ?? '' );
		}
		update_post_meta( $listing_id, '_mauriel_social_links', wp_json_encode( $social_data ) );

		// Update post title
		$business_name = sanitize_text_field( $_POST['business_name'] ?? '' );
		if ( $business_name ) {
			wp_update_post( array( 'ID' => $listing_id, 'post_title' => $business_name ) );
		}

		// Re-geocode if address changed
		$old_address = get_post_meta( $listing_id, '_mauriel_address', true );
		$new_address = sanitize_text_field( $_POST['address'] ?? '' );
		if ( $old_address !== $new_address && ! empty( $new_address ) ) {
			$city  = sanitize_text_field( $_POST['city'] ?? '' );
			$state = sanitize_text_field( $_POST['state'] ?? '' );
			$zip   = sanitize_text_field( $_POST['zip'] ?? '' );
			$full  = "{$new_address}, {$city}, {$state} {$zip}";
			$geo   = Mauriel_Geocoder::geocode_address( $full );
			if ( ! is_wp_error( $geo ) ) {
				update_post_meta( $listing_id, '_mauriel_lat', $geo['lat'] );
				update_post_meta( $listing_id, '_mauriel_lng', $geo['lng'] );
			}
		}

		// Category
		if ( isset( $_POST['category_ids'] ) ) {
			$cat_ids = array_map( 'absint', (array) $_POST['category_ids'] );
			wp_set_object_terms( $listing_id, $cat_ids, 'mauriel_category' );
		}

		set_transient( 'mauriel_dashboard_notice_' . get_current_user_id(), array( 'type' => 'success', 'message' => __( 'Listing updated successfully!', 'mauriel-service-directory' ) ), 60 );
		wp_redirect( add_query_arg( 'tab', 'listing', get_permalink() ) );
		exit;
	}

	private function save_hours() {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'mauriel_save_hours' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'mauriel-service-directory' ) );
		}

		$listing_id = absint( $_POST['listing_id'] ?? 0 );
		$owner_id   = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );

		if ( get_current_user_id() !== $owner_id && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not authorized.', 'mauriel-service-directory' ) );
		}

		$hours_input = isset( $_POST['hours'] ) ? (array) $_POST['hours'] : array();
		$hours_data  = array();

		for ( $day = 0; $day <= 6; $day++ ) {
			$day_data         = $hours_input[ $day ] ?? array();
			$hours_data[$day] = array(
				'day_of_week' => $day,
				'listing_id'  => $listing_id,
				'is_open'     => ! empty( $day_data['is_open'] ) ? 1 : 0,
				'is_24_hours' => ! empty( $day_data['is_24_hours'] ) ? 1 : 0,
				'open_time'   => sanitize_text_field( $day_data['open_time'] ?? '09:00' ),
				'close_time'  => sanitize_text_field( $day_data['close_time'] ?? '17:00' ),
			);
		}

		Mauriel_DB_Hours::save_hours( $listing_id, $hours_data );

		set_transient( 'mauriel_dashboard_notice_' . get_current_user_id(), array( 'type' => 'success', 'message' => __( 'Hours saved!', 'mauriel-service-directory' ) ), 60 );
		wp_redirect( add_query_arg( 'tab', 'hours', get_permalink() ) );
		exit;
	}

	private function handle_delete_coupon() {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'mauriel_delete_coupon' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'mauriel-service-directory' ) );
		}
		$coupon_id = absint( $_POST['coupon_id'] ?? 0 );
		Mauriel_Coupons::delete( $coupon_id, get_current_user_id() );
		wp_redirect( add_query_arg( 'tab', 'coupons', get_permalink() ) );
		exit;
	}

	public function get_allowed_tabs() {
		$all_tabs = $this->get_all_tabs();
		if ( ! $this->package ) {
			return array_intersect_key( $all_tabs, array_flip( array( 'listing', 'hours', 'media', 'leads', 'reviews', 'subscription' ) ) );
		}
		$slug = $this->package->slug ?? 'free';
		if ( 'premium' === $slug ) {
			return $all_tabs;
		}
		if ( 'pro' === $slug ) {
			$allowed = array( 'listing', 'hours', 'media', 'leads', 'reviews', 'analytics', 'coupons', 'subscription' );
			return array_intersect_key( $all_tabs, array_flip( $allowed ) );
		}
		// Basic/Free
		$allowed = array( 'listing', 'hours', 'media', 'leads', 'reviews', 'subscription' );
		return array_intersect_key( $all_tabs, array_flip( $allowed ) );
	}

	public function get_all_tabs() {
		return array(
			'listing'      => __( 'My Listing', 'mauriel-service-directory' ),
			'media'        => __( 'Photos & Media', 'mauriel-service-directory' ),
			'hours'        => __( 'Business Hours', 'mauriel-service-directory' ),
			'leads'        => __( 'Leads', 'mauriel-service-directory' ),
			'reviews'      => __( 'Reviews', 'mauriel-service-directory' ),
			'analytics'    => __( 'Analytics', 'mauriel-service-directory' ),
			'coupons'      => __( 'Deals & Coupons', 'mauriel-service-directory' ),
			'subscription' => __( 'Plan & Billing', 'mauriel-service-directory' ),
		);
	}
}
