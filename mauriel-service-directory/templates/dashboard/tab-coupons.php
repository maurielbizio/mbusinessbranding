<?php defined( 'ABSPATH' ) || exit;
$listing_id = $listing->ID;
$coupons    = Mauriel_DB_Coupons::get_for_listing( $listing_id );
?>
<div class="mauriel-tab-panel mauriel-tab-coupons">
	<h2><?php esc_html_e( 'Deals & Coupons', 'mauriel-service-directory' ); ?></h2>

	<div class="mauriel-coupon-add-form postbox">
		<h3><?php esc_html_e( 'Add a New Deal', 'mauriel-service-directory' ); ?></h3>
		<form class="mauriel-coupon-form" data-listing-id="<?php echo esc_attr( $listing_id ); ?>" id="mauriel-add-coupon-form">
			<div class="mauriel-form-row mauriel-form-row-2">
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Deal Title', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
					<input type="text" name="title" class="mauriel-form-control" required placeholder="<?php esc_attr_e( 'e.g. 20% Off This Month', 'mauriel-service-directory' ); ?>">
				</div>
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Coupon Code', 'mauriel-service-directory' ); ?></label>
					<input type="text" name="coupon_code" class="mauriel-form-control" placeholder="<?php esc_attr_e( 'SAVE20', 'mauriel-service-directory' ); ?>">
				</div>
			</div>
			<div class="mauriel-form-row mauriel-form-row-2">
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Discount Type', 'mauriel-service-directory' ); ?></label>
					<select name="discount_type" class="mauriel-form-control">
						<option value="percent"><?php esc_html_e( 'Percentage Off', 'mauriel-service-directory' ); ?></option>
						<option value="fixed"><?php esc_html_e( 'Fixed Amount Off', 'mauriel-service-directory' ); ?></option>
						<option value="free_service"><?php esc_html_e( 'Free Service', 'mauriel-service-directory' ); ?></option>
						<option value="other"><?php esc_html_e( 'Other', 'mauriel-service-directory' ); ?></option>
					</select>
				</div>
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Discount Value', 'mauriel-service-directory' ); ?></label>
					<input type="number" name="discount_value" class="mauriel-form-control" step="0.01" min="0" placeholder="<?php esc_attr_e( 'e.g. 20', 'mauriel-service-directory' ); ?>">
				</div>
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Description', 'mauriel-service-directory' ); ?></label>
				<textarea name="description" class="mauriel-form-control" rows="3" placeholder="<?php esc_attr_e( 'Tell customers about this deal...', 'mauriel-service-directory' ); ?>"></textarea>
			</div>
			<div class="mauriel-form-row mauriel-form-row-3">
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Start Date', 'mauriel-service-directory' ); ?></label>
					<input type="date" name="starts_at" class="mauriel-form-control">
				</div>
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Expiry Date', 'mauriel-service-directory' ); ?></label>
					<input type="date" name="expires_at" class="mauriel-form-control">
				</div>
				<div class="mauriel-form-group">
					<label><?php esc_html_e( 'Max Uses', 'mauriel-service-directory' ); ?></label>
					<input type="number" name="max_uses" class="mauriel-form-control" min="1" placeholder="<?php esc_attr_e( 'Unlimited', 'mauriel-service-directory' ); ?>">
				</div>
			</div>
			<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">
			<div class="mauriel-notice mauriel-coupon-success" style="display:none;"></div>
			<button type="submit" class="mauriel-btn mauriel-btn-primary"><?php esc_html_e( 'Add Deal', 'mauriel-service-directory' ); ?></button>
		</form>
	</div>

	<?php if ( ! empty( $coupons ) ) : ?>
	<h3><?php esc_html_e( 'Your Current Deals', 'mauriel-service-directory' ); ?></h3>
	<div class="mauriel-coupons-manage-list">
		<?php foreach ( $coupons as $coupon ) :
			$is_expired = Mauriel_DB_Coupons::is_expired( $coupon );
			$status_class = $is_expired ? 'mauriel-coupon-expired' : ( $coupon->is_active ? 'mauriel-coupon-active' : 'mauriel-coupon-inactive' );
		?>
		<div class="mauriel-coupon-manage-card <?php echo esc_attr( $status_class ); ?>" id="coupon-<?php echo esc_attr( $coupon->id ); ?>">
			<div class="mauriel-coupon-manage-header">
				<strong><?php echo esc_html( $coupon->title ); ?></strong>
				<div class="mauriel-coupon-meta">
					<?php if ( $coupon->coupon_code ) : ?><code><?php echo esc_html( $coupon->coupon_code ); ?></code><?php endif; ?>
					<span class="mauriel-badge <?php echo $is_expired ? 'mauriel-badge-danger' : 'mauriel-badge-success'; ?>">
						<?php echo $is_expired ? esc_html__( 'Expired', 'mauriel-service-directory' ) : ( $coupon->is_active ? esc_html__( 'Active', 'mauriel-service-directory' ) : esc_html__( 'Inactive', 'mauriel-service-directory' ) ); ?>
					</span>
					<span><?php printf( esc_html__( '%d uses', 'mauriel-service-directory' ), (int) $coupon->use_count ); ?></span>
				</div>
			</div>
			<div class="mauriel-coupon-manage-actions">
				<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-danger mauriel-delete-coupon"
				        data-coupon-id="<?php echo esc_attr( $coupon->id ); ?>">
					<?php esc_html_e( 'Delete', 'mauriel-service-directory' ); ?>
				</button>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
