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
	public const MENU_SLUG = 'tka-site-utilities';

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
				__('TKA Site Utilities Settings', 'tka-site-utilities'),
				__('TKA Site Utilities', 'tka-site-utilities'),
				'manage_options',
				self::MENU_SLUG,
				[$this, 'renderSettingsPage'],
				'dashicons-admin-generic',
				80
			);

			// Default first submenu matching the parent slug
			add_submenu_page(
				self::MENU_SLUG,
				__('TKA Site Utilities Settings', 'tka-site-utilities'),
				__('Settings Dashboard', 'tka-site-utilities'),
				'manage_options',
				self::MENU_SLUG,
				[$this, 'renderSettingsPage']
			);

			// Standalone Admin Columns Customizer submenu
			add_submenu_page(
				self::MENU_SLUG,
				__('Admin Columns Customizer', 'tka-site-utilities'),
				__('Admin Columns', 'tka-site-utilities'),
				'manage_options',
				'tka-site-utilities-columns',
				[$this, 'renderAdminColumnsPage']
			);

			// Standalone Admin Menu Organizer submenu
			add_submenu_page(
				self::MENU_SLUG,
				__('Admin Menu Organizer', 'tka-site-utilities'),
				__('Menu Organizer', 'tka-site-utilities'),
				'manage_options',
				'tka-site-utilities-menu-organizer',
				[$this, 'renderMenuOrganizerPage']
			);

			// Standalone Bulk Retroactive Image Optimizer submenu
			add_submenu_page(
				self::MENU_SLUG,
				__('Bulk Retroactive Image Optimizer', 'tka-site-utilities'),
				__('Bulk Optimizer', 'tka-site-utilities'),
				'manage_options',
				'tka-site-utilities-bulk-optimizer',
				[$this, 'renderBulkOptimizerPage']
			);

			// License Manager submenu
			add_submenu_page(
				self::MENU_SLUG,
				__('TKA License Management', 'tka-site-utilities'),
				__('License', 'tka-site-utilities'),
				'manage_options',
				'tka-site-utilities-license',
				[$this, 'renderLicensePage']
			);
		}

		// Shortcut to Bulk Optimizer under the Media menu (Available to all admins)
		add_submenu_page(
			'upload.php',
			__('Bulk Retroactive Image Optimizer', 'tka-site-utilities'),
			__('Bulk Optimizer', 'tka-site-utilities'),
			'manage_options',
			'tka-site-utilities-bulk-optimizer-media',
			[$this, 'renderBulkOptimizerPage']
		);

		// Redirects manager as a Top-Level Menu (Available to all admins)
		add_menu_page(
			__('URL Redirects', 'tka-site-utilities'),
			__('Redirects', 'tka-site-utilities'),
			'manage_options',
			'tka-site-utilities-redirects',
			[$this, 'renderRedirectsPage'],
			'dashicons-randomize',
			81 // Position right below TKA Site Utilities
		);
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public function registerSettings(): void
	{
		register_setting(
			'tka_site_utilities_group',
			'tka_site_utilities_options',
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
					'order_taxonomies' => [],
					'duplicate_enabled' => 0,
					'duplicate_post_types' => [],
					'replace_media_enabled' => 0,
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
					'gf_optimize_cwv' => 0,
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
					'deferred_scripts' => [],
					'deferred_scripts_custom' => '',
					'delayed_scripts_interaction' => '',
					'async_styles' => '',
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
					'link_prefetch' => 0,
				],
			]
		);

		register_setting(
			'tka_site_utilities_redirects_group',
			'tka_site_utilities_redirects',
			[
				'sanitize_callback' => [$this, 'sanitizeRedirects'],
				'default' => []
			]
		);

		register_setting(
			'tka_site_utilities_columns_group',
			'tka_site_utilities_columns',
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
							// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
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
		$existing = get_option('tka_site_utilities_options', []);
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
			'replace_media_enabled' => 0,
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
			'gf_optimize_cwv' => 0,
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
			'deferred_scripts' => [],
			'deferred_scripts_custom' => '',
			'delayed_scripts_interaction' => '',
			'async_styles' => '',
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
			'link_prefetch' => 0,
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
			$sanitized['order_enabled'] = !empty($input['order_enabled']) ? 1 : 0;
			$sanitized['order_post_types'] = [];
			if (isset($input['order_post_types']) && is_array($input['order_post_types'])) {
				foreach ($input['order_post_types'] as $post_type) {
					$sanitized['order_post_types'][] = sanitize_text_field($post_type);
				}
			}

			$sanitized['order_taxonomies'] = [];
			if (isset($input['order_taxonomies']) && is_array($input['order_taxonomies'])) {
				foreach ($input['order_taxonomies'] as $tax) {
					$sanitized['order_taxonomies'][] = sanitize_text_field($tax);
				}
			}

			$sanitized['duplicate_enabled'] = !empty($input['duplicate_enabled']) ? 1 : 0;
			$sanitized['duplicate_post_types'] = isset($input['duplicate_post_types']) && is_array($input['duplicate_post_types']) ? array_map('sanitize_text_field', $input['duplicate_post_types']) : [];
			$sanitized['replace_media_enabled'] = isset($input['replace_media_enabled']) ? 1 : 0;
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
				$sanitized['gf_optimize_cwv'] = isset($input['gf_optimize_cwv']) ? 1 : 0;
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
			$sanitized['link_prefetch'] = isset($input['link_prefetch']) ? 1 : 0;

			$sanitized['deferred_scripts'] = [];
			if (isset($input['deferred_scripts']) && is_array($input['deferred_scripts'])) {
				foreach ($input['deferred_scripts'] as $script) {
					$sanitized['deferred_scripts'][] = sanitize_text_field($script);
				}
			}
			$sanitized['deferred_scripts_custom'] = isset($input['deferred_scripts_custom']) ? sanitize_textarea_field($input['deferred_scripts_custom']) : '';
			$sanitized['delayed_scripts_interaction'] = isset($input['delayed_scripts_interaction']) ? sanitize_textarea_field($input['delayed_scripts_interaction']) : '';
			$sanitized['async_styles'] = isset($input['async_styles']) ? sanitize_textarea_field($input['async_styles']) : '';

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
			'toplevel_page_tka-site-utilities-redirects',
			'tka-site-utilities_page_tka-site-utilities-columns',
			'tka-site-utilities_page_tka-site-utilities-menu-organizer',
			'tka-site-utilities_page_tka-site-utilities-bulk-optimizer',
			'tka-site-utilities_page_tka-site-utilities-license',
			'media_page_tka-site-utilities-bulk-optimizer-media',
			'admin_page_tka-site-utilities-bulk-optimizer-media',
			'settings_page_' . self::MENU_SLUG,
			'tka-site-utilities_page_' . self::MENU_SLUG,
			'admin_page_' . self::MENU_SLUG,
		];

		if (!in_array($hook, $allowed_hooks, true)) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'tka-site-utilities-admin-css',
			TKA_SITE_UTILITIES_URL . 'admin/css/admin-style.css',
			[],
			TKA_SITE_UTILITIES_VERSION
		);

		wp_enqueue_script(
			'tka-site-utilities-admin-js',
			TKA_SITE_UTILITIES_URL . 'admin/js/admin-script.js',
			['jquery', 'jquery-ui-sortable'],
			TKA_SITE_UTILITIES_VERSION,
			true
		);

		wp_localize_script('tka-site-utilities-admin-js', 'tkaWpUtilsAdmin', [
			'ajaxUrl'           => admin_url('admin-ajax.php'),
			'bulkOptimizeNonce' => wp_create_nonce('tka_site_utilities_bulk_optimize'),
		]);

		if ('tka-site-utilities_page_tka-site-utilities-columns' === $hook) {
			wp_enqueue_script(
				'tka-site-utilities-columns-js',
				TKA_SITE_UTILITIES_URL . 'admin/js/admin-columns.js',
				['jquery', 'jquery-ui-sortable'],
				TKA_SITE_UTILITIES_VERSION,
				true
			);
			wp_localize_script('tka-site-utilities-columns-js', 'tkaWpUtilsColumns', [
				'metaKeys' => self::getAvailableMetaKeys(),
				'i18n' => [
					'selectField' => __('— Select a Field —', 'tka-site-utilities'),
					'enterCustomKey' => __('— Enter Custom Key —', 'tka-site-utilities'),
					'customKeyPlaceholder' => __('Enter Custom Meta Key', 'tka-site-utilities'),
					'plainText' => __('Plain Text / Value', 'tka-site-utilities'),
					'relatedPost' => __('Related Post ID or Object (Linked & Filterable)', 'tka-site-utilities'),
					'relatedTerm' => __('Related Taxonomy Term (Linked & Filterable)', 'tka-site-utilities'),
				],
			]);
		}
	}

	/**
	 * Get a list of all distinct meta keys in the database.
	 */
	public static function getAvailableMetaKeys(): array
	{
		$cache_key   = 'tka_available_meta_keys';
		$cache_group = 'tka-site-utilities';
		$keys        = wp_cache_get( $cache_key, $cache_group );
		if ( false === $keys ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$keys = $wpdb->get_col("
				SELECT DISTINCT meta_key 
				FROM $wpdb->postmeta 
				WHERE meta_key NOT LIKE '\\_edit\\_%' 
				  AND meta_key NOT LIKE '\\_wp\\_%'
				  AND meta_key NOT LIKE '\\_oembed\\_%'
				ORDER BY meta_key ASC
			");
			if ( ! is_array( $keys ) ) {
				$keys = [];
			}
			wp_cache_set( $cache_key, $keys, $cache_group, DAY_IN_SECONDS );
		}
		return array_values(array_filter($keys));
	}


	/**
	 * Render the settings page HTML.
	 */
	public function renderSettingsPage(): void
	{
		$options = get_option('tka_site_utilities_options');
		$public_post_types = get_post_types(['show_ui' => true], 'objects');
		$public_taxonomies = get_taxonomies(['show_ui' => true], 'objects');
		?>
				<div class="wrap tka-site-utilities-wrap">
					<div class="tka-dashboard">
						<!-- Header Section -->
						<header class="tka-dashboard-header">
							<div class="tka-header-brand">
								<span class="dashicons dashicons-admin-generic" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
								<h1>TKA Site Utilities</h1>
								<span class="tka-version-badge">v<?php echo esc_html(TKA_SITE_UTILITIES_VERSION); ?></span>
							</div>
							<p class="tka-tagline">
								<?php esc_html_e('Customize and secure your WordPress publishing workflow.', 'tka-site-utilities'); ?></p>
						</header>

						<!-- Settings Body Layout -->
						<div class="tka-dashboard-body">
							<!-- Sidebar Menu -->
							<aside class="tka-dashboard-sidebar">
								<nav class="tka-dashboard-nav">
									<a href="#general" class="tka-nav-item active" data-tab="general">
										<span class="dashicons dashicons-admin-settings"></span>
										<?php esc_html_e('Editor & Blocks', 'tka-site-utilities'); ?>
									</a>
									<a href="#uploads" class="tka-nav-item" data-tab="uploads">
										<span class="dashicons dashicons-shield"></span>
										<?php esc_html_e('Security', 'tka-site-utilities'); ?>
									</a>
									<a href="#frontend-optimization" class="tka-nav-item" data-tab="frontend-optimization">
										<span class="dashicons dashicons-performance"></span>
										<?php esc_html_e('Frontend Optimization', 'tka-site-utilities'); ?>
									</a>
									<a href="#content" class="tka-nav-item" data-tab="content">
										<span class="dashicons dashicons-category"></span>
										<?php esc_html_e('Content Management', 'tka-site-utilities'); ?>
									</a>
									<a href="#database" class="tka-nav-item" data-tab="database">
										<span class="dashicons dashicons-database"></span>
										<?php esc_html_e('Database', 'tka-site-utilities'); ?>
									</a>
									<?php if (AdminInterface::isCurrentUserInstaller()): ?>
											<a href="#admin-interface" class="tka-nav-item" data-tab="admin-interface">
												<span class="dashicons dashicons-admin-users"></span>
												<?php esc_html_e('Admin Interface', 'tka-site-utilities'); ?>
											</a>
											<a href="#design" class="tka-nav-item" data-tab="design">
												<span class="dashicons dashicons-art"></span>
												<?php esc_html_e('Design Customization', 'tka-site-utilities'); ?>
											</a>
									<?php endif; ?>
									<?php if (class_exists('ACF')): ?>
											<a href="#acf" class="tka-nav-item" data-tab="acf">
												<span class="dashicons dashicons-welcome-widgets-menus"></span>
												<?php esc_html_e('ACF Settings', 'tka-site-utilities'); ?>
											</a>
									<?php endif; ?>
									<a href="#images" class="tka-nav-item" data-tab="images">
										<span class="dashicons dashicons-format-image"></span>
										<?php esc_html_e('Image Optimization', 'tka-site-utilities'); ?>
									</a>
									<?php if (class_exists('GFCommon')): ?>
											<a href="#gravity-forms" class="tka-nav-item" data-tab="gravity-forms">
												<span class="dashicons dashicons-feedback"></span>
												<?php esc_html_e('Gravity Forms', 'tka-site-utilities'); ?>
											</a>
									<?php endif; ?>
									<?php if (class_exists('WooCommerce')): ?>
											<a href="#woocommerce" class="tka-nav-item" data-tab="woocommerce">
												<span class="dashicons dashicons-cart"></span>
												<?php esc_html_e('WooCommerce', 'tka-site-utilities'); ?>
											</a>
									<?php endif; ?>
									<?php if (class_exists('SitePress') || defined('ICL_SITEPRESS_VERSION')): ?>
											<a href="#wpml-opt" class="tka-nav-item" data-tab="wpml-opt">
												<span class="dashicons dashicons-translation"></span>
												<?php esc_html_e('WPML Optimization', 'tka-site-utilities'); ?>
											</a>
									<?php endif; ?>
									<a href="#transitions" class="tka-nav-item" data-tab="transitions">
										<span class="dashicons dashicons-randomize"></span>
										<?php esc_html_e('Page Transitions', 'tka-site-utilities'); ?>
									</a>
									<a href="#maintenance" class="tka-nav-item" data-tab="maintenance">
										<span class="dashicons dashicons-clock"></span>
										<?php esc_html_e('Maintenance Mode', 'tka-site-utilities'); ?>
									</a>
									<a href="#htaccess" class="tka-nav-item" data-tab="htaccess">
										<span class="dashicons dashicons-editor-code"></span>
										<?php esc_html_e('.htaccess Control', 'tka-site-utilities'); ?>
									</a>
									<a href="#smtp" class="tka-nav-item" data-tab="smtp">
										<span class="dashicons dashicons-email"></span>
										<?php esc_html_e('SMTP & Email', 'tka-site-utilities'); ?>
									</a>
							
								</nav>

								<div class="tka-sidebar-info">
									<h3><?php esc_html_e('System Status', 'tka-site-utilities'); ?></h3>
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
								<?php if (!\TKA\WPUtils\Licensing\Licensing::isActive()): ?>
									<div class="tka-settings-card" style="padding: 40px; text-align: center;">
										<span class="dashicons dashicons-lock" style="font-size: 64px; width: 64px; height: 64px; color: #ef4444; margin-bottom: 20px;"></span>
										<h2 style="font-size: 24px; margin-bottom: 15px;"><?php esc_html_e('License Required', 'tka-site-utilities'); ?></h2>
										<p style="font-size: 16px; color: var(--tka-text-muted); margin-bottom: 25px;">
											<?php esc_html_e('Your license is not active or has expired. Please activate a valid license to access and configure these settings.', 'tka-site-utilities'); ?>
										</p>
										<a href="<?php echo esc_url(admin_url('admin.php?page=tka-site-utilities-license')); ?>" class="tka-btn tka-btn-primary" style="font-size: 16px; padding: 10px 24px;"><?php esc_html_e('Manage License', 'tka-site-utilities'); ?></a>
									</div>
								<?php else: ?>
								<form method="post" action="options.php">
									<?php
									settings_fields('tka_site_utilities_group');
									?>
									<input type="hidden" name="tka_site_utilities_options[form_context]" value="general_settings">

									<!-- EDITOR & BLOCKS PANEL -->
									<section id="panel-general" class="tka-tab-panel active">
										<h2><?php esc_html_e('Editor & Block Control', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Customize your content editing experience by controlling the Classic Editor, Classic Widgets, and Gutenberg blocks.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Classic Editor', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Reverts post/page creation and editing back to the classic rich text (TinyMCE) editor.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[classic_editor]" value="1"
															<?php checked(1, $options['classic_editor'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Classic Widgets', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Restores the traditional widgets dashboard and blocks the block-based widget editor.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[classic_widgets]" value="1"
															<?php checked(1, $options['classic_widgets'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<div class="tka-setting-row stack">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Gutenberg Block Editor', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Choose to allow Gutenberg, completely disable it, or disable it only for specific content types.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control-radios">
													<label class="tka-radio-card">
														<input type="radio" name="tka_site_utilities_options[disable_gutenberg]" value="none"
															<?php checked('none', $options['disable_gutenberg'] ?? 'none'); ?>>
														<div class="radio-card-content">
															<strong><?php esc_html_e('Keep Gutenberg Active', 'tka-site-utilities'); ?></strong>
															<span><?php esc_html_e('Default WordPress block editor behavior.', 'tka-site-utilities'); ?></span>
														</div>
													</label>

													<label class="tka-radio-card">
														<input type="radio" name="tka_site_utilities_options[disable_gutenberg]" value="all"
															<?php checked('all', $options['disable_gutenberg'] ?? 'none'); ?>>
														<div class="radio-card-content">
															<strong><?php esc_html_e('Disable Completely', 'tka-site-utilities'); ?></strong>
															<span><?php esc_html_e('Globally disable Gutenberg for all post types.', 'tka-site-utilities'); ?></span>
														</div>
													</label>

													<label class="tka-radio-card">
														<input type="radio" name="tka_site_utilities_options[disable_gutenberg]"
															value="post_types" <?php checked('post_types', $options['disable_gutenberg'] ?? 'none'); ?>>
														<div class="radio-card-content">
															<strong><?php esc_html_e('Disable by Post Type', 'tka-site-utilities'); ?></strong>
															<span><?php esc_html_e('Selectively revert back to Classic Editor on specific post types.', 'tka-site-utilities'); ?></span>
														</div>
													</label>

													<?php if (class_exists('WooCommerce')): ?>
															<label class="tka-radio-card">
																<input type="radio" name="tka_site_utilities_options[disable_gutenberg]"
																	value="wc_except_cart_checkout" <?php checked('wc_except_cart_checkout', $options['disable_gutenberg'] ?? 'none'); ?>>
																<div class="radio-card-content">
																	<strong><?php esc_html_e('Disable everywhere except Cart & Checkout', 'tka-site-utilities'); ?></strong>
																	<span><?php esc_html_e('Suppresses Gutenberg everywhere, but preserves it on WooCommerce Cart and Checkout pages.', 'tka-site-utilities'); ?></span>
																</div>
															</label>
													<?php endif; ?>
												</div>
											</div>

											<div class="tka-setting-row nested-gutenberg-post-types"
												style="<?php echo ('post_types' === ($options['disable_gutenberg'] ?? 'none')) ? 'display: block;' : 'display: none;'; ?>">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Choose Post Types to Disable Gutenberg', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Gutenberg editor will be turned OFF for any post type checked below.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-checkbox-grid">
													<?php foreach ($public_post_types as $post_type): ?>
															<label class="tka-checkbox-item">
																<input type="checkbox" name="tka_site_utilities_options[gutenberg_post_types][]"
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
													<strong><?php esc_html_e('Dequeue Core Gutenberg Block Styles', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Removes core Gutenberg library stylesheets (wp-block-library.css and wp-block-library-theme.css) from enqueuing on the frontend. Highly recommended if you use Classic Editor, ACF Layouts, or custom page builders.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[gutenberg_dequeue_block_styles]" value="1"
															<?php checked(1, $options['gutenberg_dequeue_block_styles'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>
									</section>

									<!-- SECURITY PANEL -->
									<section id="panel-uploads" class="tka-tab-panel">
										<h2><?php esc_html_e('Security & Hardening Settings', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Protect your WordPress installation from user enumeration, spam harvesters, brute-force attacks, and XML security threats.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Allow SVG Uploads with Strict Validation', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Allows administrators and creators to upload .svg vectors. Validates the XML hierarchy upon upload to protect against XML Entity Expansion (XXE) and Cross-Site Scripting (XSS) injection scripts.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[svg_upload]" value="1" <?php checked(1, $options['svg_upload'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Obfuscate Author URLs & REST User Slugs', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Hides usernames from public author links (e.g. /author/username/ becomes /author/obfuscated_hash/) and REST API endpoints. Attempts to request the original username-based author links return a 404 error.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[obfuscate_author_urls]"
															value="1" <?php checked(1, $options['obfuscate_author_urls'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Obfuscate Email Addresses', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Scans published post and widget contents to convert email addresses into randomized decimal/hexadecimal HTML entities. Keeps emails perfectly readable for humans but shields them from automatic spam scraping bots.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[obfuscate_emails]" value="1"
															<?php checked(1, $options['obfuscate_emails'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable XML-RPC & Block Trackbacks/Pingbacks', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Protects your site from remote brute-force, DOS, and DDOS attacks by disabling the legacy XML-RPC protocol completely, and stripping all active pingback headers.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[disable_xmlrpc]" value="1"
															<?php checked(1, $options['disable_xmlrpc'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>
									</section>

									<!-- FRONTEND OPTIMIZATION PANEL -->
									<section id="panel-frontend-optimization" class="tka-tab-panel">
										<h2><?php esc_html_e('Frontend Optimization', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Clean up unnecessary assets, restrict API endpoints, and optimize the WordPress backend/frontend environment.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Emojis', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Prevents WordPress emoji scripts and styles from loading on frontend and admin screens.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[disable_emojis]" value="1"
															<?php checked(1, $options['disable_emojis'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Comments Completely', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Turns off all front-end commenting/pings, closes comments, and hides Comments menus in the admin dashboard.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[disable_comments]" value="1"
															<?php checked(1, $options['disable_comments'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Restrict REST API', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Requires authentication for REST API endpoints. Non-logged-in guest API calls will receive a 401 Unauthorized status.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[disable_rest_api]" value="1"
															<?php checked(1, $options['disable_rest_api'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Feeds', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Stops serving RSS/Atom XML feeds, strips feed head links, and redirects guest feed calls back to the homepage.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[disable_feeds]" value="1"
															<?php checked(1, $options['disable_feeds'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Embeds', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Deregisters the wp-embed.min.js script, disables auto-discovery, and removes oEmbed REST/header links.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[disable_embeds]" value="1"
															<?php checked(1, $options['disable_embeds'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Remove Version Strings & Generator Tag', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Removes the generator meta tag from head and strips "?ver=" parameters from enqueued styles/scripts on front-end.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[disable_version_strings]"
															value="1" <?php checked(1, $options['disable_version_strings'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Front-End Dashicons', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Dequeues dashicons.min.css on front-end enqueues for non-logged-in guest visitors to optimize assets size.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[disable_front_dashicons]"
															value="1" <?php checked(1, $options['disable_front_dashicons'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Link Hover Prefetching', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Speed up page loads by prefetching internal links when the user hovers over (or touches) them.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[link_prefetch]" value="1"
															<?php checked(1, $options['link_prefetch'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Disable Virtual Cron (WP-Cron)', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Disables the default pseudo-cron that runs on page loads. Requires setting up a real system-level cron job to execute scheduled events.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-disable-wp-cron-toggle" name="tka_site_utilities_options[disable_wp_cron]" value="1"
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
															<?php esc_html_e('System Cron Configuration Note', 'tka-site-utilities'); ?>
														</strong>
														<p style="margin: 0 0 12px 0; font-size: 13px; color: var(--tka-text-muted); line-height: 1.5;">
															<?php esc_html_e('Since WP-Cron is disabled, scheduled tasks (like backups, publishing scheduled posts, and background updates) will not run automatically. You MUST set up a real cron job in your hosting panel (cPanel, Plesk, SSH, etc.) to trigger this file at regular intervals (e.g., every 15 minutes).', 'tka-site-utilities'); ?>
														</p>
														<p style="margin: 0 0 8px 0; font-size: 13px; color: var(--tka-text-muted); font-weight: 600;">
															<?php esc_html_e('Recommended Cron Command:', 'tka-site-utilities'); ?>
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
													<strong><?php esc_html_e('Heartbeat API Control', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Control or disable the WordPress Heartbeat API, which performs AJAX requests in the background.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<select name="tka_site_utilities_options[heartbeat_control]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border); background: var(--tka-bg-card); color: var(--tka-text-main);">
														<option value="default" <?php selected('default', $options['heartbeat_control'] ?? 'default'); ?>><?php esc_html_e('Default (No Override)', 'tka-site-utilities'); ?></option>
														<option value="disable_everywhere" <?php selected('disable_everywhere', $options['heartbeat_control'] ?? 'default'); ?>><?php esc_html_e('Disable Everywhere', 'tka-site-utilities'); ?></option>
														<option value="disable_dashboard" <?php selected('disable_dashboard', $options['heartbeat_control'] ?? 'default'); ?>><?php esc_html_e('Disable on Dashboard', 'tka-site-utilities'); ?></option>
														<option value="allow_only_post_edit" <?php selected('allow_only_post_edit', $options['heartbeat_control'] ?? 'default'); ?>><?php esc_html_e('Allow only on Post Edit Screen', 'tka-site-utilities'); ?></option>
													</select>
												</div>
											</div>

											<!-- Heartbeat Frequency -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Heartbeat Frequency', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Specify the interval (in seconds) between Heartbeat API requests when active.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<select name="tka_site_utilities_options[heartbeat_frequency]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border); background: var(--tka-bg-card); color: var(--tka-text-main);">
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
													<strong><?php esc_html_e('Post Revisions Limit', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Limit the number of revisions stored in the database for each post to prevent database bloat.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<select name="tka_site_utilities_options[revisions_limit]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border); background: var(--tka-bg-card); color: var(--tka-text-main);">
														<option value="-1" <?php selected(-1, intval($options['revisions_limit'] ?? -1)); ?>><?php esc_html_e('Unlimited (WordPress Default)', 'tka-site-utilities'); ?></option>
														<option value="0" <?php selected(0, intval($options['revisions_limit'] ?? -1)); ?>><?php esc_html_e('Disable Revisions', 'tka-site-utilities'); ?></option>
														<?php
														for ($i = 1; $i <= 10; $i++) {
															/* translators: %d: number of revisions */
															$revision_text = sprintf( _n( '%d Revision', '%d Revisions', $i, 'tka-site-utilities' ), $i );
															echo '<option value="' . esc_attr($i) . '" ' . selected($i, intval($options['revisions_limit'] ?? -1), false) . '>' . esc_html($revision_text) . '</option>';
														}
														?>
													</select>
												</div>
											</div>

											<!-- Autosave Interval -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Autosave Interval', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Set the frequency (in seconds) at which WordPress autosaves post changes while editing.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<select name="tka_site_utilities_options[autosave_interval]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border); background: var(--tka-bg-card); color: var(--tka-text-main);">
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

										<!-- SCRIPTS OPTIMIZER CARD -->
										<h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 1.2em; font-weight: 600; border-bottom: 1px solid var(--tka-border); padding-bottom: 8px;"><?php esc_html_e('Script Optimizer', 'tka-site-utilities'); ?></h3>
										<p class="section-desc" style="margin-bottom: 20px;">
											<?php esc_html_e('Defer the loading of useless or non-critical frontend WordPress scripts to improve page speed and Core Web Vitals.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row stack">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Core WordPress Scripts', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Select which commonly enqueued WordPress scripts you want to defer on the frontend.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<div style="margin-bottom: 10px;">
														<button type="button" class="button button-secondary tka-btn-small" id="tka-toggle-all-scripts"><?php esc_html_e('Toggle All', 'tka-site-utilities'); ?></button>
													</div>
													<?php
													$common_scripts = [
														'wp-i18n' => __('wp-i18n (Internationalization)', 'tka-site-utilities'),
														'wp-a11y' => __('wp-a11y (Accessibility)', 'tka-site-utilities'),
														'wp-hooks' => __('wp-hooks (Hooks API)', 'tka-site-utilities'),
														'wp-polyfill' => __('wp-polyfill (Browser Polyfills)', 'tka-site-utilities'),
														'wp-api-fetch' => __('wp-api-fetch (REST API Fetch)', 'tka-site-utilities'),
														'lodash' => __('lodash (Utility Library)', 'tka-site-utilities'),
														'comment-reply' => __('comment-reply (Threaded Comments)', 'tka-site-utilities'),
														'wp-embed' => __('wp-embed (oEmbed Support)', 'tka-site-utilities'),
														'regenerator-runtime' => __('regenerator-runtime (Babel Async)', 'tka-site-utilities'),
														'wp-dom-ready' => __('wp-dom-ready (DOM Ready Utility)', 'tka-site-utilities'),
														'jquery' => __('jquery (jQuery Core Loader)', 'tka-site-utilities'),
														'jquery-core' => __('jquery-core (jQuery Base)', 'tka-site-utilities'),
														'jquery-migrate' => __('jquery-migrate (jQuery Compatibility)', 'tka-site-utilities'),
													];
													$deferred_scripts = $options['deferred_scripts'] ?? [];
													foreach ($common_scripts as $handle => $label) :
														$is_checked = in_array($handle, $deferred_scripts, true);
													?>
														<label class="tka-checkbox-label" style="display:block; margin-bottom:8px;">
															<input type="checkbox" name="tka_site_utilities_options[deferred_scripts][]" value="<?php echo esc_attr($handle); ?>" <?php checked($is_checked); ?>>
															<?php echo esc_html($label); ?>
														</label>
													<?php endforeach; ?>
												</div>
											</div>

											<div class="tka-setting-row stack">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Custom Scripts to Defer', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Enter the handles of any other scripts you want to defer (one per line). These can be third-party plugin scripts or custom theme scripts.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<textarea name="tka_site_utilities_options[deferred_scripts_custom]" rows="5" class="large-text code" placeholder="contact-form-7&#10;woocommerce-general&#10;some-custom-script"><?php echo esc_textarea($options['deferred_scripts_custom'] ?? ''); ?></textarea>
												</div>
											</div>
											
											<div class="tka-setting-row stack" style="background: rgba(46, 204, 113, 0.05); border-left: 4px solid var(--tka-success);">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Aggressively Delay Scripts (Interaction Loader)', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Enter handles of scripts (one per line) that should NOT be loaded at all until the user interacts with the page (scroll, mousemove, touch). This completely hides them from Lighthouse to maximize Core Web Vitals.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<textarea name="tka_site_utilities_options[delayed_scripts_interaction]" rows="5" class="large-text code" placeholder="chat-widget&#10;heavy-slider-script"><?php echo esc_textarea($options['delayed_scripts_interaction'] ?? ''); ?></textarea>
												</div>
											</div>

											<div class="tka-setting-row stack" style="background: rgba(46, 204, 113, 0.05); border-left: 4px solid var(--tka-success);">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Asynchronous CSS (Non-Render Blocking)', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Enter the handles of stylesheets (one per line) that should be loaded asynchronously via the media="print" technique. Perfect for Google Fonts or heavy, non-critical plugin CSS.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<textarea name="tka_site_utilities_options[async_styles]" rows="5" class="large-text code" placeholder="google-fonts-icons&#10;some-heavy-css"><?php echo esc_textarea($options['async_styles'] ?? ''); ?></textarea>
												</div>
											</div>
										</div>
									</section>

									<!-- CONTENT MANAGEMENT PANEL -->
									<section id="panel-content" class="tka-tab-panel">
										<h2><?php esc_html_e('Content Management', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Configure drag-and-drop manual ordering and one-click duplication for your posts and custom post types.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<!-- Draggable sorting toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Drag & Drop Sorting', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Enables sorting posts manually by dragging and dropping rows in the post lists tables.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-order-enabled-toggle"
															name="tka_site_utilities_options[order_enabled]" value="1" <?php checked(1, $options['order_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Order post types selection grid -->
											<div class="tka-setting-row nested-order-post-types"
												style="<?php echo (!empty($options['order_enabled'])) ? 'display: block;' : 'display: none;'; ?>">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Choose Post Types for Drag & Drop Sorting', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Select which post types can be manually ordered.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-checkbox-grid">
													<?php foreach ($public_post_types as $post_type): ?>
															<label class="tka-checkbox-item">
																<input type="checkbox" name="tka_site_utilities_options[order_post_types][]"
																	value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $options['order_post_types'] ?? [], true)); ?>>
																<span><?php echo esc_html($post_type->label); ?> <code
																		class="tka-code-badge"><?php echo esc_html($post_type->name); ?></code></span>
															</label>
													<?php endforeach; ?>
												</div>
											</div>

											<!-- Order taxonomies selection grid -->
											<div class="tka-setting-row nested-order-post-types"
												style="<?php echo (!empty($options['order_enabled'])) ? 'display: block;' : 'display: none;'; ?>">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Choose Taxonomies for Drag & Drop Sorting', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Select which taxonomies (Categories, Tags, etc.) can be manually ordered.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-checkbox-grid">
													<?php foreach ($public_taxonomies as $tax): ?>
															<label class="tka-checkbox-item">
																<input type="checkbox" name="tka_site_utilities_options[order_taxonomies][]"
																	value="<?php echo esc_attr($tax->name); ?>" <?php checked(in_array($tax->name, $options['order_taxonomies'] ?? [], true)); ?>>
																<span><?php echo esc_html($tax->label); ?> <code
																		class="tka-code-badge"><?php echo esc_html($tax->name); ?></code></span>
															</label>
													<?php endforeach; ?>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- Content duplication toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Content Duplication', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Adds a secure "Duplicate" link to row actions inside post list tables to clone any item to a new Draft.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-duplicate-enabled-toggle"
															name="tka_site_utilities_options[duplicate_enabled]" value="1" <?php checked(1, $options['duplicate_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Duplicate post types selection grid -->
											<div class="tka-setting-row nested-duplicate-post-types"
												style="<?php echo (!empty($options['duplicate_enabled'])) ? 'display: block;' : 'display: none;'; ?>">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Choose Post Types for Duplication', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Select which post types can be duplicated.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-checkbox-grid">
													<?php foreach ($public_post_types as $post_type): ?>
															<label class="tka-checkbox-item">
																<input type="checkbox" name="tka_site_utilities_options[duplicate_post_types][]"
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
													<strong><?php esc_html_e('Enable Media Folders', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Adds virtual folders and drag-and-drop file organization inside the WordPress Media Library grid view and selection modals.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-media-folders-enabled-toggle"
															name="tka_site_utilities_options[media_folders_enabled]" value="1" <?php checked(1, $options['media_folders_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- Replace Media toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Replace Media', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Adds a "Replace File" button to the Media Library, allowing you to seamlessly overwrite images and PDFs with a new upload while keeping the exact same URL and attachment ID.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[replace_media_enabled]" value="1" <?php checked(1, $options['replace_media_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>
									</section>

									<!-- DATABASE PANEL -->
									<section id="panel-database" class="tka-tab-panel">
										<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
											<h2 style="margin: 0;"><?php esc_html_e('Database Maintenance', 'tka-site-utilities'); ?></h2>
											<button type="button" class="button button-primary" id="tka-db-clean-all"><?php esc_html_e('Run All Optimizations', 'tka-site-utilities'); ?></button>
										</div>
										<p class="section-desc">
											<?php esc_html_e('Safely clean up your WordPress database, remove orphaned data, and optimize tables to improve performance. Click an action to run it immediately.', 'tka-site-utilities'); ?>
										</p>
										
										<div id="tka-db-messages" style="margin-bottom: 15px;"></div>
										<input type="hidden" id="tka_site_utilities_db_nonce" value="<?php echo esc_attr(wp_create_nonce('tka_site_utilities_db_nonce')); ?>">

										<div class="tka-settings-card">
											<?php
											$db_actions = [
												'revisions' => [ 'label' => __('Post Revisions', 'tka-site-utilities'), 'desc' => __('Old revisions of posts and pages.', 'tka-site-utilities') ],
												'auto_drafts' => [ 'label' => __('Auto-Drafts', 'tka-site-utilities'), 'desc' => __('Orphaned auto-drafts saved while editing.', 'tka-site-utilities') ],
												'trashed_posts' => [ 'label' => __('Trashed Posts', 'tka-site-utilities'), 'desc' => __('Posts and pages currently in the trash.', 'tka-site-utilities') ],
												'spam_comments' => [ 'label' => __('Spam & Trashed Comments', 'tka-site-utilities'), 'desc' => __('Comments marked as spam or in the trash.', 'tka-site-utilities') ],
												'orphan_postmeta' => [ 'label' => __('Orphaned Post Meta', 'tka-site-utilities'), 'desc' => __('Meta data for posts that no longer exist.', 'tka-site-utilities') ],
												'orphan_commentmeta' => [ 'label' => __('Orphaned Comment Meta', 'tka-site-utilities'), 'desc' => __('Meta data for comments that no longer exist.', 'tka-site-utilities') ],
												'expired_transients' => [ 'label' => __('Expired Transients', 'tka-site-utilities'), 'desc' => __('Temporary cached options that have expired.', 'tka-site-utilities') ],
												'optimize_tables' => [ 'label' => __('Optimize Tables', 'tka-site-utilities'), 'desc' => __('Reclaim unused space and defragment data files.', 'tka-site-utilities') ],
												'postmeta_index' => [ 'label' => __('PostMeta Optimization Index', 'tka-site-utilities'), 'desc' => __('Toggle a custom composite index to speed up complex meta_query searches. Recommended for WooCommerce or heavy filtering plugins.', 'tka-site-utilities') ],
											];
											
											foreach ($db_actions as $key => $action) :
											?>
											<div class="tka-setting-row" style="align-items: center;">
												<div class="tka-setting-label">
													<strong><?php echo esc_html($action['label']); ?></strong>
													<p><?php echo esc_html($action['desc']); ?></p>
												</div>
												<div class="tka-setting-control" style="text-align: right; min-width: 200px;">
													<?php if ($key !== 'optimize_tables') : ?>
														<span class="tka-db-count" id="tka-db-count-<?php echo esc_attr($key); ?>" style="display:inline-block; margin-right: 15px; font-weight: bold; color: var(--tka-text-muted);">
															<span class="spinner is-active" style="float:none; margin:0;"></span>
														</span>
													<?php endif; ?>
													<button type="button" class="button button-secondary tka-db-clean-btn" data-action="<?php echo esc_attr($key); ?>">
														<?php 
															if ($key === 'optimize_tables') {
																esc_html_e('Optimize', 'tka-site-utilities');
															} elseif ($key === 'postmeta_index') {
																esc_html_e('Toggle Index', 'tka-site-utilities');
															} else {
																esc_html_e('Clean', 'tka-site-utilities');
															}
														?>
													</button>
												</div>
											</div>
											<?php endforeach; ?>
										</div>

										<h3 style="margin-top: 30px; margin-bottom: 15px; font-size: 1.2em; font-weight: 600; border-bottom: 1px solid var(--tka-border); padding-bottom: 8px;"><?php esc_html_e('Search & Replace (WP-CLI)', 'tka-site-utilities'); ?></h3>
										<p class="section-desc" style="margin-bottom: 20px;">
											<?php esc_html_e('Safely search and replace strings across your entire database. Serialized data (like arrays or objects) is natively preserved. A database backup is automatically created unless performing a dry run.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Show Search & Replace Utility', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Reveal the inputs to perform a search and replace operation on the database.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-sr-enabled-toggle" value="1">
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="nested-search-replace" style="display: none;">
												<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 0 0 20px 0;">
												
												<div class="tka-setting-row" style="display: flex; gap: 20px; border-bottom: 1px solid var(--tka-border); padding-bottom: 20px; margin-bottom: 20px;">
													<div style="flex: 1;">
														<div class="tka-setting-label" style="margin-bottom: 8px;">
															<strong><?php esc_html_e('Search String', 'tka-site-utilities'); ?></strong>
														</div>
														<input type="text" id="tka-sr-search" class="regular-text" placeholder="http://old-url.local" style="width: 100%; max-width: none;">
													</div>
													<div style="flex: 1;">
														<div class="tka-setting-label" style="margin-bottom: 8px;">
															<strong><?php esc_html_e('Replace String', 'tka-site-utilities'); ?></strong>
														</div>
														<input type="text" id="tka-sr-replace" class="regular-text" placeholder="https://new-url.com" style="width: 100%; max-width: none;">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Dry Run', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('Simulate the replacement without actually modifying the database. Highly recommended for testing first.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<label class="tka-switch">
															<input type="checkbox" id="tka-sr-dry-run" value="1" checked>
															<span class="tka-slider"></span>
														</label>
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-control" style="width: 100%;">
														<button type="button" class="button button-primary" id="tka-sr-btn" style="width: 100%; text-align: center;">
															<?php esc_html_e('Run Search & Replace', 'tka-site-utilities'); ?>
														</button>
													</div>
												</div>

												<!-- Results Area -->
												<div id="tka-sr-results" style="display: none; margin-top: 15px; padding: 15px; background: var(--tka-bg-main); border: 1px solid var(--tka-border); border-radius: 6px;">
													<h4 style="margin-top: 0; margin-bottom: 10px;"><?php esc_html_e('Output', 'tka-site-utilities'); ?></h4>
													<div id="tka-sr-backup-msg" style="margin-bottom: 10px; font-weight: 600; color: var(--tka-primary);"></div>
													<div id="tka-sr-output" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;"></div>
												</div>
											</div>
										</div>
									</section>

									<?php if (AdminInterface::isCurrentUserInstaller()): ?>
											<!-- ADMIN INTERFACE PANEL -->
											<section id="panel-admin-interface" class="tka-tab-panel">
												<h2><?php esc_html_e('Admin Interface Customization', 'tka-site-utilities'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('Selectively hide sidebar menu items, help screen options, and dashboard admin notices from other administrators. These do not affect your account.', 'tka-site-utilities'); ?>
												</p>

												<div class="tka-settings-card" style="margin-bottom: 20px;">
													<div class="tka-setting-row stack">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Assign Superadmins', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Select other administrators who should have full Superadmin privileges over this plugin (including accessing these settings).', 'tka-site-utilities'); ?></p>
														</div>
														<div class="tka-setting-control">
															<?php
															$installer_id = \TKA\WPUtils\Features\AdminInterface::getInstallerId();
															$all_admins = get_users(['role' => 'administrator']);
															$current_superadmins = $options['superadmin_users'] ?? [];
															
															if (empty($all_admins) || (count($all_admins) === 1 && $all_admins[0]->ID === $installer_id)) {
																echo '<p style="color: #64748b; font-style: italic;">' . esc_html__('No other administrators found on this site.', 'tka-site-utilities') . '</p>';
															} else {
																echo '<div style="display: flex; flex-direction: column; gap: 8px;">';
																foreach ($all_admins as $admin) {
																	if ($admin->ID === $installer_id) {
																		// Original installer is always superadmin
																		echo '<label style="color: #64748b; cursor: not-allowed;"><input type="checkbox" checked disabled> ' . esc_html($admin->user_login) . ' (Original Installer)</label>';
																	} else {
																		$is_checked = in_array($admin->ID, $current_superadmins, true);
																		echo '<label><input type="checkbox" name="tka_site_utilities_options[superadmin_users][]" value="' . esc_attr($admin->ID) . '" ' . checked($is_checked, true, false) . '> ' . esc_html($admin->user_login) . ' (' . esc_html($admin->user_email) . ')</label>';
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
															<strong><?php esc_html_e('Hide Help & Screen Options Tabs', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Removes the "Help" and "Screen Options" tabs from the top-right of admin pages for other administrators.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[hide_help_screen_options]"
																	value="1" <?php checked(1, $options['hide_help_screen_options'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Command Palette', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Disables the WordPress Command Palette (Cmd/Ctrl+K shortcut) for other administrators.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[disable_command_palette]" value="1"
																	<?php checked(1, $options['disable_command_palette'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Hide Dashboard Admin Notices', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Hides update warning notifications, notices, and nagging alerts in the admin panel for other administrators.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[hide_admin_notices]" value="1"
																	<?php checked(1, $options['hide_admin_notices'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Remove Footer Text & WP Version', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Removes the default "Thank you for creating with WordPress." and version number from the footer for other administrators.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[remove_footer_text]" value="1"
																	<?php checked(1, $options['remove_footer_text'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

													<!-- Clean Up Admin Bar Checkboxes -->
													<div class="tka-setting-row stack">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Clean Up Admin Bar', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Selectively remove nodes from the top administration bar for other administrators.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-checkbox-grid">
															<?php
															$admin_bar_items = [
																'wp-logo' => __('Remove WordPress Logo/Menu', 'tka-site-utilities'),
																'site-name' => __('Remove Home Icon & Site Name', 'tka-site-utilities'),
																'customize' => __('Remove Customize Menu', 'tka-site-utilities'),
																'updates' => __('Remove Updates Link/Counter', 'tka-site-utilities'),
																'comments' => __('Remove Comments Link/Counter', 'tka-site-utilities'),
																'new-content' => __('Remove New Content Menu', 'tka-site-utilities'),
																'howdy' => __('Remove "Howdy" Greeting', 'tka-site-utilities'),
															];
															foreach ($admin_bar_items as $item_id => $item_label): ?>
																	<label class="tka-checkbox-item">
																		<input type="checkbox" name="tka_site_utilities_options[admin_bar_cleanup][]"
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
															<strong><?php esc_html_e('Disable Dashboard Widgets', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Selectively disable widgets on the WordPress dashboard for other administrators.', 'tka-site-utilities'); ?>
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
																'dashboard_right_now' => __('At a Glance', 'tka-site-utilities'),
																'dashboard_activity' => __('Activity', 'tka-site-utilities'),
																'dashboard_quick_press' => __('Quick Draft', 'tka-site-utilities'),
																'dashboard_primary' => __('WordPress Events and News', 'tka-site-utilities'),
																'dashboard_site_health' => __('Site Health Status', 'tka-site-utilities'),
															];
															
															$available_widgets = array_merge($core_widgets, $available_widgets);

															foreach ($available_widgets as $widget_id => $widget_label): ?>
																	<label class="tka-checkbox-item">
																		<input type="checkbox" name="tka_site_utilities_options[disabled_dashboard_widgets][]"
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
												<h2><?php esc_html_e('Design Customization', 'tka-site-utilities'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('White-label the wp-login page and backend toolbar with custom brand identity.', 'tka-site-utilities'); ?>
												</p>

												<div class="tka-settings-card">
													<!-- 1. Custom Login Logo Upload -->
													<div class="tka-setting-row stack" style="width: 100%;">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('wp-login.php Page Logo', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Upload or select a custom logo to replace the default WordPress logo on the login page.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-image-upload-control" style="width: 100%; margin-top: 10px;">
															<input type="text" class="regular-text tka-logo-input"
																name="tka_site_utilities_options[login_logo]"
																value="<?php echo esc_url($options['login_logo'] ?? ''); ?>"
																style="width: 70%; display: inline-block; vertical-align: middle;">
															<button type="button" class="button tka-upload-btn"
																style="vertical-align: middle; margin-left: 10px;"><?php esc_html_e('Select Image', 'tka-site-utilities'); ?></button>
															<button type="button" class="button tka-remove-btn"
																style="vertical-align: middle; margin-left: 5px; color: var(--tka-danger);"><?php esc_html_e('Clear', 'tka-site-utilities'); ?></button>
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
															<strong><?php esc_html_e('Admin Bar Replacement Logo', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Upload or select a small custom logo (square/transparent icon works best) to replace the WordPress logo in the top admin toolbar.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-image-upload-control" style="width: 100%; margin-top: 10px;">
															<input type="text" class="regular-text tka-logo-input"
																name="tka_site_utilities_options[admin_logo]"
																value="<?php echo esc_url($options['admin_logo'] ?? ''); ?>"
																style="width: 70%; display: inline-block; vertical-align: middle;">
															<button type="button" class="button tka-upload-btn"
																style="vertical-align: middle; margin-left: 10px;"><?php esc_html_e('Select Image', 'tka-site-utilities'); ?></button>
															<button type="button" class="button tka-remove-btn"
																style="vertical-align: middle; margin-left: 5px; color: var(--tka-danger);"><?php esc_html_e('Clear', 'tka-site-utilities'); ?></button>
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
															<strong><?php esc_html_e('Custom Login CSS', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Enter custom CSS rules to customize the login screen aesthetics (background color, buttons, typography). This CSS applies exclusively to the login screen.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<textarea name="tka_site_utilities_options[login_custom_css]" class="large-text code" rows="6"
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
												<h2><?php esc_html_e('Advanced Custom Fields (ACF) Integration', 'tka-site-utilities'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('Optimize and secure your ACF setup for client-facing websites.', 'tka-site-utilities'); ?>
												</p>
 
												<div class="tka-settings-card">
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Hide Custom Fields Admin Menu', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Hides the "Custom Fields" sidebar menu item for all administrators except the plugin installer (developer). Keeps your ACF schemas safe from client edits.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[hide_acf_menu]" value="1"
																	<?php checked(1, $options['hide_acf_menu'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable [acf] Shortcode', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Disables front-end execution of the legacy `[acf]` shortcode. This is a vital security hardening practice to prevent unauthorized data exposure of raw database values.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[disable_acf_shortcode]"
																	value="1" <?php checked(1, $options['disable_acf_shortcode'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Auto-Inject Video Poster Field', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Automatically registers a "Video Poster Image" ACF field to all video attachments in the Media Library, allowing you to easily assign fallback cover images to MP4 uploads.', 'tka-site-utilities'); ?>
																<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Field Name:', 'tka-site-utilities'); ?> <code>video_poster_image</code></span>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[acf_video_poster]"
																	value="1" <?php checked(1, $options['acf_video_poster'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Theme-Independent Local JSON', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Saves and loads ACF field groups in a shared, theme-independent directory (`/wp-content/acf-json/`) instead of the active theme folder. Prevents accidental field group loss during theme updates or switches.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[acf_custom_json_path]"
																	value="1" <?php checked(1, $options['acf_custom_json_path'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row" <?php if ($acfe_active): ?>style="opacity: 0.6;"<?php endif; ?>>
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable Flexible Layout Copy & Paste', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Adds Copy/Paste buttons and selection checkboxes to each Flexible Content layout in the WordPress editor. Copied blocks can be bulk pasted across fields and posts.', 'tka-site-utilities'); ?>
																<?php if ($acfe_active): ?>
																		<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Note: This feature is managed by ACF Extended.', 'tka-site-utilities'); ?></span>
																<?php endif; ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[acf_copy_paste]" value="1"
																	<?php checked(1, $options['acf_copy_paste'] ?? 0); ?> 			<?php if ($acfe_active): ?>disabled<?php endif; ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row" <?php if ($acfe_active): ?>style="opacity: 0.6;"<?php endif; ?>>
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Flexible Layout Selection Modal', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Replaces the default ACF Flexible Content "Add Row" dropdown list with a beautiful, searchable modal overlay supporting visual previews and category filtering.', 'tka-site-utilities'); ?>
																<?php if ($acfe_active): ?>
																		<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Note: This feature is managed by ACF Extended.', 'tka-site-utilities'); ?></span>
																<?php endif; ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[acf_layout_modal]" value="1"
																	<?php checked(1, $options['acf_layout_modal'] ?? 0); ?> 			<?php if ($acfe_active): ?>disabled<?php endif; ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row" <?php if ($acfe_active): ?>style="opacity: 0.6;"<?php endif; ?>>
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable Flexible Layout Toggle (Visibility)', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Adds a visibility toggle button (eye icon) to each layout block in the ACF Field Group editor. Allows developers to disable individual layouts globally, hiding them from post editors and frontend output.', 'tka-site-utilities'); ?>
																<?php if ($acfe_active): ?>
																		<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Note: This feature is managed by ACF Extended.', 'tka-site-utilities'); ?></span>
																<?php endif; ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[acf_layout_toggle]" value="1"
																	<?php checked(1, $options['acf_layout_toggle'] ?? 0); ?> 			<?php if ($acfe_active): ?>disabled<?php endif; ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
 
													<div class="tka-setting-row" <?php if ($acfe_active): ?>style="opacity: 0.6;"<?php endif; ?>>
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable Layout Click-to-Rename Hijack', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Allows editors to rename flexible layout blocks directly by clicking on their title text inside the post editor, bypassing the need to open the action menu (three dots dropdown).', 'tka-site-utilities'); ?>
																<?php if ($acfe_active): ?>
																		<br><span style="color: var(--tka-primary); font-weight: 600;"><?php esc_html_e('Note: This feature is managed by ACF Extended.', 'tka-site-utilities'); ?></span>
																<?php endif; ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[acf_layout_rename]" value="1"
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
																<strong><?php esc_html_e('ACF Custom Field Extensions', 'tka-site-utilities'); ?></strong>
																<p><?php esc_html_e('Toggle dynamically discovered custom field extensions. Drop new PHP files in /includes/AcfExtensions/ to register them.', 'tka-site-utilities'); ?>
																</p>
															</div>
															<div class="tka-checkbox-grid">
																<?php foreach ($acf_extensions as $filename => $ext): ?>
																	<label class="tka-checkbox-item">
																		<input type="checkbox" name="tka_site_utilities_options[acf_extensions][]"
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
										<h2><?php esc_html_e('Image Optimization & WebP Engine', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Convert newly uploaded JPEGs and PNGs to next-generation WebP format and compress assets in-place to save maximum disk storage and load websites lightning fast.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<!-- Global Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Image Optimization Features', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Master switch to enable enqueues, compression quality rules, and upload filters.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[image_optimization_enabled]" value="1"
															<?php checked(1, $options['image_optimization_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- WebP Conversion Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Convert Uploads to WebP', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Automatically converts uploaded JPEG and PNG image formats into highly efficient WebP files.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[webp_conversion_enabled]" value="1"
															<?php checked(1, $options['webp_conversion_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- WebP Keep Original Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Keep Original JPEGs / PNGs', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Retain the original files on the server folder after converting them to WebP. Turn off to delete original uploads and save absolute maximum disk space.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[webp_keep_original]" value="1"
															<?php checked(1, $options['webp_keep_original'] ?? 1); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Compress Original Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Optimize & Compress Original Uploads', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Compresses original full-size JPEGs and PNGs directly upon upload, rather than just compressing downscaled sub-sizes.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[compress_original_images]" value="1"
															<?php checked(1, $options['compress_original_images'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Strip Metadata Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Strip EXIF Image Metadata', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Automatically strips all camera profiles, copyright tags, and GPS coordinates from images upon upload to safeguard privacy and save size.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[strip_image_metadata]" value="1"
															<?php checked(1, $options['strip_image_metadata'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

											<!-- Compression Quality Slider -->
											<div class="tka-setting-row stack" style="border-bottom: none; padding-bottom: 0;">
												<div class="tka-setting-label" style="max-width: 100%;">
													<strong><?php esc_html_e('Image Compression Quality', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Select target compression quality. Lower quality yields smaller files, while higher quality yields sharp detail (82% is recommended).', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div style="width: 100%; display: flex; align-items: center; gap: 15px; margin-top: 10px;">
													<input type="range" id="tka-image-quality-slider" name="tka_site_utilities_options[image_compression_quality]"
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
												<?php esc_html_e('Server Compatibility Check', 'tka-site-utilities'); ?>
											</h3>
											<ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
												<li style="display: flex; justify-content: space-between; align-items: center;">
													<span style="color: var(--tka-text-muted);"><?php esc_html_e('PHP GD WebP Support:', 'tka-site-utilities'); ?></span>
													<strong>
														<?php if (function_exists('imagewebp')): ?>
																<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Enabled', 'tka-site-utilities'); ?></span>
														<?php else: ?>
																<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Disabled', 'tka-site-utilities'); ?></span>
														<?php endif; ?>
													</strong>
												</li>
												<li style="display: flex; justify-content: space-between; align-items: center;">
													<span style="color: var(--tka-text-muted);"><?php esc_html_e('PHP Imagick WebP Support:', 'tka-site-utilities'); ?></span>
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
																<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Enabled', 'tka-site-utilities'); ?></span>
														<?php else: ?>
																<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Disabled', 'tka-site-utilities'); ?></span>
														<?php endif; ?>
													</strong>
												</li>
												<li style="display: flex; justify-content: space-between; align-items: center;">
													<span style="color: var(--tka-text-muted);"><?php esc_html_e('WordPress Engine Native WebP Upload:', 'tka-site-utilities'); ?></span>
													<strong>
														<?php if (wp_image_editor_supports(array('mime_type' => 'image/webp'))): ?>
																<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Supported', 'tka-site-utilities'); ?></span>
														<?php else: ?>
																<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> <?php esc_html_e('Unsupported', 'tka-site-utilities'); ?></span>
														<?php endif; ?>
													</strong>
												</li>
											</ul>
										</div>
									</section>

									<?php if (class_exists('GFCommon')): ?>
											<!-- GRAVITY FORMS PANEL -->
											<section id="panel-gravity-forms" class="tka-tab-panel">
												<h2><?php esc_html_e('Gravity Forms Integrations & Enhancements', 'tka-site-utilities'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('Customize and optimize your Gravity Forms workflows. Clean up markup, disable default styling, and prevent double form submissions.', 'tka-site-utilities'); ?>
												</p>

												<div class="tka-settings-card">
													<!-- Disable GF CSS Toggle -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Default CSS completely', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Blocks all built-in Gravity Forms styles and stylesheets from loading on the frontend. Highly recommended when building custom themes or using Tailwind/bootstrap styles.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[gf_disable_css]" value="1"
																	<?php checked(1, $options['gf_disable_css'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Gravity Forms CWV Optimizer -->
													<div class="tka-setting-row" style="background: rgba(46, 204, 113, 0.05); border-left: 4px solid var(--tka-success);">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Core Web Vitals Optimizer (99 Score)', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Completely eliminates the Gravity Forms Lighthouse penalty by aggressively delaying all GF/jQuery Javascript until user interaction (scroll/mouse movement) and rendering CSS asynchronously. Restores high FCP and eliminates Unused JS warnings.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[gf_optimize_cwv]" value="1"
																	<?php checked(1, $options['gf_optimize_cwv'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Change Submit Input to Button Toggle -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Convert Submit Input to Button', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Converts standard <input type="submit"> tags into modern <button type="submit"> elements, allowing for much better flex/grid layout control and pseudo-elements.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[gf_submit_button_to_button]" value="1"
																	<?php checked(1, $options['gf_submit_button_to_button'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Change Submit Button Text on Click Toggle -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable Button Submit Loading Text', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Temporarily changes the button text to a loading feedback string (e.g. "Sending...") upon form submit to prevent multiple clicks and double submissions.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" id="tka-gf-text-change-toggle" name="tka_site_utilities_options[gf_submit_button_text_change]" value="1"
																	<?php checked(1, $options['gf_submit_button_text_change'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Loading text input field -->
													<div class="tka-setting-row nested-gf-loading-text"
														style="<?php echo (!empty($options['gf_submit_button_text_change'])) ? 'display: block;' : 'display: none;'; ?>">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Custom Submit Loading Text', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Specify the custom text to display during form submission.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<input type="text" name="tka_site_utilities_options[gf_submit_button_loading_text]"
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
												<h2><?php esc_html_e('WooCommerce Speed & Bloat Settings', 'tka-site-utilities'); ?></h2>
												<p class="section-desc">
													<?php esc_html_e('Optimize your WooCommerce store frontend load speed and declutter the admin dashboard.', 'tka-site-utilities'); ?>
												</p>

												<div class="tka-settings-card">
													<!-- Disable scripts/styles non-shop pages -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Scripts & Styles on Non-Shop Pages', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Dequeues WooCommerce frontend styles and scripts on non-shop pages like homepage, blog posts, and custom pages to optimize asset payload size.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_disable_scripts_non_wc]" value="1"
																	<?php checked(1, $options['wc_disable_scripts_non_wc'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Cart Fragments AJAX mode -->
													<div class="tka-setting-row stack">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Cart Fragments AJAX', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Choose how to restrict the AJAX cart fragments request on the frontend.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control-radios">
															<label class="tka-radio-card">
																<input type="radio" name="tka_site_utilities_options[wc_disable_cart_fragments]" value="none"
																	<?php checked('none', $options['wc_disable_cart_fragments'] ?? 'none'); ?>>
																<div class="radio-card-content">
																	<strong><?php esc_html_e('Keep Active', 'tka-site-utilities'); ?></strong>
																	<span><?php esc_html_e('Default WooCommerce cart updating behaviour.', 'tka-site-utilities'); ?></span>
																</div>
															</label>

															<label class="tka-radio-card">
																<input type="radio" name="tka_site_utilities_options[wc_disable_cart_fragments]" value="all"
																	<?php checked('all', $options['wc_disable_cart_fragments'] ?? 'none'); ?>>
																<div class="radio-card-content">
																	<strong><?php esc_html_e('Disable Completely', 'tka-site-utilities'); ?></strong>
																	<span><?php esc_html_e('Globally block AJAX cart fragments (maximum performance).', 'tka-site-utilities'); ?></span>
																</div>
															</label>

															<label class="tka-radio-card">
																<input type="radio" name="tka_site_utilities_options[wc_disable_cart_fragments]" value="non_shop"
																	<?php checked('non_shop', $options['wc_disable_cart_fragments'] ?? 'none'); ?>>
																<div class="radio-card-content">
																	<strong><?php esc_html_e('Disable on Non-Shop Pages', 'tka-site-utilities'); ?></strong>
																	<span><?php esc_html_e('Keep fragments enabled on shop pages/checkout; block them everywhere else.', 'tka-site-utilities'); ?></span>
																</div>
															</label>
														</div>
													</div>

													<!-- Disable block styles -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Gutenberg Block Styles', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Blocks WooCommerce built-in blocks layout styles from loading on the frontend. Recommended if your theme does not use WooCommerce blocks.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_disable_block_styles]" value="1"
																	<?php checked(1, $options['wc_disable_block_styles'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Disable password strength meter -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Password Strength Meter JS', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Dequeues zxcvbn.min.js and the password strength meter on checkout/my-account pages to save up to 400KB of page weight.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_disable_password_meter]" value="1"
																	<?php checked(1, $options['wc_disable_password_meter'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Clean Admin UI -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Clean WooCommerce Admin UI & Nags', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Hides the WooCommerce marketing menu tab, removes status widgets from the dashboard, and disables marketplace suggestions.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_clean_admin_ui]" value="1"
																	<?php checked(1, $options['wc_clean_admin_ui'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>
												</div>

												<div class="tka-settings-card" style="margin-top: 20px;">
													<h3><?php esc_html_e('WooCommerce Helpers & Extras', 'tka-site-utilities'); ?></h3>
													<p class="card-desc" style="margin-bottom: 20px; color: var(--tka-text-muted);">
														<?php esc_html_e('Configure helpful tweaks and extra features for your WooCommerce shop.', 'tka-site-utilities'); ?>
													</p>

													<!-- Buy Now Button -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Enable "Buy Now" Button', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Adds a direct checkout button on single product pages, bypassing the cart page.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_buy_now_button]" value="1"
																	<?php checked(1, $options['wc_buy_now_button'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Redirect SKU to Product -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Redirect SKU to Product Page', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Automatically redirects users typing or requesting a product SKU in the URL (e.g. /your-sku/) to that product page.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_redirect_sku]" value="1"
																	<?php checked(1, $options['wc_redirect_sku'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Remove add-to-cart from URL -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Remove "add-to-cart" From URL', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Redirects the user back to the referrer page after adding a product to cart, removing the add-to-cart query string from the URL.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_remove_add_to_cart_from_url]" value="1"
																	<?php checked(1, $options['wc_remove_add_to_cart_from_url'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Hide View Cart on Shop -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Hide "View Cart" Link on Shop', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Hides the AJAX-injected "View Cart" secondary link after adding a product to cart on the shop archive pages.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_hide_view_cart_shop]" value="1"
																	<?php checked(1, $options['wc_hide_view_cart_shop'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Plus/Minus quantity buttons -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Plus/Minus Quantity Buttons', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Injects interactive "-" and "+" buttons around the product quantity inputs on single product pages.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_plus_minus_quantity]" value="1"
																	<?php checked(1, $options['wc_plus_minus_quantity'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Quantity dropdown -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Quantity Dropdown Select', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Converts the standard numeric input field for quantity into a select dropdown (max value limit 20).', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wc_quantity_dropdown]" value="1"
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
										<h2><?php esc_html_e('Maintenance Mode Settings', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Take your site offline temporarily for scheduled maintenance while showing a beautiful message to visitors.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Maintenance Mode', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('When active, non-logged-in visitors will be redirected to a temporary maintenance page with a 503 Service Unavailable status.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[maintenance_enabled]" value="1"
															<?php checked(1, $options['maintenance_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Maintenance Page Title', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Enter the title to display on the maintenance screen.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<input type="text" name="tka_site_utilities_options[maintenance_title]"
														value="<?php echo esc_attr($options['maintenance_title'] ?? __('Under Maintenance', 'tka-site-utilities')); ?>"
														class="regular-text" style="width: 100%; max-width: 400px; border-radius: 8px; padding: 8px 12px; border-color: var(--tka-border);">
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Maintenance Message', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Enter a descriptive message for visitors.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<textarea name="tka_site_utilities_options[maintenance_message]" rows="5" class="large-text"
														style="width: 100%; max-width: 400px; border-radius: 6px; padding: 8px 12px; border-color: var(--tka-border);"><?php echo esc_textarea($options['maintenance_message'] ?? 'Our website is currently undergoing scheduled maintenance. We will be back shortly. Thank you for your patience!'); ?></textarea>
												</div>
											</div>

											<!-- Logo Upload -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Maintenance Page Logo', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Upload or select a custom logo to display on the maintenance screen.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<div class="tka-image-upload-control">
														<input type="text" name="tka_site_utilities_options[maintenance_logo]"
															class="tka-logo-input" value="<?php echo esc_url($options['maintenance_logo'] ?? ''); ?>" style="display:none;">
														<div class="tka-logo-preview" style="margin-bottom: 10px;">
															<?php if (!empty($options['maintenance_logo'])): ?>
																	<img src="<?php echo esc_url($options['maintenance_logo']); ?>"
																		style="max-height: 80px; display: block; border-radius: 4px; border: 1px solid var(--tka-border);">
															<?php endif; ?>
														</div>
														<button class="button tka-upload-btn"><?php esc_html_e('Choose Image', 'tka-site-utilities'); ?></button>
														<button class="button tka-remove-btn"><?php esc_html_e('Remove', 'tka-site-utilities'); ?></button>
													</div>
												</div>
											</div>

											<!-- Background Image Upload -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Maintenance Page Background', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Upload or select a background image for the maintenance screen.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<div class="tka-image-upload-control">
														<input type="text" name="tka_site_utilities_options[maintenance_background]"
															class="tka-logo-input" value="<?php echo esc_url($options['maintenance_background'] ?? ''); ?>" style="display:none;">
														<div class="tka-logo-preview" style="margin-bottom: 10px;">
															<?php if (!empty($options['maintenance_background'])): ?>
																	<img src="<?php echo esc_url($options['maintenance_background']); ?>"
																		style="max-height: 80px; display: block; border-radius: 4px; border: 1px solid var(--tka-border);">
															<?php endif; ?>
														</div>
														<button class="button tka-upload-btn"><?php esc_html_e('Choose Image', 'tka-site-utilities'); ?></button>
														<button class="button tka-remove-btn"><?php esc_html_e('Remove', 'tka-site-utilities'); ?></button>
													</div>
												</div>
											</div>
										</div>
									</section>

									<!-- PAGE TRANSITIONS PANEL -->
									<section id="panel-transitions" class="tka-tab-panel">
										<h2><?php esc_html_e('Page Transitions (View Transitions)', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Enable smooth, modern cross-document view transitions for seamless frontend navigation.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<!-- Global Toggle -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Page Transitions', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Master switch to enable enqueues, scripts, and transition styling on the frontend.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" id="tka-page-transitions-enabled-toggle" name="tka_site_utilities_options[page_transitions_enabled]" value="1"
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
														<strong><?php esc_html_e('Override Theme Configuration', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('Force-apply the selectors and settings below, overriding any defaults provided by the theme.', 'tka-site-utilities'); ?>
														</p>
													</div>
													<div class="tka-setting-control">
														<label class="tka-switch">
															<input type="checkbox" name="tka_site_utilities_options[page_transitions_override_theme]" value="1"
																<?php checked(1, $options['page_transitions_override_theme'] ?? 0); ?>>
															<span class="tka-slider"></span>
														</label>
													</div>
												</div>

												<!-- Default Transition Animation Select -->
												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Default Transition Animation', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('Choose the default animation used when no custom rules match.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<select name="tka_site_utilities_options[page_transitions_default_animation]" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
															<?php
															$animations_labels = [
																'fade' => __('Fade (default)', 'tka-site-utilities'),
																'slide-from-right' => __('Slide (from right)', 'tka-site-utilities'),
																'slide-from-left' => __('Slide (from left)', 'tka-site-utilities'),
																'slide-from-bottom' => __('Slide (from bottom)', 'tka-site-utilities'),
																'slide-from-top' => __('Slide (from top)', 'tka-site-utilities'),
																'swipe-from-right' => __('Swipe (from right)', 'tka-site-utilities'),
																'swipe-from-left' => __('Swipe (from left)', 'tka-site-utilities'),
																'swipe-from-bottom' => __('Swipe (from bottom)', 'tka-site-utilities'),
																'swipe-from-top' => __('Swipe (from top)', 'tka-site-utilities'),
																'wipe-from-right' => __('Wipe (from right)', 'tka-site-utilities'),
																'wipe-from-left' => __('Wipe (from left)', 'tka-site-utilities'),
																'wipe-from-bottom' => __('Wipe (from bottom)', 'tka-site-utilities'),
																'wipe-from-top' => __('Wipe (from top)', 'tka-site-utilities'),
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
														<strong><?php esc_html_e('Transition Animation Duration', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('Control the duration of the transition in milliseconds (default: 400).', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="number" name="tka_site_utilities_options[page_transitions_default_animation_duration]"
															value="<?php echo esc_attr($options['page_transitions_default_animation_duration'] ?? 400); ?>"
															class="regular-text" style="width: 100px; border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<!-- Selector Fields -->
												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Header Selector', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('CSS selector for the global header element.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_site_utilities_options[page_transitions_header_selector]"
															value="<?php echo esc_attr($options['page_transitions_header_selector'] ?? 'header'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Main Selector', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('CSS selector for the global main element.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_site_utilities_options[page_transitions_main_selector]"
															value="<?php echo esc_attr($options['page_transitions_main_selector'] ?? 'main'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Post Title Selector', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('CSS selector for the post title element.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_site_utilities_options[page_transitions_post_title_selector]"
															value="<?php echo esc_attr($options['page_transitions_post_title_selector'] ?? '.wp-block-post-title, .entry-title'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Post Thumbnail Selector', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('CSS selector for the post thumbnail image.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_site_utilities_options[page_transitions_post_thumbnail_selector]"
															value="<?php echo esc_attr($options['page_transitions_post_thumbnail_selector'] ?? '.wp-post-image'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Post Content Selector', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('CSS selector for the post content block.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<input type="text" name="tka_site_utilities_options[page_transitions_post_content_selector]"
															value="<?php echo esc_attr($options['page_transitions_post_content_selector'] ?? '.wp-block-post-content, .entry-content'); ?>"
															class="regular-text" style="border-radius: 8px; padding: 6px 12px; border-color: var(--tka-border);">
													</div>
												</div>

												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('WP Admin Transitions', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('Enable transitions inside the WordPress administration dashboard.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-setting-control">
														<label class="tka-switch">
															<input type="checkbox" name="tka_site_utilities_options[page_transitions_enable_admin]" value="1"
																<?php checked(1, $options['page_transitions_enable_admin'] ?? 0); ?>>
															<span class="tka-slider"></span>
														</label>
													</div>
												</div>

												<!-- CUSTOM TRANSITION RULES -->
												<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">
												<div class="tka-setting-row stack" style="width: 100%;">
													<div class="tka-setting-label" style="max-width: 100%;">
														<strong><?php esc_html_e('Custom Navigation Rules', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('Define target animations for specific source and destination navigations.', 'tka-site-utilities'); ?></p>
													</div>
													<div class="tka-transitions-rules-manager" style="width: 100%; margin-top: 10px;">
														<div class="tka-rules-header-row" style="display: flex; gap: 15px; padding: 8px 10px; background: var(--tka-bg-main); font-weight: 700; font-size: 12px; text-transform: uppercase; color: var(--tka-text-muted); border-radius: 6px; border: 1px solid var(--tka-border); margin-bottom: 10px;">
															<div style="flex: 2;"><?php esc_html_e('From Page Type / URL Pattern', 'tka-site-utilities'); ?></div>
															<div style="flex: 2;"><?php esc_html_e('To Page Type / URL Pattern', 'tka-site-utilities'); ?></div>
															<div style="flex: 1.5;"><?php esc_html_e('Animation', 'tka-site-utilities'); ?></div>
															<div style="flex: 1.5;"><?php esc_html_e('Custom HTML Class', 'tka-site-utilities'); ?></div>
															<div style="width: 40px;"></div>
														</div>
														<div id="tka-transitions-rules-list" style="display: flex; flex-direction: column; gap: 10px;">
															<?php
															$rules = $options['page_transitions_rules'] ?? [];
															$page_types = [
																'any' => __('Any Page', 'tka-site-utilities'),
																'home' => __('Homepage', 'tka-site-utilities'),
																'archive' => __('Archive/Category', 'tka-site-utilities'),
																'single_post' => __('Single Post', 'tka-site-utilities'),
																'single_page' => __('Single Page', 'tka-site-utilities'),
																'custom_url' => __('Custom URL Pattern...', 'tka-site-utilities'),
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
																			<select name="tka_site_utilities_options[page_transitions_rules][<?php echo intval($rule_index); ?>][from_type]" class="tka-rule-from-select" style="width: 100%; padding: 5px; border-radius: 6px;">
																				<?php foreach ($page_types as $val => $lbl): ?>
																						<option value="<?php echo esc_attr($val); ?>" <?php selected($from_type, $val); ?>><?php echo esc_html($lbl); ?></option>
																				<?php endforeach; ?>
																			</select>
																			<input type="text" name="tka_site_utilities_options[page_transitions_rules][<?php echo intval($rule_index); ?>][from_url]" value="<?php echo esc_attr($rule['from_url'] ?? ''); ?>" placeholder="<?php esc_attr_e('/blog/*', 'tka-site-utilities'); ?>" class="tka-rule-from-url" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); font-family: monospace; display: <?php echo ($from_type === 'custom_url') ? 'block' : 'none'; ?>;">
																		</div>

																		<!-- TO PAGE -->
																		<div style="flex: 2; display: flex; flex-direction: column; gap: 5px;">
																			<select name="tka_site_utilities_options[page_transitions_rules][<?php echo intval($rule_index); ?>][to_type]" class="tka-rule-to-select" style="width: 100%; padding: 5px; border-radius: 6px;">
																				<?php foreach ($page_types as $val => $lbl): ?>
																						<option value="<?php echo esc_attr($val); ?>" <?php selected($to_type, $val); ?>><?php echo esc_html($lbl); ?></option>
																				<?php endforeach; ?>
																			</select>
																			<input type="text" name="tka_site_utilities_options[page_transitions_rules][<?php echo intval($rule_index); ?>][to_url]" value="<?php echo esc_attr($rule['to_url'] ?? ''); ?>" placeholder="<?php esc_attr_e('/shop/*', 'tka-site-utilities'); ?>" class="tka-rule-to-url" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); font-family: monospace; display: <?php echo ($to_type === 'custom_url') ? 'block' : 'none'; ?>;">
																		</div>

																		<!-- ANIMATION -->
																		<div style="flex: 1.5;">
																			<select name="tka_site_utilities_options[page_transitions_rules][<?php echo intval($rule_index); ?>][animation]" class="tka-rule-anim-select" style="width: 100%; padding: 5px; border-radius: 6px;">
																				<?php
																				$all_anims = array_merge(array_keys($animations_labels), ['custom']);
																				foreach ($all_anims as $anim):
																					$lbl = ($anim === 'custom') ? __('Custom CSS Class...', 'tka-site-utilities') : ($animations_labels[$anim] ?? $anim);
																					?>
																						<option value="<?php echo esc_attr($anim); ?>" <?php selected($rule_anim, $anim); ?>><?php echo esc_html($lbl); ?></option>
																				<?php endforeach; ?>
																			</select>
																		</div>

																		<!-- CUSTOM CLASS -->
																		<div style="flex: 1.5;">
																			<input type="text" name="tka_site_utilities_options[page_transitions_rules][<?php echo intval($rule_index); ?>][custom_class]" value="<?php echo esc_attr($rule['custom_class'] ?? ''); ?>" placeholder="<?php esc_attr_e('tka-transition-zoom', 'tka-site-utilities'); ?>" class="tka-rule-class-input" style="width: 100%; padding: 5px; border-radius: 6px; border: 1px solid var(--tka-border); display: <?php echo ($rule_anim === 'custom') ? 'block' : 'none'; ?>;">
																		</div>

																		<!-- DELETE BUTTON -->
																		<button type="button" class="button tka-rule-delete-btn" style="color: var(--tka-danger); border-color: rgba(239, 68, 68, 0.2); padding: 5px 8px; border-radius: 6px;" title="<?php esc_attr_e('Delete rule', 'tka-site-utilities'); ?>">
																			<span class="dashicons dashicons-trash" style="vertical-align: middle; margin: 0;"></span>
																		</button>
																	</div>
																<?php
																$rule_index++;
															endforeach; ?>
														</div>
														<button type="button" id="tka-add-rule-btn" class="button" style="margin-top: 15px; border-color: var(--tka-primary); color: var(--tka-primary); background: rgba(79, 70, 229, 0.02); padding: 5px 15px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
															<span class="dashicons dashicons-plus" style="font-size:16px; width:16px; height:16px; margin:0; display:inline-block; vertical-align:middle;"></span>
															<?php esc_html_e('Add Transition Rule', 'tka-site-utilities'); ?>
														</button>
													</div>
												</div>

												<!-- CUSTOM CSS EDITOR -->
												<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">
												<div class="tka-setting-row stack" style="width: 100%;">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Custom CSS Transitions Stylesheet', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('Write custom view transition styles and keyframes. Rules will be printed inside a <style> block on the page.', 'tka-site-utilities'); ?>
														</p>
													</div>
													<textarea name="tka_site_utilities_options[page_transitions_custom_css]" class="large-text code" rows="8"
														placeholder="html.tka-transition-custom-zoom::view-transition-old(root) {&#10;    animation: zoom-out 0.4s ease;&#10;}&n;html.tka-transition-custom-zoom::view-transition-new(root) {&#10;    animation: zoom-in 0.4s ease;&#10;}"
														style="width: 100%; font-family: SFMono-Regular, Consolas, monospace; font-size: 13px; margin-top: 10px; border-radius: 6px; padding: 12px; border-color: var(--tka-border);"><?php echo esc_textarea($options['page_transitions_custom_css'] ?? ''); ?></textarea>
												</div>
											</div>
										</div>
									</section>

									<?php if (class_exists('SitePress') || defined('ICL_SITEPRESS_VERSION')): ?>
										<!-- WPML OPTIMIZATION PANEL -->
										<section id="panel-wpml-opt" class="tka-tab-panel">
											<h2><?php esc_html_e('WPML Performance Optimization', 'tka-site-utilities'); ?></h2>
											<p class="section-desc">
												<?php esc_html_e('Optimize performance and reduce database overhead caused by WPML queries and filters.', 'tka-site-utilities'); ?>
											</p>

											<div class="tka-settings-card">
												<!-- Global Toggle -->
												<div class="tka-setting-row">
													<div class="tka-setting-label">
														<strong><?php esc_html_e('Enable WPML Optimization', 'tka-site-utilities'); ?></strong>
														<p><?php esc_html_e('Master switch to enable custom performance filters for WPML query processes.', 'tka-site-utilities'); ?>
														</p>
													</div>
													<div class="tka-setting-control">
														<label class="tka-switch">
															<input type="checkbox" id="tka-wpml-optimization-enabled-toggle" name="tka_site_utilities_options[wpml_optimization_enabled]" value="1"
																<?php checked(1, $options['wpml_optimization_enabled'] ?? 0); ?>>
															<span class="tka-slider"></span>
														</label>
													</div>
												</div>

												<div class="nested-wpml-optimization-settings" style="<?php echo (!empty($options['wpml_optimization_enabled'])) ? 'display: block;' : 'display: none;'; ?>">
													<!-- Disable theme adjustments -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Disable Theme ID Adjustments', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Bypasses the "Adjust IDs for multilingual functionality" runtime translations. Highly recommended for themes to prevent excessive database select queries on page loads.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wpml_disable_adjust_ids]" value="1"
																	<?php checked(1, $options['wpml_disable_adjust_ids'] ?? 0); ?>>
																<span class="tka-slider"></span>
															</label>
														</div>
													</div>

													<!-- Suppress canonical redirects for AJAX/REST -->
													<div class="tka-setting-row">
														<div class="tka-setting-label">
															<strong><?php esc_html_e('Suppress Canonical Redirects for AJAX & REST', 'tka-site-utilities'); ?></strong>
															<p><?php esc_html_e('Bypasses WPML\'s URL canonical redirection validations during background AJAX transactions and REST API requests, optimizing backend processing speed.', 'tka-site-utilities'); ?>
															</p>
														</div>
														<div class="tka-setting-control">
															<label class="tka-switch">
																<input type="checkbox" name="tka_site_utilities_options[wpml_disable_canonical_redirects_ajax]" value="1"
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
										<h2><?php esc_html_e('.htaccess Control & Hardening', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Optimize and harden your Apache/LiteSpeed server configuration directly from WordPress.', 'tka-site-utilities'); ?>
										</p>

										<!-- Server Status Card -->
										<div class="tka-settings-card" style="background: var(--tka-bg-main); border-color: var(--tka-border);">
											<h3 style="margin-top: 0; margin-bottom: 15px; font-size: 16px; color: var(--tka-text-main);">
												<span class="dashicons dashicons-info" style="color: var(--tka-primary); vertical-align: text-bottom; margin-right: 5px;"></span>
												<?php esc_html_e('Server & Write Status', 'tka-site-utilities'); ?>
											</h3>
											<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
												<div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid var(--tka-border);">
													<span style="font-size: 12px; color: var(--tka-text-muted); display: block; margin-bottom: 4px;"><?php esc_html_e('Web Server', 'tka-site-utilities'); ?></span>
													<strong style="font-size: 14px; color: var(--tka-text-main);">
														<?php echo esc_html(isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'Unknown'); ?>
														<?php if (\TKA\WPUtils\Features\HtaccessManager::isApacheOrLiteSpeed()): ?>
															<span style="color: var(--tka-success); font-size: 12px; font-weight: normal; margin-left: 5px;">(<?php esc_html_e('Supported', 'tka-site-utilities'); ?>)</span>
														<?php else: ?>
															<span style="color: var(--tka-danger); font-size: 12px; font-weight: normal; margin-left: 5px;">(<?php esc_html_e('Rules will not run', 'tka-site-utilities'); ?>)</span>
														<?php endif; ?>
													</strong>
												</div>

												<div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid var(--tka-border);">
													<span style="font-size: 12px; color: var(--tka-text-muted); display: block; margin-bottom: 4px;"><?php esc_html_e('Root .htaccess', 'tka-site-utilities'); ?></span>
													<strong style="font-size: 14px; color: var(--tka-text-main);">
														<?php if (\TKA\WPUtils\Features\HtaccessManager::isRootHtaccessWritable()): ?>
															<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="vertical-align: text-bottom; margin-right: 2px;"></span><?php esc_html_e('Writable', 'tka-site-utilities'); ?></span>
														<?php else: ?>
															<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="vertical-align: text-bottom; margin-right: 2px;"></span><?php esc_html_e('Read-Only / Protected', 'tka-site-utilities'); ?></span>
														<?php endif; ?>
													</strong>
												</div>

												<div style="background: #ffffff; padding: 15px; border-radius: 8px; border: 1px solid var(--tka-border);">
													<span style="font-size: 12px; color: var(--tka-text-muted); display: block; margin-bottom: 4px;"><?php esc_html_e('Uploads Directory', 'tka-site-utilities'); ?></span>
													<strong style="font-size: 14px; color: var(--tka-text-main);">
														<?php if (\TKA\WPUtils\Features\HtaccessManager::isUploadsHtaccessWritable()): ?>
															<span style="color: var(--tka-success);"><span class="dashicons dashicons-yes" style="vertical-align: text-bottom; margin-right: 2px;"></span><?php esc_html_e('Writable', 'tka-site-utilities'); ?></span>
														<?php else: ?>
															<span style="color: var(--tka-danger);"><span class="dashicons dashicons-no" style="vertical-align: text-bottom; margin-right: 2px;"></span><?php esc_html_e('Protected', 'tka-site-utilities'); ?></span>
														<?php endif; ?>
													</strong>
												</div>
											</div>
										</div>

										<div class="tka-settings-card">
											<!-- Security & Hardening -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Security Hardening', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Protects wp-config.php, user.ini, and .htaccess itself from HTTP access, disables directory indexes, and blocks common sensitive development/configuration files.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[htaccess_security]" value="1"
															<?php checked(1, $options['htaccess_security'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Author Enumeration -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Prevent Author Enumeration Scans', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Blocks username scans through URL queries like /?author=N by returning a 403 Forbidden status code.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[htaccess_prevent_author_scan]" value="1"
															<?php checked(1, $options['htaccess_prevent_author_scan'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Performance / Leverage Caching & Gzip -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Caching & Gzip Compression', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Enables Gzip mod_deflate compression, Cache-Control headers, Keep-Alive, and Expires headers for browser caching (max cache times increased to 1 year for static assets).', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[htaccess_performance]" value="1"
															<?php checked(1, $options['htaccess_performance'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- CORS Headers -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Set CORS Headers for Static Assets', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Sets Access-Control-Allow-Origin "*" headers for web fonts, CSS, JS, and images to prevent loading issues across multiple domains or CDNs.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[htaccess_cors]" value="1"
															<?php checked(1, $options['htaccess_cors'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<!-- Block PHP in uploads directory -->
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Block PHP Execution in Uploads', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Creates a secondary .htaccess file inside the uploads directory that blocks all direct executions of PHP scripts. Highly recommended for security.', 'tka-site-utilities'); ?></p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[htaccess_uploads_prevent_php]" value="1"
															<?php checked(1, $options['htaccess_uploads_prevent_php'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>
										</div>

										<!-- Preview Section -->
										<div class="tka-settings-card">
											<h3 style="margin-top: 0; font-size: 16px; color: var(--tka-text-main);"><?php esc_html_e('Generated .htaccess Rules Preview', 'tka-site-utilities'); ?></h3>
											<p style="font-size: 13px; color: var(--tka-text-muted); margin-bottom: 15px;">
												<?php esc_html_e('Below is a real-time preview of the rules being injected into your configuration files based on the toggles selected above.', 'tka-site-utilities'); ?>
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
														echo esc_html("# BEGIN TKA_Site_Utilities\n" . implode("\n", $root_preview_rules) . "\n# END TKA_Site_Utilities");
													} else {
														esc_html_e('# No root rules active.', 'tka-site-utilities');
													}
												?></pre>
											</div>

											<div>
												<strong style="font-size: 13px; color: var(--tka-text-main); display: block; margin-bottom: 5px;">Uploads Directory .htaccess Block:</strong>
												<pre style="background: var(--tka-bg-main); border: 1px solid var(--tka-border); padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; max-height: 150px; overflow-y: auto; white-space: pre-wrap; margin: 0; color: var(--tka-text-main);"><?php
													if (!empty($uploads_preview_rules)) {
														echo esc_html("# BEGIN TKA_Site_Utilities_Uploads\n" . implode("\n", $uploads_preview_rules) . "\n# END TKA_Site_Utilities_Uploads");
													} else {
														esc_html_e('# No uploads rules active.', 'tka-site-utilities');
													}
												?></pre>
											</div>
										</div>
									</section>

									<!-- SMTP PANEL -->
									<section id="panel-smtp" class="tka-tab-panel">
										<h2><?php esc_html_e('SMTP & Email Configuration', 'tka-site-utilities'); ?></h2>
										<p class="section-desc">
											<?php esc_html_e('Configure WordPress to route outgoing emails through an external SMTP server, and enable local Mailpit catching during development.', 'tka-site-utilities'); ?>
										</p>

										<div class="tka-settings-card">
											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Enable Custom SMTP', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('Intercepts WordPress emails and forces them to use the SMTP settings defined below.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[smtp_enabled]" value="1"
															<?php checked(1, $options['smtp_enabled'] ?? 0); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Local Mailpit for Development', 'tka-site-utilities'); ?></strong>
													<p><?php esc_html_e('If the environment type is set to "development" (e.g. WP_ENV="development"), automatically overrides the settings below and routes emails to a local Mailpit instance on port 1025.', 'tka-site-utilities'); ?>
													</p>
												</div>
												<div class="tka-setting-control">
													<label class="tka-switch">
														<input type="checkbox" name="tka_site_utilities_options[smtp_mailpit_dev]" value="1"
															<?php checked(1, $options['smtp_mailpit_dev'] ?? 1); ?>>
														<span class="tka-slider"></span>
													</label>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('SMTP Host', 'tka-site-utilities'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<input type="text" name="tka_site_utilities_options[smtp_host]"
														value="<?php echo esc_attr($options['smtp_host'] ?? ''); ?>"
														placeholder="smtp.example.com" class="regular-text">
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('SMTP Port', 'tka-site-utilities'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<input type="number" name="tka_site_utilities_options[smtp_port]"
														value="<?php echo esc_attr($options['smtp_port'] ?? ''); ?>"
														placeholder="587" class="small-text">
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('Encryption', 'tka-site-utilities'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<select name="tka_site_utilities_options[smtp_encryption]">
														<option value="none" <?php selected('none', $options['smtp_encryption'] ?? 'none'); ?>>None</option>
														<option value="ssl" <?php selected('ssl', $options['smtp_encryption'] ?? 'none'); ?>>SSL</option>
														<option value="tls" <?php selected('tls', $options['smtp_encryption'] ?? 'none'); ?>>TLS</option>
													</select>
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('SMTP Username', 'tka-site-utilities'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<input type="text" name="tka_site_utilities_options[smtp_username]"
														value="<?php echo esc_attr($options['smtp_username'] ?? ''); ?>"
														class="regular-text" autocomplete="off">
												</div>
											</div>

											<div class="tka-setting-row">
												<div class="tka-setting-label">
													<strong><?php esc_html_e('SMTP Password', 'tka-site-utilities'); ?></strong>
												</div>
												<div class="tka-setting-control">
													<input type="password" name="tka_site_utilities_options[smtp_password]"
														value="<?php echo esc_attr($options['smtp_password'] ?? ''); ?>"
														class="regular-text" autocomplete="new-password">
												</div>
											</div>

										</div>
									</section>

									<!-- BUTTON WRAPPER -->
									<div class="tka-submit-section">
										<?php submit_button(__('Save Settings', 'tka-site-utilities'), 'primary tka-save-btn', 'submit', false); ?>
									</div>
								</form>
								<?php endif; ?>

							</main>
						</div>
					</div>
				</div>
				<?php
	}

	/**
	 * Helper method to display a locked page when license is inactive.
	 */
	private function renderLicenseLockedPage(string $title, string $dashicon = 'dashicons-lock'): void
	{
		?>
		<div class="wrap tka-site-utilities-wrap">
			<div class="tka-dashboard">
				<header class="tka-dashboard-header">
					<div class="tka-header-brand">
						<span class="dashicons <?php echo esc_attr($dashicon); ?>" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
						<h1><?php esc_html_e('TKA Site Utilities', 'tka-site-utilities'); ?></h1>
						<span class="tka-version-badge"><?php echo esc_html($title); ?></span>
					</div>
				</header>
				<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
					<main class="tka-dashboard-content">
						<div class="tka-settings-card" style="padding: 40px; text-align: center;">
							<span class="dashicons dashicons-lock" style="font-size: 64px; width: 64px; height: 64px; color: #ef4444; margin-bottom: 20px;"></span>
							<h2 style="font-size: 24px; margin-bottom: 15px;"><?php esc_html_e('License Required', 'tka-site-utilities'); ?></h2>
							<p style="font-size: 16px; color: var(--tka-text-muted); margin-bottom: 25px;">
								<?php esc_html_e('Your license is not active or has expired. Please activate a valid license to access this feature.', 'tka-site-utilities'); ?>
							</p>
							<a href="<?php echo esc_url(admin_url('admin.php?page=tka-site-utilities-license')); ?>" class="tka-btn tka-btn-primary" style="font-size: 16px; padding: 10px 24px;"><?php esc_html_e('Manage License', 'tka-site-utilities'); ?></a>
						</div>
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
		if (!\TKA\WPUtils\Licensing\Licensing::isActive()) {
			$this->renderLicenseLockedPage(__('Admin Columns', 'tka-site-utilities'), 'dashicons-align-left');
			return;
		}
		$columns = get_option('tka_site_utilities_columns', []);
		$public_post_types = get_post_types(['show_ui' => true], 'objects');
		$available_keys = self::getAvailableMetaKeys();
		?>
				<div class="wrap tka-site-utilities-wrap">
					<div class="tka-dashboard">
						<!-- Header Section -->
						<header class="tka-dashboard-header">
							<div class="tka-header-brand">
								<span class="dashicons dashicons-align-left" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
								<h1><?php esc_html_e('TKA Site Utilities', 'tka-site-utilities'); ?></h1>
								<span class="tka-version-badge"><?php esc_html_e('Admin Columns', 'tka-site-utilities'); ?></span>
							</div>
							<p class="tka-tagline">
								<?php esc_html_e('Define and customize columns for your admin post list tables based on meta keys.', 'tka-site-utilities'); ?>
							</p>
						</header>

						<!-- Settings Body Layout -->
						<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
							<main class="tka-dashboard-content">
								<form method="post" action="options.php" id="tka-columns-customizer-form">
									<?php
									settings_fields('tka_site_utilities_columns_group');
									?>

									<h2><?php esc_html_e('Admin Columns Customizer', 'tka-site-utilities'); ?></h2>
									<p class="section-desc">
										<?php esc_html_e('Expose custom metadata fields in the list tables. Select a post type to get started.', 'tka-site-utilities'); ?>
									</p>

									<div class="tka-settings-card">
										<!-- Post Type Selection Dropdown -->
										<div class="tka-setting-row" style="padding-top: 0; margin-bottom: 25px;">
											<div class="tka-setting-label">
												<strong><?php esc_html_e('Select Post Type', 'tka-site-utilities'); ?></strong>
												<p><?php esc_html_e('Choose which post type list table you want to customize.', 'tka-site-utilities'); ?>
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
												<strong><?php esc_html_e('Column Rules Definition', 'tka-site-utilities'); ?></strong>
												<p><?php esc_html_e('Define headers, choose custom fields, and specify their type. Drag rows to reorder them.', 'tka-site-utilities'); ?>
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
																	<?php esc_html_e('Column Header Label', 'tka-site-utilities'); ?></div>
																<div class="tka-col-hdr tka-hdr-key">
																	<?php esc_html_e('Database Meta Field Key', 'tka-site-utilities'); ?></div>
																<div class="tka-col-hdr tka-hdr-type">
																	<?php esc_html_e('Field Type & Linkage', 'tka-site-utilities'); ?></div>
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
																						title="<?php esc_attr_e('Drag to reorder', 'tka-site-utilities'); ?>">
																						<span class="dashicons dashicons-menu"></span>
																					</div>

																					<!-- Layout Inputs Grid -->
																					<div class="tka-column-inputs-grid">
																						<!-- Label Input -->
																						<div>
																							<input type="text"
																								name="tka_site_utilities_columns[<?php echo esc_attr($post_type->name); ?>][<?php echo intval($index); ?>][label]"
																								value="<?php echo esc_attr($col['label'] ?? ''); ?>"
																								placeholder="<?php esc_attr_e('Column Header Label (e.g. Price)', 'tka-site-utilities'); ?>"
																								class="tka-col-input-field">
																						</div>

																						<!-- Meta Key Selector Dropdown + Text Input -->
																						<div>
																							<div class="tka-meta-key-selector-wrap">
																								<select class="tka-meta-key-select">
																									<option value="">
																										<?php esc_html_e('— Select a Field —', 'tka-site-utilities'); ?>
																									</option>
																									<?php foreach ($available_keys as $key): ?>
																											<option value="<?php echo esc_attr($key); ?>" <?php selected($meta_val, $key); ?>>
																												<?php echo esc_html($key); ?></option>
																									<?php endforeach; ?>
																									<option value="__custom__" <?php selected($is_custom); ?>>
																										<?php esc_html_e('— Enter Custom Key —', 'tka-site-utilities'); ?>
																									</option>
																								</select>
																								<input type="text" class="tka-meta-key-input"
																									name="tka_site_utilities_columns[<?php echo esc_attr($post_type->name); ?>][<?php echo intval($index); ?>][meta_key]"
																									value="<?php echo esc_attr($meta_val); ?>"
																									placeholder="<?php esc_attr_e('Enter Custom Meta Key', 'tka-site-utilities'); ?>"
																									style="font-family: monospace; <?php echo $is_custom ? 'display: block;' : 'display: none;'; ?>">
																							</div>
																						</div>

																						<!-- Field Type Select -->
																						<div>
																							<select
																								name="tka_site_utilities_columns[<?php echo esc_attr($post_type->name); ?>][<?php echo intval($index); ?>][field_type]"
																								class="tka-field-type-select">
																								<option value="text" <?php selected($col['field_type'] ?? 'text', 'text'); ?>>
																									<?php esc_html_e('Plain Text / Value', 'tka-site-utilities'); ?>
																								</option>
																								<option value="post_relation" <?php selected($col['field_type'] ?? 'text', 'post_relation'); ?>>
																									<?php esc_html_e('Related Post ID or Object (Linked & Filterable)', 'tka-site-utilities'); ?>
																								</option>
																								<option value="term_relation" <?php selected($col['field_type'] ?? 'text', 'term_relation'); ?>>
																									<?php esc_html_e('Related Taxonomy Term (Linked & Filterable)', 'tka-site-utilities'); ?>
																								</option>
																							</select>
																						</div>
																					</div>

																					<!-- Delete Button -->
																					<button type="button" class="button tka-column-remove-btn"
																						title="<?php esc_attr_e('Delete column rule', 'tka-site-utilities'); ?>">
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
																	<?php esc_html_e('No custom columns configured for this post type.', 'tka-site-utilities'); ?>
																</p>
																<p style="margin: 4px 0 0 0; font-size: 12px; color: var(--tka-text-muted);">
																	<?php esc_html_e('Add a new column rule below to display custom metadata inside post list tables.', 'tka-site-utilities'); ?>
																</p>
															</div>

															<button type="button" class="button tka-add-column-row-btn"
																data-posttype="<?php echo esc_attr($post_type->name); ?>"
																style="margin-top: 20px; border-color: var(--tka-primary); color: var(--tka-primary); background: rgba(79, 70, 229, 0.02); padding: 5px 16px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--tka-shadow);">
																<span class="dashicons dashicons-plus"
																	style="font-size: 16px; width: 16px; height: 16px; margin: 0; display: inline-block; vertical-align: middle;"></span>
																<?php esc_html_e('Add Custom Column', 'tka-site-utilities'); ?>
															</button>
														</div>
												<?php endforeach; ?>
											</div>
										</div>
									</div>

									<!-- BUTTON WRAPPER -->
									<div class="tka-submit-section" style="margin-top: 30px;">
										<?php submit_button(__('Save Columns Customizer', 'tka-site-utilities'), 'primary tka-save-btn', 'submit', false); ?>
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
		if (!\TKA\WPUtils\Licensing\Licensing::isActive()) {
			$this->renderLicenseLockedPage(__('Menu Organizer', 'tka-site-utilities'), 'dashicons-menu-alt3');
			return;
		}

		$options = get_option('tka_site_utilities_options');
		?>
				<div class="wrap tka-site-utilities-wrap">
					<div class="tka-dashboard">
						<!-- Header Section -->
						<header class="tka-dashboard-header">
							<div class="tka-header-brand">
								<span class="dashicons dashicons-menu-alt" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
								<h1><?php esc_html_e('TKA Site Utilities', 'tka-site-utilities'); ?></h1>
								<span class="tka-version-badge"><?php esc_html_e('Menu Organizer', 'tka-site-utilities'); ?></span>
							</div>
							<p class="tka-tagline">
								<?php esc_html_e('Configure separate drag-and-drop menu order and visibility rules for yourself and other administrators.', 'tka-site-utilities'); ?>
							</p>
						</header>

						<!-- Settings Body Layout -->
						<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
							<main class="tka-dashboard-content">
								<form method="post" action="options.php">
									<?php
									settings_fields('tka_site_utilities_group');
									?>
									<input type="hidden" name="tka_site_utilities_options[form_context]" value="menu_organizer">

									<h2><?php esc_html_e('Admin Menu Organizer', 'tka-site-utilities'); ?></h2>
									<p class="section-desc">
										<?php esc_html_e('Organize and clean up navigation lists below. Toggle visibility or drag items vertically to sort.', 'tka-site-utilities'); ?>
									</p>

									<div class="tka-settings-card">
										<p style="font-size: 13px; color: var(--tka-primary); background: rgba(79, 70, 229, 0.04); padding: 8px 14px; border-radius: 8px; border-left: 4px solid var(--tka-primary); line-height: 1.4; margin-bottom: 20px;">
											<span class="dashicons dashicons-info" style="font-size: 16px; width: 16px; height: 16px; margin-top: -3px; vertical-align: middle; margin-right: 4px;"></span>
											<strong><?php esc_html_e('Note:', 'tka-site-utilities'); ?></strong>
											<?php esc_html_e('If the "Appearance" (themes.php) menu is hidden for other administrators, a standalone "Menus" option will automatically be exposed in their sidebar to allow navigation menu adjustments.', 'tka-site-utilities'); ?>
										</p>

										<?php
										$default_menus = [
											'index.php' => __('Dashboard', 'tka-site-utilities'),
											'edit.php' => __('Posts', 'tka-site-utilities'),
											'upload.php' => __('Media', 'tka-site-utilities'),
											'edit.php?post_type=page' => __('Pages', 'tka-site-utilities'),
											'edit-comments.php' => __('Comments', 'tka-site-utilities'),
											'themes.php' => __('Appearance', 'tka-site-utilities'),
											'plugins.php' => __('Plugins', 'tka-site-utilities'),
											'users.php' => __('Users', 'tka-site-utilities'),
											'tools.php' => __('Tools', 'tka-site-utilities'),
											'options-general.php' => __('Settings', 'tka-site-utilities'),
											'nav-menus.php' => __('Menus', 'tka-site-utilities'),
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
													<?php esc_html_e('Client Layout (Other Administrators)', 'tka-site-utilities'); ?>
												</button>
												<button type="button" class="tka-sub-tab-btn" data-subtab="owner">
													<?php esc_html_e('Your Account Layout (Original Installer Only)', 'tka-site-utilities'); ?>
												</button>
											</div>

											<!-- 1. Client Menu Organizer Tab Content -->
											<div class="tka-sub-tab-content active" id="tka-subtab-client-content" style="width: 100%;">
												<div class="tka-setting-row stack" style="margin-top: 15px; border-bottom: none; padding-bottom: 0; width: 100%;">
													<div class="tka-setting-label">
														<p><?php esc_html_e('Customize what other administrators see in their sidebar navigation.', 'tka-site-utilities'); ?>
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
																	<input type="hidden" name="tka_site_utilities_options[admin_menu_order][]" value="<?php echo esc_attr($slug); ?>">

																	<!-- Hidden Visibility Checkbox (checked = hidden) -->
																	<input type="checkbox" class="tka-menu-visibility-checkbox" name="tka_site_utilities_options[hidden_admin_menus][]" value="<?php echo esc_attr($slug); ?>" <?php checked($is_hidden); ?> style="display: none;">

																	<div class="tka-organizer-drag">
																		<span class="dashicons dashicons-menu"></span>
																	</div>

																	<div class="tka-organizer-details">
																		<strong class="tka-organizer-title"><?php echo esc_html($label); ?></strong>
																		<code class="tka-code-badge"><?php echo esc_html($slug); ?></code>
																	</div>

																	<div class="tka-organizer-actions">
																		<button type="button" class="tka-organizer-toggle-btn <?php echo $is_hidden ? 'tka-btn-hidden' : 'tka-btn-visible'; ?>" title="<?php esc_attr_e('Toggle Visibility', 'tka-site-utilities'); ?>">
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
														<p><?php esc_html_e('Customize your own personal sidebar navigation order and visibility.', 'tka-site-utilities'); ?>
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
																	<input type="hidden" name="tka_site_utilities_options[owner_admin_menu_order][]" value="<?php echo esc_attr($slug); ?>">

																	<!-- Hidden Visibility Checkbox (checked = hidden) -->
																	<input type="checkbox" class="tka-menu-visibility-checkbox" name="tka_site_utilities_options[owner_hidden_admin_menus][]" value="<?php echo esc_attr($slug); ?>" <?php checked($is_hidden); ?> style="display: none;">

																	<div class="tka-organizer-drag">
																		<span class="dashicons dashicons-menu"></span>
																	</div>

																	<div class="tka-organizer-details">
																		<strong class="tka-organizer-title"><?php echo esc_html($label); ?></strong>
																		<code class="tka-code-badge"><?php echo esc_html($slug); ?></code>
																	</div>

																	<div class="tka-organizer-actions">
																		<button type="button" class="tka-organizer-toggle-btn <?php echo $is_hidden ? 'tka-btn-hidden' : 'tka-btn-visible'; ?>" title="<?php esc_attr_e('Toggle Visibility', 'tka-site-utilities'); ?>">
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
										<?php submit_button(__('Save Menu Organizer', 'tka-site-utilities'), 'primary tka-save-btn', 'submit', false); ?>
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
		if (!current_user_can('manage_options')) {
			return;
		}

		if (!\TKA\WPUtils\Licensing\Licensing::isActive()) {
			$this->renderLicenseLockedPage(__('Bulk Optimizer', 'tka-site-utilities'), 'dashicons-images-alt2');
			return;
		}
		?>
				<div class="wrap tka-site-utilities-wrap">
					<div class="tka-dashboard">
						<!-- Header Section -->
						<header class="tka-dashboard-header">
							<div class="tka-header-brand">
								<span class="dashicons dashicons-format-image" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
								<h1><?php esc_html_e('TKA Site Utilities', 'tka-site-utilities'); ?></h1>
								<span class="tka-version-badge"><?php esc_html_e('Bulk Optimizer', 'tka-site-utilities'); ?></span>
							</div>
							<p class="tka-tagline">
								<?php esc_html_e('Retroactively compress and convert existing media library JPEGs and PNGs to WebP in bulk.', 'tka-site-utilities'); ?>
							</p>
						</header>

						<!-- Settings Body Layout -->
						<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
							<main class="tka-dashboard-content">
								<h2><?php esc_html_e('Bulk Retroactive Image Optimizer', 'tka-site-utilities'); ?></h2>
								<p class="section-desc">
									<?php esc_html_e('This advanced utility processes images sequentially (1 by 1) to guarantee absolute server safety, prevent FPM gateway timeouts, and seamlessly clean up intermediate thumbnails on disk.', 'tka-site-utilities'); ?>
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
										'suppress_filters' => false,
									]);
									$total_eligible_images = $images_query->post_count;
									
									$all_time_savings = wp_cache_get( 'tka_all_time_savings', 'tka-site-utilities' );
									if ( false === $all_time_savings ) {
										global $wpdb;
										// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
										$all_time_savings = (int) $wpdb->get_var("SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_tka_image_savings'");
										wp_cache_set( 'tka_all_time_savings', $all_time_savings, 'tka-site-utilities', HOUR_IN_SECONDS );
									}
									?>

									<div style="background: var(--tka-bg-main); padding: 18px 24px; border-radius: 8px; border: 1px solid var(--tka-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
										<div>
											<span style="font-size: 13px; color: var(--tka-text-muted); display: block; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
												<?php esc_html_e('Eligible Media Library Images', 'tka-site-utilities'); ?>
											</span>
											<strong style="font-size: 24px; color: var(--tka-text-main); margin-top: 4px; display: block;">
												<span id="tka-bulk-total-count"><?php echo intval($total_eligible_images); ?></span> <?php esc_html_e('images', 'tka-site-utilities'); ?>
											</strong>
										</div>
										<div style="display: flex; gap: 10px;">
											<button type="button" id="tka-bulk-optimize-pause-btn" class="button button-secondary"
												style="border-color: var(--tka-danger); color: var(--tka-danger); background: rgba(239, 68, 68, 0.02); font-weight: 600; padding: 6px 18px; border-radius: 8px; height: auto; transition: all 0.15s ease-in-out; display: none;">
												<span class="dashicons dashicons-controls-pause" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-top: -3px; margin-right: 4px;"></span>
												<?php esc_html_e('Pause', 'tka-site-utilities'); ?>
											</button>
											<button type="button" id="tka-bulk-optimize-start-btn" class="button button-secondary"
												style="border-color: var(--tka-primary); color: var(--tka-primary); background: rgba(79, 70, 229, 0.02); font-weight: 600; padding: 6px 18px; border-radius: 8px; height: auto; transition: all 0.15s ease-in-out;"
												<?php disabled($total_eligible_images, 0); ?>>
												<span class="dashicons dashicons-performance" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-top: -3px; margin-right: 4px;"></span>
												<?php esc_html_e('Start Bulk Optimization', 'tka-site-utilities'); ?>
											</button>
										</div>
									</div>

									<!-- Interactive Progress Panel (hidden by default) -->
									<div id="tka-bulk-progress-panel" style="display: none; margin-top: 20px; border-top: 1px solid var(--tka-border); padding-top: 20px; animation: tkaFadeIn 0.3s ease-in-out;">
										<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: var(--tka-text-main);">
											<span id="tka-bulk-progress-status"><?php esc_html_e('Preparing images...', 'tka-site-utilities'); ?></span>
											<span id="tka-bulk-progress-percentage">0%</span>
										</div>
										<div style="width: 100%; background: #e2e8f0; height: 10px; border-radius: 5px; overflow: hidden; margin-bottom: 15px;">
											<div id="tka-bulk-progress-bar" style="width: 0%; background: var(--tka-primary); height: 100%; transition: width 0.3s ease-in-out; border-radius: 5px;"></div>
										</div>
										<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 15px;">
											<div style="background: rgba(16, 185, 129, 0.03); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 6px; padding: 12px 16px;">
												<span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--tka-text-muted); display: block; letter-spacing: 0.5px;">
													<?php esc_html_e('Storage Saved (This Run)', 'tka-site-utilities'); ?>
												</span>
												<strong id="tka-bulk-total-savings" style="font-size: 18px; color: var(--tka-success); margin-top: 2px; display: block;">
													0 KB
												</strong>
											</div>
											<div style="background: rgba(16, 185, 129, 0.03); border: 1px solid rgba(16, 185, 129, 0.1); border-radius: 6px; padding: 12px 16px;">
												<span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--tka-text-muted); display: block; letter-spacing: 0.5px;">
													<?php esc_html_e('Storage Saved (All Time)', 'tka-site-utilities'); ?>
												</span>
												<strong id="tka-bulk-all-time-savings" style="font-size: 18px; color: var(--tka-success); margin-top: 2px; display: block;" data-initial="<?php echo esc_attr($all_time_savings); ?>">
													<?php echo esc_html(size_format($all_time_savings)); ?>
												</strong>
											</div>
											<div style="background: rgba(79, 70, 229, 0.03); border: 1px solid rgba(79, 70, 229, 0.1); border-radius: 6px; padding: 12px 16px;">
												<span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--tka-text-muted); display: block; letter-spacing: 0.5px;">
													<?php esc_html_e('Optimized Images', 'tka-site-utilities'); ?>
												</span>
												<strong style="font-size: 18px; color: var(--tka-primary); margin-top: 2px; display: block;">
													<span id="tka-bulk-optimized-count">0</span> / <span id="tka-bulk-eligible-total-count"><?php echo intval($total_eligible_images); ?></span>
												</strong>
											</div>
										</div>

										<!-- Live Log Box -->
										<div id="tka-bulk-log-box" style="margin-top: 15px; background: #0f172a; color: #38bdf8; font-family: SFMono-Regular, Consolas, monospace; font-size: 11px; padding: 12px 16px; border-radius: 6px; height: 170px; overflow-y: auto; line-height: 1.5; border: 1px solid #1e293b;">
											<div style="color: #64748b;"><?php esc_html_e('>> Ready to begin bulk scan...', 'tka-site-utilities'); ?></div>
										</div>
									</div>
								</div>

								<!-- Media Library Status Table -->
								<div class="tka-settings-card" style="margin-top: 30px; padding: 24px;">
									<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
										<div>
											<h3 style="margin-top: 0; margin-bottom: 8px; font-size: 18px; color: var(--tka-text-main); font-weight: 700;">
												<?php esc_html_e('Media Library Status', 'tka-site-utilities'); ?>
											</h3>
											<p class="section-desc" style="margin-bottom: 0;">
												<?php esc_html_e('Monitor all JPEG, PNG, and WebP attachments, their current format state, and their calculated disk storage footprint savings.', 'tka-site-utilities'); ?>
											</p>
										</div>
										<div class="tka-status-tabs" style="display: flex; gap: 8px;">
											<button type="button" class="button tka-status-tab-btn active" data-status="all" style="border-radius: 20px;"><?php esc_html_e('All', 'tka-site-utilities'); ?></button>
											<button type="button" class="button tka-status-tab-btn" data-status="pending" style="border-radius: 20px;"><?php esc_html_e('Pending', 'tka-site-utilities'); ?></button>
											<button type="button" class="button tka-status-tab-btn" data-status="optimized" style="border-radius: 20px;"><?php esc_html_e('Optimized', 'tka-site-utilities'); ?></button>
										</div>
									</div>

									<div style="overflow-x: auto;">
										<table class="tka-table" style="margin-top: 0;">
											<thead>
												<tr>
													<th style="width: 60px;"><?php esc_html_e('Preview', 'tka-site-utilities'); ?></th>
													<th><?php esc_html_e('Filename', 'tka-site-utilities'); ?></th>
													<th style="width: 120px;"><?php esc_html_e('Current Format', 'tka-site-utilities'); ?></th>
													<th style="width: 140px;"><?php esc_html_e('Status', 'tka-site-utilities'); ?></th>
													<th style="width: 140px; text-align: right;"><?php esc_html_e('Size Savings', 'tka-site-utilities'); ?></th>
												</tr>
											</thead>
											<tbody id="tka-bulk-status-table-body">
												<tr>
													<td colspan="5" style="text-align: center; padding: 30px; color: var(--tka-text-muted);">
														<span class="spinner is-active" style="float: none; margin-right: 8px;"></span> <?php esc_html_e('Loading images...', 'tka-site-utilities'); ?>
													</td>
												</tr>
											</tbody>
										</table>
									</div>
									
									<div class="tka-pagination-controls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; border-top: 1px solid var(--tka-border); padding-top: 15px;">
										<div style="font-size: 13px; color: var(--tka-text-muted);">
											<?php esc_html_e('Showing', 'tka-site-utilities'); ?> <span id="tka-pagination-start">0</span> - <span id="tka-pagination-end">0</span> <?php esc_html_e('of', 'tka-site-utilities'); ?> <span id="tka-pagination-total">0</span>
										</div>
										<div style="display: flex; gap: 8px;">
											<button type="button" id="tka-pagination-prev" class="button" disabled>&laquo; <?php esc_html_e('Previous', 'tka-site-utilities'); ?></button>
											<span id="tka-pagination-current" style="display: inline-flex; align-items: center; padding: 0 10px; font-weight: 600;">1</span>
											<button type="button" id="tka-pagination-next" class="button" disabled><?php esc_html_e('Next', 'tka-site-utilities'); ?> &raquo;</button>
										</div>
									</div>
								</div>
							</main>
						</div>
					</div>
				</div>
				<?php
	}

	/**
	 * Sanitize Redirects Array.
	 */
	public function sanitizeRedirects($input): array
	{
		$sanitized = [];
		if (is_array($input)) {
			foreach ($input as $redirect) {
				if (empty($redirect['old_url']) || empty($redirect['new_url'])) {
					continue;
				}
				$sanitized[] = [
					'old_url' => sanitize_text_field(wp_unslash($redirect['old_url'])),
					'new_url' => esc_url_raw(wp_unslash($redirect['new_url'])),
				];
			}
		}
		return $sanitized;
	}

	/**
	 * Render the URL Redirects settings page.
	 */
	public function renderRedirectsPage(): void
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$redirects = get_option('tka_site_utilities_redirects', []);
		?>
		<div class="wrap tka-site-utilities-wrap">
			<div class="tka-dashboard">
				<!-- Header Section -->
				<header class="tka-dashboard-header">
					<div class="tka-header-brand">
						<span class="dashicons dashicons-randomize" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
						<h1><?php esc_html_e('TKA Site Utilities', 'tka-site-utilities'); ?></h1>
						<span class="tka-version-badge"><?php esc_html_e('URL Redirects', 'tka-site-utilities'); ?></span>
					</div>
					<p class="tka-tagline">
						<?php esc_html_e('Ultra-fast 301 redirects routed natively at the parse request level.', 'tka-site-utilities'); ?>
					</p>
				</header>

				<!-- Settings Body Layout -->
				<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
					<main class="tka-dashboard-content">
						<h2><?php esc_html_e('URL Redirects', 'tka-site-utilities'); ?></h2>

						<form action="options.php" method="post" id="tka-redirects-form">
							<?php settings_fields('tka_site_utilities_redirects_group'); ?>
							
							<div class="tka-settings-card" style="padding: 24px; margin-top: 20px;">
								<div style="margin-bottom: 20px; padding: 15px; background: rgba(56, 189, 248, 0.1); border-left: 3px solid #38bdf8; border-radius: 4px;">
									<p style="margin: 0; font-size: 13px; color: var(--tka-text-main);">
										<strong><?php esc_html_e('Tip:', 'tka-site-utilities'); ?></strong> <?php esc_html_e('Use relative paths for Old URL (e.g., /old-page/). You can use an asterisk (*) as a wildcard at the end of the Old URL to redirect an entire folder (e.g., /old-category/*). New URLs can be relative or absolute.', 'tka-site-utilities'); ?>
									</p>
								</div>

								<table class="tka-table" style="width: 100%;">
									<thead>
										<tr>
											<th style="width: 45%;"><?php esc_html_e('Old URL', 'tka-site-utilities'); ?></th>
											<th style="width: 45%;"><?php esc_html_e('New URL', 'tka-site-utilities'); ?></th>
											<th style="width: 10%; text-align: right;"></th>
										</tr>
									</thead>
									<tbody id="tka-redirects-list">
										<?php
										$index = 0;
										if (!empty($redirects) && is_array($redirects)) {
											foreach ($redirects as $redirect) {
												?>
												<tr class="tka-redirect-row">
													<td>
														<input type="text" name="tka_site_utilities_redirects[<?php echo esc_attr($index); ?>][old_url]" value="<?php echo esc_attr($redirect['old_url']); ?>" placeholder="/old-page/" style="width: 100%;">
													</td>
													<td>
														<input type="text" name="tka_site_utilities_redirects[<?php echo esc_attr($index); ?>][new_url]" value="<?php echo esc_attr($redirect['new_url']); ?>" placeholder="/new-page/" style="width: 100%;">
													</td>
													<td style="text-align: right;">
														<button type="button" class="button tka-remove-redirect-btn" style="color: #ef4444; border-color: #ef4444;" title="<?php esc_attr_e('Remove', 'tka-site-utilities'); ?>">
															<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
														</button>
													</td>
												</tr>
												<?php
												$index++;
											}
										}
										?>
									</tbody>
								</table>

								<div style="margin-top: 15px;">
									<button type="button" id="tka-add-redirect-btn" class="button button-secondary" data-next-index="<?php echo esc_attr($index); ?>">
										+ <?php esc_html_e('Add Redirect', 'tka-site-utilities'); ?>
									</button>
								</div>
							</div>

							<div class="tka-footer" style="margin-top: 20px; display: flex; justify-content: flex-end; align-items: center;">
								<?php submit_button(__('Save Redirects', 'tka-site-utilities'), 'primary', 'submit', false); ?>
							</div>
						</form>
					</main>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const list = document.getElementById('tka-redirects-list');
			const addBtn = document.getElementById('tka-add-redirect-btn');
			
			if (addBtn && list) {
				addBtn.addEventListener('click', function(e) {
					e.preventDefault();
					const index = parseInt(addBtn.getAttribute('data-next-index'), 10);
					
					const tr = document.createElement('tr');
					tr.className = 'tka-redirect-row';
					tr.innerHTML = `
						<td>
							<input type="text" name="tka_site_utilities_redirects[${index}][old_url]" value="" placeholder="/old-page/" style="width: 100%;">
						</td>
						<td>
							<input type="text" name="tka_site_utilities_redirects[${index}][new_url]" value="" placeholder="/new-page/" style="width: 100%;">
						</td>
						<td style="text-align: right;">
							<button type="button" class="button tka-remove-redirect-btn" style="color: #ef4444; border-color: #ef4444;" title="Remove">
								<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
							</button>
						</td>
					`;
					
					list.appendChild(tr);
					addBtn.setAttribute('data-next-index', index + 1);
				});

				list.addEventListener('click', function(e) {
					const btn = e.target.closest('.tka-remove-redirect-btn');
					if (btn) {
						e.preventDefault();
						btn.closest('.tka-redirect-row').remove();
					}
				});
			}
		});
		</script>
		<?php
	}

	/**
	 * Render the License settings page.
	 */
	public function renderLicensePage(): void
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		$status = get_option('tka_site_utilities_license_status', []);
		$message = '';
		$message_type = 'updated';
		$server_url = 'https://plugins.thekitchen.agency';

		$request_method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';
		// If no POST action, we do a real-time heartbeat check on this page load to make sure UI is up-to-date
		if ($request_method !== 'POST' && !empty($status['license_key'])) {
			$manager = new \TKA\WPUtils\Licensing\LicenseManager($server_url, $status['license_key'], 'tka-site-utilities');
			$result = $manager->verify();

			if (isset($result['success']) && $result['success'] && isset($result['status']) && $result['status'] === 'active') {
				$status['status'] = 'active';
				$status['last_check'] = time();
				$status['grace_active'] = false;
				update_option('tka_site_utilities_license_status', $status);
				set_transient('tka_site_utilities_license_check_transient', 'active', 24 * HOUR_IN_SECONDS);
			} else {
				if (isset($result['status']) && $result['status'] !== 'network_error') {
					$status['status'] = 'suspended';
					$status['error_message'] = $result['error'] ?? 'Unregistered domain seat.';
					update_option('tka_site_utilities_license_status', $status);
					set_transient('tka_site_utilities_license_check_transient', 'suspended', 24 * HOUR_IN_SECONDS);
				}
			}
		}

		if ($request_method === 'POST' && isset($_POST['tka_license_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tka_license_nonce'])), 'tka_license_action')) {
			$action = isset($_POST['tka_license_action_type']) ? sanitize_text_field(wp_unslash($_POST['tka_license_action_type'])) : '';
			$license_key = isset($_POST['tka_license_key']) ? sanitize_text_field(wp_unslash($_POST['tka_license_key'])) : '';

			$server_url = 'https://plugins.thekitchen.agency';

			if ($action === 'activate' && !empty($license_key)) {
				$manager = new \TKA\WPUtils\Licensing\LicenseManager($server_url, $license_key, 'tka-site-utilities');
				$result = $manager->activate();

				if (isset($result['success']) && $result['success']) {
					$status = [
						'license_key' => $license_key,
						'status' => 'active',
						'last_check' => time(),
						'grace_active' => false
					];
					update_option('tka_site_utilities_license_status', $status);
					set_transient('tka_site_utilities_license_check_transient', 'active', 24 * HOUR_IN_SECONDS);
					$message = __('License activated successfully.', 'tka-site-utilities');
					$message_type = 'updated';
				} else {
					$message = $result['error'] ?? __('Failed to activate license.', 'tka-site-utilities');
					$message_type = 'error';
				}
			} elseif ($action === 'deactivate') {
				if (!empty($status['license_key'])) {
					$manager = new \TKA\WPUtils\Licensing\LicenseManager($server_url, $status['license_key'], 'tka-site-utilities');
					$manager->deactivate();
				}
				
				$status = [];
				update_option('tka_site_utilities_license_status', $status);
				delete_transient('tka_site_utilities_license_check_transient');
				
				$message = __('License deactivated locally.', 'tka-site-utilities');
				$message_type = 'updated';
			}
		}

		$is_active = \TKA\WPUtils\Licensing\Licensing::isActive();
		$current_key = $status['license_key'] ?? '';
		$error_message = $status['error_message'] ?? '';
		?>
		<div class="wrap tka-site-utilities-wrap">
			<div class="tka-dashboard">
				<!-- Header Section -->
				<header class="tka-dashboard-header">
					<div class="tka-header-brand">
						<span class="dashicons dashicons-lock" style="font-size: 32px; width: 32px; height: 32px; color: #ffffff;"></span>
						<h1><?php esc_html_e('TKA Site Utilities', 'tka-site-utilities'); ?></h1>
						<span class="tka-version-badge"><?php esc_html_e('License Management', 'tka-site-utilities'); ?></span>
					</div>
					<p class="tka-tagline">
						<?php esc_html_e('Activate your license to enable all features and updates.', 'tka-site-utilities'); ?>
					</p>
				</header>

				<!-- Settings Body Layout -->
				<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
					<main class="tka-dashboard-content">
						<h2><?php esc_html_e('License Status', 'tka-site-utilities'); ?></h2>

						<?php if (!empty($message)): ?>
							<div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible" style="margin-left: 0; margin-bottom: 20px;">
								<p><?php echo esc_html($message); ?></p>
							</div>
						<?php endif; ?>

						<div class="tka-settings-card" style="padding: 24px; margin-top: 20px;">
							
							<?php if ($is_active): ?>
								<div style="margin-bottom: 20px; padding: 15px; background: rgba(34, 197, 94, 0.1); border-left: 3px solid #22c55e; border-radius: 4px;">
									<p style="margin: 0; font-size: 14px; color: var(--tka-text-main);">
										<span class="dashicons dashicons-yes-alt" style="color: #22c55e; vertical-align: middle;"></span>
										<strong><?php esc_html_e('Active:', 'tka-site-utilities'); ?></strong> <?php esc_html_e('Your license is currently active and verified.', 'tka-site-utilities'); ?>
									</p>
								</div>
							<?php elseif (!empty($status['status']) && $status['status'] === 'suspended'): ?>
								<div style="margin-bottom: 20px; padding: 15px; background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; border-radius: 4px;">
									<p style="margin: 0; font-size: 14px; color: var(--tka-text-main);">
										<span class="dashicons dashicons-warning" style="color: #ef4444; vertical-align: middle;"></span>
										<strong><?php esc_html_e('Suspended / Invalid:', 'tka-site-utilities'); ?></strong> <?php echo esc_html($error_message ? $error_message : __('Your license is invalid or expired.', 'tka-site-utilities')); ?>
									</p>
								</div>
							<?php else: ?>
								<div style="margin-bottom: 20px; padding: 15px; background: rgba(245, 158, 11, 0.1); border-left: 3px solid #f59e0b; border-radius: 4px;">
									<p style="margin: 0; font-size: 14px; color: var(--tka-text-main);">
										<span class="dashicons dashicons-info" style="color: #f59e0b; vertical-align: middle;"></span>
										<strong><?php esc_html_e('Not Activated:', 'tka-site-utilities'); ?></strong> <?php esc_html_e('Please enter your license key to unlock the plugin features.', 'tka-site-utilities'); ?>
									</p>
								</div>
							<?php endif; ?>

							<form action="" method="post">
								<?php wp_nonce_field('tka_license_action', 'tka_license_nonce'); ?>
								
								<table class="form-table" style="margin-bottom: 20px;">
									<tr>
										<th scope="row"><label for="tka_license_key"><?php esc_html_e('License Key', 'tka-site-utilities'); ?></label></th>
										<td>
											<input type="text" id="tka_license_key" name="tka_license_key" value="<?php echo esc_attr($current_key); ?>" class="regular-text tka-input" style="width: 100%; max-width: 400px;" <?php echo $is_active ? 'readonly' : ''; ?> />
											<p class="description" style="color: var(--tka-text-muted); margin-top: 5px;"><?php esc_html_e('Enter your license key provided by TKA Systems.', 'tka-site-utilities'); ?></p>
										</td>
									</tr>
								</table>
								
								<p class="submit" style="margin: 0; padding: 0;">
									<?php if ($is_active): ?>
										<input type="hidden" name="tka_license_action_type" value="deactivate">
										<button type="submit" class="tka-btn tka-btn-danger"><?php esc_html_e('Deactivate License', 'tka-site-utilities'); ?></button>
									<?php else: ?>
										<input type="hidden" name="tka_license_action_type" value="activate">
										<button type="submit" class="tka-btn tka-btn-primary"><?php esc_html_e('Activate License', 'tka-site-utilities'); ?></button>
									<?php endif; ?>
								</p>
							</form>
						</div>
					</main>
				</div>
			</div>
		</div>
		<?php
	}
}
