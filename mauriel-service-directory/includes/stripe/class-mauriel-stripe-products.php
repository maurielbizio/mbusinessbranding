<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Stripe_Products
 *
 * Manages syncing of plugin packages to Stripe Products and Prices.
 */
class Mauriel_Stripe_Products {

    /**
     * Syncs a plugin package to a Stripe Product and creates/updates Prices.
     *
     * @param int $package_id DB package ID.
     *
     * @return array|WP_Error Array with product_id, price_id_monthly, price_id_yearly, or WP_Error.
     */
    public static function sync_package_to_stripe( int $package_id ) {
        if ( ! Mauriel_Stripe::is_configured() ) {
            return new WP_Error(
                'mauriel_stripe_not_configured',
                __( 'Stripe is not configured.', 'mauriel-service-directory' )
            );
        }

        $package = Mauriel_DB_Packages::get( $package_id );
        if ( ! $package ) {
            return new WP_Error(
                'mauriel_invalid_package',
                __( 'Package not found.', 'mauriel-service-directory' )
            );
        }

        $currency        = strtolower( (string) get_option( 'mauriel_currency', 'usd' ) );
        $monthly_cents   = (int) round( (float) $package->price_monthly * 100 );
        $yearly_cents    = (int) round( (float) $package->price_yearly * 100 );
        $is_free         = 0 === $monthly_cents && 0 === $yearly_cents;

        // -----------------------------------------------------------------------
        // Step 1: Create or update the Stripe Product.
        // -----------------------------------------------------------------------
        $stripe_product_id = ! empty( $package->stripe_product_id ) ? (string) $package->stripe_product_id : '';

        if ( $stripe_product_id ) {
            // Update existing product.
            try {
                $stripe = Mauriel_Stripe::get_client();
                $stripe->products->update( $stripe_product_id, [
                    'name'        => (string) $package->name,
                    'description' => ! empty( $package->description ) ? (string) $package->description : '',
                ] );
            } catch ( \Stripe\Exception\ApiErrorException $e ) {
                return new WP_Error( 'mauriel_stripe_api_error', $e->getMessage() );
            }
        } else {
            // Create new product.
            $created_product_id = self::create_stripe_product( $package );
            if ( is_wp_error( $created_product_id ) ) {
                return $created_product_id;
            }
            $stripe_product_id = $created_product_id;

            // Persist new product ID.
            Mauriel_DB_Packages::update( $package_id, [
                'stripe_product_id' => $stripe_product_id,
            ] );
        }

        // -----------------------------------------------------------------------
        // Step 2: Handle Prices (skip for free tier).
        // -----------------------------------------------------------------------
        $price_id_monthly = null;
        $price_id_yearly  = null;

        if ( ! $is_free ) {
            // Monthly price.
            $price_id_monthly = self::sync_price(
                $stripe_product_id,
                $package_id,
                'monthly',
                $monthly_cents,
                ! empty( $package->stripe_price_id_monthly ) ? (string) $package->stripe_price_id_monthly : '',
                isset( $package->price_monthly_prev ) ? (int) round( (float) $package->price_monthly_prev * 100 ) : 0,
                $currency
            );

            if ( is_wp_error( $price_id_monthly ) ) {
                return $price_id_monthly;
            }

            // Yearly price.
            $price_id_yearly = self::sync_price(
                $stripe_product_id,
                $package_id,
                'yearly',
                $yearly_cents,
                ! empty( $package->stripe_price_id_yearly ) ? (string) $package->stripe_price_id_yearly : '',
                isset( $package->price_yearly_prev ) ? (int) round( (float) $package->price_yearly_prev * 100 ) : 0,
                $currency
            );

            if ( is_wp_error( $price_id_yearly ) ) {
                return $price_id_yearly;
            }

            // Persist price IDs.
            Mauriel_DB_Packages::update( $package_id, [
                'stripe_price_id_monthly' => $price_id_monthly,
                'stripe_price_id_yearly'  => $price_id_yearly,
            ] );
        } else {
            // Free tier — store NULL price IDs.
            Mauriel_DB_Packages::update( $package_id, [
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
            ] );
        }

        return [
            'product_id'       => $stripe_product_id,
            'price_id_monthly' => $price_id_monthly,
            'price_id_yearly'  => $price_id_yearly,
        ];
    }

    /**
     * Syncs a single Stripe Price for a given interval.
     * If a price ID already exists and the amount has changed, archives the old price and creates a new one.
     *
     * @param string $product_id       Stripe Product ID.
     * @param int    $package_id       DB package ID.
     * @param string $interval         'monthly' or 'yearly'.
     * @param int    $amount_cents     New amount in cents.
     * @param string $existing_price_id Existing Stripe Price ID (may be empty).
     * @param int    $prev_amount_cents Previously stored amount in cents (0 if unknown).
     * @param string $currency         Three-letter ISO currency.
     *
     * @return string|WP_Error New or existing price ID, or WP_Error.
     */
    private static function sync_price(
        string $product_id,
        int $package_id,
        string $interval,
        int $amount_cents,
        string $existing_price_id,
        int $prev_amount_cents,
        string $currency
    ) {
        if ( $existing_price_id ) {
            $price_changed = ( $prev_amount_cents !== $amount_cents && $prev_amount_cents > 0 );

            if ( $price_changed ) {
                // Archive old price and create a new one.
                $archive_result = self::archive_stripe_price( $existing_price_id );
                if ( is_wp_error( $archive_result ) ) {
                    return $archive_result;
                }

                return self::create_stripe_price( $product_id, $amount_cents, $interval, $currency );
            }

            // Price unchanged — return existing ID.
            return $existing_price_id;
        }

        // No existing price — create new.
        return self::create_stripe_price( $product_id, $amount_cents, $interval, $currency );
    }

    /**
     * Deactivates (archives) a Stripe Price object.
     *
     * @param string $price_id Stripe Price ID.
     *
     * @return true|WP_Error
     */
    public static function archive_stripe_price( string $price_id ) {
        try {
            $stripe = Mauriel_Stripe::get_client();
            $stripe->prices->update( $price_id, [ 'active' => false ] );
            return true;
        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new WP_Error( 'mauriel_stripe_api_error', $e->getMessage() );
        }
    }

    /**
     * Creates a Stripe Product.
     *
     * @param object $package DB package object.
     *
     * @return string|WP_Error Stripe Product ID or WP_Error.
     */
    public static function create_stripe_product( $package ) {
        try {
            $stripe = Mauriel_Stripe::get_client();
            $data   = [
                'name'     => (string) $package->name,
                'metadata' => [
                    'mauriel_package_id' => (string) $package->id,
                ],
            ];

            if ( ! empty( $package->description ) ) {
                $data['description'] = (string) $package->description;
            }

            $product = $stripe->products->create( $data );

            return $product->id;

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new WP_Error( 'mauriel_stripe_api_error', $e->getMessage() );
        } catch ( \Exception $e ) {
            return new WP_Error( 'mauriel_stripe_error', $e->getMessage() );
        }
    }

    /**
     * Creates a Stripe Price.
     *
     * @param string $product_id   Stripe Product ID.
     * @param int    $amount_cents Amount in cents.
     * @param string $interval     'monthly' or 'yearly'.
     * @param string $currency     ISO currency code (e.g. 'usd').
     *
     * @return string|WP_Error Stripe Price ID or WP_Error.
     */
    public static function create_stripe_price( string $product_id, int $amount_cents, string $interval, string $currency ) {
        $stripe_interval       = 'yearly' === $interval ? 'year' : 'month';
        $interval_count        = 1;

        try {
            $stripe = Mauriel_Stripe::get_client();
            $price  = $stripe->prices->create( [
                'product'        => $product_id,
                'unit_amount'    => $amount_cents,
                'currency'       => $currency,
                'recurring'      => [
                    'interval'       => $stripe_interval,
                    'interval_count' => $interval_count,
                ],
                'metadata'       => [
                    'mauriel_interval' => $interval,
                ],
            ] );

            return $price->id;

        } catch ( \Stripe\Exception\ApiErrorException $e ) {
            return new WP_Error( 'mauriel_stripe_api_error', $e->getMessage() );
        } catch ( \Exception $e ) {
            return new WP_Error( 'mauriel_stripe_error', $e->getMessage() );
        }
    }
}
