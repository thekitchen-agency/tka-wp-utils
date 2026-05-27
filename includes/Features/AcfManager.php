<?php

namespace TKA\WPUtils\Features;

/**
 * Handles security hardening and menu visibility customization for Advanced Custom Fields.
 */
class AcfManager
{

	/**
	 * Active option settings array.
	 */
	private array $options;

	/**
	 * Constructor.
	 */
	public function __construct(array $options)
	{
		$this->options = $options;
	}

	/**
	 * Register hooks for active ACF utilities.
	 */
	public function hook(): void
	{
		if (!empty($this->options['hide_acf_menu'])) {
			add_filter('acf/settings/show_admin', [$this, 'controlAdminVisibility'], 999);
		}

		if (!empty($this->options['disable_acf_shortcode'])) {
			add_action('acf/init', [$this, 'disableAcfShortcode']);
		}

		if (!empty($this->options['acf_custom_json_path'])) {
			add_filter('acf/settings/save_json', [$this, 'getCustomJsonSavePath']);
			add_filter('acf/settings/load_json', [$this, 'getCustomJsonLoadPaths']);
		}

		if (!empty($this->options['acf_copy_paste'])) {
			add_action('admin_enqueue_scripts', [$this, 'enqueueCopyPasteAssets']);
		}

		if (!empty($this->options['acf_layout_modal'])) {
			add_action('admin_enqueue_scripts', [$this, 'enqueueLayoutModalAssets']);
		}
	}

	/**
	 * Enqueue ACF visual layout selection modal assets on post creation/editing pages.
	 */
	public function enqueueLayoutModalAssets(string $hook): void
	{
		if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
			return;
		}

		$css_path = TKA_WP_UTILS_PATH . 'admin/css/acf-layout-modal.css';
		$js_path = TKA_WP_UTILS_PATH . 'admin/js/acf-layout-modal.js';

		$css_version = file_exists($css_path) ? filemtime($css_path) : TKA_WP_UTILS_VERSION;
		$js_version = file_exists($js_path) ? filemtime($js_path) : TKA_WP_UTILS_VERSION;

		wp_enqueue_style(
			'tka-acf-layout-modal-css',
			TKA_WP_UTILS_URL . 'admin/css/acf-layout-modal.css',
			[],
			$css_version
		);

		wp_enqueue_script(
			'tka-acf-layout-modal-js',
			TKA_WP_UTILS_URL . 'admin/js/acf-layout-modal.js',
			['jquery'],
			$js_version,
			true
		);

		$default_metadata = [
			'block_text' => [
				'description' => __('Standard rich text block. Ideal for paragraphs, sub-headings, lists, and formatted textual copy.', 'tka-wp-utils'),
				'icon'        => 'dashicons-editor-paragraph',
				'category'    => 'content',
			],
			'block_quote' => [
				'description' => __('Sleek blockquote styling. Best for drawing attention to testimonials, highlighted statements, or citations.', 'tka-wp-utils'),
				'icon'        => 'dashicons-editor-quote',
				'category'    => 'content',
			],
			'block_gallery' => [
				'description' => __('Visual grid photo gallery. Perfect for showcase images, portfolios, or side-by-side snapshots.', 'tka-wp-utils'),
				'icon'        => 'dashicons-images-alt',
				'category'    => 'media',
			],
			'block_post_items' => [
				'description' => __('Dynamic recent posts grid. Automatically stream and display recent blog posts or custom post type listings.', 'tka-wp-utils'),
				'icon'        => 'dashicons-admin-post',
				'category'    => 'advanced',
			],
			'block_teaser' => [
				'description' => __('Engaging promotional teaser card. Feature a custom card layout with a title, image link, and CTA.', 'tka-wp-utils'),
				'icon'        => 'dashicons-megaphone',
				'category'    => 'marketing',
			],
			'block_teaser_copy' => [
				'description' => __('Advanced editorial promo block. Extended teaser variations with extra layout controls and content cards.', 'tka-wp-utils'),
				'icon'        => 'dashicons-art',
				'category'    => 'marketing',
			],
			'block_hero' => [
				'description' => __('Impactful hero header section. Prominent section banner with heading overlay, subtext, and background media.', 'tka-wp-utils'),
				'icon'        => 'dashicons-cover-image',
				'category'    => 'media',
			],
			'block_slider' => [
				'description' => __('Touch-interactive slide carousel. Showcase a sequence of slides, testimonials, or promotional images.', 'tka-wp-utils'),
				'icon'        => 'dashicons-slides',
				'category'    => 'media',
			],
			'block_location' => [
				'description' => __('Interactive map and contact details. Displays Google Maps embeds alongside local office address and hours.', 'tka-wp-utils'),
				'icon'        => 'dashicons-location-alt',
				'category'    => 'advanced',
			],
		];

		// Discover custom blocks from active theme template folders dynamically
		$discovered_metadata = $this->discoverThemeBlocks();
		$merged_metadata = array_merge($default_metadata, $discovered_metadata);
		$metadata = apply_filters('tka_acf_layout_modal_metadata', $merged_metadata);

		wp_localize_script('tka-acf-layout-modal-js', 'tkaAcfLayoutModalSettings', [
			'themeUrl' => get_stylesheet_directory_uri(),
			'metadata' => $metadata,
			'i18n' => [
				'selectLayout' => __('Select Block Layout', 'tka-wp-utils'),
				'searchPlaceholder' => __('Search layouts...', 'tka-wp-utils'),
				'noLayoutsFound' => __('No layouts matching your search.', 'tka-wp-utils'),
			],
		]);
	}

	/**
	 * Discover custom blocks inside the active theme and parse metadata from template headers.
	 */
	public function discoverThemeBlocks(): array
	{
		$theme_dir = get_stylesheet_directory();
		$default_dirs = [
			$theme_dir . '/resources/views/builder/blocks',
			$theme_dir . '/template-parts/blocks',
			$theme_dir . '/blocks',
		];

		$dirs = apply_filters('tka_acf_layout_modal_template_dirs', $default_dirs);
		$discovered = [];

		$headers = [
			'title'       => 'Title',
			'category'    => 'Category',
			'icon'        => 'Icon',
			'description' => 'Description',
		];

		foreach ($dirs as $dir) {
			if (!is_dir($dir)) {
				continue;
			}

			// Scan directory for php or blade.php files
			$files = glob($dir . '/*.php');
			if (empty($files)) {
				continue;
			}

			foreach ($files as $file_path) {
				$filename = basename($file_path);
				
				// Determine layout slug from filename
				// Matches block_gallery.blade.php or gallery.php
				$slug = str_replace(['.blade.php', '.php'], '', $filename);
				
				// Read headers natively via WordPress Core get_file_data()
				$data = get_file_data($file_path, $headers);
				
				if (!empty($data['description']) || !empty($data['icon']) || !empty($data['category'])) {
					$meta = [];
					if (!empty($data['description'])) {
						$meta['description'] = sanitize_text_field($data['description']);
					}
					if (!empty($data['icon'])) {
						$meta['icon'] = sanitize_text_field($data['icon']);
					}
					if (!empty($data['category'])) {
						$meta['category'] = sanitize_text_field($data['category']);
					}
					
					$discovered[$slug] = $meta;
					
					// Also register fallback variations to match slug structures (e.g. quote vs block_quote)
					if (strpos($slug, 'block_') === 0) {
						$alt_slug = substr($slug, 6);
						$discovered[$alt_slug] = $meta;
					} else {
						$alt_slug = 'block_' . $slug;
						$discovered[$alt_slug] = $meta;
					}
				}
			}
		}

		return $discovered;
	}

	/**
	 * Enqueue ACF copy and paste utility assets on post creation/editing pages.
	 */
	public function enqueueCopyPasteAssets(string $hook): void
	{
		if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
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
			['jquery'],
			TKA_WP_UTILS_VERSION,
			true
		);

		wp_localize_script('tka-acf-copy-paste-js', 'tkaAcfSettings', [
			'enableMultiselect' => !empty($this->options['acf_copy_paste']) ? 1 : 0,
			'i18n' => [
				'copy' => __('Copy', 'tka-wp-utils'),
				'copied' => __('Copied!', 'tka-wp-utils'),
				'paste' => __('Paste Block', 'tka-wp-utils'),
				'copySelected' => __('Copy Selected', 'tka-wp-utils'),
				'nothingCopied' => __('No copied block layout found in clipboard.', 'tka-wp-utils'),
				'confirmPaste' => __('Are you sure you want to paste the copied block(s)?', 'tka-wp-utils'),
				'layoutsCopied' => __('Selected layout blocks successfully copied to clipboard!', 'tka-wp-utils'),
			],
		]);
	}

	/**
	 * Filter visibility of the ACF Admin Menu item.
	 */
	public function controlAdminVisibility(bool $show): bool
	{
		if (!AdminInterface::isCurrentUserInstaller()) {
			return false;
		}
		return $show;
	}

	/**
	 * Disable the [acf] shortcode completely.
	 */
	public function disableAcfShortcode(): void
	{
		acf_update_setting('enable_shortcode', false);
	}

	/**
	 * Get theme-independent custom directory for saving local ACF JSONs.
	 */
	public function getCustomJsonSavePath(string $path): string
	{
		$custom_path = WP_CONTENT_DIR . '/acf-json';
		if (!file_exists($custom_path)) {
			wp_mkdir_p($custom_path);
		}
		return $custom_path;
	}

	/**
	 * Register theme-independent custom directory for loading local ACF JSONs.
	 */
	public function getCustomJsonLoadPaths(array $paths): array
	{
		$custom_path = WP_CONTENT_DIR . '/acf-json';
		if (file_exists($custom_path)) {
			$paths[] = $custom_path;
		}
		return $paths;
	}
}
