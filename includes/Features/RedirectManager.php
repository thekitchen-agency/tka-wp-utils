<?php

namespace TKA\WPUtils\Features;

/**
 * Handles fast 301 URL redirects at the parse request level.
 */
class RedirectManager
{
	/**
	 * Register hooks.
	 */
	public function hook(): void
	{
		// Hook very early, before queries and template loading
		add_action('parse_request', [$this, 'processRedirects'], 1);
	}

	/**
	 * Process redirects based on the requested URL.
	 */
	public function processRedirects(): void
	{
		// Only run on front-end, skip admin area entirely for safety
		if (is_admin()) {
			return;
		}

		$redirects = get_option('tka_wp_utils_redirects', []);
		if (empty($redirects)) {
			return;
		}

		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		if (empty($request_uri)) {
			return;
		}

		// Normalize requested URI by removing trailing slash for comparison
		$path = parse_url($request_uri, PHP_URL_PATH);
		$normalized_request = untrailingslashit($path);

		foreach ($redirects as $rule) {
			if (empty($rule['old_url']) || empty($rule['new_url'])) {
				continue;
			}

			$old_url = trim($rule['old_url']);
			$new_url = trim($rule['new_url']);

			// Strip domain from old_url if accidentally provided
			if (str_starts_with($old_url, 'http')) {
				$old_url = parse_url($old_url, PHP_URL_PATH);
			}

			$is_wildcard = str_ends_with($old_url, '*');
			
			if ($is_wildcard) {
				// Remove the asterisk and trailing slash
				$base_old_url = untrailingslashit(substr($old_url, 0, -1));
				
				// Check if request path starts with the base old URL
				if (str_starts_with($normalized_request, $base_old_url)) {
					// Check if they want to append the remaining path, or just static redirect
					// If the new URL ends in *, replace * with the remainder of the path
					if (str_ends_with($new_url, '*')) {
						$remainder = substr($path, strlen($base_old_url));
						$destination = untrailingslashit(substr($new_url, 0, -1)) . $remainder;
					} else {
						$destination = $new_url;
					}

					wp_redirect($destination, 301);
					exit;
				}
			} else {
				// Exact match
				$normalized_old = untrailingslashit($old_url);
				if ($normalized_request === $normalized_old) {
					wp_redirect($new_url, 301);
					exit;
				}
			}
		}
	}
}
