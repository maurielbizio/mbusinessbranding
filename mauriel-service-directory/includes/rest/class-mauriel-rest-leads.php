<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_REST_Leads
 *
 * REST endpoints for lead management.
 */
class Mauriel_REST_Leads extends Mauriel_REST_Controller {

    /**
     * Registers REST routes for leads.
     *
     * @return void
     */
    public function register_routes(): void {
        // POST mauriel/v1/leads — submit a lead (public with rate limiting).
        register_rest_route(
            $this->namespace,
            '/leads',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'handle_submit_lead' ],
                    'permission_callback' => '__return_true',
                ],
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'handle_get_leads' ],
                    'permission_callback' => [ $this, 'get_leads_permissions_check' ],
                ],
            ]
        );

        // POST mauriel/v1/leads/{id}/read — mark lead as read (owner only).
        register_rest_route(
            $this->namespace,
            '/leads/(?P<id>[\d]+)/read',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'handle_mark_read' ],
                    'permission_callback' => [ $this, 'mark_read_permissions_check' ],
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
     * Permission check for getting leads.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return bool|WP_Error
     */
    public function get_leads_permissions_check( WP_REST_Request $request ) {
        return $this->get_nonce_permission( $request );
    }

    /**
     * Permission check for marking a lead read.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return bool|WP_Error
     */
    public function mark_read_permissions_check( WP_REST_Request $request ) {
        return $this->get_nonce_permission( $request );
    }

    // -------------------------------------------------------------------------
    // Handlers
    // -------------------------------------------------------------------------

    /**
     * Handles a public lead form submission.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_submit_lead( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) || ! is_array( $params ) ) {
            $params = $request->get_params();
        }

        $listing_id = isset( $params['listing_id'] ) ? absint( $params['listing_id'] ) : 0;

        if ( ! $listing_id ) {
            return $this->error( 'mauriel_missing_listing_id', __( 'Listing ID is required.', 'mauriel-service-directory' ), 400 );
        }

        // Verify nonce — expected as mauriel_lead_{listing_id}.
        $nonce = isset( $params['nonce'] ) ? sanitize_text_field( $params['nonce'] ) : '';
        if ( ! wp_verify_nonce( $nonce, 'mauriel_lead_' . $listing_id ) ) {
            return $this->error( 'mauriel_invalid_nonce', __( 'Security check failed.', 'mauriel-service-directory' ), 403 );
        }

        // Verify listing exists and is published.
        $listing = get_post( $listing_id );
        if ( ! $listing || 'mauriel_listing' !== $listing->post_type || 'publish' !== $listing->post_status ) {
            return $this->error( 'mauriel_listing_not_found', __( 'Listing not found.', 'mauriel-service-directory' ), 404 );
        }

        // Rate limit: max 3 leads per hour per IP per listing.
        $ip               = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
        $rate_limit_key   = 'mauriel_lead_limit_' . hash( 'sha256', $ip ) . '_' . $listing_id;
        $current_count    = (int) get_transient( $rate_limit_key );

        if ( $current_count >= 3 ) {
            return $this->error(
                'mauriel_rate_limited',
                __( 'Too many submissions. Please try again later.', 'mauriel-service-directory' ),
                429
            );
        }

        // Sanitize inputs.
        $allowed_lead_types = [ 'contact', 'quote', 'appointment', 'general' ];
        $lead_type          = isset( $params['lead_type'] ) ? sanitize_key( $params['lead_type'] ) : 'general';
        if ( ! in_array( $lead_type, $allowed_lead_types, true ) ) {
            $lead_type = 'general';
        }

        $name    = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
        $email   = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
        $phone   = isset( $params['phone'] ) ? sanitize_text_field( $params['phone'] ) : '';
        $message = isset( $params['message'] ) ? sanitize_textarea_field( $params['message'] ) : '';

        if ( empty( $name ) ) {
            return $this->error( 'mauriel_name_required', __( 'Name is required.', 'mauriel-service-directory' ), 400 );
        }

        if ( empty( $email ) || ! is_email( $email ) ) {
            return $this->error( 'mauriel_invalid_email', __( 'A valid email address is required.', 'mauriel-service-directory' ), 400 );
        }

        // Build IP hash using salt.
        $salt     = defined( 'MAURIEL_IP_SALT' ) ? MAURIEL_IP_SALT : 'mauriel_default_salt';
        $ip_hash  = hash( 'sha256', $ip . $salt );

        // Create lead in DB.
        $lead_id = Mauriel_DB_Leads::create( [
            'listing_id' => $listing_id,
            'lead_type'  => $lead_type,
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'message'    => $message,
            'ip_hash'    => $ip_hash,
            'status'     => 'unread',
            'created_at' => current_time( 'mysql' ),
        ] );

        if ( ! $lead_id ) {
            return $this->error( 'mauriel_lead_failed', __( 'Could not save lead. Please try again.', 'mauriel-service-directory' ), 500 );
        }

        // Update rate limit counter.
        if ( 0 === $current_count ) {
            set_transient( $rate_limit_key, 1, HOUR_IN_SECONDS );
        } else {
            set_transient( $rate_limit_key, $current_count + 1, HOUR_IN_SECONDS );
        }

        // Fire action hook.
        do_action( 'mauriel_new_lead', $lead_id, $listing_id );

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Your message has been sent successfully.', 'mauriel-service-directory' ),
        ] );
    }

    /**
     * Returns paginated leads for an owner's listing.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_get_leads( WP_REST_Request $request ) {
        $user_id    = get_current_user_id();
        $listing_id = (int) $request->get_param( 'listing_id' );

        if ( ! $listing_id ) {
            return $this->error( 'mauriel_missing_listing_id', __( 'Listing ID is required.', 'mauriel-service-directory' ), 400 );
        }

        // Verify ownership.
        $ownership_check = $this->owner_permission_check( $request, $listing_id );
        if ( is_wp_error( $ownership_check ) ) {
            return $ownership_check;
        }

        $page     = max( 1, (int) $request->get_param( 'page' ) );
        $per_page = min( 50, max( 1, (int) $request->get_param( 'per_page' ) ) );
        if ( ! $per_page ) {
            $per_page = 20;
        }

        $leads = Mauriel_DB_Leads::get_for_listing( $listing_id, [
            'page'     => $page,
            'per_page' => $per_page,
        ] );

        $total = Mauriel_DB_Leads::count_for_listing( $listing_id );
        $pages = $total > 0 ? (int) ceil( $total / $per_page ) : 0;

        return rest_ensure_response( [
            'success'      => true,
            'leads'        => $leads,
            'total'        => $total,
            'pages'        => $pages,
            'current_page' => $page,
        ] );
    }

    /**
     * Marks a lead as read.
     *
     * @param WP_REST_Request $request REST request.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_mark_read( WP_REST_Request $request ) {
        $lead_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();

        $lead = Mauriel_DB_Leads::get( $lead_id );
        if ( ! $lead ) {
            return $this->error( 'mauriel_lead_not_found', __( 'Lead not found.', 'mauriel-service-directory' ), 404 );
        }

        // Verify ownership of the listing associated with the lead.
        $ownership_check = $this->owner_permission_check( $request, (int) $lead->listing_id );
        if ( is_wp_error( $ownership_check ) ) {
            return $ownership_check;
        }

        Mauriel_DB_Leads::update( $lead_id, [ 'status' => 'read' ] );

        return rest_ensure_response( [
            'success' => true,
            'message' => __( 'Lead marked as read.', 'mauriel-service-directory' ),
        ] );
    }
}
