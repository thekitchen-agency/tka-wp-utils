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

		add_filter( 'script_loader_tag', [ $this, 'deferScripts' ], 10, 3 );
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

		if ( in_array( $handle, $deferred_scripts, true ) ) {
			// Prevent multiple defer attributes if another plugin already added it
			if ( ! str_contains( $tag, ' defer' ) ) {
				$tag = str_replace( ' src', ' defer src', $tag );
			}
		}

		return $tag;
	}
}
