<?php
/**
 * Plugin Name: AI Feed Digest
 * Plugin URI: https://github.com/jeremyfelt/feed-digester
 * Description: Aggregate RSS feeds and receive AI-generated newsletter summaries via email.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Jeremy Felt
 * Author URI: https://jeremyfelt.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-feed-digest
 * Domain Path: /languages
 *
 * @package AIFeedDigest
 */

namespace AIFeedDigest;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'AFD_VERSION', '1.0.0' );
define( 'AFD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AFD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoloader.
if ( file_exists( AFD_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once AFD_PLUGIN_DIR . 'vendor/autoload.php';
}

// Initialize the plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

/**
 * Initialize the plugin.
 *
 * @return void
 */
function init(): void {
	Core\Plugin::get_instance();
}

// Activation hook.
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate' );

/**
 * Plugin activation callback.
 *
 * @return void
 */
function activate(): void {
	// Register post types so rewrite rules are generated.
	Core\PostTypes::register();
	Core\Cron::schedule_events();

	// Set default options if not already set.
	if ( ! get_option( 'afd_prompt_template' ) ) {
		update_option( 'afd_prompt_template', Core\Plugin::get_default_prompt_template() );
	}

	flush_rewrite_rules();
}

// Deactivation hook.
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate' );

/**
 * Plugin deactivation callback.
 *
 * @return void
 */
function deactivate(): void {
	Core\Cron::unschedule_events();
	flush_rewrite_rules();
}
