<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Stripe_Checkout
 *
 * Handles creation of Stripe Checkout Sessions, Customer Portal sessions,
 * and Stripe Customer management.
 */
class Mauriel_Stripe_Checkout {

    /**
     * Creates a Stripe Checkout Session for subscribing to a package.
     *
     * @param int    $user_id          WordPress user ID.
     * @param int    $package_id       Package DB ID.
     * @param string $billing_interval 'monthly' or 'yearly'.
     * @param int    $listing_id       Listing post ID.
     *
     * @return string|WP_Error Checkout session URL on success, WP_Error on failure.
     */
    public static function create_session( int $user_id, int $package_id, string $billing_interval, int $listing_id ) {
        if ( ! Mauriel_Stripe::is_configured() ) {
            return new WP_Error( 'mauriel_stripe_not_configured', __( 'Stripe is not configured.', 'mauriel-service-directory' ) );
        }

        // Get package from DB.
        $package = Mauriel_DB_Packages::get( $package_id );
        if ( ! $package ) {
            return new WP_Error( 'mauriel_invalid_package', __( 'Invalid package selected.', 'mauriel-service-directory' ) );
        }

        // Resolve price ID.
        $price_id = '';
        if ( 'yearly' === $billing_interval ) {
            $price_id = ! empty( $package->stripe_price_id_yearly ) ? $package->stripe_price_id_yearly : '';
        } else {
            $price_id = ! empty( $package->stripe_price_id_monthly ) ? $package->stripe_price_id_monthly : '';
        }

        if ( empty( $price_id ) ) {
            return new WP_Error( 'mauriel_no_price_id', __( 'Stripe price ID is not configured for this package.', 'mauriel-service-directory' ) );
        }

        // Get or create Stripe customer.
        $stripe_customer_id = self::get_or_create_customer( $user_id );
        if ( is_wp_error( $stripe_customer_id ) ) {
            return $stripe_customer_id;
        }

        // Build URLs.
        $dashboard_page_id = (int) get_option( 'mauriel_dashboard_page_id', 0 );
        $register_page_id  = (int) get_option( 'mauriel_register_page_id', 0 );

        $success_url = add_query_arg(
            [
                'mauriel_payment' => 'success',
                'listing_id'      => $listing_id,
            ],
            $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/dashboard/' )
        );

        $cancel_url = add_query_arg(
            [ 'step' => '3' ],
            $register_page_id ? get_permalink( $register_page_id ) : home_url( '/register/' )
        );

        try {
            $stripe  = Mauriel_Stripe::get_client();
            $session = $stripe->checkout->sessions->create( [
                'customer'           => $stripe_customer_id,
                'mode'               => 'subscription',
                'line_items'         => [
                    [
                        'price'    => $price_id,
                        'quantity' => 1,
                    ],
                ],
                'success_url'        => $success_url,
                'cancel_url'         => $cancel_url,
                'metadata'           => [
                    'user_id'          => (string) $user_id,
                    'package_id'       => (string) $package_id,
                    'listing_id'       => (string) $listing_id,
                    'billing_interval' => $billing_interval,
                ],
                'subscription_data'  => [
                    'metadata' => [
                        'user_id'          => (string) $user_id,
                        'package_id'       => (string) $package_id,
                        'listing_id'       => (string) $listing_id,
                        'billing_interval' => $billing_interval,
                    ],
                ],
            ] );

            return $session->url;

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new WP_Error(
                'mauriel_stripe_api_error',
                $e->getMessage(),
                [ 'status' => 500 ]
            );
        } catch ( \Exception $e ) {
            return new WP_Error(
                'mauriel_stripe_error',
                $e->getMessage(),
                [ 'status' => 500 ]
            );
        }
    }

    /**
     * Gets or creates a Stripe customer for a WordPress user.
     *
     * @param int $user_id WordPress user ID.
     *
     * @return string|WP_Error Stripe customer ID on success, WP_Error on failure.
     */
    public static function get_or_create_customer( int $user_id ) {
        $wp_user = get_userdata( $user_id );
        if ( ! $wp_user ) {
            return new WP_Error( 'mauriel_invalid_user', __( 'Invalid user ID.', 'mauriel-service-directory' ) );
        }

        $existing_customer_id = get_user_meta( $user_id, '_mauriel_stripe_customer_id', true );

        if ( ! empty( $existing_customer_id ) ) {
            // Validate that the customer still exists in Stripe.
            try {
                $stripe   = Mauriel_Stripe::get_client();
                $customer = $stripe->customers->retrieve( $existing_customer_id );

                // If the customer is deleted in Stripe, create a fresh one.
                if ( isset( $customer->deleted ) && true === $customer->deleted ) {
                    $existing_customer_id = '';
                } else {
                    return $existing_customer_id;
                }
            } catch ( \Stripe\Exception\InvalidRequestException $e ) {
                // Customer not found; fall through to create.
                $existing_customer_id = '';
            } catch ( \Stripe\Exception\ApiErrorException $e ) {
                return new WP_Error( 'mauriel_stripe_api_error', $e->getMessage() );
            }
        }

        // Create a new Stripe customer.
        try {
            $stripe   = Mauriel_Stripe::get_client();
            $customer = $stripe->customers->create( [
                'email' => $wp_user->user_email,
                'name'  => $wp_user->display_name,
                'metadata' => [
                    'wp_user_id' => (string) $user_id,
                ],
            ] );

            update_user_meta( $user_id, '_mauriel_stripe_customer_id', $customer->id );

            return $customer->id;

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new WP_Error( 'mauriel_stripe_api_error', $e->getMessage() );
        } catch ( \Exception $e ) {
            return new WP_Error( 'mauriel_stripe_error', $e->getMessage() );
        }
    }

    /**
     * Creates a Stripe Customer Portal session.
     *
     * @param string $stripe_customer_id Stripe customer ID.
     * @param string $return_url         URL to return to after portal.
     *
     * @return string|WP_Error Portal session URL on success, WP_Error on failure.
     */
    public static function create_customer_portal_session( string $stripe_customer_id, string $return_url ) {
        if ( ! Mauriel_Stripe::is_configured() ) {
            return new WP_Error( 'mauriel_stripe_not_configured', __( 'Stripe is not configured.', 'mauriel-service-directory' ) );
        }

        if ( empty( $stripe_customer_id ) ) {
            return new WP_Error( 'mauriel_no_customer', __( 'No Stripe customer ID provided.', 'mauriel-service-directory' ) );
        }

        try {
            $stripe  = Mauriel_Stripe::get_client();
            $session = $stripe->billingPortal->sessions->create( [
                'customer'   => $stripe_customer_id,
                'return_url' => esc_url_raw( $return_url ),
            ] );

            return $session->url;

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new WP_Error( 'mauriel_stripe_api_error', $e->getMessage(), [ 'status' => 500 ] );
        } catch ( \Exception $e ) {
            return new WP_Error( 'mauriel_stripe_error', $e->getMessage(), [ 'status' => 500 ] );
        }
    }

    /**
     * Retrieves a Stripe Checkout Session by session ID.
     *
     * @param string $session_id Stripe session ID.
     *
     * @return \Stripe\Checkout\Session|WP_Error
     */
    public static function get_checkout_session( string $session_id ) {
        if ( ! Mauriel_Stripe::is_configured() ) {
            return new WP_Error( 'mauriel_stripe_not_configured', __( 'Stripe is not configured.', 'mauriel-service-directory' ) );
        }

        try {
            $stripe  = Mauriel_Stripe::get_client();
            $session = $stripe->checkout->sessions->retrieve( $session_id, [
                'expand' => [ 'subscription', 'customer' ],
            ] );

            return $session;

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new WP_Error( 'mauriel_stripe_api_error', $e->getMessage() );
        } catch ( \Exception $e ) {
            return new WP_Error( 'mauriel_stripe_error', $e->getMessage() );
        }
    }
}
