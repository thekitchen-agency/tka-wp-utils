<?php
/**
 * Plugin Name:       TKA Site Utilities
 * Plugin URI:        https://github.com/thekitchen-agency/tka-site-utilities
 * Description:       A collection of utility tools to customize and secure your WordPress experience, including Classic Editor, Classic Widgets, Disable Gutenberg (granular), and Safe SVG upload validation.
 * Version:           1.13.0
 * Author:            TKA
 * Author URI:        https://wp-play.ddev.site
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       tka-site-utilities
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      8.3
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Define plugin constants
define('TKA_SITE_UTILITIES_VERSION', '1.13.0');
define('TKA_SITE_UTILITIES_PATH', plugin_dir_path(__FILE__));
define('TKA_SITE_UTILITIES_URL', plugin_dir_url(__FILE__));

// Define AUTOSAVE_INTERVAL early if the option is set and not already defined
if (!defined('AUTOSAVE_INTERVAL')) {
	$tka_options = get_option('tka_site_utilities_options', []);
	if (!empty($tka_options['autosave_interval'])) {
		define('AUTOSAVE_INTERVAL', intval($tka_options['autosave_interval']));
	}
}

/**
 * Register PSR-4 Autoloader for the plugin.
 */
spl_autoload_register(function ($class) {
	$prefix = 'TKA\\WPUtils\\';
	$base_dir = TKA_SITE_UTILITIES_PATH . 'includes/';

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
function tka_site_utilities_run()
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
		update_option('tka_site_utilities_installer_id', $current_user_id);
	}
});

// Require WooCommerce pluggable overrides early
require_once TKA_SITE_UTILITIES_PATH . 'includes/pluggables.php';

tka_site_utilities_run();
