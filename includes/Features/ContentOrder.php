<?php

namespace TKA\WPUtils\Features;

/**
 * Handles custom drag-and-drop ordering of posts in the admin list view.
 */
class ContentOrder {

	/**
	 * Order-enabled post types array.
	 */
	private array $post_types;

	/**
	 * Constructor.
	 */
	public function __construct( array $post_types ) {
		$this->post_types = $post_types;
	}

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		if ( empty( $this->post_types ) ) {
			return;
		}

		add_action( 'init', [ $this, 'addOrderSupport' ], 9999 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'wp_ajax_tka_wp_utils_save_order', [ $this, 'saveOrderAjax' ] );
		add_action( 'pre_get_posts', [ $this, 'filterPostsQueryOrder' ] );
	}

	/**
	 * Add page-attributes support dynamically to post types to enable menu_order.
	 */
	public function addOrderSupport(): void {
		foreach ( $this->post_types as $post_type ) {
			add_post_type_support( $post_type, 'page-attributes' );
		}
	}

	/**
	 * Enqueue sorting scripts and inject CSS to list pages.
	 */
	public function enqueueAssets( string $hook ): void {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, $this->post_types, true ) ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script(
			'tka-wp-utils-order-js',
			TKA_WP_UTILS_URL . 'admin/js/admin-order.js',
			[ 'jquery', 'jquery-ui-sortable' ],
			TKA_WP_UTILS_VERSION,
			true
		);

		wp_localize_script( 'tka-wp-utils-order-js', 'tkaWpUtilsOrder', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'tka-wp-utils-order-nonce' ),
		] );

		// Inject custom inline styling for drag-and-drop feedback
		wp_add_inline_style( 'wp-admin', '
			.wp-list-table tbody tr { cursor: move !important; }
			.wp-list-table tbody tr.ui-sortable-placeholder { background: #f8fafc !important; border: 2px dashed #cbd5e1 !important; visibility: visible !important; height: 50px !important; }
			.wp-list-table tbody tr.ui-sortable-helper { background: #ffffff !important; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1) !important; display: table !important; width: 100% !important; }
		' );
	}

	/**
	 * Handle sorting order AJAX updates safely.
	 */
	public function saveOrderAjax(): void {
		check_ajax_referer( 'tka-wp-utils-order-nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'tka-wp-utils' ) ] );
		}

		$post_ids = $_POST['ids'] ?? ($_POST['post_ids'] ?? []);
		if ( ! is_array( $post_ids ) || empty( $post_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post IDs sequence.', 'tka-wp-utils' ) ] );
		}

		global $wpdb;

		foreach ( $post_ids as $index => $post_id ) {
			$post_id = intval( $post_id );
			$wpdb->update(
				$wpdb->posts,
				[ 'menu_order' => $index ],
				[ 'ID' => $post_id ],
				[ '%d' ],
				[ '%d' ]
			);

			clean_post_cache( $post_id );
		}

		do_action( 'tka_wp_utils_order_saved', $post_ids );
		wp_send_json_success( [ 'message' => __( 'Order saved successfully.', 'tka-wp-utils' ) ] );
	}

	/**
	 * Hook and enforce menu_order ASC sorting for enabled post types.
	 */
	public function filterPostsQueryOrder( $query ): void {
		if ( is_admin() ) {
			// In admin post lists, sort by menu_order if no column orderby is actively clicked
			if ( $query->is_main_query() ) {
				$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
				if ( $screen && 'edit' === $screen->base && in_array( $screen->post_type, $this->post_types, true ) ) {
					if ( ! isset( $_GET['orderby'] ) ) {
						$query->set( 'orderby', 'menu_order' );
						$query->set( 'order', 'ASC' );
					}
				}
			}
			return;
		}

		// On frontend main loop queries, sort by menu_order ASC
		if ( $query->is_main_query() ) {
			$post_type = $query->get( 'post_type' );
			if ( empty( $post_type ) ) {
				$post_type = 'post';
			}

			if ( is_string( $post_type ) && in_array( $post_type, $this->post_types, true ) ) {
				$query->set( 'orderby', 'menu_order' );
				$query->set( 'order', 'ASC' );
			}
		}
	}
}
