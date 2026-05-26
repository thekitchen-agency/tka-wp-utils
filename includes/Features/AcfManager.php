<?php

namespace TKA\WPUtils\Features;

/**
 * Handles security hardening and menu visibility customization for Advanced Custom Fields.
 */
class AcfManager {

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
	 * Register hooks for active ACF utilities.
	 */
	public function hook(): void {
		if ( ! empty( $this->options['hide_acf_menu'] ) ) {
			add_filter( 'acf/settings/show_admin', [ $this, 'controlAdminVisibility' ], 999 );
		}

		if ( ! empty( $this->options['disable_acf_shortcode'] ) ) {
			add_action( 'acf/init', [ $this, 'disableAcfShortcode' ] );
		}

		if ( ! empty( $this->options['acf_custom_json_path'] ) ) {
			add_filter( 'acf/settings/save_json', [ $this, 'getCustomJsonSavePath' ] );
			add_filter( 'acf/settings/load_json', [ $this, 'getCustomJsonLoadPaths' ] );
		}
	}

	/**
	 * Filter visibility of the ACF Admin Menu item.
	 */
	public function controlAdminVisibility( bool $show ): bool {
		if ( ! AdminInterface::isCurrentUserInstaller() ) {
			return false;
		}
		return $show;
	}

	/**
	 * Disable the [acf] shortcode completely.
	 */
	public function disableAcfShortcode(): void {
		acf_update_setting( 'enable_shortcode', false );
	}

	/**
	 * Get theme-independent custom directory for saving local ACF JSONs.
	 */
	public function getCustomJsonSavePath( string $path ): string {
		$custom_path = WP_CONTENT_DIR . '/acf-json';
		if ( ! file_exists( $custom_path ) ) {
			wp_mkdir_p( $custom_path );
		}
		return $custom_path;
	}

	/**
	 * Register theme-independent custom directory for loading local ACF JSONs.
	 */
	public function getCustomJsonLoadPaths( array $paths ): array {
		$custom_path = WP_CONTENT_DIR . '/acf-json';
		if ( file_exists( $custom_path ) ) {
			$paths[] = $custom_path;
		}
		return $paths;
	}
}
