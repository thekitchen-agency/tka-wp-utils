<?php

namespace TKA\WPUtils\Features;

/**
 * Handles WooCommerce speed and bloat optimizations.
 */
class WooCommerceManager {

	/**
	 * Active plugin options.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Constructor.
	 *
	 * @param array $options Active options.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	/**
	 * Register actions and filters.
	 */
	public function hook(): void {
		// 1. Disable WooCommerce scripts and styles on non-WooCommerce pages
		if ( ! empty( $this->options['wc_disable_scripts_non_wc'] ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'dequeueScriptsStylesNonWc' ], 99 );
		}

		// 2. Disable Cart Fragments AJAX
		$cart_fragments_mode = $this->options['wc_disable_cart_fragments'] ?? 'none';
		if ( 'none' !== $cart_fragments_mode ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'disableCartFragments' ], 99 );
		}

		// 3. Disable WooCommerce Block Styles
		if ( ! empty( $this->options['wc_disable_block_styles'] ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'disableBlockStyles' ], 100 );
		}

		// 4. Disable Password Strength Meter
		if ( ! empty( $this->options['wc_disable_password_meter'] ) ) {
			add_action( 'wp_print_scripts', [ $this, 'disablePasswordMeter' ], 100 );
		}

		// 5. Clean WooCommerce Admin UI (Marketing, Dashboard Widgets, Suggestions)
		if ( ! empty( $this->options['wc_clean_admin_ui'] ) ) {
			add_action( 'admin_menu', [ $this, 'cleanAdminMenu' ], 9999 );
			add_action( 'wp_dashboard_setup', [ $this, 'cleanDashboardWidgets' ], 40 );
			
			// Suppress marketplace suggestions and connection nags
			add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
			add_filter( 'woocommerce_show_admin_notice_helper', '__return_false' );
			add_filter( 'woocommerce_helper_suppress_connect_notice', '__return_true' );

			// Disable marketing feature in WooCommerce Admin to move coupons back to WooCommerce menu
			add_filter( 'woocommerce_admin_features', [ $this, 'disableWcMarketingFeature' ] );

			// CSS fallback to ensure menu items are hidden even if re-injected via JS
			add_action( 'admin_head', [ $this, 'injectCleanAdminUiStyles' ] );
		}
	}

	/**
	 * Helper to check if the current page is a WooCommerce page.
	 *
	 * @return bool True if WooCommerce page, cart, checkout, or account.
	 */
	private function isWcPage(): bool {
		if ( ! function_exists( 'is_woocommerce' ) ) {
			return false;
		}

		return is_woocommerce() || is_cart() || is_checkout() || is_account_page();
	}

	/**
	 * Dequeue WooCommerce frontend scripts and styles on non-shop pages.
	 */
	public function dequeueScriptsStylesNonWc(): void {
		if ( $this->isWcPage() ) {
			return;
		}

		// Dequeue Core WooCommerce Stylesheets
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_dequeue_style( 'woocommerce_prettyPhoto_css' );

		// Dequeue Core WooCommerce Scripts
		wp_dequeue_script( 'woocommerce' );
		wp_dequeue_script( 'wc-add-to-cart' );
		wp_dequeue_script( 'wc-add-to-cart-variation' );
		wp_dequeue_script( 'wc-single-product' );
		wp_dequeue_script( 'wc-country-select' );
		wp_dequeue_script( 'wc-address-i18n' );
		wp_dequeue_script( 'wc-checkout' );
		wp_dequeue_script( 'wc-cart' );
		wp_dequeue_script( 'wc-chosen' );
		wp_dequeue_script( 'prettyPhoto' );
		wp_dequeue_script( 'prettyPhoto-init' );
		wp_dequeue_script( 'jquery-blockui' );
		wp_dequeue_script( 'jquery-placeholder' );
		wp_dequeue_script( 'jquery-payment' );
	}

	/**
	 * Disable AJAX Cart Fragments based on option value.
	 */
	public function disableCartFragments(): void {
		$mode = $this->options['wc_disable_cart_fragments'] ?? 'none';

		if ( 'all' === $mode ) {
			wp_dequeue_script( 'wc-cart-fragments' );
			wp_deregister_script( 'wc-cart-fragments' );
		} elseif ( 'non_shop' === $mode && ! $this->isWcPage() ) {
			wp_dequeue_script( 'wc-cart-fragments' );
			wp_deregister_script( 'wc-cart-fragments' );
		}
	}

	/**
	 * Dequeue and deregister all WooCommerce Gutenberg block styles.
	 */
	public function disableBlockStyles(): void {
		global $wp_styles;
		if ( ! empty( $wp_styles->registered ) ) {
			foreach ( array_keys( $wp_styles->registered ) as $handle ) {
				if ( str_starts_with( $handle, 'wc-blocks-' ) ) {
					wp_dequeue_style( $handle );
					wp_deregister_style( $handle );
				}
			}
		}
	}

	/**
	 * Dequeue customer password strength meter and zxcvbn scripts.
	 */
	public function disablePasswordMeter(): void {
		wp_dequeue_script( 'wc-password-strength-meter' );
		wp_deregister_script( 'zxcvbn' );
	}

	/**
	 * Remove marketing and payments submenus and items from Admin sidebar.
	 */
	public function cleanAdminMenu(): void {
		global $menu, $submenu;

		// Remove Marketing menu page
		remove_menu_page( 'woocommerce-marketing' );

		// Remove Payments menu page
		remove_menu_page( 'wc-admin&path=/payments/connect' );
		remove_menu_page( 'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM' );

		// Remove submenus under WooCommerce if present
		remove_submenu_page( 'woocommerce', 'woocommerce-marketing' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/payments' );
		remove_submenu_page( 'woocommerce', 'wc-admin&path=/payments/connect' );

		// Clean up top-level Payments menu shortcuts pointing to checkout settings
		if ( ! empty( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[2] ) ) {
					$slug = $item[2];
					if ( str_contains( $slug, 'page=wc-settings&tab=checkout' ) || str_contains( $slug, 'path=/payments' ) ) {
						unset( $menu[ $key ] );
					}
				}
			}
		}
	}

	/**
	 * Disable the marketing feature in WooCommerce Admin features.
	 *
	 * @param array $features Active features.
	 * @return array Filtered features.
	 */
	public function disableWcMarketingFeature( array $features ): array {
		if ( ( $key = array_search( 'marketing', $features, true ) ) !== false ) {
			unset( $features[ $key ] );
		}
		return $features;
	}

	/**
	 * Inject inline CSS to hide Marketing and Payments menu items to ensure they are hidden even if injected via JS.
	 */
	public function injectCleanAdminUiStyles(): void {
		?>
		<style type="text/css">
			#adminmenu li[id*="woocommerce-marketing"],
			#adminmenu li[id*="PAYMENTS_MENU_ITEM"],
			#adminmenu li[id*="payments-connect"],
			#adminmenu a[href*="woocommerce-marketing"],
			#adminmenu a[href*="PAYMENTS_MENU_ITEM"],
			#adminmenu a[href*="path=/payments"] {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Remove WooCommerce-specific dashboard widgets.
	 */
	public function cleanDashboardWidgets(): void {
		remove_meta_box( 'woocommerce_dashboard_status', 'dashboard', 'normal' );
		remove_meta_box( 'woocommerce_dashboard_recent_reviews', 'dashboard', 'normal' );
	}
}
