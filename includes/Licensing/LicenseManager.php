<?php

namespace TKA\WPUtils\Licensing;

/**
 * TKA License Manager Client
 * Handles communication with the licensing server.
 */
class LicenseManager
{
	private string $serverUrl;
	private string $licenseKey;
	private string $productRef;
	private string $domain;

	public function __construct(string $serverUrl, string $licenseKey, string $productRef)
	{
		$this->serverUrl = rtrim($serverUrl, '/');
		$this->licenseKey = trim($licenseKey);
		$this->productRef = trim($productRef);

		// Normalize domain
		$host = parse_url($_SERVER['HTTP_HOST'] ?? 'localhost', PHP_URL_HOST);
		$this->domain = $host ? $host : ($_SERVER['HTTP_HOST'] ?? 'localhost');
	}

	/**
	 * Activate domain seat.
	 */
	public function activate(): array
	{
		return $this->sendRequest('/api/license/activate');
	}

	/**
	 * Check current license validity (Heartbeat).
	 */
	public function verify(): array
	{
		return $this->sendRequest('/api/license/verify');
	}

	/**
	 * Deactivate domain seat.
	 */
	public function deactivate(): array
	{
		return $this->sendRequest('/api/license/deactivate');
	}

	/**
	 * Internal cURL dispatcher.
	 */
	private function sendRequest(string $endpoint): array
	{
		$url = $this->serverUrl . $endpoint;
		$payload = json_encode([
			'license_key' => $this->licenseKey,
			'domain' => $this->domain,
			'product_ref' => $this->productRef
		]);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5-second strict threshold
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json'
		]);

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false) {
			return [
				'success' => false,
				'status' => 'network_error',
				'error' => 'Licensing server unreachable.'
			];
		}

		$data = json_decode($response, true);
		$data['http_code'] = $httpCode;
		return $data;
	}
}
