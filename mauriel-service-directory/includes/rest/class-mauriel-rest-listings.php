<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_REST_Listings
 *
 * REST endpoints for directory listing CRUD.
 */
class Mauriel_REST_Listings extends Mauriel_REST_Controller {

    /**
     * Registers REST routes for listings.
     *
     * @return void
     */
    public function register_routes(): void {
        // GET mauriel/v1/listings — public list.
        register_rest_route(
            $this->namespace,
            '/listings',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_listings' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        // GET/POST/DELETE mauriel/v1/listings/{id}.
        register_rest_route(
            $this->namespace,
            '/listings/(?P<id>[\d]+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_listing' ],
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'id' => [
                            'required'          => true,
                            'validate_callback' => static function ( $param ) {
                                return is_numeric( $param ) && (int) $param > 0;
                            },
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update_listing' ],
                    'permission_callback' => [ $this, 'update_listing_permissions_check' ],
                    'args'                => [
                        'id' => [
                            'required'          => true,
                            'validate_callback' => static function ( $param ) {
                                return is_numeric( $param ) && (int) $param > 0;
                            },
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'delete_listing' ],
                    'permission_callback' => [ $this, 'delete_listing_permissions_check' ],
                    'args'                => [
                        'id' => [
                            'required'          => true,
                            'validate_callback' => static function ( $param ) {
                                return is_numeric( $param ) && (int) $param > 0;
                            },
                        ],
                    ],
                ],
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Permission callbacks
    // -------------------------------------------------------------------------

    /**
     * Permission check for updating a listing.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return bool|WP_Error
     */
    public function update_listing_permissions_check( WP_REST_Request $request ) {
        $listing_id = (int) $request->get_param( 'id' );
        return $this->owner_permission_check( $request, $listing_id );
    }

    /**
     * Permission check for deleting a listing.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return bool|WP_Error
     */
    public function delete_listing_permissions_check( WP_REST_Request $request ) {
        $nonce_check = $this->get_nonce_permission( $request );
        if ( is_wp_error( $nonce_check ) ) {
            return $nonce_check;
        }

        if ( ! $this->is_directory_admin() ) {
            return $this->error( 'mauriel_forbidden', __( 'Admin access required.', 'mauriel-service-directory' ), 403 );
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Handlers
    // -------------------------------------------------------------------------

    /**
     * GET /listings — public listing search (delegates to Mauriel_Search).
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response
     */
    public function get_listings( WP_REST_Request $request ) {
        $params = Mauriel_Search_Filters::sanitize( $request->get_params() );
        $result = Mauriel_Search::run( $params );

        return rest_ensure_response( $result );
    }

    /**
     * GET /listings/{id} — public single listing detail.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_listing( WP_REST_Request $request ) {
        $listing_id = (int) $request->get_param( 'id' );
        $post       = get_post( $listing_id );

        if ( ! $post || 'mauriel_listing' !== $post->post_type ) {
            return $this->error( 'mauriel_not_found', __( 'Listing not found.', 'mauriel-service-directory' ), 404 );
        }

        if ( 'publish' !== $post->post_status ) {
            // Only show to owner or admin.
            $owner_id = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );
            $user_id  = get_current_user_id();

            if ( ! $this->is_directory_admin() && $user_id !== $owner_id ) {
                return $this->error( 'mauriel_not_found', __( 'Listing not found.', 'mauriel-service-directory' ), 404 );
            }
        }

        $listing = Mauriel_Search::format_listing( $post );

        // Add extra detail fields for single view.
        $listing['description'] = wp_kses_post( (string) get_post_meta( $listing_id, '_mauriel_description', true ) );
        $listing['gallery']     = $this->get_gallery( $listing_id );
        $listing['social_links'] = $this->get_social_links( $listing_id );
        $listing['hours']        = class_exists( 'Mauriel_DB_Hours' ) ? Mauriel_DB_Hours::get_for_listing( $listing_id ) : [];

        return rest_ensure_response( $listing );
    }

    /**
     * POST /listings/{id} — update listing (owner only).
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function update_listing( WP_REST_Request $request ) {
        $listing_id = (int) $request->get_param( 'id' );
        $post       = get_post( $listing_id );

        if ( ! $post || 'mauriel_listing' !== $post->post_type ) {
            return $this->error( 'mauriel_not_found', __( 'Listing not found.', 'mauriel-service-directory' ), 404 );
        }

        $params = $request->get_json_params();
        if ( empty( $params ) || ! is_array( $params ) ) {
            $params = $request->get_params();
        }

        // Sanitize all updatable fields.
        $business_name = isset( $params['business_name'] ) ? sanitize_text_field( $params['business_name'] ) : null;
        $address       = isset( $params['address'] ) ? sanitize_text_field( $params['address'] ) : null;
        $city          = isset( $params['city'] ) ? sanitize_text_field( $params['city'] ) : null;
        $state         = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : null;
        $zip           = isset( $params['zip'] ) ? sanitize_text_field( $params['zip'] ) : null;
        $phone         = isset( $params['phone'] ) ? sanitize_text_field( $params['phone'] ) : null;
        $biz_email     = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : null;
        $website       = isset( $params['website'] ) ? esc_url_raw( $params['website'] ) : null;
        $description   = isset( $params['description'] ) ? wp_kses_post( $params['description'] ) : null;
        $tagline       = isset( $params['tagline'] ) ? sanitize_text_field( $params['tagline'] ) : null;
        $logo_id       = isset( $params['logo_id'] ) ? absint( $params['logo_id'] ) : null;
        $cover_id      = isset( $params['cover_id'] ) ? absint( $params['cover_id'] ) : null;
        $gallery_ids   = isset( $params['gallery_ids'] ) ? $params['gallery_ids'] : null;
        $social_links  = isset( $params['social_links'] ) ? $params['social_links'] : null;
        $category_id   = isset( $params['category_id'] ) ? absint( $params['category_id'] ) : null;

        // Update post title if business name provided.
        if ( null !== $business_name && ! empty( $business_name ) ) {
            wp_update_post( [
                'ID'         => $listing_id,
                'post_title' => $business_name,
            ] );
        }

        // Update post meta.
        $meta_map = [
            '_mauriel_address'     => $address,
            '_mauriel_city'        => $city,
            '_mauriel_state'       => $state,
            '_mauriel_zip'         => $zip,
            '_mauriel_phone'       => $phone,
            '_mauriel_email'       => $biz_email,
            '_mauriel_website'     => $website,
            '_mauriel_description' => $description,
            '_mauriel_tagline'     => $tagline,
            '_mauriel_logo_id'     => $logo_id,
            '_mauriel_cover_id'    => $cover_id,
        ];

        foreach ( $meta_map as $key => $value ) {
            if ( null !== $value ) {
                update_post_meta( $listing_id, $key, $value );
            }
        }

        // Handle gallery IDs as JSON array of ints.
        if ( null !== $gallery_ids ) {
            if ( is_array( $gallery_ids ) ) {
                $gallery_ids = array_map( 'absint', $gallery_ids );
                $gallery_ids = array_filter( $gallery_ids );
            } else {
                $gallery_ids = [];
            }
            update_post_meta( $listing_id, '_mauriel_gallery_ids', wp_json_encode( $gallery_ids ) );
        }

        // Handle social_links as JSON.
        if ( null !== $social_links ) {
            if ( is_array( $social_links ) ) {
                $sanitized_social = [];
                $allowed_keys     = [ 'facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok', 'pinterest' ];
                foreach ( $allowed_keys as $platform ) {
                    if ( isset( $social_links[ $platform ] ) ) {
                        $sanitized_social[ $platform ] = esc_url_raw( (string) $social_links[ $platform ] );
                    }
                }
                update_post_meta( $listing_id, '_mauriel_social_links', wp_json_encode( $sanitized_social ) );
            }
        }

        // Update category taxonomy.
        if ( null !== $category_id && $category_id > 0 ) {
            wp_set_post_terms( $listing_id, [ $category_id ], 'mauriel_category' );
        }

        // Re-geocode if address changed.
        $address_changed = ( null !== $address || null !== $city || null !== $state || null !== $zip );

        if ( $address_changed ) {
            $current_address = get_post_meta( $listing_id, '_mauriel_address', true );
            $current_city    = get_post_meta( $listing_id, '_mauriel_city', true );
            $current_state   = get_post_meta( $listing_id, '_mauriel_state', true );
            $current_zip     = get_post_meta( $listing_id, '_mauriel_zip', true );

            $full_address = trim( "$current_address, $current_city, $current_state $current_zip" );

            if ( ! empty( $full_address ) ) {
                $geocode = Mauriel_Geocoder::geocode_address( $full_address );
                if ( ! is_wp_error( $geocode ) ) {
                    update_post_meta( $listing_id, '_mauriel_lat', $geocode['lat'] );
                    update_post_meta( $listing_id, '_mauriel_lng', $geocode['lng'] );
                    update_post_meta( $listing_id, '_mauriel_formatted_address', $geocode['formatted_address'] );
                }
            }
        }

        $updated_post    = get_post( $listing_id );
        $updated_listing = Mauriel_Search::format_listing( $updated_post );

        do_action( 'mauriel_listing_updated', $listing_id, $params );

        return rest_ensure_response( [
            'success' => true,
            'listing' => $updated_listing,
        ] );
    }

    /**
     * DELETE /listings/{id} — delete listing (admin only).
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function delete_listing( WP_REST_Request $request ) {
        $listing_id = (int) $request->get_param( 'id' );
        $post       = get_post( $listing_id );

        if ( ! $post || 'mauriel_listing' !== $post->post_type ) {
            return $this->error( 'mauriel_not_found', __( 'Listing not found.', 'mauriel-service-directory' ), 404 );
        }

        $deleted = wp_delete_post( $listing_id, true );

        if ( ! $deleted ) {
            return $this->error( 'mauriel_delete_failed', __( 'Failed to delete listing.', 'mauriel-service-directory' ), 500 );
        }

        do_action( 'mauriel_listing_deleted', $listing_id );

        return rest_ensure_response( [
            'success'    => true,
            'message'    => __( 'Listing deleted successfully.', 'mauriel-service-directory' ),
            'listing_id' => $listing_id,
        ] );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the decoded gallery attachment IDs for a listing.
     *
     * @param int $listing_id Listing post ID.
     *
     * @return array Array of attachment IDs (integers).
     */
    private function get_gallery( int $listing_id ): array {
        $raw = get_post_meta( $listing_id, '_mauriel_gallery_ids', true );
        if ( empty( $raw ) ) {
            return [];
        }
        $decoded = json_decode( $raw, true );
        return is_array( $decoded ) ? array_map( 'absint', $decoded ) : [];
    }

    /**
     * Returns decoded social links for a listing.
     *
     * @param int $listing_id Listing post ID.
     *
     * @return array Associative array of platform => URL.
     */
    private function get_social_links( int $listing_id ): array {
        $raw = get_post_meta( $listing_id, '_mauriel_social_links', true );
        if ( empty( $raw ) ) {
            return [];
        }
        $decoded = json_decode( $raw, true );
        return is_array( $decoded ) ? $decoded : [];
    }
}
