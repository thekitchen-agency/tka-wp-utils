<?php

namespace TKA\WPUtils\Features;

/**
 * Reverts WordPress post/page editor to the Classic TinyMCE Editor.
 */
class ClassicEditor {

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		add_filter( 'use_block_editor_for_post', '__return_false', 100 );
		add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
	}
}
