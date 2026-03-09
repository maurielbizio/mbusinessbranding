<?php
/**
 * Plugin Name: Mauriel Service Directory
 * Plugin URI:  https://mbusinessbrandingai.com
 * Description: AI-powered local service directory with lead capture, subscription packages, reputation management, and business dashboards.
 * Version:     1.0.0
 * Author:      Mbusiness Branding AI
 * Author URI:  https://mbusinessbrandingai.com
 * Text Domain: mauriel-service-directory
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------
define( 'MAURIEL_VERSION',     '1.0.0' );
define( 'MAURIEL_PLUGIN_FILE', __FILE__ );
define( 'MAURIEL_PATH',        plugin_dir_path( __FILE__ ) );
define( 'MAURIEL_URL',         plugin_dir_url( __FILE__ ) );

// ---------------------------------------------------------------------------
// Activation / Deactivation hooks  (must be registered before any autoloading)
// ---------------------------------------------------------------------------
register_activation_hook(
	__FILE__,
	static function () {
		require_once MAURIEL_PATH . 'includes/class-mauriel-activator.php';
		Mauriel_Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		require_once MAURIEL_PATH . 'includes/class-mauriel-deactivator.php';
		Mauriel_Deactivator::deactivate();
	}
);

// ---------------------------------------------------------------------------
// Composer autoloader (optional – only present when `composer install` has run)
// ---------------------------------------------------------------------------
if ( file_exists( MAURIEL_PATH . 'vendor/autoload.php' ) ) {
	require_once MAURIEL_PATH . 'vendor/autoload.php';
}

// ---------------------------------------------------------------------------
// Plugin autoloader
// ---------------------------------------------------------------------------
require_once MAURIEL_PATH . 'includes/class-mauriel-autoloader.php';
new Mauriel_Autoloader();

// ---------------------------------------------------------------------------
// Boot the plugin on plugins_loaded so all WP functions are available
// ---------------------------------------------------------------------------
add_action(
	'plugins_loaded',
	static function () {
		Mauriel_Core::get_instance()->init();
	}
);
