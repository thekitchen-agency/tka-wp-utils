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
	 * Scripts to be printed in the footer.
	 *
	 * @var array
	 */
	private array $vanilla_scripts = [];

	/**
	 * Constructor.
	 *
	 * @param array $options Active options.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	/**
	 * Helper to enqueue raw Vanilla JS without WooCommerce's forced jQuery wrapper.
	 */
	private function enqueueVanillaJs( string $id, string $script ): void {
		if ( empty( $this->vanilla_scripts ) ) {
			add_action( 'wp_footer', function() {
				if ( empty( $this->vanilla_scripts ) ) return;
				echo "<script type='text/javascript'>\n";
				echo "document.addEventListener('DOMContentLoaded', function() {\n";
				foreach ( $this->vanilla_scripts as $s ) {
					echo $s . "\n";
				}
				echo "});\n";
				echo "</script>\n";
			}, 999 );
		}
		$this->vanilla_scripts[ $id ] = $script;
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

		// 6. Direct Buy Now Button
		if ( ! empty( $this->options['wc_buy_now_button'] ) ) {
			add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'addBuyNowButton' ], 1 );
		}

		// 7. Redirect SKU in URL to single product page
		if ( ! empty( $this->options['wc_redirect_sku'] ) ) {
			add_action( 'template_redirect', [ $this, 'redirectSkuInUrlToProduct' ] );
		}

		// 8. Remove add-to-cart from URL on redirect
		if ( ! empty( $this->options['wc_remove_add_to_cart_from_url'] ) ) {
			add_filter( 'woocommerce_add_to_cart_redirect', 'wp_get_referer' );
		}

		// 9. Hide "View Cart" AJAX button on Shop page
		if ( ! empty( $this->options['wc_hide_view_cart_shop'] ) ) {
			add_action( 'wp_footer', [ $this, 'hideAjaxViewCartButton' ] );
		}

		// 10. Plus / Minus quantity buttons
		if ( ! empty( $this->options['wc_plus_minus_quantity'] ) ) {
			add_action( 'woocommerce_before_quantity_input_field', [ $this, 'displayQuantityMinus' ] );
			add_action( 'woocommerce_after_quantity_input_field', [ $this, 'displayQuantityPlus' ] );
			add_action( 'woocommerce_before_single_product', [ $this, 'addCartQuantityPlusMinus' ] );
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

	/**
	 * Add "Buy Now" Button on single product page.
	 */
	public function addBuyNowButton(): void {
		global $product;
		if ( ! $product ) {
			return;
		}

		$product_id  = $product->get_id();
		$buy_now_url = esc_url( add_query_arg(
			[ 'products' => $product_id . ':1' ],
			'/checkout-link/'
		) );

		echo ' &mdash; OR &mdash; <a href="' . esc_url( $buy_now_url ) . '" class="single_add_to_cart_button button buy_now_button" data-product-id="' . esc_attr( $product_id ) . '">' . esc_html__( 'Buy Now', 'tka-wp-utils' ) . '</a>';

		$this->enqueueVanillaJs( 'buy_now', "
			function updateBuyNowURL() {
				var form = document.querySelector('form.cart');
				if (!form) return;
				var qtyInput = form.querySelector('input.qty');
				var qty = qtyInput ? (qtyInput.value || 1) : 1;
				
				var buyNowBtn = document.querySelector('a.buy_now_button');
				if (!buyNowBtn) return;
				var productId = buyNowBtn.getAttribute('data-product-id');
				
				var variationInput = form.querySelector('input[name=\"variation_id\"]');
				var variationId = variationInput ? variationInput.value : '';
				if (variationId && variationId !== '0') {
					productId = variationId;
				}
				
				var newUrl = '/checkout-link/?products=' + productId + ':' + qty;
				buyNowBtn.setAttribute('href', newUrl);
			}
			
			document.addEventListener('change', function(e) {
				if (e.target && e.target.classList.contains('qty')) updateBuyNowURL();
			});
			document.addEventListener('input', function(e) {
				if (e.target && e.target.classList.contains('qty')) updateBuyNowURL();
			});
			
			// Fallback: WooCommerce triggers variations solely through jQuery.
			// We hook in just in case jQuery/WC is present to catch the exact moment a variation is selected.
			if (typeof jQuery !== 'undefined') {
				jQuery(document).on('show_variation hide_variation', updateBuyNowURL);
			}
			
			updateBuyNowURL();
		" );
	}

	/**
	 * Redirect custom SKU URLs (which fall back to a 404 page) to their single product permalinks.
	 */
	public function redirectSkuInUrlToProduct(): void {
		if ( is_404() && isset( $GLOBALS['wp']->request ) ) {
			$sku = sanitize_text_field( $GLOBALS['wp']->request );
			if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
				$id = wc_get_product_id_by_sku( $sku );
				if ( $id ) {
					wp_safe_redirect( get_permalink( $id ) );
					exit;
				}
			}
		}
	}

	/**
	 * Enqueue JS script to hide the AJAX-injected "View Cart" button on Shop pages.
	 * Uses a native MutationObserver instead of relying on jQuery events.
	 */
	public function hideAjaxViewCartButton(): void {
		$this->enqueueVanillaJs( 'hide_view_cart', "
			const observer = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					mutation.addedNodes.forEach(function(node) {
						if (node.nodeType === 1) {
							if (node.classList.contains('added_to_cart') && node.classList.contains('wc-forward')) {
								node.remove();
							} else if (node.querySelectorAll) {
								node.querySelectorAll('.added_to_cart.wc-forward').forEach(function(el) {
									el.remove();
								});
							}
						}
					});
				});
			});
			observer.observe(document.body, { childList: true, subtree: true });
		" );
	}

	/**
	 * Output minus button before quantity input field.
	 */
	public function displayQuantityMinus(): void {
		if ( ! is_product() ) {
			return;
		}
		echo '<button type="button" class="minus">-</button>';
	}

	/**
	 * Output plus button after quantity input field.
	 */
	public function displayQuantityPlus(): void {
		if ( ! is_product() ) {
			return;
		}
		echo '<button type="button" class="plus">+</button>';
	}

	/**
	 * Enqueue JavaScript to support Plus & Minus interactive increment/decrement.
	 * 100% Vanilla JS.
	 */
	public function addCartQuantityPlusMinus(): void {
		$this->enqueueVanillaJs( 'qty_plus_minus', "
			document.addEventListener('click', function(e) {
				if (e.target && (e.target.classList.contains('plus') || e.target.classList.contains('minus'))) {
					var form = e.target.closest('form.cart');
					if (!form) return;
					var qty = form.querySelector('.qty');
					if (!qty) return;
					
					var val = parseFloat(qty.value) || 0;
					var max = parseFloat(qty.getAttribute('max'));
					var min = parseFloat(qty.getAttribute('min')) || 1;
					var step = parseFloat(qty.getAttribute('step')) || 1;

					if (e.target.classList.contains('plus')) {
						if (max && (max <= val)) {
							qty.value = max;
						} else {
							qty.value = val + step;
						}
					} else {
						if (min && (min >= val)) {
							qty.value = min;
						} else if (val > min) {
							qty.value = val - step;
						}
					}
					
					// Trigger native change event so other scripts can pick it up
					qty.dispatchEvent(new Event('change', { bubbles: true }));
					
					// WooCommerce core depends on jQuery for its own internal cart updates.
					// We dispatch it via jQuery only if WC has loaded it.
					if (typeof jQuery !== 'undefined') {
						jQuery(qty).trigger('change');
					}
				}
			});
		" );
	}
}
