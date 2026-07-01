<?php

namespace TKA\WPUtils\Features;

/**
 * Handles various optimizations and clean-up features inside WordPress.
 */
class VariousCleaner {

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
		if ( ! empty( $this->options['disable_emojis'] ) ) {
			$this->disableEmojis();
		}
		if ( ! empty( $this->options['disable_comments'] ) ) {
			$this->disableComments();
		}
		if ( ! empty( $this->options['disable_rest_api'] ) ) {
			$this->disableRestApi();
		}
		if ( ! empty( $this->options['disable_feeds'] ) ) {
			$this->disableFeeds();
		}
		if ( ! empty( $this->options['disable_embeds'] ) ) {
			$this->disableEmbeds();
		}
		if ( ! empty( $this->options['disable_version_strings'] ) ) {
			$this->disableVersionStrings();
		}
		if ( ! empty( $this->options['disable_front_dashicons'] ) ) {
			$this->disableFrontDashicons();
		}
		if ( ! empty( $this->options['disable_wp_cron'] ) ) {
			$this->disableWpCron();
		}
	}

	/**
	 * 1. Disable Emojis scripts and styles.
	 */
	private function disableEmojis(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', [ $this, 'disableEmojisTinyMce' ] );
		add_filter( 'wp_resource_hints', [ $this, 'disableEmojisResourceHints' ], 10, 2 );
	}

	/**
	 * Remove emoji plugins from TinyMCE.
	 */
	public function disableEmojisTinyMce( array $plugins ): array {
		return array_values( array_diff( $plugins, [ 'wpemoji' ] ) );
	}

	/**
	 * Remove emoji DNS prefetch resources.
	 */
	public function disableEmojisResourceHints( array $urls, string $relation_type ): array {
		if ( 'dns-prefetch' === $relation_type ) {
			foreach ( $urls as $key => $url ) {
				if ( str_contains( $url, 's.w.org/images/core/emoji/' ) ) {
					unset( $urls[ $key ] );
				}
			}
		}
		return $urls;
	}


	/**
	 * 3. Globally disable comments.
	 */
	private function disableComments(): void {
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'pings_open', '__return_false', 20, 2 );
		add_filter( 'comments_array', '__return_empty_array', 10, 2 );
		add_action( 'admin_menu', [ $this, 'removeCommentsAdminMenu' ] );
		add_action( 'wp_before_admin_bar_render', [ $this, 'removeCommentsAdminBar' ] );
		add_action( 'admin_init', [ $this, 'removeCommentsPostTypeSupport' ] );
	}

	/**
	 * Remove comments from admin page list.
	 */
	public function removeCommentsAdminMenu(): void {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Remove comments from admin top bar.
	 */
	public function removeCommentsAdminBar(): void {
		global $wp_admin_bar;
		if ( $wp_admin_bar ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}
	}

	/**
	 * Disable post type registration support.
	 */
	public function removeCommentsPostTypeSupport(): void {
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	/**
	 * 4. Disable public REST API.
	 */
	private function disableRestApi(): void {
		add_filter( 'rest_authentication_errors', [ $this, 'restrictRestApi' ] );
	}

	/**
	 * Return error for unauthenticated calls.
	 */
	public function restrictRestApi( $result ) {
		if ( true === $result || is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_not_logged_in',
				__( 'REST API restricted to authenticated users only.', 'tka-site-utilities' ),
				[ 'status' => 401 ]
			);
		}
		return $result;
	}

	/**
	 * 5. Disable RSS and Atom Feeds.
	 */
	private function disableFeeds(): void {
		add_action( 'do_feed', [ $this, 'disableFeedRedirect' ], 1 );
		add_action( 'do_feed_rdf', [ $this, 'disableFeedRedirect' ], 1 );
		add_action( 'do_feed_rss', [ $this, 'disableFeedRedirect' ], 1 );
		add_action( 'do_feed_rss2', [ $this, 'disableFeedRedirect' ], 1 );
		add_action( 'do_feed_atom', [ $this, 'disableFeedRedirect' ], 1 );
		add_action( 'do_feed_rss2_comments', [ $this, 'disableFeedRedirect' ], 1 );
		add_action( 'do_feed_atom_comments', [ $this, 'disableFeedRedirect' ], 1 );

		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}

	/**
	 * Trigger wp_die on feed attempts.
	 */
	public function disableFeedRedirect(): void {
		wp_die(
			wp_kses_post(
				sprintf(
					/* translators: %s: Homepage URL */
					__( 'Feeds are disabled. Please visit our <a href="%s">homepage</a>.', 'tka-site-utilities' ),
					esc_url( home_url( '/' ) )
				)
			)
		);
	}

	/**
	 * 6. Disable Embeds.
	 */
	private function disableEmbeds(): void {
		add_action( 'init', [ $this, 'removeEmbedHooks' ] );
	}

	/**
	 * Dequeue embeds asset routines.
	 */
	public function removeEmbedHooks(): void {
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'embed_oembed_discover', '__return_false' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
		wp_deregister_script( 'wp-embed' );
	}

	/**
	 * 7. Disable Version query strings and meta generators.
	 */
	private function disableVersionStrings(): void {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'script_loader_src', [ $this, 'removeVersionQuery' ], 9999 );
		add_filter( 'style_loader_src', [ $this, 'removeVersionQuery' ], 9999 );
	}

	/**
	 * Strip ver parameters from resources.
	 */
	public function removeVersionQuery( string $src ): string {
		if ( str_contains( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	/**
	 * 8. Dequeue Front-End Dashicons.
	 */
	private function disableFrontDashicons(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeueDashicons' ], 100 );
	}

	/**
	 * Dequeue stylesheet for anonymous visitors.
	 */
	public function dequeueDashicons(): void {
		if ( ! is_admin() && ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_style( 'dashicons' );
		}
	}

	/**
	 * 9. Disable Virtual Cron (WP-Cron) loopback requests.
	 */
	private function disableWpCron(): void {
		if ( ! defined( 'DISABLE_WP_CRON' ) ) {
			define( 'DISABLE_WP_CRON', true );
		}
	}

}
