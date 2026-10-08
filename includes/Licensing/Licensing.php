<?php

namespace TKA\WPUtils\Licensing;

/**
 * TKA Systems WordPress Licensing Core
 */
class Licensing
{
	private static string $option_key = 'tka_site_utilities_license_status';
	private static string $transient_key = 'tka_site_utilities_license_check_transient';
	private static string $whitelist_transient_key = 'tka_site_utilities_whitelist_transient';
	private static string $server_url = 'https://plugins.thekitchen.agency';

	public static function init(): void
	{
		add_action('admin_init', [self::class, 'run_daily_heartbeat']);
		add_filter('pre_set_site_transient_update_plugins', [self::class, 'check_update']);
		add_filter('plugins_api', [self::class, 'plugin_info'], 20, 3);
	}

	/**
	 * Retrieve license server base URL, allowing override via constant or environment.
	 */
	public static function getServerUrl(): string
	{
		if (defined('TKA_LICENSE_SERVER_URL') && !empty(constant('TKA_LICENSE_SERVER_URL'))) {
			return rtrim((string) constant('TKA_LICENSE_SERVER_URL'), '/');
		}

		if (function_exists('env')) {
			$val = env('TKA_LICENSE_SERVER_URL');
			if (!empty($val)) {
				return rtrim((string) $val, '/');
			}
		}

		$getenv = getenv('TKA_LICENSE_SERVER_URL');
		if (!empty($getenv)) {
			return rtrim((string) $getenv, '/');
		}

		if (!empty($_ENV['TKA_LICENSE_SERVER_URL'])) {
			return rtrim((string) $_ENV['TKA_LICENSE_SERVER_URL'], '/');
		}

		if (!empty($_SERVER['TKA_LICENSE_SERVER_URL'])) {
			return rtrim((string) $_SERVER['TKA_LICENSE_SERVER_URL'], '/');
		}

		return self::$server_url;
	}

	/**
	 * Retrieve license key defined via .env or PHP constant, if present.
	 */
	public static function getEnvLicenseKey(): ?string
	{
		// 1. PHP Constants
		if (defined('TKA_SITE_UTILITIES_LICENSE_KEY') && !empty(constant('TKA_SITE_UTILITIES_LICENSE_KEY'))) {
			return trim((string) constant('TKA_SITE_UTILITIES_LICENSE_KEY'));
		}
		if (defined('TKA_LICENSE_KEY') && !empty(constant('TKA_LICENSE_KEY'))) {
			return trim((string) constant('TKA_LICENSE_KEY'));
		}

		// 2. env() helper function (Roots Bedrock / Dotenv)
		if (function_exists('env')) {
			$val = env('TKA_SITE_UTILITIES_LICENSE_KEY');
			if (!empty($val)) {
				return trim((string) $val);
			}
			$val = env('TKA_LICENSE_KEY');
			if (!empty($val)) {
				return trim((string) $val);
			}
		}

		// 3. getenv()
		$getenv = getenv('TKA_SITE_UTILITIES_LICENSE_KEY');
		if (!empty($getenv)) {
			return trim((string) $getenv);
		}
		$getenv = getenv('TKA_LICENSE_KEY');
		if (!empty($getenv)) {
			return trim((string) $getenv);
		}

		// 4. $_ENV
		if (!empty($_ENV['TKA_SITE_UTILITIES_LICENSE_KEY'])) {
			return trim((string) $_ENV['TKA_SITE_UTILITIES_LICENSE_KEY']);
		}
		if (!empty($_ENV['TKA_LICENSE_KEY'])) {
			return trim((string) $_ENV['TKA_LICENSE_KEY']);
		}

		// 5. $_SERVER
		if (!empty($_SERVER['TKA_SITE_UTILITIES_LICENSE_KEY'])) {
			return trim((string) $_SERVER['TKA_SITE_UTILITIES_LICENSE_KEY']);
		}
		if (!empty($_SERVER['TKA_LICENSE_KEY'])) {
			return trim((string) $_SERVER['TKA_LICENSE_KEY']);
		}

		return null;
	}

	/**
	 * Check if the active license key is sourced from an environment variable / .env.
	 */
	public static function isLicenseKeyFromEnv(): bool
	{
		return self::getEnvLicenseKey() !== null;
	}

	/**
	 * Retrieve active license key (giving precedence to .env / constant over database).
	 */
	public static function getActiveLicenseKey(): string
	{
		$env_key = self::getEnvLicenseKey();
		if ($env_key !== null) {
			return $env_key;
		}

		$saved = get_option(self::$option_key, []);
		return !empty($saved['license_key']) ? trim((string) $saved['license_key']) : '';
	}

	/**
	 * Check if the hosted domain is verified as whitelisted on the license server.
	 */
	public static function isDomainWhitelisted(): bool
	{
		$saved = get_option(self::$option_key, []);
		return !empty($saved['is_whitelisted']);
	}

	/**
	 * Check if running on a local development environment.
	 */
	public static function isLocalEnvironment(): bool
	{
		$domain = LicenseManager::determineDomain();
		if (empty($domain)) {
			return false;
		}

		$local_suffixes = ['.local', '.localhost', '.ddev.site', '.test', 'localhost', '127.0.0.1', '::1'];
		foreach ($local_suffixes as $suffix) {
			if ($suffix === $domain || (strpos($suffix, '.') === 0 && str_ends_with($domain, $suffix))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if the hosted domain is on the whitelist of the license server.
	 */
	public static function checkDomainWhitelist(bool $force = false): array
	{
		if (!$force) {
			$cached = get_transient(self::$whitelist_transient_key);
			if ($cached !== false) {
				return [
					'success'        => self::isDomainWhitelisted(),
					'status'         => self::isDomainWhitelisted() ? 'active' : 'unregistered',
					'is_whitelisted' => self::isDomainWhitelisted(),
					'cached'         => true,
				];
			}
		}

		$manager = new LicenseManager(self::getServerUrl(), '', 'tka-site-utilities');
		$result  = $manager->checkWhitelist();

		$saved = get_option(self::$option_key, []);
		$saved['last_whitelist_check'] = time();

		$is_whitelisted = LicenseManager::isWhitelistedResponse($result);

		if ($is_whitelisted) {
			$saved['is_whitelisted'] = true;
			$saved['status']         = 'active';
			$saved['last_check']     = time();
			$saved['grace_active']   = false;
			unset($saved['error_message']);
			update_option(self::$option_key, $saved);

			set_transient(self::$transient_key, 'active', 24 * HOUR_IN_SECONDS);
			set_transient(self::$whitelist_transient_key, 'whitelisted', 24 * HOUR_IN_SECONDS);
		} else {
			$saved['is_whitelisted'] = false;
			if (empty($saved['license_key']) && !self::isLicenseKeyFromEnv()) {
				$saved['status'] = 'unregistered';
			}
			update_option(self::$option_key, $saved);

			set_transient(self::$whitelist_transient_key, 'not_whitelisted', 12 * HOUR_IN_SECONDS);
		}

		return $result;
	}

	/**
	 * Validate a license key with the license server and synchronize status.
	 */
	public static function validateAndSync(?string $key = null, bool $is_env = false): array
	{
		if ($key === null) {
			$key    = self::getActiveLicenseKey();
			$is_env = self::isLicenseKeyFromEnv();
		}

		if (empty($key)) {
			return self::checkDomainWhitelist(true);
		}

		$manager = new LicenseManager(self::getServerUrl(), $key, 'tka-site-utilities');
		$result  = $manager->verify();

		// If domain seat is not yet registered under this valid key, attempt activation
		if (isset($result['status']) && $result['status'] === 'unregistered') {
			$result = $manager->activate();
		}

		$saved = get_option(self::$option_key, []);
		$saved['license_key']   = $key;
		$saved['validated_key'] = $key;
		$saved['is_env']        = $is_env;
		$saved['last_check']    = time();

		if (!empty($result['success']) && isset($result['status']) && $result['status'] === 'active') {
			$saved['status']         = 'active';
			$saved['is_whitelisted'] = LicenseManager::isWhitelistedResponse($result);
			$saved['grace_active']   = false;
			unset($saved['error_message']);
			update_option(self::$option_key, $saved);

			set_transient(self::$transient_key, 'active', 24 * HOUR_IN_SECONDS);
		} else {
			if (isset($result['status']) && $result['status'] === 'network_error') {
				self::handle_grace_period($saved);
			} else {
				$saved['status']         = 'suspended';
				$saved['error_message']  = $result['error'] ?? __('Invalid or inactive license key.', 'tka-site-utilities');
				$saved['is_whitelisted'] = false;
				update_option(self::$option_key, $saved);

				set_transient(self::$transient_key, 'suspended', 24 * HOUR_IN_SECONDS);
			}
		}

		return $result;
	}

	/**
	 * Retrieve local license status.
	 */
	public static function isActive(): bool
	{
		$env_key = self::getEnvLicenseKey();

		// a) If license key is entered via .env file, it MUST be validated with the server
		if (!empty($env_key)) {
			$saved = get_option(self::$option_key, []);
			if (empty($saved['validated_key']) || $saved['validated_key'] !== $env_key) {
				self::validateAndSync($env_key, true);
				$saved = get_option(self::$option_key, []);
			}

			$status = get_transient(self::$transient_key);
			if ($status !== false) {
				return $status === 'active';
			}

			return !empty($saved['status']) && $saved['status'] === 'active';
		}

		// b) If domain hosted is on the whitelist of the license server
		$saved = get_option(self::$option_key, []);
		if (!empty($saved['is_whitelisted']) && !empty($saved['status']) && $saved['status'] === 'active') {
			return true;
		}

		// If no key entered and whitelist not checked yet, check domain whitelist
		if (empty($saved['license_key']) && empty($saved['last_whitelist_check'])) {
			self::checkDomainWhitelist();
			$saved = get_option(self::$option_key, []);
			if (!empty($saved['is_whitelisted']) && !empty($saved['status']) && $saved['status'] === 'active') {
				return true;
			}
		}

		// If no key configured and not whitelisted, allow local development environment fallback
		if (empty($saved['license_key']) && self::isLocalEnvironment()) {
			return true;
		}

		$status = get_transient(self::$transient_key);
		if ($status !== false) {
			return $status === 'active';
		}

		// Cache expired; load raw saved options
		if (empty($saved['license_key']) || empty($saved['status'])) {
			return false;
		}

		if ($saved['status'] === 'active') {
			set_transient(self::$transient_key, 'active', 24 * HOUR_IN_SECONDS);
			return true;
		}

		return false;
	}

	/**
	 * Heartbeat validation cron hook.
	 */
	public static function run_daily_heartbeat(): void
	{
		// Throttle: check only when transient expires
		if (get_transient(self::$transient_key) !== false) {
			return;
		}

		$env_key = self::getEnvLicenseKey();
		if (!empty($env_key)) {
			self::validateAndSync($env_key, true);
			return;
		}

		$saved = get_option(self::$option_key, []);

		// If previously whitelisted, re-verify domain whitelist
		if (!empty($saved['is_whitelisted'])) {
			self::checkDomainWhitelist(true);
			return;
		}

		if (empty($saved['license_key'])) {
			if (!self::isLocalEnvironment()) {
				self::checkDomainWhitelist(true);
			}
			return;
		}

		self::validateAndSync($saved['license_key'], false);
	}

	/**
	 * Grace Period Handler for network fault tolerance.
	 */
	private static function handle_grace_period(array &$saved): void
	{
		$now = time();
		if (empty($saved['grace_started_at'])) {
			$saved['grace_started_at'] = $now;
			$saved['grace_active']     = true;
		}

		$elapsed = $now - $saved['grace_started_at'];
		$grace_threshold = 5 * DAY_IN_SECONDS; // 5-day grace threshold

		if ($elapsed < $grace_threshold) {
			// Server offline but within grace period; preserve plugin availability
			set_transient(self::$transient_key, 'active', 12 * HOUR_IN_SECONDS); // recheck sooner
			update_option(self::$option_key, $saved);
		} else {
			// Grace period expired; deactivate license features
			$saved['status']        = 'suspended';
			$saved['error_message'] = 'Licensing authentication timeout. Server unreachable.';
			update_option(self::$option_key, $saved);
			set_transient(self::$transient_key, 'suspended', 12 * HOUR_IN_SECONDS);
		}
	}

	/**
	 * Retrieve plugin basename file identifier.
	 */
	public static function getPluginFile(): string
	{
		if (defined('TKA_SITE_UTILITIES_PATH')) {
			return plugin_basename(TKA_SITE_UTILITIES_PATH . 'tka-site-utilities.php');
		}
		return 'tka-site-utilities/tka-site-utilities.php';
	}

	/**
	 * Check for plugin updates.
	 */
	public static function check_update($transient)
	{
		if (empty($transient) || !is_object($transient) || empty($transient->checked)) {
			return $transient;
		}

		$key            = self::getActiveLicenseKey();
		$saved          = get_option(self::$option_key, []);
		$is_whitelisted = !empty($saved['is_whitelisted']);

		if (empty($key) && !$is_whitelisted) {
			return $transient;
		}

		$plugin_file     = self::getPluginFile();
		$current_version = $transient->checked[$plugin_file] ?? (defined('TKA_SITE_UTILITIES_VERSION') ? TKA_SITE_UTILITIES_VERSION : '0.0.0');

		$response = wp_remote_post(self::getServerUrl() . '/api/license/update-check', [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body' => json_encode([
				'license_key' => $key,
				'domain'      => LicenseManager::determineDomain(),
				'product_ref' => 'tka-site-utilities',
				'version'     => $current_version,
			]),
			'timeout' => 15,
		]);

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return $transient;
		}

		$data = json_decode(wp_remote_retrieve_body($response));

		if ($data && isset($data->success) && $data->success && !empty($data->new_version)) {
			$obj = new \stdClass();
			$obj->id           = $plugin_file;
			$obj->slug         = $data->slug ?? 'tka-site-utilities';
			$obj->plugin       = $plugin_file;
			$obj->new_version  = $data->new_version;
			$obj->url          = $data->url ?? 'https://github.com/thekitchen-agency/tka-site-utilities';
			$obj->package      = $data->package ?? '';
			$obj->icons        = (array) ($data->icons ?? []);
			$obj->banners      = (array) ($data->banners ?? []);
			$obj->banners_rtl  = (array) ($data->banners_rtl ?? []);
			$obj->requires     = $data->requires ?? '6.2';
			$obj->tested       = $data->tested ?? '6.7';
			$obj->requires_php = $data->requires_php ?? '8.3';

			if (!empty($data->package) && version_compare($current_version, $data->new_version, '<')) {
				$transient->response[$plugin_file] = $obj;
				if (isset($transient->no_update[$plugin_file])) {
					unset($transient->no_update[$plugin_file]);
				}
			} else {
				$transient->no_update[$plugin_file] = $obj;
				if (isset($transient->response[$plugin_file])) {
					unset($transient->response[$plugin_file]);
				}
			}
		}

		return $transient;
	}

	/**
	 * Force an immediate check for updates from the licensing server and refresh WordPress transients.
	 */
	public static function forceCheckUpdate(): array
	{
		delete_site_transient('update_plugins');

		$key            = self::getActiveLicenseKey();
		$saved          = get_option(self::$option_key, []);
		$is_whitelisted = !empty($saved['is_whitelisted']);

		if (empty($key) && !$is_whitelisted) {
			return [
				'success' => false,
				'error'   => __('No license key entered and domain is not whitelisted.', 'tka-site-utilities'),
			];
		}

		$plugin_file     = self::getPluginFile();
		$current_version = defined('TKA_SITE_UTILITIES_VERSION') ? TKA_SITE_UTILITIES_VERSION : '0.0.0';

		$response = wp_remote_post(self::getServerUrl() . '/api/license/update-check', [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body' => json_encode([
				'license_key' => $key,
				'domain'      => LicenseManager::determineDomain(),
				'product_ref' => 'tka-site-utilities',
				'version'     => $current_version,
			]),
			'timeout' => 15,
		]);

		if (is_wp_error($response)) {
			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if (!is_array($data)) {
			return [
				'success' => false,
				'error'   => __('Invalid response from licensing server.', 'tka-site-utilities'),
			];
		}

		if (function_exists('wp_update_plugins')) {
			wp_update_plugins();
		}

		$data['current_version'] = $current_version;
		$data['has_update']      = !empty($data['new_version']) && version_compare($current_version, $data['new_version'], '<');

		return $data;
	}

	/**
	 * Retrieve plugin information for the update details modal.
	 */
	public static function plugin_info($res, $action, $args)
	{
		if ($action !== 'plugin_information' || !is_object($args) || $args->slug !== 'tka-site-utilities') {
			return $res;
		}

		$key            = self::getActiveLicenseKey();
		$saved          = get_option(self::$option_key, []);
		$is_whitelisted = !empty($saved['is_whitelisted']);

		if (empty($key) && !$is_whitelisted) {
			return $res;
		}

		$current_version = defined('TKA_SITE_UTILITIES_VERSION') ? TKA_SITE_UTILITIES_VERSION : '0.0.0';

		$response = wp_remote_post(self::getServerUrl() . '/api/license/update-check', [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body' => json_encode([
				'license_key' => $key,
				'domain'      => LicenseManager::determineDomain(),
				'product_ref' => 'tka-site-utilities',
				'version'     => $current_version,
			]),
			'timeout' => 15,
		]);

		if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if ($data && isset($data->success) && $data->success) {
				$res = new \stdClass();
				$res->name           = $data->name ?? 'TKA Site Utilities';
				$res->slug           = $data->slug ?? 'tka-site-utilities';
				$res->version        = $data->new_version ?? '0.0.0';
				$res->package        = $data->package ?? '';
				$res->download_link  = $data->package ?? ''; // Required by WordPress core modal!
				$res->trunk          = $data->package ?? '';
				$res->author         = $data->author ?? '<a href="https://thekitchen.agency">TKA</a>';
				$res->author_profile = 'https://thekitchen.agency';
				$res->homepage       = $data->url ?? 'https://github.com/thekitchen-agency/tka-site-utilities';
				$res->sections       = (array) ($data->sections ?? [
					'description' => 'A collection of utility tools to customize and secure your WordPress experience.',
					'changelog'   => 'No changelog provided.',
				]);
				$res->requires       = $data->requires ?? '6.2';
				$res->tested         = $data->tested ?? '6.7';
				$res->requires_php   = $data->requires_php ?? '8.3';
				return $res;
			}
		}

		return $res;
	}
}
