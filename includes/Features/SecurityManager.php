<?php

namespace TKA\WPUtils\Features;

/**
 * Handles author URL obfuscation, email protection, and XML-RPC lockdown.
 */
class SecurityManager {

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
		if ( ! empty( $this->options['obfuscate_author_urls'] ) ) {
			add_filter( 'author_link', [ $this, 'obfuscateAuthorLink' ], 20, 3 );
			add_filter( 'request', [ $this, 'resolveAuthorRequest' ] );
			add_action( 'template_redirect', [ $this, 'forceOriginalAuthorUrl404' ] );
			add_filter( 'rest_prepare_user', [ $this, 'obfuscateRestUserSlug' ], 20, 3 );
		}

		if ( ! empty( $this->options['obfuscate_emails'] ) ) {
			add_filter( 'the_content', [ $this, 'obfuscateEmailsInHtml' ], 99 );
			add_filter( 'widget_text', [ $this, 'obfuscateEmailsInHtml' ], 99 );
		}

		if ( ! empty( $this->options['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'wp_headers', [ $this, 'removePingbackHeader' ] );
			add_action( 'xmlrpc_call', [ $this, 'disableXmlrpcCall' ] );
		}
	}

	/**
	 * Generate a secure, 16-character alphanumeric obfuscation hash for a user.
	 */
	public static function getObfuscatedAuthorSlug( int $user_id ): string {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'tka_default_salt_key';
		return substr( md5( $user_id . $salt ), 0, 16 );
	}

	/**
	 * Obfuscate public author URLs.
	 */
	public function obfuscateAuthorLink( string $link, int $author_id, string $author_nicename ): string {
		$hash = self::getObfuscatedAuthorSlug( $author_id );
		return str_replace( '/author/' . $author_nicename, '/author/' . $hash, $link );
	}

	/**
	 * Resolve obfuscated hashes back to real user IDs in WordPress requests.
	 */
	public function resolveAuthorRequest( array $query_vars ): array {
		if ( ! empty( $query_vars['author_name'] ) ) {
			$requested_slug = $query_vars['author_name'];
			$users = get_users( [ 'fields' => [ 'ID' ] ] );
			foreach ( $users as $user ) {
				if ( self::getObfuscatedAuthorSlug( $user->ID ) === $requested_slug ) {
					$query_vars['author'] = $user->ID;
					unset( $query_vars['author_name'] );
					break;
				}
			}
		}
		return $query_vars;
	}

	/**
	 * Output a 404 page for requests containing real usernames.
	 */
	public function forceOriginalAuthorUrl404(): void {
		if ( is_author() ) {
			$author = get_queried_object();
			if ( $author instanceof \WP_User ) {
				$requested_slug = get_query_var( 'author_name' );
				if ( $requested_slug === $author->user_nicename ) {
					global $wp_query;
					$wp_query->set_404();
					status_header( 404 );
					nocache_headers();
					$template_404 = get_query_template( '404' );
					if ( $template_404 && file_exists( $template_404 ) ) {
						include( $template_404 );
					}
					exit;
				}
			}
		}
	}

	/**
	 * Obfuscate username slug inside user JSON responses on REST API.
	 */
	public function obfuscateRestUserSlug( $response, $user, $request ) {
		if ( isset( $response->data['slug'] ) ) {
			$response->data['slug'] = self::getObfuscatedAuthorSlug( $user->ID );
		}
		return $response;
	}

	/**
	 * Automatically scan content and obfuscate email addresses using HTML entities.
	 */
	public function obfuscateEmailsInHtml( string $content ): string {
		$pattern = '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i';
		return preg_replace_callback( $pattern, function ( $matches ) {
			return antispambot( $matches[1] );
		}, $content );
	}

	/**
	 * Remove XML-RPC Pingback HTTP header.
	 */
	public function removePingbackHeader( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}

	/**
	 * Kill XML-RPC executions with a 403 Forbidden exit.
	 */
	public function disableXmlrpcCall(): void {
		wp_die(
			__( 'XML-RPC is disabled on this site.', 'tka-wp-utils' ),
			__( 'XML-RPC Disabled', 'tka-wp-utils' ),
			[ 'response' => 403 ]
		);
	}
}
