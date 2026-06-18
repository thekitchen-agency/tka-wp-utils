<?php

namespace TKA\WPUtils\Features;

/**
 * Configure WordPress to use Mailpit for SMTP in development environments,
 * or custom SMTP configuration as defined in plugin settings.
 */
class SmtpManager
{
	/**
	 * @var array Plugin settings
	 */
	private array $options;

	public function __construct(array $options)
	{
		$this->options = $options;
	}

	public function hook(): void
	{
		if (empty($this->options['smtp_enabled'])) {
			return;
		}

		add_action('phpmailer_init', [$this, 'configureSmtp'], 999);
	}

	/**
	 * Configure PHPMailer.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
	 */
	public function configureSmtp($phpmailer): void
	{
		$phpmailer->isSMTP();

		// Check if we are in development and "Local Mailpit for Development" is enabled
		$is_dev_mode = wp_get_environment_type() === 'development' || (defined('WP_ENV') && WP_ENV === 'development');
		if ($is_dev_mode && !empty($this->options['smtp_mailpit_dev'])) {
			$host = (defined('DB_HOST') && strpos(DB_HOST, 'ddev') !== false) || isset($_SERVER['IS_DDEV_PROJECT']) 
				? 'host.docker.internal' 
				: '127.0.0.1';
				
			$phpmailer->Host       = $host;
			$phpmailer->SMTPAuth   = false;
			$phpmailer->Port       = 1025;
			$phpmailer->SMTPSecure = false;
			$phpmailer->SMTPAutoTLS = false;
			return;
		}

		// Otherwise, use custom defined SMTP settings
		$phpmailer->Host = $this->options['smtp_host'] ?? '';
		$phpmailer->Port = absint($this->options['smtp_port'] ?? 587);

		$username = $this->options['smtp_username'] ?? '';
		$password = $this->options['smtp_password'] ?? '';
		
		if (!empty($username) || !empty($password)) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $username;
			$phpmailer->Password = $password;
		} else {
			$phpmailer->SMTPAuth = false;
		}

		$encryption = $this->options['smtp_encryption'] ?? 'none';
		if ($encryption === 'ssl') {
			$phpmailer->SMTPSecure = 'ssl';
		} elseif ($encryption === 'tls') {
			$phpmailer->SMTPSecure = 'tls';
		} else {
			$phpmailer->SMTPSecure = false;
			$phpmailer->SMTPAutoTLS = false;
		}
	}
}
