<?php

namespace TKA\WPUtils\Features;

/**
 * Handles optimization of the WordPress Heartbeat API and Post Revisions.
 */
class HeartbeatRevisionManager {

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
	 * Register actions and filters dynamically based on active options.
	 */
	public function hook(): void {
		$this->controlHeartbeat();
		$this->controlRevisions();
	}

	/**
	 * Heartbeat API Control.
	 */
	private function controlHeartbeat(): void {
		$control = $this->options['heartbeat_control'] ?? 'default';

		if ( 'disable_everywhere' === $control ) {
			add_action( 'init', [ $this, 'deregisterHeartbeat' ], 1 );
		} elseif ( 'disable_dashboard' === $control ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'disableHeartbeatOnDashboard' ], 1 );
		} elseif ( 'allow_only_post_edit' === $control ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'allowHeartbeatOnlyOnPostEdit' ], 1 );
		}

		// Apply custom frequency if heartbeat is not completely disabled
		if ( 'disable_everywhere' !== $control ) {
			add_filter( 'heartbeat_settings', [ $this, 'adjustHeartbeatFrequency' ] );
		}
	}

	/**
	 * Dequeue and deregister Heartbeat script globally.
	 */
	public function deregisterHeartbeat(): void {
		wp_deregister_script( 'heartbeat' );
	}

	/**
	 * Disable Heartbeat on the main Admin Dashboard page.
	 */
	public function disableHeartbeatOnDashboard(): void {
		$screen = get_current_screen();
		if ( $screen && 'dashboard' === $screen->base ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	/**
	 * Disable Heartbeat everywhere except on post edit screens.
	 */
	public function allowHeartbeatOnlyOnPostEdit(): void {
		$screen = get_current_screen();
		if ( $screen && 'post' !== $screen->base ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	/**
	 * Adjust the interval between Heartbeat requests.
	 */
	public function adjustHeartbeatFrequency( array $settings ): array {
		$frequency = intval( $this->options['heartbeat_frequency'] ?? 60 );
		if ( $frequency >= 15 && $frequency <= 120 ) {
			$settings['interval'] = $frequency;
		}
		return $settings;
	}

	/**
	 * Post Revisions Control.
	 */
	private function controlRevisions(): void {
		if ( isset( $this->options['revisions_limit'] ) ) {
			add_filter( 'wp_revisions_to_keep', [ $this, 'getRevisionsLimit' ], 10, 2 );
		}
	}

	/**
	 * Callback to dynamically set post revisions limit.
	 */
	public function getRevisionsLimit( int $num, $post ): int {
		$limit = intval( $this->options['revisions_limit'] ?? -1 );
		return $limit;
	}
}
