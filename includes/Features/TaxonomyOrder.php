<?php

namespace TKA\WPUtils\Features;

/**
 * Handles custom drag-and-drop ordering of taxonomies in the admin list view.
 */
class TaxonomyOrder {

	/**
	 * Order-enabled taxonomies array.
	 */
	private array $taxonomies;

	/**
	 * Constructor.
	 */
	public function __construct( array $taxonomies ) {
		$this->taxonomies = $taxonomies;
	}

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		if ( empty( $this->taxonomies ) ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'wp_ajax_tka_wp_utils_save_term_order', [ $this, 'saveOrderAjax' ] );
		add_filter( 'terms_clauses', [ $this, 'filterTermsClauses' ], 10, 3 );
	}

	/**
	 * Enqueue sorting scripts and inject CSS to list pages.
	 */
	public function enqueueAssets( string $hook ): void {
		if ( 'edit-tags.php' !== $hook ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->taxonomy, $this->taxonomies, true ) ) {
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

		$term_ids = $_POST['ids'] ?? [];
		if ( ! is_array( $term_ids ) || empty( $term_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid term IDs sequence.', 'tka-wp-utils' ) ] );
		}

		foreach ( $term_ids as $index => $term_id ) {
			$term_id = intval( $term_id );
			update_term_meta( $term_id, '_tka_term_order', $index );
		}

		do_action( 'tka_wp_utils_term_order_saved', $term_ids );
		wp_send_json_success( [ 'message' => __( 'Term order saved successfully.', 'tka-wp-utils' ) ] );
	}

	/**
	 * Intercept the term query clauses and inject our custom sorting via meta.
	 */
	public function filterTermsClauses( array $pieces, array $taxonomies, array $args ): array {
		// Only affect enabled taxonomies
		$intersect = array_intersect( $taxonomies, $this->taxonomies );
		if ( empty( $intersect ) ) {
			return $pieces;
		}

		// Don't override if an explicit custom orderby is requested
		// By default, $args['orderby'] is 'name' or 't.name'
		if ( ! isset( $args['orderby'] ) || in_array( $args['orderby'], [ 'name', 't.name', 'menu_order' ], true ) ) {
			global $wpdb;

			// Inject a LEFT JOIN so we don't drop terms that lack the meta key completely
			$pieces['join'] .= " LEFT JOIN {$wpdb->termmeta} AS tka_tm ON t.term_id = tka_tm.term_id AND tka_tm.meta_key = '_tka_term_order'";

			// WordPress appends $pieces['order'] (e.g. 'ASC') to the end automatically
			$pieces['orderby'] = 'ORDER BY CAST(COALESCE(tka_tm.meta_value, 0) AS SIGNED) ASC, t.name';
		}

		return $pieces;
	}
}
