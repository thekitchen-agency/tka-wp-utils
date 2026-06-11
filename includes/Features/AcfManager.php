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
		$acfe_active = class_exists('ACFE') || defined('ACFE') || function_exists('acfe');

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

		if (!$acfe_active) {
			if (!empty($this->options['acf_copy_paste'])) {
				add_action('admin_enqueue_scripts', [$this, 'enqueueCopyPasteAssets']);
			}

			if (!empty($this->options['acf_layout_modal'])) {
				add_action('admin_enqueue_scripts', [$this, 'enqueueLayoutModalAssets']);
			}

			if (!empty($this->options['acf_layout_toggle']) || !empty($this->options['acf_layout_rename'])) {
				add_action('admin_enqueue_scripts', [$this, 'enqueueLayoutToggleAssets']);
			}

			if (!empty($this->options['acf_layout_toggle'])) {
				add_action('acf/render_field', [$this, 'renderLayoutDisabledSetting'], 10, 1);
				add_filter('acf/prepare_field/type=flexible_content', [$this, 'prepareFlexibleContentFieldForEditor'], 10, 1);
				add_filter('acf/format_value/type=flexible_content', [$this, 'filterFormattedFlexibleContentValue'], 10, 3);
			}
		}

		// Fallback for custom Gravity Forms field type "forms" if enabled and sayhellogmbh plugin is deactivated/missing
		if (!empty($this->options['acf_gravity_forms_fallback']) && !class_exists('ACFGravityformsField\Field')) {
			add_filter('acf/load_field/type=forms', [$this, 'loadGravityFormsField']);
			add_filter('acf/format_value', [$this, 'formatGravityFormsValue'], 10, 3);
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

	/**
	 * Enqueue ACF layout enable/disable visibility toggle assets on post and field group edit pages.
	 */
	public function enqueueLayoutToggleAssets(string $hook): void
	{
		if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
			return;
		}

		$css_path = TKA_WP_UTILS_PATH . 'admin/css/acf-layout-toggle.css';
		$js_path = TKA_WP_UTILS_PATH . 'admin/js/acf-layout-toggle.js';

		$css_version = file_exists($css_path) ? filemtime($css_path) : TKA_WP_UTILS_VERSION;
		$js_version = file_exists($js_path) ? filemtime($js_path) : TKA_WP_UTILS_VERSION;

		wp_enqueue_style(
			'tka-acf-layout-toggle-css',
			TKA_WP_UTILS_URL . 'admin/css/acf-layout-toggle.css',
			[],
			$css_version
		);

		wp_enqueue_script(
			'tka-acf-layout-toggle-js',
			TKA_WP_UTILS_URL . 'admin/js/acf-layout-toggle.js',
			['jquery'],
			$js_version,
			true
		);

		wp_localize_script('tka-acf-layout-toggle-js', 'tkaAcfLayoutToggleSettings', [
			'enableToggle' => !empty($this->options['acf_layout_toggle']) ? 1 : 0,
			'enableRename' => !empty($this->options['acf_layout_rename']) ? 1 : 0,
		]);
	}

	/**
	 * Hook into field settings rendering to output the disabled state input.
	 */
	public function renderLayoutDisabledSetting(array $field): void
	{
		// In acf/render_field, the field name has been prepared and looks like:
		// acf_fields[39][layouts][layout_6a161244d8df5][label]
		if (empty($field['name'])) {
			return;
		}

		if (preg_match('/^acf_fields\[(?P<parent_field_key>field_[a-zA-Z0-9_]+|[0-9]+)\]\[layouts\]\[(?P<layout_key>layout_[a-zA-Z0-9_]+)\]\[label\]$/', $field['name'], $matches)) {
			$parent_field_key = $matches['parent_field_key'];
			$layout_key = $matches['layout_key'];

			// Load the parent field to get the layout's saved disabled state
			$parent_field = acf_get_field($parent_field_key);
			$disabled = 0;

			if ($parent_field && !empty($parent_field['layouts'])) {
				foreach ($parent_field['layouts'] as $layout) {
					if ($layout['key'] === $layout_key) {
						$disabled = !empty($layout['disabled']) ? 1 : 0;
						break;
					}
				}
			}

			// Render the hidden input for the layout's disabled state
			$input_name = preg_replace('/\[label\]$/', '[disabled]', $field['name']);
			echo '<input type="hidden" class="tka-layout-disabled-input" name="' . esc_attr($input_name) . '" value="' . esc_attr($disabled) . '">';
		}
	}

	/**
	 * Hook into prepare flexible content field for editor to inject disabled layout names.
	 */
	public function prepareFlexibleContentFieldForEditor(array $field): array
	{
		$disabled_layouts = [];
		if (!empty($field['layouts'])) {
			foreach ($field['layouts'] as $layout) {
				if (!empty($layout['disabled'])) {
					$disabled_layouts[] = $layout['name'];
				}
			}
		}

		if (!empty($disabled_layouts)) {
			// Add a custom data attribute to the field wrapper
			$field['wrapper']['data-tka-disabled-layouts'] = implode(',', $disabled_layouts);
		}

		return $field;
	}

	/**
	 * Filter the formatted flexible content value on the front-end to exclude disabled rows.
	 */
	public function filterFormattedFlexibleContentValue($value, $post_id, $field)
	{
		// Only filter on the front-end (exclude admin view)
		if (is_admin()) {
			return $value;
		}

		if (!is_array($value) || empty($value)) {
			return $value;
		}

		// Map layouts to their disabled status
		$disabled_layouts = [];
		if (!empty($field['layouts'])) {
			foreach ($field['layouts'] as $layout) {
				if (!empty($layout['disabled'])) {
					$disabled_layouts[$layout['name']] = true;
				}
			}
		}

		$filtered_value = [];
		foreach ($value as $row) {
			$layout_name = $row['acf_fc_layout'] ?? '';
			// If layout is not disabled globally, keep it
			if (empty($disabled_layouts[$layout_name])) {
				$filtered_value[] = $row;
			}
		}

		return $filtered_value;
	}

	/**
	 * Dynamically convert "forms" field type to "select" and populate with Gravity Forms choices.
	 *
	 * @param array $field The ACF field settings.
	 * @return array The modified field settings.
	 */
	public function loadGravityFormsField(array $field): array
	{
		$field['type'] = 'select';
		$field['original_type'] = 'forms';
		$field['ui'] = 1; // Enable Select2 search UI
		$field['choices'] = [];

		if (class_exists('GFAPI')) {
			$forms = \GFAPI::get_forms(true, false, 'title');
			if (!empty($forms)) {
				foreach ($forms as $form) {
					$field['choices'][$form['id']] = $form['title'];
				}
			}
		}

		return $field;
	}

	/**
	 * Format gravity forms values based on return format configuration.
	 *
	 * @param mixed $value The field value in DB.
	 * @param mixed $post_id The post ID.
	 * @param array $field The field array settings.
	 * @return mixed Formatted value.
	 */
	public function formatGravityFormsValue($value, $post_id, array $field)
	{
		if (isset($field['original_type']) && $field['original_type'] === 'forms') {
			if (empty($value)) {
				return $value;
			}

			$return_format = $field['return_format'] ?? 'id';
			if ($return_format === 'id') {
				return is_array($value) ? array_map('intval', $value) : (int) $value;
			}

			if (class_exists('GFAPI')) {
				if (is_array($value)) {
					$formatted = [];
					foreach ($value as $val) {
						$form = \GFAPI::get_form($val);
						if ($form && !is_wp_error($form)) {
							$formatted[] = $form;
						}
					}
					return $formatted;
				} else {
					$form = \GFAPI::get_form($value);
					return ($form && !is_wp_error($form)) ? $form : false;
				}
			}
		}

		return $value;
	}
}
