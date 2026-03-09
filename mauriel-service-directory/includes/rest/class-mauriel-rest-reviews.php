<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_REST_Reviews
 *
 * REST endpoints for listing reviews.
 */
class Mauriel_REST_Reviews extends Mauriel_REST_Controller {

    /**
     * Registers REST routes for reviews.
     *
     * @return void
     */
    public function register_routes(): void {
        // POST — submit review (public, rate limited).
        // GET  — get reviews for a listing (public).
        register_rest_route(
            $this->namespace,
            '/reviews',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'handle_submit' ],
                    'permission_callback' => '__return_true',
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'handle_get_reviews' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        // POST mauriel/v1/reviews/{id}/approve — admin only.
        register_rest_route(
            $this->namespace,
            '/reviews/(?P<id>[\d]+)/approve',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'handle_approve' ],
                    'permission_callback' => [ $this, 'admin_permission_check' ],
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

        // POST mauriel/v1/reviews/{id}/trash — admin or owner.
        register_rest_route(
            $this->namespace,
            '/reviews/(?P<id>[\d]+)/trash',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'handle_trash' ],
                    'permission_callback' => [ $this, 'trash_permission_check' ],
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

        // POST mauriel/v1/reviews/{id}/respond — owner only.
        register_rest_route(
            $this->namespace,
            '/reviews/(?P<id>[\d]+)/respond',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'handle_respond' ],
                    'permission_callback' => [ $this, 'respond_permission_check' ],
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
     * Admin permission check.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return bool|WP_Error
     */
    public function admin_permission_check( WP_REST_Request $request ) {
        $nonce_check = $this->get_nonce_permission( $request );
        if ( is_wp_error( $nonce_check ) ) {
            return $nonce_check;
        }

        if ( ! $this->is_directory_admin() ) {
            return $this->error( 'mauriel_forbidden', __( 'Admin access required.', 'mauriel-service-directory' ), 403 );
        }

        return true;
    }

    /**
     * Trash permission check — admin or listing owner.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return bool|WP_Error
     */
    public function trash_permission_check( WP_REST_Request $request ) {
        $nonce_check = $this->get_nonce_permission( $request );
        if ( is_wp_error( $nonce_check ) ) {
            return $nonce_check;
        }

        if ( $this->is_directory_admin() ) {
            return true;
        }

        // Owner check: find the listing the comment belongs to.
        $comment_id = (int) $request->get_param( 'id' );
        $comment    = get_comment( $comment_id );

        if ( ! $comment ) {
            return $this->error( 'mauriel_not_found', __( 'Review not found.', 'mauriel-service-directory' ), 404 );
        }

        $listing_id = (int) $comment->comment_post_ID;
        return $this->owner_permission_check( $request, $listing_id );
    }

    /**
     * Respond permission check — owner only.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return bool|WP_Error
     */
    public function respond_permission_check( WP_REST_Request $request ) {
        $nonce_check = $this->get_nonce_permission( $request );
        if ( is_wp_error( $nonce_check ) ) {
            return $nonce_check;
        }

        $comment_id = (int) $request->get_param( 'id' );
        $comment    = get_comment( $comment_id );

        if ( ! $comment ) {
            return $this->error( 'mauriel_not_found', __( 'Review not found.', 'mauriel-service-directory' ), 404 );
        }

        $listing_id = (int) $comment->comment_post_ID;
        return $this->owner_permission_check( $request, $listing_id );
    }

    // -------------------------------------------------------------------------
    // Handlers
    // -------------------------------------------------------------------------

    /**
     * Handles a public review submission.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_submit( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) || ! is_array( $params ) ) {
            $params = $request->get_params();
        }

        $listing_id = isset( $params['listing_id'] ) ? absint( $params['listing_id'] ) : 0;

        if ( ! $listing_id ) {
            return $this->error( 'mauriel_missing_listing_id', __( 'Listing ID is required.', 'mauriel-service-directory' ), 400 );
        }

        // Verify nonce.
        $nonce = isset( $params['nonce'] ) ? sanitize_text_field( $params['nonce'] ) : '';
        if ( ! wp_verify_nonce( $nonce, 'mauriel_review_' . $listing_id ) ) {
            return $this->error( 'mauriel_invalid_nonce', __( 'Security check failed.', 'mauriel-service-directory' ), 403 );
        }

        // Verify listing exists and is published.
        $listing = get_post( $listing_id );
        if ( ! $listing || 'mauriel_listing' !== $listing->post_type || 'publish' !== $listing->post_status ) {
            return $this->error( 'mauriel_listing_not_found', __( 'Listing not found.', 'mauriel-service-directory' ), 404 );
        }

        // Rate limit: max 1 review per IP per listing per 24 hours.
        $ip             = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
        $rate_limit_key = 'mauriel_review_limit_' . hash( 'sha256', $ip ) . '_' . $listing_id;

        if ( get_transient( $rate_limit_key ) ) {
            return $this->error(
                'mauriel_rate_limited',
                __( 'You have already submitted a review for this listing today. Please try again tomorrow.', 'mauriel-service-directory' ),
                429
            );
        }

        // Sanitize inputs.
        $rating       = isset( $params['rating'] ) ? max( 1, min( 5, intval( $params['rating'] ) ) ) : 0;
        $author_name  = isset( $params['author_name'] ) ? sanitize_text_field( $params['author_name'] ) : '';
        $author_email = isset( $params['author_email'] ) ? sanitize_email( $params['author_email'] ) : '';
        $content      = isset( $params['content'] ) ? wp_kses_post( $params['content'] ) : '';

        if ( ! $rating ) {
            return $this->error( 'mauriel_rating_required', __( 'Rating is required (1-5).', 'mauriel-service-directory' ), 400 );
        }

        if ( empty( $author_name ) ) {
            return $this->error( 'mauriel_name_required', __( 'Your name is required.', 'mauriel-service-directory' ), 400 );
        }

        if ( empty( $author_email ) || ! is_email( $author_email ) ) {
            return $this->error( 'mauriel_invalid_email', __( 'A valid email address is required.', 'mauriel-service-directory' ), 400 );
        }

        // Auto-approve based on option.
        $auto_approve    = (bool) get_option( 'mauriel_auto_approve_reviews', 0 );
        $comment_approved = $auto_approve ? 1 : 0;

        // Insert comment.
        $comment_data = [
            'comment_post_ID'      => $listing_id,
            'comment_author'       => $author_name,
            'comment_author_email' => $author_email,
            'comment_content'      => $content,
            'comment_type'         => 'mauriel_review',
            'comment_approved'     => $comment_approved,
            'comment_author_IP'    => $ip,
            'comment_date'         => current_time( 'mysql' ),
            'comment_date_gmt'     => current_time( 'mysql', true ),
        ];

        $comment_id = wp_insert_comment( $comment_data );

        if ( ! $comment_id ) {
            return $this->error( 'mauriel_review_failed', __( 'Could not save review. Please try again.', 'mauriel-service-directory' ), 500 );
        }

        // Store rating as comment meta.
        add_comment_meta( $comment_id, '_mauriel_rating', $rating, true );

        // Recalculate average rating if approved.
        if ( $comment_approved ) {
            self::recalculate_average_rating( $listing_id );
        }

        // Set rate limit transient (24 hours).
        set_transient( $rate_limit_key, 1, DAY_IN_SECONDS );

        // Fire action hook.
        do_action( 'mauriel_review_submitted', $comment_id, $listing_id );

        return rest_ensure_response( [
            'success'  => true,
            'message'  => $auto_approve
                ? __( 'Thank you! Your review has been published.', 'mauriel-service-directory' )
                : __( 'Thank you! Your review is pending approval.', 'mauriel-service-directory' ),
            'approved' => $auto_approve,
        ] );
    }

    /**
     * Returns paginated reviews for a listing.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_get_reviews( WP_REST_Request $request ) {
        $listing_id = absint( $request->get_param( 'listing_id' ) );

        if ( ! $listing_id ) {
            return $this->error( 'mauriel_missing_listing_id', __( 'Listing ID is required.', 'mauriel-service-directory' ), 400 );
        }

        $page     = max( 1, absint( $request->get_param( 'page' ) ) );
        $per_page = min( 50, max( 1, absint( $request->get_param( 'per_page' ) ) ) );
        if ( ! $per_page ) {
            $per_page = 10;
        }

        $args = [
            'post_id'  => $listing_id,
            'type'     => 'mauriel_review',
            'status'   => 'approve',
            'number'   => $per_page,
            'offset'   => ( $page - 1 ) * $per_page,
            'orderby'  => 'comment_date',
            'order'    => 'DESC',
        ];

        $comments = get_comments( $args );
        $total    = (int) get_comments( array_merge( $args, [ 'count' => true, 'number' => 0, 'offset' => 0 ] ) );
        $pages    = $total > 0 ? (int) ceil( $total / $per_page ) : 0;

        $reviews = array_map( [ $this, 'format_review' ], $comments );

        return rest_ensure_response( [
            'success'      => true,
            'reviews'      => $reviews,
            'total'        => $total,
            'pages'        => $pages,
            'current_page' => $page,
        ] );
    }

    /**
     * Approves a review (admin only).
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_approve( WP_REST_Request $request ) {
        $comment_id = (int) $request->get_param( 'id' );
        $comment    = get_comment( $comment_id );

        if ( ! $comment || 'mauriel_review' !== $comment->comment_type ) {
            return $this->error( 'mauriel_not_found', __( 'Review not found.', 'mauriel-service-directory' ), 404 );
        }

        wp_set_comment_status( $comment_id, 'approve' );

        // Recalculate average.
        self::recalculate_average_rating( (int) $comment->comment_post_ID );

        do_action( 'mauriel_review_approved', $comment_id );

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Review approved.', 'mauriel-service-directory' ),
        ] );
    }

    /**
     * Trashes a review (admin or listing owner).
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_trash( WP_REST_Request $request ) {
        $comment_id = (int) $request->get_param( 'id' );
        $comment    = get_comment( $comment_id );

        if ( ! $comment || 'mauriel_review' !== $comment->comment_type ) {
            return $this->error( 'mauriel_not_found', __( 'Review not found.', 'mauriel-service-directory' ), 404 );
        }

        $listing_id = (int) $comment->comment_post_ID;

        wp_set_comment_status( $comment_id, 'trash' );

        // Recalculate average after removal.
        self::recalculate_average_rating( $listing_id );

        do_action( 'mauriel_review_trashed', $comment_id );

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Review removed.', 'mauriel-service-directory' ),
        ] );
    }

    /**
     * Adds an owner response to a review.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_respond( WP_REST_Request $request ) {
        $comment_id = (int) $request->get_param( 'id' );
        $comment    = get_comment( $comment_id );

        if ( ! $comment || 'mauriel_review' !== $comment->comment_type ) {
            return $this->error( 'mauriel_not_found', __( 'Review not found.', 'mauriel-service-directory' ), 404 );
        }

        $params          = $request->get_json_params();
        $response_text   = isset( $params['response'] ) ? wp_kses_post( $params['response'] ) : '';

        if ( empty( $response_text ) ) {
            return $this->error( 'mauriel_response_empty', __( 'Response text cannot be empty.', 'mauriel-service-directory' ), 400 );
        }

        // Max 1000 characters.
        if ( strlen( strip_tags( $response_text ) ) > 1000 ) {
            return $this->error( 'mauriel_response_too_long', __( 'Response must be 1000 characters or less.', 'mauriel-service-directory' ), 400 );
        }

        update_comment_meta( $comment_id, '_mauriel_owner_response', $response_text );
        update_comment_meta( $comment_id, '_mauriel_owner_response_date', current_time( 'mysql' ) );

        do_action( 'mauriel_review_responded', $comment_id, $response_text );

        return rest_ensure_response( [
            'success'  => true,
            'message'  => __( 'Response saved.', 'mauriel-service-directory' ),
            'response' => $response_text,
        ] );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Recalculates and stores the average rating for a listing.
     *
     * @param int $listing_id Listing post ID.
     *
     * @return void
     */
    public static function recalculate_average_rating( int $listing_id ): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $avg = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(cm.meta_value)
                 FROM {$wpdb->commentmeta} cm
                 INNER JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id
                 WHERE cm.meta_key = '_mauriel_rating'
                   AND c.comment_post_ID = %d
                   AND c.comment_approved = '1'
                   AND c.comment_type = 'mauriel_review'",
                $listing_id
            )
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(c.comment_ID)
                 FROM {$wpdb->comments} c
                 WHERE c.comment_post_ID = %d
                   AND c.comment_approved = '1'
                   AND c.comment_type = 'mauriel_review'",
                $listing_id
            )
        );

        $avg_rounded = $avg ? round( (float) $avg, 1 ) : 0.0;

        update_post_meta( $listing_id, '_mauriel_avg_rating', $avg_rounded );
        update_post_meta( $listing_id, '_mauriel_review_count', (int) $count );
    }

    /**
     * Formats a WP_Comment object into an array for API responses.
     *
     * @param WP_Comment $comment Comment object.
     *
     * @return array
     */
    private function format_review( WP_Comment $comment ): array {
        $owner_response      = get_comment_meta( $comment->comment_ID, '_mauriel_owner_response', true );
        $owner_response_date = get_comment_meta( $comment->comment_ID, '_mauriel_owner_response_date', true );

        return [
            'id'                  => (int) $comment->comment_ID,
            'listing_id'          => (int) $comment->comment_post_ID,
            'author_name'         => esc_html( $comment->comment_author ),
            'content'             => wp_kses_post( $comment->comment_content ),
            'rating'              => (int) get_comment_meta( $comment->comment_ID, '_mauriel_rating', true ),
            'date'                => $comment->comment_date,
            'status'              => $comment->comment_approved,
            'owner_response'      => $owner_response ? wp_kses_post( $owner_response ) : '',
            'owner_response_date' => $owner_response_date ?: '',
        ];
    }
}
