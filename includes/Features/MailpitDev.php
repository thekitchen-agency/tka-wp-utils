<?php

namespace TKA\WPUtils\Features;

/**
 * Configure WordPress to use Mailpit for SMTP in development environments.
 */
class MailpitDev
{
	public function hook(): void
	{
		// Only run if environment is development
		if (wp_get_environment_type() !== 'development') {
			return;
		}

		add_action('phpmailer_init', [$this, 'configureMailpit']);
	}

	/**
	 * Configure PHPMailer to use local Mailpit instance.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
	 */
	public function configureMailpit($phpmailer): void
	{
		$phpmailer->isSMTP();
		
		// If running in DDEV or Docker, host.docker.internal routes to the host machine. 
		// For native local environments, 127.0.0.1 is used.
		$host = (defined('DB_HOST') && strpos(DB_HOST, 'ddev') !== false) || isset($_SERVER['IS_DDEV_PROJECT']) 
			? 'host.docker.internal' 
			: '127.0.0.1';
			
		$phpmailer->Host       = $host;
		$phpmailer->SMTPAuth   = false;
		$phpmailer->Port       = 1025; // Default Mailpit SMTP port
		$phpmailer->SMTPSecure = false;
		$phpmailer->SMTPAutoTLS = false;
	}
}
