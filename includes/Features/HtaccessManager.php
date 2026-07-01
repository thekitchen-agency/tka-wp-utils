<?php

namespace TKA\WPUtils\Features;

/**
 * Handles .htaccess generation and writing for root and uploads directories.
 */
class HtaccessManager {

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
	 * Hook actions.
	 */
	public function hook(): void {
		// Hook when options are updated/added
		add_action( 'update_option_tka_site_utilities_options', [ $this, 'onSettingsSaved' ], 10, 2 );
		add_action( 'add_option_tka_site_utilities_options', [ $this, 'onSettingsSaved' ], 10, 2 );
	}

	/**
	 * Triggered when plugin options are saved.
	 */
	public function onSettingsSaved( $old_value, $value ): void {
		if ( is_array( $value ) ) {
			$this->options = $value;
			$this->writeRules();
			\TKA\WPUtils\Core\Plugin::purgePageCaches();
		}
	}

	/**
	 * Check if the web server software runs Apache or LiteSpeed.
	 */
	public static function isApacheOrLiteSpeed(): bool {
		$server = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		if ( empty( $server ) ) {
			return false;
		}
		return str_contains( strtolower( $server ), 'apache' ) || str_contains( strtolower( $server ), 'litespeed' );
	}

	/**
	 * Get the home path reliably, including fallback under WP-CLI or Bedrock environments.
	 */
	public static function getHomePath(): string {
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$home_path = get_home_path();
		if ( $home_path === '/' || empty( $home_path ) || ! is_dir( $home_path ) ) {
			$home_path = ABSPATH;
			$home = set_url_scheme( get_option( 'home' ), 'http' );
			$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );
			if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
				$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl );
				$wp_path_rel_to_home = trim( $wp_path_rel_to_home, '/' );
				if ( ! empty( $wp_path_rel_to_home ) ) {
					$abspath_norm = str_replace( '\\', '/', ABSPATH );
					if ( str_ends_with( rtrim( $abspath_norm, '/' ), '/' . $wp_path_rel_to_home ) ) {
						$home_path = substr( $abspath_norm, 0, -strlen( $wp_path_rel_to_home ) - 1 );
					}
				}
			}
		}
		return trailingslashit( str_replace( '\\', '/', $home_path ) );
	}

	/**
	 * Check if the root .htaccess is writeable (or writeable upon creation).
	 */
	public static function isRootHtaccessWritable(): bool {
		$home_path = self::getHomePath();
		$htaccess = $home_path . '.htaccess';
		if ( file_exists( $htaccess ) ) {
			return self::isPathWritable( $htaccess );
		}
		return self::isPathWritable( $home_path );
	}

	/**
	 * Check if the uploads directory .htaccess is writeable.
	 */
	public static function isUploadsHtaccessWritable(): bool {
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return false;
		}
		$uploads_path = $upload_dir['basedir'];
		$htaccess = $uploads_path . '/.htaccess';
		if ( file_exists( $htaccess ) ) {
			return self::isPathWritable( $htaccess );
		}
		return self::isPathWritable( $uploads_path );
	}

	/**
	 * Write root and uploads .htaccess rules based on active options.
	 */
	public function writeRules(): bool {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$root_success = true;
		$uploads_success = true;

		// 1. Root .htaccess rules
		$root_htaccess = self::getHomePath() . '.htaccess';
		$root_rules = $this->generateRootRules();

		if ( self::isRootHtaccessWritable() ) {
			// insert_with_markers expects rules as array of lines
			$inserted = insert_with_markers( $root_htaccess, 'TKA_Site_Utilities', $root_rules );
			if ( ! $inserted ) {
				$root_success = false;
			}
		} else {
			$root_success = false;
		}

		// 2. Uploads .htaccess rules
		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['error'] ) ) {
			$uploads_htaccess = $upload_dir['basedir'] . '/.htaccess';
			$uploads_rules = $this->generateUploadsRules();

			if ( self::isUploadsHtaccessWritable() ) {
				if ( ! empty( $uploads_rules ) ) {
					$inserted = insert_with_markers( $uploads_htaccess, 'TKA_Site_Utilities_Uploads', $uploads_rules );
					if ( ! $inserted ) {
						$uploads_success = false;
					}
				} else {
					// Clean up / remove markers if disabled
					insert_with_markers( $uploads_htaccess, 'TKA_Site_Utilities_Uploads', [] );
					// If the file is now empty, delete it
					if ( file_exists( $uploads_htaccess ) && filesize( $uploads_htaccess ) === 0 ) {
						wp_delete_file( $uploads_htaccess );
					}
				}
			} else {
				$uploads_success = false;
			}
		} else {
			$uploads_success = false;
		}

		return $root_success && $uploads_success;
	}

	/**
	 * Clear all written markers from root and uploads .htaccess.
	 */
	public function clearRules(): void {
		if ( ! function_exists( 'insert_with_markers' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}

		$root_htaccess = self::getHomePath() . '.htaccess';
		if ( file_exists( $root_htaccess ) && self::isPathWritable( $root_htaccess ) ) {
			insert_with_markers( $root_htaccess, 'TKA_Site_Utilities', [] );
		}

		$upload_dir = wp_upload_dir();
		if ( empty( $upload_dir['error'] ) ) {
			$uploads_htaccess = $upload_dir['basedir'] . '/.htaccess';
			if ( file_exists( $uploads_htaccess ) && self::isPathWritable( $uploads_htaccess ) ) {
				insert_with_markers( $uploads_htaccess, 'TKA_Site_Utilities_Uploads', [] );
				if ( filesize( $uploads_htaccess ) === 0 ) {
					wp_delete_file( $uploads_htaccess );
				}
			}
		}
	}

	/**
	 * Generate root .htaccess rules array.
	 */
	public function generateRootRules(): array {
		$rules = [];

		// CORS Headers
		if ( ! empty( $this->options['htaccess_cors'] ) ) {
			$rules[] = '# Asset CORS Headers';
			$rules[] = '<IfModule mod_headers.c>';
			$rules[] = '    <FilesMatch "\.(ttf|ttc|otf|eot|woff|woff2|font.css|css|js|mjs|gif|png|jpe?g|svg|svgz|ico|webp)$">';
			$rules[] = '        Header set Access-Control-Allow-Origin "*"';
			$rules[] = '    </FilesMatch>';
			$rules[] = '</IfModule>';
			$rules[] = '';
		}

		// Security & Hardening
		if ( ! empty( $this->options['htaccess_security'] ) ) {
			$rules[] = '# Deny access to wp-config.php file';
			$rules[] = '<files wp-config.php>';
			$rules[] = 'order allow,deny';
			$rules[] = 'deny from all';
			$rules[] = '</files>';
			$rules[] = '';
			$rules[] = '# Deny access to user.ini file';
			$rules[] = '<files user.ini>';
			$rules[] = 'order allow,deny';
			$rules[] = 'deny from all';
			$rules[] = '</files>';
			$rules[] = '';
			$rules[] = '# Deny access to all .htaccess files';
			$rules[] = '<files ~ "^.*\.([Hh][Tt][Aa])">';
			$rules[] = 'order allow,deny';
			$rules[] = 'deny from all';
			$rules[] = 'satisfy all';
			$rules[] = '</files>';
			$rules[] = '';
			$rules[] = '# Disable Directory Browsing';
			$rules[] = 'Options -Indexes';
			$rules[] = '';
			$rules[] = '# Block access to sensitive development/config files';
			$rules[] = '<FilesMatch "^(readme\.html|wp-config-sample\.php|license\.txt|composer\.json|composer\.lock|package\.json|package-lock\.json)$">';
			$rules[] = '    <IfModule mod_authz_core.c>';
			$rules[] = '        Require all denied';
			$rules[] = '    </IfModule>';
			$rules[] = '    <IfModule !mod_authz_core.c>';
			$rules[] = '        Order deny,allow';
			$rules[] = '        Deny from all';
			$rules[] = '    </IfModule>';
			$rules[] = '</FilesMatch>';
			$rules[] = '';
		}

		// Block XML-RPC access
		if ( ! empty( $this->options['disable_xmlrpc'] ) ) {
			$rules[] = '# Block XML-RPC access';
			$rules[] = '<Files xmlrpc.php>';
			$rules[] = '    Order Deny,Allow';
			$rules[] = '    Deny from all';
			$rules[] = '</Files>';
			$rules[] = '';
		}

		// Prevent Author Enumeration scans
		if ( ! empty( $this->options['htaccess_prevent_author_scan'] ) ) {
			$rules[] = '# Block Author Enumeration Scans';
			$rules[] = '<IfModule mod_rewrite.c>';
			$rules[] = '    RewriteEngine On';
			$rules[] = '    RewriteCond %{REQUEST_URI} ^/$';
			$rules[] = '    RewriteCond %{QUERY_STRING} ^author=([0-9]+) [NC]';
			$rules[] = '    RewriteRule ^ - [F]';
			$rules[] = '</IfModule>';
			$rules[] = '';
		}

		// Performance, Compression & Caching
		if ( ! empty( $this->options['htaccess_performance'] ) ) {
			$rules[] = '# Gzip Compression';
			$rules[] = '<IfModule mod_deflate.c>';
			$rules[] = '    AddOutputFilterByType DEFLATE text/plain';
			$rules[] = '    AddOutputFilterByType DEFLATE text/html';
			$rules[] = '    AddOutputFilterByType DEFLATE text/xml';
			$rules[] = '    AddOutputFilterByType DEFLATE text/css';
			$rules[] = '    AddOutputFilterByType DEFLATE text/vtt';
			$rules[] = '    AddOutputFilterByType DEFLATE text/x-component';
			$rules[] = '    AddOutputFilterByType DEFLATE application/xml';
			$rules[] = '    AddOutputFilterByType DEFLATE application/xhtml+xml';
			$rules[] = '    AddOutputFilterByType DEFLATE application/rss+xml';
			$rules[] = '    AddOutputFilterByType DEFLATE application/js';
			$rules[] = '    AddOutputFilterByType DEFLATE application/javascript';
			$rules[] = '    AddOutputFilterByType DEFLATE application/x-javascript';
			$rules[] = '    AddOutputFilterByType DEFLATE application/x-httpd-php';
			$rules[] = '    AddOutputFilterByType DEFLATE application/x-httpd-fastphp';
			$rules[] = '    AddOutputFilterByType DEFLATE application/atom+xml';
			$rules[] = '    AddOutputFilterByType DEFLATE application/json';
			$rules[] = '    AddOutputFilterByType DEFLATE application/ld+json';
			$rules[] = '    AddOutputFilterByType DEFLATE application/vnd.ms-fontobject';
			$rules[] = '    AddOutputFilterByType DEFLATE application/x-font-ttf';
			$rules[] = '    AddOutputFilterByType DEFLATE application/font-woff2';
			$rules[] = '    AddOutputFilterByType DEFLATE application/x-font-woff';
			$rules[] = '    AddOutputFilterByType DEFLATE application/x-web-app-manifest+json font/woff';
			$rules[] = '    AddOutputFilterByType DEFLATE font/woff';
			$rules[] = '    AddOutputFilterByType DEFLATE font/opentype';
			$rules[] = '    AddOutputFilterByType DEFLATE image/svg+xml';
			$rules[] = '    AddOutputFilterByType DEFLATE image/x-icon';
			$rules[] = '';
			$rules[] = '    # Exception: Images';
			$rules[] = '    SetEnvIfNoCase REQUEST_URI \.(?:gif|jpg|jpeg|png|svg)$ no-gzip dont-vary';
			$rules[] = '';
			$rules[] = '    # Drop problematic browsers';
			$rules[] = '    BrowserMatch ^Mozilla/4 gzip-only-text/html';
			$rules[] = '    BrowserMatch ^Mozilla/4\.0[678] no-gzip';
			$rules[] = '    BrowserMatch \bMSI[E] !no-gzip !gzip-only-text/html';
			$rules[] = '';
			$rules[] = '    # Make sure proxies don\'t deliver the wrong content';
			$rules[] = '    Header append Vary User-Agent env=!dont-vary';
			$rules[] = '</IfModule>';
			$rules[] = '';
			$rules[] = '# Leverage Browser Caching (mod_headers)';
			$rules[] = '<IfModule mod_headers.c>';
			$rules[] = '    <FilesMatch "\.(ico|pdf|flv|swf|js|mjs|css|gif|png|jpg|jpeg|txt|woff2|woff)$">';
			$rules[] = '        Header set Cache-Control "max-age=31536000, public"';
			$rules[] = '    </FilesMatch>';
			$rules[] = '    <FilesMatch "\.(js|mjs|css|xml|gz)$">';
			$rules[] = '        Header append Vary Accept-Encoding';
			$rules[] = '    </FilesMatch>';
			$rules[] = '    # Set Keep Alive Header';
			$rules[] = '    Header set Connection keep-alive';
			$rules[] = '    <FilesMatch "\.(js|mjs|css|xml|gz|html|woff|woff2|ttf)$">';
			$rules[] = '        Header append Vary: Accept-Encoding';
			$rules[] = '    </FilesMatch>';
			$rules[] = '</IfModule>';
			$rules[] = '';
			$rules[] = '# Leverage Browser Caching (mod_expires)';
			$rules[] = '<IfModule mod_expires.c>';
			$rules[] = '    ExpiresActive on';
			$rules[] = '    ExpiresDefault                                      "access plus 1 month"';
			$rules[] = '';
			$rules[] = '    # CSS';
			$rules[] = '    ExpiresByType text/css                              "access plus 1 year"';
			$rules[] = '';
			$rules[] = '    # Data interchange';
			$rules[] = '    ExpiresByType application/atom+xml                  "access plus 1 hour"';
			$rules[] = '    ExpiresByType application/rdf+xml                   "access plus 1 hour"';
			$rules[] = '    ExpiresByType application/rss+xml                   "access plus 1 hour"';
			$rules[] = '';
			$rules[] = '    ExpiresByType application/json                      "access plus 0 seconds"';
			$rules[] = '    ExpiresByType application/ld+json                   "access plus 0 seconds"';
			$rules[] = '    ExpiresByType application/schema+json               "access plus 0 seconds"';
			$rules[] = '    ExpiresByType application/vnd.geo+json              "access plus 0 seconds"';
			$rules[] = '    ExpiresByType application/xml                       "access plus 0 seconds"';
			$rules[] = '    ExpiresByType text/xml                              "access plus 0 seconds"';
			$rules[] = '';
			$rules[] = '    # Favicon and cursor images';
			$rules[] = '    ExpiresByType image/vnd.microsoft.icon              "access plus 1 week"';
			$rules[] = '    ExpiresByType image/x-icon                          "access plus 1 week"';
			$rules[] = '';
			$rules[] = '    # HTML - No Caching';
			$rules[] = '    ExpiresByType text/html                             "access plus 0 seconds"';
			$rules[] = '';
			$rules[] = '    # JavaScript';
			$rules[] = '    ExpiresByType application/javascript                "access plus 1 year"';
			$rules[] = '    ExpiresByType application/x-javascript              "access plus 1 year"';
			$rules[] = '    ExpiresByType text/javascript                       "access plus 1 year"';
			$rules[] = '';
			$rules[] = '    # Manifest files';
			$rules[] = '    ExpiresByType application/manifest+json             "access plus 1 week"';
			$rules[] = '    ExpiresByType application/x-web-app-manifest+json   "access plus 0 seconds"';
			$rules[] = '    ExpiresByType text/cache-manifest                   "access plus 0 seconds"';
			$rules[] = '';
			$rules[] = '    # Media files';
			$rules[] = '    ExpiresByType audio/ogg                             "access plus 1 year"';
			$rules[] = '    ExpiresByType image/bmp                             "access plus 1 year"';
			$rules[] = '    ExpiresByType image/gif                             "access plus 1 year"';
			$rules[] = '    ExpiresByType image/jpeg                            "access plus 1 year"';
			$rules[] = '    ExpiresByType image/png                             "access plus 1 year"';
			$rules[] = '    ExpiresByType image/svg+xml                         "access plus 1 year"';
			$rules[] = '    ExpiresByType image/webp                            "access plus 1 year"';
			$rules[] = '    ExpiresByType video/mp4                             "access plus 1 year"';
			$rules[] = '    ExpiresByType video/ogg                             "access plus 1 year"';
			$rules[] = '    ExpiresByType video/webm                            "access plus 1 year"';
			$rules[] = '';
			$rules[] = '    # Web fonts';
			$rules[] = '    # Embedded OpenType (EOT)';
			$rules[] = '    ExpiresByType application/vnd.ms-fontobject         "access plus 1 year"';
			$rules[] = '    ExpiresByType font/eot                              "access plus 1 year"';
			$rules[] = '';
			$rules[] = '    # OpenType';
			$rules[] = '    ExpiresByType font/opentype                         "access plus 1 year"';
			$rules[] = '';
			$rules[] = '    # TrueType';
			$rules[] = '    ExpiresByType application/x-font-ttf                "access plus 1 year"';
			$rules[] = '';
			$rules[] = '    # Web Open Font Format (WOFF) 1.0';
			$rules[] = '    ExpiresByType application/font-woff                 "access plus 1 year"';
			$rules[] = '    ExpiresByType application/x-font-woff               "access plus 1 year"';
			$rules[] = '    ExpiresByType font/woff                             "access plus 1 year"';
			$rules[] = '';
			$rules[] = '    # Web Open Font Format (WOFF) 2.0';
			$rules[] = '    ExpiresByType application/font-woff2                "access plus 1 year"';
			$rules[] = '';
			$rules[] = '    # Other';
			$rules[] = '    ExpiresByType text/x-cross-domain-policy            "access plus 1 week"';
			$rules[] = '</IfModule>';
			$rules[] = '';
		}

		// Remove any trailing empty line rules
		return array_filter( array_map( 'trim', $rules ) );
	}

	/**
	 * Generate uploads .htaccess rules array.
	 */
	public function generateUploadsRules(): array {
		$rules = [];

		if ( ! empty( $this->options['htaccess_uploads_prevent_php'] ) ) {
			$rules[] = '# Block PHP execution inside uploads directory';
			$rules[] = '<FilesMatch "\.(?i:php|php[34578]?|phtml)$">';
			$rules[] = '    <IfModule mod_authz_core.c>';
			$rules[] = '        Require all denied';
			$rules[] = '    </IfModule>';
			$rules[] = '    <IfModule !mod_authz_core.c>';
			$rules[] = '        Order deny,allow';
			$rules[] = '        Deny from all';
			$rules[] = '    </IfModule>';
			$rules[] = '</FilesMatch>';
		}

		return $rules;
	}

	/**
	 * Check if a path is writable using WP_Filesystem.
	 */
	private static function isPathWritable( string $path ): bool {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( ! WP_Filesystem() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
				return is_writable( $path );
			}
		}
		if ( $wp_filesystem ) {
			return $wp_filesystem->is_writable( $path );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		return is_writable( $path );
	}
}
