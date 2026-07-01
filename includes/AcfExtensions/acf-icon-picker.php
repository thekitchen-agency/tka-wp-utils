<?php
/**
 * Extension Name: ACF Icon Picker
 * Description: A premium search-enabled icon picker for Google Material Symbols Outlined.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'acf/include_field_types', function( $version ) {

    class tka_acf_field_icon_picker extends acf_field {

        public function initialize() {
            $this->name     = 'tka_icon_picker';
            $this->label    = __( 'Icon Picker (TKA)', 'tka-site-utilities' );
            $this->category = 'choice';
            $this->defaults = array(
                'default_value' => '',
                'return_format' => 'value',
            );
        }

        public function render_field_settings( $field ) {
            // Return Format setting
            acf_render_field_setting( $field, array(
                'label'        => __( 'Return Format', 'tka-site-utilities' ),
                'instructions' => __( 'Specify the returned value format', 'tka-site-utilities' ),
                'type'         => 'radio',
                'name'         => 'return_format',
                'layout'       => 'horizontal',
                'choices'      => array(
                    'value' => __( 'Icon Name (e.g. flight_takeoff)', 'tka-site-utilities' ),
                    'html'  => __( 'HTML Icon Tag (e.g. &lt;span class="material-symbols-outlined"&gt;flight_takeoff&lt;/span&gt;)', 'tka-site-utilities' ),
                ),
            ) );
        }

        public function input_admin_enqueue_scripts() {
            // Enqueue Material Symbols Outlined stylesheet so icons render inside the admin
            wp_enqueue_style( 'material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', array(), '1' );

            $plugin_url = TKA_SITE_UTILITIES_URL . 'includes/AcfExtensions/assets/acf-icon-picker/';
            
            wp_enqueue_style( 'acf-icon-picker-css', $plugin_url . 'css/acf-icon-picker.css', array(), TKA_SITE_UTILITIES_VERSION );
            wp_enqueue_script( 'acf-icon-picker-js', $plugin_url . 'js/acf-icon-picker.js', array('jquery'), TKA_SITE_UTILITIES_VERSION, true );
        }

        public function render_field( $field ) {
            $value = is_string($field['value']) ? $field['value'] : '';
            $has_value = !empty($value);
            
            echo '<div class="acf-icon-picker-wrapper">';
            echo '<div class="acf-icon-picker-main">';
            
            // Hidden input to store selected value
            echo '<input type="hidden" name="' . esc_attr($field['name']) . '" class="acf-icon-picker-value" value="' . esc_attr($value) . '" />';
            
            // Visual Preview Card
            $preview_class = $has_value ? 'has-value' : 'empty';
            echo '<div class="acf-icon-picker-preview ' . esc_attr($preview_class) . '">';
            echo '<span class="material-symbols-outlined acf-icon-picker-icon-display">' . esc_html($value) . '</span>';
            echo '<span class="acf-icon-picker-placeholder">' . esc_html__('Add Icon', 'tka-site-utilities') . '</span>';
            echo '</div>';
            
            // Action Buttons
            echo '<div class="acf-icon-picker-actions">';
            echo '<button type="button" class="button acf-icon-picker-select-btn">' . esc_html__('Choose Icon', 'tka-site-utilities') . '</button>';
            
            $clear_class = $has_value ? '' : 'hidden';
            echo '<button type="button" class="button acf-icon-picker-clear-btn ' . esc_attr($clear_class) . '">' . esc_html__('Clear', 'tka-site-utilities') . '</button>';
            echo '</div>';
            
            echo '</div>';
            echo '</div>';
        }

        public function update_value( $value, $post_id, $field ) {
            if ( empty( $value ) ) {
                return '';
            }
            return sanitize_text_field( $value );
        }

        public function format_value( $value, $post_id, $field ) {
            if ( empty( $value ) ) {
                return '';
            }
            
            if ( isset($field['return_format']) && $field['return_format'] === 'html' ) {
                return '<span class="material-symbols-outlined">' . esc_html( $value ) . '</span>';
            }
            
            return $value;
        }
    }

    new tka_acf_field_icon_picker();

} );
