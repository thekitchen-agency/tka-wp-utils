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

		if (!empty($this->options['acf_video_poster'])) {
			add_action('acf/init', [$this, 'registerVideoPosterFieldGroup']);
		}

		if (!empty($this->options['acf_allow_videos'])) {
			$this->enableVideoSupport();
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

		// Load enabled ACF Extensions
		if (!empty($this->options['acf_extensions']) && is_array($this->options['acf_extensions'])) {
			$available_extensions = self::getAvailableExtensions();
			foreach ($this->options['acf_extensions'] as $filename) {
				if (isset($available_extensions[$filename])) {
					require_once $available_extensions[$filename]['path'];
				}
			}
		}
	}

	/**
	 * Scan the AcfExtensions directory and return available extensions with their metadata.
	 *
	 * @return array
	 */
	public static function getAvailableExtensions(): array
	{
		$extensions_dir = TKA_SITE_UTILITIES_PATH . 'includes/AcfExtensions';
		if (!is_dir($extensions_dir)) {
			return [];
		}

		$files = glob($extensions_dir . '/*.php');
		if (empty($files)) {
			return [];
		}

		$extensions = [];
		$headers = [
			'name'        => 'Extension Name',
			'description' => 'Description',
		];

		foreach ($files as $file_path) {
			$filename = basename($file_path);
			$data = get_file_data($file_path, $headers);

			if (!empty($data['name'])) {
				$extensions[$filename] = [
					'name'        => sanitize_text_field($data['name']),
					'description' => sanitize_text_field($data['description']),
					'path'        => $file_path,
				];
			}
		}

		return $extensions;
	}

	/**
	 * Enqueue ACF visual layout selection modal assets on post creation/editing pages.
	 */
	public function enqueueLayoutModalAssets(string $hook): void
	{
		if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
			return;
		}

		$css_path = TKA_SITE_UTILITIES_PATH . 'admin/css/acf-layout-modal.css';
		$js_path = TKA_SITE_UTILITIES_PATH . 'admin/js/acf-layout-modal.js';

		$css_version = file_exists($css_path) ? filemtime($css_path) : TKA_SITE_UTILITIES_VERSION;
		$js_version = file_exists($js_path) ? filemtime($js_path) : TKA_SITE_UTILITIES_VERSION;

		wp_enqueue_style(
			'tka-acf-layout-modal-css',
			TKA_SITE_UTILITIES_URL . 'admin/css/acf-layout-modal.css',
			[],
			$css_version
		);

		wp_enqueue_script(
			'tka-acf-layout-modal-js',
			TKA_SITE_UTILITIES_URL . 'admin/js/acf-layout-modal.js',
			['jquery'],
			$js_version,
			true
		);

		$default_metadata = [
			'block_text' => [
				'description' => __('Standard rich text block. Ideal for paragraphs, sub-headings, lists, and formatted textual copy.', 'tka-site-utilities'),
				'icon'        => 'dashicons-editor-paragraph',
				'category'    => 'content',
			],
			'block_quote' => [
				'description' => __('Sleek blockquote styling. Best for drawing attention to testimonials, highlighted statements, or citations.', 'tka-site-utilities'),
				'icon'        => 'dashicons-editor-quote',
				'category'    => 'content',
			],
			'block_gallery' => [
				'description' => __('Visual grid photo gallery. Perfect for showcase images, portfolios, or side-by-side snapshots.', 'tka-site-utilities'),
				'icon'        => 'dashicons-images-alt',
				'category'    => 'media',
			],
			'block_post_items' => [
				'description' => __('Dynamic recent posts grid. Automatically stream and display recent blog posts or custom post type listings.', 'tka-site-utilities'),
				'icon'        => 'dashicons-admin-post',
				'category'    => 'advanced',
			],
			'block_teaser' => [
				'description' => __('Engaging promotional teaser card. Feature a custom card layout with a title, image link, and CTA.', 'tka-site-utilities'),
				'icon'        => 'dashicons-megaphone',
				'category'    => 'marketing',
			],
			'block_teaser_copy' => [
				'description' => __('Advanced editorial promo block. Extended teaser variations with extra layout controls and content cards.', 'tka-site-utilities'),
				'icon'        => 'dashicons-art',
				'category'    => 'marketing',
			],
			'block_hero' => [
				'description' => __('Impactful hero header section. Prominent section banner with heading overlay, subtext, and background media.', 'tka-site-utilities'),
				'icon'        => 'dashicons-cover-image',
				'category'    => 'media',
			],
			'block_slider' => [
				'description' => __('Touch-interactive slide carousel. Showcase a sequence of slides, testimonials, or promotional images.', 'tka-site-utilities'),
				'icon'        => 'dashicons-slides',
				'category'    => 'media',
			],
			'block_location' => [
				'description' => __('Interactive map and contact details. Displays Google Maps embeds alongside local office address and hours.', 'tka-site-utilities'),
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
				'selectLayout' => __('Select Block Layout', 'tka-site-utilities'),
				'searchPlaceholder' => __('Search layouts...', 'tka-site-utilities'),
				'noLayoutsFound' => __('No layouts matching your search.', 'tka-site-utilities'),
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
			TKA_SITE_UTILITIES_URL . 'admin/css/acf-copy-paste.css',
			[],
			TKA_SITE_UTILITIES_VERSION
		);

		wp_enqueue_script(
			'tka-acf-copy-paste-js',
			TKA_SITE_UTILITIES_URL . 'admin/js/acf-copy-paste.js',
			['jquery'],
			TKA_SITE_UTILITIES_VERSION,
			true
		);

		wp_localize_script('tka-acf-copy-paste-js', 'tkaAcfSettings', [
			'enableMultiselect' => !empty($this->options['acf_copy_paste']) ? 1 : 0,
			'i18n' => [
				'copy' => __('Copy', 'tka-site-utilities'),
				'copied' => __('Copied!', 'tka-site-utilities'),
				'paste' => __('Paste Block', 'tka-site-utilities'),
				'copySelected' => __('Copy Selected', 'tka-site-utilities'),
				'nothingCopied' => __('No copied block layout found in clipboard.', 'tka-site-utilities'),
				'confirmPaste' => __('Are you sure you want to paste the copied block(s)?', 'tka-site-utilities'),
				'layoutsCopied' => __('Selected layout blocks successfully copied to clipboard!', 'tka-site-utilities'),
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

		$css_path = TKA_SITE_UTILITIES_PATH . 'admin/css/acf-layout-toggle.css';
		$js_path = TKA_SITE_UTILITIES_PATH . 'admin/js/acf-layout-toggle.js';

		$css_version = file_exists($css_path) ? filemtime($css_path) : TKA_SITE_UTILITIES_VERSION;
		$js_version = file_exists($js_path) ? filemtime($js_path) : TKA_SITE_UTILITIES_VERSION;

		wp_enqueue_style(
			'tka-acf-layout-toggle-css',
			TKA_SITE_UTILITIES_URL . 'admin/css/acf-layout-toggle.css',
			[],
			$css_version
		);

		wp_enqueue_script(
			'tka-acf-layout-toggle-js',
			TKA_SITE_UTILITIES_URL . 'admin/js/acf-layout-toggle.js',
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
	 * Register a Video Poster field group for video attachments.
	 */
	public function registerVideoPosterFieldGroup(): void
	{
		if (function_exists('acf_add_local_field_group')) {
			acf_add_local_field_group(array(
				'key' => 'group_tka_video_poster',
				'title' => __('Video Poster', 'tka-site-utilities'),
				'fields' => array(
					array(
						'key' => 'field_tka_video_poster_image',
						'label' => __('Poster Image', 'tka-site-utilities'),
						'name' => 'video_poster_image',
						'type' => 'image',
						'instructions' => __('Upload a fallback poster image for this video.', 'tka-site-utilities'),
						'required' => 0,
						'conditional_logic' => 0,
						'return_format' => 'id',
						'preview_size' => 'medium',
						'library' => 'all',
						'mime_types' => 'jpg,jpeg,png,webp',
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'attachment',
							'operator' => '==',
							'value' => 'video',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
				'show_in_rest' => 1,
			));
		}
	}

	/**
	 * Enable MP4/WebM/MOV video uploads and selection in ACF Gallery and Image fields.
	 */
	public function enableVideoSupport(): void
	{
		add_filter('upload_mimes', [$this, 'filterUploadMimes']);
		add_filter('wp_check_filetype_and_ext', [$this, 'filterCheckFiletypeAndExt'], 10, 4);
		add_filter('ajax_query_attachments_args', [$this, 'filterAjaxQueryAttachmentsArgs']);
		add_filter('acf/load_field/type=gallery', [$this, 'filterLoadGalleryField']);

		add_action('acf/init', [$this, 'removeGalleryImageValidation'], 20);
		add_action('init', [$this, 'removeGalleryImageValidation'], 20);

		add_filter('acf/validate_is_image_attachment', [$this, 'filterValidateIsImageAttachment'], 10, 5);
		add_filter('acf/validate_attachment', [$this, 'filterValidateAttachment'], 20, 5);
		add_filter('acf/upload_prefilter', [$this, 'filterUploadPrefilter'], 99, 3);
		add_filter('wp_prepare_attachment_for_js', [$this, 'filterPrepareAttachmentForJs'], 99, 3);
		add_filter('wp_handle_upload_prefilter', [$this, 'filterHandleUploadPrefilter'], 99);

		add_filter('acf/validate_value', [$this, 'filterValidateValue'], 99, 4);
		add_filter('acf/validate_rest_value/type=gallery', [$this, 'filterValidateRestValue'], 99, 3);
		add_filter('acf/validate_rest_value/type=image', [$this, 'filterValidateRestValue'], 99, 3);
	}

	public function filterUploadMimes(array $mimes): array
	{
		$mimes['webm'] = 'video/webm';
		$mimes['mp4']  = 'video/mp4';
		$mimes['mov']  = 'video/quicktime';
		return $mimes;
	}

	public function filterCheckFiletypeAndExt(array $data, $file, string $filename, $mimes): array
	{
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if ('webm' === $ext) {
			$data['ext']  = 'webm';
			$data['type'] = 'video/webm';
		} elseif ('mp4' === $ext) {
			$data['ext']  = 'mp4';
			$data['type'] = 'video/mp4';
		} elseif ('mov' === $ext) {
			$data['ext']  = 'mov';
			$data['type'] = 'video/quicktime';
		}
		return $data;
	}

	public function filterAjaxQueryAttachmentsArgs(array $query): array
	{
		if (isset($_POST['query']['_acfuploader']) || isset($_REQUEST['_acfuploader'])) {
			$field_key = $_POST['query']['_acfuploader'] ?? $_REQUEST['_acfuploader'];
			$field     = acf_get_field($field_key);

			if ($field) {
				if (in_array($field['type'], ['gallery', 'file', 'image'], true)) {
					$query['post_mime_type'] = ['image', 'video'];
				}
			} else {
				$query['post_mime_type'] = ['image', 'video'];
			}
		}
		return $query;
	}

	public function filterLoadGalleryField(array $field): array
	{
		$field['mime_types'] = '';
		return $field;
	}

	public function removeGalleryImageValidation(): void
	{
		remove_filter('acf/validate_attachment/type=gallery', 'acf_validate_is_image_attachment', 10);
	}

	public function filterValidateIsImageAttachment(array $errors, $file, $attachment, $field, $context): array
	{
		if (isset($field['type']) && $field['type'] === 'gallery') {
			unset($errors['invalid_image']);
			return $errors;
		}

		$mime     = $attachment['mime'] ?? $attachment['type'] ?? '';
		$filename = $file['name'] ?? $attachment['filename'] ?? $attachment['url'] ?? '';
		$ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'mov', 'ogg', 'ogv'], true)) {
			unset($errors['invalid_image']);
		}

		return $errors;
	}

	public function filterValidateAttachment(array $errors, $file, $attachment, $field, $context): array
	{
		if (isset($field['type']) && $field['type'] === 'gallery') {
			unset($errors['invalid_image']);
		}

		$mime     = $attachment['mime'] ?? $attachment['type'] ?? '';
		$filename = $file['name'] ?? $attachment['filename'] ?? $attachment['url'] ?? '';
		$ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'webm', 'mov', 'ogg', 'ogv'], true)) {
			unset($errors['invalid_image']);
			unset($errors['mime_types']);
		}

		return $errors;
	}

	public function filterUploadPrefilter(array $errors, array $file, array $field): array
	{
		$ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
		if (in_array($ext, ['webm', 'mp4', 'mov', 'ogg', 'ogv'], true)) {
			unset($errors['invalid_image']);
			unset($errors['mime_types']);
		}
		return $errors;
	}

	public function filterPrepareAttachmentForJs(array $response, $attachment, $meta): array
	{
		$mime     = $response['mime'] ?? $response['type'] ?? ($attachment->post_mime_type ?? '');
		$filename = $response['filename'] ?? (isset($response['url']) ? basename($response['url']) : '');
		$ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		if (str_starts_with($mime, 'video/') || in_array($ext, ['webm', 'mp4', 'mov', 'ogg', 'ogv'], true)) {
			$response['acf_errors'] = false;
		}
		return $response;
	}

	public function filterHandleUploadPrefilter(array $file): array
	{
		$ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
		if (in_array($ext, ['webm', 'mp4', 'mov', 'ogg', 'ogv'], true) && isset($file['error'])) {
			if (str_contains(strtolower($file['error']), 'image') || str_contains(strtolower($file['error']), 'type')) {
				unset($file['error']);
			}
		}
		return $file;
	}

	public function filterValidateValue($valid, $value, array $field, string $input)
	{
		if ($valid !== true && !empty($value)) {
			$attachment_ids = is_array($value) ? $value : [$value];
			$all_valid = true;

			foreach ($attachment_ids as $id) {
				if (is_numeric($id)) {
					$is_image = wp_attachment_is_image((int) $id);
					$mime     = (string) get_post_mime_type((int) $id);
					$is_video = str_starts_with($mime, 'video/');

					if (!$is_image && !$is_video) {
						$all_valid = false;
						break;
					}
				} else {
					$all_valid = false;
					break;
				}
			}

			if ($all_valid && !empty($attachment_ids)) {
				return true;
			}
		}
		return $valid;
	}

	public function filterValidateRestValue($valid, $value, array $field)
	{
		if ($valid !== true && !empty($value)) {
			$attachment_ids = is_array($value) ? $value : [$value];
			$all_valid = true;

			foreach ($attachment_ids as $id) {
				if (is_numeric($id)) {
					$is_image = wp_attachment_is_image((int) $id);
					$mime     = (string) get_post_mime_type((int) $id);
					$is_video = str_starts_with($mime, 'video/');

					if (!$is_image && !$is_video) {
						$all_valid = false;
						break;
					}
				} else {
					$all_valid = false;
					break;
				}
			}

			if ($all_valid && !empty($attachment_ids)) {
				return true;
			}
		}
		return $valid;
	}
}
