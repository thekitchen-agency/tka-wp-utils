<?php
/**
 * Pluggable function overrides.
 */

if ( ! function_exists( 'woocommerce_quantity_input' ) ) {
	/**
	 * Override WooCommerce quantity input to convert it to a dropdown if enabled.
	 *
	 * @param array           $args    Arguments.
	 * @param WC_Product|null $product Product instance.
	 * @param bool            $echo    Whether to echo or return.
	 * @return string
	 */
	function woocommerce_quantity_input( $args = array(), $product = null, $echo = true ) {
		$options = get_option( 'tka_wp_utils_options', [] );
		
		if ( ! empty( $options['wc_quantity_dropdown'] ) ) {
			if ( is_null( $product ) ) {
				$product = $GLOBALS['product'] ?? null;
			}
			$defaults = array(
				'input_id'     => uniqid( 'quantity_' ),
				'input_name'   => 'quantity',
				'input_value'  => '1',
				'classes'      => apply_filters( 'woocommerce_quantity_input_classes', array( 'qty', 'text' ), $product ),
				'max_value'    => apply_filters( 'woocommerce_quantity_input_max', -1, $product ),
				'min_value'    => apply_filters( 'woocommerce_quantity_input_min', 0, $product ),
				'step'         => apply_filters( 'woocommerce_quantity_input_step', 1, $product ),
				'pattern'      => apply_filters( 'woocommerce_quantity_input_pattern', has_filter( 'woocommerce_stock_amount', 'intval' ) ? '[0-9]*' : '' ),
				'inputmode'    => apply_filters( 'woocommerce_quantity_input_inputmode', has_filter( 'woocommerce_stock_amount', 'intval' ) ? 'numeric' : '' ),
				'product_name' => $product ? $product->get_title() : '',
			);
			$args = apply_filters( 'woocommerce_quantity_input_args', wp_parse_args( $args, $defaults ), $product );
			
			// Apply sanity to min/max args - min cannot be lower than 0.
			$args['min_value'] = max( $args['min_value'], 0 );
			
			// Note: change 20 to whatever you like
			$args['max_value'] = 0 < $args['max_value'] ? $args['max_value'] : 20;
			
			// Max cannot be lower than min if defined.
			if ( '' !== $args['max_value'] && $args['max_value'] < $args['min_value'] ) {
				$args['max_value'] = $args['min_value'];
			}
			
			$options_html = '';
			for ( $count = $args['min_value']; $count <= $args['max_value']; $count = $count + $args['step'] ) {
				if ( '' !== $args['input_value'] && $args['input_value'] >= 1 && $count == $args['input_value'] ) {
					$selected = 'selected';
				} else {
					$selected = '';
				}
				$options_html .= '<option value="' . esc_attr( $count ) . '" ' . $selected . '>' . esc_html( $count ) . '</option>';
			}
			
			$string = '<div class="quantity"><label for="' . esc_attr( $args['input_id'] ) . '">' . esc_html__ ( 'Qty', 'woocommerce' ) . '</label><select id="' . esc_attr( $args['input_id'] ) . '" name="' . esc_attr( $args['input_name'] ) . '" class="' . esc_attr( join( ' ', (array) $args['classes'] ) ) . '">' . $options_html . '</select></div>';
			
			if ( $echo ) {
				echo $string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				return $string;
			}
		} else {
			// Run default WooCommerce function logic if function is not enabled
			if ( function_exists( 'wc_get_quantity_input_args' ) && function_exists( 'wc_get_template' ) ) {
				$product = is_null( $product ) ? ($GLOBALS['product'] ?? null) : $product;
				$args    = wc_get_quantity_input_args( $args, $product );

				ob_start();
				wc_get_template( 'global/quantity-input.php', $args );

				if ( $echo ) {
					echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					return ob_get_clean();
				}
			}
			return '';
		}
	}
}
