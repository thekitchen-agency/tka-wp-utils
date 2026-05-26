<?php

namespace TKA\WPUtils\Features;

/**
 * Reverts WordPress widgets interface to the Classic Widget manager.
 */
class ClassicWidgets {

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		add_filter( 'use_widgets_block_editor', '__return_false', 100 );
	}
}
