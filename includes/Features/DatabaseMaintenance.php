<?php

namespace TKA\WPUtils\Features;

/**
 * Handles database maintenance and cleanup actions.
 */
class DatabaseMaintenance {

	/**
	 * Register hooks.
	 */
	public function hook(): void {
		add_action( 'wp_ajax_tka_wp_utils_db_get_counts', [ $this, 'ajaxGetCounts' ] );
		add_action( 'wp_ajax_tka_wp_utils_db_clean', [ $this, 'ajaxClean' ] );
		add_action( 'wp_ajax_tka_wp_utils_search_replace', [ $this, 'ajaxSearchReplace' ] );
	}

	/**
	 * Verify AJAX request permissions.
	 */
	private function verifyRequest(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'tka-wp-utils' ) ] );
		}
		
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tka_wp_utils_db_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'tka-wp-utils' ) ] );
		}
	}

	/**
	 * AJAX endpoint to get current counts of disposable items.
	 */
	public function ajaxGetCounts(): void {
		$this->verifyRequest();
		wp_send_json_success( $this->getCounts() );
	}

	/**
	 * Get counts for all cleanup categories.
	 */
	private function getCounts(): array {
		global $wpdb;
		$counts = [];

		$counts['revisions'] = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'revision'" );
		$counts['auto_drafts'] = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" );
		$counts['trashed_posts'] = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'trash'" );
		$counts['spam_comments'] = (int) $wpdb->get_var( "SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'spam' OR comment_approved = 'trash'" );
		$counts['orphan_postmeta'] = (int) $wpdb->get_var( "SELECT COUNT(pm.meta_id) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL" );
		$counts['orphan_commentmeta'] = (int) $wpdb->get_var( "SELECT COUNT(cm.meta_id) FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} wc ON wc.comment_ID = cm.comment_id WHERE wc.comment_ID IS NULL" );
		
		$now = time();
		$counts['expired_transients'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(option_id) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', $now ) );

		$counts['postmeta_index'] = $wpdb->get_var( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'idx_tka_meta_key_value'" ) ? 1 : 0;

		return $counts;
	}

	/**
	 * AJAX endpoint to execute a cleanup action.
	 */
	public function ajaxClean(): void {
		$this->verifyRequest();

		$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';

		global $wpdb;
		$deleted = 0;
		$message = '';

		try {
			switch ( $action_type ) {
				case 'revisions':
					$deleted = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'" );
					$message = sprintf( __( 'Deleted %d revisions.', 'tka-wp-utils' ), $deleted );
					break;

				case 'auto_drafts':
					$deleted = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" );
					$message = sprintf( __( 'Deleted %d auto-drafts.', 'tka-wp-utils' ), $deleted );
					break;

				case 'trashed_posts':
					$deleted = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'" );
					$message = sprintf( __( 'Deleted %d trashed posts.', 'tka-wp-utils' ), $deleted );
					break;

				case 'spam_comments':
					$deleted = $wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam' OR comment_approved = 'trash'" );
					$message = sprintf( __( 'Deleted %d spam/trashed comments.', 'tka-wp-utils' ), $deleted );
					break;

				case 'orphan_postmeta':
					$deleted = $wpdb->query( "DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL" );
					$message = sprintf( __( 'Deleted %d orphaned post meta entries.', 'tka-wp-utils' ), $deleted );
					break;

				case 'orphan_commentmeta':
					$deleted = $wpdb->query( "DELETE cm FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} wc ON wc.comment_ID = cm.comment_id WHERE wc.comment_ID IS NULL" );
					$message = sprintf( __( 'Deleted %d orphaned comment meta entries.', 'tka-wp-utils' ), $deleted );
					break;

				case 'expired_transients':
					$now = time();
					$timeouts = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', $now ) );
					$count = 0;
					foreach ( $timeouts as $timeout ) {
						$transient_name = str_replace( '_transient_timeout_', '', $timeout->option_name );
						delete_transient( $transient_name );
						delete_site_transient( $transient_name );
						$count++;
					}
					$message = sprintf( __( 'Cleared %d expired transients.', 'tka-wp-utils' ), $count );
					break;

				case 'optimize_tables':
					$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
					foreach ( $tables as $table ) {
						$wpdb->query( "OPTIMIZE TABLE $table" );
					}
					$message = __( 'Successfully optimized database tables.', 'tka-wp-utils' );
					break;

				case 'postmeta_index':
					$has_index = $wpdb->get_var( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'idx_tka_meta_key_value'" );
					if ( $has_index ) {
						$wpdb->query( "ALTER TABLE {$wpdb->postmeta} DROP INDEX idx_tka_meta_key_value" );
						$message = __( 'Removed custom PostMeta index.', 'tka-wp-utils' );
					} else {
						// Suppress errors during index creation if it somehow exists
						$wpdb->hide_errors();
						$wpdb->query( "CREATE INDEX idx_tka_meta_key_value ON {$wpdb->postmeta} (meta_key(191), meta_value(64))" );
						$wpdb->show_errors();
						$message = __( 'Added custom PostMeta index successfully.', 'tka-wp-utils' );
					}
					break;

				default:
					wp_send_json_error( [ 'message' => __( 'Invalid action type.', 'tka-wp-utils' ) ] );
					break;
			}

			// Return updated counts
			wp_send_json_success( [
				'message' => $message,
				'counts'  => $this->getCounts(),
			] );

		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * AJAX endpoint to execute WP-CLI Search and Replace with automated DB dump.
	 */
	public function ajaxSearchReplace(): void {
		$this->verifyRequest();

		if ( ! function_exists( 'shell_exec' ) ) {
			wp_send_json_error( [ 'message' => __( 'The shell_exec function is disabled on this server. WP-CLI commands cannot run.', 'tka-wp-utils' ) ] );
		}

		$search = isset( $_POST['search_string'] ) ? wp_unslash( $_POST['search_string'] ) : '';
		$replace = isset( $_POST['replace_string'] ) ? wp_unslash( $_POST['replace_string'] ) : '';
		$is_dry_run = isset( $_POST['dry_run'] ) && $_POST['dry_run'] === '1';

		if ( empty( $search ) ) {
			wp_send_json_error( [ 'message' => __( 'Search string cannot be empty.', 'tka-wp-utils' ) ] );
		}

		$backup_msg = '';

		// Run automated backup if this is a live run
		if ( ! $is_dry_run ) {
			$upload_dir = wp_upload_dir();
			$backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'tka-db-backups';
			
			// Create directory securely
			if ( ! wp_mkdir_p( $backup_dir ) ) {
				wp_send_json_error( [ 'message' => __( 'Failed to create backup directory.', 'tka-wp-utils' ) ] );
			}

			// Add .htaccess to block public access
			$htaccess = $backup_dir . '/.htaccess';
			if ( ! file_exists( $htaccess ) ) {
				file_put_contents( $htaccess, "Order deny,allow\nDeny from all" );
			}

			$timestamp = gmdate( 'Ymd_His' );
			$hash = substr( wp_hash( $timestamp ), 0, 8 );
			$backup_file = $backup_dir . "/backup_{$timestamp}_{$hash}.sql";

			// Execute DB Export
			$wp_bin = 'wp';
			$export_cmd = sprintf(
				'%s db export %s --path=%s 2>&1',
				escapeshellcmd( $wp_bin ),
				escapeshellarg( $backup_file ),
				escapeshellarg( ABSPATH )
			);
			
			$export_output = shell_exec( $export_cmd );

			if ( ! file_exists( $backup_file ) || filesize( $backup_file ) === 0 ) {
				wp_send_json_error( [ 
					'message' => __( 'Failed to create database backup before search and replace. Aborting for safety.', 'tka-wp-utils' ),
					'debug' => $export_output
				] );
			}

			$backup_msg = sprintf( __( 'Backup saved successfully to %s.', 'tka-wp-utils' ), basename( $backup_file ) );
		}

		// Execute Search and Replace
		$wp_bin = 'wp';
		$cmd = sprintf(
			'%s search-replace %s %s --path=%s --all-tables --report-changed-only %s',
			escapeshellcmd( $wp_bin ),
			escapeshellarg( $search ),
			escapeshellarg( $replace ),
			escapeshellarg( ABSPATH ),
			$is_dry_run ? '--dry-run' : ''
		);

		$output = shell_exec( $cmd );
		
		if ( $output === null ) {
			wp_send_json_error( [ 'message' => __( 'WP-CLI execution failed. No output received.', 'tka-wp-utils' ) ] );
		}

		wp_send_json_success( [
			'backup_msg' => $backup_msg,
			'raw_output' => $output
		] );
	}
}
