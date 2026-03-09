<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_REST_Analytics extends Mauriel_REST_Controller {

	public function register_routes() {
		register_rest_route( $this->namespace, '/analytics/(?P<listing_id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'handle_get' ),
			'permission_callback' => array( $this, 'owner_or_admin_permission' ),
			'args'                => array(
				'listing_id' => array( 'validate_callback' => 'is_numeric', 'sanitize_callback' => 'absint' ),
				'days'       => array( 'default' => 30, 'sanitize_callback' => 'absint' ),
			),
		) );

		register_rest_route( $this->namespace, '/analytics/record', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_record' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'listing_id'  => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				'event_type'  => array( 'required' => true, 'sanitize_callback' => 'sanitize_key' ),
				'search_term' => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );
	}

	public function owner_or_admin_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', __( 'You must be logged in.', 'mauriel-service-directory' ), array( 'status' => 401 ) );
		}
		$listing_id = (int) $request['listing_id'];
		$owner_id   = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );
		if ( get_current_user_id() !== $owner_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'forbidden', __( 'You do not have permission to view these analytics.', 'mauriel-service-directory' ), array( 'status' => 403 ) );
		}
		return true;
	}

	public function handle_get( $request ) {
		$listing_id = (int) $request['listing_id'];
		$days       = min( absint( $request->get_param( 'days' ) ?: 30 ), 90 );

		$summary = Mauriel_DB_Analytics::get_summary( $listing_id, $days );
		$trend   = array(
			'view'        => Mauriel_DB_Analytics::get_trend( $listing_id, 'view', $days ),
			'lead_submit' => Mauriel_DB_Analytics::get_trend( $listing_id, 'lead_submit', $days ),
			'phone_click' => Mauriel_DB_Analytics::get_trend( $listing_id, 'phone_click', $days ),
		);
		$totals = Mauriel_DB_Analytics::get_totals( $listing_id );

		return rest_ensure_response( array(
			'summary' => $summary,
			'trend'   => $trend,
			'totals'  => $totals,
			'days'    => $days,
		) );
	}

	public function handle_record( $request ) {
		$listing_id  = (int) $request['listing_id'];
		$event_type  = sanitize_key( $request['event_type'] );
		$search_term = sanitize_text_field( $request->get_param( 'search_term' ) ?: '' );

		$allowed_events = array( 'view', 'impression', 'click', 'phone_click', 'direction_click', 'website_click', 'lead_submit' );
		if ( ! in_array( $event_type, $allowed_events, true ) ) {
			return $this->error( 'invalid_event', __( 'Invalid event type.', 'mauriel-service-directory' ) );
		}

		$post = get_post( $listing_id );
		if ( ! $post || 'mauriel_listing' !== $post->post_type ) {
			return $this->error( 'invalid_listing', __( 'Listing not found.', 'mauriel-service-directory' ) );
		}

		$session_hash = Mauriel_DB_Analytics::build_session_hash( $listing_id, $event_type );
		$referrer     = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		Mauriel_DB_Analytics::record( $listing_id, $event_type, $session_hash, $referrer, $search_term );

		return rest_ensure_response( array( 'success' => true ) );
	}
}
