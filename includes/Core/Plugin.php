<?php

namespace TKA\WPUtils\Core;

use TKA\WPUtils\Admin\Settings;
use TKA\WPUtils\Features\ClassicEditor;
use TKA\WPUtils\Features\ClassicWidgets;
use TKA\WPUtils\Features\GutenbergManager;
use TKA\WPUtils\Features\SvgValidator;
use TKA\WPUtils\Features\VariousCleaner;
use TKA\WPUtils\Features\ContentOrder;
use TKA\WPUtils\Features\ContentDuplicate;
use TKA\WPUtils\Features\AdminInterface;
use TKA\WPUtils\Features\SecurityManager;
use TKA\WPUtils\Features\AdminColumns;
use TKA\WPUtils\Features\AcfManager;
use TKA\WPUtils\Features\ImageOptimizer;
use TKA\WPUtils\Features\GravityFormsManager;
use TKA\WPUtils\Features\WooCommerceManager;
use TKA\WPUtils\Features\MaintenanceMode;
use TKA\WPUtils\Features\MediaFolders;

/**
 * Main Plugin Coordinator class.
 */
class Plugin {

	/**
	 * Singleton instance of the class.
	 */
	private static ?Plugin $instance = null;

	/**
	 * Settings instance.
	 */
	private ?Settings $settings = null;

	/**
	 * Get the singleton instance.
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
		// Initialize the settings handler
		$this->settings = new Settings();
	}

	/**
	 * Run the plugin. Initializes and hooks features based on settings.
	 */
	public function run(): void {
		// Hook the Settings Page admin screens
		$this->settings->init();

		// Fetch currently saved options
		$options = get_option( 'tka_wp_utils_options', [] );

		// Load and hook features if they are enabled
		if ( ! empty( $options['classic_editor'] ) ) {
			$classic_editor = new ClassicEditor();
			$classic_editor->hook();
		}

		if ( ! empty( $options['classic_widgets'] ) ) {
			$classic_widgets = new ClassicWidgets();
			$classic_widgets->hook();
		}

		// Gutenberg disable check
		$gutenberg_mode = $options['disable_gutenberg'] ?? 'none';
		if ( 'none' !== $gutenberg_mode ) {
			$gutenberg_manager = new GutenbergManager( $gutenberg_mode, $options['gutenberg_post_types'] ?? [] );
			$gutenberg_manager->hook();
		}

		// SVG Upload validation
		if ( ! empty( $options['svg_upload'] ) ) {
			$svg_validator = new SvgValidator();
			$svg_validator->hook();
		}

		// Various cleaner utilities
		$various_cleaner = new VariousCleaner( $options );
		$various_cleaner->hook();

		// Content ordering
		if ( ! empty( $options['order_enabled'] ) && ! empty( $options['order_post_types'] ) ) {
			$content_order = new ContentOrder( $options['order_post_types'] );
			$content_order->hook();
		}

		// Content duplication
		if ( ! empty( $options['duplicate_enabled'] ) && ! empty( $options['duplicate_post_types'] ) ) {
			$content_duplicate = new ContentDuplicate( $options['duplicate_post_types'] );
			$content_duplicate->hook();
		}

		// Media Folders
		if ( ! empty( $options['media_folders_enabled'] ) ) {
			$media_folders = new MediaFolders();
			$media_folders->hook();
		}

		// Maintenance Mode
		if ( ! empty( $options['maintenance_enabled'] ) ) {
			$maintenance_mode = new MaintenanceMode( $options );
			$maintenance_mode->hook();
		}

		// Admin Interface menu hiding
		$admin_interface = new AdminInterface( $options );
		$admin_interface->hook();

		// Security Manager
		$security_manager = new SecurityManager( $options );
		$security_manager->hook();

		// Image Optimizer
		$image_optimizer = new ImageOptimizer( $options );
		$image_optimizer->hook();

		// Custom Admin Columns manager
		$columns_options = get_option( 'tka_wp_utils_columns', [] );
		if ( ! empty( $columns_options ) ) {
			$admin_columns = new AdminColumns( $columns_options );
			$admin_columns->hook();
		}

		// Load third-party integrations after all plugins are loaded
		add_action( 'plugins_loaded', function() use ( $options ) {
			error_log('plugins_loaded ran. WooCommerce active: ' . (class_exists('WooCommerce') ? 'YES' : 'NO'));
			// ACF Integration (only runs if ACF is active)
			if ( class_exists( 'ACF' ) ) {
				$acf_manager = new AcfManager( $options );
				$acf_manager->hook();
			}

			// Gravity Forms Integration (only runs if Gravity Forms is active)
			if ( class_exists( 'GFCommon' ) ) {
				$gf_manager = new GravityFormsManager( $options );
				$gf_manager->hook();
			}

			// WooCommerce Integration (only runs if WooCommerce is active)
			if ( class_exists( 'WooCommerce' ) ) {
				$woocommerce_manager = new WooCommerceManager( $options );
				$woocommerce_manager->hook();
			}
		} );
	}
}
