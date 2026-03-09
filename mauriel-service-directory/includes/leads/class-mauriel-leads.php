<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Leads
 *
 * Handles lead capture, storage, and owner notification for directory listings.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_Leads {

	/**
	 * Allowed lead types.
	 *
	 * @var string[]
	 */
	private static $allowed_types = array(
		'contact',
		'quote_request',
		'callback_request',
		'email_inquiry',
		'phone_inquiry',
		'appointment_request',
	);

	/**
	 * Constructor.
	 *
	 * Hooks the lead-notification handler so it fires whenever a new lead is
	 * recorded, regardless of where the lead originates.
	 */
	public function __construct() {
		add_action( 'mauriel_new_lead', array( $this, 'send_notification' ), 10, 3 );
	}

	// -------------------------------------------------------------------------
	// Submit
	// -------------------------------------------------------------------------

	/**
	 * Validates, sanitises, and stores a new lead for a listing.
	 *
	 * @param  int    $listing_id  Post ID of the mauriel_listing.
	 * @param  string $type        Lead type — must be in self::$allowed_types.
	 * @param  array  $data        Lead data: name, email, phone, message.
	 * @param  int    $user_id     Optional: logged-in user ID (0 for guests).
	 * @return int|WP_Error        New lead ID or WP_Error on failure.
	 */
	public function submit( $listing_id, $type, array $data, $user_id = 0 ) {
		$listing_id = absint( $listing_id );
		$user_id    = absint( $user_id );

		// ------------------------------------------------------------------
		// Validate listing.
		// ------------------------------------------------------------------
		$listing = get_post( $listing_id );
		if (
			! $listing
			|| 'mauriel_listing' !== $listing->post_type
			|| 'publish' !== $listing->post_status
		) {
			return new WP_Error(
				'invalid_listing',
				__( 'The listing does not exist or is not published.', 'mauriel-service-directory' )
			);
		}

		// ------------------------------------------------------------------
		// Validate lead type.
		// ------------------------------------------------------------------
		$type = sanitize_key( $type );
		if ( ! in_array( $type, self::$allowed_types, true ) ) {
			return new WP_Error(
				'invalid_lead_type',
				sprintf(
					/* translators: %s: comma-separated list of allowed types */
					__( 'Invalid lead type. Allowed: %s', 'mauriel-service-directory' ),
					implode( ', ', self::$allowed_types )
				)
			);
		}

		// ------------------------------------------------------------------
		// Sanitise fields.
		// ------------------------------------------------------------------
		$name    = isset( $data['name'] )    ? sanitize_text_field( $data['name'] )    : '';
		$email   = isset( $data['email'] )   ? sanitize_email( $data['email'] )         : '';
		$phone   = isset( $data['phone'] )   ? sanitize_text_field( $data['phone'] )   : '';
		$message = isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : '';

		if ( '' === $name ) {
			return new WP_Error(
				'missing_name',
				__( 'Name is required.', 'mauriel-service-directory' )
			);
		}

		if ( '' === $email && '' === $phone ) {
			return new WP_Error(
				'missing_contact',
				__( 'At least one contact method (email or phone) is required.', 'mauriel-service-directory' )
			);
		}

		// ------------------------------------------------------------------
		// Build a privacy-safe IP hash for dedup / spam analysis.
		// ------------------------------------------------------------------
		$remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$ip_hash   = hash( 'sha256', $remote_ip . NONCE_SALT );

		// ------------------------------------------------------------------
		// Persist via DB layer.
		// ------------------------------------------------------------------
		$lead_data = array(
			'listing_id' => $listing_id,
			'lead_type'  => $type,
			'name'       => $name,
			'email'      => $email,
			'phone'      => $phone,
			'message'    => $message,
			'ip_hash'    => $ip_hash,
			'user_id'    => $user_id,
		);

		if ( ! class_exists( 'Mauriel_DB_Leads' ) ) {
			return new WP_Error(
				'db_class_missing',
				__( 'Mauriel_DB_Leads class is not available.', 'mauriel-service-directory' )
			);
		}

		$lead_id = Mauriel_DB_Leads::create( $lead_data );

		if ( is_wp_error( $lead_id ) ) {
			return $lead_id;
		}

		if ( ! $lead_id ) {
			return new WP_Error(
				'insert_failed',
				__( 'Failed to save the lead. Please try again.', 'mauriel-service-directory' )
			);
		}

		// ------------------------------------------------------------------
		// Fire action — triggers send_notification() and any third-party hooks.
		// ------------------------------------------------------------------

		/**
		 * Fires after a new lead is stored.
		 *
		 * @param int    $lead_id     Newly created lead ID.
		 * @param int    $listing_id  The listing the lead is for.
		 * @param string $type        Lead type slug.
		 */
		do_action( 'mauriel_new_lead', $lead_id, $listing_id, $type );

		// ------------------------------------------------------------------
		// Record analytics event.
		// ------------------------------------------------------------------
		if ( class_exists( 'Mauriel_Analytics' ) ) {
			Mauriel_Analytics::record( $listing_id, 'lead_submit' );
		}

		return $lead_id;
	}

	// -------------------------------------------------------------------------
	// Notification
	// -------------------------------------------------------------------------

	/**
	 * Sends an email notification to the listing owner when a new lead arrives.
	 *
	 * Hooked to 'mauriel_new_lead'.
	 *
	 * @param  int    $lead_id     Lead ID.
	 * @param  int    $listing_id  Listing post ID.
	 * @param  string $lead_type   Lead type slug.
	 * @return void
	 */
	public function send_notification( $lead_id, $listing_id, $lead_type ) {
		// Check the notification toggle.
		if ( ! (bool) get_option( 'mauriel_email_lead_notify', 1 ) ) {
			return;
		}

		$listing_id = absint( $listing_id );
		$lead_id    = absint( $lead_id );

		// Get the listing.
		$listing = get_post( $listing_id );
		if ( ! $listing ) {
			return;
		}

		// Get the owner's user account.
		$owner_id = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );
		if ( ! $owner_id ) {
			// Fall back to admin email if no owner is assigned.
			$owner_email = get_option( 'admin_email' );
			$owner_name  = get_bloginfo( 'name' );
		} else {
			$owner       = get_userdata( $owner_id );
			if ( ! $owner ) {
				return;
			}
			$owner_email = $owner->user_email;
			$owner_name  = $owner->display_name;
		}

		// Fetch lead details from DB.
		if ( ! class_exists( 'Mauriel_DB_Leads' ) ) {
			return;
		}

		$lead = Mauriel_DB_Leads::get( $lead_id );
		if ( ! $lead ) {
			return;
		}

		// Build email.
		$listing_name = esc_html( $listing->post_title );
		$lead_type_label = ucwords( str_replace( '_', ' ', $lead_type ) );

		$subject = sprintf(
			/* translators: %s: listing name */
			__( 'New lead for %s', 'mauriel-service-directory' ),
			$listing_name
		);

		$message  = sprintf( __( 'Hello %s,', 'mauriel-service-directory' ), $owner_name ) . "\n\n";
		$message .= sprintf(
			/* translators: 1: lead type label 2: listing name */
			__( 'You have received a new %1$s for your listing "%2$s".', 'mauriel-service-directory' ),
			$lead_type_label,
			$listing_name
		) . "\n\n";
		$message .= __( 'Lead Details', 'mauriel-service-directory' ) . "\n";
		$message .= str_repeat( '-', 40 ) . "\n";

		if ( ! empty( $lead->name ) ) {
			$message .= sprintf( __( 'Name:    %s', 'mauriel-service-directory' ), $lead->name ) . "\n";
		}
		if ( ! empty( $lead->email ) ) {
			$message .= sprintf( __( 'Email:   %s', 'mauriel-service-directory' ), $lead->email ) . "\n";
		}
		if ( ! empty( $lead->phone ) ) {
			$message .= sprintf( __( 'Phone:   %s', 'mauriel-service-directory' ), $lead->phone ) . "\n";
		}
		if ( ! empty( $lead->message ) ) {
			$message .= "\n" . __( 'Message:', 'mauriel-service-directory' ) . "\n";
			$message .= $lead->message . "\n";
		}

		$message .= "\n" . str_repeat( '-', 40 ) . "\n";
		$message .= sprintf(
			/* translators: %s: dashboard URL */
			__( 'View all leads in your dashboard: %s', 'mauriel-service-directory' ),
			admin_url( 'admin.php?page=mauriel-dashboard&tab=leads' )
		) . "\n\n";
		$message .= sprintf(
			/* translators: %s: site name */
			__( 'This notification was sent by %s.', 'mauriel-service-directory' ),
			get_bloginfo( 'name' )
		);

		// Build from headers.
		$from_name  = (string) get_option( 'mauriel_email_from_name', get_bloginfo( 'name' ) );
		$from_email = (string) get_option( 'mauriel_email_from', get_option( 'admin_email' ) );

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		);

		wp_mail( $owner_email, $subject, $message, $headers );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns the list of allowed lead types.
	 *
	 * @return string[]
	 */
	public static function get_allowed_types() {
		return self::$allowed_types;
	}
}
