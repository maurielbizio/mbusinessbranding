<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Registration
 *
 * Handles the multi-step business owner registration flow.
 */
class Mauriel_Registration {

    /**
     * Constructor — hooks form submission handler on init.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'handle_form_submission' ] );
    }

    /**
     * Detects a registration form POST submission and dispatches to the correct step handler.
     *
     * @return void
     */
    public function handle_form_submission(): void {
        if ( ! isset( $_POST['mauriel_register_action'] ) ) {
            return;
        }

        // Verify nonce.
        if ( ! isset( $_POST['mauriel_register_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mauriel_register_nonce'] ) ), 'mauriel_register_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed. Please go back and try again.', 'mauriel-service-directory' ) );
        }

        $step = isset( $_POST['step'] ) ? (int) $_POST['step'] : 1;
        $data = wp_unslash( $_POST );

        switch ( $step ) {
            case 1:
                $this->handle_step_1( $data );
                break;

            case 2:
                $user_id = get_current_user_id();
                if ( ! $user_id ) {
                    $this->redirect_with_error( 1, 'not_logged_in' );
                }
                $this->handle_step_2( $data, $user_id );
                break;

            case 3:
                $user_id = get_current_user_id();
                if ( ! $user_id ) {
                    $this->redirect_with_error( 2, 'not_logged_in' );
                }
                $this->handle_step_3( $data, $user_id );
                break;

            default:
                break;
        }
    }

    /**
     * Handles Step 1: Account creation (email, password, name).
     *
     * @param array $data Raw POST data.
     *
     * @return void
     */
    private function handle_step_1( array $data ): void {
        $email    = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
        $password = isset( $data['password'] ) ? $data['password'] : '';
        $name     = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';

        $errors = [];

        // Validate email.
        if ( empty( $email ) ) {
            $errors[] = 'email_required';
        } elseif ( ! is_email( $email ) ) {
            $errors[] = 'email_invalid';
        } elseif ( email_exists( $email ) ) {
            $errors[] = 'email_exists';
        }

        // Validate password.
        if ( empty( $password ) ) {
            $errors[] = 'password_required';
        } elseif ( strlen( $password ) < 8 ) {
            $errors[] = 'password_too_short';
        }

        // Validate name.
        if ( empty( $name ) ) {
            $errors[] = 'name_required';
        }

        if ( ! empty( $errors ) ) {
            $this->redirect_with_error( 1, implode( ',', $errors ) );
            return;
        }

        // Generate a unique username from the email address.
        $username = self::generate_username_from_email( $email );

        // Create user.
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            $this->redirect_with_error( 1, $user_id->get_error_code() );
            return;
        }

        // Set display name and role.
        wp_update_user( [
            'ID'           => $user_id,
            'display_name' => $name,
            'first_name'   => $name,
        ] );

        // Assign role.
        $user = new WP_User( $user_id );
        $user->set_role( 'mauriel_business_owner' );

        // Log in the new user.
        wp_set_auth_cookie( $user_id, false );
        wp_set_current_user( $user_id );

        // Mark step 1 complete.
        Mauriel_Onboarding::mark_step_complete( $user_id, 1 );

        // Redirect to step 2.
        $register_page_id = (int) get_option( 'mauriel_register_page_id', 0 );
        $register_url     = $register_page_id ? get_permalink( $register_page_id ) : home_url( '/register/' );

        wp_redirect( add_query_arg( 'step', '2', $register_url ) );
        exit;
    }

    /**
     * Handles Step 2: Business information and listing creation.
     *
     * @param array $data    Raw POST data.
     * @param int   $user_id Current user ID.
     *
     * @return void
     */
    private function handle_step_2( array $data, int $user_id ): void {
        // Verify user has the correct role.
        $user = get_userdata( $user_id );
        if ( ! $user || ! in_array( 'mauriel_business_owner', (array) $user->roles, true ) ) {
            $this->redirect_with_error( 2, 'invalid_role' );
            return;
        }

        // Sanitize all business fields.
        $business_name = isset( $data['business_name'] ) ? sanitize_text_field( $data['business_name'] ) : '';
        $address       = isset( $data['address'] ) ? sanitize_text_field( $data['address'] ) : '';
        $city          = isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : '';
        $state         = isset( $data['state'] ) ? sanitize_text_field( $data['state'] ) : '';
        $zip           = isset( $data['zip'] ) ? sanitize_text_field( $data['zip'] ) : '';
        $phone         = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
        $biz_email     = isset( $data['biz_email'] ) ? sanitize_email( $data['biz_email'] ) : '';
        $website       = isset( $data['website'] ) ? esc_url_raw( $data['website'] ) : '';
        $description   = isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '';
        $tagline       = isset( $data['tagline'] ) ? sanitize_text_field( $data['tagline'] ) : '';
        $category_id   = isset( $data['category_id'] ) ? absint( $data['category_id'] ) : 0;

        if ( empty( $business_name ) ) {
            $this->redirect_with_error( 2, 'business_name_required' );
            return;
        }

        // Geocode address.
        $lat              = '';
        $lng              = '';
        $formatted_address = '';

        if ( ! empty( $address ) && ! empty( $city ) && ! empty( $state ) ) {
            $full_address     = trim( "$address, $city, $state $zip" );
            $geocode_result   = Mauriel_Geocoder::geocode_address( $full_address );

            if ( ! is_wp_error( $geocode_result ) ) {
                $lat              = $geocode_result['lat'];
                $lng              = $geocode_result['lng'];
                $formatted_address = $geocode_result['formatted_address'];
            }
        }

        // Create the listing post.
        $post_id = wp_insert_post( [
            'post_type'   => 'mauriel_listing',
            'post_title'  => $business_name,
            'post_status' => 'pending',
            'post_author' => $user_id,
        ] );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $this->redirect_with_error( 2, 'listing_creation_failed' );
            return;
        }

        // Assign category taxonomy.
        if ( $category_id ) {
            wp_set_post_terms( $post_id, [ $category_id ], 'mauriel_category' );
        }

        // Store all meta fields.
        update_post_meta( $post_id, '_mauriel_address', $address );
        update_post_meta( $post_id, '_mauriel_city', $city );
        update_post_meta( $post_id, '_mauriel_state', $state );
        update_post_meta( $post_id, '_mauriel_zip', $zip );
        update_post_meta( $post_id, '_mauriel_phone', $phone );
        update_post_meta( $post_id, '_mauriel_email', $biz_email );
        update_post_meta( $post_id, '_mauriel_website', $website );
        update_post_meta( $post_id, '_mauriel_description', $description );
        update_post_meta( $post_id, '_mauriel_tagline', $tagline );
        update_post_meta( $post_id, '_mauriel_lat', $lat );
        update_post_meta( $post_id, '_mauriel_lng', $lng );
        update_post_meta( $post_id, '_mauriel_formatted_address', $formatted_address );
        update_post_meta( $post_id, '_mauriel_owner_id', $user_id );
        update_post_meta( $post_id, '_mauriel_approval_status', 'pending' );
        update_post_meta( $post_id, '_mauriel_featured', 0 );
        update_post_meta( $post_id, '_mauriel_verified', 0 );
        update_post_meta( $post_id, '_mauriel_avg_rating', 0 );
        update_post_meta( $post_id, '_mauriel_review_count', 0 );

        // Store pending listing ID in user meta.
        update_user_meta( $user_id, '_mauriel_pending_listing_id', $post_id );

        // Mark step 2 complete.
        Mauriel_Onboarding::mark_step_complete( $user_id, 2 );

        // Redirect to step 3.
        $register_page_id = (int) get_option( 'mauriel_register_page_id', 0 );
        $register_url     = $register_page_id ? get_permalink( $register_page_id ) : home_url( '/register/' );

        wp_redirect( add_query_arg( 'step', '3', $register_url ) );
        exit;
    }

    /**
     * Handles Step 3: Package selection and payment.
     *
     * @param array $data    Raw POST data.
     * @param int   $user_id Current user ID.
     *
     * @return void
     */
    private function handle_step_3( array $data, int $user_id ): void {
        $listing_id       = $this->get_pending_listing_id( $user_id );
        $package_id       = isset( $data['package_id'] ) ? absint( $data['package_id'] ) : 0;
        $billing_interval = isset( $data['billing_interval'] ) ? sanitize_key( $data['billing_interval'] ) : 'monthly';

        if ( ! $listing_id ) {
            $this->redirect_with_error( 3, 'no_pending_listing' );
            return;
        }

        if ( ! $package_id ) {
            $this->redirect_with_error( 3, 'no_package_selected' );
            return;
        }

        if ( ! in_array( $billing_interval, [ 'monthly', 'yearly', 'none' ], true ) ) {
            $billing_interval = 'monthly';
        }

        // Get package from DB.
        $package = Mauriel_DB_Packages::get( $package_id );
        if ( ! $package ) {
            $this->redirect_with_error( 3, 'invalid_package' );
            return;
        }

        $monthly_price = (float) $package->price_monthly;
        $is_free       = 0.0 === $monthly_price || ( 0.0 === $monthly_price && 0.0 === (float) $package->price_yearly );

        if ( $is_free ) {
            // Free package: create subscription record, possibly approve listing.
            Mauriel_DB_Subscriptions::create( [
                'user_id'          => $user_id,
                'listing_id'       => $listing_id,
                'package_id'       => $package_id,
                'status'           => 'free',
                'billing_interval' => 'none',
            ] );

            update_post_meta( $listing_id, '_mauriel_package_id', $package_id );

            $auto_approve = (bool) get_option( 'mauriel_auto_approve_listings', 0 );

            if ( $auto_approve ) {
                wp_update_post( [
                    'ID'          => $listing_id,
                    'post_status' => 'publish',
                ] );
                update_post_meta( $listing_id, '_mauriel_approval_status', 'approved' );

                // Send approval email.
                $user    = get_userdata( $user_id );
                $listing = get_post( $listing_id );
                if ( $user && $listing ) {
                    $dashboard_page_id = (int) get_option( 'mauriel_dashboard_page_id', 0 );
                    $dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/dashboard/' );

                    $subject = sprintf(
                        __( '[%s] Your listing has been approved!', 'mauriel-service-directory' ),
                        get_bloginfo( 'name' )
                    );
                    $message = sprintf(
                        __( "Hello %s,\n\nYour listing has been approved and is now live.\n\nManage your listing: %s", 'mauriel-service-directory' ),
                        $user->display_name,
                        esc_url( $dashboard_url )
                    );
                    wp_mail( $user->user_email, $subject, $message );
                }
            } else {
                // Send admin notification.
                $admin_email = get_option( 'admin_email' );
                $review_url  = admin_url( 'post.php?post=' . $listing_id . '&action=edit' );
                $biz_name    = get_the_title( $listing_id );

                wp_mail(
                    $admin_email,
                    sprintf( __( '[%s] New free listing pending approval', 'mauriel-service-directory' ), get_bloginfo( 'name' ) ),
                    sprintf( __( 'A new free listing "%s" is pending approval. Review: %s', 'mauriel-service-directory' ), $biz_name, esc_url( $review_url ) )
                );
            }

            // Mark step complete.
            Mauriel_Onboarding::mark_step_complete( $user_id, 3 );

            // Redirect to dashboard.
            $dashboard_page_id = (int) get_option( 'mauriel_dashboard_page_id', 0 );
            $dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/dashboard/' );

            wp_redirect( $dashboard_url );
            exit;
        }

        // Paid package: create Stripe Checkout Session.
        $checkout_url = Mauriel_Stripe_Checkout::create_session( $user_id, $package_id, $billing_interval, $listing_id );

        if ( is_wp_error( $checkout_url ) ) {
            $this->redirect_with_error( 3, $checkout_url->get_error_code() );
            return;
        }

        // Mark step complete.
        Mauriel_Onboarding::mark_step_complete( $user_id, 3 );

        wp_redirect( $checkout_url );
        exit;
    }

    /**
     * Returns the pending listing ID for a given user.
     *
     * @param int $user_id WP user ID.
     *
     * @return int Listing post ID or 0.
     */
    public function get_pending_listing_id( int $user_id ): int {
        return (int) get_user_meta( $user_id, '_mauriel_pending_listing_id', true );
    }

    /**
     * Checks whether a registration step has been marked complete.
     *
     * @param int $step    Step number (1, 2, or 3).
     * @param int $user_id WP user ID.
     *
     * @return bool
     */
    public function is_step_complete( int $step, int $user_id ): bool {
        return (bool) get_user_meta( $user_id, "_mauriel_step_{$step}_complete", true );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Generates a unique WP username derived from an email address.
     *
     * @param string $email Email address.
     *
     * @return string Unique username.
     */
    private static function generate_username_from_email( string $email ): string {
        $base     = sanitize_user( strstr( $email, '@', true ), true );
        $base     = strtolower( $base );
        $username = $base;
        $i        = 1;

        while ( username_exists( $username ) ) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }

    /**
     * Redirects to the register page with an error query param.
     *
     * @param int    $step  Step number to redirect back to.
     * @param string $error Error code or comma-separated codes.
     *
     * @return void
     */
    private function redirect_with_error( int $step, string $error ): void {
        $register_page_id = (int) get_option( 'mauriel_register_page_id', 0 );
        $register_url     = $register_page_id ? get_permalink( $register_page_id ) : home_url( '/register/' );

        wp_redirect( add_query_arg( [
            'step'          => $step,
            'mauriel_error' => rawurlencode( $error ),
        ], $register_url ) );
        exit;
    }
}
