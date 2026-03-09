<?php
/**
 * Admin Payments / Subscriptions Management
 *
 * Displays all directory subscriptions and provides admin tools to
 * cancel them via the Stripe API.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Admin_Payments
 */
class Mauriel_Admin_Payments {

	/**
	 * Number of subscriptions per page.
	 *
	 * @var int
	 */
	const PER_PAGE = 25;

	/**
	 * Constructor — handle cancel actions before output.
	 */
	public function __construct() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['mauriel_payment_action'] ) ? sanitize_key( $_POST['mauriel_payment_action'] ) : '';

		if ( 'cancel' === $action ) {
			$this->handle_cancel();
		}
	}

	// -----------------------------------------------------------------------
	// Handlers
	// -----------------------------------------------------------------------

	/**
	 * Cancel a subscription via Stripe and update the local DB record.
	 */
	private function handle_cancel() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		check_admin_referer( 'mauriel_cancel_subscription' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$sub_id         = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$stripe_sub_id  = isset( $_POST['stripe_subscription_id'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_subscription_id'] ) ) : '';

		if ( ! $sub_id ) {
			$this->redirect_with_error( 'Invalid subscription ID.' );
			return;
		}

		// Cancel in Stripe if the class is available.
		if ( $stripe_sub_id && class_exists( 'Mauriel_Stripe_Subscriptions' ) ) {
			$result = Mauriel_Stripe_Subscriptions::cancel( $stripe_sub_id );
			if ( is_wp_error( $result ) ) {
				$this->redirect_with_error( $result->get_error_message() );
				return;
			}
		}

		// Update the local subscriptions table.
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'mauriel_subscriptions',
			array(
				'status'       => 'canceled',
				'canceled_at'  => current_time( 'mysql', true ),
			),
			array( 'id' => $sub_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		wp_safe_redirect( add_query_arg( array( 'page' => 'mauriel-subscriptions', 'notice' => 'canceled' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Redirect back to the subscriptions page with an error notice.
	 *
	 * @param string $message Error message.
	 */
	private function redirect_with_error( $message ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'mauriel-subscriptions',
					'error' => urlencode( $message ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -----------------------------------------------------------------------
	// Data Retrieval
	// -----------------------------------------------------------------------

	/**
	 * Fetch subscriptions from the DB with user, listing, and package data.
	 *
	 * @param string $status_filter Status filter value ('', 'active', 'canceled', 'past_due', 'free').
	 * @param int    $offset        Query offset for pagination.
	 * @return array { rows, total }
	 */
	private function get_subscriptions( $status_filter, $offset ) {
		global $wpdb;

		$table_subs = $wpdb->prefix . 'mauriel_subscriptions';
		$table_pkgs = $wpdb->prefix . 'mauriel_packages';

		$where = '';
		$where_values = array();

		if ( $status_filter && 'free' !== $status_filter ) {
			$where          = 'WHERE s.status = %s';
			$where_values[] = $status_filter;
		} elseif ( 'free' === $status_filter ) {
			$where = "WHERE (s.package_id IS NULL OR s.package_id = 0)";
		}

		// Total count.
		if ( $where_values ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_subs} s {$where}", ...$where_values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_subs} s {$where}" );
		}

		// Rows.
		$limit = self::PER_PAGE;
		$sql   = "
			SELECT
				s.id,
				s.user_id,
				s.listing_id,
				s.package_id,
				s.status,
				s.billing_interval,
				s.current_period_end,
				s.stripe_subscription_id,
				s.created_at,
				s.canceled_at,
				p.name AS package_name,
				u.user_email,
				u.display_name
			FROM {$table_subs} s
			LEFT JOIN {$table_pkgs} p ON p.id = s.package_id
			LEFT JOIN {$wpdb->users} u ON u.ID = s.user_id
			{$where}
			ORDER BY s.created_at DESC
			LIMIT %d OFFSET %d
		";

		$query_values   = array_merge( $where_values, array( $limit, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$query_values ) );

		return array(
			'rows'  => $rows ? $rows : array(),
			'total' => $total,
		);
	}

	// -----------------------------------------------------------------------
	// Render
	// -----------------------------------------------------------------------

	/**
	 * Render the full subscriptions admin page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$status_filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		$current_page  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$notice        = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$error         = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
		// phpcs:enable

		$offset = ( $current_page - 1 ) * self::PER_PAGE;
		$data   = $this->get_subscriptions( $status_filter, $offset );
		$rows   = $data['rows'];
		$total  = $data['total'];
		$pages  = (int) ceil( $total / self::PER_PAGE );

		$base_url    = admin_url( 'admin.php?page=mauriel-subscriptions' );
		$stripe_mode = get_option( 'mauriel_stripe_mode', 'test' );
		$stripe_base = 'live' === $stripe_mode ? 'https://dashboard.stripe.com' : 'https://dashboard.stripe.com/test';

		$status_tabs = array(
			''          => __( 'All', 'mauriel-service-directory' ),
			'active'    => __( 'Active', 'mauriel-service-directory' ),
			'canceled'  => __( 'Canceled', 'mauriel-service-directory' ),
			'past_due'  => __( 'Past Due', 'mauriel-service-directory' ),
			'free'      => __( 'Free (No Package)', 'mauriel-service-directory' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscriptions', 'mauriel-service-directory' ); ?></h1>
			<hr class="wp-header-end">

			<?php if ( $error ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
			<?php elseif ( 'canceled' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Subscription canceled successfully.', 'mauriel-service-directory' ); ?></p>
				</div>
			<?php endif; ?>

			<!-- Status filter tabs -->
			<ul class="subsubsub">
				<?php foreach ( $status_tabs as $key => $label ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'status', $key, $base_url ) ); ?>"
							class="<?php echo ( $status_filter === $key ) ? 'current' : ''; ?>"
						>
							<?php echo esc_html( $label ); ?>
						</a>
						<?php echo ( array_key_last( $status_tabs ) !== $key ) ? ' | ' : ''; ?>
					</li>
				<?php endforeach; ?>
			</ul>

			<p class="search-box">
				<strong><?php echo esc_html( sprintf( _n( '%d subscription found.', '%d subscriptions found.', $total, 'mauriel-service-directory' ), $total ) ); ?></strong>
			</p>

			<table class="wp-list-table widefat fixed striped mauriel-subscriptions-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'User', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Listing', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Package', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Status', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Billing', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Renews / Ends', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Stripe Subscription', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Created', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'mauriel-service-directory' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $rows ) : ?>
						<?php foreach ( $rows as $sub ) : ?>
							<?php
							$listing_title = $sub->listing_id ? get_the_title( absint( $sub->listing_id ) ) : '—';
							$listing_url   = $sub->listing_id ? get_edit_post_link( absint( $sub->listing_id ) ) : '';
							$user_edit_url = $sub->user_id ? get_edit_user_link( absint( $sub->user_id ) ) : '';
							$period_end    = $sub->current_period_end ? wp_date( 'Y-m-d', strtotime( $sub->current_period_end ) ) : '—';
							$status_class  = 'active' === $sub->status ? 'mauriel-badge--green' : ( 'past_due' === $sub->status ? 'mauriel-badge--yellow' : 'mauriel-badge--red' );
							?>
							<tr>
								<td>
									<?php if ( $user_edit_url ) : ?>
										<a href="<?php echo esc_url( $user_edit_url ); ?>">
											<?php echo esc_html( $sub->user_email ); ?>
										</a>
									<?php else : ?>
										<?php echo esc_html( $sub->user_email ? $sub->user_email : '—' ); ?>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $listing_url ) : ?>
										<a href="<?php echo esc_url( $listing_url ); ?>"><?php echo esc_html( $listing_title ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $listing_title ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $sub->package_name ? $sub->package_name : __( 'Free', 'mauriel-service-directory' ) ); ?></td>
								<td>
									<span class="mauriel-badge <?php echo esc_attr( $status_class ); ?>">
										<?php echo esc_html( ucfirst( str_replace( '_', ' ', $sub->status ) ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( ucfirst( $sub->billing_interval ? $sub->billing_interval : '—' ) ); ?></td>
								<td><?php echo esc_html( $period_end ); ?></td>
								<td>
									<?php if ( $sub->stripe_subscription_id ) : ?>
										<a href="<?php echo esc_url( $stripe_base . '/subscriptions/' . $sub->stripe_subscription_id ); ?>" target="_blank" rel="noopener noreferrer">
											<?php echo esc_html( $sub->stripe_subscription_id ); ?>
										</a>
									<?php else : ?>
										<em><?php esc_html_e( 'N/A', 'mauriel-service-directory' ); ?></em>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $sub->created_at ? wp_date( 'Y-m-d', strtotime( $sub->created_at ) ) : '—' ); ?></td>
								<td>
									<?php if ( 'active' === $sub->status || 'past_due' === $sub->status ) : ?>
										<form method="post" onsubmit="return confirm('<?php esc_attr_e( 'Cancel this subscription? This cannot be undone.', 'mauriel-service-directory' ); ?>');">
											<?php wp_nonce_field( 'mauriel_cancel_subscription' ); ?>
											<input type="hidden" name="mauriel_payment_action" value="cancel" />
											<input type="hidden" name="subscription_id" value="<?php echo esc_attr( $sub->id ); ?>" />
											<input type="hidden" name="stripe_subscription_id" value="<?php echo esc_attr( $sub->stripe_subscription_id ? $sub->stripe_subscription_id : '' ); ?>" />
											<button type="submit" class="button button-small button-link-delete">
												<?php esc_html_e( 'Cancel', 'mauriel-service-directory' ); ?>
											</button>
										</form>
									<?php else : ?>
										<em><?php esc_html_e( 'No actions', 'mauriel-service-directory' ); ?></em>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="9"><?php esc_html_e( 'No subscriptions found.', 'mauriel-service-directory' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th><?php esc_html_e( 'User', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Listing', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Package', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Status', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Billing', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Renews / Ends', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Stripe Subscription', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Created', 'mauriel-service-directory' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'mauriel-service-directory' ); ?></th>
					</tr>
				</tfoot>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						$pagination_args = array(
							'base'    => add_query_arg( 'paged', '%#%', $base_url ),
							'format'  => '',
							'current' => $current_page,
							'total'   => $pages,
						);
						if ( $status_filter ) {
							$pagination_args['base'] = add_query_arg( array( 'status' => $status_filter, 'paged' => '%#%' ), $base_url );
						}
						echo paginate_links( $pagination_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<?php
	}
}
