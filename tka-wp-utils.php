<?php
/**
 * Plugin Name:       TKA WP Utils
 * Plugin URI:        https://github.com/thekitchen-agency/tka-wp-utils
 * Description:       A collection of utility tools to customize and secure your WordPress experience, including Classic Editor, Classic Widgets, Disable Gutenberg (granular), and Safe SVG upload validation.
 * Version:           1.1.0
 * Author:            TKA
 * Author URI:        https://wp-play.ddev.site
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       tka-wp-utils
 * Domain Path:       /languages
 * Requires PHP:      8.3
 * Requires at least: 6.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Define plugin constants
define('TKA_WP_UTILS_VERSION', '1.1.0');
define('TKA_WP_UTILS_PATH', plugin_dir_path(__FILE__));
define('TKA_WP_UTILS_URL', plugin_dir_url(__FILE__));

/**
 * Register PSR-4 Autoloader for the plugin.
 */
spl_autoload_register(function ($class) {
	$prefix = 'TKA\\WPUtils\\';
	$base_dir = TKA_WP_UTILS_PATH . 'includes/';

	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		return;
	}

	$relative_class = substr($class, $len);
	$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

	if (file_exists($file)) {
		require_once $file;
	}
});

/**
 * Begin plugin execution.
 */
function tka_wp_utils_run()
{
	$plugin = TKA\WPUtils\Core\Plugin::getInstance();
	$plugin->run();
}

/**
 * Register activation hook.
 */
register_activation_hook(__FILE__, function () {
	$current_user_id = get_current_user_id();
	if ($current_user_id) {
		update_option('tka_wp_utils_installer_id', $current_user_id);
	}
});

tka_wp_utils_run();
