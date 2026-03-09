<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_REST_AI extends Mauriel_REST_Controller {

	public function register_routes() {
		register_rest_route( $this->namespace, '/ai/generate-description', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_generate_description' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( $this->namespace, '/ai/suggest-response', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_suggest_response' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		// Stripe webhook
		register_rest_route( $this->namespace, '/stripe/webhook', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_stripe_webhook' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function check_permission( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'unauthorized', __( 'You must be logged in.', 'mauriel-service-directory' ), array( 'status' => 401 ) );
		}
		if ( ! Mauriel_AI::is_enabled() ) {
			return new WP_Error( 'ai_disabled', __( 'AI features are not enabled.', 'mauriel-service-directory' ), array( 'status' => 403 ) );
		}
		return true;
	}

	public function handle_generate_description( $request ) {
		$user_id = get_current_user_id();

		// Rate limiting: 10 AI calls per user per day
		$rate_key   = 'mauriel_ai_calls_' . $user_id . '_' . date( 'Y-m-d' );
		$call_count = (int) get_transient( $rate_key );
		if ( $call_count >= 10 ) {
			return $this->error( 'rate_limit', __( 'Daily AI generation limit reached. Try again tomorrow.', 'mauriel-service-directory' ), 429 );
		}

		$business_name   = sanitize_text_field( $request->get_param( 'business_name' ) ?: '' );
		$category        = sanitize_text_field( $request->get_param( 'category' ) ?: '' );
		$city            = sanitize_text_field( $request->get_param( 'city' ) ?: '' );
		$state           = sanitize_text_field( $request->get_param( 'state' ) ?: '' );
		$services        = sanitize_text_field( $request->get_param( 'services' ) ?: '' );
		$keywords        = sanitize_text_field( $request->get_param( 'keywords' ) ?: '' );

		if ( empty( $business_name ) ) {
			return $this->error( 'missing_data', __( 'Business name is required.', 'mauriel-service-directory' ) );
		}

		$result = Mauriel_AI_Description::generate( array(
			'business_name' => $business_name,
			'category'      => $category,
			'city'          => $city,
			'state'         => $state,
			'services'      => $services,
			'keywords'      => $keywords,
		) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Increment rate counter
		set_transient( $rate_key, $call_count + 1, DAY_IN_SECONDS );

		return rest_ensure_response( array( 'suggestion' => $result ) );
	}

	public function handle_suggest_response( $request ) {
		$user_id    = get_current_user_id();
		$comment_id = (int) $request->get_param( 'comment_id' );

		// Rate limiting
		$rate_key   = 'mauriel_ai_calls_' . $user_id . '_' . date( 'Y-m-d' );
		$call_count = (int) get_transient( $rate_key );
		if ( $call_count >= 10 ) {
			return $this->error( 'rate_limit', __( 'Daily AI generation limit reached.', 'mauriel-service-directory' ), 429 );
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment || 'mauriel_review' !== $comment->comment_type ) {
			return $this->error( 'not_found', __( 'Review not found.', 'mauriel-service-directory' ), 404 );
		}

		// Verify owner
		$listing_id = (int) $comment->comment_post_ID;
		$owner_id   = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );
		if ( $user_id !== $owner_id && ! current_user_can( 'manage_options' ) ) {
			return $this->error( 'forbidden', __( 'You do not own this listing.', 'mauriel-service-directory' ), 403 );
		}

		$rating        = (int) get_comment_meta( $comment_id, '_mauriel_rating', true );
		$review_text   = $comment->comment_content;
		$business_name = get_the_title( $listing_id );

		$result = Mauriel_AI_Review_Response::suggest( $review_text, $rating, $business_name );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		set_transient( $rate_key, $call_count + 1, DAY_IN_SECONDS );

		return rest_ensure_response( array( 'suggestion' => $result ) );
	}

	public function handle_stripe_webhook( $request ) {
		$webhook = new Mauriel_Stripe_Webhook();
		$result  = $webhook->handle_request();

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 400 );
		}

		return new WP_REST_Response( array( 'received' => true ), 200 );
	}
}
