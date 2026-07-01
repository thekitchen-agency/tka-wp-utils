<?php

namespace TKA\WPUtils\Licensing;

/**
 * TKA Systems WordPress Licensing Core
 */
class Licensing
{
	private static string $option_key = 'tka_site_utilities_license_status';
	private static string $transient_key = 'tka_site_utilities_license_check_transient';
	private static string $server_url = 'https://plugins.thekitchen.agency';

	public static function init(): void
	{
		add_action('admin_init', [self::class, 'run_daily_heartbeat']);
	}

	/**
	 * Retrieve local license status (Checking transient first to prevent remote call bottlenecks).
	 */
	public static function isActive(): bool
	{
		$status = get_transient(self::$transient_key);

		if ($status !== false) {
			return $status === 'active';
		}

		// Cache expired; load raw saved options
		$saved = get_option(self::$option_key, []);
		if (empty($saved['license_key']) || empty($saved['status'])) {
			return false;
		}

		// If active locally, rebuild transient to prevent blocking checks during thread execution
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
		if (get_transient(self::$transient_key) !== false) {
			return; // Throttle: Check only once every 24 hours
		}

		$saved = get_option(self::$option_key, []);
		if (empty($saved['license_key'])) {
			return;
		}

		$manager = new LicenseManager(self::$server_url, $saved['license_key'], 'tka-site-utilities');
		$result = $manager->verify();

		if (isset($result['success']) && $result['success'] && isset($result['status']) && $result['status'] === 'active') {
			// Heartbeat validated successfully
			$saved['status'] = 'active';
			$saved['last_check'] = time();
			$saved['grace_active'] = false;
			update_option(self::$option_key, $saved);

			set_transient(self::$transient_key, 'active', 24 * HOUR_IN_SECONDS);
		} else {
			// Check for temporary server offline
			if (isset($result['status']) && $result['status'] === 'network_error') {
				self::handle_grace_period($saved);
				return;
			}

			// Revoked, suspended or expired! Lock features locally
			$saved['status'] = 'suspended';
			$saved['error_message'] = $result['error'] ?? 'Unregistered domain seat.';
			update_option(self::$option_key, $saved);

			set_transient(self::$transient_key, 'suspended', 24 * HOUR_IN_SECONDS);
		}
	}

	/**
	 * Grace Period Handler for network fault tolerance.
	 */
	private static function handle_grace_period(array &$saved): void
	{
		$now = time();
		if (empty($saved['grace_started_at'])) {
			$saved['grace_started_at'] = $now;
			$saved['grace_active'] = true;
		}

		$elapsed = $now - $saved['grace_started_at'];
		$grace_threshold = 5 * DAY_IN_SECONDS; // 5-day grace threshold

		if ($elapsed < $grace_threshold) {
			// Server offline but within grace period; preserve plugin availability
			set_transient(self::$transient_key, 'active', 12 * HOUR_IN_SECONDS); // recheck sooner
			update_option(self::$option_key, $saved);
		} else {
			// Grace period expired; deactivate license features
			$saved['status'] = 'suspended';
			$saved['error_message'] = 'Licensing authentication timeout. Server unreachable.';
			update_option(self::$option_key, $saved);
			set_transient(self::$transient_key, 'suspended', 12 * HOUR_IN_SECONDS);
		}
	}
}
