<?php
/**
 * Extension Name: ACF Link Extended
 * Description: Extends the default ACF Link field to add a 'class' input in the WP Link modal.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action( 'acf/include_field_types', function( $version ) {

    if ( ! class_exists( 'acf_field_link' ) ) {
        return;
    }

    /**
     * Class acf_field_link_extended
     * Extends the default ACF Link field to add a 'class' input in the WP Link modal.
     */
    class acf_field_link_extended extends acf_field_link {
        
        public function initialize() {
            // Call parent initialization first to ensure default properties are set
            parent::initialize();
            
            $this->name     = 'link_extended';
            $this->label    = __( 'Link (with Class)', 'acf' );
            $this->category = 'relational';
        }
        
        /**
         * Add field settings for the Class Choices.
         */
        public function render_field_settings( $field ) {
            // Render the parent settings first
            parent::render_field_settings( $field );
            
            // Add our custom class choices setting
            acf_render_field_setting( $field, array(
                'label'        => __( 'Class Choices', 'acf' ),
                'instructions' => __( 'Enter each choice on a new line.<br>For more control, you may specify both a value and label like this:<br>button : Button<br>button-primary : Primary Button', 'acf' ),
                'type'         => 'textarea',
                'name'         => 'class_choices',
            ) );
        }

        /**
         * Render the field HTML.
         */
        public function render_field( $field ) {
            // Parse choices
            $choices = array();
            if ( !empty( $field['class_choices'] ) ) {
                if ( function_exists('acf_decode_choices') ) {
                    $choices = acf_decode_choices( $field['class_choices'] );
                } else {
                    $lines = explode("\n", $field['class_choices']);
                    foreach($lines as $line) {
                        $line = trim($line);
                        if(empty($line)) continue;
                        if(strpos($line, ' : ') !== false) {
                            list($k, $v) = explode(' : ', $line, 2);
                            $choices[trim($k)] = trim($v);
                        } else {
                            $choices[$line] = $line;
                        }
                    }
                }
            }
            
            // Output choices so JS can read them
            echo '<div class="acf-link-class-choices" style="display:none;" data-choices="' . esc_attr( wp_json_encode( $choices ) ) . '"></div>';

            // Ensure the 'class' key exists in the value array.
            // This forces the parent::render_field() method to automatically 
            // generate the hidden input for the class attribute within the .acf-hidden div.
            if ( ! is_array( $field['value'] ) ) {
                $field['value'] = array();
            }
            if ( ! isset( $field['value']['class'] ) ) {
                $field['value']['class'] = '';
            }

            // Render the standard Link field UI
            parent::render_field( $field );
        }
        
        /**
         * Ensure the 'class' value is saved.
         */
        public function update_value( $value, $post_id, $field ) {
            $class = '';
            if ( is_array( $value ) && isset( $value['class'] ) ) {
                $class = $value['class'];
            }
            
            $value = parent::update_value( $value, $post_id, $field );
            
            if ( is_array( $value ) ) {
                $value['class'] = $class;
            }
            
            return $value;
        }
        
        /**
         * Ensure the 'class' value is returned when fetching the field. 
         */
        public function format_value( $value, $post_id, $field ) {
            $class = '';
            if ( is_array( $value ) && isset( $value['class'] ) ) {
                $class = $value['class'];
            }
            
            $value = parent::format_value( $value, $post_id, $field );
            
            if ( is_array( $value ) ) {
                $value['class'] = $class;
            }
            
            return $value;
        }

        /**
         * Output JS in the admin footer to inject the field into the wpLink modal.
         */
        public function input_admin_footer() {
            ?>
            <script type="text/javascript">
            (function($){
                
                // 1. Unconditionally inject the HTML into the wpLink dialog when the DOM is ready
                $(document).ready(function(){
                    if ( $('#wp-link-class-wrap').length === 0 ) {
                        var classInputHTML = '<div id="wp-link-class-wrap" class="wp-link-text-field">' +
                            '<label><span>Class</span> <span id="wp-link-class-input-container"><input type="text" id="wp-link-class" /></span></label>' +
                            '</div>';
                        $('#link-options').append(classInputHTML);
                    }
                });

                // 2. We bypass ACF's JS model entirely and use robust global event delegates.
                // This ensures clicks always work, even on newly cloned Flexible Content rows.
                var fieldSelector = '.acf-field-link-extended, .acf-field-link_extended';

                // Handle 'Select Link' and 'Edit' button clicks
                $(document).on('click', fieldSelector + ' [data-name="add"], ' + fieldSelector + ' [data-name="edit"]', function(e) {
                    e.preventDefault();
                    
                    var $el = $(this).closest('.acf-field');
                    var $urlInput = $el.find('.input-url');
                    
                    // Flexible content rows might lack unique IDs, so we generate one if missing
                    var inputId = $urlInput.attr('id');
                    if (!inputId) {
                        inputId = 'acf-link-' + Math.random().toString(36).substr(2, 9);
                        $urlInput.attr('id', inputId);
                    }
                    
                    if (typeof wpLink !== 'undefined') {
                        // Dynamically build the input or select based on configured choices
                        var choicesData = $el.find('.acf-link-class-choices').attr('data-choices');
                        var choices = choicesData ? JSON.parse(choicesData) : {};
                        
                        var $container = $('#wp-link-class-input-container');
                        
                        if (Object.keys(choices).length > 0) {
                            var selectHTML = '<select id="wp-link-class">';
                            selectHTML += '<option value="">- Select -</option>';
                            $.each(choices, function(val, label) {
                                selectHTML += '<option value="' + val + '">' + label + '</option>';
                            });
                            selectHTML += '</select>';
                            $container.html(selectHTML);
                        } else {
                            $container.html('<input type="text" id="wp-link-class" />');
                        }
                        
                        // Open the WP Link modal
                        wpLink.open(inputId);
                        
                        // Manually populate the modal fields from our hidden inputs
                        $('#wp-link-url').val($urlInput.val());
                        $('#wp-link-text').val($el.find('.input-title').val());
                        $('#wp-link-target').prop('checked', $el.find('.input-target').val() === '_blank');
                        $('#wp-link-class').val($el.find('.input-class').val() || '');
                        
                        // Override the submit handler uniquely for this specific field
                        wpLink.update = function() {
                            
                            // Read directly from the wpLink inputs instead of using getAttrs()
                            // which returns different keys like 'href' instead of 'url'.
                            var newTitle  = $('#wp-link-text').val() || '';
                            var newUrl    = $('#wp-link-url').val() || '';
                            var newTarget = $('#wp-link-target').prop('checked') ? '_blank' : '';
                            var newClass  = $('#wp-link-class').val() || '';
                            
                            // Save data back to hidden inputs
                            $el.find('.input-title').val(newTitle);
                            $el.find('.input-url').val(newUrl).trigger('change');
                            $el.find('.input-target').val(newTarget);
                            $el.find('.input-class').val(newClass).trigger('change');
                            
                            // Update visual display (must target the inner .acf-link wrapper for ACF CSS to work)
                            var $inner = $el.find('.acf-link');
                            
                            $inner.find('.link-title').text(newTitle);
                            $inner.find('.link-url').text(newUrl).attr('href', newUrl);
                            
                            if (newUrl) {
                                $inner.addClass('-value');
                            } else {
                                $inner.removeClass('-value');
                            }
                            
                            if (newTarget === '_blank') {
                                $inner.addClass('-external');
                            } else {
                                $inner.removeClass('-external');
                            }
                            
                            wpLink.close();
                        };
                    } else {
                        console.error('wpLink is not defined. Ensure WordPress link scripts are loaded.');
                    }
                });

                // Handle 'Remove' button click
                $(document).on('click', fieldSelector + ' [data-name="remove"]', function(e) {
                    e.preventDefault();
                    var $el = $(this).closest('.acf-field');
                    
                    // Clear hidden inputs
                    $el.find('.input-title').val('');
                    $el.find('.input-url').val('').trigger('change');
                    $el.find('.input-target').val('');
                    $el.find('.input-class').val('').trigger('change');
                    
                    // Reset visual display
                    $el.find('.acf-link').removeClass('-value -external');
                });

            })(jQuery);
            </script>
            <?php
        }
    }

    new acf_field_link_extended();

} );
