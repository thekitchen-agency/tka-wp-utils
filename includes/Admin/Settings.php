<?php

namespace TKA\WPUtils\Admin;

use TKA\WPUtils\Features\SvgValidator;
use TKA\WPUtils\Features\AdminInterface;

/**
 * Handles the admin settings interface and option registration.
 */
class Settings
{

	/**
	 * Menu page slug.
	 */
	public const MENU_SLUG = 'tka-wp-utils';

	/**
	 * Initialize settings hooks.
	 */
	public function init(): void
	{
		add_action('admin_menu', [$this, 'addMenuPage']);
		add_action('admin_init', [$this, 'registerSettings']);
		add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
	}

	/**
	 * Add the options menu page.
	 */
	public function addMenuPage(): void
	{
		// Restrict main plugin settings and submenus to Superadmins only
		if (\TKA\WPUtils\Features\AdminInterface::isCurrentUserInstaller()) {
			// Convert to Top-Level Sidebar menu page
			add_menu_page(
				__('TKA WP Utils Settings', 'tka-wp-utils'),
				__('TKA WP Utils', 'tka-wp-utils'),
				'manage_options',
				self::MENU_SLUG,
				[$this, 'renderSettingsPage'],
				'dashicons-admin-generic',
				80
			);

			// Default first submenu matching the parent slug
			add_submenu_page(
				self::MENU_SLUG,
				__('TKA WP Utils Settings', 'tka-wp-utils'),
				__('Settings Dashboard', 'tka-wp-utils'),
				'manage_options',
				self::MENU_SLUG,
				[$this, 'renderSettingsPage']
			);

			// Standalone Admin Columns Customizer submenu
			add_submenu_page(
				self::MENU_SLUG,
				__('Admin Columns Customizer', 'tka-wp-utils'),
				__('Admin Columns', 'tka-wp-utils'),
				'manage_options',
				'tka-wp-utils-columns',
				[$this, 'renderAdminColumnsPage']
			);

			// Standalone Admin Menu Organizer submenu
			add_submenu_page(
				self::MENU_SLUG,
				__('Admin Menu Organizer', 'tka-wp-utils'),
				__('Menu Organizer', 'tka-wp-utils'),
				'manage_options',
				'tka-wp-utils-menu-organizer',
				[$this, 'renderMenuOrganizerPage']
			);

			// Standalone Bulk Retroactive Image Optimizer submenu
			add_submenu_page(
				self::MENU_SLUG,
				__('Bulk Retroactive Image Optimizer', 'tka-wp-utils'),
				__('Bulk Optimizer', 'tka-wp-utils'),
				'manage_options',
				'tka-wp-utils-bulk-optimizer',
				[$this, 'renderBulkOptimizerPage']
			);
		}

		// Shortcut to Bulk Optimizer under the Media menu (Available to all admins)
		add_submenu_page(
			'upload.php',
			__('Bulk Retroactive Image Optimizer', 'tka-wp-utils'),
			__('Bulk Optimizer', 'tka-wp-utils'),
			'manage_options',
			'tka-wp-utils-bulk-optimizer-media',
			[$this, 'renderBulkOptimizerPage']
		);
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public function registerSettings(): void
	{
		register_setting(
			'tka_wp_utils_group',
			'tka_wp_utils_options',
			[
				'sanitize_callback' => [$this, 'sanitizeOptions'],
				'default' => [
					'classic_editor' => 0,
					'classic_widgets' => 0,
					'disable_wp_cron' => 0,
					'disable_gutenberg' => 'none',
					'gutenberg_post_types' => [],
					'svg_upload' => 0,
					'disable_emojis' => 0,
					'hide_help_screen_options' => 0,
					'disable_comments' => 0,
					'disable_rest_api' => 0,
					'disable_feeds' => 0,
					'disable_embeds' => 0,
					'disable_version_strings' => 0,
					'disable_front_dashicons' => 0,
					'hide_admin_notices' => 0,
					'order_enabled' => 0,
					'order_post_types' => [],
					'duplicate_enabled' => 0,
					'duplicate_post_types' => [],
					'hidden_admin_menus' => [],
					'admin_bar_cleanup' => [],
					'disabled_dashboard_widgets' => [],
					'admin_menu_order' => [],
					'owner_hidden_admin_menus' => [],
					'owner_admin_menu_order' => [],
					'obfuscate_author_urls' => 0,
					'obfuscate_emails' => 0,
					'disable_xmlrpc' => 0,
					'login_logo' => '',
					'admin_logo' => '',
					'login_custom_css' => '',
					'remove_footer_text' => 0,
					'hide_acf_menu' => 0,
					'acf_video_poster' => 0,
					'disable_acf_shortcode' => 0,
					'acf_custom_json_path' => 0,
					'acf_copy_paste' => 0,
					'acf_copy_paste_multiselect' => 0,
					'acf_layout_modal' => 0,
					'acf_layout_toggle' => 0,
					'acf_layout_rename' => 0,
					'image_optimization_enabled' => 0,
					'webp_conversion_enabled' => 0,
					'webp_keep_original' => 1,
					'image_compression_quality' => 82,
					'compress_original_images' => 0,
					'strip_image_metadata' => 0,
					// WooCommerce Speed & Bloat
					'wc_disable_scripts_non_wc' => 0,
					'wc_disable_cart_fragments' => 'none',
					'wc_disable_block_styles' => 0,
					'wc_disable_password_meter' => 0,
					'wc_clean_admin_ui' => 0,
					// WooCommerce Helpers & Extras
					'wc_buy_now_button' => 0,
					'wc_redirect_sku' => 0,
					'wc_remove_add_to_cart_from_url' => 0,
					'wc_hide_view_cart_shop' => 0,
					'wc_plus_minus_quantity' => 0,
					'wc_quantity_dropdown' => 0,
					// Gravity Forms
					'gf_disable_css' => 0,
					'gf_submit_button_to_button' => 0,
					'gf_submit_button_text_change' => 0,
					'gf_submit_button_loading_text' => 'Sending...',
					'maintenance_enabled' => 0,
					'maintenance_title' => 'Under Maintenance',
					'maintenance_message' => 'Our website is currently undergoing scheduled maintenance. We will be back shortly. Thank you for your patience!',
					'maintenance_logo' => '',
					'maintenance_background' => '',
					'page_transitions_enabled' => 0,
					'page_transitions_override_theme' => 0,
					'page_transitions_default_animation' => 'fade',
					'page_transitions_default_animation_duration' => 400,
					'page_transitions_header_selector' => 'header',
					'page_transitions_main_selector' => 'main',
					'page_transitions_post_title_selector' => '.wp-block-post-title, .entry-title',
					'page_transitions_post_thumbnail_selector' => '.wp-post-image',
					'page_transitions_post_content_selector' => '.wp-block-post-content, .entry-content',
					'page_transitions_enable_admin' => 0,
					'page_transitions_rules' => [],
					'page_transitions_custom_css' => '',
					'wpml_optimization_enabled' => 0,
					'wpml_disable_adjust_ids' => 0,
					'wpml_disable_canonical_redirects_ajax' => 0,
					'gutenberg_dequeue_block_styles' => 0,
					'heartbeat_control' => 'default',
					'heartbeat_frequency' => 60,
					'revisions_limit' => -1,
					'autosave_interval' => 60,
					'htaccess_security' => 0,
					'htaccess_uploads_prevent_php' => 0,
					'htaccess_performance' => 0,
					'htaccess_cors' => 0,
					'htaccess_prevent_author_scan' => 0,
					'smtp_enabled' => 0,
					'smtp_mailpit_dev' => 1,
					'smtp_host' => '',
					'smtp_port' => '',
					'smtp_username' => '',
					'smtp_password' => '',
					'smtp_encryption' => 'none',
				],
			]
		);

		register_setting(
			'tka_wp_utils_columns_group',
			'tka_wp_utils_columns',
			[
				'sanitize_callback' => [$this, 'sanitizeColumnsOptions'],
				'default' => [],
			]
		);
	}

	/**
	 * Sanitize custom admin columns data structure.
	 */
	public function sanitizeColumnsOptions(array $input): array
	{
		$sanitized = [];

		foreach ($input as $post_type => $columns) {
			$post_type = sanitize_key($post_type);
			if (empty($post_type)) {
				continue;
			}

			$sanitized[$post_type] = [];
			if (is_array($columns)) {
				foreach ($columns as $col) {
					$label = isset($col['label']) ? sanitize_text_field($col['label']) : '';
					$meta_key = isset($col['meta_key']) ? sanitize_text_field($col['meta_key']) : '';
					$field_type = isset($col['field_type']) ? sanitize_text_field($col['field_type']) : 'text';

					if (!empty($label) || !empty($meta_key)) {
						$sanitized[$post_type][] = [
							'label' => $label,
							'meta_key' => $meta_key,
							'field_type' => $field_type,
						];
					}
				}
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize options callback.
	 */
	public function sanitizeOptions(array $input): array
	{
		$existing = get_option('tka_wp_utils_options', []);
		if (!is_array($existing)) {
			$existing = [];
		}

		// Merge existing options with defaults to ensure all keys exist
		$defaults = [
			'classic_editor' => 0,
			'classic_widgets' => 0,
			'disable_wp_cron' => 0,
			'disable_gutenberg' => 'none',
			'gutenberg_post_types' => [],
			'svg_upload' => 0,
			'disable_emojis' => 0,
			'hide_help_screen_options' => 0,
			'disable_comments' => 0,
			'disable_rest_api' => 0,
			'disable_feeds' => 0,
			'disable_embeds' => 0,
			'disable_version_strings' => 0,
			'disable_front_dashicons' => 0,
			'hide_admin_notices' => 0,
			'order_enabled' => 0,
			'order_post_types' => [],
			'duplicate_enabled' => 0,
			'duplicate_post_types' => [],
			'media_folders_enabled' => 0,
			'hidden_admin_menus' => [],
			'admin_bar_cleanup' => [],
			'disabled_dashboard_widgets' => [],
			'admin_menu_order' => [],
			'owner_hidden_admin_menus' => [],
			'owner_admin_menu_order' => [],
			'obfuscate_author_urls' => 0,
			'obfuscate_emails' => 0,
			'disable_xmlrpc' => 0,
			'login_logo' => '',
			'admin_logo' => '',
			'login_custom_css' => '',
			'remove_footer_text' => 0,
			'hide_acf_menu' => 0,
			'acf_video_poster' => 0,
			'disable_acf_shortcode' => 0,
			'acf_custom_json_path' => 0,
			'acf_copy_paste' => 0,
			'acf_copy_paste_multiselect' => 0,
			'acf_layout_modal' => 0,
			'acf_layout_toggle' => 0,
			'acf_layout_rename' => 0,
			'image_optimization_enabled' => 0,
			'webp_conversion_enabled' => 0,
			'webp_keep_original' => 1,
			'image_compression_quality' => 82,
			'compress_original_images' => 0,
			'strip_image_metadata' => 0,
			'wc_disable_scripts_non_wc' => 0,
			'wc_disable_cart_fragments' => 'none',
			'wc_disable_block_styles' => 0,
			'wc_disable_password_meter' => 0,
			'wc_clean_admin_ui' => 0,
			'wc_buy_now_button' => 0,
			'wc_redirect_sku' => 0,
			'wc_remove_add_to_cart_from_url' => 0,
			'wc_hide_view_cart_shop' => 0,
			'wc_plus_minus_quantity' => 0,
			'wc_quantity_dropdown' => 0,
			'gf_disable_css' => 0,
			'gf_submit_button_to_button' => 0,
			'gf_submit_button_text_change' => 0,
			'gf_submit_button_loading_text' => 'Sending...',
			'maintenance_enabled' => 0,
			'maintenance_title' => 'Under Maintenance',
			'maintenance_message' => 'Our website is currently undergoing scheduled maintenance. We will be back shortly. Thank you for your patience!',
			'maintenance_logo' => '',
			'maintenance_background' => '',
			'page_transitions_enabled' => 0,
			'page_transitions_override_theme' => 0,
			'page_transitions_default_animation' => 'fade',
			'page_transitions_default_animation_duration' => 400,
			'page_transitions_header_selector' => 'header',
			'page_transitions_main_selector' => 'main',
			'page_transitions_post_title_selector' => '.wp-block-post-title, .entry-title',
			'page_transitions_post_thumbnail_selector' => '.wp-post-image',
			'page_transitions_post_content_selector' => '.wp-block-post-content, .entry-content',
			'page_transitions_enable_admin' => 0,
			'page_transitions_rules' => [],
			'page_transitions_custom_css' => '',
			'wpml_optimization_enabled' => 0,
			'wpml_disable_adjust_ids' => 0,
			'wpml_disable_canonical_redirects_ajax' => 0,
			'gutenberg_dequeue_block_styles' => 0,
			'heartbeat_control' => 'default',
			'heartbeat_frequency' => 60,
			'revisions_limit' => -1,
			'autosave_interval' => 60,
			'htaccess_security' => 0,
			'htaccess_uploads_prevent_php' => 0,
			'htaccess_performance' => 0,
			'htaccess_cors' => 0,
			'htaccess_prevent_author_scan' => 0,
			'smtp_enabled' => 0,
			'smtp_mailpit_dev' => 1,
			'smtp_host' => '',
			'smtp_port' => '',
			'smtp_username' => '',
			'smtp_password' => '',
			'smtp_encryption' => 'none',
		];

		$sanitized = array_merge($defaults, $existing);

		$form_context = $input['form_context'] ?? '';
		if (empty($form_context)) {
			if (isset($input['classic_editor']) || isset($input['image_optimization_enabled']) || isset($input['disable_gutenberg'])) {
				$form_context = 'general_settings';
			} elseif (isset($input['admin_menu_order']) || isset($input['hidden_admin_menus']) || isset($input['owner_admin_menu_order']) || isset($input['owner_hidden_admin_menus'])) {
				$form_context = 'menu_organizer';
			}
		}

		if ($form_context === 'menu_organizer') {
			$is_owner = AdminInterface::isCurrentUserInstaller();

			// Always sanitize and save client/other admin layout settings when submitted
			$sanitized['hidden_admin_menus'] = [];
			if (isset($input['hidden_admin_menus']) && is_array($input['hidden_admin_menus'])) {
				foreach ($input['hidden_admin_menus'] as $menu) {
					$sanitized['hidden_admin_menus'][] = sanitize_text_field($menu);
				}
			}

			$sanitized['admin_menu_order'] = [];
			if (isset($input['admin_menu_order']) && is_array($input['admin_menu_order'])) {
				foreach ($input['admin_menu_order'] as $slug) {
					$sanitized['admin_menu_order'][] = sanitize_text_field($slug);
				}
			}

			// Only save/update owner layout settings if the saving user is the owner
			if ($is_owner) {
				$sanitized['owner_hidden_admin_menus'] = [];
				if (isset($input['owner_hidden_admin_menus']) && is_array($input['owner_hidden_admin_menus'])) {
					foreach ($input['owner_hidden_admin_menus'] as $menu) {
						$sanitized['owner_hidden_admin_menus'][] = sanitize_text_field($menu);
					}
				}

				$sanitized['owner_admin_menu_order'] = [];
				if (isset($input['owner_admin_menu_order']) && is_array($input['owner_admin_menu_order'])) {
					foreach ($input['owner_admin_menu_order'] as $slug) {
						$sanitized['owner_admin_menu_order'][] = sanitize_text_field($slug);
					}
				}
			}
		} elseif ($form_context === 'general_settings') {
			$sanitized['classic_editor'] = isset($input['classic_editor']) ? 1 : 0;
			$sanitized['classic_widgets'] = isset($input['classic_widgets']) ? 1 : 0;
			$sanitized['disable_wp_cron'] = isset($input['disable_wp_cron']) ? 1 : 0;

			$gutenberg_mode = $input['disable_gutenberg'] ?? 'none';
			$sanitized['disable_gutenberg'] = in_array($gutenberg_mode, ['none', 'all', 'post_types', 'wc_except_cart_checkout'], true) ? $gutenberg_mode : 'none';

			$sanitized['gutenberg_post_types'] = [];
			if (isset($input['gutenberg_post_types']) && is_array($input['gutenberg_post_types'])) {
				foreach ($input['gutenberg_post_types'] as $post_type) {
					$sanitized['gutenberg_post_types'][] = sanitize_text_field($post_type);
				}
			}

			$sanitized['svg_upload'] = isset($input['svg_upload']) ? 1 : 0;

			// Sanitize various utilities toggles
			$sanitized['disable_emojis'] = isset($input['disable_emojis']) ? 1 : 0;
			$sanitized['disable_comments'] = isset($input['disable_comments']) ? 1 : 0;
			$sanitized['disable_rest_api'] = isset($input['disable_rest_api']) ? 1 : 0;
			$sanitized['disable_feeds'] = isset($input['disable_feeds']) ? 1 : 0;
			$sanitized['disable_embeds'] = isset($input['disable_embeds']) ? 1 : 0;
			$sanitized['disable_version_strings'] = isset($input['disable_version_strings']) ? 1 : 0;
			$sanitized['disable_front_dashicons'] = isset($input['disable_front_dashicons']) ? 1 : 0;

			// Sanitize Content Management toggles
			$sanitized['order_enabled'] = isset($input['order_enabled']) ? 1 : 0;
			$sanitized['order_post_types'] = [];
			if (isset($input['order_post_types']) && is_array($input['order_post_types'])) {
				foreach ($input['order_post_types'] as $post_type) {
					$sanitized['order_post_types'][] = sanitize_text_field($post_type);
				}
			}

			$sanitized['duplicate_enabled'] = isset($input['duplicate_enabled']) ? 1 : 0;
			$sanitized['duplicate_post_types'] = [];
			if (isset($input['duplicate_post_types']) && is_array($input['duplicate_post_types'])) {
				foreach ($input['duplicate_post_types'] as $post_type) {
					$sanitized['duplicate_post_types'][] = sanitize_text_field($post_type);
				}
			}

			$sanitized['media_folders_enabled'] = isset($input['media_folders_enabled']) ? 1 : 0;

			$sanitized['obfuscate_author_urls'] = isset($input['obfuscate_author_urls']) ? 1 : 0;
			$sanitized['obfuscate_emails'] = isset($input['obfuscate_emails']) ? 1 : 0;
			$sanitized['disable_xmlrpc'] = isset($input['disable_xmlrpc']) ? 1 : 0;

			$sanitized['htaccess_security'] = isset($input['htaccess_security']) ? 1 : 0;
			$sanitized['htaccess_uploads_prevent_php'] = isset($input['htaccess_uploads_prevent_php']) ? 1 : 0;
			$sanitized['htaccess_performance'] = isset($input['htaccess_performance']) ? 1 : 0;
			$sanitized['htaccess_cors'] = isset($input['htaccess_cors']) ? 1 : 0;
			$sanitized['htaccess_prevent_author_scan'] = isset($input['htaccess_prevent_prevent_author_scan']) || isset($input['htaccess_prevent_author_scan']) ? 1 : 0;

			// Admin Interface (conditional)
			if (AdminInterface::isCurrentUserInstaller()) {
				$sanitized['superadmin_users'] = [];
				if (isset($input['superadmin_users']) && is_array($input['superadmin_users'])) {
					foreach ($input['superadmin_users'] as $uid) {
						$sanitized['superadmin_users'][] = intval($uid);
					}
				}

				$sanitized['hide_help_screen_options'] = isset($input['hide_help_screen_options']) ? 1 : 0;
				$sanitized['disable_command_palette'] = isset($input['disable_command_palette']) ? 1 : 0;
				$sanitized['hide_admin_notices'] = isset($input['hide_admin_notices']) ? 1 : 0;
				$sanitized['remove_footer_text'] = isset($input['remove_footer_text']) ? 1 : 0;

				$sanitized['admin_bar_cleanup'] = [];
				if (isset($input['admin_bar_cleanup']) && is_array($input['admin_bar_cleanup'])) {
					foreach ($input['admin_bar_cleanup'] as $item) {
						$sanitized['admin_bar_cleanup'][] = sanitize_text_field($item);
					}
				}

				$sanitized['disabled_dashboard_widgets'] = [];
				if (isset($input['disabled_dashboard_widgets']) && is_array($input['disabled_dashboard_widgets'])) {
					foreach ($input['disabled_dashboard_widgets'] as $widget) {
						$sanitized['disabled_dashboard_widgets'][] = sanitize_text_field($widget);
					}
				}

				$sanitized['login_logo'] = isset($input['login_logo']) ? sanitize_text_field($input['login_logo']) : '';
				$sanitized['admin_logo'] = isset($input['admin_logo']) ? sanitize_text_field($input['admin_logo']) : '';
				$sanitized['login_custom_css'] = isset($input['login_custom_css']) ? wp_strip_all_tags($input['login_custom_css']) : '';
			}

			// ACF Integration (conditional)
			if (class_exists('ACF')) {
				$sanitized['hide_acf_menu'] = isset($input['hide_acf_menu']) ? 1 : 0;
				$sanitized['acf_video_poster'] = isset($input['acf_video_poster']) ? 1 : 0;
				$sanitized['disable_acf_shortcode'] = isset($input['disable_acf_shortcode']) ? 1 : 0;
				$sanitized['acf_custom_json_path'] = isset($input['acf_custom_json_path']) ? 1 : 0;
				$sanitized['acf_copy_paste'] = isset($input['acf_copy_paste']) ? 1 : 0;
				$sanitized['acf_copy_paste_multiselect'] = isset($input['acf_copy_paste_multiselect']) ? 1 : 0;
				$sanitized['acf_layout_modal'] = isset($input['acf_layout_modal']) ? 1 : 0;
				$sanitized['acf_layout_toggle'] = isset($input['acf_layout_toggle']) ? 1 : 0;
				$sanitized['acf_layout_rename'] = isset($input['acf_layout_rename']) ? 1 : 0;

				$sanitized['acf_extensions'] = [];
				if (isset($input['acf_extensions']) && is_array($input['acf_extensions'])) {
					foreach ($input['acf_extensions'] as $ext) {
						$sanitized['acf_extensions'][] = sanitize_file_name($ext);
					}
				}
			}

			// Image Optimization
			$sanitized['image_optimization_enabled'] = isset($input['image_optimization_enabled']) ? 1 : 0;
			$sanitized['webp_conversion_enabled'] = isset($input['webp_conversion_enabled']) ? 1 : 0;
			$sanitized['webp_keep_original'] = isset($input['webp_keep_original']) ? 1 : 0;
			$sanitized['image_compression_quality'] = isset($input['image_compression_quality']) ? max(50, min(100, intval($input['image_compression_quality']))) : 82;
			$sanitized['compress_original_images'] = isset($input['compress_original_images']) ? 1 : 0;
			$sanitized['strip_image_metadata'] = isset($input['strip_image_metadata']) ? 1 : 0;

			// WooCommerce (conditional)
			if (class_exists('WooCommerce')) {
				$sanitized['wc_disable_scripts_non_wc'] = isset($input['wc_disable_scripts_non_wc']) ? 1 : 0;
				$sanitized['wc_disable_cart_fragments'] = isset($input['wc_disable_cart_fragments']) && in_array($input['wc_disable_cart_fragments'], ['none', 'all', 'non_shop'], true) ? $input['wc_disable_cart_fragments'] : 'none';
				$sanitized['wc_disable_block_styles'] = isset($input['wc_disable_block_styles']) ? 1 : 0;
				$sanitized['wc_disable_password_meter'] = isset($input['wc_disable_password_meter']) ? 1 : 0;
				$sanitized['wc_clean_admin_ui'] = isset($input['wc_clean_admin_ui']) ? 1 : 0;

				$sanitized['wc_buy_now_button'] = isset($input['wc_buy_now_button']) ? 1 : 0;
				$sanitized['wc_redirect_sku'] = isset($input['wc_redirect_sku']) ? 1 : 0;
				$sanitized['wc_remove_add_to_cart_from_url'] = isset($input['wc_remove_add_to_cart_from_url']) ? 1 : 0;
				$sanitized['wc_hide_view_cart_shop'] = isset($input['wc_hide_view_cart_shop']) ? 1 : 0;
				$sanitized['wc_plus_minus_quantity'] = isset($input['wc_plus_minus_quantity']) ? 1 : 0;
				$sanitized['wc_quantity_dropdown'] = isset($input['wc_quantity_dropdown']) ? 1 : 0;
			}

			// Gravity Forms (conditional)
			if (class_exists('GFCommon')) {
				$sanitized['gf_disable_css'] = isset($input['gf_disable_css']) ? 1 : 0;
				$sanitized['gf_submit_button_to_button'] = isset($input['gf_submit_button_to_button']) ? 1 : 0;
				$sanitized['gf_submit_button_text_change'] = isset($input['gf_submit_button_text_change']) ? 1 : 0;
				$sanitized['gf_submit_button_loading_text'] = isset($input['gf_submit_button_loading_text']) ? sanitize_text_field($input['gf_submit_button_loading_text']) : 'Sending...';
			}

			// Maintenance Mode
			$sanitized['maintenance_enabled'] = isset($input['maintenance_enabled']) ? 1 : 0;
			$sanitized['maintenance_title'] = isset($input['maintenance_title']) ? sanitize_text_field($input['maintenance_title']) : 'Under Maintenance';
			$sanitized['maintenance_message'] = isset($input['maintenance_message']) ? sanitize_textarea_field($input['maintenance_message']) : '';
			$sanitized['maintenance_logo'] = isset($input['maintenance_logo']) ? sanitize_text_field($input['maintenance_logo']) : '';
			$sanitized['maintenance_background'] = isset($input['maintenance_background']) ? sanitize_text_field($input['maintenance_background']) : '';

			// Page Transitions
			$sanitized['page_transitions_enabled'] = isset($input['page_transitions_enabled']) ? 1 : 0;
			$sanitized['page_transitions_override_theme'] = isset($input['page_transitions_override_theme']) ? 1 : 0;

			$allowed_animations = [
				'fade',
				'slide-from-right',
				'slide-from-left',
				'slide-from-bottom',
				'slide-from-top',
				'swipe-from-right',
				'swipe-from-left',
				'swipe-from-bottom',
				'swipe-from-top',
				'wipe-from-right',
				'wipe-from-left',
				'wipe-from-bottom',
				'wipe-from-top'
			];
			$sanitized['page_transitions_default_animation'] = (isset($input['page_transitions_default_animation']) && in_array($input['page_transitions_default_animation'], $allowed_animations, true)) ? $input['page_transitions_default_animation'] : 'fade';
			$sanitized['page_transitions_default_animation_duration'] = isset($input['page_transitions_default_animation_duration']) ? absint($input['page_transitions_default_animation_duration']) : 400;

			$pt_selectors = [
				'page_transitions_header_selector' => 'header',
				'page_transitions_main_selector' => 'main',
				'page_transitions_post_title_selector' => '.wp-block-post-title, .entry-title',
				'page_transitions_post_thumbnail_selector' => '.wp-post-image',
				'page_transitions_post_content_selector' => '.wp-block-post-content, .entry-content',
			];
			foreach ($pt_selectors as $key => $default_sel) {
				$sanitized[$key] = (isset($input[$key]) && is_string($input[$key]) && '' !== trim($input[$key])) ? sanitize_text_field(trim($input[$key])) : $default_sel;
			}
			$sanitized['page_transitions_enable_admin'] = isset($input['page_transitions_enable_admin']) ? 1 : 0;

			// Sanitize Page Transitions Rules
			$sanitized['page_transitions_rules'] = [];
			if (isset($input['page_transitions_rules']) && is_array($input['page_transitions_rules'])) {
				foreach ($input['page_transitions_rules'] as $rule) {
					if (empty($rule['animation'])) {
						continue;
					}
					$sanitized['page_transitions_rules'][] = [
						'from_type' => sanitize_key($rule['from_type'] ?? 'any'),
						'from_url' => sanitize_text_field($rule['from_url'] ?? ''),
						'to_type' => sanitize_key($rule['to_type'] ?? 'any'),
						'to_url' => sanitize_text_field($rule['to_url'] ?? ''),
						'animation' => in_array($rule['animation'], array_merge($allowed_animations, ['custom']), true) ? $rule['animation'] : 'fade',
						'custom_class' => sanitize_html_class($rule['custom_class'] ?? ''),
					];
				}
			}

			$sanitized['page_transitions_custom_css'] = isset($input['page_transitions_custom_css']) ? wp_strip_all_tags($input['page_transitions_custom_css']) : '';
			$sanitized['wpml_optimization_enabled'] = isset($input['wpml_optimization_enabled']) ? 1 : 0;
			$sanitized['wpml_disable_adjust_ids'] = isset($input['wpml_disable_adjust_ids']) ? 1 : 0;
			$sanitized['wpml_disable_canonical_redirects_ajax'] = isset($input['wpml_disable_canonical_redirects_ajax']) ? 1 : 0;
			$sanitized['gutenberg_dequeue_block_styles'] = isset($input['gutenberg_dequeue_block_styles']) ? 1 : 0;

			// Heartbeat
			$allowed_heartbeat = ['default', 'disable_everywhere', 'disable_dashboard', 'allow_only_post_edit'];
			$sanitized['heartbeat_control'] = (isset($input['heartbeat_control']) && in_array($input['heartbeat_control'], $allowed_heartbeat, true)) ? $input['heartbeat_control'] : 'default';
			$sanitized['heartbeat_frequency'] = isset($input['heartbeat_frequency']) ? max(15, min(120, intval($input['heartbeat_frequency']))) : 60;

			// Revisions & Autosave
			$sanitized['revisions_limit'] = isset($input['revisions_limit']) ? intval($input['revisions_limit']) : -1;
			$sanitized['autosave_interval'] = isset($input['autosave_interval']) ? max(60, min(300, intval($input['autosave_interval']))) : 60;

			// SMTP Settings
			$sanitized['smtp_enabled'] = isset($input['smtp_enabled']) ? 1 : 0;
			$sanitized['smtp_mailpit_dev'] = isset($input['smtp_mailpit_dev']) ? 1 : 0;
			$sanitized['smtp_host'] = isset($input['smtp_host']) ? sanitize_text_field($input['smtp_host']) : '';
			$sanitized['smtp_port'] = isset($input['smtp_port']) ? absint($input['smtp_port']) : '';
			$sanitized['smtp_username'] = isset($input['smtp_username']) ? sanitize_text_field($input['smtp_username']) : '';
			$sanitized['smtp_password'] = isset($input['smtp_password']) ? sanitize_text_field($input['smtp_password']) : '';
			
			$allowed_encryption = ['none', 'ssl', 'tls'];
			$sanitized['smtp_encryption'] = (isset($input['smtp_encryption']) && in_array($input['smtp_encryption'], $allowed_encryption, true)) ? $input['smtp_encryption'] : 'none';
		}

		return $sanitized;
	}

	/**
	 * Enqueue assets only on the settings page.
	 */
	public function enqueueAssets(string $hook): void
	{
		$allowed_hooks = [
			'toplevel_page_' . self::MENU_SLUG,
			'tka-wp-utils_page_tka-wp-utils-columns',
			'tka-wp-utils_page_tka-wp-utils-menu-organizer',
			'tka-wp-utils_page_tka-wp-utils-bulk-optimizer',
			'media_page_tka-wp-utils-bulk-optimizer-media',
			'admin_page_tka-wp-utils-bulk-optimizer-media',
			'settings_page_' . self::MENU_SLUG,
			'tka-wp-utils_page_' . self::MENU_SLUG,
			'admin_page_' . self::MENU_SLUG,
		];

		if (!in_array($hook, $allowed_hooks, true)) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'tka-wp-utils-admin-css',
			TKA_WP_UTILS_URL . 'admin/css/admin-style.css',
			[],
			TKA_WP_UTILS_VERSION
		);

		wp_enqueue_script(
			'tka-wp-utils-admin-js',
			TKA_WP_UTILS_URL . 'admin/js/admin-script.js',
			['jquery', 'jquery-ui-sortable'],
			TKA_WP_UTILS_VERSION,
			true
		);

		wp_localize_script('tka-wp-utils-admin-js', 'tkaWpUtilsAdmin', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
		]);

		if ('tka-wp-utils_page_tka-wp-utils-columns' === $hook) {
			wp_enqueue_script(
				'tka-wp-utils-columns-js',
				TKA_WP_UTILS_URL . 'admin/js/admin-columns.js',
				['jquery', 'jquery-ui-sortable'],
				TKA_WP_UTILS_VERSION,
				true
			);
			wp_localize_script('tka-wp-utils-columns-js', 'tkaWpUtilsColumns', [
				'metaKeys' => self::getAvailableMetaKeys(),
				'i18n' => [
					'selectField' => __('— Select a Field —', 'tka-wp-utils'),
					'enterCustomKey' => __('— Enter Custom Key —', 'tka-wp-utils'),
					'customKeyPlaceholder' => __('Enter Custom Meta Key', 'tka-wp-utils'),
					'plainText' => __('Plain Text / Value', 'tka-wp-utils'),
					'relatedPost' => __('Related Post ID or Object (Linked & Filterable)', 'tka-wp-utils'),
					'relatedTerm' => __('Related Taxonomy Term (Linked & Filterable)', 'tka-wp-utils'),
				],
			]);
		}
	}

	/**
	 * Get a list of all distinct meta keys in the database.
	 */
	public static function getAvailableMetaKeys(): array
	{
		global $wpdb;
		$keys = $wpdb->get_col("
			SELECT DISTINCT meta_key 
			FROM $wpdb->postmeta 
			WHERE meta_key NOT LIKE '\\_edit\\_%' 
			  AND meta_key NOT LIKE '\\_wp\\_%'
			  AND meta_key NOT LIKE '\\_oembed\\_%'
			ORDER BY meta_key ASC
		");
		return array_values(array_filter($keys));
	}


	/**
	 * Render the settings page HTML.
	 */
	public function renderSettingsPage(): void
	{
		$options = get_option('tka_wp_utils_options');
		$public_post_types = get_post_types(['show_ui' => true], 'objects');
		?>
				<div class="wrap tka-wp-utils-wrap">
					<div class="tka-dashboard">
						<!-- Header Section -->
						<header class="tka-dashboard-header">
							<div class="tka-header-brand">
								<span class="dashicons dashicons-admin-generic" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
								<h1>TKA WP Utils</h1>
								<span class="tka-version-badge">v<?php echo esc_html(TKA_WP_UTILS_VERSION); ?></span>
							</div>
							<p class="tka-tagline">
								<?php esc_html_e('Customize and secure your WordPress publishing workflow.', 'tka-wp-utils'); ?></p>
						</header>

						<!-- Settings Body Layout -->
						<div class="tka-dashboard-body">
							<!-- Sidebar Menu -->
							<aside class="tka-dashboard-sidebar">
								<nav class="tka-dashboard-nav">
									<a href="#general" class="tka-nav-item active" data-tab="general">
										<span class="dashicons dashicons-admin-settings"></span>
										<?php esc_html_e('Editor & Widgets', 'tka-wp-utils'); ?>
									</a>
									<a href="#gutenberg" class="tka-nav-item" data-tab="gutenberg">
										<span class="dashicons dashicons-edit"></span>
										<?php esc_html_e('Gutenberg Control', 'tka-wp-utils'); ?>
									</a>
									<a href="#uploads" class="tka-nav-item" data-tab="uploads">
										<span class="dashicons dashicons-shield"></span>
										<?php esc_html_e('Security', 'tka-wp-utils'); ?>
									</a>
									<a href="#various" class="tka-nav-item" data-tab="various">
										<span class="dashicons dashicons-admin-generic"></span>
										<?php esc_html_e('Various Settings', 'tka-wp-utils'); ?>
									</a>
									<a href="#content" class="tka-nav-item" data-tab="content">
										<span class="dashicons dashicons-category"></span>
										<?php esc_html_e('Content Management', 'tka-wp-utils'); ?>
									</a>
									<?php if (AdminInterface::isCurrentUserInstaller()): ?>
											<a href="#admin-interface" class="tka-nav-item" data-tab="admin-interface">
												<span class="dashicons dashicons-admin-users"></span>
												<?php esc_html_e('Admin Interface', 'tka-wp-utils'); ?>
											</a>
											<a href="#design" class="tka-nav-item" data-tab="design">
												<span class="dashicons dashicons-art"></span>
												<?php esc_html_e('Design Customization', 'tka-wp-utils'); ?>
											</a>
									<?php endif; ?>
									<?php if (class_exists('ACF')): ?>
											<a href="#acf" class="tka-nav-item" data-tab="acf">
												<span class="dashicons dashicons-welcome-widgets-menus"></span>
												<?php esc_html_e('ACF Settings', 'tka-wp-utils'); ?>
											</a>
									<?php endif; ?>
									<a href="#images" class="tka-nav-item" data-tab="images">
										<span class="dashicons dashicons-format-image"></span>
										<?php esc_html_e('Image Optimization', 'tka-wp-utils'); ?>
									</a>
									<?php if (class_exists('GFCommon')): ?>
											<a href="#gravity-forms" class="tka-nav-item" data-tab="gravity-forms">
												<span class="dashicons dashicons-feedback"></span>
												<?php esc_html_e('Gravity Forms', 'tka-wp-utils'); ?>
											</a>
									<?php endif; ?>
									<?php if (class_exists('WooCommerce')): ?>
											<a href="#woocommerce" class="tka-nav-item" data-tab="woocommerce">
												<span class="dashicons dashicons-cart"></span>
												<?php esc_html_e('WooCommerce', 'tka-wp-utils'); ?>
											</a>
									<?php endif; ?>
									<?php if (class_exists('SitePress') || defined('ICL_SITEPRESS_VERSION')): ?>
											<a href="#wpml-opt" class="tka-nav-item" data-tab="wpml-opt">
												<span class="dashicons dashicons-translation"></span>
												<?php esc_html_e('WPML Optimization', 'tka-wp-utils'); ?>
											</a>
									<?php endif; ?>
									<a href="#transitions" class="tka-nav-item" data-tab="transitions">
										<span class="dashicons dashicons-randomize"></span>
										<?php esc_html_e('Page Transitions', 'tka-wp-utils'); ?>
									</a>
									<a href="#maintenance" class="tka-nav-item" data-tab="maintenance">
										<span class="dashicons dashicons-clock"></span>
										<?php esc_html_e('Maintenance Mode', 'tka-wp-utils'); ?>
									</a>
									<a href="#htaccess" class="tka-nav-item" data-tab="htaccess">
										<span class="dashicons dashicons-editor-code"></span>
										<?php esc_html_e('.htaccess Control', 'tka-wp-utils'); ?>
									</a>
									<a href="#smtp" class="tka-nav-item" data-tab="smtp">
										<span class="dashicons dashicons-email"></span>
										<?php esc_html_e('SMTP & Email', 'tka-wp-utils'); ?>
									</a>
							
								</nav>

								<div class="tka-sidebar-info">
									<h3><?php esc_html_e('System Status', 'tka-wp-utils'); ?></h3>
									<ul class="tka-status-list">
										<li>
											<span>WordPress</span>
											<strong><?php echo esc_html(get_bloginfo('version')); ?></strong>
										</li>
										<li>
											<span>PHP Version</span>
											<strong><?php echo esc_html(PHP_VERSION); ?></strong>
										</li>
										<li>
											<span>Max Upload Size</span>
											<strong><?php echo esc_html(size_format(wp_max_upload_size())); ?></strong>
										</li>
									</ul>
								</div>
							</aside>

							<!-- Settings Form Panels -->
							<main class="tka-dashboard-content">
								<form method="post" action="options.php">
									<?php
									settings_fields('tka_wp_utils_group');
									?>
									<input type="hidden" name="tka_wp_utils_options[form_context]" value="general_settings">

									<!-- GENERAL PANEL -->
									<section id="panel-general" class="tka-tab-panel active">
										<h2><?php esc_html_e('Classic Experience Settings', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Restore the tried-and-true classic WordPress editors and widget workflows.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Classic Editor', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Reverts post/page creation and editing back to the classic rich text (TinyMCE) editor.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[classic_editor]" value="1"
															<?php checked(1, $options['classic_editor'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Classic Widgets', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Restores the traditional widgets dashboard and blocks the block-based widget editor.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[classic_widgets]" value="1"
															<?php checked(1, $options['classic_widgets'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>
									</section>

									<!-- GUTENBERG PANEL -->
									<section id="panel-gutenberg" class="tka-tab-panel">
										<h2><?php esc_html_e('Gutenberg Editor Control', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Decide exactly where and when the Gutenberg Block Editor is active.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row stack">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Gutenberg Block Editor', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Choose to allow Gutenberg, completely disable it, or disable it only for specific content types.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control-radios">
													<label class="tka-radio-card">
														<input type="radio" name="tka_wp_utils_options[disable_gutenberg]" value="none"
															<?php checked('none', $options['disable_gutenberg'] ?? 'none'); ?>>
														<div class="radio-card-content">
															<strong><?php esc_html_e('Keep Gutenberg Active', 'tka-wp-utils'); ?></strong>
															<span><?php esc_html_e('Default WordPress block editor behavior.', 'tka-wp-utils'); ?></span>
														</div>
													</label>

													<label class="tka-radio-card">
														<input type="radio" name="tka_wp_utils_options[disable_gutenberg]" value="all"
															<?php checked('all', $options['disable_gutenberg'] ?? 'none'); ?>>
														<div class="radio-card-content">
															<strong><?php esc_html_e('Disable Completely', 'tka-wp-utils'); ?></strong>
															<span><?php esc_html_e('Globally disable Gutenberg for all post types.', 'tka-wp-utils'); ?></span>
														</div>
													</label>

													<label class="tka-radio-card">
														<input type="radio" name="tka_wp_utils_options[disable_gutenberg]"
															value="post_types" <?php checked('post_types', $options['disable_gutenberg'] ?? 'none'); ?>>
														<div class="radio-card-content">
															<strong><?php esc_html_e('Disable by Post Type', 'tka-wp-utils'); ?></strong>
															<span><?php esc_html_e('Selectively revert back to Classic Editor on specific post types.', 'tka-wp-utils'); ?></span>
														</div>
													</label>

													<?php if (class_exists('WooCommerce')): ?>
															<label class="tka-radio-card">
																<input type="radio" name="tka_wp_utils_options[disable_gutenberg]"
																	value="wc_except_cart_checkout" <?php checked('wc_except_cart_checkout', $options['disable_gutenberg'] ?? 'none'); ?>>
																<div class="radio-card-content">
																	<strong><?php esc_html_e('Disable everywhere except Cart & Checkout', 'tka-wp-utils'); ?></strong>
																	<span><?php esc_html_e('Suppresses Gutenberg everywhere, but preserves it on WooCommerce Cart and Checkout pages.', 'tka-wp-utils'); ?></span>
																</div>
															</label>
													<?php endif; ?>
												</div>
											</div>

											<div class="tka-setting-row nested-gutenberg-post-types"
												style="<?php echo ('post_types' === ($options['disable_gutenberg'] ?? 'none')) ? 'display: block;' : 'display: none;'; ?>">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Choose Post Types to Disable Gutenberg', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Gutenberg editor will be turned OFF for any post type checked below.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-checkbox-grid">
													<?php foreach ($public_post_types as $post_type): ?>
															<label class="tka-checkbox-item">
																<input type="checkbox" name="tka_wp_utils_options[gutenberg_post_types][]"
																	value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $options['gutenberg_post_types'] ?? [], true)); ?>>
																<span><?php echo esc_html($post_type->label); ?> <code
																		class="tka-code-badge"><?php echo esc_html($post_type->name); ?></code></span>
															</label>
													<?php endforeach; ?>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- Dequeue Gutenberg Block Styles -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Dequeue Core Gutenberg Block Styles', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Removes core Gutenberg library stylesheets (wp-block-library.css and wp-block-library-theme.css) from enqueuing on the frontend. Highly recommended if you use Classic Editor, ACF Layouts, or custom page builders.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[gutenberg_dequeue_block_styles]" value="1"
															<?php checked(1, $options['gutenberg_dequeue_block_styles'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>
									</section>

									<!-- SECURITY PANEL -->
									<section id="panel-uploads" class="tka-tab-panel">
										<h2><?php esc_html_e('Security & Hardening Settings', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Protect your WordPress installation from user enumeration, spam harvesters, brute-force attacks, and XML security threats.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Allow SVG Uploads with Strict Validation', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Allows administrators and creators to upload .svg vectors. Validates the XML hierarchy upon upload to protect against XML Entity Expansion (XXE) and Cross-Site Scripting (XSS) injection scripts.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[svg_upload]" value="1" <?php checked(1, $options['svg_upload'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Obfuscate Author URLs & REST User Slugs', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Hides usernames from public author links (e.g. /author/username/ becomes /author/obfuscated_hash/) and REST API endpoints. Attempts to request the original username-based author links return a 404 error.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[obfuscate_author_urls]"
															value="1" <?php checked(1, $options['obfuscate_author_urls'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Obfuscate Email Addresses', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Scans published post and widget contents to convert email addresses into randomized decimal/hexadecimal HTML entities. Keeps emails perfectly readable for humans but shields them from automatic spam scraping bots.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[obfuscate_emails]" value="1"
															<?php checked(1, $options['obfuscate_emails'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable XML-RPC & Block Trackbacks/Pingbacks', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Protects your site from remote brute-force, DOS, and DDOS attacks by disabling the legacy XML-RPC protocol completely, and stripping all active pingback headers.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[disable_xmlrpc]" value="1"
															<?php checked(1, $options['disable_xmlrpc'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>
									</section>

									<!-- VARIOUS PANEL -->
									<section id="panel-various" class="tka-tab-panel">
										<h2><?php esc_html_e('Various Settings & Optimizations', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Clean up unnecessary assets, restrict API endpoints, and optimize the WordPress backend/frontend environment.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Emojis', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Prevents WordPress emoji scripts and styles from loading on frontend and admin screens.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[disable_emojis]" value="1"
															<?php checked(1, $options['disable_emojis'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Comments Completely', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Turns off all front-end commenting/pings, closes comments, and hides Comments menus in the admin dashboard.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[disable_comments]" value="1"
															<?php checked(1, $options['disable_comments'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Restrict REST API', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Requires authentication for REST API endpoints. Non-logged-in guest API calls will receive a 401 Unauthorized status.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[disable_rest_api]" value="1"
															<?php checked(1, $options['disable_rest_api'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Feeds', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Stops serving RSS/Atom XML feeds, strips feed head links, and redirects guest feed calls back to the homepage.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[disable_feeds]" value="1"
															<?php checked(1, $options['disable_feeds'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Embeds', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Deregisters the wp-embed.min.js script, disables auto-discovery, and removes oEmbed REST/header links.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[disable_embeds]" value="1"
															<?php checked(1, $options['disable_embeds'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Remove Version Strings & Generator Tag', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Removes the generator meta tag from head and strips "?ver=" parameters from enqueued styles/scripts on front-end.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[disable_version_strings]"
															value="1" <?php checked(1, $options['disable_version_strings'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Front-End Dashicons', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Dequeues dashicons.min.css on front-end enqueues for non-logged-in guest visitors to optimize assets size.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[disable_front_dashicons]"
															value="1" <?php checked(1, $options['disable_front_dashicons'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Virtual Cron (WP-Cron)', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Disables the default pseudo-cron that runs on page loads. Requires setting up a real system-level cron job to execute scheduled events.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-disable-wp-cron-toggle" name="tka_wp_utils_options[disable_wp_cron]" value="1"
															<?php checked(1, $options['disable_wp_cron'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row nested-wp-cron-notice" style="<?php echo (!empty($options['disable_wp_cron'])) ? 'display: block;' : 'display: none;'; ?> width: 100%; border-bottom: none; padding-bottom: 0;">
												<div class="tka-setting-label" style="max-width: 100%; width: 100%;">
													<div class="tka-info-box" style="background-color: var(--tka-success-bg); border: 1px solid var(--tka-border); border-radius: var(--tka-radius); padding: 15px 20px; width: 100%; box-sizing: border-box;">
														<strong style="color: var(--tka-text-main); font-size: 14px; display: block; margin-bottom: 8px;">
															<span class="dashicons dashicons-info" style="color: var(--tka-primary); vertical-align: text-bottom; margin-right: 5px;"></span>
															<?php esc_html_e('System Cron Configuration Note', 'tka-wp-utils'); ?>
														</strong>
														<p style="margin: 0 0 12px 0; font-size: 13px; color: var(--tka-text-muted); line-height: 1.5;">
															<?php esc_html_e('Since WP-Cron is disabled, scheduled tasks (like backups, publishing scheduled posts, and background updates) will not run automatically. You MUST set up a real cron job in your hosting panel (cPanel, Plesk, SSH, etc.) to trigger this file at regular intervals (e.g., every 15 minutes).', 'tka-wp-utils'); ?>
														</p>
														<p style="margin: 0 0 8px 0; font-size: 13px; color: var(--tka-text-muted); font-weight: 600;">
															<?php esc_html_e('Recommended Cron Command:', 'tka-wp-utils'); ?>
														</p>
														<code class="tka-code-badge" style="display: block; font-size: 12px; padding: 10px 12px; background: var(--tka-bg-main); border: 1px solid var(--tka-border); border-radius: 6px; font-family: SFMono-Regular, Consolas, monospace; overflow-x: auto; word-break: break-all; color: var(--tka-text-main);">
															*/15 * * * * wget -q -O - <?php echo esc_url(site_url('wp-cron.php?doing_wp_cron')); ?> &gt;/dev/null 2&gt;&amp;1
														</code>
													</div>
												</div>
											</div>

											<!-- Heartbeat API Control -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Heartbeat API Control', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Control or disable the WordPress Heartbeat API, which performs AJAX requests in the background.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<select name="tka_wp_utils_options[heartbeat_control]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border); background: var(--tka-bg-card); color: var(--tka-text-main);">
														<option value="default" <?php selected('default', $options['heartbeat_control'] ?? 'default'); ?>><?php esc_html_e('Default (No Override)', 'tka-wp-utils'); ?></option>
														<option value="disable_everywhere" <?php selected('disable_everywhere', $options['heartbeat_control'] ?? 'default'); ?>><?php esc_html_e('Disable Everywhere', 'tka-wp-utils'); ?></option>
														<option value="disable_dashboard" <?php selected('disable_dashboard', $options['heartbeat_control'] ?? 'default'); ?>><?php esc_html_e('Disable on Dashboard', 'tka-wp-utils'); ?></option>
														<option value="allow_only_post_edit" <?php selected('allow_only_post_edit', $options['heartbeat_control'] ?? 'default'); ?>><?php esc_html_e('Allow only on Post Edit Screen', 'tka-wp-utils'); ?></option>
													</select>
												</div>
											</div>

											<!-- Heartbeat Frequency -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Heartbeat Frequency', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Specify the interval (in seconds) between Heartbeat API requests when active.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<select name="tka_wp_utils_options[heartbeat_frequency]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border); background: var(--tka-bg-card); color: var(--tka-text-main);">
														<?php
														$freqs = [15 => '15s', 30 => '30s', 60 => '60s (Default)', 90 => '90s', 120 => '120s'];
														foreach ($freqs as $val => $lbl) {
															echo '<option value="' . esc_attr($val) . '" ' . selected($val, intval($options['heartbeat_frequency'] ?? 60), false) . '>' . esc_html($lbl) . '</option>';
														}
														?>
													</select>
												</div>
											</div>

											<!-- Post Revisions Limit -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Post Revisions Limit', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Limit the number of revisions stored in the database for each post to prevent database bloat.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<select name="tka_wp_utils_options[revisions_limit]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border); background: var(--tka-bg-card); color: var(--tka-text-main);">
														<option value="-1" <?php selected(-1, intval($options['revisions_limit'] ?? -1)); ?>><?php esc_html_e('Unlimited (WordPress Default)', 'tka-wp-utils'); ?></option>
														<option value="0" <?php selected(0, intval($options['revisions_limit'] ?? -1)); ?>><?php esc_html_e('Disable Revisions', 'tka-wp-utils'); ?></option>
														<?php
														for ($i = 1; $i <= 10; $i++) {
															echo '<option value="' . esc_attr($i) . '" ' . selected($i, intval($options['revisions_limit'] ?? -1), false) . '>' . sprintf(_n('%d Revision', '%d Revisions', $i, 'tka-wp-utils'), $i) . '</option>';
														}
														?>
													</select>
												</div>
											</div>

											<!-- Autosave Interval -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Autosave Interval', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Set the frequency (in seconds) at which WordPress autosaves post changes while editing.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<select name="tka_wp_utils_options[autosave_interval]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border); background: var(--tka-bg-card); color: var(--tka-text-main);">
														<?php
														$intervals = [60 => '60s (Default)', 120 => '2 mins', 180 => '3 mins', 240 => '4 mins', 300 => '5 mins'];
														foreach ($intervals as $val => $lbl) {
															echo '<option value="' . esc_attr($val) . '" ' . selected($val, intval($options['autosave_interval'] ?? 60), false) . '>' . esc_html($lbl) . '</option>';
														}
														?>
													</select>
												</div>
											</div>
										</div>
									</section>

									<!-- CONTENT MANAGEMENT PANEL -->
									<section id="panel-content" class="tka-tab-panel">
										<h2><?php esc_html_e('Content Management', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Configure drag-and-drop manual ordering and one-click duplication for your posts and custom post types.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<!-- Draggable sorting toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Drag & Drop Sorting', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Enables sorting posts manually by dragging and dropping rows in the post lists tables.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-order-enabled-toggle"
															name="tka_wp_utils_options[order_enabled]" value="1" <?php checked(1, $options['order_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Order post types selection grid -->
											<div class="tka-setting-row nested-order-post-types"
												style="<?php echo (!empty($options['order_enabled'])) ? 'display: block;' : 'display: none;'; ?>">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Choose Post Types for Drag & Drop Sorting', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Select which post types can be manually ordered.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-checkbox-grid">
													<?php foreach ($public_post_types as $post_type): ?>
															<label class="tka-checkbox-item">
																<input type="checkbox" name="tka_wp_utils_options[order_post_types][]"
																	value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $options['order_post_types'] ?? [], true)); ?>>
																<span><?php echo esc_html($post_type->label); ?> <code
																		class="tka-code-badge"><?php echo esc_html($post_type->name); ?></code></span>
															</label>
													<?php endforeach; ?>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- Content duplication toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Content Duplication', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Adds a secure "Duplicate" link to row actions inside post list tables to clone any item to a new Draft.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-duplicate-enabled-toggle"
															name="tka_wp_utils_options[duplicate_enabled]" value="1" <?php checked(1, $options['duplicate_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Duplicate post types selection grid -->
											<div class="tka-setting-row nested-duplicate-post-types"
												style="<?php echo (!empty($options['duplicate_enabled'])) ? 'display: block;' : 'display: none;'; ?>">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Choose Post Types for Duplication', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Select which post types can be duplicated.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-checkbox-grid">
													<?php foreach ($public_post_types as $post_type): ?>
															<label class="tka-checkbox-item">
																<input type="checkbox" name="tka_wp_utils_options[duplicate_post_types][]"
																	value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $options['duplicate_post_types'] ?? [], true)); ?>>
																<span><?php echo esc_html($post_type->label); ?> <code
																		class="tka-code-badge"><?php echo esc_html($post_type->name); ?></code></span>
															</label>
													<?php endforeach; ?>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- Media Folders toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Media Folders', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Adds virtual folders and drag-and-drop file organization inside the WordPress Media Library grid view and selection modals.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-media-folders-enabled-toggle"
															name="tka_wp_utils_options[media_folders_enabled]" value="1" <?php checked(1, $options['media_folders_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>
									</section>

									<?php if (AdminInterface::isCurrentUserInstaller()): ?>
											<!-- ADMIN INTERFACE PANEL -->
											<section id="panel-admin-interface" class="tka-tab-panel">
												<h2><?php esc_html_e('Admin Interface Customization', 'tka-wp-utils'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('Selectively hide sidebar menu items, help screen options, and dashboard admin notices from other administrators. These do not affect your account.', 'tka-wp-utils'); ?>
												</p>

												<div class="tka-settings-card" style="margin-bottom: 20px;">
													<div class="tka-setting-row stack">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Assign Superadmins', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Select other administrators who should have full Superadmin privileges over this plugin (including accessing these settings).', 'tka-wp-utils'); ?></p>
														</div>
														<div class="tka-setting-control">
															<?php
															$installer_id = \TKA\WPUtils\Features\AdminInterface::getInstallerId();
															$all_admins = get_users(['role' => 'administrator']);
															$current_superadmins = $options['superadmin_users'] ?? [];
															
															if (empty($all_admins) || (count($all_admins) === 1 && $all_admins[0]->ID === $installer_id)) {
																echo '<p style="color: #64748b; font-style: italic;">' . esc_html__('No other administrators found on this site.', 'tka-wp-utils') . '</p>';
															} else {
																echo '<div style="display: flex; flex-direction: column; gap: 8px;">';
																foreach ($all_admins as $admin) {
																	if ($admin->ID === $installer_id) {
																		// Original installer is always superadmin
																		echo '<label style="color: #64748b; cursor: not-allowed;"><input type="checkbox" checked disabled> ' . esc_html($admin->user_login) . ' (Original Installer)</label>';
																	} else {
																		$checked = in_array($admin->ID, $current_superadmins, true) ? 'checked' : '';
																		echo '<label><input type="checkbox" name="tka_wp_utils_options[superadmin_users][]" value="' . esc_attr($admin->ID) . '" ' . $checked . '> ' . esc_html($admin->user_login) . ' (' . esc_html($admin->user_email) . ')</label>';
																	}
																}
																echo '</div>';
															}
															?>
														</div>
													</div>
												</div>

												<div class="tka-settings-card">
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Hide Help & Screen Options Tabs', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Removes the "Help" and "Screen Options" tabs from the top-right of admin pages for other administrators.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[hide_help_screen_options]"
																	value="1" <?php checked(1, $options['hide_help_screen_options'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Command Palette', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Disables the WordPress Command Palette (Cmd/Ctrl+K shortcut) for other administrators.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[disable_command_palette]" value="1"
																	<?php checked(1, $options['disable_command_palette'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Hide Dashboard Admin Notices', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Hides update warning notifications, notices, and nagging alerts in the admin panel for other administrators.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[hide_admin_notices]" value="1"
																	<?php checked(1, $options['hide_admin_notices'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Remove Footer Text & WP Version', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Removes the default "Thank you for creating with WordPress." and version number from the footer for other administrators.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[remove_footer_text]" value="1"
																	<?php checked(1, $options['remove_footer_text'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

													<!-- Clean Up Admin Bar Checkboxes -->
													<div class="tka-setting-row stack">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Clean Up Admin Bar', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Selectively remove nodes from the top administration bar for other administrators.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-checkbox-grid">
															<?php
															$admin_bar_items = [
																'wp-logo' => __('Remove WordPress Logo/Menu', 'tka-wp-utils'),
																'site-name' => __('Remove Home Icon & Site Name', 'tka-wp-utils'),
																'customize' => __('Remove Customize Menu', 'tka-wp-utils'),
																'updates' => __('Remove Updates Link/Counter', 'tka-wp-utils'),
																'comments' => __('Remove Comments Link/Counter', 'tka-wp-utils'),
																'new-content' => __('Remove New Content Menu', 'tka-wp-utils'),
																'howdy' => __('Remove "Howdy" Greeting', 'tka-wp-utils'),
															];
															foreach ($admin_bar_items as $item_id => $item_label): ?>
																	<label class="tka-checkbox-item">
																		<input type="checkbox" name="tka_wp_utils_options[admin_bar_cleanup][]"
																			value="<?php echo esc_attr($item_id); ?>" <?php checked(in_array($item_id, $options['admin_bar_cleanup'] ?? [], true)); ?>>
																		<span><?php echo esc_html($item_label); ?></span>
																	</label>
															<?php endforeach; ?>
														</div>
													</div>

													<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

													<!-- Disable Dashboard Widgets Checkboxes (Dynamic) -->
													<div class="tka-setting-row stack">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Dashboard Widgets', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Selectively disable widgets on the WordPress dashboard for other administrators.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-checkbox-grid">
															<?php
															// Merge widgets detected actively and widgets we have seen previously on the real dashboard
															global $wp_meta_boxes;
															if (!function_exists('wp_dashboard_setup')) {
																require_once ABSPATH . 'wp-admin/includes/dashboard.php';
															}
															do_action('wp_dashboard_setup');

															$available_widgets = get_option('tka_known_dashboard_widgets', []);
															
															if (!empty($wp_meta_boxes['dashboard'])) {
																foreach ($wp_meta_boxes['dashboard'] as $container_key => $containers) {
																	foreach ($containers as $priority_key => $priorities) {
																		foreach ($priorities as $widget_id => $widget) {
																			$available_widgets[$widget_id] = !empty($widget['title']) ? wp_strip_all_tags($widget['title']) : $widget_id;
																		}
																	}
																}
															}

															// Fallback list of core widgets
															$core_widgets = [
																'dashboard_right_now' => __('At a Glance', 'tka-wp-utils'),
																'dashboard_activity' => __('Activity', 'tka-wp-utils'),
																'dashboard_quick_press' => __('Quick Draft', 'tka-wp-utils'),
																'dashboard_primary' => __('WordPress Events and News', 'tka-wp-utils'),
																'dashboard_site_health' => __('Site Health Status', 'tka-wp-utils'),
															];
															
															$available_widgets = array_merge($core_widgets, $available_widgets);

															foreach ($available_widgets as $widget_id => $widget_label): ?>
																	<label class="tka-checkbox-item">
																		<input type="checkbox" name="tka_wp_utils_options[disabled_dashboard_widgets][]"
																			value="<?php echo esc_attr($widget_id); ?>" <?php checked(in_array($widget_id, $options['disabled_dashboard_widgets'] ?? [], true)); ?>>
																		<span><?php echo esc_html($widget_label); ?> <code
																				class="tka-code-badge"><?php echo esc_html($widget_id); ?></code></span>
																	</label>
															<?php endforeach; ?>
														</div>
													</div>
												</div>
											</section>

											<!-- DESIGN PANEL -->
											<section id="panel-design" class="tka-tab-panel">
												<h2><?php esc_html_e('Design Customization', 'tka-wp-utils'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('White-label the wp-login page and backend toolbar with custom brand identity.', 'tka-wp-utils'); ?>
												</p>

												<div class="tka-settings-card">
													<!-- 1. Custom Login Logo Upload -->
													<div class="tka-setting-row stack" style="width: 100%;">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('wp-login.php Page Logo', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Upload or select a custom logo to replace the default WordPress logo on the login page.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-image-upload-control" style="width: 100%; margin-top: 10px;">
															<input type="text" class="regular-text tka-logo-input"
																name="tka_wp_utils_options[login_logo]"
																value="<?php echo esc_url($options['login_logo'] ?? ''); ?>"
																style="width: 70%; display: inline-block; vertical-align: middle;">
															<button type="button" class="button tka-upload-btn"
																style="vertical-align: middle; margin-left: 10px;"><?php esc_html_e('Select Image', 'tka-wp-utils'); ?></button>
															<button type="button" class="button tka-remove-btn"
																style="vertical-align: middle; margin-left: 5px; color: var(--tka-danger);"><?php esc_html_e('Clear', 'tka-wp-utils'); ?></button>
															<div class="tka-logo-preview" style="margin-top: 10px;">
																<?php if (!empty($options['login_logo'])): ?>
																		<img src="<?php echo esc_url($options['login_logo']); ?>"
																			style="max-height: 80px; display: block; border-radius: 4px; border: 1px solid var(--tka-border);">
																<?php endif; ?>
															</div>
														</div>
													</div>

													<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

													<!-- 2. Custom Admin Bar Logo Upload -->
													<div class="tka-setting-row stack" style="width: 100%;">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Admin Bar Replacement Logo', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Upload or select a small custom logo (square/transparent icon works best) to replace the WordPress logo in the top admin toolbar.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-image-upload-control" style="width: 100%; margin-top: 10px;">
															<input type="text" class="regular-text tka-logo-input"
																name="tka_wp_utils_options[admin_logo]"
																value="<?php echo esc_url($options['admin_logo'] ?? ''); ?>"
																style="width: 70%; display: inline-block; vertical-align: middle;">
															<button type="button" class="button tka-upload-btn"
																style="vertical-align: middle; margin-left: 10px;"><?php esc_html_e('Select Image', 'tka-wp-utils'); ?></button>
															<button type="button" class="button tka-remove-btn"
																style="vertical-align: middle; margin-left: 5px; color: var(--tka-danger);"><?php esc_html_e('Clear', 'tka-wp-utils'); ?></button>
															<div class="tka-logo-preview" style="margin-top: 10px;">
																<?php if (!empty($options['admin_logo'])): ?>
																		<img src="<?php echo esc_url($options['admin_logo']); ?>"
																			style="max-height: 30px; display: block; border-radius: 4px; border: 1px solid var(--tka-border);">
																<?php endif; ?>
															</div>
														</div>
													</div>

													<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

													<!-- 3. Custom Login CSS Textarea -->
													<div class="tka-setting-row stack"
														style="width: 100%; border-bottom: none; padding-bottom: 0;">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Custom Login CSS', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Enter custom CSS rules to customize the login screen aesthetics (background color, buttons, typography). This CSS applies exclusively to the login screen.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<textarea name="tka_wp_utils_options[login_custom_css]" class="large-text code" rows="6"
															placeholder="body.login { background: #4f46e5; }&#10;.login h1 a { filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); }"
															style="width: 100%; font-family: SFMono-Regular, Consolas, monospace; font-size: 13px; margin-top: 10px; border-radius: 6px; padding: 10px; border-color: var(--tka-border);"><?php echo esc_textarea($options['login_custom_css'] ?? ''); ?></textarea>
													</div>
												</div>
											</section>
									<?php endif; ?>

									<?php if (class_exists('ACF')):
										$acfe_active = class_exists('ACFE') || defined('ACFE') || function_exists('acfe');
										?>
											<!-- ACF PANEL -->
											<section id="panel-acf" class="tka-tab-panel">
												<h2><?php esc_html_e('Advanced Custom Fields (ACF) Integration', 'tka-wp-utils'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('Optimize and secure your ACF setup for client-facing websites.', 'tka-wp-utils'); ?>
												</p>
 
												<div class="tka-settings-card">
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Hide Custom Fields Admin Menu', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Hides the "Custom Fields" sidebar menu item for all administrators except the plugin installer (developer). Keeps your ACF schemas safe from client edits.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[hide_acf_menu]" value="1"
																	<?php checked(1, $options['hide_acf_menu'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable [acf] Shortcode', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Disables front-end execution of the legacy `[acf]` shortcode. This is a vital security hardening practice to prevent unauthorized data exposure of raw database values.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[disable_acf_shortcode]"
																	value="1" <?php checked(1, $options['disable_acf_shortcode'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Auto-Inject Video Poster Field', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Automatically registers a "Video Poster Image" ACF field to all video attachments in the Media Library, allowing you to easily assign fallback cover images to MP4 uploads.', 'tka-wp-utils'); ?>
																<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Field Name:', 'tka-wp-utils'); ?> <code>video_poster_image</code></span>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[acf_video_poster]"
																	value="1" <?php checked(1, $options['acf_video_poster'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Theme-Independent Local JSON', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Saves and loads ACF field groups in a shared, theme-independent directory (`/wp-content/acf-json/`) instead of the active theme folder. Prevents accidental field group loss during theme updates or switches.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[acf_custom_json_path]"
																	value="1" <?php checked(1, $options['acf_custom_json_path'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row" <?php if ($acfe_active): ?>style="opacity: 0.6;"<?php endif; ?>>
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable Flexible Layout Copy & Paste', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Adds Copy/Paste buttons and selection checkboxes to each Flexible Content layout in the WordPress editor. Copied blocks can be bulk pasted across fields and posts.', 'tka-wp-utils'); ?>
																<?php if ($acfe_active): ?>
																		<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Note: This feature is managed by ACF Extended.', 'tka-wp-utils'); ?></span>
																<?php endif; ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[acf_copy_paste]" value="1"
																	<?php checked(1, $options['acf_copy_paste'] ?? 0); ?> 			<?php if ($acfe_active): ?>disabled<?php endif; ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row" <?php if ($acfe_active): ?>style="opacity: 0.6;"<?php endif; ?>>
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Flexible Layout Selection Modal', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Replaces the default ACF Flexible Content "Add Row" dropdown list with a beautiful, searchable modal overlay supporting visual previews and category filtering.', 'tka-wp-utils'); ?>
																<?php if ($acfe_active): ?>
																		<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Note: This feature is managed by ACF Extended.', 'tka-wp-utils'); ?></span>
																<?php endif; ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[acf_layout_modal]" value="1"
																	<?php checked(1, $options['acf_layout_modal'] ?? 0); ?> 			<?php if ($acfe_active): ?>disabled<?php endif; ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row" <?php if ($acfe_active): ?>style="opacity: 0.6;"<?php endif; ?>>
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable Flexible Layout Toggle (Visibility)', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Adds a visibility toggle button (eye icon) to each layout block in the ACF Field Group editor. Allows developers to disable individual layouts globally, hiding them from post editors and frontend output.', 'tka-wp-utils'); ?>
																<?php if ($acfe_active): ?>
																		<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Note: This feature is managed by ACF Extended.', 'tka-wp-utils'); ?></span>
																<?php endif; ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[acf_layout_toggle]" value="1"
																	<?php checked(1, $options['acf_layout_toggle'] ?? 0); ?> 			<?php if ($acfe_active): ?>disabled<?php endif; ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row" <?php if ($acfe_active): ?>style="opacity: 0.6;"<?php endif; ?>>
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable Layout Click-to-Rename Hijack', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Allows editors to rename flexible layout blocks directly by clicking on their title text inside the post editor, bypassing the need to open the action menu (three dots dropdown).', 'tka-wp-utils'); ?>
																<?php if ($acfe_active): ?>
																		<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Note: This feature is managed by ACF Extended.', 'tka-wp-utils'); ?></span>
																<?php endif; ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[acf_layout_rename]" value="1"
																	<?php checked(1, $options['acf_layout_rename'] ?? 0); ?> 			<?php if ($acfe_active): ?>disabled<?php endif; ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>


													<?php
													$acf_extensions = \TKA\WPUtils\Features\AcfManager::getAvailableExtensions();
													if (!empty($acf_extensions)):
													?>
														<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">
														<div class="tka-setting-row stack">
															<div class="tka-setting-label">
																<strong><?php esc_html_e('ACF Custom Field Extensions', 'tka-wp-utils'); ?></strong>
																<p><?php esc_html_e('Toggle dynamically discovered custom field extensions. Drop new PHP files in /includes/AcfExtensions/ to register them.', 'tka-wp-utils'); ?>
																</p>
															</div>
															<div class="tka-checkbox-grid">
																<?php foreach ($acf_extensions as $filename => $ext): ?>
																	<label class="tka-checkbox-item">
																		<input type="checkbox" name="tka_wp_utils_options[acf_extensions][]"
																			value="<?php echo esc_attr($filename); ?>" <?php checked(in_array($filename, $options['acf_extensions'] ?? [], true)); ?>>
																		<span>
																			<?php echo esc_html($ext['name']); ?>
																			<?php if (!empty($ext['description'])): ?>
																				<p style="margin: 4px 0 0; font-size: 12px; color: var(--tka-text-muted);"><?php echo esc_html($ext['description']); ?></p>
																			<?php endif; ?>
																		</span>
																	</label>
																<?php endforeach; ?>
															</div>
														</div>
													<?php endif; ?>
												</div>
											</section>
									<?php endif; ?>

									<!-- IMAGE OPTIMIZATION PANEL -->
									<section id="panel-images" class="tka-tab-panel">
										<h2><?php esc_html_e('Image Optimization & WebP Engine', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Convert newly uploaded JPEGs and PNGs to next-generation WebP format and compress assets in-place to save maximum disk storage and load websites lightning fast.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<!-- Global Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Image Optimization Features', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Master switch to enable enqueues, compression quality rules, and upload filters.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[image_optimization_enabled]" value="1"
															<?php checked(1, $options['image_optimization_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- WebP Conversion Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Convert Uploads to WebP', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Automatically converts uploaded JPEG and PNG image formats into highly efficient WebP files.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[webp_conversion_enabled]" value="1"
															<?php checked(1, $options['webp_conversion_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- WebP Keep Original Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Keep Original JPEGs / PNGs', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Retain the original files on the server folder after converting them to WebP. Turn off to delete original uploads and save absolute maximum disk space.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[webp_keep_original]" value="1"
															<?php checked(1, $options['webp_keep_original'] ?? 1); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Compress Original Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Optimize & Compress Original Uploads', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Compresses original full-size JPEGs and PNGs directly upon upload, rather than just compressing downscaled sub-sizes.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[compress_original_images]" value="1"
															<?php checked(1, $options['compress_original_images'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Strip Metadata Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Strip EXIF Image Metadata', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Automatically strips all camera profiles, copyright tags, and GPS coordinates from images upon upload to safeguard privacy and save size.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[strip_image_metadata]" value="1"
															<?php checked(1, $options['strip_image_metadata'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- Compression Quality Slider -->
											<div class="tka-setting-row stack" style="border-bottom: none; padding-bottom: 0;">
												<div class="tka-setting-label" style="max-width: 100%;">
													<strong><?php esc_html_e('Image Compression Quality', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Select target compression quality. Lower quality yields smaller files, while higher quality yields sharp detail (82% is recommended).', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div style="width: 100%; display: flex; align-items: center; gap: 15px; margin-top: 10px;">
													<input type="range" id="tka-image-quality-slider" name="tka_wp_utils_options[image_compression_quality]"
														min="50" max="100" value="<?php echo esc_attr($options['image_compression_quality'] ?? 82); ?>"
														style="flex-grow: 1; accent-color: var(--tka-primary); cursor: pointer;">
													<span id="tka-image-quality-display" style="font-weight: 700; color: var(--tka-primary); font-size: 16px; min-width: 45px; text-align: right;">
														<?php echo esc_html($options['image_compression_quality'] ?? 82); ?>%
													</span>
												</div>
											</div>
										</div>

										<!-- Server Compatibility Card -->
										<div class="tka-settings-card" style="margin-top: 20px; border-left: 4px solid var(--tka-primary);">
											<h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; color: var(--tka-text-main); text-transform: uppercase; letter-spacing: 0.5px;">
												<?php esc_html_e('Server Compatibility Check', 'tka-wp-utils'); ?>
											</h3>
											<ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
												<li style="display: flex; justify-content: space-between; align-items: center;">
													<span style="color: var(--tka-text-muted);"><?php esc_html_e('PHP GD WebP Support:', 'tka-wp-utils'); ?></span>
													<strong>
														<?php if (function_exists('imagewebp')): ?>
																<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Enabled', 'tka-wp-utils'); ?></span>
														<?php else: ?>
																<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Disabled', 'tka-wp-utils'); ?></span>
														<?php endif; ?>
													</strong>
												</li>
												<li style="display: flex; justify-content: space-between; align-items: center;">
													<span style="color: var(--tka-text-muted);"><?php esc_html_e('PHP Imagick WebP Support:', 'tka-wp-utils'); ?></span>
													<strong>
														<?php
														$imagick_ok = false;
														if (class_exists('\Imagick')) {
															try {
																$formats = \Imagick::queryFormats('WEBP');
																$imagick_ok = !empty($formats);
															} catch (\Throwable $e) {
																$imagick_ok = false;
															}
														}
														if ($imagick_ok): ?>
																<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Enabled', 'tka-wp-utils'); ?></span>
														<?php else: ?>
																<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Disabled', 'tka-wp-utils'); ?></span>
														<?php endif; ?>
													</strong>
												</li>
												<li style="display: flex; justify-content: space-between; align-items: center;">
													<span style="color: var(--tka-text-muted);"><?php esc_html_e('WordPress Engine Native WebP Upload:', 'tka-wp-utils'); ?></span>
													<strong>
														<?php if (wp_image_editor_supports(array('mime_type' => 'image/webp'))): ?>
																<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Supported', 'tka-wp-utils'); ?></span>
														<?php else: ?>
																<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Unsupported', 'tka-wp-utils'); ?></span>
														<?php endif; ?>
													</strong>
												</li>
											</ul>
										</div>
									</section>

									<?php if (class_exists('GFCommon')): ?>
											<!-- GRAVITY FORMS PANEL -->
											<section id="panel-gravity-forms" class="tka-tab-panel">
												<h2><?php esc_html_e('Gravity Forms Integrations & Enhancements', 'tka-wp-utils'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('Customize and optimize your Gravity Forms workflows. Clean up markup, disable default styling, and prevent double form submissions.', 'tka-wp-utils'); ?>
												</p>

												<div class="tka-settings-card">
													<!-- Disable GF CSS Toggle -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Default CSS completely', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Blocks all built-in Gravity Forms styles and stylesheets from loading on the frontend. Highly recommended when building custom themes or using Tailwind/bootstrap styles.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[gf_disable_css]" value="1"
																	<?php checked(1, $options['gf_disable_css'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Change Submit Input to Button Toggle -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Convert Submit Input to Button', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Converts standard <input type="submit"> tags into modern <button type="submit"> elements, allowing for much better flex/grid layout control and pseudo-elements.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[gf_submit_button_to_button]" value="1"
																	<?php checked(1, $options['gf_submit_button_to_button'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Change Submit Button Text on Click Toggle -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable Button Submit Loading Text', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Temporarily changes the button text to a loading feedback string (e.g. "Sending...") upon form submit to prevent multiple clicks and double submissions.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" id="tka-gf-text-change-toggle" name="tka_wp_utils_options[gf_submit_button_text_change]" value="1"
																	<?php checked(1, $options['gf_submit_button_text_change'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Loading text input field -->
													<div class="tka-setting-row nested-gf-loading-text"
														style="<?php echo (!empty($options['gf_submit_button_text_change'])) ? 'display: block;' : 'display: none;'; ?>">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Custom Submit Loading Text', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Specify the custom text to display during form submission.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<input type="text" name="tka_wp_utils_options[gf_submit_button_loading_text]"
																value="<?php echo esc_attr($options['gf_submit_button_loading_text'] ?? 'Sending...'); ?>"
																class="regular-text" style="border-radius: 8px; padding: 8px 12px; border-color: var(--tka-border);">
														</div>
													</div>
												</div>
											</section>
									<?php endif; ?>

									<?php if (class_exists('WooCommerce')): ?>
											<!-- WOOCOMMERCE PANEL -->
											<section id="panel-woocommerce" class="tka-tab-panel">
												<h2><?php esc_html_e('WooCommerce Speed & Bloat Settings', 'tka-wp-utils'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('Optimize your WooCommerce store frontend load speed and declutter the admin dashboard.', 'tka-wp-utils'); ?>
												</p>

												<div class="tka-settings-card">
													<!-- Disable scripts/styles non-shop pages -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Scripts & Styles on Non-Shop Pages', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Dequeues WooCommerce frontend styles and scripts on non-shop pages like homepage, blog posts, and custom pages to optimize asset payload size.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_disable_scripts_non_wc]" value="1"
																	<?php checked(1, $options['wc_disable_scripts_non_wc'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Cart Fragments AJAX mode -->
													<div class="tka-setting-row stack">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Cart Fragments AJAX', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Choose how to restrict the AJAX cart fragments request on the frontend.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control-radios">
															<label class="tka-radio-card">
																<input type="radio" name="tka_wp_utils_options[wc_disable_cart_fragments]" value="none"
																	<?php checked('none', $options['wc_disable_cart_fragments'] ?? 'none'); ?>>
																<div class="radio-card-content">
																	<strong><?php esc_html_e('Keep Active', 'tka-wp-utils'); ?></strong>
																	<span><?php esc_html_e('Default WooCommerce cart updating behaviour.', 'tka-wp-utils'); ?></span>
																</div>
															</label>

															<label class="tka-radio-card">
																<input type="radio" name="tka_wp_utils_options[wc_disable_cart_fragments]" value="all"
																	<?php checked('all', $options['wc_disable_cart_fragments'] ?? 'none'); ?>>
																<div class="radio-card-content">
																	<strong><?php esc_html_e('Disable Completely', 'tka-wp-utils'); ?></strong>
																	<span><?php esc_html_e('Globally block AJAX cart fragments (maximum performance).', 'tka-wp-utils'); ?></span>
																</div>
															</label>

															<label class="tka-radio-card">
																<input type="radio" name="tka_wp_utils_options[wc_disable_cart_fragments]" value="non_shop"
																	<?php checked('non_shop', $options['wc_disable_cart_fragments'] ?? 'none'); ?>>
																<div class="radio-card-content">
																	<strong><?php esc_html_e('Disable on Non-Shop Pages', 'tka-wp-utils'); ?></strong>
																	<span><?php esc_html_e('Keep fragments enabled on shop pages/checkout; block them everywhere else.', 'tka-wp-utils'); ?></span>
																</div>
															</label>
														</div>
													</div>

													<!-- Disable block styles -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Gutenberg Block Styles', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Blocks WooCommerce built-in blocks layout styles from loading on the frontend. Recommended if your theme does not use WooCommerce blocks.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_disable_block_styles]" value="1"
																	<?php checked(1, $options['wc_disable_block_styles'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Disable password strength meter -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Password Strength Meter JS', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Dequeues zxcvbn.min.js and the password strength meter on checkout/my-account pages to save up to 400KB of page weight.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_disable_password_meter]" value="1"
																	<?php checked(1, $options['wc_disable_password_meter'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Clean Admin UI -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Clean WooCommerce Admin UI & Nags', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Hides the WooCommerce marketing menu tab, removes status widgets from the dashboard, and disables marketplace suggestions.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_clean_admin_ui]" value="1"
																	<?php checked(1, $options['wc_clean_admin_ui'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
												</div>

												<div class="tka-settings-card" style="margin-top: 20px;">
													<h3><?php esc_html_e('WooCommerce Helpers & Extras', 'tka-wp-utils'); ?></h3>
													<p class="card-desc" style="margin-bottom: 20px; color: var(--tka-text-muted);">
														<?php esc_html_e('Configure helpful tweaks and extra features for your WooCommerce shop.', 'tka-wp-utils'); ?>
													</p>

													<!-- Buy Now Button -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable "Buy Now" Button', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Adds a direct checkout button on single product pages, bypassing the cart page.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_buy_now_button]" value="1"
																	<?php checked(1, $options['wc_buy_now_button'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Redirect SKU to Product -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Redirect SKU to Product Page', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Automatically redirects users typing or requesting a product SKU in the URL (e.g. /your-sku/) to that product page.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_redirect_sku]" value="1"
																	<?php checked(1, $options['wc_redirect_sku'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Remove add-to-cart from URL -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Remove "add-to-cart" From URL', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Redirects the user back to the referrer page after adding a product to cart, removing the add-to-cart query string from the URL.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_remove_add_to_cart_from_url]" value="1"
																	<?php checked(1, $options['wc_remove_add_to_cart_from_url'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Hide View Cart on Shop -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Hide "View Cart" Link on Shop', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Hides the AJAX-injected "View Cart" secondary link after adding a product to cart on the shop archive pages.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_hide_view_cart_shop]" value="1"
																	<?php checked(1, $options['wc_hide_view_cart_shop'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Plus/Minus quantity buttons -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Plus/Minus Quantity Buttons', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Injects interactive "-" and "+" buttons around the product quantity inputs on single product pages.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_plus_minus_quantity]" value="1"
																	<?php checked(1, $options['wc_plus_minus_quantity'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Quantity dropdown -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Quantity Dropdown Select', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Converts the standard numeric input field for quantity into a select dropdown (max value limit 20).', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wc_quantity_dropdown]" value="1"
																	<?php checked(1, $options['wc_quantity_dropdown'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
												</div>
											</section>
									<?php endif; ?>

									<!-- MAINTENANCE PANEL -->
									<section id="panel-maintenance" class="tka-tab-panel">
										<h2><?php esc_html_e('Maintenance Mode Settings', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Take your site offline temporarily for scheduled maintenance while showing a beautiful message to visitors.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Maintenance Mode', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('When active, non-logged-in visitors will be redirected to a temporary maintenance page with a 503 Service Unavailable status.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[maintenance_enabled]" value="1"
															<?php checked(1, $options['maintenance_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Maintenance Page Title', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Enter the title to display on the maintenance screen.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<input type="text" name="tka_wp_utils_options[maintenance_title]"
														value="<?php echo esc_attr($options['maintenance_title'] ?? __('Under Maintenance', 'tka-wp-utils')); ?>"
														class="regular-text" style="width: 100%; max-width: 400px; border-radius: 8px; padding: 8px 12px; border-color: var(--tka-border);">
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Maintenance Message', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Enter a descriptive message for visitors.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<textarea name="tka_wp_utils_options[maintenance_message]" rows="5" class="large-text"
														style="width: 100%; max-width: 400px; border-radius: 6px; padding: 8px 12px; border-color: var(--tka-border);"><?php echo esc_textarea($options['maintenance_message'] ?? 'Our website is currently undergoing scheduled maintenance. We will be back shortly. Thank you for your patience!'); ?></textarea>
												</div>
											</div>

											<!-- Logo Upload -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Maintenance Page Logo', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Upload or select a custom logo to display on the maintenance screen.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<div class="tka-image-upload-control">
														<input type="text" name="tka_wp_utils_options[maintenance_logo]"
															class="tka-logo-input" value="<?php echo esc_url($options['maintenance_logo'] ?? ''); ?>" style="display:none;">
														<div class="tka-logo-preview" style="margin-bottom: 10px;">
															<?php if (!empty($options['maintenance_logo'])): ?>
																	<img src="<?php echo esc_url($options['maintenance_logo']); ?>"
																		style="max-height: 80px; display: block; border-radius: 4px; border: 1px solid var(--tka-border);">
															<?php endif; ?>
														</div>
														<button class="button tka-upload-btn"><?php esc_html_e('Choose Image', 'tka-wp-utils'); ?></button>
														<button class="button tka-remove-btn"><?php esc_html_e('Remove', 'tka-wp-utils'); ?></button>
													</div>
												</div>
											</div>

											<!-- Background Image Upload -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Maintenance Page Background', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Upload or select a background image for the maintenance screen.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<div class="tka-image-upload-control">
														<input type="text" name="tka_wp_utils_options[maintenance_background]"
															class="tka-logo-input" value="<?php echo esc_url($options['maintenance_background'] ?? ''); ?>" style="display:none;">
														<div class="tka-logo-preview" style="margin-bottom: 10px;">
															<?php if (!empty($options['maintenance_background'])): ?>
																	<img src="<?php echo esc_url($options['maintenance_background']); ?>"
																		style="max-height: 80px; display: block; border-radius: 4px; border: 1px solid var(--tka-border);">
															<?php endif; ?>
														</div>
														<button class="button tka-upload-btn"><?php esc_html_e('Choose Image', 'tka-wp-utils'); ?></button>
														<button class="button tka-remove-btn"><?php esc_html_e('Remove', 'tka-wp-utils'); ?></button>
													</div>
												</div>
											</div>
										</div>
									</section>

									<!-- PAGE TRANSITIONS PANEL -->
									<section id="panel-transitions" class="tka-tab-panel">
										<h2><?php esc_html_e('Page Transitions (View Transitions)', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Enable smooth, modern cross-document view transitions for seamless frontend navigation.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<!-- Global Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Page Transitions', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Master switch to enable enqueues, scripts, and transition styling on the frontend.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-page-transitions-enabled-toggle" name="tka_wp_utils_options[page_transitions_enabled]" value="1"
															<?php checked(1, $options['page_transitions_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Nested settings container -->
											<div class="nested-page-transitions-settings" style="<?php echo (!empty($options['page_transitions_enabled'])) ? 'display: block;' : 'display: none;'; ?>">
												<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

												<!-- Override Theme Support -->
												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Override Theme Configuration', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('Force-apply the selectors and settings below, overriding any defaults provided by the theme.', 'tka-wp-utils'); ?>
														</p>
													</div>
													<div class="tka-setting-control">
														<label class="tka-switch">
															<input type="checkbox" name="tka_wp_utils_options[page_transitions_override_theme]" value="1"
																<?php checked(1, $options['page_transitions_override_theme'] ?? 0); ?>>
															<span class="tka-slider"></span>
														</label>
													</div>
												</div>

												<!-- Default Transition Animation Select -->
												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Default Transition Animation', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('Choose the default animation used when no custom rules match.', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-setting-control">
														<select name="tka_wp_utils_options[page_transitions_default_animation]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
															<?php
															$animations_labels = [
																'fade' => __('Fade (default)', 'tka-wp-utils'),
																'slide-from-right' => __('Slide (from right)', 'tka-wp-utils'),
																'slide-from-left' => __('Slide (from left)', 'tka-wp-utils'),
																'slide-from-bottom' => __('Slide (from bottom)', 'tka-wp-utils'),
																'slide-from-top' => __('Slide (from top)', 'tka-wp-utils'),
																'swipe-from-right' => __('Swipe (from right)', 'tka-wp-utils'),
																'swipe-from-left' => __('Swipe (from left)', 'tka-wp-utils'),
																'swipe-from-bottom' => __('Swipe (from bottom)', 'tka-wp-utils'),
																'swipe-from-top' => __('Swipe (from top)', 'tka-wp-utils'),
																'wipe-from-right' => __('Wipe (from right)', 'tka-wp-utils'),
																'wipe-from-left' => __('Wipe (from left)', 'tka-wp-utils'),
																'wipe-from-bottom' => __('Wipe (from bottom)', 'tka-wp-utils'),
																'wipe-from-top' => __('Wipe (from top)', 'tka-wp-utils'),
															];
															foreach ($animations_labels as $key => $label) {
																echo '<option value="' . esc_attr($key) . '" ' . selected($key, $options['page_transitions_default_animation'] ?? 'fade', false) . '>' . esc_html($label) . '</option>';
															}
															?>
														</select>
													</div>
												</div>

												<!-- Animation Duration -->
												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Transition Animation Duration', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('Control the duration of the transition in milliseconds (default: 400).', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="number" name="tka_wp_utils_options[page_transitions_default_animation_duration]"
															value="<?php echo esc_attr($options['page_transitions_default_animation_duration'] ?? 400); ?>"
															class="regular-text" style="width: 100px; border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<!-- Selector Fields -->
												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Header Selector', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('CSS selector for the global header element.', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_wp_utils_options[page_transitions_header_selector]"
															value="<?php echo esc_attr($options['page_transitions_header_selector'] ?? 'header'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Main Selector', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('CSS selector for the global main element.', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_wp_utils_options[page_transitions_main_selector]"
															value="<?php echo esc_attr($options['page_transitions_main_selector'] ?? 'main'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Post Title Selector', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('CSS selector for the post title element.', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_wp_utils_options[page_transitions_post_title_selector]"
															value="<?php echo esc_attr($options['page_transitions_post_title_selector'] ?? '.wp-block-post-title, .entry-title'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Post Thumbnail Selector', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('CSS selector for the post thumbnail image.', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_wp_utils_options[page_transitions_post_thumbnail_selector]"
															value="<?php echo esc_attr($options['page_transitions_post_thumbnail_selector'] ?? '.wp-post-image'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Post Content Selector', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('CSS selector for the post content block.', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_wp_utils_options[page_transitions_post_content_selector]"
															value="<?php echo esc_attr($options['page_transitions_post_content_selector'] ?? '.wp-block-post-content, .entry-content'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('WP Admin Transitions', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('Enable transitions inside the WordPress administration dashboard.', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-setting-control">
														<label class="tka-switch">
															<input type="checkbox" name="tka_wp_utils_options[page_transitions_enable_admin]" value="1"
																<?php checked(1, $options['page_transitions_enable_admin'] ?? 0); ?>>
															<span class="tka-slider"></span>
														</label>
													</div>
												</div>

												<!-- CUSTOM TRANSITION RULES -->
												<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">
												<div class="tka-setting-row stack" style="width: 100%;">
													<div class="tka-setting-label" style="max-width: 100%;">
														<strong><?php esc_html_e('Custom Navigation Rules', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('Define target animations for specific source and destination navigations.', 'tka-wp-utils'); ?></p>
													</div>
													<div class="tka-transitions-rules-manager" style="width: 100%; margin-top: 10px;">
														<div class="tka-rules-header-row" style="display: flex; gap: 15px; padding: 8px 10px; background: var(--tka-bg-main); font-weight: 700; font-size: 12px; text-transform: uppercase; color: var(--tka-text-muted); border-radius: 6px; border: 1px solid var(--tka-border); margin-bottom: 10px;">
															<div style="flex: 2;"><?php esc_html_e('From Page Type / URL Pattern', 'tka-wp-utils'); ?></div>
															<div style="flex: 2;"><?php esc_html_e('To Page Type / URL Pattern', 'tka-wp-utils'); ?></div>
															<div style="flex: 1.5;"><?php esc_html_e('Animation', 'tka-wp-utils'); ?></div>
															<div style="flex: 1.5;"><?php esc_html_e('Custom HTML Class', 'tka-wp-utils'); ?></div>
															<div style="width: 40px;"></div>
														</div>
														<div id="tka-transitions-rules-list" style="display: flex; flex-direction: column; gap: 10px;">
															<?php
															$rules = $options['page_transitions_rules'] ?? [];
															$page_types = [
																'any' => __('Any Page', 'tka-wp-utils'),
																'home' => __('Homepage', 'tka-wp-utils'),
																'archive' => __('Archive/Category', 'tka-wp-utils'),
																'single_post' => __('Single Post', 'tka-wp-utils'),
																'single_page' => __('Single Page', 'tka-wp-utils'),
																'custom_url' => __('Custom URL Pattern...', 'tka-wp-utils'),
															];

															$rule_index = 0;
															foreach ($rules as $rule):
																$from_type = $rule['from_type'] ?? 'any';
																$to_type = $rule['to_type'] ?? 'any';
																$rule_anim = $rule['animation'] ?? 'fade';
																?>
																	<div class="tka-rule-row-item" style="display: flex; gap: 15px; align-items: center; padding: 10px; border: 1px solid var(--tka-border); border-radius: 8px; background: #ffffff; box-shadow: var(--tka-shadow);">
																		<!-- FROM PAGE -->
																		<div style="flex: 2; display: flex; flex-direction: column; gap: 5px;">
																			<select name="tka_wp_utils_options[page_transitions_rules][<?php echo intval($rule_index); ?>][from_type]" class="tka-rule-from-select" style="width: 100%; padding: 5px; border-radius: 6px;">
																				<?php foreach ($page_types as $val => $lbl): ?>
																						<option value="<?php echo esc_attr($val); ?>" <?php selected($from_type, $val); ?>><?php echo esc_html($lbl); ?></option>
																				<?php endforeach; ?>
																			</select>
																			<input type="text" name="tka_wp_utils_options[page_transitions_rules][<?php echo intval($rule_index); ?>][from_url]" value="<?php echo esc_attr($rule['from_url'] ?? ''); ?>" placeholder="<?php esc_attr_e('/blog/*', 'tka-wp-utils'); ?>" class="tka-rule-from-url" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); font-family: monospace; display: <?php echo ($from_type === 'custom_url') ? 'block' : 'none'; ?>;">
																		</div>

																		<!-- TO PAGE -->
																		<div style="flex: 2; display: flex; flex-direction: column; gap: 5px;">
																			<select name="tka_wp_utils_options[page_transitions_rules][<?php echo intval($rule_index); ?>][to_type]" class="tka-rule-to-select" style="width: 100%; padding: 5px; border-radius: 6px;">
																				<?php foreach ($page_types as $val => $lbl): ?>
																						<option value="<?php echo esc_attr($val); ?>" <?php selected($to_type, $val); ?>><?php echo esc_html($lbl); ?></option>
																				<?php endforeach; ?>
																			</select>
																			<input type="text" name="tka_wp_utils_options[page_transitions_rules][<?php echo intval($rule_index); ?>][to_url]" value="<?php echo esc_attr($rule['to_url'] ?? ''); ?>" placeholder="<?php esc_attr_e('/shop/*', 'tka-wp-utils'); ?>" class="tka-rule-to-url" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); font-family: monospace; display: <?php echo ($to_type === 'custom_url') ? 'block' : 'none'; ?>;">
																		</div>

																		<!-- ANIMATION -->
																		<div style="flex: 1.5;">
																			<select name="tka_wp_utils_options[page_transitions_rules][<?php echo intval($rule_index); ?>][animation]" class="tka-rule-anim-select" style="width: 100%; padding: 5px; border-radius: 6px;">
																				<?php
																				$all_anims = array_merge(array_keys($animations_labels), ['custom']);
																				foreach ($all_anims as $anim):
																					$lbl = ($anim === 'custom') ? __('Custom CSS Class...', 'tka-wp-utils') : ($animations_labels[$anim] ?? $anim);
																					?>
																						<option value="<?php echo esc_attr($anim); ?>" <?php selected($rule_anim, $anim); ?>><?php echo esc_html($lbl); ?></option>
																				<?php endforeach; ?>
																			</select>
																		</div>

																		<!-- CUSTOM CLASS -->
																		<div style="flex: 1.5;">
																			<input type="text" name="tka_wp_utils_options[page_transitions_rules][<?php echo intval($rule_index); ?>][custom_class]" value="<?php echo esc_attr($rule['custom_class'] ?? ''); ?>" placeholder="<?php esc_attr_e('tka-transition-zoom', 'tka-wp-utils'); ?>" class="tka-rule-class-input" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); display: <?php echo ($rule_anim === 'custom') ? 'block' : 'none'; ?>;">
																		</div>

																		<!-- DELETE BUTTON -->
																		<button type="button" class="button tka-rule-delete-btn" style="color: var(--tka-danger); border-color: rgba(239, 68, 68, 0.2); padding: 5px 8px; border-radius: 6px;" title="<?php esc_attr_e('Delete rule', 'tka-wp-utils'); ?>">
																			<span class="dashicons dashicons-trash" style="vertical-align: middle; margin: 0;"></span>
																		</button>
																	</div>
																<?php
																$rule_index++;
															endforeach; ?>
														</div>
														<button type="button" id="tka-add-rule-btn" class="button" style="margin-top: 15px; border-color: var(--tka-primary); color: var(--tka-primary); background: rgba(79, 70, 229, 0.02); padding: 5px 15px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
															<span class="dashicons dashicons-plus" style="font-size:16px; width:16px; height:16px; margin:0; display:inline-block; vertical-align:middle;"></span>
															<?php esc_html_e('Add Transition Rule', 'tka-wp-utils'); ?>
														</button>
													</div>
												</div>

												<!-- CUSTOM CSS EDITOR -->
												<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">
												<div class="tka-setting-row stack" style="width: 100%;">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Custom CSS Transitions Stylesheet', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('Write custom view transition styles and keyframes. Rules will be printed inside a <style> block on the page.', 'tka-wp-utils'); ?>
														</p>
													</div>
													<textarea name="tka_wp_utils_options[page_transitions_custom_css]" class="large-text code" rows="8"
														placeholder="html.tka-transition-custom-zoom::view-transition-old(root) {&#10;    animation: zoom-out 0.4s ease;&#10;}&n;html.tka-transition-custom-zoom::view-transition-new(root) {&#10;    animation: zoom-in 0.4s ease;&#10;}"
														style="width: 100%; font-family: SFMono-Regular, Consolas, monospace; font-size: 13px; margin-top: 10px; border-radius: 6px; padding: 12px; border-color: var(--tka-border);"><?php echo esc_textarea($options['page_transitions_custom_css'] ?? ''); ?></textarea>
												</div>
											</div>
										</div>
									</section>

									<?php if (class_exists('SitePress') || defined('ICL_SITEPRESS_VERSION')): ?>
										<!-- WPML OPTIMIZATION PANEL -->
										<section id="panel-wpml-opt" class="tka-tab-panel">
											<h2><?php esc_html_e('WPML Performance Optimization', 'tka-wp-utils'); ?></h2>
											<p class="section-desc">
												<?php esc_html_e('Optimize performance and reduce database overhead caused by WPML queries and filters.', 'tka-wp-utils'); ?>
											</p>

											<div class="tka-settings-card">
												<!-- Global Toggle -->
												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Enable WPML Optimization', 'tka-wp-utils'); ?></strong>
														<p><?php esc_html_e('Master switch to enable custom performance filters for WPML query processes.', 'tka-wp-utils'); ?>
														</p>
													</div>
													<div class="tka-setting-control">
														<label class="tka-switch">
															<input type="checkbox" id="tka-wpml-optimization-enabled-toggle" name="tka_wp_utils_options[wpml_optimization_enabled]" value="1"
																<?php checked(1, $options['wpml_optimization_enabled'] ?? 0); ?>>
															<span class="tka-slider"></span>
														</label>
													</div>
												</div>

												<div class="nested-wpml-optimization-settings" style="<?php echo (!empty($options['wpml_optimization_enabled'])) ? 'display: block;' : 'display: none;'; ?>">
													<!-- Disable theme adjustments -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Theme ID Adjustments', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Bypasses the "Adjust IDs for multilingual functionality" runtime translations. Highly recommended for themes to prevent excessive database select queries on page loads.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wpml_disable_adjust_ids]" value="1"
																	<?php checked(1, $options['wpml_disable_adjust_ids'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Suppress canonical redirects for AJAX/REST -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Suppress Canonical Redirects for AJAX & REST', 'tka-wp-utils'); ?></strong>
															<p><?php esc_html_e('Bypasses WPML\'s URL canonical redirection validations during background AJAX transactions and REST API requests, optimizing backend processing speed.', 'tka-wp-utils'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_wp_utils_options[wpml_disable_canonical_redirects_ajax]" value="1"
																	<?php checked(1, $options['wpml_disable_canonical_redirects_ajax'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
												</div>
											</div>
										</section>
									<?php endif; ?>

									<!-- HTACCESS PANEL -->
									<section id="panel-htaccess" class="tka-tab-panel">
										<h2><?php esc_html_e('.htaccess Control & Hardening', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Optimize and harden your Apache/LiteSpeed server configuration directly from WordPress.', 'tka-wp-utils'); ?>
										</p>

										<!-- Server Status Card -->
										<div class="tka-settings-card" style="background: var(--tka-bg-main); border-color: var(--tka-border);">
											<h3 style="margin-top: 0; margin-bottom: 15px; font-size: 16px; color: var(--tka-text-main);">
												<span class="dashicons dashicons-info" style="color: var(--tka-primary); vertical-align: text-bottom; margin-right: 5px;"></span>
												<?php esc_html_e('Server & Write Status', 'tka-wp-utils'); ?>
											</h3>
											<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
												<div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid var(--tka-border);">
													<span style="font-size: 12px; color: var(--tka-text-muted); display: block; margin-bottom: 4px;"><?php esc_html_e('Web Server', 'tka-wp-utils'); ?></span>
													<strong style="font-size: 14px; color: var(--tka-text-main);">
														<?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?>
														<?php if (\TKA\WPUtils\Features\HtaccessManager::isApacheOrLiteSpeed()): ?>
															<span style="color: var(--tka-success); font-size: 12px; font-weight: normal; margin-left: 5px;">(<?php esc_html_e('Supported', 'tka-wp-utils'); ?>)</span>
														<?php else: ?>
															<span style="color: var(--tka-danger); font-size: 12px; font-weight: normal; margin-left: 5px;">(<?php esc_html_e('Rules will not run', 'tka-wp-utils'); ?>)</span>
														<?php endif; ?>
													</strong>
												</div>

												<div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid var(--tka-border);">
													<span style="font-size: 12px; color: var(--tka-text-muted); display: block; margin-bottom: 4px;"><?php esc_html_e('Root .htaccess', 'tka-wp-utils'); ?></span>
													<strong style="font-size: 14px; color: var(--tka-text-main);">
														<?php if (\TKA\WPUtils\Features\HtaccessManager::isRootHtaccessWritable()): ?>
															<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="vertical-align: text-bottom; margin-right: 2px;"></span><?php esc_html_e('Writable', 'tka-wp-utils'); ?></span>
														<?php else: ?>
															<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="vertical-align: text-bottom; margin-right: 2px;"></span><?php esc_html_e('Read-Only / Protected', 'tka-wp-utils'); ?></span>
														<?php endif; ?>
													</strong>
												</div>

												<div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid var(--tka-border);">
													<span style="font-size: 12px; color: var(--tka-text-muted); display: block; margin-bottom: 4px;"><?php esc_html_e('Uploads Directory', 'tka-wp-utils'); ?></span>
													<strong style="font-size: 14px; color: var(--tka-text-main);">
														<?php if (\TKA\WPUtils\Features\HtaccessManager::isUploadsHtaccessWritable()): ?>
															<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="vertical-align: text-bottom; margin-right: 2px;"></span><?php esc_html_e('Writable', 'tka-wp-utils'); ?></span>
														<?php else: ?>
															<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="vertical-align: text-bottom; margin-right: 2px;"></span><?php esc_html_e('Protected', 'tka-wp-utils'); ?></span>
														<?php endif; ?>
													</strong>
												</div>
											</div>
										</div>

										<div class="tka-settings-card">
											<!-- Security & Hardening -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Security Hardening', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Protects wp-config.php, user.ini, and .htaccess itself from HTTP access, disables directory indexes, and blocks common sensitive development/configuration files.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[htaccess_security]" value="1"
															<?php checked(1, $options['htaccess_security'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Author Enumeration -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Prevent Author Enumeration Scans', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Blocks username scans through URL queries like /?author=N by returning a 403 Forbidden status code.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[htaccess_prevent_author_scan]" value="1"
															<?php checked(1, $options['htaccess_prevent_author_scan'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Performance / Leverage Caching & Gzip -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Caching & Gzip Compression', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Enables Gzip mod_deflate compression, Cache-Control headers, Keep-Alive, and Expires headers for browser caching (max cache times increased to 1 year for static assets).', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[htaccess_performance]" value="1"
															<?php checked(1, $options['htaccess_performance'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- CORS Headers -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Set CORS Headers for Static Assets', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Sets Access-Control-Allow-Origin "*" headers for web fonts, CSS, JS, and images to prevent loading issues across multiple domains or CDNs.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[htaccess_cors]" value="1"
															<?php checked(1, $options['htaccess_cors'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Block PHP in uploads directory -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Block PHP Execution in Uploads', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Creates a secondary .htaccess file inside the uploads directory that blocks all direct executions of PHP scripts. Highly recommended for security.', 'tka-wp-utils'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[htaccess_uploads_prevent_php]" value="1"
															<?php checked(1, $options['htaccess_uploads_prevent_php'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>

										<!-- Preview Section -->
										<div class="tka-settings-card">
											<h3 style="margin-top: 0; font-size: 16px; color: var(--tka-text-main);"><?php esc_html_e('Generated .htaccess Rules Preview', 'tka-wp-utils'); ?></h3>
											<p style="font-size: 13px; color: var(--tka-text-muted); margin-bottom: 15px;">
												<?php esc_html_e('Below is a real-time preview of the rules being injected into your configuration files based on the toggles selected above.', 'tka-wp-utils'); ?>
											</p>

											<?php
											$preview_mgr = new \TKA\WPUtils\Features\HtaccessManager($options);
											$root_preview_rules = $preview_mgr->generateRootRules();
											$uploads_preview_rules = $preview_mgr->generateUploadsRules();
											?>

											<div style="margin-bottom: 20px;">
												<strong style="font-size: 13px; color: var(--tka-text-main); display: block; margin-bottom: 5px;">Root .htaccess Block:</strong>
												<pre style="background: var(--tka-bg-main); border: 1px solid var(--tka-border); padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; max-height: 250px; overflow-y: auto; white-space: pre-wrap; margin: 0; color: var(--tka-text-main);"><?php
													if (!empty($root_preview_rules)) {
														echo esc_html("# BEGIN TKA_WP_Utils\n" . implode("\n", $root_preview_rules) . "\n# END TKA_WP_Utils");
													} else {
														esc_html_e('# No root rules active.', 'tka-wp-utils');
													}
												?></pre>
											</div>

											<div>
												<strong style="font-size: 13px; color: var(--tka-text-main); display: block; margin-bottom: 5px;">Uploads Directory .htaccess Block:</strong>
												<pre style="background: var(--tka-bg-main); border: 1px solid var(--tka-border); padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; max-height: 150px; overflow-y: auto; white-space: pre-wrap; margin: 0; color: var(--tka-text-main);"><?php
													if (!empty($uploads_preview_rules)) {
														echo esc_html("# BEGIN TKA_WP_Utils_Uploads\n" . implode("\n", $uploads_preview_rules) . "\n# END TKA_WP_Utils_Uploads");
													} else {
														esc_html_e('# No uploads rules active.', 'tka-wp-utils');
													}
												?></pre>
											</div>
										</div>
									</section>

									<!-- SMTP PANEL -->
									<section id="panel-smtp" class="tka-tab-panel">
										<h2><?php esc_html_e('SMTP & Email Configuration', 'tka-wp-utils'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Configure WordPress to route outgoing emails through an external SMTP server, and enable local Mailpit catching during development.', 'tka-wp-utils'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Custom SMTP', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('Intercepts WordPress emails and forces them to use the SMTP settings defined below.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[smtp_enabled]" value="1"
															<?php checked(1, $options['smtp_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Local Mailpit for Development', 'tka-wp-utils'); ?></strong>
													<p><?php esc_html_e('If the environment type is set to "development" (e.g. WP_ENV="development"), automatically overrides the settings below and routes emails to a local Mailpit instance on port 1025.', 'tka-wp-utils'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_wp_utils_options[smtp_mailpit_dev]" value="1"
															<?php checked(1, $options['smtp_mailpit_dev'] ?? 1); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('SMTP Host', 'tka-wp-utils'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<input type="text" name="tka_wp_utils_options[smtp_host]"
														value="<?php echo esc_attr($options['smtp_host'] ?? ''); ?>"
														placeholder="smtp.example.com" class="regular-text">
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('SMTP Port', 'tka-wp-utils'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<input type="number" name="tka_wp_utils_options[smtp_port]"
														value="<?php echo esc_attr($options['smtp_port'] ?? ''); ?>"
														placeholder="587" class="small-text">
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Encryption', 'tka-wp-utils'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<select name="tka_wp_utils_options[smtp_encryption]">
														<option value="none" <?php selected('none', $options['smtp_encryption'] ?? 'none'); ?>>None</option>
														<option value="ssl" <?php selected('ssl', $options['smtp_encryption'] ?? 'none'); ?>>SSL</option>
														<option value="tls" <?php selected('tls', $options['smtp_encryption'] ?? 'none'); ?>>TLS</option>
													</select>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('SMTP Username', 'tka-wp-utils'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<input type="text" name="tka_wp_utils_options[smtp_username]"
														value="<?php echo esc_attr($options['smtp_username'] ?? ''); ?>"
														class="regular-text" autocomplete="off">
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('SMTP Password', 'tka-wp-utils'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<input type="password" name="tka_wp_utils_options[smtp_password]"
														value="<?php echo esc_attr($options['smtp_password'] ?? ''); ?>"
														class="regular-text" autocomplete="new-password">
												</div>
											</div>

										</div>
									</section>

									<!-- BUTTON WRAPPER -->
									<div class="tka-submit-section">
										<?php submit_button(__('Save Settings', 'tka-wp-utils'), 'primary tka-save-btn', 'submit', false); ?>
									</div>
								</form>

							</main>
						</div>
					</div>
				</div>
				<?php
	}

	/**
	 * Render the Admin Columns Customizer subpage HTML.
	 */
	public function renderAdminColumnsPage(): void
	{
		$columns = get_option('tka_wp_utils_columns', []);
		$public_post_types = get_post_types(['show_ui' => true], 'objects');
		$available_keys = self::getAvailableMetaKeys();
		?>
				<div class="wrap tka-wp-utils-wrap">
					<div class="tka-dashboard">
						<!-- Header Section -->
						<header class="tka-dashboard-header">
							<div class="tka-header-brand">
								<span class="dashicons dashicons-align-left" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
								<h1><?php esc_html_e('TKA WP Utils', 'tka-wp-utils'); ?></h1>
								<span class="tka-version-badge"><?php esc_html_e('Admin Columns', 'tka-wp-utils'); ?></span>
							</div>
							<p class="tka-tagline">
								<?php esc_html_e('Define and customize columns for your admin post list tables based on meta keys.', 'tka-wp-utils'); ?>
							</p>
						</header>

						<!-- Settings Body Layout -->
						<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
							<main class="tka-dashboard-content">
								<form method="post" action="options.php" id="tka-columns-customizer-form">
									<?php
									settings_fields('tka_wp_utils_columns_group');
									?>

									<h2><?php esc_html_e('Admin Columns Customizer', 'tka-wp-utils'); ?></h2>
									<p class="section-desc">
										<?php esc_html_e('Expose custom metadata fields in the list tables. Select a post type to get started.', 'tka-wp-utils'); ?>
									</p>

									<div class="tka-settings-card">
										<!-- Post Type Selection Dropdown -->
										<div class="tka-setting-row" style="padding-top: 0; margin-bottom: 25px;">
											<div class="tka-setting-label">
												<strong><?php esc_html_e('Select Post Type', 'tka-wp-utils'); ?></strong>
												<p><?php esc_html_e('Choose which post type list table you want to customize.', 'tka-wp-utils'); ?>
												</p>
											</div>
											<div class="tka-setting-control">
												<select id="tka-column-post-type-selector"
													style="min-width: 240px; padding: 8px 16px; border-radius: 8px; border-color: var(--tka-border); font-weight: 500; font-size: 14px; box-shadow: var(--tka-shadow);">
													<?php foreach ($public_post_types as $post_type): ?>
															<option value="<?php echo esc_attr($post_type->name); ?>">
																<?php echo esc_html($post_type->label); ?>
																(<?php echo esc_html($post_type->name); ?>)</option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>

										<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 25px 0;">

										<!-- Custom Columns Manager Grid -->
										<div class="tka-setting-row stack" style="border-bottom: none; padding-bottom: 0;">
											<div class="tka-setting-label" style="max-width: 100%;">
												<strong><?php esc_html_e('Column Rules Definition', 'tka-wp-utils'); ?></strong>
												<p><?php esc_html_e('Define headers, choose custom fields, and specify their type. Drag rows to reorder them.', 'tka-wp-utils'); ?>
												</p>
											</div>

											<!-- Twin container panels dynamically shown via JS selector -->
											<div class="tka-columns-manager-panels-wrap" style="width: 100%; margin-top: 15px;">
												<?php foreach ($public_post_types as $post_type):
													$post_type_cols = $columns[$post_type->name] ?? [];
													?>
														<div class="tka-columns-post-type-panel"
															id="tka-columns-panel-<?php echo esc_attr($post_type->name); ?>"
															style="display: none; width: 100%;">

															<!-- Table-like headers -->
															<div class="tka-columns-headers-row"
																style="<?php echo empty($post_type_cols) ? 'display: none;' : ''; ?>">
																<div class="tka-col-hdr tka-hdr-drag" style="width: 30px;"></div>
																<div class="tka-col-hdr tka-hdr-label">
																	<?php esc_html_e('Column Header Label', 'tka-wp-utils'); ?></div>
																<div class="tka-col-hdr tka-hdr-key">
																	<?php esc_html_e('Database Meta Field Key', 'tka-wp-utils'); ?></div>
																<div class="tka-col-hdr tka-hdr-type">
																	<?php esc_html_e('Field Type & Linkage', 'tka-wp-utils'); ?></div>
																<div class="tka-col-hdr tka-hdr-actions" style="width: 50px;"></div>
															</div>

															<div class="tka-columns-rows-list"
																style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
																<?php if (!empty($post_type_cols)):
																	foreach ($post_type_cols as $index => $col):
																		$meta_val = $col['meta_key'] ?? '';
																		$is_custom = !empty($meta_val) && !in_array($meta_val, $available_keys, true);
																		?>
																				<div class="tka-column-row-item">
																					<!-- Drag Handle -->
																					<div class="tka-column-drag-handle"
																						title="<?php esc_attr_e('Drag to reorder', 'tka-wp-utils'); ?>">
																						<span class="dashicons dashicons-menu"></span>
																					</div>

																					<!-- Layout Inputs Grid -->
																					<div class="tka-column-inputs-grid">
																						<!-- Label Input -->
																						<div>
																							<input type="text"
																								name="tka_wp_utils_columns[<?php echo esc_attr($post_type->name); ?>][<?php echo intval($index); ?>][label]"
																								value="<?php echo esc_attr($col['label'] ?? ''); ?>"
																								placeholder="<?php esc_attr_e('Column Header Label (e.g. Price)', 'tka-wp-utils'); ?>"
																								class="tka-col-input-field">
																						</div>

																						<!-- Meta Key Selector Dropdown + Text Input -->
																						<div>
																							<div class="tka-meta-key-selector-wrap">
																								<select class="tka-meta-key-select">
																									<option value="">
																										<?php esc_html_e('— Select a Field —', 'tka-wp-utils'); ?>
																									</option>
																									<?php foreach ($available_keys as $key): ?>
																											<option value="<?php echo esc_attr($key); ?>" <?php selected($meta_val, $key); ?>>
																												<?php echo esc_html($key); ?></option>
																									<?php endforeach; ?>
																									<option value="__custom__" <?php selected($is_custom); ?>>
																										<?php esc_html_e('— Enter Custom Key —', 'tka-wp-utils'); ?>
																									</option>
																								</select>
																								<input type="text" class="tka-meta-key-input"
																									name="tka_wp_utils_columns[<?php echo esc_attr($post_type->name); ?>][<?php echo intval($index); ?>][meta_key]"
																									value="<?php echo esc_attr($meta_val); ?>"
																									placeholder="<?php esc_attr_e('Enter Custom Meta Key', 'tka-wp-utils'); ?>"
																									style="font-family: monospace; <?php echo $is_custom ? 'display: block;' : 'display: none;'; ?>">
																							</div>
																						</div>

																						<!-- Field Type Select -->
																						<div>
																							<select
																								name="tka_wp_utils_columns[<?php echo esc_attr($post_type->name); ?>][<?php echo intval($index); ?>][field_type]"
																								class="tka-field-type-select">
																								<option value="text" <?php selected($col['field_type'] ?? 'text', 'text'); ?>>
																									<?php esc_html_e('Plain Text / Value', 'tka-wp-utils'); ?>
																								</option>
																								<option value="post_relation" <?php selected($col['field_type'] ?? 'text', 'post_relation'); ?>>
																									<?php esc_html_e('Related Post ID or Object (Linked & Filterable)', 'tka-wp-utils'); ?>
																								</option>
																								<option value="term_relation" <?php selected($col['field_type'] ?? 'text', 'term_relation'); ?>>
																									<?php esc_html_e('Related Taxonomy Term (Linked & Filterable)', 'tka-wp-utils'); ?>
																								</option>
																							</select>
																						</div>
																					</div>

																					<!-- Delete Button -->
																					<button type="button" class="button tka-column-remove-btn"
																						title="<?php esc_attr_e('Delete column rule', 'tka-wp-utils'); ?>">
																						<span class="dashicons dashicons-trash"></span>
																					</button>
																				</div>
																		<?php endforeach;
																endif; ?>
															</div>

															<!-- Placeholder if empty -->
															<div class="tka-columns-empty-placeholder"
																style="text-align: center; border: 2px dashed var(--tka-border); border-radius: var(--tka-radius); padding: 40px 20px; background: var(--tka-bg-main); <?php echo !empty($post_type_cols) ? 'display: none;' : ''; ?>">
																<span class="dashicons dashicons-database"
																	style="font-size: 40px; width: 40px; height: 40px; color: var(--tka-text-muted); margin-bottom: 12px; display: inline-block;"></span>
																<p
																	style="margin: 0; font-size: 14px; font-weight: 500; color: var(--tka-text-main);">
																	<?php esc_html_e('No custom columns configured for this post type.', 'tka-wp-utils'); ?>
																</p>
																<p style="margin: 4px 0 0 0; font-size: 12px; color: var(--tka-text-muted);">
																	<?php esc_html_e('Add a new column rule below to display custom metadata inside post list tables.', 'tka-wp-utils'); ?>
																</p>
															</div>

															<button type="button" class="button tka-add-column-row-btn"
																data-posttype="<?php echo esc_attr($post_type->name); ?>"
																style="margin-top: 20px; border-color: var(--tka-primary); color: var(--tka-primary); background: rgba(79, 70, 229, 0.02); padding: 5px 16px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--tka-shadow);">
																<span class="dashicons dashicons-plus"
																	style="font-size: 16px; width: 16px; height: 16px; margin: 0; display: inline-block; vertical-align: middle;"></span>
																<?php esc_html_e('Add Custom Column', 'tka-wp-utils'); ?>
															</button>
														</div>
												<?php endforeach; ?>
											</div>
										</div>
									</div>

									<!-- BUTTON WRAPPER -->
									<div class="tka-submit-section" style="margin-top: 30px;">
										<?php submit_button(__('Save Columns Customizer', 'tka-wp-utils'), 'primary tka-save-btn', 'submit', false); ?>
									</div>
								</form>
							</main>
						</div>
					</div>
				</div>
				<?php
	}

	/**
	 * Render the standalone Admin Menu Organizer subpage HTML.
	 */
	public function renderMenuOrganizerPage(): void
	{
		$options = get_option('tka_wp_utils_options');
		?>
				<div class="wrap tka-wp-utils-wrap">
					<div class="tka-dashboard">
						<!-- Header Section -->
						<header class="tka-dashboard-header">
							<div class="tka-header-brand">
								<span class="dashicons dashicons-menu-alt" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
								<h1><?php esc_html_e('TKA WP Utils', 'tka-wp-utils'); ?></h1>
								<span class="tka-version-badge"><?php esc_html_e('Menu Organizer', 'tka-wp-utils'); ?></span>
							</div>
							<p class="tka-tagline">
								<?php esc_html_e('Configure separate drag-and-drop menu order and visibility rules for yourself and other administrators.', 'tka-wp-utils'); ?>
							</p>
						</header>

						<!-- Settings Body Layout -->
						<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
							<main class="tka-dashboard-content">
								<form method="post" action="options.php">
									<?php
									settings_fields('tka_wp_utils_group');
									?>
									<input type="hidden" name="tka_wp_utils_options[form_context]" value="menu_organizer">

									<h2><?php esc_html_e('Admin Menu Organizer', 'tka-wp-utils'); ?></h2>
									<p class="section-desc">
										<?php esc_html_e('Organize and clean up navigation lists below. Toggle visibility or drag items vertically to sort.', 'tka-wp-utils'); ?>
									</p>

									<div class="tka-settings-card">
										<p style="font-size: 13px; color: var(--tka-primary); background: rgba(79, 70, 229, 0.04); padding: 8px 14px; border-radius: 8px; border-left: 4px solid var(--tka-primary); line-height: 1.4; margin-bottom: 20px;">
											<span class="dashicons dashicons-info" style="font-size: 16px; width: 16px; height: 16px; margin-top: -3px; vertical-align: middle; margin-right: 4px;"></span>
											<strong><?php esc_html_e('Note:', 'tka-wp-utils'); ?></strong>
											<?php esc_html_e('If the "Appearance" (themes.php) menu is hidden for other administrators, a standalone "Menus" option will automatically be exposed in their sidebar to allow navigation menu adjustments.', 'tka-wp-utils'); ?>
										</p>

										<?php
										$default_menus = [
											'index.php' => __('Dashboard', 'tka-wp-utils'),
											'edit.php' => __('Posts', 'tka-wp-utils'),
											'upload.php' => __('Media', 'tka-wp-utils'),
											'edit.php?post_type=page' => __('Pages', 'tka-wp-utils'),
											'edit-comments.php' => __('Comments', 'tka-wp-utils'),
											'themes.php' => __('Appearance', 'tka-wp-utils'),
											'plugins.php' => __('Plugins', 'tka-wp-utils'),
											'users.php' => __('Users', 'tka-wp-utils'),
											'tools.php' => __('Tools', 'tka-wp-utils'),
											'options-general.php' => __('Settings', 'tka-wp-utils'),
											'nav-menus.php' => __('Menus', 'tka-wp-utils'),
										];

										global $menu;
										$raw_menus = [];
										if (!empty($menu)) {
											foreach ($menu as $item) {
												if (empty($item[2]) || (isset($item[4]) && str_contains($item[4], 'wp-menu-separator'))) {
													continue;
												}
												$label = wp_strip_all_tags($item[0]);
												if (empty(trim($label))) {
													continue;
												}
												$raw_menus[$item[2]] = $label;
											}
										}

										// Merge defaults so that hidden items (removed from $menu) are still kept in the UI
										$raw_menus = array_merge($default_menus, $raw_menus);
										?>

										<!-- Sub-Tabs Navigation for Organizers -->
										<div class="tka-sub-tabs-container" style="width: 100%;">
											<div class="tka-sub-tabs-nav">
												<button type="button" class="tka-sub-tab-btn active" data-subtab="client">
													<?php esc_html_e('Client Layout (Other Administrators)', 'tka-wp-utils'); ?>
												</button>
												<button type="button" class="tka-sub-tab-btn" data-subtab="owner">
													<?php esc_html_e('Your Account Layout (Original Installer Only)', 'tka-wp-utils'); ?>
												</button>
											</div>

											<!-- 1. Client Menu Organizer Tab Content -->
											<div class="tka-sub-tab-content active" id="tka-subtab-client-content" style="width: 100%;">
												<div class="tka-setting-row stack" style="margin-top: 15px; border-bottom: none; padding-bottom: 0; width: 100%;">
													<div class="tka-setting-label">
														<p><?php esc_html_e('Customize what other administrators see in their sidebar navigation.', 'tka-wp-utils'); ?>
														</p>
													</div>

													<div class="tka-menu-organizer" id="tka-menu-organizer-list-client" style="width: 100%;">
														<?php
														$menus_to_hide = [];
														$custom_order = $options['admin_menu_order'] ?? [];
														foreach ($custom_order as $slug) {
															if (isset($raw_menus[$slug])) {
																$menus_to_hide[$slug] = $raw_menus[$slug];
															}
														}
														foreach ($raw_menus as $slug => $label) {
															if (!isset($menus_to_hide[$slug])) {
																$menus_to_hide[$slug] = $label;
															}
														}

														foreach ($menus_to_hide as $slug => $label):
															$is_hidden = in_array($slug, $options['hidden_admin_menus'] ?? [], true);
															?>
																<div class="tka-organizer-item <?php echo $is_hidden ? 'menu-hidden' : 'menu-visible'; ?>" data-slug="<?php echo esc_attr($slug); ?>">
																	<!-- Sortable Order Input -->
																	<input type="hidden" name="tka_wp_utils_options[admin_menu_order][]" value="<?php echo esc_attr($slug); ?>">

																	<!-- Hidden Visibility Checkbox (checked = hidden) -->
																	<input type="checkbox" class="tka-menu-visibility-checkbox" name="tka_wp_utils_options[hidden_admin_menus][]" value="<?php echo esc_attr($slug); ?>" <?php checked($is_hidden); ?> style="display: none;">

																	<div class="tka-organizer-drag">
																		<span class="dashicons dashicons-menu"></span>
																	</div>

																	<div class="tka-organizer-details">
																		<strong class="tka-organizer-title"><?php echo esc_html($label); ?></strong>
																		<code class="tka-code-badge"><?php echo esc_html($slug); ?></code>
																	</div>

																	<div class="tka-organizer-actions">
																		<button type="button" class="tka-organizer-toggle-btn <?php echo $is_hidden ? 'tka-btn-hidden' : 'tka-btn-visible'; ?>" title="<?php esc_attr_e('Toggle Visibility', 'tka-wp-utils'); ?>">
																			<span class="dashicons <?php echo $is_hidden ? 'dashicons-hidden' : 'dashicons-visibility'; ?>"></span>
																		</button>
																	</div>
																</div>
														<?php endforeach; ?>
													</div>
												</div>
											</div>

											<!-- 2. Owner Menu Organizer Tab Content -->
											<div class="tka-sub-tab-content" id="tka-subtab-owner-content" style="display: none; width: 100%;">
												<div class="tka-setting-row stack" style="margin-top: 15px; border-bottom: none; padding-bottom: 0; width: 100%;">
													<div class="tka-setting-label">
														<p><?php esc_html_e('Customize your own personal sidebar navigation order and visibility.', 'tka-wp-utils'); ?>
														</p>
													</div>

													<div class="tka-menu-organizer" id="tka-menu-organizer-list-owner" style="width: 100%;">
														<?php
														$owner_menus_to_hide = [];
														$owner_custom_order = $options['owner_admin_menu_order'] ?? [];
														foreach ($owner_custom_order as $slug) {
															if (isset($raw_menus[$slug])) {
																$owner_menus_to_hide[$slug] = $raw_menus[$slug];
															}
														}
														foreach ($raw_menus as $slug => $label) {
															if (!isset($owner_menus_to_hide[$slug])) {
																$owner_menus_to_hide[$slug] = $label;
															}
														}

														foreach ($owner_menus_to_hide as $slug => $label):
															$is_hidden = in_array($slug, $options['owner_hidden_admin_menus'] ?? [], true);
															?>
																<div class="tka-organizer-item <?php echo $is_hidden ? 'menu-hidden' : 'menu-visible'; ?>" data-slug="<?php echo esc_attr($slug); ?>">
																	<!-- Sortable Order Input -->
																	<input type="hidden" name="tka_wp_utils_options[owner_admin_menu_order][]" value="<?php echo esc_attr($slug); ?>">

																	<!-- Hidden Visibility Checkbox (checked = hidden) -->
																	<input type="checkbox" class="tka-menu-visibility-checkbox" name="tka_wp_utils_options[owner_hidden_admin_menus][]" value="<?php echo esc_attr($slug); ?>" <?php checked($is_hidden); ?> style="display: none;">

																	<div class="tka-organizer-drag">
																		<span class="dashicons dashicons-menu"></span>
																	</div>

																	<div class="tka-organizer-details">
																		<strong class="tka-organizer-title"><?php echo esc_html($label); ?></strong>
																		<code class="tka-code-badge"><?php echo esc_html($slug); ?></code>
																	</div>

																	<div class="tka-organizer-actions">
																		<button type="button" class="tka-organizer-toggle-btn <?php echo $is_hidden ? 'tka-btn-hidden' : 'tka-btn-visible'; ?>" title="<?php esc_attr_e('Toggle Visibility', 'tka-wp-utils'); ?>">
																			<span class="dashicons <?php echo $is_hidden ? 'dashicons-hidden' : 'dashicons-visibility'; ?>"></span>
																		</button>
																	</div>
																</div>
														<?php endforeach; ?>
													</div>
												</div>
											</div>
										</div>
									</div>

									<!-- BUTTON WRAPPER -->
									<div class="tka-submit-section" style="margin-top: 30px;">
										<?php submit_button(__('Save Menu Organizer', 'tka-wp-utils'), 'primary tka-save-btn', 'submit', false); ?>
									</div>
								</form>
							</main>
						</div>
					</div>
				</div>
				<?php
	}

	/**
	 * Render the standalone Bulk Retroactive Image Optimizer subpage HTML.
	 */
	public function renderBulkOptimizerPage(): void
	{
		?>
				<div class="wrap tka-wp-utils-wrap">
					<div class="tka-dashboard">
						<!-- Header Section -->
						<header class="tka-dashboard-header">
							<div class="tka-header-brand">
								<span class="dashicons dashicons-format-image" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
								<h1><?php esc_html_e('TKA WP Utils', 'tka-wp-utils'); ?></h1>
								<span class="tka-version-badge"><?php esc_html_e('Bulk Optimizer', 'tka-wp-utils'); ?></span>
							</div>
							<p class="tka-tagline">
								<?php esc_html_e('Retroactively compress and convert existing media library JPEGs and PNGs to WebP in bulk.', 'tka-wp-utils'); ?>
							</p>
						</header>

						<!-- Settings Body Layout -->
						<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
							<main class="tka-dashboard-content">
								<h2><?php esc_html_e('Bulk Retroactive Image Optimizer', 'tka-wp-utils'); ?></h2>
								<p class="section-desc">
									<?php esc_html_e('This advanced utility processes images sequentially (1 by 1) to guarantee absolute server safety, prevent FPM gateway timeouts, and seamlessly clean up intermediate thumbnails on disk.', 'tka-wp-utils'); ?>
								</p>

								<!-- Bulk Retroactive Image Optimizer Card -->
								<div class="tka-settings-card" style="margin-top: 20px;">
									<?php
									$images_query = new \WP_Query([
										'post_type' => 'attachment',
										'post_mime_type' => ['image/jpeg', 'image/png'],
										'post_status' => 'inherit',
										'posts_per_page' => -1,
										'fields' => 'ids',
										'suppress_filters' => true,
									]);
									$total_eligible_images = $images_query->post_count;
									
									global $wpdb;
									$all_time_savings = (int) $wpdb->get_var("SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_tka_image_savings'");
									?>

									<div style="background: var(--tka-bg-main); padding: 18px 24px; border-radius: 8px; border: 1px solid var(--tka-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
										<div>
											<span style="font-size: 13px; color: var(--tka-text-muted); display: block; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
												<?php esc_html_e('Eligible Media Library Images', 'tka-wp-utils'); ?>
											</span>
											<strong style="font-size: 24px; color: var(--tka-text-main); margin-top: 4px; display: block;">
												<span id="tka-bulk-total-count"><?php echo intval($total_eligible_images); ?></span> <?php esc_html_e('images', 'tka-wp-utils'); ?>
											</strong>
										</div>
										<div style="display: flex; gap: 10px;">
											<button type="button" id="tka-bulk-optimize-pause-btn" class="button button-secondary"
												style="border-color: var(--tka-danger); color: var(--tka-danger); background: rgba(239, 68, 68, 0.02); font-weight: 600; padding: 6px 18px; border-radius: 8px; height: auto; transition: all 0.15s ease-in-out; display: none;">
												<span class="dashicons dashicons-controls-pause" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-top: -3px; margin-right: 4px;"></span>
												<?php esc_html_e('Pause', 'tka-wp-utils'); ?>
											</button>
											<button type="button" id="tka-bulk-optimize-start-btn" class="button button-secondary"
												style="border-color: var(--tka-primary); color: var(--tka-primary); background: rgba(79, 70, 229, 0.02); font-weight: 600; padding: 6px 18px; border-radius: 8px; height: auto; transition: all 0.15s ease-in-out;"
												<?php disabled($total_eligible_images, 0); ?>>
												<span class="dashicons dashicons-performance" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-top: -3px; margin-right: 4px;"></span>
												<?php esc_html_e('Start Bulk Optimization', 'tka-wp-utils'); ?>
											</button>
										</div>
									</div>

									<!-- Interactive Progress Panel (hidden by default) -->
									<div id="tka-bulk-progress-panel" style="display: none; margin-top: 20px; border-top: 1px solid var(--tka-border); padding-top: 20px; animation: tkaFadeIn 0.3s ease-in-out;">
										<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--tka-text-main);">
											<span id="tka-bulk-progress-status"><?php esc_html_e('Preparing images...', 'tka-wp-utils'); ?></span>
											<span id="tka-bulk-progress-percentage">0%</span>
										</div>
										<div style="width: 100%; background: #e2e8f0; height: 10px; border-radius: 5px; overflow: hidden; margin-bottom: 15px;">
											<div id="tka-bulk-progress-bar" style="width: 0%; background: var(--tka-primary); height: 100%; transition: width 0.3s ease-in-out; border-radius: 5px;"></div>
										</div>
										<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px;">
											<div style="background: rgba(16, 185, 129, 0.03); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 6px; padding: 12px 16px;">
												<span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--tka-text-muted); display: block; letter-spacing: 0.5px;">
													<?php esc_html_e('Storage Saved (This Run)', 'tka-wp-utils'); ?>
												</span>
												<strong id="tka-bulk-total-savings" style="font-size: 18px; color: var(--tka-success); margin-top: 2px; display: block;">
													0 KB
												</strong>
											</div>
											<div style="background: rgba(16, 185, 129, 0.03); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 6px; padding: 12px 16px;">
												<span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--tka-text-muted); display: block; letter-spacing: 0.5px;">
													<?php esc_html_e('Storage Saved (All Time)', 'tka-wp-utils'); ?>
												</span>
												<strong id="tka-bulk-all-time-savings" style="font-size: 18px; color: var(--tka-success); margin-top: 2px; display: block;" data-initial="<?php echo esc_attr($all_time_savings); ?>">
													<?php echo esc_html(size_format($all_time_savings)); ?>
												</strong>
											</div>
											<div style="background: rgba(79, 70, 229, 0.03); border: 1px solid rgba(79, 70, 229, 0.1); border-radius: 6px; padding: 12px 16px;">
												<span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--tka-text-muted); display: block; letter-spacing: 0.5px;">
													<?php esc_html_e('Optimized Images', 'tka-wp-utils'); ?>
												</span>
												<strong style="font-size: 18px; color: var(--tka-primary); margin-top: 2px; display: block;">
													<span id="tka-bulk-optimized-count">0</span> / <span id="tka-bulk-eligible-total-count"><?php echo intval($total_eligible_images); ?></span>
												</strong>
											</div>
										</div>

										<!-- Live Log Box -->
										<div id="tka-bulk-log-box" style="margin-top: 15px; background: #0f172a; color: #38bdf8; font-family: SFMono-Regular, Consolas, monospace; font-size: 11px; padding: 12px 16px; border-radius: 6px; height: 170px; overflow-y: auto; line-height: 1.5; border: 1px solid #1e293b;">
											<div style="color: #64748b;"><?php esc_html_e('>> Ready to begin bulk scan...', 'tka-wp-utils'); ?></div>
										</div>
									</div>
								</div>

								<!-- Media Library Status Table -->
								<div class="tka-settings-card" style="margin-top: 30px; padding: 24px;">
									<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
										<div>
											<h3 style="margin-top: 0; margin-bottom: 8px; font-size: 18px; color: var(--tka-text-main); font-weight: 700;">
												<?php esc_html_e('Media Library Status', 'tka-wp-utils'); ?>
											</h3>
											<p class="section-desc" style="margin-bottom: 0;">
												<?php esc_html_e('Monitor all JPEG, PNG, and WebP attachments, their current format state, and their calculated disk storage footprint savings.', 'tka-wp-utils'); ?>
											</p>
										</div>
										<div class="tka-status-tabs" style="display: flex; gap: 8px;">
											<button type="button" class="button tka-status-tab-btn active" data-status="all" style="border-radius: 20px;"><?php esc_html_e('All', 'tka-wp-utils'); ?></button>
											<button type="button" class="button tka-status-tab-btn" data-status="pending" style="border-radius: 20px;"><?php esc_html_e('Pending', 'tka-wp-utils'); ?></button>
											<button type="button" class="button tka-status-tab-btn" data-status="optimized" style="border-radius: 20px;"><?php esc_html_e('Optimized', 'tka-wp-utils'); ?></button>
										</div>
									</div>

									<div style="overflow-x: auto;">
										<table class="tka-table" style="margin-top: 0;">
											<thead>
												<tr>
													<th style="width: 60px;"><?php esc_html_e('Preview', 'tka-wp-utils'); ?></th>
													<th><?php esc_html_e('Filename', 'tka-wp-utils'); ?></th>
													<th style="width: 120px;"><?php esc_html_e('Current Format', 'tka-wp-utils'); ?></th>
													<th style="width: 140px;"><?php esc_html_e('Status', 'tka-wp-utils'); ?></th>
													<th style="width: 140px; text-align: right;"><?php esc_html_e('Size Savings', 'tka-wp-utils'); ?></th>
												</tr>
											</thead>
											<tbody id="tka-bulk-status-table-body">
												<tr>
													<td colspan="5" style="text-align: center; padding: 30px; color: var(--tka-text-muted);">
														<span class="spinner is-active" style="float: none; margin-right: 8px;"></span> <?php esc_html_e('Loading images...', 'tka-wp-utils'); ?>
													</td>
												</tr>
											</tbody>
										</table>
									</div>
									
									<div class="tka-pagination-controls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; border-top: 1px solid var(--tka-border); padding-top: 15px;">
										<div style="font-size: 13px; color: var(--tka-text-muted);">
											<?php esc_html_e('Showing', 'tka-wp-utils'); ?> <span id="tka-pagination-start">0</span> - <span id="tka-pagination-end">0</span> <?php esc_html_e('of', 'tka-wp-utils'); ?> <span id="tka-pagination-total">0</span>
										</div>
										<div style="display: flex; gap: 8px;">
											<button type="button" id="tka-pagination-prev" class="button" disabled>&laquo; <?php esc_html_e('Previous', 'tka-wp-utils'); ?></button>
											<span id="tka-pagination-current" style="display: inline-flex; align-items: center; padding: 0 10px; font-weight: 600;">1</span>
											<button type="button" id="tka-pagination-next" class="button" disabled><?php esc_html_e('Next', 'tka-wp-utils'); ?> &raquo;</button>
										</div>
									</div>
								</div>
							</main>
						</div>
					</div>
				</div>
				<?php
	}
}
