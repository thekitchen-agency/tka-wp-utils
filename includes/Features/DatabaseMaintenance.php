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
		add_action( 'wp_ajax_tka_site_utilities_db_get_counts', [ $this, 'ajaxGetCounts' ] );
		add_action( 'wp_ajax_tka_site_utilities_db_clean', [ $this, 'ajaxClean' ] );
		add_action( 'wp_ajax_tka_site_utilities_search_replace', [ $this, 'ajaxSearchReplace' ] );
	}

	/**
	 * Verify AJAX request permissions.
	 */
	private function verifyRequest(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'tka-site-utilities' ) ] );
		}
		
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tka_site_utilities_db_nonce' ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'tka-site-utilities' ) ] );
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts['revisions'] = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'revision'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts['auto_drafts'] = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts['trashed_posts'] = (int) $wpdb->get_var( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_status = 'trash'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts['spam_comments'] = (int) $wpdb->get_var( "SELECT COUNT(comment_ID) FROM {$wpdb->comments} WHERE comment_approved = 'spam' OR comment_approved = 'trash'" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts['orphan_postmeta'] = (int) $wpdb->get_var( "SELECT COUNT(pm.meta_id) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL" );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts['orphan_commentmeta'] = (int) $wpdb->get_var( "SELECT COUNT(cm.meta_id) FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} wc ON wc.comment_ID = cm.comment_id WHERE wc.comment_ID IS NULL" );
		
		$now = time();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts['expired_transients'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(option_id) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', $now ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$counts['postmeta_index'] = $wpdb->get_var( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'idx_tka_meta_key_value'" ) ? 1 : 0;

		return $counts;
	}

	/**
	 * AJAX endpoint to execute a cleanup action.
	 */
	public function ajaxClean(): void {
		$this->verifyRequest();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';

		global $wpdb;
		$deleted = 0;
		$message = '';

		try {
			switch ( $action_type ) {
				case 'revisions':
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$deleted = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'" );
					/* translators: %d: Number of revisions deleted */
					$message = sprintf( __( 'Deleted %d revisions.', 'tka-site-utilities' ), $deleted );
					break;

				case 'auto_drafts':
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$deleted = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" );
					/* translators: %d: Number of auto-drafts deleted */
					$message = sprintf( __( 'Deleted %d auto-drafts.', 'tka-site-utilities' ), $deleted );
					break;

				case 'trashed_posts':
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$deleted = $wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'" );
					/* translators: %d: Number of trashed posts deleted */
					$message = sprintf( __( 'Deleted %d trashed posts.', 'tka-site-utilities' ), $deleted );
					break;

				case 'spam_comments':
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$deleted = $wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam' OR comment_approved = 'trash'" );
					/* translators: %d: Number of spam/trashed comments deleted */
					$message = sprintf( __( 'Deleted %d spam/trashed comments.', 'tka-site-utilities' ), $deleted );
					break;

				case 'orphan_postmeta':
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$deleted = $wpdb->query( "DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL" );
					/* translators: %d: Number of orphaned post meta entries deleted */
					$message = sprintf( __( 'Deleted %d orphaned post meta entries.', 'tka-site-utilities' ), $deleted );
					break;

				case 'orphan_commentmeta':
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$deleted = $wpdb->query( "DELETE cm FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} wc ON wc.comment_ID = cm.comment_id WHERE wc.comment_ID IS NULL" );
					/* translators: %d: Number of orphaned comment meta entries deleted */
					$message = sprintf( __( 'Deleted %d orphaned comment meta entries.', 'tka-site-utilities' ), $deleted );
					break;

				case 'expired_transients':
					$now = time();
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$timeouts = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d", '_transient_timeout_%', $now ) );
					$count = 0;
					foreach ( $timeouts as $timeout ) {
						$transient_name = str_replace( '_transient_timeout_', '', $timeout->option_name );
						delete_transient( $transient_name );
						delete_site_transient( $transient_name );
						$count++;
					}
					/* translators: %d: Number of transients deleted */
					$message = sprintf( __( 'Cleared %d expired transients.', 'tka-site-utilities' ), $count );
					break;

				case 'optimize_tables':
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );
					foreach ( $tables as $table ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->query( $wpdb->prepare( "OPTIMIZE TABLE %i", $table ) );
					}
					$message = __( 'Successfully optimized database tables.', 'tka-site-utilities' );
					break;

				case 'postmeta_index':
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$has_index = $wpdb->get_var( "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = 'idx_tka_meta_key_value'" );
					if ( $has_index ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->query( "ALTER TABLE {$wpdb->postmeta} DROP INDEX idx_tka_meta_key_value" );
						$message = __( 'Removed custom PostMeta index.', 'tka-site-utilities' );
					} else {
						// Suppress errors during index creation if it somehow exists
						$wpdb->hide_errors();
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->query( "CREATE INDEX idx_tka_meta_key_value ON {$wpdb->postmeta} (meta_key(191), meta_value(64))" );
						$wpdb->show_errors();
						$message = __( 'Added custom PostMeta index successfully.', 'tka-site-utilities' );
					}
					break;

				default:
					wp_send_json_error( [ 'message' => __( 'Invalid action type.', 'tka-site-utilities' ) ] );
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
			wp_send_json_error( [ 'message' => __( 'The shell_exec function is disabled on this server. WP-CLI commands cannot run.', 'tka-site-utilities' ) ] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$search = isset( $_POST['search_string'] ) ? wp_unslash( $_POST['search_string'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$replace = isset( $_POST['replace_string'] ) ? wp_unslash( $_POST['replace_string'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$is_dry_run = isset( $_POST['dry_run'] ) && sanitize_text_field( wp_unslash( $_POST['dry_run'] ) ) === '1';

		if ( empty( $search ) ) {
			wp_send_json_error( [ 'message' => __( 'Search string cannot be empty.', 'tka-site-utilities' ) ] );
		}

		$backup_msg = '';

		// Run automated backup if this is a live run
		if ( ! $is_dry_run ) {
			$upload_dir = wp_upload_dir();
			$backup_dir = trailingslashit( $upload_dir['basedir'] ) . 'tka-db-backups';
			
			// Create directory securely
			if ( ! wp_mkdir_p( $backup_dir ) ) {
				wp_send_json_error( [ 'message' => __( 'Failed to create backup directory.', 'tka-site-utilities' ) ] );
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
					'message' => __( 'Failed to create database backup before search and replace. Aborting for safety.', 'tka-site-utilities' ),
					'debug' => $export_output
				] );
			}

			/* translators: %s: Backup filename */
			$backup_msg = sprintf( __( 'Backup saved successfully to %s.', 'tka-site-utilities' ), basename( $backup_file ) );
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
			wp_send_json_error( [ 'message' => __( 'WP-CLI execution failed. No output received.', 'tka-site-utilities' ) ] );
		}

		wp_send_json_success( [
			'backup_msg' => $backup_msg,
			'raw_output' => $output
		] );
	}
}
