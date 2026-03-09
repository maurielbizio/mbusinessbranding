<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Stripe
 *
 * Core Stripe helper: client singleton, key resolution, mode detection.
 */
class Mauriel_Stripe {

    /** @var \Stripe\StripeClient|null */
    private static $client = null;

    /**
     * Returns a singleton \Stripe\StripeClient initialized with the active secret key.
     *
     * @return \Stripe\StripeClient
     * @throws RuntimeException If Stripe is not configured.
     */
    public static function get_client(): \Stripe\StripeClient {
        if ( null === self::$client ) {
            $secret_key = self::get_secret_key();
            if ( empty( $secret_key ) ) {
                throw new \RuntimeException( 'Stripe is not configured. Please set the secret key in plugin settings.' );
            }
            self::$client = new \Stripe\StripeClient( $secret_key );
        }
        return self::$client;
    }

    /**
     * Reads mode from mauriel_stripe_mode option and returns the appropriate secret key.
     *
     * @return string
     */
    public static function get_secret_key(): string {
        $mode = self::get_mode();
        if ( 'live' === $mode ) {
            return (string) get_option( 'mauriel_stripe_secret_key_live', '' );
        }
        return (string) get_option( 'mauriel_stripe_secret_key_test', '' );
    }

    /**
     * Returns the appropriate publishable key based on current mode.
     *
     * @return string
     */
    public static function get_publishable_key(): string {
        $mode = self::get_mode();
        if ( 'live' === $mode ) {
            return (string) get_option( 'mauriel_stripe_pub_key_live', '' );
        }
        return (string) get_option( 'mauriel_stripe_pub_key_test', '' );
    }

    /**
     * Returns true if Stripe is configured (secret key is set and non-empty).
     *
     * @return bool
     */
    public static function is_configured(): bool {
        return '' !== self::get_secret_key();
    }

    /**
     * Returns current Stripe mode: 'test' or 'live'.
     *
     * @return string
     */
    public static function get_mode(): string {
        $mode = get_option( 'mauriel_stripe_mode', 'test' );
        return 'live' === $mode ? 'live' : 'test';
    }

    /**
     * Resets the client singleton. Useful for testing or after settings changes.
     *
     * @return void
     */
    public static function reset_client(): void {
        self::$client = null;
    }
}
