<?php

namespace TKA\WPUtils\Features;

/**
 * Handles Maintenance Mode functionality.
 */
class MaintenanceMode {

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
	 * Hook actions into WordPress.
	 */
	public function hook(): void {
		if ( empty( $this->options['maintenance_enabled'] ) ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'renderMaintenancePage' ], 1 );
	}

	/**
	 * Render the maintenance page for non-logged-in users.
	 */
	public function renderMaintenancePage(): void {
		// Bypass for administrators or anyone with manage_options capability
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Bypass for login page, admin area, or XML-RPC
		if ( is_admin() || in_array( $GLOBALS['pagenow'] ?? '', [ 'wp-login.php', 'wp-register.php' ], true ) ) {
			return;
		}

		// Set HTTP 503 Service Unavailable header
		$protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
		header( "$protocol 503 Service Unavailable", true, 503 );
		header( 'Retry-After: 3600' ); // Recommend retry in 1 hour

		$title = ! empty( $this->options['maintenance_title'] ) ? $this->options['maintenance_title'] : __( 'Under Maintenance', 'tka-wp-utils' );
		$message = ! empty( $this->options['maintenance_message'] ) ? $this->options['maintenance_message'] : __( 'Our website is currently undergoing scheduled maintenance. We will be back shortly. Thank you for your patience!', 'tka-wp-utils' );
		$logo = $this->options['maintenance_logo'] ?? '';
		$bg = $this->options['maintenance_background'] ?? '';

		// Output premium HTML maintenance page
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo esc_html( $title ); ?></title>
			<style>
				:root {
					--bg-color: #0b0f19;
					--card-bg: rgba(255, 255, 255, 0.03);
					--card-border: rgba(255, 255, 255, 0.08);
					--text-primary: #f8fafc;
					--text-secondary: #94a3b8;
					--primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
					--primary-color: #6366f1;
				}

				* {
					box-sizing: border-box;
					margin: 0;
					padding: 0;
				}

				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
					background-color: var(--bg-color);
					color: var(--text-primary);
					min-height: 100vh;
					display: flex;
					align-items: center;
					justify-content: center;
					padding: 24px;
					overflow-x: hidden;
					position: relative;
					<?php if ( ! empty( $bg ) ): ?>
						background-image: linear-gradient(rgba(11, 15, 25, 0.85), rgba(11, 15, 25, 0.85)), url('<?php echo esc_url( $bg ); ?>');
						background-size: cover;
						background-position: center;
						background-repeat: no-repeat;
					<?php endif; ?>
				}

				/* Background ambient glows (only if no custom bg image is set) */
				<?php if ( empty( $bg ) ): ?>
				body::before, body::after {
					content: '';
					position: absolute;
					width: 400px;
					height: 400px;
					border-radius: 50%;
					filter: blur(120px);
					opacity: 0.15;
					z-index: 0;
				}
				body::before {
					background: #6366f1;
					top: 10%;
					left: 10%;
				}
				body::after {
					background: #a855f7;
					bottom: 10%;
					right: 10%;
				}
				<?php endif; ?>

				.container {
					width: 100%;
					max-width: 580px;
					background: var(--card-bg);
					border: 1px solid var(--card-border);
					backdrop-filter: blur(20px);
					-webkit-backdrop-filter: blur(20px);
					border-radius: 24px;
					padding: 48px 40px;
					text-align: center;
					box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
					z-index: 1;
					animation: fadeIn 0.8s ease-out;
				}

				.logo-container {
					margin-bottom: 32px;
				}

				.logo-container img {
					max-width: 180px;
					max-height: 80px;
					object-fit: contain;
				}

				.logo-fallback {
					width: 64px;
					height: 64px;
					background: var(--primary-gradient);
					border-radius: 16px;
					display: inline-flex;
					align-items: center;
					justify-content: center;
					margin: 0 auto 24px auto;
					box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
				}

				.logo-fallback svg {
					width: 32px;
					height: 32px;
					color: #fff;
				}

				h1 {
					font-size: 32px;
					font-weight: 700;
					letter-spacing: -0.5px;
					margin-bottom: 16px;
					background: linear-gradient(to right, #ffffff, #cbd5e1);
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;
				}

				.divider {
					height: 4px;
					width: 60px;
					background: var(--primary-gradient);
					margin: 20px auto;
					border-radius: 2px;
				}

				p.message {
					font-size: 16px;
					line-height: 1.6;
					color: var(--text-secondary);
					margin-bottom: 32px;
					white-space: pre-line;
				}

				.status-badge {
					display: inline-flex;
					align-items: center;
					gap: 8px;
					background: rgba(245, 158, 11, 0.1);
					border: 1px solid rgba(245, 158, 11, 0.2);
					color: #f59e0b;
					padding: 6px 16px;
					border-radius: 9999px;
					font-size: 13px;
					font-weight: 600;
					text-transform: uppercase;
					letter-spacing: 0.5px;
				}

				.status-dot {
					width: 8px;
					height: 8px;
					background-color: #f59e0b;
					border-radius: 50%;
					animation: pulse 1.5s infinite;
				}

				@keyframes fadeIn {
					from {
						opacity: 0;
						transform: translateY(20px);
					}
					to {
						opacity: 1;
						transform: translateY(0);
					}
				}

				@keyframes pulse {
					0% {
						transform: scale(0.9);
						opacity: 0.6;
					}
					50% {
						transform: scale(1.2);
						opacity: 1;
					}
					100% {
						transform: scale(0.9);
						opacity: 0.6;
					}
				}

				@media (max-width: 480px) {
					.container {
						padding: 32px 24px;
					}
					h1 {
						font-size: 26px;
					}
				}
			</style>
		</head>
		<body>
			<div class="container">
				<?php if ( ! empty( $logo ) ): ?>
					<div class="logo-container">
						<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					</div>
				<?php else: ?>
					<div class="logo-fallback">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A1.79 1.79 0 0020 18.25l-5.83-5.83m-3.75 3.75a2.22 2.22 0 11-3.18-3.18 2.22 2.22 0 013.18 3.18zm4.5-4.5a2.22 2.22 0 10-3.18-3.18 2.22 2.22 0 003.18 3.18zm0 0L21 3m-3.75 3.75L21 3m-3.75 3.75H18c-.6 0-1.15-.22-1.55-.57L10.5 12" />
						</svg>
					</div>
				<?php endif; ?>

				<div style="margin-bottom: 24px;">
					<span class="status-badge">
						<span class="status-dot"></span>
						<?php esc_html_e( 'Maintenance Mode', 'tka-wp-utils' ); ?>
					</span>
				</div>

				<h1><?php echo esc_html( $title ); ?></h1>
				<div class="divider"></div>
				<p class="message"><?php echo esc_html( $message ); ?></p>
			</div>
		</body>
		</html>
		<?php
		if ( ! defined( 'TKA_WP_UTILS_TESTING' ) ) {
			exit;
		}
	}
}
