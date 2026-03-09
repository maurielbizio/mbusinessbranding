<?php defined( 'ABSPATH' ) || exit; ?>
<div class="mauriel-registration">
	<div class="mauriel-steps">
		<div class="mauriel-step mauriel-step-active">1</div>
		<div class="mauriel-step-line"></div>
		<div class="mauriel-step">2</div>
		<div class="mauriel-step-line"></div>
		<div class="mauriel-step">3</div>
	</div>
	<h2><?php esc_html_e( 'Create Your Business Account', 'mauriel-service-directory' ); ?></h2>
	<p class="mauriel-step-subtitle"><?php esc_html_e( 'Step 1 of 3 — Account Details', 'mauriel-service-directory' ); ?></p>

	<?php if ( isset( $_GET['mauriel_error'] ) ) : ?>
	<div class="mauriel-notice mauriel-notice-error">
		<p><?php echo esc_html( urldecode( $_GET['mauriel_error'] ) ); ?></p>
	</div>
	<?php endif; ?>

	<form method="post" action="" class="mauriel-register-form">
		<?php wp_nonce_field( 'mauriel_register_nonce', 'mauriel_register_nonce' ); ?>
		<input type="hidden" name="mauriel_register_action" value="1">
		<input type="hidden" name="step" value="1">

		<div class="mauriel-form-row mauriel-form-row-2">
			<div class="mauriel-form-group">
				<label for="first-name"><?php esc_html_e( 'First Name', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
				<input type="text" id="first-name" name="first_name" class="mauriel-form-control" required value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>">
			</div>
			<div class="mauriel-form-group">
				<label for="last-name"><?php esc_html_e( 'Last Name', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
				<input type="text" id="last-name" name="last_name" class="mauriel-form-control" required>
			</div>
		</div>
		<div class="mauriel-form-group">
			<label for="email"><?php esc_html_e( 'Email Address', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
			<input type="email" id="email" name="email" class="mauriel-form-control" required>
		</div>
		<div class="mauriel-form-row mauriel-form-row-2">
			<div class="mauriel-form-group">
				<label for="password"><?php esc_html_e( 'Password', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
				<input type="password" id="password" name="password" class="mauriel-form-control" required minlength="8">
				<p class="description"><?php esc_html_e( 'At least 8 characters.', 'mauriel-service-directory' ); ?></p>
			</div>
			<div class="mauriel-form-group">
				<label for="password-confirm"><?php esc_html_e( 'Confirm Password', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
				<input type="password" id="password-confirm" name="password_confirm" class="mauriel-form-control" required minlength="8">
			</div>
		</div>
		<div class="mauriel-form-group">
			<label>
				<input type="checkbox" name="agree_terms" value="1" required>
				<?php printf( esc_html__( 'I agree to the %sTerms of Service%s', 'mauriel-service-directory' ), '<a href="' . esc_url( home_url('/terms') ) . '" target="_blank">', '</a>' ); ?>
			</label>
		</div>
		<button type="submit" class="mauriel-btn mauriel-btn-primary mauriel-btn-block mauriel-btn-lg">
			<?php esc_html_e( 'Next: Business Details →', 'mauriel-service-directory' ); ?>
		</button>
	</form>
	<p class="mauriel-login-link"><?php printf( esc_html__( 'Already have an account? %sLog in%s', 'mauriel-service-directory' ), '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">', '</a>' ); ?></p>
</div>
