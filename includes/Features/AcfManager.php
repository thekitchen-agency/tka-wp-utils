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

		if ( ! empty( $this->options['acf_copy_paste'] ) ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueCopyPasteAssets' ] );
		}
	}

	/**
	 * Enqueue ACF copy and paste utility assets on post creation/editing pages.
	 */
	public function enqueueCopyPasteAssets( string $hook ): void {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'tka-acf-copy-paste-css',
			TKA_WP_UTILS_URL . 'admin/css/acf-copy-paste.css',
			[],
			TKA_WP_UTILS_VERSION
		);

		wp_enqueue_script(
			'tka-acf-copy-paste-js',
			TKA_WP_UTILS_URL . 'admin/js/acf-copy-paste.js',
			[ 'jquery' ],
			TKA_WP_UTILS_VERSION,
			true
		);

		wp_localize_script( 'tka-acf-copy-paste-js', 'tkaAcfSettings', [
			'enableMultiselect' => ! empty( $this->options['acf_copy_paste'] ) ? 1 : 0,
			'enableToggles'     => ! empty( $this->options['acf_copy_paste_toggles'] ) ? 1 : 0,
			'i18n'              => [
				'copy'          => __( 'Copy', 'tka-wp-utils' ),
				'copied'        => __( 'Copied!', 'tka-wp-utils' ),
				'paste'         => __( 'Paste Block', 'tka-wp-utils' ),
				'copySelected'  => __( 'Copy Selected', 'tka-wp-utils' ),
				'nothingCopied' => __( 'No copied block layout found in clipboard.', 'tka-wp-utils' ),
				'confirmPaste'  => __( 'Are you sure you want to paste the copied block(s)?', 'tka-wp-utils' ),
				'layoutsCopied' => __( 'Selected layout blocks successfully copied to clipboard!', 'tka-wp-utils' ),
			],
		] );
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
