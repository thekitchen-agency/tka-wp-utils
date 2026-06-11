<?php

namespace TKA\WPUtils\Features;

/**
 * Handles performance optimizations for the WPML (WordPress Multilingual) plugin.
 */
class WpmlOptimizer {

	/**
	 * Stored plugin options.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Constructor.
	 *
	 * @param array $options Saved options.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	/**
	 * Register hooks if the feature is enabled.
	 */
	public function hook(): void {
		if ( empty( $this->options['wpml_optimization_enabled'] ) ) {
			return;
		}

		// Disable Adjust IDs setting to reduce queries
		if ( ! empty( $this->options['wpml_disable_adjust_ids'] ) ) {
			add_filter( 'wpml_should_use_theme_adjustments', '__return_false', 999 );
		}

		// Suppress Canonical URL Redirects for REST & AJAX requests
		if ( ! empty( $this->options['wpml_disable_canonical_redirects_ajax'] ) ) {
			add_filter( 'wpml_is_redirected', [ $this, 'maybeSuppressRedirection' ], 10, 2 );
		}
	}

	/**
	 * Suppress redirects during Ajax or REST requests.
	 *
	 * @param bool $is_redirected Default redirect state.
	 * @param int  $post_id        Target post ID.
	 * @return bool Modified redirect state.
	 */
	public function maybeSuppressRedirection( bool $is_redirected, $post_id ): bool {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		return $is_redirected;
	}
}
