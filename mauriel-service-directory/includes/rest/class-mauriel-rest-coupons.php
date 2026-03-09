<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_REST_Coupons extends Mauriel_REST_Controller {

	public function register_routes() {
		register_rest_route( $this->namespace, '/coupons/(?P<listing_id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_active_coupons' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( $this->namespace, '/coupons/(?P<listing_id>\d+)/manage', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_all_coupons' ),
			'permission_callback' => array( $this, 'check_owner_permission' ),
		) );

		register_rest_route( $this->namespace, '/coupons', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_coupon' ),
			'permission_callback' => array( $this, 'check_logged_in' ),
		) );

		register_rest_route( $this->namespace, '/coupons/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_coupon' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_coupon' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			),
		) );
	}

	public function check_logged_in( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', __( 'You must be logged in.', 'mauriel-service-directory' ), array( 'status' => 401 ) );
		}
		return true;
	}

	public function check_owner_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', __( 'You must be logged in.', 'mauriel-service-directory' ), array( 'status' => 401 ) );
		}
		$listing_id = (int) $request['listing_id'];
		$owner_id   = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );
		if ( get_current_user_id() !== $owner_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', __( 'Access denied.', 'mauriel-service-directory' ), array( 'status' => 403 ) );
		}
		return true;
	}

	public function get_active_coupons( $request ) {
		$listing_id = (int) $request['listing_id'];
		$coupons    = Mauriel_DB_Coupons::get_active_for_listing( $listing_id );
		return rest_ensure_response( $coupons );
	}

	public function get_all_coupons( $request ) {
		$listing_id = (int) $request['listing_id'];
		$coupons    = Mauriel_DB_Coupons::get_for_listing( $listing_id );
		return rest_ensure_response( $coupons );
	}

	public function create_coupon( $request ) {
		$listing_id = (int) $request->get_param( 'listing_id' );
		$user_id    = get_current_user_id();
		$owner_id   = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );

		if ( $user_id !== $owner_id && ! current_user_can( 'manage_options' ) ) {
			return $this->error( 'forbidden', __( 'You do not own this listing.', 'mauriel-service-directory' ), 403 );
		}

		$data = $this->sanitize_coupon_data( $request->get_params() );
		$id   = Mauriel_DB_Coupons::create( $data );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return rest_ensure_response( array( 'success' => true, 'id' => $id ) );
	}

	public function update_coupon( $request ) {
		$coupon_id  = (int) $request['id'];
		$coupon     = Mauriel_DB_Coupons::get( $coupon_id );

		if ( ! $coupon ) {
			return $this->error( 'not_found', __( 'Coupon not found.', 'mauriel-service-directory' ), 404 );
		}

		$owner_id = (int) get_post_meta( $coupon->listing_id, '_mauriel_owner_id', true );
		if ( get_current_user_id() !== $owner_id && ! current_user_can( 'manage_options' ) ) {
			return $this->error( 'forbidden', __( 'Access denied.', 'mauriel-service-directory' ), 403 );
		}

		$data = $this->sanitize_coupon_data( $request->get_params() );
		Mauriel_DB_Coupons::update_coupon( $coupon_id, $data );

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_coupon( $request ) {
		$coupon_id = (int) $request['id'];
		$coupon    = Mauriel_DB_Coupons::get( $coupon_id );

		if ( ! $coupon ) {
			return $this->error( 'not_found', __( 'Coupon not found.', 'mauriel-service-directory' ), 404 );
		}

		$owner_id = (int) get_post_meta( $coupon->listing_id, '_mauriel_owner_id', true );
		if ( get_current_user_id() !== $owner_id && ! current_user_can( 'manage_options' ) ) {
			return $this->error( 'forbidden', __( 'Access denied.', 'mauriel-service-directory' ), 403 );
		}

		Mauriel_DB_Coupons::delete_coupon( $coupon_id );

		return rest_ensure_response( array( 'success' => true ) );
	}

	private function sanitize_coupon_data( $params ) {
		$allowed_types = array( 'percent', 'fixed', 'free_service', 'other' );
		return array(
			'listing_id'     => absint( $params['listing_id'] ?? 0 ),
			'title'          => sanitize_text_field( $params['title'] ?? '' ),
			'description'    => sanitize_textarea_field( $params['description'] ?? '' ),
			'coupon_code'    => sanitize_text_field( $params['coupon_code'] ?? '' ),
			'discount_type'  => in_array( $params['discount_type'] ?? '', $allowed_types, true ) ? $params['discount_type'] : 'percent',
			'discount_value' => isset( $params['discount_value'] ) ? floatval( $params['discount_value'] ) : null,
			'starts_at'      => ! empty( $params['starts_at'] ) ? sanitize_text_field( $params['starts_at'] ) : null,
			'expires_at'     => ! empty( $params['expires_at'] ) ? sanitize_text_field( $params['expires_at'] ) : null,
			'max_uses'       => ! empty( $params['max_uses'] ) ? absint( $params['max_uses'] ) : null,
			'is_active'      => isset( $params['is_active'] ) ? ( $params['is_active'] ? 1 : 0 ) : 1,
		);
	}
}
