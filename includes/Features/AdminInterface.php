<?php

namespace TKA\WPUtils\Features;

/**
 * Handles customizing the admin interface menu list visibility for secondary administrators.
 */
class AdminInterface {

	/**
	 * Active option settings array.
	 */
	private array $options;

	/**
	 * Constructor.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	public function hook(): void {
		add_action( 'admin_menu', [ $this, 'hideAdminMenus' ], 999 );

		if ( ! empty( $this->options['hide_help_screen_options'] ) ) {
			add_action( 'current_screen', [ $this, 'removeHelpAndScreenOptions' ] );
		}

		if ( ! empty( $this->options['hide_admin_notices'] ) ) {
			add_action( 'admin_print_styles', [ $this, 'injectNoticeHideStyles' ] );
		}

		if ( ! empty( $this->options['disable_command_palette'] ) ) {
			add_action( 'init', [ $this, 'disableCommandPalette' ] );
		}

		if ( ! empty( $this->options['admin_bar_cleanup'] ) ) {
			add_action( 'wp_before_admin_bar_render', [ $this, 'cleanupAdminBar' ], PHP_INT_MAX );
		}

		// Always record dashboard widgets so we know what they are
		add_action( 'wp_dashboard_setup', [ $this, 'recordDashboardWidgets' ], PHP_INT_MAX - 1 );

		if ( ! empty( $this->options['disabled_dashboard_widgets'] ) ) {
			add_action( 'wp_dashboard_setup', [ $this, 'disableDashboardWidgets' ], PHP_INT_MAX );
		}

		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', [ $this, 'reorderAdminMenus' ], 999 );
		add_action( 'admin_init', [ $this, 'enforceDirectAccessRestriction' ] );

		// Design customization hooks
		if ( ! empty( $this->options['login_logo'] ) || ! empty( $this->options['login_custom_css'] ) ) {
			add_action( 'login_enqueue_scripts', [ $this, 'customLoginCustomizations' ] );
			add_filter( 'login_headerurl', [ $this, 'customLoginHeaderUrl' ] );
			add_filter( 'login_headertext', [ $this, 'customLoginHeaderText' ] );
		}

		if ( ! empty( $this->options['admin_logo'] ) ) {
			add_action( 'admin_head', [ $this, 'injectAdminLogoCss' ] );
			add_action( 'wp_head', [ $this, 'injectAdminLogoCss' ] );
		}

		if ( ! empty( $this->options['remove_footer_text'] ) ) {
			add_action( 'admin_init', [ $this, 'registerFooterRemoval' ] );
		}
	}

	/**
	 * Retrieve the active menu view configuration for the current user.
	 *
	 * Returns array with:
	 *  - 'type': 'owner' | 'custom' | 'client' | 'none'
	 *  - 'view_id': string|null
	 *  - 'hidden_menus': array
	 *  - 'menu_order': array
	 *  - 'block_direct_access': bool
	 */
	public function getCurrentUserMenuView(): array {
		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ) {
			return [
				'type'                => 'none',
				'view_id'             => null,
				'hidden_menus'        => [],
				'menu_order'          => [],
				'block_direct_access' => false,
			];
		}

		// 1. Installer / Superadmin check
		if ( self::isCurrentUserInstaller() ) {
			return [
				'type'                => 'owner',
				'view_id'             => null,
				'hidden_menus'        => $this->options['owner_hidden_admin_menus'] ?? [],
				'menu_order'          => $this->options['owner_admin_menu_order'] ?? [],
				'block_direct_access' => false, // Never block site owner / installer!
			];
		}

		// 2. Check if assigned to a Custom View
		$custom_views = $this->options['menu_views'] ?? [];
		if ( ! empty( $custom_views ) && is_array( $custom_views ) ) {
			foreach ( $custom_views as $view_id => $view ) {
				$assigned_users = $view['user_ids'] ?? [];
				if ( is_array( $assigned_users ) && in_array( $current_user_id, array_map( 'intval', $assigned_users ), true ) ) {
					return [
						'type'                => 'custom',
						'view_id'             => $view_id,
						'hidden_menus'        => $view['hidden_menus'] ?? [],
						'menu_order'          => $view['menu_order'] ?? [],
						'block_direct_access' => ! empty( $view['block_direct_access'] ),
					];
				}
			}
		}

		// 3. Fallback to Client Layout
		return [
			'type'                => 'client',
			'view_id'             => null,
			'hidden_menus'        => $this->options['hidden_admin_menus'] ?? [],
			'menu_order'          => $this->options['admin_menu_order'] ?? [],
			'block_direct_access' => ! empty( $this->options['client_block_direct_access'] ),
		];
	}

	/**
	 * Restrict standard WordPress sidebar menus for secondary administrators or assigned view users.
	 */
	public function hideAdminMenus(): void {
		$view = $this->getCurrentUserMenuView();
		if ( $view['type'] === 'none' ) {
			return;
		}

		// If it's a fallback client view, only apply to users with manage_options
		if ( $view['type'] === 'client' && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$hidden_menus = $view['hidden_menus'];
		if ( empty( $hidden_menus ) ) {
			return;
		}

		foreach ( $hidden_menus as $menu_slug ) {
			remove_menu_page( $menu_slug );
		}

		// If Appearance (themes.php) is hidden but the user can edit menus,
		// expose "Menus" (nav-menus.php) as a standalone top-level menu page,
		// unless nav-menus.php is itself explicitly in hidden_menus!
		if ( in_array( 'themes.php', $hidden_menus, true ) && ! in_array( 'nav-menus.php', $hidden_menus, true ) && current_user_can( 'edit_theme_options' ) ) {
			add_menu_page(
				__( 'Menus', 'tka-site-utilities' ),
				__( 'Menus', 'tka-site-utilities' ),
				'edit_theme_options',
				'nav-menus.php',
				'',
				'dashicons-menu',
				60
			);
		}
	}

	/**
	 * Get the stored owner installer ID with founder fallback.
	 */
	public static function getInstallerId(): int {
		$installer_id = get_option( 'tka_site_utilities_installer_id' );
		if ( ! $installer_id ) {
			$admins = get_users( [
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
			] );
			if ( ! empty( $admins ) ) {
				$installer_id = $admins[0]->ID;
				update_option( 'tka_site_utilities_installer_id', $installer_id );
			}
		}
		return intval( $installer_id );
	}

	/**
	 * Verify if current active logged-in user is the installer.
	 */
	public static function isCurrentUserInstaller(): bool {
		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ) {
			return false;
		}

		if ( $current_user_id === self::getInstallerId() ) {
			return true;
		}

		$options = get_option( 'tka_site_utilities_options', [] );
		$superadmins = $options['superadmin_users'] ?? [];

		return in_array( $current_user_id, $superadmins, true );
	}

	/**
	 * Intercept current screen and strip panels for secondary administrators/users.
	 */
	public function removeHelpAndScreenOptions( $screen ): void {
		if ( self::isCurrentUserInstaller() ) {
			return; // Installer owner can see help screen options
		}
		if ( ! $screen ) {
			return;
		}
		add_filter( 'screen_options_show_screen', '__return_false' );
		if ( method_exists( $screen, 'remove_help_tabs' ) ) {
			$screen->remove_help_tabs();
		}
		
		// Fallback to visually hide the tabs if they are injected later
		add_action( 'admin_print_styles', function() {
			echo '<style>#contextual-help-link-wrap, #screen-meta-links { display: none !important; }</style>';
		} );
	}

	/**
	 * Disable the WordPress Command Palette for other administrators.
	 */
	public function disableCommandPalette(): void {
		if ( self::isCurrentUserInstaller() ) {
			return; // Installer retains command palette
		}
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_commands_app' );
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_commands_app' );
	}

	/**
	 * Inject CSS overrides to hide admin notices for other administrators.
	 */
	public function injectNoticeHideStyles(): void {
		if ( self::isCurrentUserInstaller() ) {
			return; // Installer owner can see admin notices
		}
		echo '<style>.update-nag, .notice, .error, .updated, .notice-info, .notice-warning, .notice-error, .notice-success { display: none !important; }</style>';
	}

	/**
	 * Remove selected Admin Bar items for secondary administrators/users.
	 */
	public function cleanupAdminBar(): void {
		if ( self::isCurrentUserInstaller() ) {
			return; // Installer owner retains the standard bar
		}

		global $wp_admin_bar;
		if ( ! $wp_admin_bar ) {
			return;
		}

		$cleanup_items = $this->options['admin_bar_cleanup'] ?? [];

		if ( in_array( 'wp-logo', $cleanup_items, true ) ) {
			$wp_admin_bar->remove_node( 'wp-logo' );
		}
		if ( in_array( 'site-name', $cleanup_items, true ) ) {
			$wp_admin_bar->remove_node( 'site-name' );
		}
		if ( in_array( 'customize', $cleanup_items, true ) ) {
			$wp_admin_bar->remove_node( 'customize' );
		}
		if ( in_array( 'updates', $cleanup_items, true ) ) {
			$wp_admin_bar->remove_node( 'updates' );
		}
		if ( in_array( 'comments', $cleanup_items, true ) ) {
			$wp_admin_bar->remove_node( 'comments' );
		}
		if ( in_array( 'new-content', $cleanup_items, true ) ) {
			$wp_admin_bar->remove_node( 'new-content' );
		}
		if ( in_array( 'howdy', $cleanup_items, true ) ) {
			$my_account = $wp_admin_bar->get_node( 'my-account' );
			if ( $my_account ) {
				$new_title = str_replace( 'Howdy, ', '', $my_account->title );
				$wp_admin_bar->add_node( [
					'id'    => 'my-account',
					'title' => $new_title,
				] );
			}
		}
	}

	/**
	 * Record dashboard widgets when they are actually rendered on the dashboard,
	 * so we can display them in our settings panel even if plugins restrict their
	 * registration strictly to the dashboard screen.
	 */
	public function recordDashboardWidgets(): void {
		global $wp_meta_boxes;

		if ( empty( $wp_meta_boxes['dashboard'] ) ) {
			return;
		}

		$known_widgets = get_option( 'tka_known_dashboard_widgets', [] );
		$updated = false;

		foreach ( $wp_meta_boxes['dashboard'] as $container_key => $containers ) {
			foreach ( $containers as $priority_key => $priorities ) {
				foreach ( $priorities as $widget_id => $widget ) {
					$title = ! empty( $widget['title'] ) ? wp_strip_all_tags( $widget['title'] ) : $widget_id;
					if ( ! isset( $known_widgets[ $widget_id ] ) || $known_widgets[ $widget_id ] !== $title ) {
						$known_widgets[ $widget_id ] = $title;
						$updated = true;
					}
				}
			}
		}

		if ( $updated ) {
			update_option( 'tka_known_dashboard_widgets', $known_widgets );
		}
	}

	/**
	 * Remove selected dashboard meta boxes for secondary administrators.
	 */
	public function disableDashboardWidgets(): void {
		if ( self::isCurrentUserInstaller() ) {
			return; // Installer owner retains all dashboard widgets
		}

		$widgets = $this->options['disabled_dashboard_widgets'] ?? [];
		if ( empty( $widgets ) ) {
			return;
		}

		foreach ( $widgets as $widget_id ) {
			foreach ( [ 'normal', 'side', 'advanced' ] as $context ) {
				remove_meta_box( $widget_id, 'dashboard', $context );
			}
		}
		
		// Fallback: visually hide the widgets in case they are injected later or via strange hooks
		add_action( 'admin_print_styles', function() use ( $widgets ) {
			$css = '';
			foreach ( $widgets as $widget_id ) {
				$css .= '#' . esc_attr( $widget_id ) . ' { display: none !important; } ';
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<style>' . $css . '</style>';
		} );
	}

	/**
	 * Re-order top level admin menu items based on custom order settings.
	 */
	public function reorderAdminMenus( array $menu_order ): array {
		$view = $this->getCurrentUserMenuView();
		if ( $view['type'] === 'none' ) {
			return $menu_order;
		}

		if ( $view['type'] === 'client' && ! current_user_can( 'manage_options' ) ) {
			return $menu_order; // Skip non-administrators in default client layout
		}

		$custom_order = $view['menu_order'];
		if ( empty( $custom_order ) ) {
			return $menu_order;
		}

		$new_order = [];
		foreach ( $custom_order as $slug ) {
			if ( in_array( $slug, $menu_order, true ) ) {
				$new_order[] = $slug;
			}
		}

		foreach ( $menu_order as $slug ) {
			if ( ! in_array( $slug, $new_order, true ) ) {
				$new_order[] = $slug;
			}
		}

		return $new_order;
	}

	/**
	 * Strictly block direct URL access to pages corresponding to hidden menu items.
	 */
	public function enforceDirectAccessRestriction(): void {
		if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Never lock out site owner/installer or superadmins
		if ( self::isCurrentUserInstaller() ) {
			return;
		}

		// Allow user impersonation switch-back action
		if ( isset( $_GET['worldsend_switch_back'] ) ) {
			return;
		}

		$view = $this->getCurrentUserMenuView();
		if ( empty( $view['block_direct_access'] ) || empty( $view['hidden_menus'] ) ) {
			return;
		}

		// Client layout restriction only applies to users who otherwise have admin access
		if ( $view['type'] === 'client' && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $pagenow;
		if ( empty( $pagenow ) ) {
			return;
		}

		$current_page      = $pagenow;
		$current_page_slug = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
		$current_post_type = sanitize_text_field( wp_unslash( $_GET['post_type'] ?? '' ) );

		if ( $current_page === 'post.php' && empty( $current_post_type ) && ! empty( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
			if ( $post_id > 0 ) {
				$current_post_type = get_post_type( $post_id ) ?: '';
			}
		}

		$hidden_menus = $view['hidden_menus'];

		// If Dashboard (index.php) is hidden and user lands on index.php (e.g. right after login),
		// gracefully redirect to their first allowed menu instead of terminating with 403.
		if ( $current_page === 'index.php' && empty( $current_page_slug ) && in_array( 'index.php', $hidden_menus, true ) ) {
			$allowed_slug = $this->getFirstAllowedAdminUrl( $view );
			if ( $allowed_slug ) {
				wp_safe_redirect( admin_url( $allowed_slug ) );
				exit;
			}
		}

		$is_blocked = false;
		foreach ( $hidden_menus as $menu_slug ) {
			if ( $this->isScreenMatchingMenuSlug( $menu_slug, $current_page, $current_page_slug, $current_post_type ) ) {
				$is_blocked = true;
				break;
			}
		}

		if ( $is_blocked ) {
			wp_die(
				esc_html__( 'Sorry, you are not allowed to access this page.', 'tka-site-utilities' ),
				esc_html__( 'Access Denied', 'tka-site-utilities' ),
				[
					'response'  => 403,
					'back_link' => true,
				]
			);
		}
	}

	/**
	 * Find the first available admin page slug that is not hidden in the given view.
	 */
	private function getFirstAllowedAdminUrl( array $view ): ?string {
		$menu_order   = $view['menu_order'] ?? [];
		$hidden_menus = $view['hidden_menus'] ?? [];

		foreach ( $menu_order as $slug ) {
			if ( ! in_array( $slug, $hidden_menus, true ) && $slug !== 'index.php' ) {
				return $slug;
			}
		}

		// Fallback check against standard post types/pages
		$fallbacks = [ 'edit.php?post_type=page', 'edit.php', 'upload.php' ];
		foreach ( $fallbacks as $fb ) {
			if ( ! in_array( $fb, $hidden_menus, true ) ) {
				return $fb;
			}
		}

		return null;
	}

	/**
	 * Check whether the current screen matches a hidden menu slug.
	 */
	private function isScreenMatchingMenuSlug( string $menu_slug, string $current_page, string $current_page_slug, string $current_post_type ): bool {
		// 1. Exact match on $pagenow
		if ( $current_page === $menu_slug ) {
			return true;
		}

		// 2. Exact match on admin page query parameter (?page=slug)
		if ( ! empty( $current_page_slug ) ) {
			if ( $current_page_slug === $menu_slug ) {
				return true;
			}
			$slug_query = [];
			parse_str( (string) wp_parse_url( $menu_slug, PHP_URL_QUERY ), $slug_query );
			if ( ! empty( $slug_query['page'] ) && $slug_query['page'] === $current_page_slug ) {
				return true;
			}

			// Submenu match: if the hidden slug has registered submenus that match $current_page_slug
			global $submenu;
			if ( ! empty( $submenu[ $menu_slug ] ) && is_array( $submenu[ $menu_slug ] ) ) {
				foreach ( $submenu[ $menu_slug ] as $sub_item ) {
					if ( ! empty( $sub_item[2] ) && ( $sub_item[2] === $current_page_slug || $sub_item[2] === $current_page ) ) {
						return true;
					}
				}
			}
		}

		// 3. Settings (options-general.php) and subpages
		if ( $menu_slug === 'options-general.php' ) {
			$options_screens = [
				'options-general.php',
				'options-writing.php',
				'options-reading.php',
				'options-discussion.php',
				'options-media.php',
				'options-permalink.php',
				'options-privacy.php',
			];
			if ( in_array( $current_page, $options_screens, true ) ) {
				return true;
			}
		}

		// 4. Plugins (plugins.php) and subpages
		if ( $menu_slug === 'plugins.php' ) {
			if ( in_array( $current_page, [ 'plugins.php', 'plugin-install.php', 'plugin-editor.php' ], true ) ) {
				return true;
			}
		}

		// 5. Appearance (themes.php) and subpages (excluding nav-menus.php if nav-menus.php is not explicitly hidden)
		if ( $menu_slug === 'themes.php' ) {
			if ( in_array( $current_page, [ 'themes.php', 'theme-install.php', 'theme-editor.php', 'customize.php' ], true ) ) {
				return true;
			}
		}

		// 6. Tools (tools.php) and subpages
		if ( $menu_slug === 'tools.php' ) {
			$tools_screens = [
				'tools.php',
				'import.php',
				'export.php',
				'site-health.php',
				'export-personal-data.php',
				'erase-personal-data.php',
			];
			if ( in_array( $current_page, $tools_screens, true ) ) {
				return true;
			}
		}

		// 7. Users (users.php) and adding users (allow editing own profile)
		if ( $menu_slug === 'users.php' ) {
			if ( in_array( $current_page, [ 'users.php', 'user-new.php' ], true ) ) {
				return true;
			}
			if ( $current_page === 'user-edit.php' && isset( $_GET['user_id'] ) && intval( $_GET['user_id'] ) !== get_current_user_id() ) {
				return true;
			}
		}

		// 8. Media (upload.php)
		if ( $menu_slug === 'upload.php' ) {
			if ( in_array( $current_page, [ 'upload.php', 'media-new.php' ], true ) ) {
				return true;
			}
		}

		// 9. Comments (edit-comments.php)
		if ( $menu_slug === 'edit-comments.php' ) {
			if ( in_array( $current_page, [ 'edit-comments.php', 'comment.php' ], true ) ) {
				return true;
			}
		}

		// 10. Posts & Custom Post Types
		$target_post_type = '';
		if ( str_starts_with( $menu_slug, 'edit.php?post_type=' ) ) {
			$target_post_type = sanitize_key( substr( $menu_slug, strlen( 'edit.php?post_type=' ) ) );
		} elseif ( $menu_slug === 'edit.php' ) {
			$target_post_type = 'post';
		}

		if ( ! empty( $target_post_type ) ) {
			if ( in_array( $current_page, [ 'edit.php', 'post-new.php', 'post.php' ], true ) ) {
				$effective_post_type = ! empty( $current_post_type ) ? $current_post_type : ( $current_page === 'edit.php' ? 'post' : '' );
				if ( $effective_post_type === $target_post_type ) {
					return true;
				}
			}

			if ( $current_page === 'edit-tags.php' && ! empty( $_GET['taxonomy'] ) ) {
				$tax = get_taxonomy( sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) );
				if ( $tax && in_array( $target_post_type, (array) $tax->object_type, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Output custom logo and custom CSS on the wp-login page.
	 */
	public function customLoginCustomizations(): void {
		$login_logo = $this->options['login_logo'] ?? '';
		$custom_css = $this->options['login_custom_css'] ?? '';

		echo '<style type="text/css">';
		if ( ! empty( $login_logo ) ) {
			echo '
			#login h1 a {
				background-image: url(' . esc_url( $login_logo ) . ') !important;
				background-size: contain !important;
				background-repeat: no-repeat !important;
				background-position: center !important;
				width: 100% !important;
				height: 80px !important;
				margin-bottom: 20px !important;
			}';
		}
		if ( ! empty( $custom_css ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo wp_strip_all_tags( $custom_css );
		}
		echo '</style>';
	}

	/**
	 * Link custom login logo to home URL.
	 */
	public function customLoginHeaderUrl(): string {
		return home_url();
	}

	/**
	 * Use site name as the custom logo text.
	 */
	public function customLoginHeaderText(): string {
		return get_bloginfo( 'name' );
	}

	/**
	 * Inject custom CSS to replace the WordPress logo in the Admin Bar.
	 */
	public function injectAdminLogoCss(): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		$admin_logo = $this->options['admin_logo'] ?? '';
		if ( empty( $admin_logo ) ) {
			return;
		}

		echo '
		<style type="text/css">
			#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
				content: "" !important;
				background-image: url(' . esc_url( $admin_logo ) . ') !important;
				background-size: contain !important;
				background-repeat: no-repeat !important;
				background-position: center !important;
				width: 20px !important;
				height: 20px !important;
				display: inline-block !important;
			}
		</style>';
	}

	/**
	 * Remove footer brandings and version strings for other administrators.
	 */
	public function registerFooterRemoval(): void {
		if ( self::isCurrentUserInstaller() ) {
			return; // Installer owner can see the original footer
		}
		add_filter( 'admin_footer_text', '__return_empty_string', 999 );
		add_filter( 'update_footer', '__return_empty_string', 999 );
	}
}

