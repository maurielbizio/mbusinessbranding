<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Geocoder
 *
 * Handles geocoding via the Google Maps Geocoding API.
 */
class Mauriel_Geocoder {

    /** Base URL for Google Geocoding API. */
    const API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    /**
     * Geocodes a full address string.
     *
     * @param string $address Full address (e.g. "123 Main St, Austin, TX 78701").
     *
     * @return array|WP_Error Array with keys: lat, lng, city, state, zip, formatted_address — or WP_Error.
     */
    public static function geocode_address( string $address ) {
        if ( empty( $address ) ) {
            return new WP_Error( 'mauriel_empty_address', __( 'Address is empty.', 'mauriel-service-directory' ) );
        }

        $cache_key = 'mauriel_geocode_' . md5( $address );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $api_key = (string) get_option( 'mauriel_google_geocoding_key', '' );

        $url = add_query_arg( [
            'address' => rawurlencode( $address ),
            'key'     => $api_key,
        ], self::API_URL );

        $response = wp_remote_get( $url, [
            'timeout'   => 10,
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data ) || 'OK' !== $data['status'] ) {
            $error_msg = isset( $data['error_message'] ) ? $data['error_message'] : __( 'Geocoding failed.', 'mauriel-service-directory' );
            return new WP_Error( 'mauriel_geocode_failed', $error_msg );
        }

        $result          = $data['results'][0];
        $location        = $result['geometry']['location'];
        $components      = self::parse_address_components( $result['address_components'] );
        $formatted       = $result['formatted_address'];

        $parsed = [
            'lat'               => (float) $location['lat'],
            'lng'               => (float) $location['lng'],
            'city'              => $components['city'],
            'state'             => $components['state'],
            'zip'               => $components['zip'],
            'formatted_address' => $formatted,
        ];

        set_transient( $cache_key, $parsed, 7 * DAY_IN_SECONDS );

        return $parsed;
    }

    /**
     * Geocodes a ZIP code and returns its center coordinates and city/state info.
     *
     * @param string $zip ZIP/postal code.
     *
     * @return array|WP_Error Array with keys: lat, lng, city, state — or WP_Error.
     */
    public static function geocode_zip( string $zip ) {
        $zip = preg_replace( '/[^0-9A-Za-z\-]/', '', $zip );

        if ( empty( $zip ) ) {
            return new WP_Error( 'mauriel_empty_zip', __( 'ZIP code is empty.', 'mauriel-service-directory' ) );
        }

        $cache_key = 'mauriel_geocode_zip_' . md5( $zip );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $api_key = (string) get_option( 'mauriel_google_geocoding_key', '' );

        $url = add_query_arg( [
            'address'    => rawurlencode( $zip ),
            'components' => 'postal_code:' . rawurlencode( $zip ),
            'key'        => $api_key,
        ], self::API_URL );

        $response = wp_remote_get( $url, [
            'timeout'   => 10,
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data ) || 'OK' !== $data['status'] ) {
            $error_msg = isset( $data['error_message'] ) ? $data['error_message'] : __( 'ZIP geocoding failed.', 'mauriel-service-directory' );
            return new WP_Error( 'mauriel_geocode_zip_failed', $error_msg );
        }

        $result     = $data['results'][0];
        $location   = $result['geometry']['location'];
        $components = self::parse_address_components( $result['address_components'] );

        $parsed = [
            'lat'   => (float) $location['lat'],
            'lng'   => (float) $location['lng'],
            'city'  => $components['city'],
            'state' => $components['state'],
        ];

        set_transient( $cache_key, $parsed, 30 * DAY_IN_SECONDS );

        return $parsed;
    }

    /**
     * Reverse geocodes lat/lng coordinates to address components.
     *
     * @param float $lat Latitude.
     * @param float $lng Longitude.
     *
     * @return array|WP_Error Address components or WP_Error.
     */
    public static function reverse_geocode( float $lat, float $lng ) {
        $api_key = (string) get_option( 'mauriel_google_geocoding_key', '' );

        $url = add_query_arg( [
            'latlng' => rawurlencode( $lat . ',' . $lng ),
            'key'    => $api_key,
        ], self::API_URL );

        $response = wp_remote_get( $url, [
            'timeout'   => 10,
            'sslverify' => true,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data ) || 'OK' !== $data['status'] ) {
            $error_msg = isset( $data['error_message'] ) ? $data['error_message'] : __( 'Reverse geocoding failed.', 'mauriel-service-directory' );
            return new WP_Error( 'mauriel_reverse_geocode_failed', $error_msg );
        }

        $result     = $data['results'][0];
        $components = self::parse_address_components( $result['address_components'] );

        return [
            'formatted_address' => $result['formatted_address'],
            'city'              => $components['city'],
            'state'             => $components['state'],
            'zip'               => $components['zip'],
            'country'           => $components['country'],
            'lat'               => $lat,
            'lng'               => $lng,
        ];
    }

    /**
     * Returns an SQL expression string for calculating haversine distance in miles.
     *
     * The expression expects the postmeta JOIN aliases lat_meta and lng_meta
     * to exist in the query. Use with $wpdb->prepare().
     *
     * @param float $lat Reference latitude.
     * @param float $lng Reference longitude.
     *
     * @return string SQL fragment (not prepared — caller must use $wpdb->prepare()).
     */
    public static function haversine_sql_fragment( float $lat, float $lng ): string {
        $distance_unit = (string) get_option( 'mauriel_distance_unit', 'miles' );

        // 6371 = Earth radius in km. Multiply by 0.621371 for miles.
        if ( 'miles' === $distance_unit ) {
            $multiplier = '3958.8'; // Earth radius in miles directly.
        } else {
            $multiplier = '6371';
        }

        // Returns the haversine great-circle distance formula as a SQL string.
        // %f placeholders will be filled by $wpdb->prepare() by the caller.
        return '(' . $multiplier . ' * acos(' .
               'cos(radians(%f)) ' .
               '* cos(radians(lat_meta.meta_value)) ' .
               '* cos(radians(lng_meta.meta_value) - radians(%f)) ' .
               '+ sin(radians(%f)) ' .
               '* sin(radians(lat_meta.meta_value))' .
               '))';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parses Google address_components array into a flat associative array.
     *
     * @param array $components Google address_components array.
     *
     * @return array Keys: city, state, zip, country.
     */
    private static function parse_address_components( array $components ): array {
        $parsed = [
            'city'    => '',
            'state'   => '',
            'zip'     => '',
            'country' => '',
        ];

        foreach ( $components as $component ) {
            $types = $component['types'];

            if ( in_array( 'locality', $types, true ) ) {
                $parsed['city'] = $component['long_name'];
            } elseif ( in_array( 'sublocality_level_1', $types, true ) && empty( $parsed['city'] ) ) {
                $parsed['city'] = $component['long_name'];
            } elseif ( in_array( 'administrative_area_level_1', $types, true ) ) {
                $parsed['state'] = $component['short_name'];
            } elseif ( in_array( 'postal_code', $types, true ) ) {
                $parsed['zip'] = $component['long_name'];
            } elseif ( in_array( 'country', $types, true ) ) {
                $parsed['country'] = $component['short_name'];
            }
        }

        return $parsed;
    }
}
