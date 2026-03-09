<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_Claim {

	public function __construct() {
		add_action( 'init', array( $this, 'handle_verify_request' ) );
	}

	/**
	 * Initiate a claim request: generate token, send email.
	 */
	public static function initiate( $listing_id, $email ) {
		$listing_id = absint( $listing_id );
		$email      = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'Please provide a valid email address.', 'mauriel-service-directory' ) );
		}

		$post = get_post( $listing_id );
		if ( ! $post || 'mauriel_listing' !== $post->post_type ) {
			return new WP_Error( 'invalid_listing', __( 'Listing not found.', 'mauriel-service-directory' ) );
		}

		$already_verified = get_post_meta( $listing_id, '_mauriel_verified', true );
		if ( $already_verified ) {
			return new WP_Error( 'already_claimed', __( 'This listing has already been claimed.', 'mauriel-service-directory' ) );
		}

		// Generate secure token
		$token      = bin2hex( random_bytes( 32 ) );
		$token_hash = hash( 'sha256', $token );
		$expires    = time() + DAY_IN_SECONDS;

		update_post_meta( $listing_id, '_mauriel_claim_token', $token_hash );
		update_post_meta( $listing_id, '_mauriel_claim_token_expires', $expires );
		update_post_meta( $listing_id, '_mauriel_claim_email', $email );

		$verify_url = add_query_arg(
			array(
				'mauriel_claim_verify' => 1,
				'listing_id'           => $listing_id,
				'token'                => $token,
			),
			home_url( '/' )
		);

		// Send verification email
		$subject = sprintf( __( 'Claim Your Business Listing: %s', 'mauriel-service-directory' ), get_the_title( $listing_id ) );
		$message = self::get_claim_email_content( get_the_title( $listing_id ), $verify_url, '24 hours' );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$from_name  = get_option( 'mauriel_email_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'mauriel_email_from', get_option( 'admin_email' ) );
		$headers[]  = "From: {$from_name} <{$from_email}>";

		wp_mail( $email, $subject, $message, $headers );

		return true;
	}

	/**
	 * Verify a claim token and transfer ownership.
	 */
	public static function verify( $listing_id, $token, $user_id ) {
		$listing_id = absint( $listing_id );
		$user_id    = absint( $user_id );

		if ( ! $user_id ) {
			return new WP_Error( 'not_logged_in', __( 'You must be logged in to claim a listing.', 'mauriel-service-directory' ) );
		}

		$stored_hash = get_post_meta( $listing_id, '_mauriel_claim_token', true );
		$expires     = (int) get_post_meta( $listing_id, '_mauriel_claim_token_expires', true );
		$claim_email = get_post_meta( $listing_id, '_mauriel_claim_email', true );

		if ( empty( $stored_hash ) ) {
			return new WP_Error( 'no_claim', __( 'No claim request found for this listing.', 'mauriel-service-directory' ) );
		}

		if ( time() > $expires ) {
			delete_post_meta( $listing_id, '_mauriel_claim_token' );
			delete_post_meta( $listing_id, '_mauriel_claim_token_expires' );
			delete_post_meta( $listing_id, '_mauriel_claim_email' );
			return new WP_Error( 'token_expired', __( 'Your claim link has expired. Please request a new one.', 'mauriel-service-directory' ) );
		}

		if ( ! hash_equals( $stored_hash, hash( 'sha256', $token ) ) ) {
			return new WP_Error( 'invalid_token', __( 'Invalid claim token.', 'mauriel-service-directory' ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user || strtolower( $user->user_email ) !== strtolower( $claim_email ) ) {
			return new WP_Error( 'email_mismatch', __( 'Your account email does not match the claim email.', 'mauriel-service-directory' ) );
		}

		// Transfer ownership
		update_post_meta( $listing_id, '_mauriel_owner_id', $user_id );
		update_post_meta( $listing_id, '_mauriel_verified', 1 );

		// Clear token
		delete_post_meta( $listing_id, '_mauriel_claim_token' );
		delete_post_meta( $listing_id, '_mauriel_claim_token_expires' );
		delete_post_meta( $listing_id, '_mauriel_claim_email' );

		// Assign business owner role if not already
		if ( ! $user->has_cap( 'mauriel_business_owner' ) ) {
			$user->add_role( 'mauriel_business_owner' );
		}

		// Store which listing they own
		update_user_meta( $user_id, '_mauriel_listing_id', $listing_id );

		do_action( 'mauriel_listing_claimed', $listing_id, $user_id );

		return true;
	}

	/**
	 * Handle verify request from URL.
	 */
	public function handle_verify_request() {
		if ( ! isset( $_GET['mauriel_claim_verify'] ) ) {
			return;
		}

		$listing_id = isset( $_GET['listing_id'] ) ? absint( $_GET['listing_id'] ) : 0;
		$token      = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( ! $listing_id || empty( $token ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			$redirect = add_query_arg(
				array(
					'mauriel_claim_verify' => 1,
					'listing_id'           => $listing_id,
					'token'                => $token,
				),
				home_url( '/' )
			);
			wp_redirect( wp_login_url( $redirect ) );
			exit;
		}

		$result = self::verify( $listing_id, $token, get_current_user_id() );

		$dashboard_url = get_permalink( get_option( 'mauriel_dashboard_page_id' ) );

		if ( is_wp_error( $result ) ) {
			wp_redirect( add_query_arg( 'mauriel_claim_error', urlencode( $result->get_error_message() ), $dashboard_url ) );
		} else {
			wp_redirect( add_query_arg( 'mauriel_claimed', 1, $dashboard_url ) );
		}
		exit;
	}

	/**
	 * Get email content for claim verification.
	 */
	private static function get_claim_email_content( $business_name, $verify_url, $expires_in ) {
		ob_start();
		$template = MAURIEL_PATH . 'templates/emails/claim-verification.php';
		if ( file_exists( $template ) ) {
			include $template;
		} else {
			echo '<p>' . esc_html__( 'Click the link below to claim your listing:', 'mauriel-service-directory' ) . '</p>';
			echo '<p><a href="' . esc_url( $verify_url ) . '">' . esc_url( $verify_url ) . '</a></p>';
			echo '<p>' . esc_html__( 'This link expires in:', 'mauriel-service-directory' ) . ' ' . esc_html( $expires_in ) . '</p>';
		}
		return ob_get_clean();
	}
}
