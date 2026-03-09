<?php
defined('ABSPATH') || exit;

/**
 * Abstract class Mauriel_REST_Controller
 *
 * Base class for all Mauriel REST API controllers.
 */
class Mauriel_REST_Controller extends WP_REST_Controller {

    /**
     * REST API namespace.
     *
     * @var string
     */
    protected $namespace = 'mauriel/v1';

    /**
     * Registers REST routes. Must be implemented by subclasses.
     *
     * @return void
     */
    public function register_routes() {}

    /**
     * Verifies the X-WP-Nonce header for logged-in endpoint authentication.
     *
     * @param WP_REST_Request $request REST request object.
     *
     * @return bool|WP_Error True if nonce valid, WP_Error otherwise.
     */
    protected function get_nonce_permission( WP_REST_Request $request ) {
        $nonce = $request->get_header( 'X-WP-Nonce' );

        if ( empty( $nonce ) ) {
            return $this->error( 'mauriel_missing_nonce', __( 'Nonce is required.', 'mauriel-service-directory' ), 401 );
        }

        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return $this->error( 'mauriel_invalid_nonce', __( 'Invalid or expired nonce.', 'mauriel-service-directory' ), 401 );
        }

        if ( ! is_user_logged_in() ) {
            return $this->error( 'mauriel_not_logged_in', __( 'You must be logged in.', 'mauriel-service-directory' ), 401 );
        }

        return true;
    }

    /**
     * Verifies that the current user owns a given listing.
     *
     * @param WP_REST_Request $request    REST request object.
     * @param int             $listing_id Listing post ID to check ownership of.
     *
     * @return bool|WP_Error True if owner, WP_Error otherwise.
     */
    protected function owner_permission_check( WP_REST_Request $request, int $listing_id ) {
        $nonce_check = $this->get_nonce_permission( $request );
        if ( is_wp_error( $nonce_check ) ) {
            return $nonce_check;
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return $this->error( 'mauriel_not_logged_in', __( 'You must be logged in.', 'mauriel-service-directory' ), 401 );
        }

        // Admins bypass ownership check.
        if ( current_user_can( 'manage_options' ) || current_user_can( 'mauriel_directory_admin' ) ) {
            return true;
        }

        $owner_id = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );

        if ( $owner_id !== $user_id ) {
            return $this->error(
                'mauriel_forbidden',
                __( 'You do not have permission to manage this listing.', 'mauriel-service-directory' ),
                403
            );
        }

        return true;
    }

    /**
     * Returns a WP_Error with the mauriel_ namespace prefix.
     *
     * @param string $code    Error code (will be prefixed with mauriel_ if not already).
     * @param string $message Human-readable error message.
     * @param int    $status  HTTP status code. Default 400.
     *
     * @return WP_Error
     */
    protected function error( string $code, string $message, int $status = 400 ): WP_Error {
        // Ensure code has mauriel_ prefix.
        if ( strpos( $code, 'mauriel_' ) !== 0 ) {
            $code = 'mauriel_' . $code;
        }

        return new WP_Error( $code, $message, [ 'status' => $status ] );
    }

    /**
     * Checks whether the current user has the mauriel_directory_admin capability or wp admin.
     *
     * @return bool
     */
    protected function is_directory_admin(): bool {
        return current_user_can( 'manage_options' ) || current_user_can( 'mauriel_directory_admin' );
    }

    /**
     * Checks whether the current user has the mauriel_business_owner role.
     *
     * @return bool
     */
    protected function is_business_owner(): bool {
        $user = wp_get_current_user();
        return in_array( 'mauriel_business_owner', (array) $user->roles, true );
    }
}
