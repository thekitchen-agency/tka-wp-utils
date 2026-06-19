<?php

namespace TKA\WPUtils\Features;

/**
 * Handles deferring of selected frontend scripts.
 */
class ScriptDeferManager {

	/**
	 * Active option settings array.
	 */
	private array $options;

	/**
	 * Constructor.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	/**
	 * Register hooks.
	 */
	public function hook(): void {
		$deferred_scripts = $this->options['deferred_scripts'] ?? [];
		$deferred_scripts_custom = $this->options['deferred_scripts_custom'] ?? '';

		if ( empty( $deferred_scripts ) && empty( $deferred_scripts_custom ) ) {
			return;
		}

		// Modern WP 6.3+ native defer strategy (fixes inline scripts issues)
		add_action( 'wp_enqueue_scripts', [ $this, 'applyDeferStrategy' ], 9999 );
		add_action( 'wp_print_scripts', [ $this, 'applyDeferStrategy' ], 9 );
		add_action( 'wp_print_footer_scripts', [ $this, 'applyDeferStrategy' ], 9 );

		// Force WP to delay inline scripts associated with deferred handles
		add_filter( 'wp_inline_script_attributes', [ $this, 'delayInlineScripts' ], 10, 2 );

		// Fallback for older WP or dynamically injected tags
		add_filter( 'script_loader_tag', [ $this, 'deferScripts' ], 10, 3 );
	}

	/**
	 * Helper to get merged deferred scripts list.
	 */
	private function getDeferredScripts(): array {
		$deferred_scripts = $this->options['deferred_scripts'] ?? [];
		$deferred_scripts_custom = $this->options['deferred_scripts_custom'] ?? '';

		if ( ! is_array( $deferred_scripts ) ) {
			$deferred_scripts = [];
		}

		if ( ! empty( $deferred_scripts_custom ) ) {
			// Split by newlines, carriage returns, or commas
			$custom_scripts = preg_split( '/[\r\n,]+/', $deferred_scripts_custom, -1, PREG_SPLIT_NO_EMPTY );
			$custom_scripts = array_map( 'trim', $custom_scripts );
			$deferred_scripts = array_merge( $deferred_scripts, $custom_scripts );
		}

		return $deferred_scripts;
	}

	/**
	 * Apply WP 6.3+ strategy API to defer scripts cleanly.
	 */
	public function applyDeferStrategy(): void {
		if ( is_admin() || wp_is_json_request() ) {
			return;
		}

		global $wp_scripts;
		if ( ! isset( $wp_scripts ) ) {
			return;
		}

		$deferred_scripts = $this->getDeferredScripts();
		
		foreach ( $deferred_scripts as $handle ) {
			if ( isset( $wp_scripts->registered[ $handle ] ) ) {
				$wp_scripts->registered[ $handle ]->add_data( 'strategy', 'defer' );
			}
		}
	}

	/**
	 * Natively force browsers to defer inline scripts by making them JS Modules.
	 */
	public function delayInlineScripts( array $attributes, string $javascript ): array {
		if ( empty( $attributes['id'] ) ) {
			return $attributes;
		}

		$id = $attributes['id'];
		$deferred_scripts = $this->getDeferredScripts();

		foreach ( $deferred_scripts as $handle ) {
			if ( str_starts_with( $id, "{$handle}-js-" ) ) {
				// Modern browsers defer `type="module"` scripts by default.
				// This executes them in the exact same queue as classic `<script defer>` files,
				// ensuring they don't fire until `wp` and `wp-i18n` have loaded, 
				// completely bypassing WP Core's failing wrapper logic.
				$attributes['type'] = 'module';
				// Remove the useless data strategy
				unset( $attributes['data-wp-strategy'] );
				break;
			}
		}

		return $attributes;
	}

	/**
	 * Defer specified scripts by appending the defer attribute.
	 *
	 * @param string $tag    The `<script>` tag for the enqueued script.
	 * @param string $handle The script's registered handle.
	 * @param string $src    The script's source URL.
	 * @return string
	 */
	public function deferScripts( string $tag, string $handle, string $src ): string {
		// Do not defer in the admin area
		if ( is_admin() || wp_is_json_request() ) {
			return $tag;
		}

		$deferred_scripts = $this->getDeferredScripts();

		if ( in_array( $handle, $deferred_scripts, true ) ) {
			// Prevent multiple defer attributes if another plugin already added it
			if ( ! str_contains( $tag, ' defer' ) ) {
				$tag = str_replace( ' src', ' defer src', $tag );
			}
		}

		return $tag;
	}
}

