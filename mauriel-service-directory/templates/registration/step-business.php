<?php defined( 'ABSPATH' ) || exit; ?>
<div class="mauriel-registration">
	<div class="mauriel-steps">
		<div class="mauriel-step mauriel-step-done">&#10003;</div>
		<div class="mauriel-step-line mauriel-step-line-done"></div>
		<div class="mauriel-step mauriel-step-active">2</div>
		<div class="mauriel-step-line"></div>
		<div class="mauriel-step">3</div>
	</div>
	<h2><?php esc_html_e( 'Tell Us About Your Business', 'mauriel-service-directory' ); ?></h2>
	<p class="mauriel-step-subtitle"><?php esc_html_e( 'Step 2 of 3 — Business Details', 'mauriel-service-directory' ); ?></p>

	<form method="post" action="" class="mauriel-register-form">
		<?php wp_nonce_field( 'mauriel_register_nonce', 'mauriel_register_nonce' ); ?>
		<input type="hidden" name="mauriel_register_action" value="1">
		<input type="hidden" name="step" value="2">

		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Business Name', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
			<input type="text" name="business_name" class="mauriel-form-control" required>
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Tagline', 'mauriel-service-directory' ); ?></label>
			<input type="text" name="tagline" class="mauriel-form-control" placeholder="<?php esc_attr_e( 'A short description of your business', 'mauriel-service-directory' ); ?>">
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Category', 'mauriel-service-directory' ); ?></label>
			<?php wp_dropdown_categories( array( 'taxonomy' => 'mauriel_category', 'name' => 'category_id', 'show_option_none' => __( '— Select a Category —', 'mauriel-service-directory' ), 'hide_empty' => false, 'class' => 'mauriel-form-control' ) ); ?>
		</div>
		<div class="mauriel-form-row mauriel-form-row-2">
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Phone', 'mauriel-service-directory' ); ?></label>
				<input type="tel" name="phone" class="mauriel-form-control">
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Email', 'mauriel-service-directory' ); ?></label>
				<input type="email" name="business_email" class="mauriel-form-control">
			</div>
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Website', 'mauriel-service-directory' ); ?></label>
			<input type="url" name="website" class="mauriel-form-control">
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Business Description', 'mauriel-service-directory' ); ?></label>
			<textarea name="description" class="mauriel-form-control" rows="5" placeholder="<?php esc_attr_e( 'Describe your business, services, and what makes you unique...', 'mauriel-service-directory' ); ?>"></textarea>
		</div>
		<div class="mauriel-form-group">
			<label><?php esc_html_e( 'Street Address', 'mauriel-service-directory' ); ?></label>
			<input type="text" name="address" class="mauriel-form-control">
		</div>
		<div class="mauriel-form-row mauriel-form-row-3">
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'City', 'mauriel-service-directory' ); ?></label>
				<input type="text" name="city" class="mauriel-form-control">
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'State', 'mauriel-service-directory' ); ?></label>
				<input type="text" name="state" class="mauriel-form-control" maxlength="2" placeholder="TX">
			</div>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'ZIP Code', 'mauriel-service-directory' ); ?></label>
				<input type="text" name="zip" class="mauriel-form-control" maxlength="10">
			</div>
		</div>
		<button type="submit" class="mauriel-btn mauriel-btn-primary mauriel-btn-block mauriel-btn-lg">
			<?php esc_html_e( 'Next: Choose Your Plan →', 'mauriel-service-directory' ); ?>
		</button>
	</form>
</div>
