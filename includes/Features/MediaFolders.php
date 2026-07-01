<?php

namespace TKA\WPUtils\Features;

/**
 * Handles virtual media folders and drag-and-drop organization inside the media library.
 */
class MediaFolders
{

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'media_folder';

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void
	{
		add_action('init', [$this, 'registerTaxonomy']);
		add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
		add_action('wp_enqueue_media', [$this, 'enqueueAssets']);
		add_filter('ajax_query_attachments_args', [$this, 'filterAttachmentsQuery']);

		// AJAX Endpoints
		add_action('wp_ajax_tka_media_folders_get_tree', [$this, 'ajaxGetTree']);
		add_action('wp_ajax_tka_media_folders_create', [$this, 'ajaxCreateFolder']);
		add_action('wp_ajax_tka_media_folders_rename', [$this, 'ajaxRenameFolder']);
		add_action('wp_ajax_tka_media_folders_delete', [$this, 'ajaxDeleteFolder']);
		add_action('wp_ajax_tka_media_folders_move', [$this, 'ajaxMoveAttachment']);
	}

	/**
	 * Register the hierarchical custom taxonomy for attachments.
	 */
	public function registerTaxonomy(): void
	{
		$labels = [
			'name' => _x('Media Folders', 'taxonomy general name', 'tka-site-utilities'),
			'singular_name' => _x('Folder', 'taxonomy singular name', 'tka-site-utilities'),
			'search_items' => __('Search Folders', 'tka-site-utilities'),
			'all_items' => __('All Folders', 'tka-site-utilities'),
			'parent_item' => __('Parent Folder', 'tka-site-utilities'),
			'parent_item_colon' => __('Parent Folder:', 'tka-site-utilities'),
			'edit_item' => __('Edit Folder', 'tka-site-utilities'),
			'update_item' => __('Update Folder', 'tka-site-utilities'),
			'add_new_item' => __('Add New Folder', 'tka-site-utilities'),
			'new_item_name' => __('New Folder Name', 'tka-site-utilities'),
			'menu_name' => __('Folders', 'tka-site-utilities'),
		];

		register_taxonomy(self::TAXONOMY, 'attachment', [
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => false, // We build our own UI
			'show_admin_column' => true,
			'query_var' => true,
			'rewrite' => ['slug' => 'media-folder'],
			'show_in_rest' => true,
		]);
	}

	/**
	 * Enqueue frontend CSS/JS assets on media screen.
	 */
	public function enqueueAssets(): void
	{
		$css_ver = file_exists(TKA_SITE_UTILITIES_PATH . 'admin/css/media-folders.css') ? filemtime(TKA_SITE_UTILITIES_PATH . 'admin/css/media-folders.css') : TKA_SITE_UTILITIES_VERSION;
		$js_ver = file_exists(TKA_SITE_UTILITIES_PATH . 'admin/js/media-folders.js') ? filemtime(TKA_SITE_UTILITIES_PATH . 'admin/js/media-folders.js') : TKA_SITE_UTILITIES_VERSION;

		// Enqueue styling
		wp_enqueue_style(
			'tka-media-folders-css',
			TKA_SITE_UTILITIES_URL . 'admin/css/media-folders.css',
			[],
			$css_ver
		);

		// Enqueue scripts
		wp_enqueue_script(
			'tka-media-folders-js',
			TKA_SITE_UTILITIES_URL . 'admin/js/media-folders.js',
			['jquery', 'media-views'],
			$js_ver,
			true
		);

		// Localize with settings, REST API / Ajax details, and initial folder tree
		wp_localize_script('tka-media-folders-js', 'tkaMediaFolders', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tka-media-folders-nonce'),
			'i18n' => [
				'allFiles' => __('All Files', 'tka-site-utilities'),
				'unassigned' => __('Unassigned', 'tka-site-utilities'),
				'newFolder' => __('New Folder', 'tka-site-utilities'),
				'renameFolder' => __('Rename Folder', 'tka-site-utilities'),
				'deleteFolder' => __('Delete Folder', 'tka-site-utilities'),
				'confirmDelete' => __('Are you sure you want to delete this folder? Subfolders will be moved to the parent folder.', 'tka-site-utilities'),
				'emptyName' => __('Folder name cannot be empty.', 'tka-site-utilities'),
				'promptName' => __('Enter folder name:', 'tka-site-utilities'),
			]
		]);
	}

	/**
	 * Hook and alter query args for attachments ajax grid queries.
	 */
	public function filterAttachmentsQuery(array $query): array
	{
		$requested_folder = null;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (!empty($_REQUEST['query'][self::TAXONOMY])) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested_folder = sanitize_text_field(wp_unslash($_REQUEST['query'][self::TAXONOMY]));
		} elseif (!empty($query[self::TAXONOMY])) {
			$requested_folder = $query[self::TAXONOMY];
		}

		if ($requested_folder !== null && $requested_folder !== '') {
			$folder = sanitize_text_field($requested_folder);

			if (!isset($query['tax_query']) || !is_array($query['tax_query'])) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				$query['tax_query'] = [];
			}

			if ('unassigned' === $folder) {
				$query['tax_query'][] = [
					'taxonomy' => self::TAXONOMY,
					'operator' => 'NOT EXISTS',
				];
			} else {
				$query['tax_query'][] = [
					'taxonomy' => self::TAXONOMY,
					'field' => 'term_id',
					'terms' => [intval($folder)],
					'operator' => 'IN',
					'include_children' => true,
				];
			}

			// Unset the query_var from the query arguments to prevent WP_Query from auto-parsing it by term slug.
			unset($query[self::TAXONOMY]);
		}

		return $query;
	}

	/**
	 * AJAX helper to construct folder hierarchy tree.
	 */
	public function ajaxGetTree(): void
	{
		check_ajax_referer('tka-media-folders-nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(['message' => __('Unauthorized.', 'tka-site-utilities')]);
		}

		$terms = get_terms([
			'taxonomy' => self::TAXONOMY,
			'hide_empty' => false,
		]);

		if (is_wp_error($terms)) {
			wp_send_json_error(['message' => $terms->get_error_message()]);
		}

		$tree = $this->buildTree($terms);
		wp_send_json_success($tree);
	}

	/**
	 * Helper function to structure terms array hierarchically.
	 */
	private function buildTree(array $terms, int $parent_id = 0): array
	{
		$branch = [];
		foreach ($terms as $term) {
			if (intval($term->parent) === $parent_id) {
				$children = $this->buildTree($terms, $term->term_id);
				$count = $this->getAttachmentCountForFolder($term->term_id);

				$branch[] = [
					'id' => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
					'count' => $count,
					'children' => $children,
				];
			}
		}
		return $branch;
	}

	/**
	 * Get total attachments in folder, including child folders.
	 */
	private function getAttachmentCountForFolder(int $term_id): int
	{
		$term_ids = array_merge([$term_id], get_term_children($term_id, self::TAXONOMY));
		$query = new \WP_Query([
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'suppress_filters' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query' => [
				[
					'taxonomy' => self::TAXONOMY,
					'field' => 'term_id',
					'terms' => $term_ids,
					'operator' => 'IN',
				],
			],
		]);
		return $query->found_posts;
	}

	/**
	 * AJAX helper to create a new folder.
	 */
	public function ajaxCreateFolder(): void
	{
		check_ajax_referer('tka-media-folders-nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(['message' => __('Unauthorized.', 'tka-site-utilities')]);
		}

		$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		$parent = isset($_POST['parent']) ? intval(wp_unslash($_POST['parent'])) : 0;

		if (empty($name)) {
			wp_send_json_error(['message' => __('Folder name is required.', 'tka-site-utilities')]);
		}

		$result = wp_insert_term($name, self::TAXONOMY, [
			'parent' => $parent,
		]);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}

		wp_send_json_success([
			'id' => $result['term_id'],
			'name' => $name,
		]);
	}

	/**
	 * AJAX helper to rename a folder.
	 */
	public function ajaxRenameFolder(): void
	{
		check_ajax_referer('tka-media-folders-nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(['message' => __('Unauthorized.', 'tka-site-utilities')]);
		}

		$id = isset($_POST['id']) ? intval(wp_unslash($_POST['id'])) : 0;
		$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';

		if (!$id || empty($name)) {
			wp_send_json_error(['message' => __('Invalid parameters.', 'tka-site-utilities')]);
		}

		$result = wp_update_term($id, self::TAXONOMY, [
			'name' => $name,
		]);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}

		wp_send_json_success([
			'id' => $id,
			'name' => $name,
		]);
	}

	/**
	 * AJAX helper to delete a folder.
	 */
	public function ajaxDeleteFolder(): void
	{
		check_ajax_referer('tka-media-folders-nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(['message' => __('Unauthorized.', 'tka-site-utilities')]);
		}

		$id = intval($_POST['id'] ?? 0);
		if (!$id) {
			wp_send_json_error(['message' => __('Invalid folder ID.', 'tka-site-utilities')]);
		}

		// Move subfolders to parent before deleting
		$term = get_term($id, self::TAXONOMY);
		if ($term) {
			$children = get_term_children($id, self::TAXONOMY);
			foreach ($children as $child_id) {
				$child_term = get_term($child_id, self::TAXONOMY);
				if ($child_term && intval($child_term->parent) === $id) {
					wp_update_term($child_id, self::TAXONOMY, [
						'parent' => intval($term->parent),
					]);
				}
			}
		}

		$result = wp_delete_term($id, self::TAXONOMY);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}

		wp_send_json_success();
	}

	/**
	 * AJAX helper to associate an attachment with a folder.
	 */
	public function ajaxMoveAttachment(): void
	{
		check_ajax_referer('tka-media-folders-nonce', 'nonce');

		if (!current_user_can('upload_files')) {
			wp_send_json_error(['message' => __('Unauthorized.', 'tka-site-utilities')]);
		}

		$attachment_ids = isset($_POST['attachment_ids']) ? array_map('intval', (array) wp_unslash($_POST['attachment_ids'])) : [];
		$folder_id = isset($_POST['folder_id']) ? sanitize_text_field(wp_unslash($_POST['folder_id'])) : ''; // Could be term ID or 'unassigned'

		if (empty($attachment_ids)) {
			wp_send_json_error(['message' => __('No attachments specified.', 'tka-site-utilities')]);
		}

		foreach ($attachment_ids as $attachment_id) {
			if ('unassigned' === $folder_id || '' === $folder_id) {
				wp_set_object_terms($attachment_id, [], self::TAXONOMY);
			} else {
				wp_set_object_terms($attachment_id, [intval($folder_id)], self::TAXONOMY);
			}
		}

		wp_send_json_success();
	}
}
