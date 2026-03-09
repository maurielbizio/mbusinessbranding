<?php
/**
 * Admin Packages Management
 *
 * Full CRUD for directory subscription packages with Stripe product sync.
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Admin_Packages
 */
class Mauriel_Admin_Packages {

	/**
	 * Constructor — handle form submissions before output.
	 */
	public function __construct() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['mauriel_package_action'] ) ? sanitize_key( $_POST['mauriel_package_action'] ) : '';

		if ( 'save' === $action ) {
			$this->handle_save();
		} elseif ( 'delete' === $action ) {
			$this->handle_delete();
		} elseif ( 'stripe_sync' === $action ) {
			$this->handle_stripe_sync();
		}
	}

	// -----------------------------------------------------------------------
	// Handlers
	// -----------------------------------------------------------------------

	/**
	 * Validate nonce, sanitize all fields, and create or update a package.
	 */
	private function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		check_admin_referer( 'mauriel_save_package' );

		// Sanitize inputs.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$pkg_id         = isset( $_POST['package_id'] ) ? absint( $_POST['package_id'] ) : 0;
		$name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$description    = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$price_monthly  = isset( $_POST['price_monthly'] ) ? (float) $_POST['price_monthly'] : 0.00;
		$price_yearly   = isset( $_POST['price_yearly'] ) ? (float) $_POST['price_yearly'] : 0.00;
		$photo_limit    = isset( $_POST['photo_limit'] ) ? absint( $_POST['photo_limit'] ) : 5;
		$is_featured    = isset( $_POST['is_featured'] ) ? 1 : 0;
		$active         = isset( $_POST['active'] ) ? 1 : 0;
		$sort_order     = isset( $_POST['sort_order'] ) ? absint( $_POST['sort_order'] ) : 0;
		$features_raw   = isset( $_POST['features'] ) ? sanitize_textarea_field( wp_unslash( $_POST['features'] ) ) : '[]';
		$stripe_prod_id = isset( $_POST['stripe_product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_product_id'] ) ) : '';
		$stripe_price_m = isset( $_POST['stripe_price_id_monthly'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_price_id_monthly'] ) ) : '';
		$stripe_price_y = isset( $_POST['stripe_price_id_yearly'] ) ? sanitize_text_field( wp_unslash( $_POST['stripe_price_id_yearly'] ) ) : '';
		// phpcs:enable

		// Validate name.
		if ( empty( $name ) ) {
			$this->redirect_with_error( 'Package name is required.' );
			return;
		}

		// Validate features JSON.
		$features_decoded = json_decode( $features_raw, true );
		if ( null === $features_decoded ) {
			$features_raw = '[]';
		}

		$data = array(
			'name'                   => $name,
			'description'            => $description,
			'price_monthly'          => $price_monthly,
			'price_yearly'           => $price_yearly,
			'photo_limit'            => $photo_limit,
			'is_featured'            => $is_featured,
			'active'                 => $active,
			'sort_order'             => $sort_order,
			'features'               => $features_raw,
			'stripe_product_id'      => $stripe_prod_id,
			'stripe_price_id_monthly'=> $stripe_price_m,
			'stripe_price_id_yearly' => $stripe_price_y,
		);

		if ( $pkg_id ) {
			Mauriel_DB_Packages::update_package( $pkg_id, $data );
			$notice = 'updated';
		} else {
			Mauriel_DB_Packages::create( $data );
			$notice = 'created';
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'mauriel-packages', 'notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Validate nonce and delete a package.
	 */
	private function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		check_admin_referer( 'mauriel_delete_package' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$pkg_id = isset( $_POST['package_id'] ) ? absint( $_POST['package_id'] ) : 0;

		if ( ! $pkg_id ) {
			$this->redirect_with_error( 'Invalid package ID.' );
			return;
		}

		// Archive the Stripe product if the class is available.
		if ( class_exists( 'Mauriel_Stripe_Products' ) ) {
			global $wpdb;
			$pkg = $wpdb->get_row( $wpdb->prepare( "SELECT stripe_product_id FROM {$wpdb->prefix}mauriel_packages WHERE id = %d", $pkg_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $pkg && $pkg->stripe_product_id ) {
				Mauriel_Stripe_Products::archive_product( $pkg->stripe_product_id );
			}
		}

		Mauriel_DB_Packages::delete_package( $pkg_id );

		wp_safe_redirect( add_query_arg( array( 'page' => 'mauriel-packages', 'notice' => 'deleted' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Sync a package to Stripe (create/update product and prices).
	 */
	private function handle_stripe_sync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		check_admin_referer( 'mauriel_stripe_sync_package' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$pkg_id = isset( $_POST['package_id'] ) ? absint( $_POST['package_id'] ) : 0;

		if ( ! $pkg_id ) {
			$this->redirect_with_error( 'Invalid package ID.' );
			return;
		}

		if ( class_exists( 'Mauriel_Stripe_Products' ) ) {
			$result = Mauriel_Stripe_Products::sync_package_to_stripe( $pkg_id );
			if ( is_wp_error( $result ) ) {
				$this->redirect_with_error( $result->get_error_message() );
				return;
			}
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'mauriel-packages', 'notice' => 'synced' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Redirect back to the packages page with an error notice.
	 *
	 * @param string $message Error message.
	 */
	private function redirect_with_error( $message ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'mauriel-packages',
					'error' => urlencode( $message ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -----------------------------------------------------------------------
	// Render
	// -----------------------------------------------------------------------

	/**
	 * Render the full packages admin page.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'mauriel-service-directory' ) );
		}

		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$edit_id    = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$show_form  = isset( $_GET['add'] ) || $edit_id;
		$notice     = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$error      = isset( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
		// phpcs:enable

		$packages   = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}mauriel_packages ORDER BY sort_order ASC, id ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$edit_pkg   = null;
		if ( $edit_id ) {
			$edit_pkg = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}mauriel_packages WHERE id = %d", $edit_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Packages', 'mauriel-service-directory' ); ?></h1>
			<?php if ( ! $show_form ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mauriel-packages', 'add' => '1' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New Package', 'mauriel-service-directory' ); ?>
				</a>
			<?php endif; ?>
			<hr class="wp-header-end">

			<?php $this->render_notices( $notice, $error ); ?>

			<?php if ( $show_form ) : ?>
				<?php $this->render_form( $edit_pkg ); ?>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=mauriel-packages' ) ); ?>">&larr; <?php esc_html_e( 'Back to Packages', 'mauriel-service-directory' ); ?></a></p>
			<?php else : ?>
				<?php $this->render_list_table( $packages ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Output admin notices.
	 *
	 * @param string $notice Notice key.
	 * @param string $error  Error message.
	 */
	private function render_notices( $notice, $error ) {
		if ( $error ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
			return;
		}

		$messages = array(
			'created' => __( 'Package created successfully.', 'mauriel-service-directory' ),
			'updated' => __( 'Package updated successfully.', 'mauriel-service-directory' ),
			'deleted' => __( 'Package deleted.', 'mauriel-service-directory' ),
			'synced'  => __( 'Package synced to Stripe successfully.', 'mauriel-service-directory' ),
		);

		if ( $notice && isset( $messages[ $notice ] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $notice ] ) . '</p></div>';
		}
	}

	/**
	 * Render the packages list table.
	 *
	 * @param array $packages Array of package row objects.
	 */
	private function render_list_table( $packages ) {
		?>
		<table class="wp-list-table widefat fixed striped mauriel-packages-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Monthly Price', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Yearly Price', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Photo Limit', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Featured', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Active', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Sort Order', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Stripe Product', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'mauriel-service-directory' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( $packages ) : ?>
					<?php foreach ( $packages as $pkg ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $pkg->name ); ?></strong></td>
							<td><?php echo esc_html( '$' . number_format( (float) $pkg->price_monthly, 2 ) ); ?></td>
							<td><?php echo esc_html( '$' . number_format( (float) $pkg->price_yearly, 2 ) ); ?></td>
							<td><?php echo esc_html( $pkg->photo_limit ); ?></td>
							<td>
								<?php if ( $pkg->is_featured ) : ?>
									<span class="dashicons dashicons-star-filled" style="color:#f5a623;" title="<?php esc_attr_e( 'Featured', 'mauriel-service-directory' ); ?>"></span>
								<?php else : ?>
									<span class="dashicons dashicons-star-empty" style="color:#ccc;"></span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( $pkg->active ) : ?>
									<span style="color:green;">&#10003;</span>
								<?php else : ?>
									<span style="color:red;">&#10007;</span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $pkg->sort_order ); ?></td>
							<td>
								<?php if ( $pkg->stripe_product_id ) : ?>
									<a href="https://dashboard.stripe.com/products/<?php echo esc_attr( $pkg->stripe_product_id ); ?>" target="_blank" rel="noopener noreferrer">
										<?php echo esc_html( $pkg->stripe_product_id ); ?>
									</a>
								<?php else : ?>
									<em><?php esc_html_e( 'Not synced', 'mauriel-service-directory' ); ?></em>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mauriel-packages', 'edit' => $pkg->id ), admin_url( 'admin.php' ) ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'mauriel-service-directory' ); ?>
								</a>
								&nbsp;
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Delete this package?', 'mauriel-service-directory' ); ?>');">
									<?php wp_nonce_field( 'mauriel_delete_package' ); ?>
									<input type="hidden" name="package_id" value="<?php echo esc_attr( $pkg->id ); ?>" />
									<input type="hidden" name="mauriel_package_action" value="delete" />
									<button type="submit" class="button button-small button-link-delete">
										<?php esc_html_e( 'Delete', 'mauriel-service-directory' ); ?>
									</button>
								</form>
								&nbsp;
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'mauriel_stripe_sync_package' ); ?>
									<input type="hidden" name="package_id" value="<?php echo esc_attr( $pkg->id ); ?>" />
									<input type="hidden" name="mauriel_package_action" value="stripe_sync" />
									<button type="submit" class="button button-small">
										<?php esc_html_e( 'Sync to Stripe', 'mauriel-service-directory' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="9"><?php esc_html_e( 'No packages found. Add your first package.', 'mauriel-service-directory' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render the add/edit package form.
	 *
	 * @param object|null $pkg Existing package row, or null when adding.
	 */
	private function render_form( $pkg ) {
		$is_edit   = (bool) $pkg;
		$page_title = $is_edit ? __( 'Edit Package', 'mauriel-service-directory' ) : __( 'Add New Package', 'mauriel-service-directory' );

		// Default values.
		$name           = $is_edit ? $pkg->name : '';
		$description    = $is_edit ? $pkg->description : '';
		$price_monthly  = $is_edit ? (float) $pkg->price_monthly : 0.00;
		$price_yearly   = $is_edit ? (float) $pkg->price_yearly : 0.00;
		$photo_limit    = $is_edit ? absint( $pkg->photo_limit ) : 5;
		$is_featured    = $is_edit ? (int) $pkg->is_featured : 0;
		$active         = $is_edit ? (int) $pkg->active : 1;
		$sort_order     = $is_edit ? absint( $pkg->sort_order ) : 0;
		$features       = $is_edit ? $pkg->features : '[]';
		$stripe_prod_id = $is_edit ? $pkg->stripe_product_id : '';
		$stripe_price_m = $is_edit ? $pkg->stripe_price_id_monthly : '';
		$stripe_price_y = $is_edit ? $pkg->stripe_price_id_yearly : '';
		?>
		<h2><?php echo esc_html( $page_title ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mauriel-packages' ) ); ?>" class="mauriel-package-form">
			<?php wp_nonce_field( 'mauriel_save_package' ); ?>
			<input type="hidden" name="mauriel_package_action" value="save" />
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="package_id" value="<?php echo esc_attr( $pkg->id ); ?>" />
			<?php endif; ?>

			<table class="form-table">
				<tr>
					<th><label for="pkg-name"><?php esc_html_e( 'Package Name', 'mauriel-service-directory' ); ?> <span class="required">*</span></label></th>
					<td>
						<input type="text" id="pkg-name" name="name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" required />
					</td>
				</tr>
				<tr>
					<th><label for="pkg-description"><?php esc_html_e( 'Description', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<textarea id="pkg-description" name="description" rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label for="pkg-price-monthly"><?php esc_html_e( 'Monthly Price ($)', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<input type="number" id="pkg-price-monthly" name="price_monthly" value="<?php echo esc_attr( number_format( $price_monthly, 2, '.', '' ) ); ?>" step="0.01" min="0" class="small-text" />
					</td>
				</tr>
				<tr>
					<th><label for="pkg-price-yearly"><?php esc_html_e( 'Yearly Price ($)', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<input type="number" id="pkg-price-yearly" name="price_yearly" value="<?php echo esc_attr( number_format( $price_yearly, 2, '.', '' ) ); ?>" step="0.01" min="0" class="small-text" />
						<p class="description"><?php esc_html_e( 'Leave 0 to disable yearly billing.', 'mauriel-service-directory' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="pkg-photo-limit"><?php esc_html_e( 'Photo Limit', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<input type="number" id="pkg-photo-limit" name="photo_limit" value="<?php echo esc_attr( $photo_limit ); ?>" min="0" max="100" class="small-text" />
						<p class="description"><?php esc_html_e( 'Max number of gallery photos allowed. 0 = unlimited.', 'mauriel-service-directory' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Featured Badge', 'mauriel-service-directory' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="is_featured" value="1" <?php checked( 1, $is_featured ); ?> />
							<?php esc_html_e( 'Listings on this package are marked as featured.', 'mauriel-service-directory' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Active', 'mauriel-service-directory' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="active" value="1" <?php checked( 1, $active ); ?> />
							<?php esc_html_e( 'Show this package on the registration/upgrade page.', 'mauriel-service-directory' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="pkg-sort-order"><?php esc_html_e( 'Sort Order', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<input type="number" id="pkg-sort-order" name="sort_order" value="<?php echo esc_attr( $sort_order ); ?>" min="0" class="small-text" />
						<p class="description"><?php esc_html_e( 'Lower numbers appear first.', 'mauriel-service-directory' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="pkg-features"><?php esc_html_e( 'Features (JSON)', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<textarea id="pkg-features" name="features" rows="6" class="large-text" placeholder='["Unlimited leads","Priority placement","Photo gallery"]'><?php echo esc_textarea( $features ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Enter a JSON array of feature strings. Example: ["Feature one","Feature two"]', 'mauriel-service-directory' ); ?></p>
					</td>
				</tr>

				<tr><th colspan="2"><hr /><h3><?php esc_html_e( 'Stripe Integration', 'mauriel-service-directory' ); ?></h3></th></tr>

				<tr>
					<th><label for="pkg-stripe-product-id"><?php esc_html_e( 'Stripe Product ID', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<input type="text" id="pkg-stripe-product-id" name="stripe_product_id" value="<?php echo esc_attr( $stripe_prod_id ); ?>" class="regular-text" placeholder="prod_xxxxxxxxxxxx" />
						<p class="description"><?php esc_html_e( 'Leave blank and use "Sync to Stripe" to auto-create.', 'mauriel-service-directory' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="pkg-stripe-price-monthly"><?php esc_html_e( 'Stripe Monthly Price ID', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<input type="text" id="pkg-stripe-price-monthly" name="stripe_price_id_monthly" value="<?php echo esc_attr( $stripe_price_m ); ?>" class="regular-text" placeholder="price_xxxxxxxxxxxx" />
					</td>
				</tr>
				<tr>
					<th><label for="pkg-stripe-price-yearly"><?php esc_html_e( 'Stripe Yearly Price ID', 'mauriel-service-directory' ); ?></label></th>
					<td>
						<input type="text" id="pkg-stripe-price-yearly" name="stripe_price_id_yearly" value="<?php echo esc_attr( $stripe_price_y ); ?>" class="regular-text" placeholder="price_xxxxxxxxxxxx" />
					</td>
				</tr>
			</table>

			<?php submit_button( $is_edit ? __( 'Update Package', 'mauriel-service-directory' ) : __( 'Create Package', 'mauriel-service-directory' ) ); ?>
		</form>
		<?php
	}
}
