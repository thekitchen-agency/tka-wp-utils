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
	 * Constructor.
	 */
	public function __construct( string $mode, array $post_types ) {
		$this->mode       = $mode;
		$this->post_types = $post_types;
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
}
