<?php

namespace TKA\WPUtils\Features;

/**
 * Handles converting rendering-blocking CSS into asynchronous CSS.
 */
class AsyncCssManager {

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
		$async_styles = $this->options['async_styles'] ?? '';
		
		if ( empty( $async_styles ) ) {
			return;
		}

		add_filter( 'style_loader_tag', [ $this, 'makeStylesAsync' ], 999, 2 );
	}

	/**
	 * Convert standard stylesheet links into async loaded stylesheets.
	 */
	public function makeStylesAsync( string $html, string $handle ): string {
		if ( is_admin() || wp_is_json_request() ) {
			return $html;
		}

		$async_styles_list = preg_split( '/[\r\n,]+/', $this->options['async_styles'], -1, PREG_SPLIT_NO_EMPTY );
		$async_styles_list = array_map( 'trim', $async_styles_list );

		// Check if handle exactly matches or if handle contains the string
		$is_match = false;
		foreach ( $async_styles_list as $async_handle ) {
			if ( $handle === $async_handle || strpos( $handle, $async_handle ) !== false ) {
				$is_match = true;
				break;
			}
		}

		if ( $is_match ) {
			// Replace single quotes
			$html = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $html );
			// Replace double quotes
			$html = str_replace( 'media="all"', 'media="print" onload="this.media=\'all\'"', $html );
			
			// Fallback if media attribute was omitted
			if ( strpos( $html, 'media=' ) === false ) {
				$html = str_replace( '/>', ' media="print" onload="this.media=\'all\'" />', $html );
				$html = str_replace( '></link>', ' media="print" onload="this.media=\'all\'"></link>', $html );
			}
		}

		return $html;
	}
}
