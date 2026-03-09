<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Stripe_Webhook
 *
 * Handles all inbound Stripe webhook events.
 */
class Mauriel_Stripe_Webhook {

    /**
     * Entry point called by the REST endpoint.
     * Reads the raw request body, verifies the signature, and dispatches the event.
     *
     * @return true|WP_Error
     */
    public static function handle_request() {
        $payload   = file_get_contents( 'php://input' );
        $sig_header = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
        $webhook_secret = (string) get_option( 'mauriel_stripe_webhook_secret', '' );

        if ( empty( $webhook_secret ) ) {
            return new WP_Error(
                'mauriel_webhook_not_configured',
                __( 'Webhook secret is not configured.', 'mauriel-service-directory' ),
                [ 'status' => 500 ]
            );
        }

        try {
            $event = \Stripe\Webhook::constructEvent( $payload, $sig_header, $webhook_secret );
        } catch ( \Stripe\Exception\SignatureVerificationException $e ) {
            return new WP_Error(
                'mauriel_invalid_signature',
                __( 'Webhook signature verification failed.', 'mauriel-service-directory' ),
                [ 'status' => 400 ]
            );
        } catch ( \UnexpectedValueException $e ) {
            return new WP_Error(
                'mauriel_invalid_payload',
                __( 'Invalid webhook payload.', 'mauriel-service-directory' ),
                [ 'status' => 400 ]
            );
        }

        return self::dispatch( $event );
    }

    /**
     * Dispatches a Stripe event to the appropriate handler.
     *
     * @param \Stripe\Event $event
     *
     * @return true|WP_Error
     */
    public static function dispatch( \Stripe\Event $event ) {
        switch ( $event->type ) {
            case 'checkout.session.completed':
                return self::on_checkout_completed( $event );

            case 'customer.subscription.updated':
                return self::on_subscription_updated( $event );

            case 'customer.subscription.deleted':
                return self::on_subscription_deleted( $event );

            case 'customer.subscription.paused':
                return self::on_subscription_paused( $event );

            case 'customer.subscription.resumed':
                return self::on_subscription_resumed( $event );

            case 'invoice.paid':
                return self::on_invoice_paid( $event );

            case 'invoice.payment_failed':
                return self::on_invoice_failed( $event );

            case 'customer.updated':
                return self::on_customer_updated( $event );

            default:
                // Unhandled event type — return true to acknowledge receipt.
                return true;
        }
    }

    /**
     * Handles checkout.session.completed event.
     *
     * @param \Stripe\Event $event
     *
     * @return true|WP_Error
     */
    private static function on_checkout_completed( \Stripe\Event $event ) {
        $session = $event->data->object;

        // Extract metadata.
        $metadata         = $session->metadata;
        $user_id          = isset( $metadata->user_id ) ? (int) $metadata->user_id : 0;
        $package_id       = isset( $metadata->package_id ) ? (int) $metadata->package_id : 0;
        $listing_id       = isset( $metadata->listing_id ) ? (int) $metadata->listing_id : 0;
        $billing_interval = isset( $metadata->billing_interval ) ? sanitize_text_field( $metadata->billing_interval ) : 'monthly';

        if ( ! $user_id || ! $package_id || ! $listing_id ) {
            return new WP_Error(
                'mauriel_missing_metadata',
                __( 'Checkout session metadata is incomplete.', 'mauriel-service-directory' )
            );
        }

        $stripe_customer_id     = (string) $session->customer;
        $stripe_subscription_id = (string) $session->subscription;

        // Retrieve the full subscription object.
        try {
            $stripe       = Mauriel_Stripe::get_client();
            $subscription = $stripe->subscriptions->retrieve( $stripe_subscription_id );
        } catch ( \Exception $e ) {
            return new WP_Error( 'mauriel_stripe_error', $e->getMessage() );
        }

        $status               = (string) $subscription->status;
        $current_period_start = (int) $subscription->current_period_start;
        $current_period_end   = (int) $subscription->current_period_end;

        // Create DB subscription record.
        Mauriel_DB_Subscriptions::create( [
            'user_id'                 => $user_id,
            'listing_id'              => $listing_id,
            'package_id'              => $package_id,
            'stripe_customer_id'      => $stripe_customer_id,
            'stripe_subscription_id'  => $stripe_subscription_id,
            'status'                  => $status,
            'billing_interval'        => $billing_interval,
            'current_period_start'    => date( 'Y-m-d H:i:s', $current_period_start ),
            'current_period_end'      => date( 'Y-m-d H:i:s', $current_period_end ),
            'cancel_at_period_end'    => (int) $subscription->cancel_at_period_end,
        ] );

        // Update listing package meta.
        update_post_meta( $listing_id, '_mauriel_package_id', $package_id );

        // Auto-approve or send admin notification.
        $auto_approve = (bool) get_option( 'mauriel_auto_approve_listings', 0 );

        if ( $auto_approve ) {
            wp_update_post( [
                'ID'          => $listing_id,
                'post_status' => 'publish',
            ] );
            update_post_meta( $listing_id, '_mauriel_approval_status', 'approved' );
            self::send_approval_email( $user_id, $listing_id );
        } else {
            self::send_admin_pending_notification( $listing_id );
        }

        do_action( 'mauriel_checkout_completed', $user_id, $listing_id, $package_id, $stripe_subscription_id );

        return true;
    }

    /**
     * Handles customer.subscription.updated event.
     *
     * @param \Stripe\Event $event
     *
     * @return true
     */
    private static function on_subscription_updated( \Stripe\Event $event ) {
        $subscription           = $event->data->object;
        $stripe_subscription_id = (string) $subscription->id;
        $status                 = (string) $subscription->status;
        $current_period_start   = (int) $subscription->current_period_start;
        $current_period_end     = (int) $subscription->current_period_end;
        $cancel_at_period_end   = (int) $subscription->cancel_at_period_end;

        // Find existing DB record.
        $db_subscription = Mauriel_DB_Subscriptions::get_by_stripe_id( $stripe_subscription_id );

        if ( ! $db_subscription ) {
            return true;
        }

        $old_status = $db_subscription->status;

        Mauriel_DB_Subscriptions::update( $db_subscription->id, [
            'status'                => $status,
            'current_period_start'  => date( 'Y-m-d H:i:s', $current_period_start ),
            'current_period_end'    => date( 'Y-m-d H:i:s', $current_period_end ),
            'cancel_at_period_end'  => $cancel_at_period_end,
        ] );

        // Handle status change to canceled.
        if ( 'canceled' === $status && 'canceled' !== $old_status ) {
            self::handle_listing_downgrade( $db_subscription );
        }

        do_action( 'mauriel_subscription_updated', $db_subscription->id, $status, $old_status );

        return true;
    }

    /**
     * Handles customer.subscription.deleted event.
     *
     * @param \Stripe\Event $event
     *
     * @return true
     */
    private static function on_subscription_deleted( \Stripe\Event $event ) {
        $subscription           = $event->data->object;
        $stripe_subscription_id = (string) $subscription->id;

        $db_subscription = Mauriel_DB_Subscriptions::get_by_stripe_id( $stripe_subscription_id );

        if ( ! $db_subscription ) {
            return true;
        }

        // Mark subscription canceled.
        Mauriel_DB_Subscriptions::update( $db_subscription->id, [
            'status' => 'canceled',
        ] );

        // Downgrade listing.
        self::handle_listing_downgrade( $db_subscription );

        // Send cancellation email to owner.
        self::send_cancellation_email( $db_subscription->user_id, $db_subscription->listing_id );

        do_action( 'mauriel_subscription_deleted', $db_subscription->id );

        return true;
    }

    /**
     * Handles customer.subscription.paused event.
     *
     * @param \Stripe\Event $event
     *
     * @return true
     */
    private static function on_subscription_paused( \Stripe\Event $event ) {
        $subscription           = $event->data->object;
        $stripe_subscription_id = (string) $subscription->id;

        $db_subscription = Mauriel_DB_Subscriptions::get_by_stripe_id( $stripe_subscription_id );

        if ( $db_subscription ) {
            Mauriel_DB_Subscriptions::update( $db_subscription->id, [
                'status' => 'paused',
            ] );

            do_action( 'mauriel_subscription_paused', $db_subscription->id );
        }

        return true;
    }

    /**
     * Handles customer.subscription.resumed event.
     *
     * @param \Stripe\Event $event
     *
     * @return true
     */
    private static function on_subscription_resumed( \Stripe\Event $event ) {
        $subscription           = $event->data->object;
        $stripe_subscription_id = (string) $subscription->id;

        $db_subscription = Mauriel_DB_Subscriptions::get_by_stripe_id( $stripe_subscription_id );

        if ( $db_subscription ) {
            Mauriel_DB_Subscriptions::update( $db_subscription->id, [
                'status' => 'active',
            ] );

            do_action( 'mauriel_subscription_resumed', $db_subscription->id );
        }

        return true;
    }

    /**
     * Handles invoice.paid event.
     *
     * @param \Stripe\Event $event
     *
     * @return true
     */
    private static function on_invoice_paid( \Stripe\Event $event ) {
        $invoice                = $event->data->object;
        $stripe_subscription_id = (string) $invoice->subscription;

        if ( empty( $stripe_subscription_id ) ) {
            return true;
        }

        $db_subscription = Mauriel_DB_Subscriptions::get_by_stripe_id( $stripe_subscription_id );

        if ( ! $db_subscription ) {
            return true;
        }

        $period_end = isset( $invoice->lines->data[0]->period->end )
            ? (int) $invoice->lines->data[0]->period->end
            : 0;

        $update_data = [ 'status' => 'active' ];

        if ( $period_end > 0 ) {
            $update_data['current_period_end'] = date( 'Y-m-d H:i:s', $period_end );
        }

        Mauriel_DB_Subscriptions::update( $db_subscription->id, $update_data );

        do_action( 'mauriel_invoice_paid', $db_subscription->id, $invoice->id );

        return true;
    }

    /**
     * Handles invoice.payment_failed event.
     *
     * @param \Stripe\Event $event
     *
     * @return true
     */
    private static function on_invoice_failed( \Stripe\Event $event ) {
        $invoice                = $event->data->object;
        $stripe_subscription_id = (string) $invoice->subscription;

        if ( empty( $stripe_subscription_id ) ) {
            return true;
        }

        $db_subscription = Mauriel_DB_Subscriptions::get_by_stripe_id( $stripe_subscription_id );

        if ( ! $db_subscription ) {
            return true;
        }

        Mauriel_DB_Subscriptions::update( $db_subscription->id, [
            'status' => 'past_due',
        ] );

        // Send payment failure email to user.
        $user = get_userdata( (int) $db_subscription->user_id );
        if ( $user ) {
            $subject = sprintf(
                /* translators: %s: site name */
                __( '[%s] Payment failed for your subscription', 'mauriel-service-directory' ),
                get_bloginfo( 'name' )
            );

            $listing   = get_post( (int) $db_subscription->listing_id );
            $biz_name  = $listing ? get_the_title( $listing ) : __( 'your listing', 'mauriel-service-directory' );

            $dashboard_page_id = (int) get_option( 'mauriel_dashboard_page_id', 0 );
            $dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/dashboard/' );

            $message = sprintf(
                /* translators: 1: user display name, 2: business name, 3: dashboard URL */
                __(
                    "Hello %1\$s,\n\nWe were unable to process your payment for %2\$s. Please update your payment method to keep your listing active.\n\nManage your subscription: %3\$s\n\nThank you.",
                    'mauriel-service-directory'
                ),
                $user->display_name,
                $biz_name,
                esc_url( $dashboard_url )
            );

            wp_mail( $user->user_email, $subject, $message );
        }

        do_action( 'mauriel_invoice_payment_failed', $db_subscription->id, $invoice->id );

        return true;
    }

    /**
     * Handles customer.updated event — syncs email back to WP user meta.
     *
     * @param \Stripe\Event $event
     *
     * @return true
     */
    private static function on_customer_updated( \Stripe\Event $event ) {
        $customer           = $event->data->object;
        $stripe_customer_id = (string) $customer->id;
        $stripe_email       = sanitize_email( (string) $customer->email );

        if ( empty( $stripe_email ) ) {
            return true;
        }

        // Find WP user by stored Stripe customer ID.
        $users = get_users( [
            'meta_key'   => '_mauriel_stripe_customer_id',
            'meta_value' => $stripe_customer_id,
            'number'     => 1,
            'fields'     => 'ID',
        ] );

        if ( ! empty( $users ) ) {
            $user_id = (int) $users[0];
            update_user_meta( $user_id, '_mauriel_stripe_customer_email', $stripe_email );

            do_action( 'mauriel_stripe_customer_updated', $user_id, $stripe_customer_id );
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Private helper methods
    // -------------------------------------------------------------------------

    /**
     * Handles downgrading a listing when its subscription is canceled.
     *
     * @param object $db_subscription DB subscription record.
     */
    private static function handle_listing_downgrade( $db_subscription ): void {
        $listing_id = (int) $db_subscription->listing_id;

        if ( ! $listing_id ) {
            return;
        }

        // Look for a free package in the DB.
        $free_package = Mauriel_DB_Packages::get_free_package();

        if ( $free_package ) {
            update_post_meta( $listing_id, '_mauriel_package_id', (int) $free_package->id );
        }

        do_action( 'mauriel_listing_downgraded', $listing_id, $db_subscription );
    }

    /**
     * Sends approval email to the listing owner.
     *
     * @param int $user_id    WP user ID.
     * @param int $listing_id Listing post ID.
     */
    private static function send_approval_email( int $user_id, int $listing_id ): void {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return;
        }

        $listing  = get_post( $listing_id );
        $biz_name = $listing ? get_the_title( $listing ) : __( 'Your listing', 'mauriel-service-directory' );

        $dashboard_page_id = (int) get_option( 'mauriel_dashboard_page_id', 0 );
        $dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/dashboard/' );

        $listing_url = get_permalink( $listing_id );

        $subject = sprintf(
            /* translators: 1: business name, 2: site name */
            __( '[%2$s] Your listing "%1$s" has been approved!', 'mauriel-service-directory' ),
            $biz_name,
            get_bloginfo( 'name' )
        );

        $message = sprintf(
            /* translators: 1: user display name, 2: business name, 3: listing URL, 4: dashboard URL */
            __(
                "Hello %1\$s,\n\nGreat news! Your listing \"%2\$s\" has been approved and is now live.\n\nView your listing: %3\$s\n\nManage your listing: %4\$s\n\nThank you!",
                'mauriel-service-directory'
            ),
            $user->display_name,
            $biz_name,
            esc_url( $listing_url ),
            esc_url( $dashboard_url )
        );

        wp_mail( $user->user_email, $subject, $message );
    }

    /**
     * Sends pending approval notification to the admin.
     *
     * @param int $listing_id Listing post ID.
     */
    private static function send_admin_pending_notification( int $listing_id ): void {
        $admin_email = get_option( 'admin_email' );
        $listing     = get_post( $listing_id );
        $biz_name    = $listing ? get_the_title( $listing ) : __( 'New listing', 'mauriel-service-directory' );

        $review_url = admin_url( 'post.php?post=' . $listing_id . '&action=edit' );

        $subject = sprintf(
            /* translators: 1: business name, 2: site name */
            __( '[%2$s] New listing pending approval: %1$s', 'mauriel-service-directory' ),
            $biz_name,
            get_bloginfo( 'name' )
        );

        $message = sprintf(
            /* translators: 1: business name, 2: review URL */
            __(
                "A new listing \"%1\$s\" is pending your approval.\n\nReview it here: %2\$s",
                'mauriel-service-directory'
            ),
            $biz_name,
            esc_url( $review_url )
        );

        wp_mail( $admin_email, $subject, $message );
    }

    /**
     * Sends a subscription cancellation email to the listing owner.
     *
     * @param int $user_id    WP user ID.
     * @param int $listing_id Listing post ID.
     */
    private static function send_cancellation_email( int $user_id, int $listing_id ): void {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return;
        }

        $listing  = get_post( $listing_id );
        $biz_name = $listing ? get_the_title( $listing ) : __( 'Your listing', 'mauriel-service-directory' );

        $dashboard_page_id = (int) get_option( 'mauriel_dashboard_page_id', 0 );
        $dashboard_url     = $dashboard_page_id ? get_permalink( $dashboard_page_id ) : home_url( '/dashboard/' );

        $subject = sprintf(
            /* translators: 1: business name, 2: site name */
            __( '[%2$s] Your subscription for "%1$s" has been canceled', 'mauriel-service-directory' ),
            $biz_name,
            get_bloginfo( 'name' )
        );

        $message = sprintf(
            /* translators: 1: user display name, 2: business name, 3: dashboard URL */
            __(
                "Hello %1\$s,\n\nYour subscription for \"%2\$s\" has been canceled. Your listing may have been downgraded to a free plan.\n\nYou can resubscribe from your dashboard: %3\$s\n\nThank you.",
                'mauriel-service-directory'
            ),
            $user->display_name,
            $biz_name,
            esc_url( $dashboard_url )
        );

        wp_mail( $user->user_email, $subject, $message );
    }
}
