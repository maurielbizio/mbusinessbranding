<?php
/**
 * Admin Listings Management
 *
 * WP_List_Table subclass for managing mauriel_listing CPT posts from the admin.
 * Also registers meta boxes on the CPT edit screen.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

// WP_List_Table is not included by default on all admin pages.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Mauriel_Admin_Listings
 */
class Mauriel_Admin_Listings extends WP_List_Table {

	/**
	 * Constructor — configure WP_List_Table and register meta box hooks.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'listing',
				'plural'   => 'listings',
				'ajax'     => false,
			)
		);

		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_mauriel_listing', array( $this, 'save_listing_meta' ), 10, 2 );
	}

	// -----------------------------------------------------------------------
	// WP_List_Table API
	// -----------------------------------------------------------------------

	/**
	 * Define the table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'              => '<input type="checkbox" />',
			'title'           => __( 'Business Name', 'mauriel-service-directory' ),
			'owner'           => __( 'Owner', 'mauriel-service-directory' ),
			'category'        => __( 'Category', 'mauriel-service-directory' ),
			'location'        => __( 'Location', 'mauriel-service-directory' ),
			'package'         => __( 'Package', 'mauriel-service-directory' ),
			'status'          => __( 'Status', 'mauriel-service-directory' ),
			'approval_status' => __( 'Approval', 'mauriel-service-directory' ),
			'date'            => __( 'Date', 'mauriel-service-directory' ),
		);
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'title'  => array( 'title', false ),
			'date'   => array( 'date', true ),
			'status' => array( 'post_status', false ),
		);
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param WP_Post $item Post object.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="post[]" value="%s" />',
			esc_attr( $item->ID )
		);
	}

	/**
	 * Render a column value, with row actions for the title column.
	 *
	 * @param WP_Post $item Post object.
	 * @param string  $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {

			case 'title':
				$edit_url    = get_edit_post_link( $item->ID );
				$approve_url = $this->action_url( $item->ID, 'approve' );
				$reject_url  = $this->action_url( $item->ID, 'reject' );
				$delete_url  = get_delete_post_link( $item->ID );

				$actions = array(
					'edit'    => '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'mauriel-service-directory' ) . '</a>',
					'approve' => '<a href="' . esc_url( $approve_url ) . '" class="mauriel-approve-link">' . __( 'Approve', 'mauriel-service-directory' ) . '</a>',
					'reject'  => '<a href="' . esc_url( $reject_url ) . '" class="mauriel-reject-link">' . __( 'Reject', 'mauriel-service-directory' ) . '</a>',
					'delete'  => '<a href="' . esc_url( $delete_url ) . '" class="submitdelete" onclick="return confirm(\'' . esc_js( __( 'Delete this listing?', 'mauriel-service-directory' ) ) . '\')">' . __( 'Delete', 'mauriel-service-directory' ) . '</a>',
				);

				return '<strong><a class="row-title" href="' . esc_url( $edit_url ) . '">' . esc_html( $item->post_title ) . '</a></strong>' . $this->row_actions( $actions );

			case 'owner':
				$author = get_userdata( $item->post_author );
				if ( $author ) {
					$user_edit = get_edit_user_link( $author->ID );
					return '<a href="' . esc_url( $user_edit ) . '">' . esc_html( $author->display_name ) . '</a>';
				}
				return '—';

			case 'category':
				$terms = get_the_terms( $item->ID, 'mauriel_category' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$names = wp_list_pluck( $terms, 'name' );
					return esc_html( implode( ', ', $names ) );
				}
				return '—';

			case 'location':
				$city  = get_post_meta( $item->ID, '_mauriel_city', true );
				$state = get_post_meta( $item->ID, '_mauriel_state', true );
				if ( $city || $state ) {
					return esc_html( trim( $city . ', ' . $state, ', ' ) );
				}
				return '—';

			case 'package':
				$pkg_id  = get_post_meta( $item->ID, '_mauriel_package_id', true );
				if ( $pkg_id ) {
					global $wpdb;
					$pkg = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}mauriel_packages WHERE id = %d", absint( $pkg_id ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					return $pkg ? esc_html( $pkg->name ) : esc_html( $pkg_id );
				}
				return __( 'Free', 'mauriel-service-directory' );

			case 'status':
				$status       = $item->post_status;
				$status_label = get_post_status_object( $status );
				return $status_label ? esc_html( $status_label->label ) : esc_html( $status );

			case 'approval_status':
				$approval = get_post_meta( $item->ID, '_mauriel_approval_status', true );
				if ( ! $approval ) {
					$approval = 'pending';
				}
				$badge_class = 'mauriel-badge';
				switch ( $approval ) {
					case 'approved':
						$badge_class .= ' mauriel-badge--green';
						break;
					case 'rejected':
						$badge_class .= ' mauriel-badge--red';
						break;
					default:
						$badge_class .= ' mauriel-badge--yellow';
				}
				return '<span class="' . esc_attr( $badge_class ) . '">' . esc_html( ucfirst( $approval ) ) . '</span>';

			case 'date':
				return esc_html( get_the_date( 'Y-m-d', $item->ID ) );

			default:
				return '—';
		}
	}

	/**
	 * Build a single-action admin URL with nonce.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $action  Action string.
	 * @return string URL.
	 */
	private function action_url( $post_id, $action ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'page'       => 'mauriel-directory',
					'action'     => 'mauriel_' . $action . '_listing',
					'listing_id' => $post_id,
				),
				admin_url( 'admin.php' )
			),
			'mauriel_' . $action . '_listing_' . $post_id
		);
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'mauriel_bulk_approve' => __( 'Approve', 'mauriel-service-directory' ),
			'mauriel_bulk_reject'  => __( 'Reject', 'mauriel-service-directory' ),
			'mauriel_bulk_delete'  => __( 'Delete', 'mauriel-service-directory' ),
		);
	}

	/**
	 * Process bulk and row-level actions.
	 */
	public function process_bulk_actions() {

		// ---- Row actions ---------------------------------------------------
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action     = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';
		$listing_id = isset( $_REQUEST['listing_id'] ) ? absint( $_REQUEST['listing_id'] ) : 0;
		// phpcs:enable

		if ( in_array( $action, array( 'mauriel_approve_listing', 'mauriel_reject_listing' ), true ) && $listing_id ) {

			$approval = ( 'mauriel_approve_listing' === $action ) ? 'approved' : 'rejected';
			check_admin_referer( 'mauriel_' . str_replace( 'mauriel_', '', str_replace( '_listing', '', $action ) ) . '_listing_' . $listing_id );

			update_post_meta( $listing_id, '_mauriel_approval_status', $approval );

			// Update post status
			if ( 'approved' === $approval ) {
				wp_update_post( array( 'ID' => $listing_id, 'post_status' => 'publish' ) );
			}

			// Notify business owner
			$this->send_approval_email( $listing_id, $approval );

			$redirect = add_query_arg( 'mauriel_notice', $approval, admin_url( 'admin.php?page=mauriel-directory' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		// ---- Bulk actions --------------------------------------------------
		$bulk_action = $this->current_action();

		if ( ! $bulk_action || ! in_array( $bulk_action, array( 'mauriel_bulk_approve', 'mauriel_bulk_reject', 'mauriel_bulk_delete' ), true ) ) {
			return;
		}

		check_admin_referer( 'bulk-listings' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_ids = isset( $_POST['post'] ) ? array_map( 'absint', (array) $_POST['post'] ) : array();

		if ( empty( $post_ids ) ) {
			return;
		}

		foreach ( $post_ids as $post_id ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			if ( 'mauriel_bulk_delete' === $bulk_action ) {
				wp_delete_post( $post_id, true );
			} elseif ( 'mauriel_bulk_approve' === $bulk_action ) {
				update_post_meta( $post_id, '_mauriel_approval_status', 'approved' );
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
				$this->send_approval_email( $post_id, 'approved' );
			} elseif ( 'mauriel_bulk_reject' === $bulk_action ) {
				update_post_meta( $post_id, '_mauriel_approval_status', 'rejected' );
				$this->send_approval_email( $post_id, 'rejected' );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mauriel-directory&mauriel_notice=bulk_done' ) );
		exit;
	}

	/**
	 * Send approval or rejection email to the listing owner.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $approval 'approved' or 'rejected'.
	 */
	private function send_approval_email( $post_id, $approval ) {
		if ( ! get_option( 'mauriel_email_approval_notify', 1 ) ) {
			return;
		}

		$post   = get_post( $post_id );
		$author = get_userdata( $post->post_author );
		if ( ! $author || ! $author->user_email ) {
			return;
		}

		$from_name  = get_option( 'mauriel_email_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'mauriel_email_from', get_option( 'admin_email' ) );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>',
		);

		if ( 'approved' === $approval ) {
			$subject = sprintf( __( 'Your listing "%s" has been approved!', 'mauriel-service-directory' ), $post->post_title );
			$message = sprintf(
				'<p>%s</p><p><a href="%s">%s</a></p>',
				sprintf( __( 'Great news! Your listing <strong>%s</strong> has been approved and is now live on the directory.', 'mauriel-service-directory' ), esc_html( $post->post_title ) ),
				esc_url( get_permalink( $post_id ) ),
				__( 'View Your Listing', 'mauriel-service-directory' )
			);
		} else {
			$subject = sprintf( __( 'Your listing "%s" needs attention', 'mauriel-service-directory' ), $post->post_title );
			$message = sprintf(
				'<p>%s</p>',
				sprintf( __( 'Your listing <strong>%s</strong> was not approved at this time. Please contact us for more information.', 'mauriel-service-directory' ), esc_html( $post->post_title ) )
			);
		}

		wp_mail( $author->user_email, $subject, $message, $headers );
	}

	/**
	 * Query listings and set up pagination.
	 */
	public function prepare_items() {

		$this->process_bulk_actions();

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search          = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
		$filter_status   = isset( $_REQUEST['approval_status'] ) ? sanitize_key( $_REQUEST['approval_status'] ) : '';
		$filter_category = isset( $_REQUEST['category'] ) ? absint( $_REQUEST['category'] ) : 0;
		// phpcs:enable

		$query_args = array(
			'post_type'      => 'mauriel_listing',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
		);

		if ( $search ) {
			$query_args['s'] = $search;
		}

		if ( $filter_status ) {
			$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_mauriel_approval_status',
					'value'   => sanitize_text_field( $filter_status ),
					'compare' => '=',
				),
			);
		}

		if ( $filter_category ) {
			$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'mauriel_category',
					'field'    => 'term_id',
					'terms'    => $filter_category,
				),
			);
		}

		$query = new WP_Query( $query_args );

		$this->items = $query->posts;

		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page'    => $per_page,
				'total_pages' => $query->max_num_pages,
			)
		);

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}

	// -----------------------------------------------------------------------
	// Page Render
	// -----------------------------------------------------------------------

	/**
	 * Render the full admin page wrapping the list table.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		$this->prepare_items();

		// Admin notices.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = isset( $_GET['mauriel_notice'] ) ? sanitize_key( $_GET['mauriel_notice'] ) : '';
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Listings', 'mauriel-service-directory' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mauriel_listing' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'mauriel-service-directory' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( $this->get_notice_message( $notice ) ); ?></p>
				</div>
			<?php endif; ?>

			<form id="mauriel-listings-form" method="post">
				<input type="hidden" name="page" value="mauriel-directory" />
				<?php
				$this->search_box( __( 'Search Listings', 'mauriel-service-directory' ), 'mauriel-listing-search' );
				$this->display();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Map notice keys to human-readable messages.
	 *
	 * @param string $notice_key Notice key.
	 * @return string
	 */
	private function get_notice_message( $notice_key ) {
		$messages = array(
			'approved'   => __( 'Listing approved and published.', 'mauriel-service-directory' ),
			'rejected'   => __( 'Listing rejected.', 'mauriel-service-directory' ),
			'bulk_done'  => __( 'Bulk action completed.', 'mauriel-service-directory' ),
		);
		return isset( $messages[ $notice_key ] ) ? $messages[ $notice_key ] : '';
	}

	// -----------------------------------------------------------------------
	// Meta Boxes
	// -----------------------------------------------------------------------

	/**
	 * Register all meta boxes on the mauriel_listing edit screen.
	 */
	public function register_meta_boxes() {
		$screen = 'mauriel_listing';

		add_meta_box(
			'mauriel_listing_basic',
			__( 'Basic Information', 'mauriel-service-directory' ),
			array( $this, 'meta_box_basic' ),
			$screen,
			'normal',
			'high'
		);

		add_meta_box(
			'mauriel_listing_location',
			__( 'Location', 'mauriel-service-directory' ),
			array( $this, 'meta_box_location' ),
			$screen,
			'normal',
			'high'
		);

		add_meta_box(
			'mauriel_listing_hours',
			__( 'Business Hours', 'mauriel-service-directory' ),
			array( $this, 'meta_box_hours' ),
			$screen,
			'normal',
			'default'
		);

		add_meta_box(
			'mauriel_listing_media',
			__( 'Media & Gallery', 'mauriel-service-directory' ),
			array( $this, 'meta_box_media' ),
			$screen,
			'normal',
			'default'
		);

		add_meta_box(
			'mauriel_listing_package',
			__( 'Package & Subscription', 'mauriel-service-directory' ),
			array( $this, 'meta_box_package' ),
			$screen,
			'side',
			'high'
		);

		add_meta_box(
			'mauriel_listing_seo',
			__( 'SEO', 'mauriel-service-directory' ),
			array( $this, 'meta_box_seo' ),
			$screen,
			'normal',
			'low'
		);

		add_meta_box(
			'mauriel_listing_approval',
			__( 'Approval Status', 'mauriel-service-directory' ),
			array( $this, 'meta_box_approval' ),
			$screen,
			'side',
			'high'
		);
	}

	/**
	 * Render Basic Information meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function meta_box_basic( $post ) {
		wp_nonce_field( 'mauriel_save_listing_meta_' . $post->ID, 'mauriel_listing_nonce' );

		$fields = array(
			'_mauriel_tagline'     => array( 'label' => __( 'Tagline', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_phone'       => array( 'label' => __( 'Phone Number', 'mauriel-service-directory' ), 'type' => 'tel' ),
			'_mauriel_email'       => array( 'label' => __( 'Business Email', 'mauriel-service-directory' ), 'type' => 'email' ),
			'_mauriel_website'     => array( 'label' => __( 'Website URL', 'mauriel-service-directory' ), 'type' => 'url' ),
			'_mauriel_facebook'    => array( 'label' => __( 'Facebook URL', 'mauriel-service-directory' ), 'type' => 'url' ),
			'_mauriel_instagram'   => array( 'label' => __( 'Instagram URL', 'mauriel-service-directory' ), 'type' => 'url' ),
			'_mauriel_twitter'     => array( 'label' => __( 'Twitter/X URL', 'mauriel-service-directory' ), 'type' => 'url' ),
			'_mauriel_youtube'     => array( 'label' => __( 'YouTube URL', 'mauriel-service-directory' ), 'type' => 'url' ),
			'_mauriel_license'     => array( 'label' => __( 'License Number', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_insured'     => array( 'label' => __( 'Insured', 'mauriel-service-directory' ), 'type' => 'checkbox' ),
			'_mauriel_year_founded'=> array( 'label' => __( 'Year Founded', 'mauriel-service-directory' ), 'type' => 'number' ),
		);

		echo '<table class="form-table">';
		foreach ( $fields as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<tr>';
			echo '<th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
			echo '<td>';

			if ( 'checkbox' === $field['type'] ) {
				echo '<input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( 1, (int) $value, false ) . ' />';
			} elseif ( 'number' === $field['type'] ) {
				echo '<input type="number" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="small-text" min="1800" max="' . esc_attr( (int) gmdate( 'Y' ) ) . '" />';
			} else {
				echo '<input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
			}

			echo '</td></tr>';
		}

		// Description textarea
		$description = get_post_meta( $post->ID, '_mauriel_description', true );
		echo '<tr><th><label for="_mauriel_description">' . esc_html__( 'Short Description', 'mauriel-service-directory' ) . '</label></th>';
		echo '<td><textarea id="_mauriel_description" name="_mauriel_description" rows="4" class="large-text">' . esc_textarea( $description ) . '</textarea></td></tr>';

		echo '</table>';
	}

	/**
	 * Render Location meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function meta_box_location( $post ) {
		$fields = array(
			'_mauriel_address'   => array( 'label' => __( 'Street Address', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_address2'  => array( 'label' => __( 'Address Line 2', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_city'      => array( 'label' => __( 'City', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_state'     => array( 'label' => __( 'State / Province', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_zip'       => array( 'label' => __( 'ZIP / Postal Code', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_country'   => array( 'label' => __( 'Country', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_lat'       => array( 'label' => __( 'Latitude', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_lng'       => array( 'label' => __( 'Longitude', 'mauriel-service-directory' ), 'type' => 'text' ),
			'_mauriel_service_radius' => array( 'label' => __( 'Service Radius (miles)', 'mauriel-service-directory' ), 'type' => 'number' ),
			'_mauriel_hide_address' => array( 'label' => __( 'Hide Exact Address', 'mauriel-service-directory' ), 'type' => 'checkbox' ),
		);

		echo '<table class="form-table">';
		foreach ( $fields as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<tr>';
			echo '<th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
			echo '<td>';
			if ( 'checkbox' === $field['type'] ) {
				echo '<input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( 1, (int) $value, false ) . ' />';
			} elseif ( 'number' === $field['type'] ) {
				echo '<input type="number" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="small-text" min="0" />';
			} else {
				echo '<input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}

	/**
	 * Render Business Hours meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function meta_box_hours( $post ) {
		$days         = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
		$day_labels   = array(
			'monday'    => __( 'Monday', 'mauriel-service-directory' ),
			'tuesday'   => __( 'Tuesday', 'mauriel-service-directory' ),
			'wednesday' => __( 'Wednesday', 'mauriel-service-directory' ),
			'thursday'  => __( 'Thursday', 'mauriel-service-directory' ),
			'friday'    => __( 'Friday', 'mauriel-service-directory' ),
			'saturday'  => __( 'Saturday', 'mauriel-service-directory' ),
			'sunday'    => __( 'Sunday', 'mauriel-service-directory' ),
		);
		$hours        = get_post_meta( $post->ID, '_mauriel_hours', true );
		$hours        = is_array( $hours ) ? $hours : array();

		echo '<table class="form-table">';
		foreach ( $days as $day ) {
			$closed     = isset( $hours[ $day ]['closed'] ) ? (bool) $hours[ $day ]['closed'] : false;
			$open_time  = isset( $hours[ $day ]['open'] ) ? $hours[ $day ]['open'] : '09:00';
			$close_time = isset( $hours[ $day ]['close'] ) ? $hours[ $day ]['close'] : '17:00';
			echo '<tr>';
			echo '<th>' . esc_html( $day_labels[ $day ] ) . '</th>';
			echo '<td>';
			echo '<label><input type="checkbox" name="mauriel_hours[' . esc_attr( $day ) . '][closed]" value="1" ' . checked( true, $closed, false ) . ' /> ' . esc_html__( 'Closed', 'mauriel-service-directory' ) . '</label>';
			echo ' &nbsp; ';
			echo '<input type="time" name="mauriel_hours[' . esc_attr( $day ) . '][open]" value="' . esc_attr( $open_time ) . '" /> ';
			echo esc_html__( 'to', 'mauriel-service-directory' );
			echo ' <input type="time" name="mauriel_hours[' . esc_attr( $day ) . '][close]" value="' . esc_attr( $close_time ) . '" />';
			echo '</td></tr>';
		}
		echo '</table>';
	}

	/**
	 * Render Media & Gallery meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function meta_box_media( $post ) {
		$logo_id    = absint( get_post_meta( $post->ID, '_mauriel_logo_id', true ) );
		$gallery    = get_post_meta( $post->ID, '_mauriel_gallery', true );
		$gallery    = is_array( $gallery ) ? $gallery : array();
		$video_url  = get_post_meta( $post->ID, '_mauriel_video_url', true );
		?>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Business Logo', 'mauriel-service-directory' ); ?></th>
				<td>
					<input type="hidden" name="_mauriel_logo_id" id="_mauriel_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
					<?php if ( $logo_id ) : ?>
						<img src="<?php echo esc_url( wp_get_attachment_image_url( $logo_id, 'thumbnail' ) ); ?>" style="max-width:150px;display:block;margin-bottom:8px;" alt="" />
					<?php endif; ?>
					<button type="button" class="button mauriel-media-select" data-target="_mauriel_logo_id">
						<?php esc_html_e( 'Select Logo', 'mauriel-service-directory' ); ?>
					</button>
					<?php if ( $logo_id ) : ?>
						<button type="button" class="button mauriel-media-remove" data-target="_mauriel_logo_id">
							<?php esc_html_e( 'Remove', 'mauriel-service-directory' ); ?>
						</button>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Gallery Images', 'mauriel-service-directory' ); ?></th>
				<td>
					<input type="hidden" name="_mauriel_gallery" id="_mauriel_gallery" value="<?php echo esc_attr( implode( ',', array_map( 'absint', $gallery ) ) ); ?>" />
					<div id="mauriel-gallery-preview">
						<?php foreach ( $gallery as $img_id ) : ?>
							<img src="<?php echo esc_url( wp_get_attachment_image_url( absint( $img_id ), 'thumbnail' ) ); ?>" style="max-width:80px;margin:4px;" alt="" />
						<?php endforeach; ?>
					</div>
					<button type="button" class="button" id="mauriel-gallery-add">
						<?php esc_html_e( 'Add/Edit Gallery', 'mauriel-service-directory' ); ?>
					</button>
					<button type="button" class="button" id="mauriel-gallery-clear">
						<?php esc_html_e( 'Clear Gallery', 'mauriel-service-directory' ); ?>
					</button>
				</td>
			</tr>
			<tr>
				<th><label for="_mauriel_video_url"><?php esc_html_e( 'Video URL', 'mauriel-service-directory' ); ?></label></th>
				<td>
					<input type="url" id="_mauriel_video_url" name="_mauriel_video_url" value="<?php echo esc_url( $video_url ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'YouTube or Vimeo URL.', 'mauriel-service-directory' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Package & Subscription meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function meta_box_package( $post ) {
		global $wpdb;

		$packages   = $wpdb->get_results( "SELECT id, name, price_monthly FROM {$wpdb->prefix}mauriel_packages WHERE active = 1 ORDER BY sort_order ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$pkg_id     = get_post_meta( $post->ID, '_mauriel_package_id', true );
		$billing    = get_post_meta( $post->ID, '_mauriel_billing_interval', true );
		$sub_status = get_post_meta( $post->ID, '_mauriel_subscription_status', true );
		$sub_id     = get_post_meta( $post->ID, '_mauriel_stripe_subscription_id', true );
		?>
		<p>
			<label for="_mauriel_package_id"><strong><?php esc_html_e( 'Package', 'mauriel-service-directory' ); ?></strong></label><br />
			<select name="_mauriel_package_id" id="_mauriel_package_id" style="width:100%;">
				<option value=""><?php esc_html_e( '— Free / No Package —', 'mauriel-service-directory' ); ?></option>
				<?php if ( $packages ) : ?>
					<?php foreach ( $packages as $pkg ) : ?>
						<option value="<?php echo esc_attr( $pkg->id ); ?>" <?php selected( $pkg_id, $pkg->id ); ?>>
							<?php echo esc_html( $pkg->name . ' ($' . number_format( (float) $pkg->price_monthly, 2 ) . '/mo)' ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</p>
		<p>
			<label><strong><?php esc_html_e( 'Billing Interval', 'mauriel-service-directory' ); ?></strong></label><br />
			<label><input type="radio" name="_mauriel_billing_interval" value="monthly" <?php checked( 'monthly', $billing ); ?> /> <?php esc_html_e( 'Monthly', 'mauriel-service-directory' ); ?></label>
			&nbsp;
			<label><input type="radio" name="_mauriel_billing_interval" value="yearly" <?php checked( 'yearly', $billing ); ?> /> <?php esc_html_e( 'Yearly', 'mauriel-service-directory' ); ?></label>
		</p>
		<p>
			<label for="_mauriel_subscription_status"><strong><?php esc_html_e( 'Subscription Status', 'mauriel-service-directory' ); ?></strong></label><br />
			<input type="text" id="_mauriel_subscription_status" name="_mauriel_subscription_status" value="<?php echo esc_attr( $sub_status ); ?>" class="widefat" readonly />
		</p>
		<p>
			<label for="_mauriel_stripe_subscription_id"><strong><?php esc_html_e( 'Stripe Subscription ID', 'mauriel-service-directory' ); ?></strong></label><br />
			<input type="text" id="_mauriel_stripe_subscription_id" name="_mauriel_stripe_subscription_id" value="<?php echo esc_attr( $sub_id ); ?>" class="widefat" />
		</p>
		<?php
	}

	/**
	 * Render SEO meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function meta_box_seo( $post ) {
		$seo_title = get_post_meta( $post->ID, '_mauriel_seo_title', true );
		$seo_desc  = get_post_meta( $post->ID, '_mauriel_seo_description', true );
		$noindex   = get_post_meta( $post->ID, '_mauriel_noindex', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="_mauriel_seo_title"><?php esc_html_e( 'SEO Title', 'mauriel-service-directory' ); ?></label></th>
				<td>
					<input type="text" id="_mauriel_seo_title" name="_mauriel_seo_title" value="<?php echo esc_attr( $seo_title ); ?>" class="large-text" maxlength="70" />
					<p class="description"><?php esc_html_e( 'Leave blank to use the auto-generated title from the SEO pattern.', 'mauriel-service-directory' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="_mauriel_seo_description"><?php esc_html_e( 'Meta Description', 'mauriel-service-directory' ); ?></label></th>
				<td>
					<textarea id="_mauriel_seo_description" name="_mauriel_seo_description" rows="3" class="large-text" maxlength="160"><?php echo esc_textarea( $seo_desc ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Leave blank to use the auto-generated description.', 'mauriel-service-directory' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'No-Index', 'mauriel-service-directory' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="_mauriel_noindex" value="1" <?php checked( 1, (int) $noindex ); ?> />
						<?php esc_html_e( 'Exclude this listing from search engine indexing.', 'mauriel-service-directory' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render Approval Status meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function meta_box_approval( $post ) {
		$approval = get_post_meta( $post->ID, '_mauriel_approval_status', true );
		if ( ! $approval ) {
			$approval = 'pending';
		}
		?>
		<p>
			<label for="_mauriel_approval_status"><strong><?php esc_html_e( 'Approval Status', 'mauriel-service-directory' ); ?></strong></label><br />
			<select name="_mauriel_approval_status" id="_mauriel_approval_status" style="width:100%;">
				<option value="pending" <?php selected( 'pending', $approval ); ?>><?php esc_html_e( 'Pending Review', 'mauriel-service-directory' ); ?></option>
				<option value="approved" <?php selected( 'approved', $approval ); ?>><?php esc_html_e( 'Approved', 'mauriel-service-directory' ); ?></option>
				<option value="rejected" <?php selected( 'rejected', $approval ); ?>><?php esc_html_e( 'Rejected', 'mauriel-service-directory' ); ?></option>
			</select>
		</p>
		<p>
			<label for="_mauriel_featured">
				<input type="checkbox" name="_mauriel_featured" id="_mauriel_featured" value="1" <?php checked( 1, (int) get_post_meta( $post->ID, '_mauriel_featured', true ) ); ?> />
				<?php esc_html_e( 'Featured Listing', 'mauriel-service-directory' ); ?>
			</label>
		</p>
		<?php
	}

	// -----------------------------------------------------------------------
	// Save Meta
	// -----------------------------------------------------------------------

	/**
	 * Sanitize and save all meta fields when the listing post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save_listing_meta( $post_id, $post ) {

		// Bail on autosave, revisions, or if nonce is missing/invalid.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['mauriel_listing_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mauriel_listing_nonce'] ) ), 'mauriel_save_listing_meta_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// ---- Text fields ---------------------------------------------------
		$text_fields = array(
			'_mauriel_tagline', '_mauriel_phone', '_mauriel_email', '_mauriel_website',
			'_mauriel_facebook', '_mauriel_instagram', '_mauriel_twitter', '_mauriel_youtube',
			'_mauriel_license', '_mauriel_address', '_mauriel_address2', '_mauriel_city',
			'_mauriel_state', '_mauriel_zip', '_mauriel_country',
			'_mauriel_billing_interval', '_mauriel_subscription_status',
			'_mauriel_stripe_subscription_id', '_mauriel_seo_title',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}

		// ---- URL fields ----------------------------------------------------
		$url_fields = array( '_mauriel_website', '_mauriel_facebook', '_mauriel_instagram', '_mauriel_twitter', '_mauriel_youtube', '_mauriel_video_url' );
		foreach ( $url_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, esc_url_raw( wp_unslash( $_POST[ $field ] ) ) );
			}
		}

		// ---- Email fields --------------------------------------------------
		if ( isset( $_POST['_mauriel_email'] ) ) {
			update_post_meta( $post_id, '_mauriel_email', sanitize_email( wp_unslash( $_POST['_mauriel_email'] ) ) );
		}

		// ---- Textarea / description -----------------------------------------
		if ( isset( $_POST['_mauriel_description'] ) ) {
			update_post_meta( $post_id, '_mauriel_description', sanitize_textarea_field( wp_unslash( $_POST['_mauriel_description'] ) ) );
		}
		if ( isset( $_POST['_mauriel_seo_description'] ) ) {
			update_post_meta( $post_id, '_mauriel_seo_description', sanitize_textarea_field( wp_unslash( $_POST['_mauriel_seo_description'] ) ) );
		}

		// ---- Numeric fields ------------------------------------------------
		$int_fields = array( '_mauriel_package_id', '_mauriel_logo_id', '_mauriel_year_founded', '_mauriel_service_radius' );
		foreach ( $int_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, absint( $_POST[ $field ] ) );
			}
		}

		// ---- Float fields --------------------------------------------------
		$float_fields = array( '_mauriel_lat', '_mauriel_lng' );
		foreach ( $float_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, (float) $_POST[ $field ] );
			}
		}

		// ---- Checkbox fields -----------------------------------------------
		$checkbox_fields = array( '_mauriel_insured', '_mauriel_hide_address', '_mauriel_featured', '_mauriel_noindex' );
		foreach ( $checkbox_fields as $field ) {
			$val = isset( $_POST[ $field ] ) ? 1 : 0;
			update_post_meta( $post_id, $field, $val );
		}

		// ---- Approval status -----------------------------------------------
		if ( isset( $_POST['_mauriel_approval_status'] ) ) {
			$allowed  = array( 'pending', 'approved', 'rejected' );
			$approval = sanitize_key( wp_unslash( $_POST['_mauriel_approval_status'] ) );
			if ( in_array( $approval, $allowed, true ) ) {
				$old_approval = get_post_meta( $post_id, '_mauriel_approval_status', true );
				update_post_meta( $post_id, '_mauriel_approval_status', $approval );
				if ( $approval !== $old_approval ) {
					$this->send_approval_email( $post_id, $approval );
				}
			}
		}

		// ---- Business hours ------------------------------------------------
		if ( isset( $_POST['mauriel_hours'] ) && is_array( $_POST['mauriel_hours'] ) ) {
			$days       = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
			$clean_hours = array();
			foreach ( $days as $day ) {
				if ( isset( $_POST['mauriel_hours'][ $day ] ) ) {
					$day_data = $_POST['mauriel_hours'][ $day ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$clean_hours[ $day ] = array(
						'closed' => isset( $day_data['closed'] ) ? 1 : 0,
						'open'   => isset( $day_data['open'] ) ? sanitize_text_field( $day_data['open'] ) : '09:00',
						'close'  => isset( $day_data['close'] ) ? sanitize_text_field( $day_data['close'] ) : '17:00',
					);
				}
			}
			update_post_meta( $post_id, '_mauriel_hours', $clean_hours );
		}

		// ---- Gallery -------------------------------------------------------
		if ( isset( $_POST['_mauriel_gallery'] ) ) {
			$gallery_raw = sanitize_text_field( wp_unslash( $_POST['_mauriel_gallery'] ) );
			if ( $gallery_raw ) {
				$gallery_ids = array_map( 'absint', explode( ',', $gallery_raw ) );
				$gallery_ids = array_filter( $gallery_ids );
				update_post_meta( $post_id, '_mauriel_gallery', $gallery_ids );
			} else {
				delete_post_meta( $post_id, '_mauriel_gallery' );
			}
		}
	}
}
