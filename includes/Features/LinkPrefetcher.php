<?php

namespace TKA\WPUtils\Features;

/**
 * Handles hover prefetching for internal links to speed up page navigation.
 */
class LinkPrefetcher {

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		// Only enqueue on frontend
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueuePrefetchScript' ] );
	}

	/**
	 * Enqueue the link prefetching script.
	 */
	public function enqueuePrefetchScript(): void {
		// Do not run in admin, on login screens, or if it is a JSON/REST request
		if ( is_admin() || wp_is_json_request() || ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'wp-login.php' ) ) {
			return;
		}

		$js_path = TKA_SITE_UTILITIES_PATH . 'admin/js/link-prefetcher.js';
		$js_version = file_exists( $js_path ) ? filemtime( $js_path ) : TKA_SITE_UTILITIES_VERSION;

		wp_enqueue_script(
			'tka-link-prefetcher',
			TKA_SITE_UTILITIES_URL . 'admin/js/link-prefetcher.js',
			[],
			$js_version,
			true // Load in footer
		);
	}
}
