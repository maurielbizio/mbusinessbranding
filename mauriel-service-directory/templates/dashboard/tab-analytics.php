<?php defined( 'ABSPATH' ) || exit;
$listing_id = $listing->ID;
$days       = isset( $_GET['analytics_days'] ) ? absint( $_GET['analytics_days'] ) : 30;
if ( ! in_array( $days, array( 7, 30, 90 ), true ) ) $days = 30;
$summary = Mauriel_DB_Analytics::get_summary( $listing_id, $days );
$trend   = Mauriel_DB_Analytics::get_trend( $listing_id, 'view', $days );
$leads   = Mauriel_DB_Analytics::get_trend( $listing_id, 'lead_submit', $days );
$dates   = array_keys( $trend );
$view_counts = array_values( $trend );
$lead_counts = array();
foreach ( $dates as $d ) { $lead_counts[] = isset( $leads[$d] ) ? (int) $leads[$d] : 0; }
?>
<div class="mauriel-tab-panel mauriel-tab-analytics">
	<h2><?php esc_html_e( 'Analytics', 'mauriel-service-directory' ); ?></h2>

	<div class="mauriel-days-filter">
		<?php foreach ( array( 7 => '7 Days', 30 => '30 Days', 90 => '90 Days' ) as $d => $label ) : ?>
		<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'analytics', 'analytics_days' => $d ), get_permalink() ) ); ?>"
		   class="mauriel-btn mauriel-btn-sm <?php echo $d === $days ? 'mauriel-btn-primary' : 'mauriel-btn-outline'; ?>">
			<?php echo esc_html( $label ); ?>
		</a>
		<?php endforeach; ?>
	</div>

	<div class="mauriel-analytics-cards">
		<?php
		$stats = array(
			'view'          => array( 'label' => __( 'Profile Views', 'mauriel-service-directory' ), 'icon' => '👁️' ),
			'impression'    => array( 'label' => __( 'Search Impressions', 'mauriel-service-directory' ), 'icon' => '🔍' ),
			'lead_submit'   => array( 'label' => __( 'Leads', 'mauriel-service-directory' ), 'icon' => '📩' ),
			'phone_click'   => array( 'label' => __( 'Phone Clicks', 'mauriel-service-directory' ), 'icon' => '📞' ),
			'website_click' => array( 'label' => __( 'Website Clicks', 'mauriel-service-directory' ), 'icon' => '🌐' ),
			'direction_click' => array( 'label' => __( 'Direction Clicks', 'mauriel-service-directory' ), 'icon' => '📍' ),
		);
		foreach ( $stats as $event => $info ) :
			$count = isset( $summary[$event] ) ? (int) $summary[$event] : 0;
		?>
		<div class="mauriel-analytics-card">
			<div class="mauriel-analytics-icon"><?php echo $info['icon']; ?></div>
			<div class="mauriel-analytics-number"><?php echo number_format( $count ); ?></div>
			<div class="mauriel-analytics-label"><?php echo esc_html( $info['label'] ); ?></div>
		</div>
		<?php endforeach; ?>
	</div>

	<div class="mauriel-chart-container">
		<h3><?php printf( esc_html__( 'Daily Views — Last %d Days', 'mauriel-service-directory' ), $days ); ?></h3>
		<canvas id="mauriel-analytics-chart" height="80"></canvas>
	</div>

	<script>
	window.maurielAnalyticsData = {
		labels: <?php echo wp_json_encode( $dates ); ?>,
		views:  <?php echo wp_json_encode( $view_counts ); ?>,
		leads:  <?php echo wp_json_encode( $lead_counts ); ?>
	};
	</script>
</div>
