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

		if ( ! empty( $this->options['admin_bar_cleanup'] ) ) {
			add_action( 'wp_before_admin_bar_render', [ $this, 'cleanupAdminBar' ], 999 );
		}

		if ( ! empty( $this->options['disabled_dashboard_widgets'] ) ) {
			add_action( 'wp_dashboard_setup', [ $this, 'disableDashboardWidgets' ], 999 );
		}

		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', [ $this, 'reorderAdminMenus' ], 999 );

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
	 * Restrict standard WordPress sidebar menus for secondary administrators.
	 */
	public function hideAdminMenus(): void {
		$is_owner = self::isCurrentUserInstaller();

		if ( ! $is_owner && ! current_user_can( 'manage_options' ) ) {
			return; // Only apply constraints to owners or other administrators
		}

		$hidden_menus = $is_owner 
			? ( $this->options['owner_hidden_admin_menus'] ?? [] )
			: ( $this->options['hidden_admin_menus'] ?? [] );

		if ( empty( $hidden_menus ) ) {
			return;
		}

		foreach ( $hidden_menus as $menu_slug ) {
			remove_menu_page( $menu_slug );
		}

		// If Appearance (themes.php) is hidden but the user can edit menus,
		// expose "Menus" (nav-menus.php) as a standalone top-level menu page.
		if ( in_array( 'themes.php', $hidden_menus, true ) && current_user_can( 'edit_theme_options' ) ) {
			add_menu_page(
				__( 'Menus', 'tka-wp-utils' ),
				__( 'Menus', 'tka-wp-utils' ),
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
		$installer_id = get_option( 'tka_wp_utils_installer_id' );
		if ( ! $installer_id ) {
			$admins = get_users( [
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
			] );
			if ( ! empty( $admins ) ) {
				$installer_id = $admins[0]->ID;
				update_option( 'tka_wp_utils_installer_id', $installer_id );
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
		return $current_user_id === self::getInstallerId();
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
	 * Remove selected dashboard meta boxes for secondary administrators.
	 */
	public function disableDashboardWidgets(): void {
		if ( self::isCurrentUserInstaller() ) {
			return; // Installer owner retains all dashboard widgets
		}

		$widgets = $this->options['disabled_dashboard_widgets'] ?? [];
		foreach ( $widgets as $widget_id ) {
			foreach ( [ 'normal', 'side', 'advanced' ] as $context ) {
				remove_meta_box( $widget_id, 'dashboard', $context );
			}
		}
	}

	/**
	 * Re-order top level admin menu items based on custom order settings.
	 */
	public function reorderAdminMenus( array $menu_order ): array {
		$is_owner = self::isCurrentUserInstaller();

		if ( ! $is_owner && ! current_user_can( 'manage_options' ) ) {
			return $menu_order; // Skip non-administrators
		}

		$custom_order = $is_owner
			? ( $this->options['owner_admin_menu_order'] ?? [] )
			: ( $this->options['admin_menu_order'] ?? [] );

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

