<?php

namespace TKA\WPUtils\Features;

/**
 * Handles seamless media replacement in the WordPress Media Library.
 */
class ReplaceMedia
{
	/**
	 * Register hooks.
	 */
	public function hook(): void
	{
		add_filter('attachment_fields_to_edit', [$this, 'addReplaceMediaField'], 10, 2);
		add_action('wp_ajax_tka_replace_media', [$this, 'ajaxReplaceMedia']);
		add_action('admin_footer', [$this, 'outputScriptAndStyle']);
		add_filter('wp_prepare_attachment_for_js', [$this, 'addCacheBusterToMediaGrid'], 10, 2);
		add_filter('wp_get_attachment_image_src', [$this, 'addCacheBusterToImageSrc'], 10, 4);
	}

	/**
	 * Appends a cache-busting timestamp to image URLs in the Media Library Grid View.
	 */
	public function addCacheBusterToMediaGrid(array $response, \WP_Post $attachment): array
	{
		$timestamp = get_post_modified_time('U', false, $attachment);

		if (isset($response['url'])) {
			$response['url'] = add_query_arg('t', $timestamp, $response['url']);
		}

		if (isset($response['sizes']) && is_array($response['sizes'])) {
			foreach ($response['sizes'] as $size => $data) {
				if (isset($response['sizes'][$size]['url'])) {
					$response['sizes'][$size]['url'] = add_query_arg('t', $timestamp, $response['sizes'][$size]['url']);
				}
			}
		}

		return $response;
	}

	/**
	 * Appends a cache-busting timestamp to image URLs in the Media Library List View (and globally).
	 */
	public function addCacheBusterToImageSrc($image, $attachment_id, $size, $icon)
	{
		if ($image && is_array($image) && !empty($image[0])) {
			$timestamp = get_post_modified_time('U', false, $attachment_id);
			$image[0] = add_query_arg('t', $timestamp, $image[0]);
		}
		return $image;
	}

	/**
	 * Outputs the necessary inline CSS and JS for the Replace Media UI.
	 */
	public function outputScriptAndStyle(): void
	{
		// Only load on screens that might have the media modal
		$screen = get_current_screen();
		if (!$screen || !in_array($screen->id, ['upload', 'post', 'page'], true)) {
			// Actually, media modal can be loaded anywhere. Let's just check if media is enqueued.
			// wp_enqueue_media() leaves a trace, but it's safer to just output this tiny script everywhere.
		}

		?>
		<style>
		/* Replace Media Styles */
		.tka-replace-media-wrapper { background: #f8fafc; padding: 15px; border: 1px dashed #cbd5e1; border-radius: 6px; margin-top: 10px; }
		.tka-replace-media-btn { margin-top: 8px !important; }
		.tka-replace-status { font-size: 13px; }
		.tka-replace-status.success { color: #10b981; }
		.tka-replace-status.error { color: #ef4444; }
		</style>
		<script>
		jQuery(document).ready(function($) {
			$(document).on('click', '.tka-replace-media-btn', function(e) {
				e.preventDefault();
				const btn = $(this);
				const attachmentId = btn.data('id');
				const nonce = btn.data('nonce');
				const fileInput = document.getElementById('tka-replace-media-file-' + attachmentId);
				const statusDiv = document.getElementById('tka-replace-status-' + attachmentId);

				if (!fileInput || fileInput.files.length === 0) {
					statusDiv.className = 'tka-replace-status error';
					statusDiv.textContent = 'Please select a file to upload first.';
					return;
				}

				const file = fileInput.files[0];
				
				btn.prop('disabled', true).text('Uploading...');
				statusDiv.className = 'tka-replace-status';
				statusDiv.textContent = 'Uploading and replacing file...';

				const formData = new FormData();
				formData.append('action', 'tka_replace_media');
				formData.append('attachment_id', attachmentId);
				formData.append('nonce', nonce);
				formData.append('replace_file', file);

				fetch(ajaxurl, { // Built-in WP variable
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(res => {
					if (res.success) {
						statusDiv.className = 'tka-replace-status success';
						statusDiv.textContent = res.data.message;
						
						const timestamp = res.data.timestamp;
						const updateDOM = () => {
							$('.attachment-details .details-image').attr('src', function(i, src) {
								if (src) return src.split('?')[0] + '?t=' + timestamp;
							});
							$('.attachment[data-id="' + attachmentId + '"] img').attr('src', function(i, src) {
								if (src) return src.split('?')[0] + '?t=' + timestamp;
							});
						};

						// Tell WordPress Backbone to fetch the updated attachment data
						if (typeof wp !== 'undefined' && wp.media && wp.media.model && wp.media.model.Attachment) {
							const wpAttachment = wp.media.model.Attachment.get(attachmentId);
							if (wpAttachment) {
								wpAttachment.fetch().done(function() {
									// Apply cache buster again after Backbone re-renders
									setTimeout(updateDOM, 100);
								});
							}
						}
						
						// Also update immediately
						updateDOM();
						
						fileInput.value = '';
					} else {
						statusDiv.className = 'tka-replace-status error';
						statusDiv.textContent = res.data.message || 'An error occurred.';
					}
				})
				.catch(err => {
					statusDiv.className = 'tka-replace-status error';
					statusDiv.textContent = 'Network error during upload.';
				})
				.finally(() => {
					btn.prop('disabled', false).text('Upload & Replace');
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Adds the "Replace File" UI to the media attachment modal.
	 *
	 * @param array    $form_fields Form fields.
	 * @param \WP_Post $post        Attachment post object.
	 * @return array
	 */
	public function addReplaceMediaField(array $form_fields, \WP_Post $post): array
	{
		$mime = get_post_mime_type($post->ID);
		$ext = pathinfo(get_attached_file($post->ID), PATHINFO_EXTENSION);
		$accept = '.' . strtolower($ext);

		// If it's a webp, we also accept jpg/png due to our WebP Exception
		if ($ext === 'webp') {
			$accept .= ', .jpg, .jpeg, .png';
		}

		$html = '
		<div class="tka-replace-media-wrapper">
			<input type="file" id="tka-replace-media-file-' . esc_attr($post->ID) . '" accept="' . esc_attr($accept) . '" style="margin-bottom: 8px; width: 100%;" />
			<button type="button" class="button button-secondary tka-replace-media-btn" data-id="' . esc_attr($post->ID) . '" data-nonce="' . esc_attr(wp_create_nonce('tka_replace_media_' . $post->ID)) . '">
				' . esc_html__('Upload & Replace', 'tka-site-utilities') . '
			</button>
			<div id="tka-replace-status-' . esc_attr($post->ID) . '" class="tka-replace-status" style="margin-top: 8px; font-weight: 500;"></div>
		</div>';

		$form_fields['tka_replace_media'] = [
			'label' => __('Replace File', 'tka-site-utilities'),
			'input' => 'html',
			'html'  => $html,
		];

		return $form_fields;
	}

	/**
	 * Handles the AJAX request to replace the media file.
	 */
	public function ajaxReplaceMedia(): void
	{
		$attachment_id = isset($_POST['attachment_id']) ? intval(wp_unslash($_POST['attachment_id'])) : 0;
		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

		if (!$attachment_id || !wp_verify_nonce($nonce, 'tka_replace_media_' . $attachment_id)) {
			wp_send_json_error(['message' => __('Invalid security token.', 'tka-site-utilities')]);
		}

		if (!current_user_can('edit_post', $attachment_id)) {
			wp_send_json_error(['message' => __('Insufficient permissions to edit this media.', 'tka-site-utilities')]);
		}

		if (empty($_FILES['replace_file']) || !isset($_FILES['replace_file']['error']) || $_FILES['replace_file']['error'] !== UPLOAD_ERR_OK) {
			wp_send_json_error(['message' => __('File upload failed.', 'tka-site-utilities')]);
		}

		$uploaded_file = isset($_FILES['replace_file']['tmp_name']) ? sanitize_text_field(wp_unslash($_FILES['replace_file']['tmp_name'])) : '';
		$new_filename = isset($_FILES['replace_file']['name']) ? sanitize_file_name(wp_unslash($_FILES['replace_file']['name'])) : '';
		$new_ext = strtolower(pathinfo($new_filename, PATHINFO_EXTENSION));

		$original_file_path = get_attached_file($attachment_id);
		if (empty($original_file_path) || !file_exists($original_file_path)) {
			wp_send_json_error(['message' => __('Original file not found on server.', 'tka-site-utilities')]);
		}

		$original_ext = strtolower(pathinfo($original_file_path, PATHINFO_EXTENSION));

		// Validation and WebP Exception Logic
		$is_webp_exception = false;
		if ($original_ext === 'webp' && in_array($new_ext, ['jpg', 'jpeg', 'png'], true)) {
			// They are uploading a jpg/png to replace a webp
			$is_webp_exception = true;
		} elseif ($original_ext !== $new_ext) {
			/* translators: %s: Expected file extension */
			wp_send_json_error(['message' => sprintf(__('File extension mismatch. You must upload a .%s file.', 'tka-site-utilities'), $original_ext)]);
		}

		// Delete old thumbnails to ensure they are cleanly regenerated later
		$old_metadata = wp_get_attachment_metadata($attachment_id);
		if (!empty($old_metadata['sizes'])) {
			$path_dir = dirname($original_file_path);
			foreach ($old_metadata['sizes'] as $size) {
				$thumb_path = $path_dir . '/' . $size['file'];
				if (file_exists($thumb_path)) {
					wp_delete_file($thumb_path);
				}
			}
		}

		if ($is_webp_exception) {
			// Use WordPress Image Editor to convert the uploaded jpg/png to webp, and save it over the original webp file
			$editor = wp_get_image_editor($uploaded_file);
			if (is_wp_error($editor)) {
				wp_send_json_error(['message' => __('Failed to open uploaded file for WebP conversion.', 'tka-site-utilities')]);
			}

			// Apply default WebP quality (e.g., 82)
			$editor->set_quality(82);

			// Save the webp directly to the original file path
			$saved = $editor->save($original_file_path, 'image/webp');
			if (is_wp_error($saved)) {
				wp_send_json_error(['message' => __('Failed to convert and save WebP file.', 'tka-site-utilities')]);
			}
		} else {
			// Direct file replacement using WP_Filesystem
			global $wp_filesystem;
			if (empty($wp_filesystem)) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}
			if (!$wp_filesystem->move($uploaded_file, $original_file_path, true)) {
				wp_send_json_error(['message' => __('Failed to move uploaded file to target directory.', 'tka-site-utilities')]);
			}
		}

		// Update attachment metadata and regenerate thumbnails (if image)
		if (strpos(get_post_mime_type($attachment_id), 'image/') === 0) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$new_metadata = wp_generate_attachment_metadata($attachment_id, $original_file_path);
			wp_update_attachment_metadata($attachment_id, $new_metadata);
		} else {
			// If it's a PDF or something else, update filesize meta
			$filesize = filesize($original_file_path);
			update_post_meta($attachment_id, '_wp_attachment_metadata', ['filesize' => $filesize]);
		}

		// Touch the post_modified timestamp to bust caches via our filters
		wp_update_post([
			'ID' => $attachment_id,
			// Passing just the ID to wp_update_post will automatically update the post_modified timestamp!
		]);

		// Purge page caches so the new file is reflected on the frontend
		\TKA\WPUtils\Core\Plugin::purgePageCaches();

		wp_send_json_success([
			'message' => __('File replaced successfully!', 'tka-site-utilities'),
			'timestamp' => time() // Cache-buster for UI updates
		]);
	}
}
