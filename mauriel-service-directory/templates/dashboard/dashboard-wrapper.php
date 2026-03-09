<?php defined( 'ABSPATH' ) || exit;
/**
 * Dashboard Wrapper Template
 * Variables: $listing, $subscription, $package, $active_tab, $allowed_tabs, $all_tabs
 */
$user         = wp_get_current_user();
$package_name = $package ? esc_html( $package->name ) : __( 'Free', 'mauriel-service-directory' );
$notice       = get_transient( 'mauriel_dashboard_notice_' . $user->ID );
if ( $notice ) delete_transient( 'mauriel_dashboard_notice_' . $user->ID );
?>
<div class="mauriel-dashboard" id="mauriel-dashboard">

	<div class="mauriel-dashboard-header">
		<div class="mauriel-dashboard-title">
			<h2><?php printf( esc_html__( 'Welcome, %s', 'mauriel-service-directory' ), esc_html( $user->display_name ) ); ?></h2>
			<p><?php echo esc_html( get_the_title( $listing->ID ) ); ?>
				<span class="mauriel-badge mauriel-badge-package"><?php echo esc_html( $package_name ); ?></span>
			</p>
		</div>
		<a href="<?php echo esc_url( get_permalink( $listing->ID ) ); ?>" class="mauriel-btn mauriel-btn-outline" target="_blank">
			<?php esc_html_e( 'View Listing', 'mauriel-service-directory' ); ?> &#8599;
		</a>
	</div>

	<?php if ( $notice ) : ?>
	<div class="mauriel-notice mauriel-notice-<?php echo esc_attr( $notice['type'] ); ?>">
		<p><?php echo esc_html( $notice['message'] ); ?></p>
	</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['mauriel_payment'] ) && 'success' === $_GET['mauriel_payment'] ) : ?>
	<div class="mauriel-notice mauriel-notice-success">
		<p><?php esc_html_e( '🎉 Payment successful! Your listing is now active.', 'mauriel-service-directory' ); ?></p>
	</div>
	<?php endif; ?>

	<div class="mauriel-dashboard-layout">
		<nav class="mauriel-dashboard-nav" id="mauriel-dashboard-nav" aria-label="<?php esc_attr_e( 'Dashboard Navigation', 'mauriel-service-directory' ); ?>">
			<?php foreach ( $all_tabs as $slug => $label ) :
				$is_allowed = isset( $allowed_tabs[ $slug ] );
				$is_active  = $slug === $active_tab;
				$tab_url    = add_query_arg( 'tab', $slug, get_permalink() );
			?>
			<?php if ( $is_allowed ) : ?>
				<a href="<?php echo esc_url( $tab_url ); ?>"
				   class="mauriel-dashboard-tab-btn <?php echo $is_active ? 'active' : ''; ?>"
				   data-tab="<?php echo esc_attr( $slug ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php else : ?>
				<span class="mauriel-dashboard-tab-btn mauriel-tab-locked" title="<?php esc_attr_e( 'Upgrade to unlock', 'mauriel-service-directory' ); ?>">
					<?php echo esc_html( $label ); ?> &#128274;
				</span>
			<?php endif; ?>
			<?php endforeach; ?>
		</nav>

		<div class="mauriel-dashboard-content" id="mauriel-dashboard-content">
			<?php
			$tab_template = Mauriel_Core::get_instance()->locate_template( 'dashboard/tab-' . $active_tab . '.php' );
			if ( $tab_template && file_exists( $tab_template ) ) {
				include $tab_template;
			} else {
				echo '<p>' . esc_html__( 'Tab not found.', 'mauriel-service-directory' ) . '</p>';
			}
			?>
		</div>
	</div>
</div>
