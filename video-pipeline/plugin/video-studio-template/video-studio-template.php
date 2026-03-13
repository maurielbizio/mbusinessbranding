<?php
/**
 * Plugin Name: Video Studio Template
 * Description: Adds the AI Video Creation Pipeline page template (Video Studio).
 * Version:     1.0.0
 * Author:      Mbusiness Branding AI
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── 1. Register "Video Studio" in the page template dropdown ──────────────────
add_filter( 'theme_page_templates', function ( $templates ) {
    $templates['video-studio'] = 'Video Studio';
    return $templates;
} );

// ── 2. Serve the plugin's template file when a page uses it ───────────────────
add_filter( 'template_include', function ( $template ) {
    if ( ! is_page() ) return $template;

    global $post;
    $chosen = get_post_meta( $post->ID, '_wp_page_template', true );

    if ( $chosen === 'video-studio' ) {
        $file = plugin_dir_path( __FILE__ ) . 'templates/page-video-studio.php';
        if ( file_exists( $file ) ) {
            return $file;
        }
    }

    return $template;
} );
