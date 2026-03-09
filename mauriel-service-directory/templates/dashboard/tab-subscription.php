<?php defined( 'ABSPATH' ) || exit;
$listing_id   = $listing->ID;
$all_packages = Mauriel_DB_Packages::get_active_packages();
$current_pkg  = $package;
$current_sub  = $subscription;
$currency     = get_option( 'mauriel_currency', 'USD' );
$currency_sym = '$';
$portal_url   = '';
if ( $current_sub && $current_sub->stripe_customer_id ) {
	$portal_result = Mauriel_Stripe_Checkout::create_customer_portal_session(
		$current_sub->stripe_customer_id,
		get_permalink()
	);
	if ( ! is_wp_error( $portal_result ) ) {
		$portal_url = $portal_result;
	}
}
?>
<div class="mauriel-tab-panel mauriel-tab-subscription">
	<h2><?php esc_html_e( 'Plan & Billing', 'mauriel-service-directory' ); ?></h2>

	<?php if ( $current_pkg && $current_sub ) : ?>
	<div class="mauriel-current-plan postbox">
		<h3><?php esc_html_e( 'Current Plan', 'mauriel-service-directory' ); ?></h3>
		<div class="mauriel-plan-info">
			<div class="mauriel-plan-name"><?php echo esc_html( $current_pkg->name ); ?></div>
			<div class="mauriel-plan-price">
				<?php if ( $current_sub->status === 'free' || (float) $current_pkg->price_monthly === 0.0 ) : ?>
					<strong><?php esc_html_e( 'Free', 'mauriel-service-directory' ); ?></strong>
				<?php else : ?>
					<strong><?php echo esc_html( $currency_sym . number_format( $current_sub->billing_interval === 'yearly' ? $current_pkg->price_yearly : $current_pkg->price_monthly, 2 ) ); ?></strong>
					/ <?php echo esc_html( $current_sub->billing_interval === 'yearly' ? __( 'year', 'mauriel-service-directory' ) : __( 'month', 'mauriel-service-directory' ) ); ?>
				<?php endif; ?>
			</div>
			<span class="mauriel-badge mauriel-status-<?php echo esc_attr( $current_sub->status ); ?>">
				<?php echo esc_html( ucfirst( $current_sub->status ) ); ?>
			</span>
			<?php if ( $current_sub->current_period_end ) : ?>
			<div class="mauriel-next-billing">
				<?php printf( esc_html__( 'Next billing: %s', 'mauriel-service-directory' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $current_sub->current_period_end ) ) ) ); ?>
			</div>
			<?php endif; ?>
		</div>
		<?php if ( $portal_url ) : ?>
		<a href="<?php echo esc_url( $portal_url ); ?>" class="mauriel-btn mauriel-btn-outline">
			<?php esc_html_e( 'Manage Billing & Invoices', 'mauriel-service-directory' ); ?>
		</a>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<div class="mauriel-upgrade-section">
		<h3><?php esc_html_e( 'Available Plans', 'mauriel-service-directory' ); ?></h3>
		<div class="mauriel-billing-toggle">
			<button class="mauriel-billing-btn active" data-billing="monthly"><?php esc_html_e( 'Monthly', 'mauriel-service-directory' ); ?></button>
			<button class="mauriel-billing-btn" data-billing="yearly"><?php esc_html_e( 'Yearly', 'mauriel-service-directory' ); ?> <span class="mauriel-save-badge"><?php esc_html_e( 'Save ~17%', 'mauriel-service-directory' ); ?></span></button>
		</div>
		<div class="mauriel-plan-cards">
			<?php foreach ( $all_packages as $pkg ) :
				$is_current = $current_pkg && (int) $pkg->id === (int) $current_pkg->id;
				$features   = $pkg->features ? json_decode( $pkg->features, true ) : array();
				$is_free    = (float) $pkg->price_monthly === 0.0;
			?>
			<div class="mauriel-plan-card <?php echo $is_current ? 'mauriel-plan-current' : ''; ?> <?php echo $pkg->is_featured ? 'mauriel-plan-featured' : ''; ?>">
				<?php if ( $pkg->is_featured ) : ?><div class="mauriel-plan-popular-badge"><?php esc_html_e( 'Most Popular', 'mauriel-service-directory' ); ?></div><?php endif; ?>
				<h4 class="mauriel-plan-name"><?php echo esc_html( $pkg->name ); ?></h4>
				<div class="mauriel-plan-price-wrap">
					<span class="mauriel-plan-price" data-monthly="<?php echo esc_attr( $currency_sym . number_format( $pkg->price_monthly, 2 ) ); ?>" data-yearly="<?php echo esc_attr( $currency_sym . number_format( $pkg->price_yearly, 2 ) ); ?>">
						<?php if ( $is_free ) : ?>
							<strong><?php esc_html_e( 'Free', 'mauriel-service-directory' ); ?></strong>
						<?php else : ?>
							<strong><?php echo esc_html( $currency_sym . number_format( $pkg->price_monthly, 2 ) ); ?></strong><span>/mo</span>
						<?php endif; ?>
					</span>
				</div>
				<?php if ( ! empty( $features ) ) : ?>
				<ul class="mauriel-plan-features">
					<?php foreach ( $features as $feature ) : ?>
					<li>&#10003; <?php echo esc_html( $feature ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
				<?php if ( $is_current ) : ?>
					<span class="mauriel-btn mauriel-btn-outline mauriel-btn-block" style="pointer-events:none;"><?php esc_html_e( 'Current Plan', 'mauriel-service-directory' ); ?></span>
				<?php elseif ( $is_free ) : ?>
					<span class="mauriel-btn mauriel-btn-outline mauriel-btn-block"><?php esc_html_e( 'Free Tier', 'mauriel-service-directory' ); ?></span>
				<?php else : ?>
					<button type="button" class="mauriel-btn mauriel-btn-primary mauriel-btn-block mauriel-upgrade-plan"
					        data-package-id="<?php echo esc_attr( $pkg->id ); ?>"
					        data-listing-id="<?php echo esc_attr( $listing_id ); ?>"
					        data-billing="monthly">
						<?php esc_html_e( 'Upgrade', 'mauriel-service-directory' ); ?>
					</button>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
