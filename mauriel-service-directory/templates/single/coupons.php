<?php defined( 'ABSPATH' ) || exit;
/**
 * Template: Deals & Coupons
 * Variables: $listing_id, $coupons
 */
if ( empty( $coupons ) ) return;
?>
<section class="mauriel-coupons-section" id="deals">
	<h2 class="mauriel-section-title"><?php esc_html_e( 'Deals & Offers', 'mauriel-service-directory' ); ?></h2>
	<div class="mauriel-coupons-grid">
		<?php foreach ( $coupons as $coupon ) :
			$discount_label = '';
			switch ( $coupon->discount_type ) {
				case 'percent':
					$discount_label = number_format( $coupon->discount_value, 0 ) . '% ' . __( 'OFF', 'mauriel-service-directory' );
					break;
				case 'fixed':
					$discount_label = get_option('mauriel_currency','$') . number_format( $coupon->discount_value, 2 ) . ' ' . __( 'OFF', 'mauriel-service-directory' );
					break;
				case 'free_service':
					$discount_label = __( 'FREE', 'mauriel-service-directory' );
					break;
				default:
					$discount_label = __( 'DEAL', 'mauriel-service-directory' );
			}
			$expires = $coupon->expires_at ? date_i18n( get_option('date_format'), strtotime($coupon->expires_at) ) : '';
		?>
		<div class="mauriel-coupon-card">
			<div class="mauriel-coupon-badge"><?php echo esc_html( $discount_label ); ?></div>
			<div class="mauriel-coupon-body">
				<h3 class="mauriel-coupon-title"><?php echo esc_html( $coupon->title ); ?></h3>
				<?php if ( $coupon->description ) : ?>
					<p class="mauriel-coupon-desc"><?php echo esc_html( $coupon->description ); ?></p>
				<?php endif; ?>
				<?php if ( $coupon->coupon_code ) : ?>
					<div class="mauriel-coupon-code-wrap">
						<span class="mauriel-coupon-code-label"><?php esc_html_e( 'Code:', 'mauriel-service-directory' ); ?></span>
						<button class="mauriel-reveal-code" data-code="<?php echo esc_attr( $coupon->coupon_code ); ?>">
							<?php esc_html_e( 'Click to reveal', 'mauriel-service-directory' ); ?>
						</button>
						<code class="mauriel-coupon-code" style="display:none;"><?php echo esc_html( $coupon->coupon_code ); ?></code>
					</div>
				<?php endif; ?>
				<?php if ( $expires ) : ?>
					<p class="mauriel-coupon-expiry"><?php printf( esc_html__( 'Expires: %s', 'mauriel-service-directory' ), esc_html( $expires ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
</section>
