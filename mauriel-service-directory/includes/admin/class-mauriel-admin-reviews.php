<?php
/**
 * Admin Reviews Moderation
 *
 * WP_List_Table subclass for moderating reviews stored as WP comments
 * with comment_type = 'mauriel_review'.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Mauriel_Admin_Reviews
 */
class Mauriel_Admin_Reviews extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'review',
				'plural'   => 'reviews',
				'ajax'     => false,
			)
		);
	}

	// -----------------------------------------------------------------------
	// WP_List_Table API
	// -----------------------------------------------------------------------

	/**
	 * Define table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'author'  => __( 'Author', 'mauriel-service-directory' ),
			'rating'  => __( 'Rating', 'mauriel-service-directory' ),
			'review'  => __( 'Review', 'mauriel-service-directory' ),
			'listing' => __( 'Listing', 'mauriel-service-directory' ),
			'date'    => __( 'Date', 'mauriel-service-directory' ),
			'status'  => __( 'Status', 'mauriel-service-directory' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'date'   => array( 'comment_date_gmt', true ),
			'rating' => array( 'rating', false ),
			'status' => array( 'comment_approved', false ),
		);
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param WP_Comment $item Comment object.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="comment_id[]" value="%s" />', esc_attr( $item->comment_ID ) );
	}

	/**
	 * Render a column value.
	 *
	 * @param WP_Comment $item        Comment object.
	 * @param string     $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {

			case 'author':
				$author = esc_html( $item->comment_author );
				if ( $item->comment_author_email ) {
					$author .= '<br /><a href="mailto:' . esc_attr( $item->comment_author_email ) . '">' . esc_html( $item->comment_author_email ) . '</a>';
				}
				if ( $item->comment_author_url ) {
					$author .= '<br /><a href="' . esc_url( $item->comment_author_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $item->comment_author_url ) . '</a>';
				}

				$approve_url = $this->action_url( $item->comment_ID, 'approve' );
				$hold_url    = $this->action_url( $item->comment_ID, 'hold' );
				$trash_url   = $this->action_url( $item->comment_ID, 'trash' );
				$reply_url   = $this->action_url( $item->comment_ID, 'reply' );

				$actions = array(
					'approve' => '<a href="' . esc_url( $approve_url ) . '" class="mauriel-approve-link">' . __( 'Approve', 'mauriel-service-directory' ) . '</a>',
					'hold'    => '<a href="' . esc_url( $hold_url ) . '">' . __( 'Hold', 'mauriel-service-directory' ) . '</a>',
					'trash'   => '<a href="' . esc_url( $trash_url ) . '" class="submitdelete">' . __( 'Trash', 'mauriel-service-directory' ) . '</a>',
					'reply'   => '<a href="' . esc_url( $reply_url ) . '">' . __( 'Admin Reply', 'mauriel-service-directory' ) . '</a>',
				);

				return $author . $this->row_actions( $actions );

			case 'rating':
				$rating = (int) get_comment_meta( $item->comment_ID, '_mauriel_rating', true );
				return $this->render_stars( $rating );

			case 'review':
				$text = wp_trim_words( $item->comment_content, 20 );
				return '<p>' . esc_html( $text ) . '</p>';

			case 'listing':
				$post_id = $item->comment_post_ID;
				$title   = get_the_title( $post_id );
				if ( $title ) {
					return '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( $title ) . '</a>';
				}
				return esc_html( (string) $post_id );

			case 'date':
				return esc_html( wp_date( 'Y-m-d g:i a', strtotime( $item->comment_date_gmt ) ) );

			case 'status':
				if ( '1' === $item->comment_approved ) {
					return '<span class="mauriel-badge mauriel-badge--green">' . esc_html__( 'Approved', 'mauriel-service-directory' ) . '</span>';
				} elseif ( '0' === $item->comment_approved ) {
					return '<span class="mauriel-badge mauriel-badge--yellow">' . esc_html__( 'Pending', 'mauriel-service-directory' ) . '</span>';
				} else {
					return '<span class="mauriel-badge mauriel-badge--red">' . esc_html( ucfirst( $item->comment_approved ) ) . '</span>';
				}

			default:
				return '—';
		}
	}

	/**
	 * Render a star-rating display string.
	 *
	 * @param int $rating Integer 1–5.
	 * @return string HTML star display.
	 */
	private function render_stars( $rating ) {
		$rating  = min( 5, max( 0, (int) $rating ) );
		$output  = '<span class="mauriel-stars" aria-label="' . esc_attr( sprintf( _n( '%d star', '%d stars', $rating, 'mauriel-service-directory' ), $rating ) ) . '">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$color   = $i <= $rating ? '#f5a623' : '#ccc';
			$output .= '<span class="dashicons dashicons-star-filled" style="color:' . esc_attr( $color ) . ';font-size:16px;width:16px;height:16px;"></span>';
		}
		$output .= '</span>';
		return $output;
	}

	/**
	 * Build action URL with nonce for a comment.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $action     Action key.
	 * @return string
	 */
	private function action_url( $comment_id, $action ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'page'       => 'mauriel-reviews',
					'action'     => 'mauriel_review_' . $action,
					'comment_id' => $comment_id,
				),
				admin_url( 'admin.php' )
			),
			'mauriel_review_' . $action . '_' . $comment_id
		);
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		return array(
			'mauriel_review_bulk_approve' => __( 'Approve', 'mauriel-service-directory' ),
			'mauriel_review_bulk_hold'    => __( 'Hold', 'mauriel-service-directory' ),
			'mauriel_review_bulk_trash'   => __( 'Trash', 'mauriel-service-directory' ),
		);
	}

	/**
	 * Process single and bulk actions.
	 */
	public function process_actions() {

		// ---- Single row actions -------------------------------------------
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action     = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';
		$comment_id = isset( $_REQUEST['comment_id'] ) ? absint( $_REQUEST['comment_id'] ) : 0;
		// phpcs:enable

		$single_actions = array(
			'mauriel_review_approve' => '1',
			'mauriel_review_hold'    => '0',
			'mauriel_review_trash'   => 'trash',
		);

		if ( array_key_exists( $action, $single_actions ) && $comment_id ) {
			$action_key = str_replace( 'mauriel_review_', '', $action );
			check_admin_referer( 'mauriel_review_' . $action_key . '_' . $comment_id );

			if ( 'mauriel_review_approve' === $action || 'mauriel_review_hold' === $action ) {
				wp_set_comment_status( $comment_id, $single_actions[ $action ] );
			} elseif ( 'mauriel_review_trash' === $action ) {
				wp_trash_comment( $comment_id );
			}

			wp_safe_redirect( add_query_arg( array( 'page' => 'mauriel-reviews', 'notice' => $action_key . '_done' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		// ---- Admin reply action -------------------------------------------
		if ( 'mauriel_review_reply' === $action && $comment_id ) {
			check_admin_referer( 'mauriel_review_reply_' . $comment_id );
			// Show the reply form — handled in render_page().
			return;
		}

		// ---- Handle posted admin reply ------------------------------------
		if ( isset( $_POST['mauriel_submit_reply'] ) ) {
			$this->handle_admin_reply();
			return;
		}

		// ---- Bulk actions -------------------------------------------------
		$bulk_action = $this->current_action();
		if ( ! $bulk_action || ! array_key_exists( $bulk_action, $this->get_bulk_actions() ) ) {
			return;
		}

		check_admin_referer( 'bulk-reviews' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$comment_ids = isset( $_POST['comment_id'] ) ? array_map( 'absint', (array) $_POST['comment_id'] ) : array();

		if ( empty( $comment_ids ) ) {
			return;
		}

		foreach ( $comment_ids as $cid ) {
			if ( 'mauriel_review_bulk_approve' === $bulk_action ) {
				wp_set_comment_status( $cid, '1' );
			} elseif ( 'mauriel_review_bulk_hold' === $bulk_action ) {
				wp_set_comment_status( $cid, '0' );
			} elseif ( 'mauriel_review_bulk_trash' === $bulk_action ) {
				wp_trash_comment( $cid );
			}
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'mauriel-reviews', 'notice' => 'bulk_done' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handle submitted admin reply to a review.
	 */
	public function handle_admin_reply() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		check_admin_referer( 'mauriel_admin_reply' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$parent_id  = isset( $_POST['parent_comment_id'] ) ? absint( $_POST['parent_comment_id'] ) : 0;
		$reply_text = isset( $_POST['reply_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reply_text'] ) ) : '';
		// phpcs:enable

		if ( ! $parent_id || ! $reply_text ) {
			$this->redirect_with_error( 'Reply text and parent comment are required.' );
			return;
		}

		$parent_comment = get_comment( $parent_id );
		if ( ! $parent_comment ) {
			$this->redirect_with_error( 'Parent comment not found.' );
			return;
		}

		$current_user = wp_get_current_user();

		$new_comment = array(
			'comment_post_ID'      => $parent_comment->comment_post_ID,
			'comment_parent'       => $parent_id,
			'comment_content'      => $reply_text,
			'comment_author'       => $current_user->display_name,
			'comment_author_email' => $current_user->user_email,
			'comment_approved'     => 1,
			'comment_type'         => 'mauriel_review_reply',
			'user_id'              => $current_user->ID,
		);

		$new_id = wp_insert_comment( $new_comment );

		if ( $new_id ) {
			// Mark the parent as having a business reply.
			update_comment_meta( $parent_id, '_mauriel_has_reply', 1 );
			update_comment_meta( $parent_id, '_mauriel_reply_comment_id', $new_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'mauriel-reviews', 'notice' => 'reply_sent' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Redirect back with an error notice.
	 *
	 * @param string $message Error message.
	 */
	private function redirect_with_error( $message ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'mauriel-reviews',
					'error' => urlencode( $message ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Query reviews and set up pagination.
	 */
	public function prepare_items() {
		$this->process_actions();

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status_filter = isset( $_REQUEST['approval_status'] ) ? sanitize_key( $_REQUEST['approval_status'] ) : 'all';
		$listing_id    = isset( $_REQUEST['listing_id'] ) ? absint( $_REQUEST['listing_id'] ) : 0;
		// phpcs:enable

		$comment_status = 'all';
		if ( 'approved' === $status_filter ) {
			$comment_status = 'approve';
		} elseif ( 'pending' === $status_filter ) {
			$comment_status = 'hold';
		} elseif ( 'trash' === $status_filter ) {
			$comment_status = 'trash';
		}

		$query_args = array(
			'type'         => 'mauriel_review',
			'status'       => $comment_status,
			'number'       => $per_page,
			'offset'       => ( $current_page - 1 ) * $per_page,
			'no_found_rows'=> false,
		);

		if ( $listing_id ) {
			$query_args['post_id'] = $listing_id;
		}

		$comments_query = new WP_Comment_Query( $query_args );
		$this->items    = $comments_query->comments;

		// Total for pagination.
		$count_args        = $query_args;
		$count_args['count']  = true;
		$count_args['number'] = 0;
		$count_args['offset'] = 0;
		$total             = (int) ( new WP_Comment_Query( $count_args ) )->comments;

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / $per_page ),
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
	 * Render the full admin reviews page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		$this->prepare_items();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$notice     = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$error      = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
		$reply_for  = isset( $_GET['action'] ) && 'mauriel_review_reply' === sanitize_key( $_GET['action'] ) ? absint( $_GET['comment_id'] ) : 0;
		// phpcs:enable

		$notice_messages = array(
			'approve_done' => __( 'Review approved.', 'mauriel-service-directory' ),
			'hold_done'    => __( 'Review held for moderation.', 'mauriel-service-directory' ),
			'trash_done'   => __( 'Review moved to trash.', 'mauriel-service-directory' ),
			'reply_sent'   => __( 'Admin reply posted.', 'mauriel-service-directory' ),
			'bulk_done'    => __( 'Bulk action completed.', 'mauriel-service-directory' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Reviews', 'mauriel-service-directory' ); ?></h1>
			<hr class="wp-header-end">

			<?php if ( $error ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
			<?php elseif ( $notice && isset( $notice_messages[ $notice ] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice_messages[ $notice ] ); ?></p></div>
			<?php endif; ?>

			<?php if ( $reply_for ) : ?>
				<?php $this->render_reply_form( $reply_for ); ?>
			<?php else : ?>
				<form id="mauriel-reviews-form" method="post">
					<input type="hidden" name="page" value="mauriel-reviews" />
					<?php
					$this->search_box( __( 'Search Reviews', 'mauriel-service-directory' ), 'mauriel-review-search' );
					$this->display();
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the admin reply form for a specific review.
	 *
	 * @param int $comment_id Parent review comment ID.
	 */
	private function render_reply_form( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			echo '<p>' . esc_html__( 'Review not found.', 'mauriel-service-directory' ) . '</p>';
			return;
		}

		$existing_reply_id = get_comment_meta( $comment_id, '_mauriel_reply_comment_id', true );
		$existing_reply    = $existing_reply_id ? get_comment( absint( $existing_reply_id ) ) : null;
		?>
		<h2><?php esc_html_e( 'Reply to Review', 'mauriel-service-directory' ); ?></h2>

		<div class="mauriel-review-detail" style="background:#f9f9f9;border:1px solid #ddd;padding:16px;margin-bottom:20px;">
			<p>
				<strong><?php esc_html_e( 'Author:', 'mauriel-service-directory' ); ?></strong>
				<?php echo esc_html( $comment->comment_author ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Rating:', 'mauriel-service-directory' ); ?></strong>
				<?php echo $this->render_stars( (int) get_comment_meta( $comment_id, '_mauriel_rating', true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>
			<p><?php echo wp_kses_post( $comment->comment_content ); ?></p>
		</div>

		<?php if ( $existing_reply ) : ?>
			<div class="notice notice-info">
				<p>
					<strong><?php esc_html_e( 'Existing Reply:', 'mauriel-service-directory' ); ?></strong>
					<?php echo esc_html( $existing_reply->comment_content ); ?>
				</p>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'mauriel_admin_reply' ); ?>
			<input type="hidden" name="parent_comment_id" value="<?php echo esc_attr( $comment_id ); ?>" />
			<input type="hidden" name="mauriel_submit_reply" value="1" />

			<table class="form-table">
				<tr>
					<th><label for="reply-text"><?php esc_html_e( 'Your Reply', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<textarea id="reply-text" name="reply_text" rows="6" class="large-text" required></textarea>
					</td>
				</tr>
			</table>

			<?php submit_button( $existing_reply ? __( 'Update Reply', 'mauriel-service-directory' ) : __( 'Post Reply', 'mauriel-service-directory' ) ); ?>
		</form>

		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mauriel-reviews' ) ); ?>">
				&larr; <?php esc_html_e( 'Back to Reviews', 'mauriel-service-directory' ); ?>
			</a>
		</p>
		<?php
	}
}
