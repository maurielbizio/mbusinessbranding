<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_Admin_Analytics {

	public function render() {
		global $wpdb;

		$days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
		if ( ! in_array( $days, array( 7, 30, 90 ), true ) ) {
			$days = 30;
		}

		$analytics_table = Mauriel_DB::table( 'analytics' );
		$leads_table     = Mauriel_DB::table( 'leads' );

		// Total listings
		$total_listings = wp_count_posts( 'mauriel_listing' )->publish;

		// Total views (30 days)
		$total_views = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$analytics_table} WHERE event_type = 'view' AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		) );

		// Total leads (30 days)
		$total_leads = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$leads_table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		) );

		// Total reviews
		$total_reviews = get_comments( array(
			'comment_type' => 'mauriel_review',
			'count'        => true,
			'status'       => 'approve',
		) );

		// Top 10 listings by views
		$top_listings = $wpdb->get_results( $wpdb->prepare(
			"SELECT listing_id, COUNT(*) as view_count
			FROM {$analytics_table}
			WHERE event_type = 'view' AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			GROUP BY listing_id
			ORDER BY view_count DESC
			LIMIT 10",
			$days
		) );

		// Daily views trend
		$daily_views = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(recorded_at) as day, COUNT(*) as count
			FROM {$analytics_table}
			WHERE event_type = 'view' AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			GROUP BY DATE(recorded_at)
			ORDER BY day ASC",
			$days
		) );

		// Recent leads
		$recent_leads = $wpdb->get_results( $wpdb->prepare(
			"SELECT l.*, p.post_title as listing_name
			FROM {$leads_table} l
			LEFT JOIN {$wpdb->posts} p ON p.ID = l.listing_id
			ORDER BY l.created_at DESC
			LIMIT 20"
		) );

		$chart_labels = array();
		$chart_data   = array();
		foreach ( $daily_views as $row ) {
			$chart_labels[] = $row->day;
			$chart_data[]   = (int) $row->count;
		}
		?>
		<div class="wrap mauriel-admin-wrap">
			<h1><?php esc_html_e( 'Directory Analytics', 'mauriel-service-directory' ); ?></h1>

			<div class="mauriel-days-filter">
				<a href="<?php echo esc_url( add_query_arg( 'days', 7 ) ); ?>" class="button <?php echo 7 === $days ? 'button-primary' : ''; ?>">7 Days</a>
				<a href="<?php echo esc_url( add_query_arg( 'days', 30 ) ); ?>" class="button <?php echo 30 === $days ? 'button-primary' : ''; ?>">30 Days</a>
				<a href="<?php echo esc_url( add_query_arg( 'days', 90 ) ); ?>" class="button <?php echo 90 === $days ? 'button-primary' : ''; ?>">90 Days</a>
			</div>

			<div class="mauriel-stats-cards" style="display:flex;gap:20px;margin:20px 0;">
				<div class="mauriel-stat-card postbox" style="flex:1;padding:20px;text-align:center;">
					<div class="mauriel-stat-number" style="font-size:2em;font-weight:bold;"><?php echo number_format( $total_listings ); ?></div>
					<div><?php esc_html_e( 'Total Listings', 'mauriel-service-directory' ); ?></div>
				</div>
				<div class="mauriel-stat-card postbox" style="flex:1;padding:20px;text-align:center;">
					<div class="mauriel-stat-number" style="font-size:2em;font-weight:bold;"><?php echo number_format( $total_views ); ?></div>
					<div><?php echo esc_html( sprintf( __( 'Views (%d days)', 'mauriel-service-directory' ), $days ) ); ?></div>
				</div>
				<div class="mauriel-stat-card postbox" style="flex:1;padding:20px;text-align:center;">
					<div class="mauriel-stat-number" style="font-size:2em;font-weight:bold;"><?php echo number_format( $total_leads ); ?></div>
					<div><?php echo esc_html( sprintf( __( 'Leads (%d days)', 'mauriel-service-directory' ), $days ) ); ?></div>
				</div>
				<div class="mauriel-stat-card postbox" style="flex:1;padding:20px;text-align:center;">
					<div class="mauriel-stat-number" style="font-size:2em;font-weight:bold;"><?php echo number_format( $total_reviews ); ?></div>
					<div><?php esc_html_e( 'Total Reviews', 'mauriel-service-directory' ); ?></div>
				</div>
			</div>

			<div class="postbox" style="padding:20px;margin-bottom:20px;">
				<h2><?php esc_html_e( 'Daily Views', 'mauriel-service-directory' ); ?></h2>
				<canvas id="mauriel-admin-chart" height="100"></canvas>
				<script>
				window.maurielAdminChartData = {
					labels: <?php echo wp_json_encode( $chart_labels ); ?>,
					data: <?php echo wp_json_encode( $chart_data ); ?>
				};
				</script>
			</div>

			<div class="postbox" style="padding:20px;margin-bottom:20px;">
				<h2><?php esc_html_e( 'Top Listings by Views', 'mauriel-service-directory' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Listing', 'mauriel-service-directory' ); ?></th>
							<th><?php esc_html_e( 'Views', 'mauriel-service-directory' ); ?></th>
							<th><?php esc_html_e( 'Rating', 'mauriel-service-directory' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $top_listings as $row ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $row->listing_id ) ); ?>"><?php echo esc_html( get_the_title( $row->listing_id ) ); ?></a></td>
							<td><?php echo number_format( $row->view_count ); ?></td>
							<td><?php echo esc_html( get_post_meta( $row->listing_id, '_mauriel_avg_rating', true ) ?: 'N/A' ); ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if ( empty( $top_listings ) ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'No data yet.', 'mauriel-service-directory' ); ?></td></tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>

			<div class="postbox" style="padding:20px;">
				<h2><?php esc_html_e( 'Recent Leads', 'mauriel-service-directory' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Listing', 'mauriel-service-directory' ); ?></th>
							<th><?php esc_html_e( 'Type', 'mauriel-service-directory' ); ?></th>
							<th><?php esc_html_e( 'Name', 'mauriel-service-directory' ); ?></th>
							<th><?php esc_html_e( 'Email', 'mauriel-service-directory' ); ?></th>
							<th><?php esc_html_e( 'Date', 'mauriel-service-directory' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $recent_leads as $lead ) : ?>
						<tr>
							<td><?php echo esc_html( $lead->listing_name ); ?></td>
							<td><?php echo esc_html( $lead->lead_type ); ?></td>
							<td><?php echo esc_html( $lead->name ); ?></td>
							<td><?php echo esc_html( $lead->email ); ?></td>
							<td><?php echo esc_html( human_time_diff( strtotime( $lead->created_at ) ) . ' ago' ); ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if ( empty( $recent_leads ) ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'No leads yet.', 'mauriel-service-directory' ); ?></td></tr>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
