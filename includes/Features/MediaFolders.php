<?php

namespace TKA\WPUtils\Features;

/**
 * Handles virtual media folders and drag-and-drop organization inside the media library.
 */
class MediaFolders
{

	/**
	 * Taxonomy slug kept for backward compatibility and JS mapping.
	 */
	const TAXONOMY = 'media_folder';

	/**
	 * Hook actions into WordPress.
	 */
	public function hook(): void
	{
		add_action('init', [$this, 'maybeCreateTables']);
		add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
		add_action('wp_enqueue_media', [$this, 'enqueueAssets']);
		add_filter('ajax_query_attachments_args', [$this, 'filterAttachmentsQuery']);
		add_filter('posts_clauses', [$this, 'filterPostsClauses'], 10, 2);
		add_action('delete_attachment', [$this, 'deleteAttachmentRelations']);
		add_action('add_attachment', [$this, 'onAddAttachment']);
		add_filter('wp_prepare_attachment_for_js', [$this, 'prepareAttachmentForJs'], 10, 3);

		// AJAX Endpoints
		add_action('wp_ajax_tka_media_folders_get_tree', [$this, 'ajaxGetTree']);
		add_action('wp_ajax_tka_media_folders_create', [$this, 'ajaxCreateFolder']);
		add_action('wp_ajax_tka_media_folders_rename', [$this, 'ajaxRenameFolder']);
		add_action('wp_ajax_tka_media_folders_delete', [$this, 'ajaxDeleteFolder']);
		add_action('wp_ajax_tka_media_folders_move', [$this, 'ajaxMoveAttachment']);
	}

	/**
	 * Create database tables if they do not exist.
	 */
	public function maybeCreateTables(): void
	{
		global $wpdb;
		$folders_table = $wpdb->prefix . 'tka_media_folders';
		$posts_table = $wpdb->prefix . 'tka_media_folder_posts';
		$charset_collate = $wpdb->get_charset_collate();

		// Check if folders table exists
		if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $folders_table)) !== $folders_table) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql_folders = "CREATE TABLE {$folders_table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				parent bigint(20) unsigned NOT NULL DEFAULT 0,
				name varchar(255) NOT NULL,
				slug varchar(255) NOT NULL,
				PRIMARY KEY  (id)
			) {$charset_collate};";
			dbDelta($sql_folders);
		}

		// Check if posts table exists
		if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $posts_table)) !== $posts_table) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql_posts = "CREATE TABLE {$posts_table} (
				attachment_id bigint(20) unsigned NOT NULL,
				folder_id bigint(20) unsigned NOT NULL,
				PRIMARY KEY  (attachment_id),
				KEY folder_id (folder_id)
			) {$charset_collate};";
			dbDelta($sql_posts);
		}
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
		if (isset($_REQUEST['query'][self::TAXONOMY]) && $_REQUEST['query'][self::TAXONOMY] !== '') {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$requested_folder = sanitize_text_field(wp_unslash($_REQUEST['query'][self::TAXONOMY]));
		} elseif (isset($query[self::TAXONOMY]) && $query[self::TAXONOMY] !== '') {
			$requested_folder = $query[self::TAXONOMY];
		}

		if ($requested_folder !== null && $requested_folder !== '') {
			$query['tka_folder_id'] = sanitize_text_field($requested_folder);
			unset($query[self::TAXONOMY]);
		}

		return $query;
	}

	/**
	 * Modify SQL clauses to filter attachments by folder.
	 */
	public function filterPostsClauses(array $clauses, \WP_Query $query): array
	{
		global $wpdb;

		// Only modify attachment queries
		if ($query->get('post_type') !== 'attachment') {
			return $clauses;
		}

		$requested_folder = null;
		if (isset($query->query_vars['tka_folder_id'])) {
			$requested_folder = $query->query_vars['tka_folder_id'];
		}

		if ($requested_folder !== null && $requested_folder !== '') {
			$folder = sanitize_text_field($requested_folder);
			$posts_table = $wpdb->prefix . 'tka_media_folder_posts';

			// Join relationship table
			$clauses['join'] .= " LEFT JOIN {$posts_table} AS tka_folder ON tka_folder.attachment_id = {$wpdb->posts}.ID ";

			if ('unassigned' === $folder) {
				// unassigned: attachment is either not in the posts table or folder_id is 0
				$clauses['where'] .= " AND (tka_folder.folder_id IS NULL OR tka_folder.folder_id = 0) ";
			} else {
				$folder_id = intval($folder);
				$folder_ids = $this->getFolderChildrenIdsRecursive($folder_id);
				$folder_ids[] = $folder_id;
				$folder_ids_in = implode(',', array_map('intval', $folder_ids));
				$clauses['where'] .= " AND tka_folder.folder_id IN ({$folder_ids_in}) ";
			}
		}

		return $clauses;
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

		global $wpdb;
		$folders_table = $wpdb->prefix . 'tka_media_folders';
		$folders = $wpdb->get_results("SELECT * FROM {$folders_table} ORDER BY name ASC");

		if (is_array($folders)) {
			$tree = $this->buildTree($folders);
			wp_send_json_success($tree);
		} else {
			wp_send_json_success([]);
		}
	}

	/**
	 * Helper function to structure folders array hierarchically.
	 */
	private function buildTree(array $folders, int $parent_id = 0): array
	{
		$branch = [];
		foreach ($folders as $folder) {
			if (intval($folder->parent) === $parent_id) {
				$children = $this->buildTree($folders, intval($folder->id));
				$count = $this->getAttachmentCountForFolder(intval($folder->id));

				$branch[] = [
					'id' => intval($folder->id),
					'name' => $folder->name,
					'slug' => $folder->slug,
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
	private function getAttachmentCountForFolder(int $folder_id): int
	{
		global $wpdb;
		$posts_table = $wpdb->prefix . 'tka_media_folder_posts';

		$folder_ids = $this->getFolderChildrenIdsRecursive($folder_id);
		$folder_ids[] = $folder_id;
		$folder_ids_in = implode(',', array_map('intval', $folder_ids));

		$count = $wpdb->get_var("SELECT COUNT(*) FROM {$posts_table} WHERE folder_id IN ({$folder_ids_in})");
		return intval($count);
	}

	/**
	 * Helper to get all children folder IDs recursively.
	 */
	private function getFolderChildrenIdsRecursive(int $folder_id, array $visited = []): array
	{
		global $wpdb;
		$folders_table = $wpdb->prefix . 'tka_media_folders';

		if (in_array($folder_id, $visited, true)) {
			return [];
		}
		$visited[] = $folder_id;

		$children_ids = [];
		$direct_children = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$folders_table} WHERE parent = %d", $folder_id));

		if (!empty($direct_children)) {
			foreach ($direct_children as $child_id) {
				$children_ids[] = intval($child_id);
				$children_ids = array_merge($children_ids, $this->getFolderChildrenIdsRecursive(intval($child_id), $visited));
			}
		}
		return $children_ids;
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

		global $wpdb;
		$folders_table = $wpdb->prefix . 'tka_media_folders';

		$inserted = $wpdb->insert(
			$folders_table,
			[
				'name' => $name,
				'parent' => $parent,
				'slug' => sanitize_title($name),
			],
			['%s', '%d', '%s']
		);

		if ($inserted === false) {
			wp_send_json_error(['message' => __('Failed to create folder in database.', 'tka-site-utilities')]);
		}

		wp_send_json_success([
			'id' => $wpdb->insert_id,
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

		global $wpdb;
		$folders_table = $wpdb->prefix . 'tka_media_folders';

		$updated = $wpdb->update(
			$folders_table,
			[
				'name' => $name,
				'slug' => sanitize_title($name),
			],
			['id' => $id],
			['%s', '%s'],
			['%d']
		);

		if ($updated === false) {
			wp_send_json_error(['message' => __('Failed to rename folder.', 'tka-site-utilities')]);
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

		global $wpdb;
		$folders_table = $wpdb->prefix . 'tka_media_folders';
		$posts_table = $wpdb->prefix . 'tka_media_folder_posts';

		// Get the parent folder ID of the folder to delete
		$parent_id = $wpdb->get_var($wpdb->prepare("SELECT parent FROM {$folders_table} WHERE id = %d", $id));
		if ($parent_id === null) {
			wp_send_json_error(['message' => __('Folder not found.', 'tka-site-utilities')]);
		}
		$parent_id = intval($parent_id);

		// Move direct child folders of this folder to its parent
		$wpdb->update(
			$folders_table,
			['parent' => $parent_id],
			['parent' => $id],
			['%d'],
			['%d']
		);

		// Move posts of this folder to its parent
		$wpdb->update(
			$posts_table,
			['folder_id' => $parent_id],
			['folder_id' => $id],
			['%d'],
			['%d']
		);

		// Delete the folder itself
		$deleted = $wpdb->delete($folders_table, ['id' => $id], ['%d']);

		if ($deleted === false) {
			wp_send_json_error(['message' => __('Failed to delete folder.', 'tka-site-utilities')]);
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
		$folder_id = isset($_POST['folder_id']) ? sanitize_text_field(wp_unslash($_POST['folder_id'])) : ''; // Could be folder ID or 'unassigned'

		if (empty($attachment_ids)) {
			wp_send_json_error(['message' => __('No attachments specified.', 'tka-site-utilities')]);
		}

		global $wpdb;
		$posts_table = $wpdb->prefix . 'tka_media_folder_posts';

		foreach ($attachment_ids as $attachment_id) {
			if ('unassigned' === $folder_id || '' === $folder_id || 0 === intval($folder_id)) {
				// Remove association from relationship table
				$wpdb->delete($posts_table, ['attachment_id' => $attachment_id], ['%d']);
			} else {
				// Insert or update association
				$wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$posts_table} (attachment_id, folder_id) VALUES (%d, %d)
						ON DUPLICATE KEY UPDATE folder_id = %d",
						$attachment_id,
						intval($folder_id),
						intval($folder_id)
					)
				);
			}
		}

		wp_send_json_success();
	}

	/**
	 * Clean up folder associations when an attachment is deleted.
	 */
	public function deleteAttachmentRelations(int $post_id): void
	{
		global $wpdb;
		$posts_table = $wpdb->prefix . 'tka_media_folder_posts';
		$wpdb->delete($posts_table, ['attachment_id' => $post_id], ['%d']);
	}

	/**
	 * Automatically assign folder on upload when tka_folder_id or media_folder is present in request.
	 */
	public function onAddAttachment(int $attachment_id): void
	{
		$folder_id = null;
		if (isset($_REQUEST['tka_folder_id'])) {
			$folder_id = sanitize_text_field(wp_unslash($_REQUEST['tka_folder_id']));
		} elseif (isset($_REQUEST['media_folder'])) {
			$folder_id = sanitize_text_field(wp_unslash($_REQUEST['media_folder']));
		}

		if ($folder_id !== null && $folder_id !== '' && $folder_id !== 'unassigned' && intval($folder_id) > 0) {
			global $wpdb;
			$posts_table = $wpdb->prefix . 'tka_media_folder_posts';
			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$posts_table} (attachment_id, folder_id) VALUES (%d, %d)
					ON DUPLICATE KEY UPDATE folder_id = %d",
					$attachment_id,
					intval($folder_id),
					intval($folder_id)
				)
			);
		}
	}

	/**
	 * Attach folder metadata to attachment object for media JavaScript views.
	 */
	public function prepareAttachmentForJs(array $response, \WP_Post $post, $meta): array
	{
		global $wpdb;
		$posts_table = $wpdb->prefix . 'tka_media_folder_posts';
		$folder_id = $wpdb->get_var($wpdb->prepare(
			"SELECT folder_id FROM {$posts_table} WHERE attachment_id = %d",
			$post->ID
		));

		$response['media_folder'] = $folder_id ? (string) $folder_id : '';
		$response['tka_folder_id'] = $folder_id ? intval($folder_id) : 0;

		return $response;
	}
}
