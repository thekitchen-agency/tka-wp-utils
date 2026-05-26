<?php

namespace TKA\WPUtils\Features;

/**
 * Handles duplicating posts, pages, and custom post types safely.
 */
class ContentDuplicate {

	/**
	 * Duplicate-enabled post types array.
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

		add_filter( 'post_row_actions', [ $this, 'addDuplicateActionLink' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'addDuplicateActionLink' ], 10, 2 );
		add_action( 'admin_post_tka_wp_utils_duplicate_post', [ $this, 'handleDuplicationRequest' ] );
	}

	/**
	 * Add "Duplicate" row action link.
	 */
	public function addDuplicateActionLink( array $actions, \WP_Post $post ): array {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $actions;
		}

		if ( ! in_array( $post->post_type, $this->post_types, true ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=tka_wp_utils_duplicate_post&post_id=' . $post->ID ),
			'tka-duplicate-post-' . $post->ID
		);

		$actions['duplicate'] = sprintf(
			'<a href="%1$s" title="%2$s">%3$s</a>',
			esc_url( $url ),
			esc_attr__( 'Duplicate this item', 'tka-wp-utils' ),
			esc_html__( 'Duplicate', 'tka-wp-utils' )
		);

		return $actions;
	}

	/**
	 * Capture and process administrative post duplication calls.
	 */
	public function handleDuplicationRequest(): void {
		$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_die( esc_html__( 'Error: No post ID specified.', 'tka-wp-utils' ) );
		}

		check_admin_referer( 'tka-duplicate-post-' . $post_id );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'tka-wp-utils' ) );
		}

		$new_post_id = $this->duplicatePost( $post_id );

		if ( $new_post_id ) {
			// Redirect straight to post editor screen
			wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
			exit;
		} else {
			wp_die( esc_html__( 'Duplication failed: Could not clone post.', 'tka-wp-utils' ) );
		}
	}

	/**
	 * Deep duplication engine logic.
	 *
	 * Clones Title (Copy), Excerpt, Content, Taxonomies, and Custom Meta fields,
	 * saving the duplicate as a Draft under current user's authorship.
	 */
	public function duplicatePost( int $post_id ): int|bool {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}

		$current_user = wp_get_current_user();

		$args = [
			'post_title'     => $post->post_title . ' ' . __( '(Copy)', 'tka-wp-utils' ),
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_status'    => 'draft',
			'post_type'      => $post->post_type,
			'post_author'    => $current_user->ID,
			'post_parent'    => $post->post_parent,
			'menu_order'     => $post->menu_order,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
		];

		$new_post_id = wp_insert_post( $args );
		if ( is_wp_error( $new_post_id ) ) {
			return false;
		}

		// 1. Duplicate post custom fields (metadata)
		$meta_keys = get_post_custom_keys( $post_id );
		if ( $meta_keys ) {
			foreach ( $meta_keys as $key ) {
				$values = get_post_custom_values( $key, $post_id );
				foreach ( $values as $value ) {
					add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
				}
			}
		}

		// 2. Duplicate taxonomies (categories, tags, custom taxs)
		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );
			wp_set_object_terms( $new_post_id, $terms, $taxonomy );
		}

		return $new_post_id;
	}
}
