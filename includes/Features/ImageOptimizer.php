<?php

namespace TKA\WPUtils\Features;

/**
 * Handles automatic image optimization, WebP conversion, and compression quality overrides.
 * 
 * ARCHITECTURAL NOTICE:
 * This engine uses a "database-first static delivery approach" for WebP delivery.
 * 
 * 1. Why We Do It:
 *    Instead of relying on fragile server-side rewrites inside .htaccess (Apache) or nginx.conf (Nginx)
 *    which often break across different host migrations, this class converts physical uploads to .webp
 *    and writes the new mime type directly to the attachment records in the database. WordPress outputs
 *    the static WebP URL natively on page render, providing 100% server-agnostic delivery and zero rewrite overhead.
 * 
 * 2. Developer Consequences & Reversibility:
 *    - Image conversion is permanent in the database record. Deactivating this plugin will NOT revert
 *      existing attachments back to their original JPEGs/PNGs.
 *    - To preserve backup copies, verify that the "Keep Original Images" ($this->options['webp_keep_original'])
 *      toggle is active. Original uncompressed versions are kept safely on disk under their respective directories.
 */
class ImageOptimizer
{
	/**
	 * Saved plugin options.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Constructor.
	 *
	 * @param array $options Saved options.
	 */
	public function __construct(array $options)
	{
		$this->options = $options;
	}

	/**
	 * Register hooks if the feature is enabled.
	 */
	public function hook(): void
	{
		if (empty($this->options['image_optimization_enabled'])) {
			return;
		}

		// Hook image quality filter for generated thumbnails and sub-sizes
		add_filter('jpeg_quality', [$this, 'setImageQuality']);
		add_filter('wp_editor_set_quality', [$this, 'setImageEditorQuality'], 10, 2);

		// Hook upload pipeline to optimize original files and convert to WebP
		add_filter('wp_handle_upload', [$this, 'optimizeAndConvertUpload'], 10, 2);

		// Hook AJAX handlers for retroactive bulk optimization
		add_action('wp_ajax_tka_wp_utils_bulk_get_images', [$this, 'ajaxBulkGetImages']);
		add_action('wp_ajax_tka_wp_utils_bulk_optimize_image', [$this, 'ajaxBulkOptimizeImage']);
	}

	/**
	 * Filter JPEG quality for WordPress enqueued editors.
	 *
	 * @param int $quality Default quality.
	 * @return int Optimized quality.
	 */
	public function setImageQuality(int $quality): int
	{
		return isset($this->options['image_compression_quality'])
			? max(50, min(100, intval($this->options['image_compression_quality'])))
			: $quality;
	}

	/**
	 * Filter overall editor quality for WP Image Editor (supports WebP, PNG, JPEG).
	 *
	 * @param int    $quality   Default quality.
	 * @param string $mime_type MIME type.
	 * @return int Optimized quality.
	 */
	public function setImageEditorQuality(int $quality, string $mime_type): int
	{
		return $this->setImageQuality($quality);
	}

	/**
	 * Strips EXIF metadata from the given image file.
	 *
	 * @param string $file_path Absolute path to the file.
	 */
	public function stripMetadata(string $file_path): void
	{
		if (empty($this->options['strip_image_metadata']) || empty($file_path) || !file_exists($file_path)) {
			return;
		}

		if (class_exists('\Imagick')) {
			try {
				$imagick = new \Imagick($file_path);
				$imagick->stripImage();
				$imagick->writeImage($file_path);
				$imagick->clear();
				$imagick->destroy();
			} catch (\Throwable $e) {
				// Fail silently if there's an Imagick issue
			}
		}
		// Note: GD automatically strips EXIF profile metadata upon save in all versions.
	}

	/**
	 * Intercepts media uploads to convert JPEG/PNG to WebP and/or compress original uploads.
	 *
	 * @param array  $upload  Upload details array.
	 * @param string $context The upload context.
	 * @return array Modified upload details.
	 */
	public function optimizeAndConvertUpload(array $upload, string $context = 'upload'): array
	{
		// If upload failed, skip
		if (!empty($upload['error'])) {
			return $upload;
		}

		$file_path = $upload['file'] ?? '';
		$mime_type = $upload['type'] ?? '';

		if (empty($file_path) || !file_exists($file_path)) {
			return $upload;
		}

		// Only optimize and convert standard JPEG and PNG files
		$allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png'];
		if (!in_array($mime_type, $allowed_mimes, true)) {
			return $upload;
		}

		$quality = isset($this->options['image_compression_quality'])
			? max(50, min(100, intval($this->options['image_compression_quality'])))
			: 82;

		$webp_enabled = !empty($this->options['webp_conversion_enabled']);
		$compress_original = !empty($this->options['compress_original_images']);

		// Check if server supports WebP and WebP conversion is enabled
		$server_supports_webp = wp_image_editor_supports(['mime_type' => 'image/webp']);

		if ($webp_enabled && $server_supports_webp) {
			$editor = wp_get_image_editor($file_path);

			if (!is_wp_error($editor)) {
				$editor->set_quality($quality);

				$dir = pathinfo($file_path, PATHINFO_DIRNAME);
				$filename = pathinfo($file_path, PATHINFO_FILENAME);
				$webp_file_path = $dir . '/' . $filename . '.webp';

				// Ensure unique WebP filename
				if (file_exists($webp_file_path)) {
					$suffix = 1;
					while (file_exists($dir . '/' . $filename . '-' . $suffix . '.webp')) {
						$suffix++;
					}
					$webp_file_path = $dir . '/' . $filename . '-' . $suffix . '.webp';
				}

				// Save as WebP
				$saved = $editor->save($webp_file_path, 'image/webp');

				if (!is_wp_error($saved)) {
					$original_file_path = $file_path;

					// Update upload meta
					$upload['file'] = $webp_file_path;
					$upload['type'] = 'image/webp';
					$upload['url']  = dirname($upload['url']) . '/' . basename($webp_file_path);

					// Strip metadata from the newly created WebP file
					$this->stripMetadata($webp_file_path);

					$keep_original = !empty($this->options['webp_keep_original']);

					if ($keep_original) {
						if ($compress_original) {
							$original_editor = wp_get_image_editor($original_file_path);
							if (!is_wp_error($original_editor)) {
								$original_editor->set_quality($quality);
								$original_editor->save($original_file_path);
								$this->stripMetadata($original_file_path);
							}
						} else {
							// Strip metadata even if original is not compressed
							$this->stripMetadata($original_file_path);
						}
					} else {
						@unlink($original_file_path);
					}
				}
			}
		} elseif ($compress_original) {
			// WebP is disabled/unsupported, but we want to compress the original JPEGs/PNGs in-place
			$editor = wp_get_image_editor($file_path);
			if (!is_wp_error($editor)) {
				$editor->set_quality($quality);
				$editor->save($file_path);
				$this->stripMetadata($file_path);
			}
		} else {
			// No compression or WebP, but strip EXIF if requested
			$this->stripMetadata($file_path);
		}

		return $upload;
	}

	/**
	 * AJAX endpoint to retrieve all eligible attachments for retroactive optimization.
	 */
	public function ajaxBulkGetImages(): void
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Insufficient permissions.', 'tka-wp-utils')]);
		}

		$query = new \WP_Query([
			'post_type'        => 'attachment',
			'post_mime_type'   => ['image/jpeg', 'image/png'],
			'post_status'      => 'inherit',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		]);

		wp_send_json_success(['ids' => $query->posts]);
	}

	/**
	 * AJAX endpoint to process, convert, and optimize a single existing attachment.
	 */
	public function ajaxBulkOptimizeImage(): void
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('Insufficient permissions.', 'tka-wp-utils')]);
		}

		$attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
		if (!$attachment_id) {
			wp_send_json_error(['message' => __('Invalid attachment ID.', 'tka-wp-utils')]);
		}

		$file_path = get_attached_file($attachment_id);
		$mime_type = get_post_mime_type($attachment_id);

		if (empty($file_path) || !file_exists($file_path)) {
			wp_send_json_error(['message' => sprintf(__('File not found: %s', 'tka-wp-utils'), basename($file_path))]);
		}

		// Calculate old total storage footprint (main + thumbnails)
		$old_size = filesize($file_path);
		$old_metadata = wp_get_attachment_metadata($attachment_id);
		$path_dir = dirname($file_path);

		if (!empty($old_metadata['sizes'])) {
			foreach ($old_metadata['sizes'] as $size) {
				$thumb_path = $path_dir . '/' . $size['file'];
				if (file_exists($thumb_path)) {
					$old_size += filesize($thumb_path);
					// Delete old JPEG/PNG thumbnail sizes so they are overwritten cleanly
					@unlink($thumb_path);
				}
			}
		}

		// Prepare mock upload details for pipeline
		$url = wp_get_attachment_url($attachment_id);
		$upload = [
			'file' => $file_path,
			'url'  => $url,
			'type' => $mime_type,
		];

		// Run through existing optimization hook
		$optimized = $this->optimizeAndConvertUpload($upload);

		// If conversion to WebP occurred, update attachment parameters in DB
		if ($optimized['type'] === 'image/webp') {
			global $wpdb;
			$wpdb->update(
				$wpdb->posts,
				[
					'post_mime_type' => 'image/webp',
					'guid'           => $optimized['url'],
				],
				['ID' => $attachment_id]
			);

			// Compute relative path from WordPress upload base dir
			$relative_path = ltrim(str_replace(wp_get_upload_dir()['basedir'], '', $optimized['file']), '/');
			update_post_meta($attachment_id, '_wp_attached_file', $relative_path);
		}

		// Regenerate compressed and optimized sub-sizes (thumbnails, mediums, etc.)
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$new_metadata = wp_generate_attachment_metadata($attachment_id, $optimized['file']);
		wp_update_attachment_metadata($attachment_id, $new_metadata);

		// Calculate new storage footprint
		$new_size = filesize($optimized['file']);
		if (!empty($new_metadata['sizes'])) {
			foreach ($new_metadata['sizes'] as $size) {
				$thumb_path = $path_dir . '/' . $size['file'];
				if (file_exists($thumb_path)) {
					$new_size += filesize($thumb_path);
				}
			}
		}

		$bytes_saved = max(0, $old_size - $new_size);

		// Save the exact storage savings in attachment post meta
		update_post_meta($attachment_id, '_tka_image_savings', $bytes_saved);

		wp_send_json_success([
			'filename'    => basename($optimized['file']),
			'bytes_saved' => $bytes_saved,
			'mime_type'   => $optimized['type'],
			'message'     => sprintf(__('Successfully optimized %s.', 'tka-wp-utils'), basename($optimized['file'])),
		]);
	}
}

