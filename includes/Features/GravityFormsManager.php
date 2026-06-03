<?php

namespace TKA\WPUtils\Features;

/**
 * Handles Gravity Forms customizations, including CSS suppression, submit button conversions, and loading text feedback.
 */
class GravityFormsManager {

	/**
	 * Active plugin options.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Constructor.
	 *
	 * @param array $options Active options.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	/**
	 * Register actions and filters.
	 */
	public function hook(): void {
		// 1. Disable Gravity Forms Default CSS completely
		if ( ! empty( $this->options['gf_disable_css'] ) ) {
			add_filter( 'gform_disable_css', '__return_true' );
		}

		// 2. Change Submit Button from Input to Button
		if ( ! empty( $this->options['gf_submit_button_to_button'] ) ) {
			add_filter( 'gform_submit_button', [ $this, 'convertSubmitButton' ], 10, 2 );
		}

		// 3. Change Button Text after Click
		if ( ! empty( $this->options['gf_submit_button_text_change'] ) ) {
			add_action( 'wp_footer', [ $this, 'addSubmitButtonLoadingScript' ], 999 );
		}
	}

	/**
	 * Convert standard input submit buttons into modern <button> elements.
	 *
	 * @param string $button Old button HTML.
	 * @param array  $form   Gravity Form object.
	 * @return string Modified button HTML.
	 */
	public function convertSubmitButton( string $button, array $form ): string {
		// Match the input element
		if ( preg_match( '/<input([^>]+)>/i', $button, $matches ) ) {
			$attrs_str = $matches[1];
			$text = '';

			// Extract the button text from the value attribute
			if ( preg_match( '/value=[\'"]([^\'"]*)[\'"]/i', $attrs_str, $text_match ) ) {
				$text = $text_match[1];
				// Strip the value attribute from attributes string
				$attrs_str = preg_replace( '/value=[\'"]([^\'"]*)[\'"]/i', '', $attrs_str );
			}

			// Strip any existing type attribute to prevent duplicates or conflicts
			$attrs_str = preg_replace( '/type=[\'"]([^\'"]*)[\'"]/i', '', $attrs_str );

			// Strip self-closing slashes if any
			$attrs_str = rtrim( $attrs_str, '/ ' );

			// Rebuild attributes cleanly
			$attrs_str = trim( preg_replace( '/\s+/', ' ', $attrs_str ) );

			// Return a semantic HTML5 button element
			return sprintf( '<button type="submit" %s>%s</button>', $attrs_str, esc_html( $text ) );
		}
		return $button;
	}

	/**
	 * Output inline jQuery script in wp_footer to update submit button text.
	 */
	public function addSubmitButtonLoadingScript(): void {
		$loading_text = ! empty( $this->options['gf_submit_button_loading_text'] )
			? $this->options['gf_submit_button_loading_text']
			: 'Sending...';
		?>
		<script type="text/javascript">
			jQuery(document).on('submit', '.gform_wrapper form', function() {
				var $form = jQuery(this);
				// Avoid double submission text triggers
				if ($form.data('tka_submitting')) {
					return;
				}
				var $submit = $form.find('input[type="submit"], button[type="submit"]');
				if ($submit.length) {
					var loadingText = <?php echo wp_json_encode( $loading_text ); ?>;
					$form.data('tka_submitting', true);
					$submit.each(function() {
						var $btn = jQuery(this);
						if ($btn.is('input')) {
							$btn.val(loadingText);
						} else {
							$btn.text(loadingText);
						}
						// Disable pointer events and add class for style overrides without blocking POST submit values
						$btn.css('pointer-events', 'none').addClass('tka-submitting');
					});
				}
			});
		</script>
		<?php
	}
}
