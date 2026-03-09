<?php defined( 'ABSPATH' ) || exit;
$packages  = Mauriel_DB_Packages::get_active_packages();
$currency  = get_option( 'mauriel_currency', 'USD' );
$currency_sym = '$';
$listing_id = get_user_meta( get_current_user_id(), '_mauriel_pending_listing_id', true );
?>
<div class="mauriel-registration">
	<div class="mauriel-steps">
		<div class="mauriel-step mauriel-step-done">&#10003;</div>
		<div class="mauriel-step-line mauriel-step-line-done"></div>
		<div class="mauriel-step mauriel-step-done">&#10003;</div>
		<div class="mauriel-step-line mauriel-step-line-done"></div>
		<div class="mauriel-step mauriel-step-active">3</div>
	</div>
	<h2><?php esc_html_e( 'Choose Your Listing Plan', 'mauriel-service-directory' ); ?></h2>
	<p class="mauriel-step-subtitle"><?php esc_html_e( 'Step 3 of 3 — Select a plan to publish your listing.', 'mauriel-service-directory' ); ?></p>

	<div class="mauriel-billing-toggle">
		<button class="mauriel-billing-btn active" data-billing="monthly"><?php esc_html_e( 'Monthly', 'mauriel-service-directory' ); ?></button>
		<button class="mauriel-billing-btn" data-billing="yearly"><?php esc_html_e( 'Yearly', 'mauriel-service-directory' ); ?> <span class="mauriel-save-badge"><?php esc_html_e( 'Save up to 17%', 'mauriel-service-directory' ); ?></span></button>
	</div>

	<div class="mauriel-package-cards">
		<?php foreach ( $packages as $pkg ) :
			$is_free  = (float) $pkg->price_monthly === 0.0;
			$features = $pkg->features ? json_decode( $pkg->features, true ) : array();
		?>
		<div class="mauriel-package-card <?php echo $pkg->is_featured ? 'mauriel-package-featured' : ''; ?>">
			<?php if ( $pkg->is_featured ) : ?><div class="mauriel-popular-badge"><?php esc_html_e( 'Most Popular', 'mauriel-service-directory' ); ?></div><?php endif; ?>
			<h3 class="mauriel-package-name"><?php echo esc_html( $pkg->name ); ?></h3>
			<div class="mauriel-package-price">
				<?php if ( $is_free ) : ?>
					<strong><?php esc_html_e( 'Free', 'mauriel-service-directory' ); ?></strong>
					<span><?php esc_html_e( 'Forever', 'mauriel-service-directory' ); ?></span>
				<?php else : ?>
					<strong class="mauriel-price-monthly"><?php echo esc_html( $currency_sym . number_format( $pkg->price_monthly, 0 ) ); ?></strong>
					<strong class="mauriel-price-yearly" style="display:none;"><?php echo esc_html( $currency_sym . number_format( $pkg->price_yearly, 0 ) ); ?></strong>
					<span class="mauriel-billing-period-label">/<?php esc_html_e( 'month', 'mauriel-service-directory' ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $features ) ) : ?>
			<ul class="mauriel-package-features">
				<?php foreach ( $features as $f ) : ?><li>&#10003; <?php echo esc_html( $f ); ?></li><?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<form method="post" action="" class="mauriel-select-package-form">
				<?php wp_nonce_field( 'mauriel_register_nonce', 'mauriel_register_nonce' ); ?>
				<input type="hidden" name="mauriel_register_action" value="1">
				<input type="hidden" name="step" value="3">
				<input type="hidden" name="package_id" value="<?php echo esc_attr( $pkg->id ); ?>">
				<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">
				<input type="hidden" name="billing_interval" class="mauriel-billing-interval" value="monthly">
				<button type="submit" class="mauriel-btn mauriel-btn-primary mauriel-btn-block">
					<?php echo $is_free ? esc_html__( 'Get Started Free', 'mauriel-service-directory' ) : esc_html__( 'Subscribe & Publish', 'mauriel-service-directory' ); ?>
				</button>
			</form>
		</div>
		<?php endforeach; ?>
	</div>
</div>
