<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Search
 *
 * Main search engine for directory listings.
 */
class Mauriel_Search {

    /**
     * Stores the active radius filter parameters for use in the posts_clauses callback.
     *
     * @var array|null
     */
    private static $radius_params = null;

    /**
     * Stores the active sort_by value for use in the posts_clauses callback.
     *
     * @var string
     */
    private static $active_sort_by = 'featured';

    /**
     * Main entry point: sanitizes params, runs query, returns formatted results.
     *
     * @param array $params Raw search parameters.
     *
     * @return array {
     *     @type array  $listings   Formatted listing data.
     *     @type array  $map_data   Minimal marker data for JS map.
     *     @type int    $total      Total number of matching listings.
     *     @type int    $pages      Total number of pages.
     * }
     */
    public static function run( array $params ): array {
        $params = Mauriel_Search_Filters::sanitize( $params );

        // Build WP_Query args.
        $query_args = self::build_query_args( $params );

        // Apply radius filter if lat/lng/radius provided.
        $has_radius = null !== $params['lat'] && null !== $params['lng'];

        if ( $has_radius ) {
            self::$radius_params  = [
                'lat'          => (float) $params['lat'],
                'lng'          => (float) $params['lng'],
                'radius_miles' => (int) $params['radius_miles'],
                'sort_by'      => $params['sort_by'],
            ];
            self::$active_sort_by = $params['sort_by'];

            add_filter( 'posts_clauses', [ __CLASS__, 'apply_radius_clauses_filter' ], 10, 2 );
        }

        $query = new WP_Query( $query_args );

        if ( $has_radius ) {
            remove_filter( 'posts_clauses', [ __CLASS__, 'apply_radius_clauses_filter' ], 10 );
            self::$radius_params = null;
        }

        $posts  = $query->posts;
        $total  = (int) $query->found_posts;
        $pages  = (int) $query->max_num_pages;

        $listings = array_map( [ __CLASS__, 'format_listing' ], $posts );

        // Filter open_now if requested (post-query because requires PHP calculation).
        if ( $params['open_now'] ) {
            $listings = array_filter( $listings, static function ( $listing ) {
                return ! empty( $listing['open_now'] );
            } );
            $listings = array_values( $listings );
        }

        $map_data = self::get_map_markers( $listings );

        return self::format_response( $listings, $params, $total, $pages );
    }

    /**
     * Builds WP_Query args from sanitized params.
     *
     * @param array $params Sanitized params from Mauriel_Search_Filters::sanitize().
     *
     * @return array WP_Query args.
     */
    public static function build_query_args( array $params ): array {
        $args = [
            'post_type'      => 'mauriel_listing',
            'post_status'    => 'publish',
            'posts_per_page' => $params['per_page'],
            'offset'         => ( $params['page'] - 1 ) * $params['per_page'],
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'   => '_mauriel_approval_status',
                    'value' => 'approved',
                ],
            ],
        ];

        // Featured only filter.
        if ( $params['featured_only'] ) {
            $args['meta_query'][] = [
                'key'   => '_mauriel_featured',
                'value' => '1',
            ];
        }

        // Minimum rating filter.
        if ( $params['rating_min'] > 0 ) {
            $args['meta_query'][] = [
                'key'     => '_mauriel_avg_rating',
                'value'   => $params['rating_min'],
                'compare' => '>=',
                'type'    => 'DECIMAL',
            ];
        }

        // City filter.
        if ( ! empty( $params['city'] ) ) {
            $args['meta_query'][] = [
                'key'     => '_mauriel_city',
                'value'   => $params['city'],
                'compare' => 'LIKE',
            ];
        }

        // State filter.
        if ( ! empty( $params['state'] ) ) {
            $args['meta_query'][] = [
                'key'   => '_mauriel_state',
                'value' => $params['state'],
            ];
        }

        // ZIP filter.
        if ( ! empty( $params['zip'] ) ) {
            $args['meta_query'][] = [
                'key'   => '_mauriel_zip',
                'value' => $params['zip'],
            ];
        }

        // Bounding box pre-filter when lat/lng/radius provided.
        if ( null !== $params['lat'] && null !== $params['lng'] ) {
            $lat           = (float) $params['lat'];
            $lng           = (float) $params['lng'];
            $radius_miles  = (int) $params['radius_miles'];

            // Approx degrees per mile at mean latitude.
            $lat_deg = $radius_miles / 69.0;
            $lng_deg = $radius_miles / ( 69.0 * cos( deg2rad( $lat ) ) );

            $lat_min = $lat - $lat_deg;
            $lat_max = $lat + $lat_deg;
            $lng_min = $lng - $lng_deg;
            $lng_max = $lng + $lng_deg;

            $args['meta_query'][] = [
                'key'     => '_mauriel_lat',
                'value'   => [ $lat_min, $lat_max ],
                'compare' => 'BETWEEN',
                'type'    => 'DECIMAL(10,6)',
            ];

            $args['meta_query'][] = [
                'key'     => '_mauriel_lng',
                'value'   => [ $lng_min, $lng_max ],
                'compare' => 'BETWEEN',
                'type'    => 'DECIMAL(10,6)',
            ];
        }

        // Taxonomy filter — category.
        if ( ! empty( $params['category_slug'] ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'mauriel_category',
                    'field'    => 'slug',
                    'terms'    => $params['category_slug'],
                ],
            ];
        }

        // Keyword search.
        if ( ! empty( $params['keyword'] ) ) {
            $args['s'] = $params['keyword'];
        }

        // Ordering.
        switch ( $params['sort_by'] ) {
            case 'rating':
                $args['meta_key'] = '_mauriel_avg_rating';
                $args['orderby']  = 'meta_value_num';
                $args['order']    = 'DESC';
                break;

            case 'newest':
                $args['orderby'] = 'date';
                $args['order']   = 'DESC';
                break;

            case 'az':
                $args['orderby'] = 'title';
                $args['order']   = 'ASC';
                break;

            case 'distance':
                // Distance ordering handled in posts_clauses; default fallback.
                $args['orderby'] = 'date';
                $args['order']   = 'DESC';
                break;

            case 'featured':
            default:
                $args['meta_key'] = '_mauriel_featured';
                $args['orderby']  = [ 'meta_value_num' => 'DESC', 'date' => 'DESC' ];
                break;
        }

        return $args;
    }

    /**
     * WordPress posts_clauses filter callback that adds haversine distance JOIN + HAVING.
     *
     * @param array    $clauses  SQL clauses array.
     * @param WP_Query $wp_query WP_Query instance.
     *
     * @return array Modified clauses.
     */
    public static function apply_radius_clauses_filter( array $clauses, WP_Query $wp_query ): array {
        if ( null === self::$radius_params ) {
            return $clauses;
        }

        global $wpdb;

        $lat          = self::$radius_params['lat'];
        $lng          = self::$radius_params['lng'];
        $radius_miles = self::$radius_params['radius_miles'];
        $sort_by      = self::$radius_params['sort_by'];

        // Build haversine fragment using $wpdb->prepare.
        $haversine_raw = Mauriel_Geocoder::haversine_sql_fragment( $lat, $lng );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $distance_expr = $wpdb->prepare( $haversine_raw, $lat, $lng, $lat );

        // JOIN lat and lng postmeta.
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS lat_meta ON ({$wpdb->posts}.ID = lat_meta.post_id AND lat_meta.meta_key = '_mauriel_lat') ";
        $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS lng_meta ON ({$wpdb->posts}.ID = lng_meta.post_id AND lng_meta.meta_key = '_mauriel_lng') ";

        // Add distance to SELECT.
        $clauses['fields'] .= ", ({$distance_expr}) AS mauriel_distance";

        // HAVING clause to restrict to radius.
        if ( ! empty( $clauses['groupby'] ) ) {
            $clauses['groupby'] .= " HAVING mauriel_distance <= {$radius_miles}";
        } else {
            $clauses['groupby'] = "{$wpdb->posts}.ID HAVING mauriel_distance <= {$radius_miles}";
        }

        // Order by distance if requested.
        if ( 'distance' === $sort_by ) {
            $clauses['orderby'] = 'mauriel_distance ASC';
        }

        return $clauses;
    }

    /**
     * Formats a single WP_Post object into an array suitable for API responses.
     *
     * @param WP_Post $post Post object.
     *
     * @return array Formatted listing data.
     */
    public static function format_listing( WP_Post $post ): array {
        $id       = (int) $post->ID;
        $meta     = get_post_meta( $id );

        $get_meta = static function ( string $key, $default = '' ) use ( $meta ) {
            return isset( $meta[ $key ][0] ) ? $meta[ $key ][0] : $default;
        };

        // Category.
        $terms         = wp_get_post_terms( $id, 'mauriel_category', [ 'fields' => 'all' ] );
        $category_name = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';

        // Logo and cover image.
        $logo_id    = (int) $get_meta( '_mauriel_logo_id', 0 );
        $cover_id   = (int) $get_meta( '_mauriel_cover_id', 0 );
        $logo_url   = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
        $cover_url  = $cover_id ? wp_get_attachment_image_url( $cover_id, 'large' ) : '';

        // Open now status.
        $open_now = false;
        if ( class_exists( 'Mauriel_DB_Hours' ) ) {
            $open_now = (bool) Mauriel_DB_Hours::is_open_now( $id );
        }

        // Distance (set by radius filter query).
        $distance = null;
        if ( isset( $post->mauriel_distance ) ) {
            $distance = round( (float) $post->mauriel_distance, 1 );
        }

        return [
            'id'            => $id,
            'title'         => esc_html( get_the_title( $post ) ),
            'slug'          => $post->post_name,
            'permalink'     => get_permalink( $id ),
            'lat'           => floatval( $get_meta( '_mauriel_lat', 0 ) ),
            'lng'           => floatval( $get_meta( '_mauriel_lng', 0 ) ),
            'address'       => esc_html( $get_meta( '_mauriel_address' ) ),
            'city'          => esc_html( $get_meta( '_mauriel_city' ) ),
            'state'         => esc_html( $get_meta( '_mauriel_state' ) ),
            'zip'           => esc_html( $get_meta( '_mauriel_zip' ) ),
            'phone'         => esc_html( $get_meta( '_mauriel_phone' ) ),
            'email'         => sanitize_email( $get_meta( '_mauriel_email' ) ),
            'website'       => esc_url( $get_meta( '_mauriel_website' ) ),
            'logo_url'      => $logo_url ? esc_url( $logo_url ) : '',
            'cover_url'     => $cover_url ? esc_url( $cover_url ) : '',
            'category_name' => esc_html( $category_name ),
            'package_id'    => (int) $get_meta( '_mauriel_package_id', 0 ),
            'featured'      => (bool) $get_meta( '_mauriel_featured', false ),
            'verified'      => (bool) $get_meta( '_mauriel_verified', false ),
            'avg_rating'    => round( floatval( $get_meta( '_mauriel_avg_rating', 0 ) ), 1 ),
            'review_count'  => (int) $get_meta( '_mauriel_review_count', 0 ),
            'tagline'       => esc_html( $get_meta( '_mauriel_tagline' ) ),
            'open_now'      => $open_now,
            'distance'      => $distance,
        ];
    }

    /**
     * Returns minimal map marker data from a listings array.
     *
     * @param array $listings Array of formatted listing arrays.
     *
     * @return array Array of map marker data.
     */
    public static function get_map_markers( array $listings ): array {
        return array_map( static function ( array $listing ): array {
            return [
                'id'       => $listing['id'],
                'lat'      => $listing['lat'],
                'lng'      => $listing['lng'],
                'title'    => $listing['title'],
                'url'      => $listing['permalink'],
                'rating'   => $listing['avg_rating'],
                'logo_url' => $listing['logo_url'],
            ];
        }, $listings );
    }

    /**
     * Builds the final response array.
     *
     * @param array $listings  Formatted listings.
     * @param array $params    Sanitized search params.
     * @param int   $total     Total number of matching posts.
     * @param int   $pages     Total number of pages.
     *
     * @return array Full response.
     */
    public static function format_response( array $listings, array $params, int $total, int $pages ): array {
        return [
            'listings'     => $listings,
            'map_data'     => self::get_map_markers( $listings ),
            'total'        => $total,
            'pages'        => $pages,
            'current_page' => $params['page'],
            'per_page'     => $params['per_page'],
            'filters'      => [
                'keyword'       => $params['keyword'],
                'category_slug' => $params['category_slug'],
                'city'          => $params['city'],
                'state'         => $params['state'],
                'zip'           => $params['zip'],
                'radius_miles'  => $params['radius_miles'],
                'sort_by'       => $params['sort_by'],
                'view'          => $params['view'],
            ],
        ];
    }
}
