<?php
/**
 * Taxonomy: mauriel_category
 *
 * @package Mauriel_Service_Directory
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Mauriel_Taxonomy_Category
 *
 * Registers the hierarchical mauriel_category taxonomy linked to
 * the mauriel_listing custom post type.
 */
class Mauriel_Taxonomy_Category {

	/**
	 * Constructor — hooks registration to WordPress 'init'.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register the taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		$labels = [
			'name'                       => _x( 'Categories', 'taxonomy general name', 'mauriel-service-directory' ),
			'singular_name'              => _x( 'Category', 'taxonomy singular name', 'mauriel-service-directory' ),
			'search_items'               => __( 'Search Categories', 'mauriel-service-directory' ),
			'popular_items'              => null, // Not applicable for hierarchical.
			'all_items'                  => __( 'All Categories', 'mauriel-service-directory' ),
			'parent_item'                => __( 'Parent Category', 'mauriel-service-directory' ),
			'parent_item_colon'          => __( 'Parent Category:', 'mauriel-service-directory' ),
			'edit_item'                  => __( 'Edit Category', 'mauriel-service-directory' ),
			'view_item'                  => __( 'View Category', 'mauriel-service-directory' ),
			'update_item'                => __( 'Update Category', 'mauriel-service-directory' ),
			'add_new_item'               => __( 'Add New Category', 'mauriel-service-directory' ),
			'new_item_name'              => __( 'New Category Name', 'mauriel-service-directory' ),
			'separate_items_with_commas' => null, // Hierarchical — not used.
			'add_or_remove_items'        => null,
			'choose_from_most_used'      => null,
			'not_found'                  => __( 'No categories found.', 'mauriel-service-directory' ),
			'no_terms'                   => __( 'No categories', 'mauriel-service-directory' ),
			'menu_name'                  => __( 'Categories', 'mauriel-service-directory' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'mauriel-service-directory' ),
			'items_list'                 => __( 'Categories list', 'mauriel-service-directory' ),
			'most_used'                  => __( 'Most Used', 'mauriel-service-directory' ),
			'back_to_items'              => __( '&larr; Go to Categories', 'mauriel-service-directory' ),
		];

		$args = [
			'hierarchical'          => true,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'show_in_nav_menus'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'mauriel-categories',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'query_var'             => true,
			'rewrite'               => [
				'slug'         => 'directory-category',
				'hierarchical' => true,
				'with_front'   => false,
			],
			'public'                => true,
			'publicly_queryable'    => true,
			'meta_box_cb'           => null, // Use default WordPress checkbox meta box for hierarchical.
		];

		register_taxonomy( 'mauriel_category', 'mauriel_listing', $args );
	}
}
