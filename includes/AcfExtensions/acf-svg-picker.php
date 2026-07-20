<?php
/**
 * Extension Name: ACF SVG Picker
 * Description: A visual searchable picker to select SVG icons from a configurable theme/plugin directory.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'acf/include_field_types', function( $version ) {

    class tka_acf_field_svg_picker extends acf_field {

        public function initialize() {
            $this->name     = 'tka_svg_picker';
            $this->label    = __( 'SVG Picker (TKA)', 'tka-site-utilities' );
            $this->category = 'choice';
            $this->defaults = array(
                'svg_directory_path' => 'resources/images/icons/',
                'return_format'      => 'filename',
                'allow_multiple'     => 0,
            );
        }

        public function render_field_settings( $field ) {
            // SVG Directory Path setting
            acf_render_field_setting( $field, array(
                'label'        => __( 'SVG Directory Path', 'tka-site-utilities' ),
                'instructions' => __( 'Specify the directory path relative to the active theme root (e.g. <code>resources/images/icons/</code> or <code>public/icons/</code>).', 'tka-site-utilities' ),
                'type'         => 'text',
                'name'         => 'svg_directory_path',
            ) );

            // Return Format setting
            acf_render_field_setting( $field, array(
                'label'        => __( 'Return Format', 'tka-site-utilities' ),
                'instructions' => __( 'Specify the returned value format', 'tka-site-utilities' ),
                'type'         => 'radio',
                'name'         => 'return_format',
                'layout'       => 'horizontal',
                'choices'      => array(
                    'filename' => __( 'Filename (e.g. icon.svg)', 'tka-site-utilities' ),
                    'slug'     => __( 'Slug (e.g. icon)', 'tka-site-utilities' ),
                    'raw'      => __( 'Raw SVG Code (Inline HTML)', 'tka-site-utilities' ),
                    'url'      => __( 'SVG URL', 'tka-site-utilities' ),
                ),
            ) );

            // Allow Multiple setting
            acf_render_field_setting( $field, array(
                'label'        => __( 'Select Multiple?', 'tka-site-utilities' ),
                'instructions' => __( 'Allow content editors to select multiple SVGs.', 'tka-site-utilities' ),
                'type'         => 'true_false',
                'name'         => 'allow_multiple',
                'ui'           => 1,
            ) );
        }

        public function input_admin_enqueue_scripts() {
            $plugin_url = TKA_SITE_UTILITIES_URL . 'includes/AcfExtensions/assets/acf-svg-picker/';
            
            wp_enqueue_style( 'tka-acf-svg-picker-css', $plugin_url . 'css/acf-svg-picker.css', array(), TKA_SITE_UTILITIES_VERSION );
            wp_enqueue_script( 'tka-acf-svg-picker-js', $plugin_url . 'js/acf-svg-picker.js', array('jquery'), TKA_SITE_UTILITIES_VERSION, true );
        }

        /**
         * Scan configured path and return list of SVG files.
         */
        private function get_available_svgs( $field ) {
            $relative_path = $field['svg_directory_path'] ?? $this->defaults['svg_directory_path'];
            $theme_dir     = get_stylesheet_directory();
            $full_path     = rtrim( $theme_dir . '/' . trim( $relative_path, '/' ), '/' );

            if ( ! is_dir( $full_path ) ) {
                return array();
            }

            $files = glob( $full_path . '/*.svg' );
            if ( empty( $files ) ) {
                return array();
            }

            $svgs = array();
            foreach ( $files as $file ) {
                $filename = basename( $file );
                $slug     = str_replace( '.svg', '', $filename );
                $content  = file_get_contents( $file );

                // Clean/sanitize raw SVG code for safe inline rendering in preview/picker
                $clean_svg = $this->sanitize_svg_for_preview( $content );

                if ( $clean_svg ) {
                    $svgs[] = array(
                        'filename' => $filename,
                        'slug'     => $slug,
                        'raw'      => $clean_svg,
                    );
                }
            }

            return $svgs;
        }

        /**
         * Sanitize SVG to allow safe preview rendering.
         */
        private function sanitize_svg_for_preview( $svg_content ) {
            // Remove XML prolog, doc type and PHP code tags
            $svg_content = preg_replace( '/<\?xml.*?\?>/i', '', $svg_content );
            $svg_content = preg_replace( '/<!DOCTYPE.*?>/i', '', $svg_content );
            
            // Only keep contents starting from <svg
            $pos = stripos( $svg_content, '<svg' );
            if ( $pos !== false ) {
                $svg_content = substr( $svg_content, $pos );
            }

            return trim( $svg_content );
        }

        public function render_field( $field ) {
            $svgs      = $this->get_available_svgs( $field );
            $multiple  = ! empty( $field['allow_multiple'] );
            
            // Normalize values as array
            $values = acf_get_array( $field['value'] );
            $values = array_filter( $values ); // Remove empty items

            echo '<div class="tka-svg-picker-wrapper" data-svgs="' . esc_attr( json_encode( $svgs ) ) . '" data-multiple="' . ( $multiple ? '1' : '0' ) . '">';
            
            if ( $multiple ) {
                // MULTIPLE SELECTIONS RENDERER
                echo '  <div class="tka-svg-picker-multiple-container">';
                
                // Hidden base input to store the field name structure and handle empty state submits
                $disabled_attr = empty( $values ) ? '' : 'disabled';
                echo '    <input type="hidden" name="' . esc_attr( $field['name'] ) . '" class="tka-svg-picker-value" value="" ' . $disabled_attr . ' />';
                
                echo '    <div class="tka-svg-picker-multiple-grid">';

                foreach ( $values as $val ) {
                    $preview_raw = '';
                    foreach ( $svgs as $s ) {
                        if ( $s['filename'] === $val || $s['slug'] === $val ) {
                            $preview_raw = $s['raw'];
                            break;
                        }
                    }

                    if ( ! empty( $preview_raw ) ) {
                        echo '      <div class="tka-svg-multiple-preview-item" data-value="' . esc_attr( $val ) . '">';
                        echo '        <div class="tka-svg-multiple-preview-display">' . $preview_raw . '</div>';
                        echo '        <span class="tka-svg-multiple-preview-title">' . esc_html( str_replace( '.svg', '', $val ) ) . '</span>';
                        echo '        <span class="tka-svg-multiple-preview-remove">&times;</span>';
                        echo '        <input type="hidden" name="' . esc_attr( $field['name'] ) . '[]" value="' . esc_attr( $val ) . '" />';
                        echo '      </div>';
                    }
                }

                echo '    </div>';
                
                // Show empty placeholder if no SVGs are selected
                $placeholder_style = empty( $values ) ? '' : 'style="display:none;"';
                echo '    <div class="tka-svg-multiple-empty-placeholder" ' . $placeholder_style . '>';
                echo '      <span>' . esc_html__( 'No SVGs selected', 'tka-site-utilities' ) . '</span>';
                echo '    </div>';

                echo '    <div class="tka-svg-picker-actions">';
                echo '      <button type="button" class="button tka-svg-picker-select-btn">' . esc_html__( 'Choose SVGs', 'tka-site-utilities' ) . '</button>';
                
                $clear_class = ! empty( $values ) ? '' : 'hidden';
                echo '      <button type="button" class="button tka-svg-picker-clear-btn ' . esc_attr( $clear_class ) . '">' . esc_html__( 'Clear All', 'tka-site-utilities' ) . '</button>';
                echo '    </div>';
                echo '  </div>';

            } else {
                // SINGLE SELECTION RENDERER
                $value     = count( $values ) > 0 ? reset( $values ) : '';
                $has_value = ! empty( $value );

                $preview_raw = '';
                foreach ( $svgs as $s ) {
                    if ( $s['filename'] === $value || $s['slug'] === $value ) {
                        $preview_raw = $s['raw'];
                        break;
                    }
                }

                echo '  <div class="tka-svg-picker-main">';
                
                // Hidden input to store selected value
                echo '    <input type="hidden" name="' . esc_attr( $field['name'] ) . '" class="tka-svg-picker-value" value="' . esc_attr( $value ) . '" />';
                
                // Visual Preview Card
                $preview_class = $has_value ? 'has-value' : 'empty';
                echo '    <div class="tka-svg-picker-preview ' . esc_attr( $preview_class ) . '">';
                echo '      <div class="tka-svg-picker-display">' . $preview_raw . '</div>';
                echo '      <span class="tka-svg-picker-placeholder">' . esc_html__( 'Add SVG', 'tka-site-utilities' ) . '</span>';
                echo '    </div>';
                
                // Action Buttons
                echo '    <div class="tka-svg-picker-actions">';
                echo '      <button type="button" class="button tka-svg-picker-select-btn">' . esc_html__( 'Choose SVG', 'tka-site-utilities' ) . '</button>';
                
                $clear_class = $has_value ? '' : 'hidden';
                echo '      <button type="button" class="button tka-svg-picker-clear-btn ' . esc_attr( $clear_class ) . '">' . esc_html__( 'Clear', 'tka-site-utilities' ) . '</button>';
                echo '    </div>';
                
                echo '  </div>';
            }

            echo '</div>';
        }

        public function update_value( $value, $post_id, $field ) {
            if ( empty( $value ) ) {
                return '';
            }

            if ( is_array( $value ) ) {
                return array_map( 'sanitize_text_field', array_filter( $value ) );
            }

            return sanitize_text_field( $value );
        }

        /**
         * Helper to resolve the correct URL/path pointing to the SVG, handling Vite manifests.
         */
        private function resolve_svg_url( $filename, $relative_dir ) {
            $theme_dir = get_stylesheet_directory();
            $theme_uri = get_stylesheet_directory_uri();
            $key       = trim( $relative_dir, '/' ) . '/' . $filename;

            // Vite manifest lookups
            $manifest_paths = array(
                $theme_dir . '/dist/manifest.json',
                $theme_dir . '/public/build/manifest.json',
                $theme_dir . '/manifest.json',
            );

            foreach ( $manifest_paths as $manifest_path ) {
                if ( file_exists( $manifest_path ) ) {
                    $manifest = json_decode( file_get_contents( $manifest_path ), true );
                    if ( is_array( $manifest ) && isset( $manifest[ $key ] ) ) {
                        $mapped    = $manifest[ $key ];
                        $file_name = is_array( $mapped ) ? ( $mapped['file'] ?? '' ) : $mapped;
                        if ( $file_name ) {
                            // Vite output is relative to its build directory
                            $dist_folder = basename( dirname( $manifest_path ) );
                            return $theme_uri . '/' . $dist_folder . '/' . $file_name;
                        }
                    }
                }
            }

            return $theme_uri . '/' . $key;
        }

        /**
         * Format a single SVG value.
         */
        private function format_single_value( $val, $relative_path, $theme_dir, $return_format ) {
            $filename = str_ends_with( $val, '.svg' ) ? $val : $val . '.svg';
            $slug     = str_replace( '.svg', '', $val );

            if ( $return_format === 'slug' ) {
                return $slug;
            }

            if ( $return_format === 'filename' ) {
                return $filename;
            }

            if ( $return_format === 'url' ) {
                return $this->resolve_svg_url( $filename, $relative_path );
            }

            if ( $return_format === 'raw' ) {
                $full_path = rtrim( $theme_dir . '/' . trim( $relative_path, '/' ), '/' ) . '/' . $filename;
                if ( file_exists( $full_path ) ) {
                    return file_get_contents( $full_path );
                }
            }

            return $val;
        }

        public function format_value( $value, $post_id, $field ) {
            if ( empty( $value ) ) {
                return $field['allow_multiple'] ? array() : '';
            }

            $relative_path = $field['svg_directory_path'] ?? $this->defaults['svg_directory_path'];
            $theme_dir     = get_stylesheet_directory();
            $return_format = $field['return_format'] ?? 'filename';

            if ( is_array( $value ) ) {
                $formatted = array();
                foreach ( $value as $val ) {
                    $formatted[] = $this->format_single_value( $val, $relative_path, $theme_dir, $return_format );
                }
                return $formatted;
            }

            return $this->format_single_value( $value, $relative_path, $theme_dir, $return_format );
        }
    }

    new tka_acf_field_svg_picker();

} );
