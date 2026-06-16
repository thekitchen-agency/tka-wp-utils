<?php
/**
 * Extension Name: ACF Intl Phone Field
 * Description: International Phone number field with country flag dropdown and auto-formatting.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'acf/include_field_types', function( $version ) {

    class acf_field_intl_phone extends acf_field {

        public function initialize() {
            $this->name     = 'intl_phone';
            $this->label    = __( 'International Phone', 'acf' );
            $this->category = 'basic';
            $this->defaults = array(
                'default_value' => '',
                'preferred_countries' => 'us,gb',
                'return_format' => 'array',
            );
        }

        public function render_field_settings( $field ) {
            // Return Format
            acf_render_field_setting( $field, array(
                'label'        => __( 'Return Format', 'acf' ),
                'instructions' => __( 'Specify the returned value format', 'acf' ),
                'type'         => 'radio',
                'name'         => 'return_format',
                'layout'       => 'horizontal',
                'choices'      => array(
                    'array' => __( 'Phone Array (Number, Country Code)', 'acf' ),
                    'string' => __( 'Formatted String (e.g. +1 555-1234)', 'acf' ),
                ),
            ) );

            // Preferred Countries
            acf_render_field_setting( $field, array(
                'label'        => __( 'Preferred Countries', 'acf' ),
                'instructions' => __( 'Comma separated 2-letter country codes (e.g., us,gb,ch) to show at the top of the list.', 'acf' ),
                'type'         => 'text',
                'name'         => 'preferred_countries',
            ) );
        }

        public function input_admin_enqueue_scripts() {
            $plugin_url = TKA_WP_UTILS_URL . 'includes/AcfExtensions/assets/intl-tel-input/';
            
            wp_enqueue_style( 'intl-tel-input-css', $plugin_url . 'css/intlTelInput.min.css', array(), '1' );
            wp_enqueue_script( 'intl-tel-input-js', $plugin_url . 'js/intlTelInput.min.js', array('jquery'), '1', true );
        }

        public function input_admin_footer() {
            $plugin_url = TKA_WP_UTILS_URL . 'includes/AcfExtensions/assets/intl-tel-input/';
            ?>
            <style>
                .iti { width: 100%; display: block; }
                .acf-field-intl-phone .acf-input-wrap { overflow: visible; }
            </style>
            <script type="text/javascript">
            (function($) {
                if (typeof acf === 'undefined') return;

                function initialize_field( field ) {
                    // field is the ACF field instance in ACF 5.7+, but could be a jQuery object in older contexts or hooks.
                    var fieldInstance = field instanceof jQuery ? acf.getField(field) : field;
                    if (!fieldInstance) return;

                    var $el = fieldInstance.$el;
                    if (!$el) return;

                    var $input = $el.find('input.intl-tel-input-field');
                    if (!$input.length) return;
                    
                    var preferred = $input.data('preferred') ? $input.data('preferred').split(',') : ['us', 'gb'];
                    var initial = $input.data('initial') || 'auto';

                    var iti = window.intlTelInput($input[0], {
                        initialCountry: initial,
                        preferredCountries: preferred,
                        utilsScript: "<?php echo esc_url($plugin_url . 'js/utils.js'); ?>",
                        geoIpLookup: function(callback) {
                            if (initial !== 'auto') return;
                            fetch("https://ipapi.co/json")
                                .then(function(res) { return res.json(); })
                                .then(function(data) { callback(data.country_code); })
                                .catch(function() { callback("us"); });
                        }
                    });

                    // Explicitly override the field's getValue method directly on the instance!
                    // This is 100% guaranteed to be called by ACF's serialize() loop,
                    // completely bypassing any fallback DOM selector logic.
                    fieldInstance.getValue = function() {
                        var raw = $input.val();
                        if (!raw) return '';

                        var num = raw;
                        if (iti && typeof iti.getNumber === 'function') {
                            var formatted = iti.getNumber();
                            if (formatted) num = formatted; // Guarantee +4144... format
                        }

                        var countryData = iti ? iti.getSelectedCountryData() : null;
                        var iso2 = countryData && countryData.iso2 ? countryData.iso2.toUpperCase() : '';
                        
                        return iso2 + ':' + num;
                    };
                }

                acf.addAction('ready_field/type=intl_phone', initialize_field);
                acf.addAction('append_field/type=intl_phone', initialize_field);

            })(jQuery);
            </script>
            <?php
        }

        public function render_field( $field ) {
            $val_number = '';
            $val_country = '';

            if ( is_string( $field['value'] ) && strpos( $field['value'], ':' ) !== false ) {
                $parts = explode(':', $field['value'], 2);
                $val_country = $parts[0] ?? '';
                $val_number = $parts[1] ?? '';
            } elseif ( is_array( $field['value'] ) ) {
                $val_number = $field['value']['number'] ?? '';
                $val_country = $field['value']['country'] ?? '';
            } else {
                $val_number = is_string($field['value']) ? $field['value'] : '';
            }

            echo '<div class="acf-input-wrap">';
            
            // VISIBLE INPUT: Now restored to full native functionality.
            // ACF's serialize loop will call our dynamic field.getValue() override above instead of parsing this input natively.
            echo '<input type="text" inputmode="tel" id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="intl-tel-input-field acf-is-prepended" value="' . esc_attr($val_number) . '" data-preferred="' . esc_attr($field['preferred_countries']) . '" data-initial="' . esc_attr(strtolower($val_country)) . '" />';
            
            echo '</div>';
        }

        public function update_value( $value, $post_id, $field ) {
            if ( empty( $value ) || $value === ':' ) {
                return '';
            }
            return is_string($value) ? trim($value) : '';
        }

        public function format_value( $value, $post_id, $field ) {
            if ( empty( $value ) || $value === ':' ) return $value;

            $country = '';
            $number = '';

            if ( is_string( $value ) ) {
                if ( strpos( $value, ':' ) !== false ) {
                    $parts = explode(':', $value, 2);
                    $country = $parts[0] ?? '';
                    $number = $parts[1] ?? '';
                } else {
                    $number = $value;
                }
            } elseif ( is_array( $value ) ) {
                $country = $value['country'] ?? '';
                $number = $value['number'] ?? '';
            }

            if ( $field['return_format'] === 'string' ) {
                return $number;
            }

            return array(
                'number' => $number,
                'country' => $country,
            );
        }
    }

    new acf_field_intl_phone();

} );
