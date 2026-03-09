<?php
defined( 'ABSPATH' ) || exit;

class Mauriel_Admin_Categories {

	public function render() {
		?>
		<div class="wrap mauriel-admin-wrap">
			<h1><?php esc_html_e( 'Service Categories', 'mauriel-service-directory' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Manage service categories and subcategories for your directory. Use the form below to add categories. Create subcategories by selecting a parent category.', 'mauriel-service-directory' ); ?></p>
			<div style="display:flex;gap:30px;margin-top:20px;">
				<div style="flex:0 0 40%;">
					<?php
					// Include the default WP taxonomy add form
					$taxonomy = get_taxonomy( 'mauriel_category' );
					if ( $taxonomy ) {
						require_once ABSPATH . 'wp-admin/includes/meta-boxes.php';
						?>
						<div class="postbox" style="padding:15px;">
							<h2 class="hndle"><?php esc_html_e( 'Add New Category', 'mauriel-service-directory' ); ?></h2>
							<form action="" method="post">
								<?php wp_nonce_field( 'add-mauriel_category', '_wpnonce_add_tag' ); ?>
								<input type="hidden" name="action" value="add-tag">
								<input type="hidden" name="taxonomy" value="mauriel_category">
								<input type="hidden" name="post_type" value="mauriel_listing">
								<div class="form-field">
									<label for="tag-name"><?php esc_html_e( 'Name', 'mauriel-service-directory' ); ?></label>
									<input name="tag-name" id="tag-name" type="text" size="40" required>
									<p><?php esc_html_e( 'The name is how it appears on your site.', 'mauriel-service-directory' ); ?></p>
								</div>
								<div class="form-field">
									<label for="tag-slug"><?php esc_html_e( 'Slug', 'mauriel-service-directory' ); ?></label>
									<input name="slug" id="tag-slug" type="text" size="40">
									<p><?php esc_html_e( 'The slug is the URL-friendly version of the name. Leave blank to auto-generate.', 'mauriel-service-directory' ); ?></p>
								</div>
								<div class="form-field">
									<label for="parent"><?php esc_html_e( 'Parent Category', 'mauriel-service-directory' ); ?></label>
									<?php
									wp_dropdown_categories( array(
										'taxonomy'         => 'mauriel_category',
										'hide_empty'       => 0,
										'name'             => 'parent',
										'orderby'          => 'name',
										'selected'         => 0,
										'hierarchical'     => true,
										'show_option_none' => __( '— None —', 'mauriel-service-directory' ),
									) );
									?>
									<p><?php esc_html_e( 'Categories, unlike tags, can have a hierarchy. Choose a parent category to make this a subcategory.', 'mauriel-service-directory' ); ?></p>
								</div>
								<div class="form-field">
									<label for="tag-description"><?php esc_html_e( 'Description', 'mauriel-service-directory' ); ?></label>
									<textarea name="description" id="tag-description" rows="5" cols="40"></textarea>
								</div>
								<?php submit_button( __( 'Add New Category', 'mauriel-service-directory' ) ); ?>
							</form>
						</div>
						<?php
					}
					?>
				</div>
				<div style="flex:1;">
					<?php
					// Redirect to the proper taxonomy management screen
					$edit_link = admin_url( 'edit-tags.php?taxonomy=mauriel_category&post_type=mauriel_listing' );
					echo '<div class="postbox" style="padding:15px;">';
					echo '<h2 class="hndle">' . esc_html__( 'Manage Categories', 'mauriel-service-directory' ) . '</h2>';
					echo '<p>' . esc_html__( 'Use the full category management page to edit, delete, and organize your categories.', 'mauriel-service-directory' ) . '</p>';
					echo '<a href="' . esc_url( $edit_link ) . '" class="button button-primary">' . esc_html__( 'Open Category Manager', 'mauriel-service-directory' ) . '</a>';
					echo '</div>';

					// Show category tree
					$terms = get_terms( array(
						'taxonomy'   => 'mauriel_category',
						'hide_empty' => false,
						'orderby'    => 'name',
					) );
					if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
						echo '<div class="postbox" style="padding:15px;margin-top:15px;">';
						echo '<h2 class="hndle">' . esc_html__( 'Category Tree', 'mauriel-service-directory' ) . '</h2>';
						echo '<ul>';
						$parents = array_filter( $terms, function( $t ) { return 0 === $t->parent; } );
						foreach ( $parents as $parent ) {
							echo '<li><strong>' . esc_html( $parent->name ) . '</strong> (' . (int) $parent->count . ')';
							$children = array_filter( $terms, function( $t ) use ( $parent ) { return (int) $t->parent === (int) $parent->term_id; } );
							if ( $children ) {
								echo '<ul style="margin-left:20px;">';
								foreach ( $children as $child ) {
									echo '<li>' . esc_html( $child->name ) . ' (' . (int) $child->count . ')</li>';
								}
								echo '</ul>';
							}
							echo '</li>';
						}
						echo '</ul></div>';
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}
}
