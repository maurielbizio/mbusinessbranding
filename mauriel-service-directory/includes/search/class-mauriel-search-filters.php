<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Search_Filters
 *
 * Sanitizes and normalizes raw search parameters.
 */
class Mauriel_Search_Filters {

    /** Allowed sort_by values. */
    const ALLOWED_SORT_BY = [ 'featured', 'rating', 'newest', 'az', 'distance' ];

    /** Allowed view values. */
    const ALLOWED_VIEWS = [ 'grid', 'list', 'map' ];

    /**
     * Sanitizes a raw params array and returns a clean, normalized array.
     *
     * @param array $params Raw input (typically from request body or query string).
     *
     * @return array Sanitized parameters.
     */
    public static function sanitize( array $params ): array {
        $default_sort    = (string) get_option( 'mauriel_default_sort', 'featured' );
        $default_per_page = (int) get_option( 'mauriel_listings_per_page', 12 );

        if ( ! in_array( $default_sort, self::ALLOWED_SORT_BY, true ) ) {
            $default_sort = 'featured';
        }

        // keyword — max 100 characters.
        $keyword = isset( $params['keyword'] ) ? sanitize_text_field( (string) $params['keyword'] ) : '';
        if ( strlen( $keyword ) > 100 ) {
            $keyword = substr( $keyword, 0, 100 );
        }

        // category_slug.
        $category_slug = isset( $params['category_slug'] ) ? sanitize_key( (string) $params['category_slug'] ) : '';

        // city.
        $city = isset( $params['city'] ) ? sanitize_text_field( (string) $params['city'] ) : '';

        // state — max 2 characters.
        $state = isset( $params['state'] ) ? sanitize_text_field( (string) $params['state'] ) : '';
        if ( strlen( $state ) > 2 ) {
            $state = substr( $state, 0, 2 );
        }

        // zip — digits only, max 10 characters.
        $zip = isset( $params['zip'] ) ? sanitize_text_field( (string) $params['zip'] ) : '';
        $zip = preg_replace( '/[^0-9]/', '', $zip );
        if ( strlen( $zip ) > 10 ) {
            $zip = substr( $zip, 0, 10 );
        }

        // radius_miles — absint, max 100, default 25.
        $radius_miles = isset( $params['radius_miles'] ) ? absint( $params['radius_miles'] ) : 25;
        $radius_miles = min( $radius_miles, 100 );
        if ( $radius_miles < 1 ) {
            $radius_miles = 25;
        }

        // rating_min — integer, clamped 1–5.
        $rating_min = isset( $params['rating_min'] ) ? intval( $params['rating_min'] ) : 0;
        if ( $rating_min > 0 ) {
            $rating_min = max( 1, min( 5, $rating_min ) );
        }

        // featured_only — boolean.
        $featured_only = isset( $params['featured_only'] ) ? (bool) $params['featured_only'] : false;

        // open_now — boolean.
        $open_now = isset( $params['open_now'] ) ? (bool) $params['open_now'] : false;

        // sort_by — must be in allowed list.
        $sort_by = isset( $params['sort_by'] ) ? sanitize_key( (string) $params['sort_by'] ) : $default_sort;
        if ( ! in_array( $sort_by, self::ALLOWED_SORT_BY, true ) ) {
            $sort_by = $default_sort;
        }

        // page — absint, min 1.
        $page = isset( $params['page'] ) ? absint( $params['page'] ) : 1;
        $page = max( 1, $page );

        // per_page — absint, min 1, max 50.
        $per_page = isset( $params['per_page'] ) ? absint( $params['per_page'] ) : $default_per_page;
        $per_page = max( 1, min( 50, $per_page ) );

        // view — must be in allowed list.
        $view = isset( $params['view'] ) ? sanitize_key( (string) $params['view'] ) : 'grid';
        if ( ! in_array( $view, self::ALLOWED_VIEWS, true ) ) {
            $view = 'grid';
        }

        // lat / lng — floatval if provided.
        $lat = isset( $params['lat'] ) && '' !== $params['lat'] ? floatval( $params['lat'] ) : null;
        $lng = isset( $params['lng'] ) && '' !== $params['lng'] ? floatval( $params['lng'] ) : null;

        // Validate lat/lng ranges.
        if ( null !== $lat && ( $lat < -90 || $lat > 90 ) ) {
            $lat = null;
        }
        if ( null !== $lng && ( $lng < -180 || $lng > 180 ) ) {
            $lng = null;
        }

        return [
            'keyword'       => $keyword,
            'category_slug' => $category_slug,
            'city'          => $city,
            'state'         => $state,
            'zip'           => $zip,
            'radius_miles'  => $radius_miles,
            'rating_min'    => $rating_min,
            'featured_only' => $featured_only,
            'open_now'      => $open_now,
            'sort_by'       => $sort_by,
            'page'          => $page,
            'per_page'      => $per_page,
            'view'          => $view,
            'lat'           => $lat,
            'lng'           => $lng,
        ];
    }
}
