<?php

namespace TKA\WPUtils\Features;

/**
 * Manages Gutenberg block editor suppression globally or by specific post types.
 */
class GutenbergManager {

	/**
	 * Gutenberg disabling mode ('all' or 'post_types').
	 */
	private string $mode;

	/**
	 * Array of post type keys where Gutenberg should be disabled.
	 */
	private array $post_types;

	/**
	 * Whether to dequeue block stylesheets on the frontend.
	 */
	private bool $dequeue_styles;

	/**
	 * Constructor.
	 */
	public function __construct( string $mode, array $post_types, bool $dequeue_styles = false ) {
		$this->mode           = $mode;
		$this->post_types     = $post_types;
		$this->dequeue_styles = $dequeue_styles;
	}

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		if ( 'all' === $this->mode ) {
			add_filter( 'use_block_editor_for_post', '__return_false', 100 );
			add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
		} elseif ( 'post_types' === $this->mode && ! empty( $this->post_types ) ) {
			add_filter( 'use_block_editor_for_post', [ $this, 'filterUseBlockEditorForPost' ], 100, 2 );
			add_filter( 'use_block_editor_for_post_type', [ $this, 'filterUseBlockEditorForPostType' ], 100, 2 );
		} elseif ( 'wc_except_cart_checkout' === $this->mode ) {
			add_filter( 'use_block_editor_for_post', [ $this, 'filterWcExceptCartCheckoutPost' ], 100, 2 );
			add_filter( 'use_block_editor_for_post_type', [ $this, 'filterWcExceptCartCheckoutPostType' ], 100, 2 );
		}

		if ( $this->dequeue_styles ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'dequeueBlockStyles' ], 100 );
		}
	}

	/**
	 * Dequeue Gutenberg block stylesheets on frontend.
	 */
	public function dequeueBlockStyles(): void {
		if ( ! is_admin() ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'wc-blocks-style' );
			wp_dequeue_style( 'wc-block-style' );
		}
	}

	/**
	 * Suppress Gutenberg for specific posts by evaluating their post type.
	 */
	public function filterUseBlockEditorForPost( bool $use_block_editor, $post ): bool {
		if ( ! $post ) {
			return $use_block_editor;
		}

		$post_type = get_post_type( $post );
		if ( in_array( $post_type, $this->post_types, true ) ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Suppress Gutenberg for specific registered post types.
	 */
	public function filterUseBlockEditorForPostType( bool $use_block_editor, string $post_type ): bool {
		if ( in_array( $post_type, $this->post_types, true ) ) {
			return false;
		}

		return $use_block_editor;
	}

	/**
	 * Suppress Gutenberg everywhere except on WooCommerce Cart and Checkout pages.
	 */
	public function filterWcExceptCartCheckoutPost( bool $use_block_editor, $post ): bool {
		if ( ! $post ) {
			return $use_block_editor;
		}

		$post_type = get_post_type( $post );
		if ( 'page' !== $post_type ) {
			return false;
		}

		// It is a page. Check if it's WooCommerce Cart or Checkout.
		$post_id = is_object( $post ) ? $post->ID : (int) $post;
		if ( function_exists( 'wc_get_page_id' ) ) {
			$cart_id     = (int) wc_get_page_id( 'cart' );
			$checkout_id = (int) wc_get_page_id( 'checkout' );

			if ( $post_id === $cart_id || $post_id === $checkout_id ) {
				return $use_block_editor;
			}
		}

		return false;
	}

	/**
	 * Suppress Gutenberg for all post types except 'page'.
	 */
	public function filterWcExceptCartCheckoutPostType( bool $use_block_editor, string $post_type ): bool {
		if ( 'page' === $post_type ) {
			return $use_block_editor;
		}

		return false;
	}
}
