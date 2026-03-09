<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_REST_Search
 *
 * REST endpoint for searching directory listings.
 */
class Mauriel_REST_Search extends Mauriel_REST_Controller {

    /**
     * Registers REST routes for search.
     *
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/search',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'handle_search' ],
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_search_args(),
                ],
            ]
        );
    }

    /**
     * Handles a search request.
     *
     * @param WP_REST_Request $request REST request object.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_search( WP_REST_Request $request ) {
        // Get all params from the request body.
        $raw_params = $request->get_json_params();

        // Fall back to query params if no JSON body.
        if ( empty( $raw_params ) || ! is_array( $raw_params ) ) {
            $raw_params = $request->get_params();
        }

        if ( ! is_array( $raw_params ) ) {
            $raw_params = [];
        }

        // Sanitize all params.
        $params = Mauriel_Search_Filters::sanitize( $raw_params );

        // Geocode ZIP if provided without lat/lng.
        if ( ! empty( $params['zip'] ) && ( null === $params['lat'] || null === $params['lng'] ) ) {
            $geocode = Mauriel_Geocoder::geocode_zip( $params['zip'] );
            if ( ! is_wp_error( $geocode ) ) {
                $params['lat'] = $geocode['lat'];
                $params['lng'] = $geocode['lng'];

                // Fill in city/state if empty.
                if ( empty( $params['city'] ) && ! empty( $geocode['city'] ) ) {
                    $params['city'] = $geocode['city'];
                }
                if ( empty( $params['state'] ) && ! empty( $geocode['state'] ) ) {
                    $params['state'] = $geocode['state'];
                }
            }
        }

        // Run the search.
        $result = Mauriel_Search::run( $params );

        return rest_ensure_response( [
            'listings'     => $result['listings'],
            'map_data'     => $result['map_data'],
            'total'        => $result['total'],
            'pages'        => $result['pages'],
            'current_page' => $result['current_page'],
            'per_page'     => $result['per_page'],
            'filters'      => $result['filters'],
            'success'      => true,
        ] );
    }

    /**
     * Returns the schema for search request arguments.
     *
     * @return array
     */
    private function get_search_args(): array {
        return [
            'keyword' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ],
            'category_slug' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_key',
                'default'           => '',
            ],
            'city' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ],
            'state' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ],
            'zip' => [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ],
            'radius_miles' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 25,
            ],
            'rating_min' => [
                'type'              => 'integer',
                'sanitize_callback' => 'intval',
                'default'           => 0,
            ],
            'featured_only' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'open_now' => [
                'type'    => 'boolean',
                'default' => false,
            ],
            'sort_by' => [
                'type'    => 'string',
                'default' => 'featured',
                'enum'    => [ 'featured', 'rating', 'newest', 'az', 'distance' ],
            ],
            'page' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 1,
            ],
            'per_page' => [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 12,
            ],
            'view' => [
                'type'    => 'string',
                'default' => 'grid',
                'enum'    => [ 'grid', 'list', 'map' ],
            ],
            'lat' => [
                'type'    => 'number',
                'default' => null,
            ],
            'lng' => [
                'type'    => 'number',
                'default' => null,
            ],
        ];
    }
}
