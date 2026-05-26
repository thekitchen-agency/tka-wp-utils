<?php

namespace TKA\WPUtils\Admin;

use TKA\WPUtils\Features\SvgValidator;
use TKA\WPUtils\Features\AdminInterface;

/**
 * Handles the admin settings interface and option registration.
 */
class Settings {

	/**
	 * Menu page slug.
	 */
	public const MENU_SLUG = 'tka-wp-utils';

	/**
	 * Initialize settings hooks.
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'addMenuPage' ] );
		add_action( 'admin_init', [ $this, 'registerSettings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'wp_ajax_tka_wp_utils_sandbox_validate_svg', [ $this, 'ajaxSandboxValidateSvg' ] );
	}

	/**
	 * Add the options menu page.
	 */
	public function addMenuPage(): void {
		// Convert to Top-Level Sidebar menu page
		add_menu_page(
			__( 'TKA WP Utils Settings', 'tka-wp-utils' ),
			__( 'TKA WP Utils', 'tka-wp-utils' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'renderSettingsPage' ],
			'dashicons-admin-generic',
			80
		);

		// Default first submenu matching the parent slug
		add_submenu_page(
			self::MENU_SLUG,
			__( 'TKA WP Utils Settings', 'tka-wp-utils' ),
			__( 'Settings Dashboard', 'tka-wp-utils' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'renderSettingsPage' ]
		);

		// Standalone Admin Columns Customizer submenu
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Admin Columns Customizer', 'tka-wp-utils' ),
			__( 'Admin Columns', 'tka-wp-utils' ),
			'manage_options',
			'tka-wp-utils-columns',
			[ $this, 'renderAdminColumnsPage' ]
		);
	}

	/**
	 * Register settings using WordPress Settings API.
	 */
	public function registerSettings(): void {
		register_setting(
			'tka_wp_utils_group',
			'tka_wp_utils_options',
			[
				'sanitize_callback' => [ $this, 'sanitizeOptions' ],
				'default'           => [
					'classic_editor'           => 0,
					'classic_widgets'          => 0,
					'disable_gutenberg'        => 'none',
					'gutenberg_post_types'     => [],
					'svg_upload'               => 0,
					'disable_emojis'           => 0,
					'hide_help_screen_options' => 0,
					'disable_comments'         => 0,
					'disable_rest_api'         => 0,
					'disable_feeds'            => 0,
					'disable_embeds'           => 0,
					'disable_version_strings'  => 0,
					'disable_front_dashicons'  => 0,
					'hide_admin_notices'       => 0,
					'order_enabled'            => 0,
					'order_post_types'         => [],
					'duplicate_enabled'        => 0,
					'duplicate_post_types'     => [],
					'hidden_admin_menus'       => [],
					'admin_bar_cleanup'        => [],
					'disabled_dashboard_widgets' => [],
					'admin_menu_order'         => [],
					'owner_hidden_admin_menus' => [],
					'owner_admin_menu_order'  => [],
					'obfuscate_author_urls'    => 0,
					'obfuscate_emails'         => 0,
					'disable_xmlrpc'           => 0,
					'login_logo'               => '',
					'admin_logo'               => '',
					'login_custom_css'         => '',
					'remove_footer_text'       => 0,
					'hide_acf_menu'            => 0,
					'disable_acf_shortcode'    => 0,
					'acf_custom_json_path'     => 0,
					'acf_copy_paste'           => 0,
					'acf_copy_paste_multiselect' => 0,
				],
			]
		);

		register_setting(
			'tka_wp_utils_columns_group',
			'tka_wp_utils_columns',
			[
				'sanitize_callback' => [ $this, 'sanitizeColumnsOptions' ],
				'default'           => [],
			]
		);
	}

	/**
	 * Sanitize custom admin columns data structure.
	 */
	public function sanitizeColumnsOptions( array $input ): array {
		$sanitized = [];

		foreach ( $input as $post_type => $columns ) {
			$post_type = sanitize_key( $post_type );
			if ( empty( $post_type ) ) {
				continue;
			}

			$sanitized[ $post_type ] = [];
			if ( is_array( $columns ) ) {
				foreach ( $columns as $col ) {
					$label      = isset( $col['label'] ) ? sanitize_text_field( $col['label'] ) : '';
					$meta_key   = isset( $col['meta_key'] ) ? sanitize_text_field( $col['meta_key'] ) : '';
					$field_type = isset( $col['field_type'] ) ? sanitize_text_field( $col['field_type'] ) : 'text';

					if ( ! empty( $label ) || ! empty( $meta_key ) ) {
						$sanitized[ $post_type ][] = [
							'label'      => $label,
							'meta_key'   => $meta_key,
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
	public function sanitizeOptions( array $input ): array {
		$sanitized = [];

		$sanitized['classic_editor']  = isset( $input['classic_editor'] ) ? 1 : 0;
		$sanitized['classic_widgets'] = isset( $input['classic_widgets'] ) ? 1 : 0;

		$gutenberg_mode = $input['disable_gutenberg'] ?? 'none';
		$sanitized['disable_gutenberg'] = in_array( $gutenberg_mode, [ 'none', 'all', 'post_types' ], true ) ? $gutenberg_mode : 'none';

		$sanitized['gutenberg_post_types'] = [];
		if ( isset( $input['gutenberg_post_types'] ) && is_array( $input['gutenberg_post_types'] ) ) {
			foreach ( $input['gutenberg_post_types'] as $post_type ) {
				$sanitized['gutenberg_post_types'][] = sanitize_text_field( $post_type );
			}
		}

		$sanitized['svg_upload'] = isset( $input['svg_upload'] ) ? 1 : 0;

		// Sanitize various utilities toggles
		$sanitized['disable_emojis']           = isset( $input['disable_emojis'] ) ? 1 : 0;
		$sanitized['hide_help_screen_options'] = isset( $input['hide_help_screen_options'] ) ? 1 : 0;
		$sanitized['disable_comments']         = isset( $input['disable_comments'] ) ? 1 : 0;
		$sanitized['disable_rest_api']         = isset( $input['disable_rest_api'] ) ? 1 : 0;
		$sanitized['disable_feeds']            = isset( $input['disable_feeds'] ) ? 1 : 0;
		$sanitized['disable_embeds']           = isset( $input['disable_embeds'] ) ? 1 : 0;
		$sanitized['disable_version_strings']  = isset( $input['disable_version_strings'] ) ? 1 : 0;
		$sanitized['disable_front_dashicons']  = isset( $input['disable_front_dashicons'] ) ? 1 : 0;
		$sanitized['hide_admin_notices']       = isset( $input['hide_admin_notices'] ) ? 1 : 0;

		// Sanitize Content Management toggles
		$sanitized['order_enabled']     = isset( $input['order_enabled'] ) ? 1 : 0;
		$sanitized['order_post_types'] = [];
		if ( isset( $input['order_post_types'] ) && is_array( $input['order_post_types'] ) ) {
			foreach ( $input['order_post_types'] as $post_type ) {
				$sanitized['order_post_types'][] = sanitize_text_field( $post_type );
			}
		}

		$sanitized['duplicate_enabled']     = isset( $input['duplicate_enabled'] ) ? 1 : 0;
		$sanitized['duplicate_post_types'] = [];
		if ( isset( $input['duplicate_post_types'] ) && is_array( $input['duplicate_post_types'] ) ) {
			foreach ( $input['duplicate_post_types'] as $post_type ) {
				$sanitized['duplicate_post_types'][] = sanitize_text_field( $post_type );
			}
		}

		$sanitized['hidden_admin_menus'] = [];
		if ( isset( $input['hidden_admin_menus'] ) && is_array( $input['hidden_admin_menus'] ) ) {
			foreach ( $input['hidden_admin_menus'] as $menu ) {
				$sanitized['hidden_admin_menus'][] = sanitize_text_field( $menu );
			}
		}

		$sanitized['admin_bar_cleanup'] = [];
		if ( isset( $input['admin_bar_cleanup'] ) && is_array( $input['admin_bar_cleanup'] ) ) {
			foreach ( $input['admin_bar_cleanup'] as $item ) {
				$sanitized['admin_bar_cleanup'][] = sanitize_text_field( $item );
			}
		}

		$sanitized['disabled_dashboard_widgets'] = [];
		if ( isset( $input['disabled_dashboard_widgets'] ) && is_array( $input['disabled_dashboard_widgets'] ) ) {
			foreach ( $input['disabled_dashboard_widgets'] as $widget ) {
				$sanitized['disabled_dashboard_widgets'][] = sanitize_text_field( $widget );
			}
		}

		$sanitized['admin_menu_order'] = [];
		if ( isset( $input['admin_menu_order'] ) && is_array( $input['admin_menu_order'] ) ) {
			foreach ( $input['admin_menu_order'] as $slug ) {
				$sanitized['admin_menu_order'][] = sanitize_text_field( $slug );
			}
		}

		$sanitized['owner_hidden_admin_menus'] = [];
		if ( isset( $input['owner_hidden_admin_menus'] ) && is_array( $input['owner_hidden_admin_menus'] ) ) {
			foreach ( $input['owner_hidden_admin_menus'] as $menu ) {
				$sanitized['owner_hidden_admin_menus'][] = sanitize_text_field( $menu );
			}
		}

		$sanitized['owner_admin_menu_order'] = [];
		if ( isset( $input['owner_admin_menu_order'] ) && is_array( $input['owner_admin_menu_order'] ) ) {
			foreach ( $input['owner_admin_menu_order'] as $slug ) {
				$sanitized['owner_admin_menu_order'][] = sanitize_text_field( $slug );
			}
		}

		$sanitized['obfuscate_author_urls'] = isset( $input['obfuscate_author_urls'] ) ? 1 : 0;
		$sanitized['obfuscate_emails']      = isset( $input['obfuscate_emails'] ) ? 1 : 0;
		$sanitized['disable_xmlrpc']        = isset( $input['disable_xmlrpc'] ) ? 1 : 0;

		$sanitized['login_logo']       = isset( $input['login_logo'] ) ? sanitize_text_field( $input['login_logo'] ) : '';
		$sanitized['admin_logo']       = isset( $input['admin_logo'] ) ? sanitize_text_field( $input['admin_logo'] ) : '';
		$sanitized['login_custom_css'] = isset( $input['login_custom_css'] ) ? wp_strip_all_tags( $input['login_custom_css'] ) : '';
		$sanitized['remove_footer_text'] = isset( $input['remove_footer_text'] ) ? 1 : 0;

		$sanitized['hide_acf_menu']         = isset( $input['hide_acf_menu'] ) ? 1 : 0;
		$sanitized['disable_acf_shortcode'] = isset( $input['disable_acf_shortcode'] ) ? 1 : 0;
		$sanitized['acf_custom_json_path']  = isset( $input['acf_custom_json_path'] ) ? 1 : 0;
		$sanitized['acf_copy_paste']             = isset( $input['acf_copy_paste'] ) ? 1 : 0;
		$sanitized['acf_copy_paste_multiselect'] = isset( $input['acf_copy_paste_multiselect'] ) ? 1 : 0;

		return $sanitized;
	}

	/**
	 * Enqueue assets only on the settings page.
	 */
	public function enqueueAssets( string $hook ): void {
		$allowed_hooks = [
			'toplevel_page_' . self::MENU_SLUG,
			'tka-wp-utils_page_tka-wp-utils-columns',
			'settings_page_' . self::MENU_SLUG,
			'tka-wp-utils_page_' . self::MENU_SLUG,
			'admin_page_' . self::MENU_SLUG,
		];

		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
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
			[ 'jquery', 'jquery-ui-sortable' ],
			TKA_WP_UTILS_VERSION,
			true
		);

		wp_localize_script( 'tka-wp-utils-admin-js', 'tkaWpUtilsAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'tka-wp-utils-sandbox-nonce' ),
		] );

		if ( 'tka-wp-utils_page_tka-wp-utils-columns' === $hook ) {
			wp_enqueue_script(
				'tka-wp-utils-columns-js',
				TKA_WP_UTILS_URL . 'admin/js/admin-columns.js',
				[ 'jquery', 'jquery-ui-sortable' ],
				TKA_WP_UTILS_VERSION,
				true
			);
			wp_localize_script( 'tka-wp-utils-columns-js', 'tkaWpUtilsColumns', [
				'metaKeys' => self::getAvailableMetaKeys(),
				'i18n'     => [
					'selectField'          => __( '— Select a Field —', 'tka-wp-utils' ),
					'enterCustomKey'       => __( '— Enter Custom Key —', 'tka-wp-utils' ),
					'customKeyPlaceholder' => __( 'Enter Custom Meta Key', 'tka-wp-utils' ),
					'plainText'            => __( 'Plain Text / Value', 'tka-wp-utils' ),
					'relatedPost'          => __( 'Related Post ID or Object (Linked & Filterable)', 'tka-wp-utils' ),
					'relatedTerm'          => __( 'Related Taxonomy Term (Linked & Filterable)', 'tka-wp-utils' ),
				],
			] );
		}
	}

	/**
	 * Get a list of all distinct meta keys in the database.
	 */
	public static function getAvailableMetaKeys(): array {
		global $wpdb;
		$keys = $wpdb->get_col( "
			SELECT DISTINCT meta_key 
			FROM $wpdb->postmeta 
			WHERE meta_key NOT LIKE '\\_edit\\_%' 
			  AND meta_key NOT LIKE '\\_wp\\_%'
			  AND meta_key NOT LIKE '\\_oembed\\_%'
			ORDER BY meta_key ASC
		" );
		return array_values( array_filter( $keys ) );
	}

	/**
	 * Handle AJAX SVG Sandbox validation.
	 */
	public function ajaxSandboxValidateSvg(): void {
		check_ajax_referer( 'tka-wp-utils-sandbox-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'tka-wp-utils' ) ] );
		}

		if ( empty( $_FILES['svg_file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file uploaded.', 'tka-wp-utils' ) ] );
		}

		$file = $_FILES['svg_file'];

		// Perform validation
		$validator = new SvgValidator();
		$result    = $validator->analyzeSvgFile( $file['tmp_name'] );

		if ( $result['safe'] ) {
			wp_send_json_success( [
				'message' => __( 'Validation Passed! This SVG is secure and safe to upload.', 'tka-wp-utils' ),
			] );
		} else {
			wp_send_json_error( [
				'message' => __( 'Security check failed. Dangerous elements detected.', 'tka-wp-utils' ),
				'threats' => $result['threats'],
			] );
		}
	}

	/**
	 * Render the premium settings page HTML.
	 */
	public function renderSettingsPage(): void {
		$options = get_option( 'tka_wp_utils_options' );
		$public_post_types = get_post_types( [ 'public' => true ], 'objects' );
		?>
		<div class="wrap tka-wp-utils-wrap">
			<div class="tka-dashboard">
				<!-- Header Section -->
				<header class="tka-dashboard-header">
					<div class="tka-header-brand">
						<h1>TKA WP Utils</h1>
						<span class="tka-version-badge">v<?php echo esc_html( TKA_WP_UTILS_VERSION ); ?></span>
					</div>
					<p class="tka-tagline"><?php esc_html_e( 'Customize and secure your WordPress publishing workflow.', 'tka-wp-utils' ); ?></p>
				</header>

				<!-- Settings Body Layout -->
				<div class="tka-dashboard-body">
					<!-- Sidebar Menu -->
					<aside class="tka-dashboard-sidebar">
						<nav class="tka-dashboard-nav">
							<a href="#general" class="tka-nav-item active" data-tab="general">
								<span class="dashicons dashicons-admin-settings"></span>
								<?php esc_html_e( 'Editor & Widgets', 'tka-wp-utils' ); ?>
							</a>
							<a href="#gutenberg" class="tka-nav-item" data-tab="gutenberg">
								<span class="dashicons dashicons-edit"></span>
								<?php esc_html_e( 'Gutenberg Control', 'tka-wp-utils' ); ?>
							</a>
							<a href="#uploads" class="tka-nav-item" data-tab="uploads">
								<span class="dashicons dashicons-shield"></span>
								<?php esc_html_e( 'Security', 'tka-wp-utils' ); ?>
							</a>
							<a href="#various" class="tka-nav-item" data-tab="various">
								<span class="dashicons dashicons-admin-generic"></span>
								<?php esc_html_e( 'Various Settings', 'tka-wp-utils' ); ?>
							</a>
							<a href="#content" class="tka-nav-item" data-tab="content">
								<span class="dashicons dashicons-category"></span>
								<?php esc_html_e( 'Content Management', 'tka-wp-utils' ); ?>
							</a>
							<?php if ( AdminInterface::isCurrentUserInstaller() ) : ?>
								<a href="#admin-interface" class="tka-nav-item" data-tab="admin-interface">
									<span class="dashicons dashicons-admin-users"></span>
									<?php esc_html_e( 'Admin Interface', 'tka-wp-utils' ); ?>
								</a>
								<a href="#design" class="tka-nav-item" data-tab="design">
									<span class="dashicons dashicons-art"></span>
									<?php esc_html_e( 'Design Customization', 'tka-wp-utils' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( class_exists( 'ACF' ) ) : ?>
								<a href="#acf" class="tka-nav-item" data-tab="acf">
									<span class="dashicons dashicons-welcome-widgets-menus"></span>
									<?php esc_html_e( 'ACF Settings', 'tka-wp-utils' ); ?>
								</a>
							<?php endif; ?>
							<a href="#sandbox" class="tka-nav-item" data-tab="sandbox">
								<span class="dashicons dashicons-shield"></span>
								<?php esc_html_e( 'SVG Sandbox Playground', 'tka-wp-utils' ); ?>
								<span class="tka-badge tka-badge-new"><?php esc_html_e( 'Interactive', 'tka-wp-utils' ); ?></span>
							</a>
						</nav>

						<div class="tka-sidebar-info">
							<h3><?php esc_html_e( 'System Status', 'tka-wp-utils' ); ?></h3>
							<ul class="tka-status-list">
								<li>
									<span>WordPress</span>
									<strong><?php echo esc_html( get_bloginfo( 'version' ) ); ?></strong>
								</li>
								<li>
									<span>PHP Version</span>
									<strong><?php echo esc_html( PHP_VERSION ); ?></strong>
								</li>
								<li>
									<span>Max Upload Size</span>
									<strong><?php echo esc_html( size_format( wp_max_upload_size() ) ); ?></strong>
								</li>
							</ul>
						</div>
					</aside>

					<!-- Settings Form Panels -->
					<main class="tka-dashboard-content">
						<form method="post" action="options.php">
							<?php
							settings_fields( 'tka_wp_utils_group' );
							?>

							<!-- GENERAL PANEL -->
							<section id="panel-general" class="tka-tab-panel active">
								<h2><?php esc_html_e( 'Classic Experience Settings', 'tka-wp-utils' ); ?></h2>
								<p class="section-desc"><?php esc_html_e( 'Restore the tried-and-true classic WordPress editors and widget workflows.', 'tka-wp-utils' ); ?></p>

								<div class="tka-settings-card">
									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Enable Classic Editor', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Reverts post/page creation and editing back to the classic rich text (TinyMCE) editor.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[classic_editor]" value="1" <?php checked( 1, $options['classic_editor'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Enable Classic Widgets', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Restores the traditional widgets dashboard and blocks the block-based widget editor.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[classic_widgets]" value="1" <?php checked( 1, $options['classic_widgets'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>
								</div>
							</section>

							<!-- GUTENBERG PANEL -->
							<section id="panel-gutenberg" class="tka-tab-panel">
								<h2><?php esc_html_e( 'Gutenberg Editor Control', 'tka-wp-utils' ); ?></h2>
								<p class="section-desc"><?php esc_html_e( 'Decide exactly where and when the Gutenberg Block Editor is active.', 'tka-wp-utils' ); ?></p>

								<div class="tka-settings-card">
									<div class="tka-setting-row stack">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Disable Gutenberg Block Editor', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Choose to allow Gutenberg, completely disable it, or disable it only for specific content types.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control-radios">
											<label class="tka-radio-card">
												<input type="radio" name="tka_wp_utils_options[disable_gutenberg]" value="none" <?php checked( 'none', $options['disable_gutenberg'] ?? 'none' ); ?>>
												<div class="radio-card-content">
													<strong><?php esc_html_e( 'Keep Gutenberg Active', 'tka-wp-utils' ); ?></strong>
													<span><?php esc_html_e( 'Default WordPress block editor behavior.', 'tka-wp-utils' ); ?></span>
												</div>
											</label>

											<label class="tka-radio-card">
												<input type="radio" name="tka_wp_utils_options[disable_gutenberg]" value="all" <?php checked( 'all', $options['disable_gutenberg'] ?? 'none' ); ?>>
												<div class="radio-card-content">
													<strong><?php esc_html_e( 'Disable Completely', 'tka-wp-utils' ); ?></strong>
													<span><?php esc_html_e( 'Globally disable Gutenberg for all post types.', 'tka-wp-utils' ); ?></span>
												</div>
											</label>

											<label class="tka-radio-card">
												<input type="radio" name="tka_wp_utils_options[disable_gutenberg]" value="post_types" <?php checked( 'post_types', $options['disable_gutenberg'] ?? 'none' ); ?>>
												<div class="radio-card-content">
													<strong><?php esc_html_e( 'Disable by Post Type', 'tka-wp-utils' ); ?></strong>
													<span><?php esc_html_e( 'Selectively revert back to Classic Editor on specific post types.', 'tka-wp-utils' ); ?></span>
												</div>
											</label>
										</div>
									</div>

									<div class="tka-setting-row nested-gutenberg-post-types" style="<?php echo ( 'post_types' === ( $options['disable_gutenberg'] ?? 'none' ) ) ? 'display: block;' : 'display: none;'; ?>">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Choose Post Types to Disable Gutenberg', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Gutenberg editor will be turned OFF for any post type checked below.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-checkbox-grid">
											<?php foreach ( $public_post_types as $post_type ) : ?>
												<label class="tka-checkbox-item">
													<input type="checkbox" name="tka_wp_utils_options[gutenberg_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $options['gutenberg_post_types'] ?? [], true ) ); ?>>
													<span><?php echo esc_html( $post_type->label ); ?> <code class="tka-code-badge"><?php echo esc_html( $post_type->name ); ?></code></span>
												</label>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</section>

							<!-- SECURITY PANEL -->
							<section id="panel-uploads" class="tka-tab-panel">
								<h2><?php esc_html_e( 'Security & Hardening Settings', 'tka-wp-utils' ); ?></h2>
								<p class="section-desc"><?php esc_html_e( 'Protect your WordPress installation from user enumeration, spam harvesters, brute-force attacks, and XML security threats.', 'tka-wp-utils' ); ?></p>

								<div class="tka-settings-card">
									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Allow SVG Uploads with Strict Validation', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Allows administrators and creators to upload .svg vectors. Validates the XML hierarchy upon upload to protect against XML Entity Expansion (XXE) and Cross-Site Scripting (XSS) injection scripts.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[svg_upload]" value="1" <?php checked( 1, $options['svg_upload'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Obfuscate Author URLs & REST User Slugs', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Hides usernames from public author links (e.g. /author/username/ becomes /author/obfuscated_hash/) and REST API endpoints. Attempts to request the original username-based author links return a 404 error.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[obfuscate_author_urls]" value="1" <?php checked( 1, $options['obfuscate_author_urls'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Obfuscate Email Addresses', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Scans published post and widget contents to convert email addresses into randomized decimal/hexadecimal HTML entities. Keeps emails perfectly readable for humans but shields them from automatic spam scraping bots.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[obfuscate_emails]" value="1" <?php checked( 1, $options['obfuscate_emails'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Disable XML-RPC & Block Trackbacks/Pingbacks', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Protects your site from remote brute-force, DOS, and DDOS attacks by disabling the legacy XML-RPC protocol completely, and stripping all active pingback headers.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[disable_xmlrpc]" value="1" <?php checked( 1, $options['disable_xmlrpc'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>
								</div>
							</section>

							<!-- VARIOUS PANEL -->
							<section id="panel-various" class="tka-tab-panel">
								<h2><?php esc_html_e( 'Various Settings & Optimizations', 'tka-wp-utils' ); ?></h2>
								<p class="section-desc"><?php esc_html_e( 'Clean up unnecessary assets, restrict API endpoints, and optimize the WordPress backend/frontend environment.', 'tka-wp-utils' ); ?></p>

								<div class="tka-settings-card">
									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Disable Emojis', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Prevents WordPress emoji scripts and styles from loading on frontend and admin screens.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[disable_emojis]" value="1" <?php checked( 1, $options['disable_emojis'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Disable Comments Completely', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Turns off all front-end commenting/pings, closes comments, and hides Comments menus in the admin dashboard.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[disable_comments]" value="1" <?php checked( 1, $options['disable_comments'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Restrict REST API', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Requires authentication for REST API endpoints. Non-logged-in guest API calls will receive a 401 Unauthorized status.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[disable_rest_api]" value="1" <?php checked( 1, $options['disable_rest_api'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Disable Feeds', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Stops serving RSS/Atom XML feeds, strips feed head links, and redirects guest feed calls back to the homepage.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[disable_feeds]" value="1" <?php checked( 1, $options['disable_feeds'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Disable Embeds', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Deregisters the wp-embed.min.js script, disables auto-discovery, and removes oEmbed REST/header links.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[disable_embeds]" value="1" <?php checked( 1, $options['disable_embeds'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Remove Version Strings & Generator Tag', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Removes the generator meta tag from head and strips "?ver=" parameters from enqueued styles/scripts on front-end.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[disable_version_strings]" value="1" <?php checked( 1, $options['disable_version_strings'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Disable Front-End Dashicons', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Dequeues dashicons.min.css on front-end enqueues for non-logged-in guest visitors to optimize assets size.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" name="tka_wp_utils_options[disable_front_dashicons]" value="1" <?php checked( 1, $options['disable_front_dashicons'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>
								</div>
							</section>

							<!-- CONTENT MANAGEMENT PANEL -->
							<section id="panel-content" class="tka-tab-panel">
								<h2><?php esc_html_e( 'Content Management', 'tka-wp-utils' ); ?></h2>
								<p class="section-desc"><?php esc_html_e( 'Configure drag-and-drop manual ordering and one-click duplication for your posts and custom post types.', 'tka-wp-utils' ); ?></p>

								<div class="tka-settings-card">
									<!-- Draggable sorting toggle -->
									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Enable Drag & Drop Sorting', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Enables sorting posts manually by dragging and dropping rows in the post lists tables.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" id="tka-order-enabled-toggle" name="tka_wp_utils_options[order_enabled]" value="1" <?php checked( 1, $options['order_enabled'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<!-- Order post types selection grid -->
									<div class="tka-setting-row nested-order-post-types" style="<?php echo ( ! empty( $options['order_enabled'] ) ) ? 'display: block;' : 'display: none;'; ?>">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Choose Post Types for Drag & Drop Sorting', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Select which post types can be manually ordered.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-checkbox-grid">
											<?php foreach ( $public_post_types as $post_type ) : ?>
												<label class="tka-checkbox-item">
													<input type="checkbox" name="tka_wp_utils_options[order_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $options['order_post_types'] ?? [], true ) ); ?>>
													<span><?php echo esc_html( $post_type->label ); ?> <code class="tka-code-badge"><?php echo esc_html( $post_type->name ); ?></code></span>
												</label>
											<?php endforeach; ?>
										</div>
									</div>

									<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

									<!-- Content duplication toggle -->
									<div class="tka-setting-row">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Enable Content Duplication', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Adds a secure "Duplicate" link to row actions inside post list tables to clone any item to a new Draft.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-setting-control">
											<label class="tka-switch">
												<input type="checkbox" id="tka-duplicate-enabled-toggle" name="tka_wp_utils_options[duplicate_enabled]" value="1" <?php checked( 1, $options['duplicate_enabled'] ?? 0 ); ?>>
												<span class="tka-slider"></span>
											</label>
										</div>
									</div>

									<!-- Duplicate post types selection grid -->
									<div class="tka-setting-row nested-duplicate-post-types" style="<?php echo ( ! empty( $options['duplicate_enabled'] ) ) ? 'display: block;' : 'display: none;'; ?>">
										<div class="tka-setting-label">
											<strong><?php esc_html_e( 'Choose Post Types for Duplication', 'tka-wp-utils' ); ?></strong>
											<p><?php esc_html_e( 'Select which post types can be duplicated.', 'tka-wp-utils' ); ?></p>
										</div>
										<div class="tka-checkbox-grid">
											<?php foreach ( $public_post_types as $post_type ) : ?>
												<label class="tka-checkbox-item">
													<input type="checkbox" name="tka_wp_utils_options[duplicate_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $options['duplicate_post_types'] ?? [], true ) ); ?>>
													<span><?php echo esc_html( $post_type->label ); ?> <code class="tka-code-badge"><?php echo esc_html( $post_type->name ); ?></code></span>
												</label>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
							</section>

							<?php if ( AdminInterface::isCurrentUserInstaller() ) : ?>
								<!-- ADMIN INTERFACE PANEL -->
								<section id="panel-admin-interface" class="tka-tab-panel">
									<h2><?php esc_html_e( 'Admin Interface Customization', 'tka-wp-utils' ); ?></h2>
									<p class="section-desc"><?php esc_html_e( 'Selectively hide sidebar menu items, help screen options, and dashboard admin notices from other administrators. These do not affect your account.', 'tka-wp-utils' ); ?></p>

									<div class="tka-settings-card">
										<div class="tka-setting-row">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Hide Help & Screen Options Tabs', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Removes the "Help" and "Screen Options" tabs from the top-right of admin pages for other administrators.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-setting-control">
												<label class="tka-switch">
													<input type="checkbox" name="tka_wp_utils_options[hide_help_screen_options]" value="1" <?php checked( 1, $options['hide_help_screen_options'] ?? 0 ); ?>>
													<span class="tka-slider"></span>
												</label>
											</div>
										</div>

										<div class="tka-setting-row">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Hide Dashboard Admin Notices', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Hides update warning notifications, notices, and nagging alerts in the admin panel for other administrators.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-setting-control">
												<label class="tka-switch">
													<input type="checkbox" name="tka_wp_utils_options[hide_admin_notices]" value="1" <?php checked( 1, $options['hide_admin_notices'] ?? 0 ); ?>>
													<span class="tka-slider"></span>
												</label>
											</div>
										</div>

										<div class="tka-setting-row">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Remove Footer Text & WP Version', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Removes the default "Thank you for creating with WordPress." and version number from the footer for other administrators.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-setting-control">
												<label class="tka-switch">
													<input type="checkbox" name="tka_wp_utils_options[remove_footer_text]" value="1" <?php checked( 1, $options['remove_footer_text'] ?? 0 ); ?>>
													<span class="tka-slider"></span>
												</label>
											</div>
										</div>

										<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

										<!-- Clean Up Admin Bar Checkboxes -->
										<div class="tka-setting-row stack">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Clean Up Admin Bar', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Selectively remove nodes from the top administration bar for other administrators.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-checkbox-grid">
												<?php
												$admin_bar_items = [
													'wp-logo'     => __( 'Remove WordPress Logo/Menu', 'tka-wp-utils' ),
													'site-name'   => __( 'Remove Home Icon & Site Name', 'tka-wp-utils' ),
													'customize'   => __( 'Remove Customize Menu', 'tka-wp-utils' ),
													'updates'     => __( 'Remove Updates Link/Counter', 'tka-wp-utils' ),
													'comments'    => __( 'Remove Comments Link/Counter', 'tka-wp-utils' ),
													'new-content' => __( 'Remove New Content Menu', 'tka-wp-utils' ),
													'howdy'       => __( 'Remove "Howdy" Greeting', 'tka-wp-utils' ),
												];
												foreach ( $admin_bar_items as $item_id => $item_label ) : ?>
													<label class="tka-checkbox-item">
														<input type="checkbox" name="tka_wp_utils_options[admin_bar_cleanup][]" value="<?php echo esc_attr( $item_id ); ?>" <?php checked( in_array( $item_id, $options['admin_bar_cleanup'] ?? [], true ) ); ?>>
														<span><?php echo esc_html( $item_label ); ?></span>
													</label>
												<?php endforeach; ?>
											</div>
										</div>

										<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

										<!-- Disable Dashboard Widgets Checkboxes (Dynamic) -->
										<div class="tka-setting-row stack">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Disable Dashboard Widgets', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Selectively disable widgets on the WordPress dashboard for other administrators.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-checkbox-grid">
												<?php
												global $wp_meta_boxes;
												if ( ! function_exists( 'wp_dashboard_setup' ) ) {
													require_once ABSPATH . 'wp-admin/includes/dashboard.php';
												}
												do_action( 'wp_dashboard_setup' );

												$available_widgets = [];
												if ( ! empty( $wp_meta_boxes['dashboard'] ) ) {
													foreach ( $wp_meta_boxes['dashboard'] as $container_key => $containers ) {
														foreach ( $containers as $priority_key => $priorities ) {
															foreach ( $priorities as $widget_id => $widget ) {
																$available_widgets[ $widget_id ] = ! empty( $widget['title'] ) ? wp_strip_all_tags( $widget['title'] ) : $widget_id;
															}
														}
													}
												}

												// Fallback list of core widgets
												if ( empty( $available_widgets ) ) {
													$available_widgets = [
														'dashboard_right_now'   => __( 'At a Glance', 'tka-wp-utils' ),
														'dashboard_activity'    => __( 'Activity', 'tka-wp-utils' ),
														'dashboard_quick_press' => __( 'Quick Draft', 'tka-wp-utils' ),
														'dashboard_primary'     => __( 'WordPress Events and News', 'tka-wp-utils' ),
														'dashboard_site_health' => __( 'Site Health Status', 'tka-wp-utils' ),
													];
												}

												foreach ( $available_widgets as $widget_id => $widget_label ) : ?>
													<label class="tka-checkbox-item">
														<input type="checkbox" name="tka_wp_utils_options[disabled_dashboard_widgets][]" value="<?php echo esc_attr( $widget_id ); ?>" <?php checked( in_array( $widget_id, $options['disabled_dashboard_widgets'] ?? [], true ) ); ?>>
														<span><?php echo esc_html( $widget_label ); ?> <code class="tka-code-badge"><?php echo esc_html( $widget_id ); ?></code></span>
													</label>
												<?php endforeach; ?>
											</div>
										</div>

										<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

										<!-- Admin Menu Organizer -->
										<div class="tka-setting-row stack" style="border-bottom: none; padding-bottom: 0;">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Admin Menu Organizer', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Configure separate drag-and-drop menu order and visibility rules for yourself and other administrators.', 'tka-wp-utils' ); ?></p>
												<p style="margin-top: 10px; font-size: 13px; color: var(--tka-primary); background: rgba(79, 70, 229, 0.04); padding: 8px 14px; border-radius: 8px; border-left: 4px solid var(--tka-primary); display: inline-block; box-sizing: border-box; line-height: 1.4;">
													<span class="dashicons dashicons-info" style="font-size: 16px; width: 16px; height: 16px; margin-top: -3px; vertical-align: middle; margin-right: 4px;"></span>
													<strong><?php esc_html_e( 'Note:', 'tka-wp-utils' ); ?></strong> <?php esc_html_e( 'If the "Appearance" (themes.php) menu is hidden for other administrators, a standalone "Menus" option will automatically be exposed in their sidebar to allow navigation menu adjustments.', 'tka-wp-utils' ); ?>
												</p>
											</div>
										</div>

										<?php
										global $menu;
										$raw_menus = [];
										if ( ! empty( $menu ) ) {
											foreach ( $menu as $item ) {
												if ( empty( $item[2] ) || ( isset( $item[4] ) && str_contains( $item[4], 'wp-menu-separator' ) ) ) {
													continue;
												}
												$label = wp_strip_all_tags( $item[0] );
												if ( empty( trim( $label ) ) ) {
													continue;
												}
												$raw_menus[ $item[2] ] = $label;
											}
										}

										// Fallback core menus if uninitialized
										if ( empty( $raw_menus ) ) {
											$raw_menus = [
												'index.php'               => __( 'Dashboard', 'tka-wp-utils' ),
												'edit.php'                => __( 'Posts', 'tka-wp-utils' ),
												'upload.php'              => __( 'Media', 'tka-wp-utils' ),
												'edit.php?post_type=page' => __( 'Pages', 'tka-wp-utils' ),
												'edit-comments.php'       => __( 'Comments', 'tka-wp-utils' ),
												'themes.php'              => __( 'Appearance', 'tka-wp-utils' ),
												'plugins.php'             => __( 'Plugins', 'tka-wp-utils' ),
												'users.php'               => __( 'Users', 'tka-wp-utils' ),
												'tools.php'               => __( 'Tools', 'tka-wp-utils' ),
												'options-general.php'     => __( 'Settings', 'tka-wp-utils' ),
											];
										}
										?>

										<!-- Sub-Tabs Navigation for Organizers -->
										<div class="tka-sub-tabs-container" style="width: 100%; margin-top: 25px;">
											<div class="tka-sub-tabs-nav">
												<button type="button" class="tka-sub-tab-btn active" data-subtab="client">
													<?php esc_html_e( 'Client Layout (Other Administrators)', 'tka-wp-utils' ); ?>
												</button>
												<button type="button" class="tka-sub-tab-btn" data-subtab="owner">
													<?php esc_html_e( 'Your Account Layout (Original Installer Only)', 'tka-wp-utils' ); ?>
												</button>
											</div>

											<!-- 1. Client Menu Organizer Tab Content -->
											<div class="tka-sub-tab-content active" id="tka-subtab-client-content" style="width: 100%;">
												<div class="tka-setting-row stack" style="margin-top: 15px; border-bottom: none; padding-bottom: 0; width: 100%;">
													<div class="tka-setting-label">
														<p><?php esc_html_e( 'Customize what other administrators see in their sidebar navigation.', 'tka-wp-utils' ); ?></p>
													</div>

													<div class="tka-menu-organizer" id="tka-menu-organizer-list-client" style="width: 100%;">
														<?php
														$menus_to_hide = [];
														$custom_order  = $options['admin_menu_order'] ?? [];
														foreach ( $custom_order as $slug ) {
															if ( isset( $raw_menus[ $slug ] ) ) {
																$menus_to_hide[ $slug ] = $raw_menus[ $slug ];
															}
														}
														foreach ( $raw_menus as $slug => $label ) {
															if ( ! isset( $menus_to_hide[ $slug ] ) ) {
																$menus_to_hide[ $slug ] = $label;
															}
														}

														foreach ( $menus_to_hide as $slug => $label ) :
															$is_hidden = in_array( $slug, $options['hidden_admin_menus'] ?? [], true );
														?>
															<div class="tka-organizer-item <?php echo $is_hidden ? 'menu-hidden' : 'menu-visible'; ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
																<!-- Sortable Order Input -->
																<input type="hidden" name="tka_wp_utils_options[admin_menu_order][]" value="<?php echo esc_attr( $slug ); ?>">
																
																<!-- Hidden Visibility Checkbox (checked = hidden) -->
																<input type="checkbox" class="tka-menu-visibility-checkbox" name="tka_wp_utils_options[hidden_admin_menus][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_hidden ); ?> style="display: none;">
																
																<div class="tka-organizer-drag">
																	<span class="dashicons dashicons-menu"></span>
																</div>
																
																<div class="tka-organizer-details">
																	<strong class="tka-organizer-title"><?php echo esc_html( $label ); ?></strong>
																	<code class="tka-code-badge"><?php echo esc_html( $slug ); ?></code>
																</div>
																
																<div class="tka-organizer-actions">
																	<button type="button" class="tka-organizer-toggle-btn <?php echo $is_hidden ? 'tka-btn-hidden' : 'tka-btn-visible'; ?>" title="<?php esc_attr_e( 'Toggle Visibility', 'tka-wp-utils' ); ?>">
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
														<p><?php esc_html_e( 'Customize your own personal sidebar navigation order and visibility.', 'tka-wp-utils' ); ?></p>
													</div>

													<div class="tka-menu-organizer" id="tka-menu-organizer-list-owner" style="width: 100%;">
														<?php
														$owner_menus_to_hide = [];
														$owner_custom_order  = $options['owner_admin_menu_order'] ?? [];
														foreach ( $owner_custom_order as $slug ) {
															if ( isset( $raw_menus[ $slug ] ) ) {
																$owner_menus_to_hide[ $slug ] = $raw_menus[ $slug ];
															}
														}
														foreach ( $raw_menus as $slug => $label ) {
															if ( ! isset( $owner_menus_to_hide[ $slug ] ) ) {
																$owner_menus_to_hide[ $slug ] = $label;
															}
														}

														foreach ( $owner_menus_to_hide as $slug => $label ) :
															$is_hidden = in_array( $slug, $options['owner_hidden_admin_menus'] ?? [], true );
														?>
															<div class="tka-organizer-item <?php echo $is_hidden ? 'menu-hidden' : 'menu-visible'; ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
																<!-- Sortable Order Input -->
																<input type="hidden" name="tka_wp_utils_options[owner_admin_menu_order][]" value="<?php echo esc_attr( $slug ); ?>">
																
																<!-- Hidden Visibility Checkbox (checked = hidden) -->
																<input type="checkbox" class="tka-menu-visibility-checkbox" name="tka_wp_utils_options[owner_hidden_admin_menus][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_hidden ); ?> style="display: none;">
																
																<div class="tka-organizer-drag">
																	<span class="dashicons dashicons-menu"></span>
																</div>
																
																<div class="tka-organizer-details">
																	<strong class="tka-organizer-title"><?php echo esc_html( $label ); ?></strong>
																	<code class="tka-code-badge"><?php echo esc_html( $slug ); ?></code>
																</div>
																
																<div class="tka-organizer-actions">
																	<button type="button" class="tka-organizer-toggle-btn <?php echo $is_hidden ? 'tka-btn-hidden' : 'tka-btn-visible'; ?>" title="<?php esc_attr_e( 'Toggle Visibility', 'tka-wp-utils' ); ?>">
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
								</section>

								<!-- DESIGN PANEL -->
								<section id="panel-design" class="tka-tab-panel">
									<h2><?php esc_html_e( 'Design Customization', 'tka-wp-utils' ); ?></h2>
									<p class="section-desc"><?php esc_html_e( 'White-label the wp-login page and backend toolbar with custom brand identity.', 'tka-wp-utils' ); ?></p>

									<div class="tka-settings-card">
										<!-- 1. Custom Login Logo Upload -->
										<div class="tka-setting-row stack" style="width: 100%;">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'wp-login.php Page Logo', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Upload or select a custom logo to replace the default WordPress logo on the login page.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-image-upload-control" style="width: 100%; margin-top: 10px;">
												<input type="text" class="regular-text tka-logo-input" name="tka_wp_utils_options[login_logo]" value="<?php echo esc_url( $options['login_logo'] ?? '' ); ?>" style="width: 70%; display: inline-block; vertical-align: middle;">
												<button type="button" class="button tka-upload-btn" style="vertical-align: middle; margin-left: 10px;"><?php esc_html_e( 'Select Image', 'tka-wp-utils' ); ?></button>
												<button type="button" class="button tka-remove-btn" style="vertical-align: middle; margin-left: 5px; color: var(--tka-danger);"><?php esc_html_e( 'Clear', 'tka-wp-utils' ); ?></button>
												<div class="tka-logo-preview" style="margin-top: 10px;">
													<?php if ( ! empty( $options['login_logo'] ) ) : ?>
														<img src="<?php echo esc_url( $options['login_logo'] ); ?>" style="max-height: 80px; display: block; border-radius: 4px; border: 1px solid var(--tka-border);">
													<?php endif; ?>
												</div>
											</div>
										</div>

										<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

										<!-- 2. Custom Admin Bar Logo Upload -->
										<div class="tka-setting-row stack" style="width: 100%;">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Admin Bar Replacement Logo', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Upload or select a small custom logo (square/transparent icon works best) to replace the WordPress logo in the top admin toolbar.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-image-upload-control" style="width: 100%; margin-top: 10px;">
												<input type="text" class="regular-text tka-logo-input" name="tka_wp_utils_options[admin_logo]" value="<?php echo esc_url( $options['admin_logo'] ?? '' ); ?>" style="width: 70%; display: inline-block; vertical-align: middle;">
												<button type="button" class="button tka-upload-btn" style="vertical-align: middle; margin-left: 10px;"><?php esc_html_e( 'Select Image', 'tka-wp-utils' ); ?></button>
												<button type="button" class="button tka-remove-btn" style="vertical-align: middle; margin-left: 5px; color: var(--tka-danger);"><?php esc_html_e( 'Clear', 'tka-wp-utils' ); ?></button>
												<div class="tka-logo-preview" style="margin-top: 10px;">
													<?php if ( ! empty( $options['admin_logo'] ) ) : ?>
														<img src="<?php echo esc_url( $options['admin_logo'] ); ?>" style="max-height: 30px; display: block; border-radius: 4px; border: 1px solid var(--tka-border);">
													<?php endif; ?>
												</div>
											</div>
										</div>

										<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 20px 0;">

										<!-- 3. Custom Login CSS Textarea -->
										<div class="tka-setting-row stack" style="width: 100%; border-bottom: none; padding-bottom: 0;">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Custom Login CSS', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Enter custom CSS rules to customize the login screen aesthetics (background color, buttons, typography). This CSS applies exclusively to the login screen.', 'tka-wp-utils' ); ?></p>
											</div>
											<textarea name="tka_wp_utils_options[login_custom_css]" class="large-text code" rows="6" placeholder="body.login { background: #4f46e5; }&#10;.login h1 a { filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); }" style="width: 100%; font-family: SFMono-Regular, Consolas, monospace; font-size: 13px; margin-top: 10px; border-radius: 6px; padding: 10px; border-color: var(--tka-border);"><?php echo esc_textarea( $options['login_custom_css'] ?? '' ); ?></textarea>
										</div>
									</div>
								</section>
							<?php endif; ?>

							<?php if ( class_exists( 'ACF' ) ) : ?>
								<!-- ACF PANEL -->
								<section id="panel-acf" class="tka-tab-panel">
									<h2><?php esc_html_e( 'Advanced Custom Fields (ACF) Integration', 'tka-wp-utils' ); ?></h2>
									<p class="section-desc"><?php esc_html_e( 'Optimize and secure your ACF setup for client-facing websites.', 'tka-wp-utils' ); ?></p>

									<div class="tka-settings-card">
										<div class="tka-setting-row">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Hide Custom Fields Admin Menu', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Hides the "Custom Fields" sidebar menu item for all administrators except the plugin installer (developer). Keeps your ACF schemas safe from client edits.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-setting-control">
												<label class="tka-switch">
													<input type="checkbox" name="tka_wp_utils_options[hide_acf_menu]" value="1" <?php checked( 1, $options['hide_acf_menu'] ?? 0 ); ?>>
													<span class="tka-slider"></span>
												</label>
											</div>
										</div>

										<div class="tka-setting-row">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Disable [acf] Shortcode', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Disables front-end execution of the legacy `[acf]` shortcode. This is a vital security hardening practice to prevent unauthorized data exposure of raw database values.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-setting-control">
												<label class="tka-switch">
													<input type="checkbox" name="tka_wp_utils_options[disable_acf_shortcode]" value="1" <?php checked( 1, $options['disable_acf_shortcode'] ?? 0 ); ?>>
													<span class="tka-slider"></span>
												</label>
											</div>
										</div>

										<div class="tka-setting-row">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Theme-Independent Local JSON', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Saves and loads ACF field groups in a shared, theme-independent directory (`/wp-content/acf-json/`) instead of the active theme folder. Prevents accidental field group loss during theme updates or switches.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-setting-control">
												<label class="tka-switch">
													<input type="checkbox" name="tka_wp_utils_options[acf_custom_json_path]" value="1" <?php checked( 1, $options['acf_custom_json_path'] ?? 0 ); ?>>
													<span class="tka-slider"></span>
												</label>
											</div>
										</div>

										<div class="tka-setting-row">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Enable Flexible Layout Copy & Paste', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Adds Copy & Paste buttons to each Flexible Content block in the WordPress post editor. Copied blocks can be pasted across different fields and posts.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-setting-control">
												<label class="tka-switch">
													<input type="checkbox" id="tka-acf-copy-paste-toggle" name="tka_wp_utils_options[acf_copy_paste]" value="1" <?php checked( 1, $options['acf_copy_paste'] ?? 0 ); ?>>
													<span class="tka-slider"></span>
												</label>
											</div>
										</div>

										<div class="tka-setting-row nested-acf-multiselect" style="<?php echo ( ! empty( $options['acf_copy_paste'] ) ) ? 'display: flex;' : 'display: none;'; ?>">
											<div class="tka-setting-label">
												<strong><?php esc_html_e( 'Enable Multiple Layout Copy/Paste (Multiselect)', 'tka-wp-utils' ); ?></strong>
												<p><?php esc_html_e( 'Enables checkboxes in layout headers to select, copy, and bulk paste multiple layout blocks simultaneously.', 'tka-wp-utils' ); ?></p>
											</div>
											<div class="tka-setting-control">
												<label class="tka-switch">
													<input type="checkbox" name="tka_wp_utils_options[acf_copy_paste_multiselect]" value="1" <?php checked( 1, $options['acf_copy_paste_multiselect'] ?? 0 ); ?>>
													<span class="tka-slider"></span>
												</label>
											</div>
										</div>
									</div>
								</section>
							<?php endif; ?>

							<!-- BUTTON WRAPPER -->
							<div class="tka-submit-section">
								<?php submit_button( __( 'Save Settings', 'tka-wp-utils' ), 'primary tka-save-btn', 'submit', false ); ?>
							</div>
						</form>

						<!-- SANDBOX PANEL (Independent of form submit) -->
						<section id="panel-sandbox" class="tka-tab-panel">
							<h2><?php esc_html_e( 'SVG Security Playground & Sandbox', 'tka-wp-utils' ); ?></h2>
							<p class="section-desc"><?php esc_html_e( 'Drag and drop any SVG image to inspect its DOM structure and verify if it satisfies our strict sanitization engine before uploading to the WordPress Media Library.', 'tka-wp-utils' ); ?></p>

							<div class="tka-settings-card">
								<div class="tka-sandbox-container">
									<div class="tka-dropzone" id="tka-svg-dropzone">
										<span class="dashicons dashicons-shield-alt tka-shield-icon"></span>
										<h3><?php esc_html_e( 'Drag & Drop SVG Here', 'tka-wp-utils' ); ?></h3>
										<p><?php esc_html_e( 'or click to choose a local file', 'tka-wp-utils' ); ?></p>
										<input type="file" id="tka-sandbox-file-input" accept="image/svg+xml" style="display: none;">
									</div>

									<div class="tka-sandbox-results" id="tka-sandbox-results" style="display: none;">
										<div class="tka-results-header">
											<span class="tka-results-badge"></span>
											<h4></h4>
										</div>
										<div class="tka-threat-details" id="tka-threat-details" style="display: none;">
											<h5><?php esc_html_e( 'Detected Security Risk Elements / Attributes:', 'tka-wp-utils' ); ?></h5>
											<ul class="tka-threat-list"></ul>
										</div>
									</div>
								</div>
							</div>
						</section>
					</main>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the premium Admin Columns Customizer subpage HTML.
	 */
	public function renderAdminColumnsPage(): void {
		$columns = get_option( 'tka_wp_utils_columns', [] );
		$public_post_types = get_post_types( [ 'public' => true ], 'objects' );
		$available_keys = self::getAvailableMetaKeys();
		?>
		<div class="wrap tka-wp-utils-wrap">
			<div class="tka-dashboard">
				<!-- Header Section -->
				<header class="tka-dashboard-header">
					<div class="tka-header-brand">
						<h1><?php esc_html_e( 'TKA WP Utils', 'tka-wp-utils' ); ?></h1>
						<span class="tka-version-badge"><?php esc_html_e( 'Admin Columns', 'tka-wp-utils' ); ?></span>
					</div>
					<p class="tka-tagline"><?php esc_html_e( 'Define and customize columns for your admin post list tables based on meta keys.', 'tka-wp-utils' ); ?></p>
				</header>

				<!-- Settings Body Layout -->
				<div class="tka-dashboard-body" style="grid-template-columns: 1fr;">
					<main class="tka-dashboard-content">
						<form method="post" action="options.php" id="tka-columns-customizer-form">
							<?php
							settings_fields( 'tka_wp_utils_columns_group' );
							?>

							<h2><?php esc_html_e( 'Admin Columns Customizer', 'tka-wp-utils' ); ?></h2>
							<p class="section-desc"><?php esc_html_e( 'Expose custom metadata fields in the list tables. Select a post type to get started.', 'tka-wp-utils' ); ?></p>

							<div class="tka-settings-card">
								<!-- Post Type Selection Dropdown -->
								<div class="tka-setting-row" style="padding-top: 0; margin-bottom: 25px;">
									<div class="tka-setting-label">
										<strong><?php esc_html_e( 'Select Post Type', 'tka-wp-utils' ); ?></strong>
										<p><?php esc_html_e( 'Choose which post type list table you want to customize.', 'tka-wp-utils' ); ?></p>
									</div>
									<div class="tka-setting-control">
										<select id="tka-column-post-type-selector" style="min-width: 240px; padding: 8px 16px; border-radius: 8px; border-color: var(--tka-border); font-weight: 500; font-size: 14px; box-shadow: var(--tka-shadow);">
											<?php foreach ( $public_post_types as $post_type ) : ?>
												<option value="<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->label ); ?> (<?php echo esc_html( $post_type->name ); ?>)</option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>

								<hr style="border: 0; border-top: 1px solid var(--tka-border); margin: 25px 0;">

								<!-- Custom Columns Manager Grid -->
								<div class="tka-setting-row stack" style="border-bottom: none; padding-bottom: 0;">
									<div class="tka-setting-label" style="max-width: 100%;">
										<strong><?php esc_html_e( 'Column Rules Definition', 'tka-wp-utils' ); ?></strong>
										<p><?php esc_html_e( 'Define headers, choose custom fields, and specify their type. Drag rows to reorder them.', 'tka-wp-utils' ); ?></p>
									</div>

									<!-- Twin container panels dynamically shown via JS selector -->
									<div class="tka-columns-manager-panels-wrap" style="width: 100%; margin-top: 15px;">
										<?php foreach ( $public_post_types as $post_type ) :
											$post_type_cols = $columns[ $post_type->name ] ?? [];
										?>
											<div class="tka-columns-post-type-panel" id="tka-columns-panel-<?php echo esc_attr( $post_type->name ); ?>" style="display: none; width: 100%;">
												
												<!-- Table-like headers -->
												<div class="tka-columns-headers-row" style="<?php echo empty( $post_type_cols ) ? 'display: none;' : ''; ?>">
													<div class="tka-col-hdr tka-hdr-drag" style="width: 30px;"></div>
													<div class="tka-col-hdr tka-hdr-label"><?php esc_html_e( 'Column Header Label', 'tka-wp-utils' ); ?></div>
													<div class="tka-col-hdr tka-hdr-key"><?php esc_html_e( 'Database Meta Field Key', 'tka-wp-utils' ); ?></div>
													<div class="tka-col-hdr tka-hdr-type"><?php esc_html_e( 'Field Type & Linkage', 'tka-wp-utils' ); ?></div>
													<div class="tka-col-hdr tka-hdr-actions" style="width: 50px;"></div>
												</div>

												<div class="tka-columns-rows-list" style="display: flex; flex-direction: column; gap: 10px; width: 100%;">
													<?php if ( ! empty( $post_type_cols ) ) :
														foreach ( $post_type_cols as $index => $col ) :
															$meta_val = $col['meta_key'] ?? '';
															$is_custom = ! empty( $meta_val ) && ! in_array( $meta_val, $available_keys, true );
													?>
														<div class="tka-column-row-item">
															<!-- Drag Handle -->
															<div class="tka-column-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'tka-wp-utils' ); ?>">
																<span class="dashicons dashicons-menu"></span>
															</div>

															<!-- Layout Inputs Grid -->
															<div class="tka-column-inputs-grid">
																<!-- Label Input -->
																<div>
																	<input type="text" name="tka_wp_utils_columns[<?php echo esc_attr( $post_type->name ); ?>][<?php echo intval( $index ); ?>][label]" value="<?php echo esc_attr( $col['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Column Header Label (e.g. Price)', 'tka-wp-utils' ); ?>" class="tka-col-input-field">
																</div>

																<!-- Meta Key Selector Dropdown + Text Input -->
																<div>
																	<div class="tka-meta-key-selector-wrap">
																		<select class="tka-meta-key-select">
																			<option value=""><?php esc_html_e( '— Select a Field —', 'tka-wp-utils' ); ?></option>
																			<?php foreach ( $available_keys as $key ) : ?>
																				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $meta_val, $key ); ?>><?php echo esc_html( $key ); ?></option>
																			<?php endforeach; ?>
																			<option value="__custom__" <?php selected( $is_custom ); ?>><?php esc_html_e( '— Enter Custom Key —', 'tka-wp-utils' ); ?></option>
																		</select>
																		<input type="text" class="tka-meta-key-input" name="tka_wp_utils_columns[<?php echo esc_attr( $post_type->name ); ?>][<?php echo intval( $index ); ?>][meta_key]" value="<?php echo esc_attr( $meta_val ); ?>" placeholder="<?php esc_attr_e( 'Enter Custom Meta Key', 'tka-wp-utils' ); ?>" style="font-family: monospace; <?php echo $is_custom ? 'display: block;' : 'display: none;'; ?>">
																	</div>
																</div>

																<!-- Field Type Select -->
																<div>
																	<select name="tka_wp_utils_columns[<?php echo esc_attr( $post_type->name ); ?>][<?php echo intval( $index ); ?>][field_type]" class="tka-field-type-select">
																		<option value="text" <?php selected( $col['field_type'] ?? 'text', 'text' ); ?>><?php esc_html_e( 'Plain Text / Value', 'tka-wp-utils' ); ?></option>
																		<option value="post_relation" <?php selected( $col['field_type'] ?? 'text', 'post_relation' ); ?>><?php esc_html_e( 'Related Post ID or Object (Linked & Filterable)', 'tka-wp-utils' ); ?></option>
																		<option value="term_relation" <?php selected( $col['field_type'] ?? 'text', 'term_relation' ); ?>><?php esc_html_e( 'Related Taxonomy Term (Linked & Filterable)', 'tka-wp-utils' ); ?></option>
																	</select>
																</div>
															</div>

															<!-- Delete Button -->
															<button type="button" class="button tka-column-remove-btn" title="<?php esc_attr_e( 'Delete column rule', 'tka-wp-utils' ); ?>">
																<span class="dashicons dashicons-trash"></span>
															</button>
														</div>
													<?php endforeach;
													endif; ?>
												</div>

												<!-- Placeholder if empty -->
												<div class="tka-columns-empty-placeholder" style="text-align: center; border: 2px dashed var(--tka-border); border-radius: var(--tka-radius); padding: 40px 20px; background: var(--tka-bg-main); <?php echo ! empty( $post_type_cols ) ? 'display: none;' : ''; ?>">
													<span class="dashicons dashicons-database" style="font-size: 40px; width: 40px; height: 40px; color: var(--tka-text-muted); margin-bottom: 12px; display: inline-block;"></span>
													<p style="margin: 0; font-size: 14px; font-weight: 500; color: var(--tka-text-main);"><?php esc_html_e( 'No custom columns configured for this post type.', 'tka-wp-utils' ); ?></p>
													<p style="margin: 4px 0 0 0; font-size: 12px; color: var(--tka-text-muted);"><?php esc_html_e( 'Add a new column rule below to display custom metadata inside post list tables.', 'tka-wp-utils' ); ?></p>
												</div>

												<button type="button" class="button tka-add-column-row-btn" data-posttype="<?php echo esc_attr( $post_type->name ); ?>" style="margin-top: 20px; border-color: var(--tka-primary); color: var(--tka-primary); background: rgba(79, 70, 229, 0.02); padding: 5px 16px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--tka-shadow);">
													<span class="dashicons dashicons-plus" style="font-size: 16px; width: 16px; height: 16px; margin: 0; display: inline-block; vertical-align: middle;"></span>
													<?php esc_html_e( 'Add Custom Column', 'tka-wp-utils' ); ?>
												</button>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>

							<!-- BUTTON WRAPPER -->
							<div class="tka-submit-section" style="margin-top: 30px;">
								<?php submit_button( __( 'Save Columns Customizer', 'tka-wp-utils' ), 'primary tka-save-btn', 'submit', false ); ?>
							</div>
						</form>
					</main>
				</div>
			</div>
		</div>
		<?php
	}
}
