<?php
/**
 * Extension Name: ACF Flexible Content Layout Rules
 * Description: Control which flexible layouts are shown on specific page templates, post types, user roles, etc.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TKA_ACF_Layout_Rules {

    public function __construct() {
        // Render settings in field group editor
        add_action( 'acf/render_field', array( $this, 'render_layout_rules_setting' ), 15, 1 );

        // Evaluate rules when preparing flexible content field
        add_filter( 'acf/prepare_field/type=flexible_content', array( $this, 'prepare_flexible_layouts_rules' ), 12, 1 );
    }

    /**
     * Hook into acf/render_field to append layout location rules below layout settings.
     */
    public function render_layout_rules_setting( $field ) {
        if ( empty( $field['name'] ) ) {
            return;
        }

        // We target the layout 'max' field to append our settings right after it
        if ( preg_match( '/^acf_fields\[(?P<parent_field_key>field_[a-zA-Z0-9_]+|[0-9]+)\]\[layouts\]\[(?P<layout_key>layout_[a-zA-Z0-9_]+)\]\[max\]$/', $field['name'], $matches ) ) {
            $parent_field_key = $matches['parent_field_key'];
            $layout_key       = $matches['layout_key'];

            // Load parent field config
            $parent_field = acf_get_field( $parent_field_key );
            $rules        = array();

            if ( $parent_field && ! empty( $parent_field['layouts'] ) ) {
                foreach ( $parent_field['layouts'] as $layout ) {
                    if ( $layout['key'] === $layout_key ) {
                        $rules = ! empty( $layout['visibility_rules'] ) ? $layout['visibility_rules'] : array();
                        break;
                    }
                }
            }

            // Enqueue assets
            wp_enqueue_style( 'tka-acf-layout-rules-css', TKA_SITE_UTILITIES_URL . 'admin/css/acf-layout-rules.css', array(), TKA_SITE_UTILITIES_VERSION );
            wp_enqueue_script( 'tka-acf-layout-toggle-js', TKA_SITE_UTILITIES_URL . 'admin/js/acf-layout-toggle.js', array( 'jquery' ), TKA_SITE_UTILITIES_VERSION, true );

            $this->render_rules_ui( $field['name'], $rules );
        }
    }

    /**
     * Output the rules builder HTML.
     */
    private function render_rules_ui( $field_name, $rules ) {
        // Prepare options data
        $post_types     = get_post_types( array( 'public' => true ), 'objects' );
        $page_templates = wp_get_theme()->get_page_templates();
        $pages          = get_pages( array( 'post_type' => 'page', 'posts_per_page' => 100 ) );
        $user_roles     = wp_roles()->get_names();

        // Base name for saved data
        // e.g. acf_fields[field_xxxx][layouts][layout_xxxx][visibility_rules]
        $rules_input_base = preg_replace( '/\[max\]$/', '[visibility_rules]', $field_name );

        // Helper to output value selectors
        $value_selectors = array(
            'post_type'     => $post_types,
            'page_template' => $page_templates,
            'page_parent'   => $pages,
            'user_role'     => $user_roles,
        );

        echo '<div class="tka-layout-location-rules" data-base-name="' . esc_attr( $rules_input_base ) . '">';
        echo '  <span class="tka-rules-title">' . esc_html__( 'Layout Location Rules', 'tka-site-utilities' ) . '</span>';
        echo '  <p class="description">' . esc_html__( 'Control where this block layout is available for content editors.', 'tka-site-utilities' ) . '</p>';

        echo '  <div class="tka-rules-groups-container">';

        $group_index = 0;
        if ( ! empty( $rules ) && is_array( $rules ) ) {
            foreach ( $rules as $group ) {
                if ( ! is_array( $group ) ) {
                    continue;
                }
                $this->render_rule_group_html( $rules_input_base, $group_index, $group, $value_selectors );
                $group_index++;
            }
        }

        echo '  </div>';

        echo '  <div class="tka-rules-actions">';
        echo '    <button type="button" class="acf-btn acf-btn-secondary acf-btn-sm tka-add-rule-group">' . esc_html__( 'Add Rule Group', 'tka-site-utilities' ) . '</button>';
        echo '  </div>';

        // Render HTML templates for JS usage
        $this->render_js_templates( $rules_input_base, $value_selectors );

        echo '</div>';
    }

    /**
     * Render HTML for a single rule group.
     */
    private function render_rule_group_html( $base_name, $group_idx, $group, $options ) {
        echo '<div class="tka-rules-group" data-group-index="' . esc_attr( $group_idx ) . '">';
        if ( $group_idx > 0 ) {
            echo '  <div class="tka-rule-group-or">' . esc_html__( 'or', 'tka-site-utilities' ) . '</div>';
        }
        echo '  <table class="tka-rules-table">';
        echo '    <tbody>';

        $rule_index = 0;
        foreach ( $group as $rule ) {
            $param    = $rule['param'] ?? 'post_type';
            $operator = $rule['operator'] ?? '==';
            $val      = $rule['value'] ?? '';

            $this->render_rule_row_html( $base_name, $group_idx, $rule_index, $param, $operator, $val, $options );
            $rule_index++;
        }

        echo '    </tbody>';
        echo '  </table>';
        echo '</div>';
    }

    /**
     * Render a single rule row.
     */
    private function render_rule_row_html( $base_name, $group_idx, $rule_idx, $selected_param, $selected_operator, $selected_value, $options ) {
        $prefix = $base_name . '[' . $group_idx . '][' . $rule_idx . ']';

        echo '<tr class="tka-rule-row" data-rule-index="' . esc_attr( $rule_idx ) . '">';
        
        // Parameter Selector
        echo '  <td class="param">';
        echo '    <select name="' . esc_attr( $prefix . '[param]' ) . '" class="tka-rule-param-select">';
        echo '      <option value="post_type" ' . selected( $selected_param, 'post_type', false ) . '>' . esc_html__( 'Post Type', 'tka-site-utilities' ) . '</option>';
        echo '      <option value="page_template" ' . selected( $selected_param, 'page_template', false ) . '>' . esc_html__( 'Page Template', 'tka-site-utilities' ) . '</option>';
        echo '      <option value="page_parent" ' . selected( $selected_param, 'page_parent', false ) . '>' . esc_html__( 'Page Parent', 'tka-site-utilities' ) . '</option>';
        echo '      <option value="post" ' . selected( $selected_param, 'post', false ) . '>' . esc_html__( 'Post ID', 'tka-site-utilities' ) . '</option>';
        echo '      <option value="user_role" ' . selected( $selected_param, 'user_role', false ) . '>' . esc_html__( 'User Role', 'tka-site-utilities' ) . '</option>';
        echo '    </select>';
        echo '  </td>';

        // Operator Selector
        echo '  <td class="operator">';
        echo '    <select name="' . esc_attr( $prefix . '[operator]' ) . '">';
        echo '      <option value="==" ' . selected( $selected_operator, '==', false ) . '>' . esc_html__( 'is equal to', 'tka-site-utilities' ) . '</option>';
        echo '      <option value="!=" ' . selected( $selected_operator, '!=', false ) . '>' . esc_html__( 'is not equal to', 'tka-site-utilities' ) . '</option>';
        echo '    </select>';
        echo '  </td>';

        // Value Container
        echo '  <td class="value">';

        // 1. Post Type Select
        $disabled = ( $selected_param !== 'post_type' ) ? 'disabled style="display:none;"' : '';
        echo '    <select class="tka-val-select tka-val-post_type" name="' . esc_attr( $prefix . '[value]' ) . '" ' . $disabled . '>';
        foreach ( $options['post_type'] as $pt ) {
            echo '      <option value="' . esc_attr( $pt->name ) . '" ' . selected( $selected_value, $pt->name, false ) . '>' . esc_html( $pt->label ) . '</option>';
        }
        echo '    </select>';

        // 2. Page Template Select
        $disabled = ( $selected_param !== 'page_template' ) ? 'disabled style="display:none;"' : '';
        echo '    <select class="tka-val-select tka-val-page_template" name="' . esc_attr( $prefix . '[value]' ) . '" ' . $disabled . '>';
        echo '      <option value="default" ' . selected( $selected_value, 'default', false ) . '>' . esc_html__( 'Default Template', 'tka-site-utilities' ) . '</option>';
        foreach ( $options['page_template'] as $name => $file ) {
            echo '      <option value="' . esc_attr( $file ) . '" ' . selected( $selected_value, $file, false ) . '>' . esc_html( $name ) . '</option>';
        }
        echo '    </select>';

        // 3. Page Parent Select
        $disabled = ( $selected_param !== 'page_parent' ) ? 'disabled style="display:none;"' : '';
        echo '    <select class="tka-val-select tka-val-page_parent" name="' . esc_attr( $prefix . '[value]' ) . '" ' . $disabled . '>';
        foreach ( $options['page_parent'] as $p ) {
            echo '      <option value="' . esc_attr( $p->ID ) . '" ' . selected( $selected_value, $p->ID, false ) . '>' . esc_html( $p->post_title ) . '</option>';
        }
        echo '    </select>';

        // 4. Post ID Input
        $disabled = ( $selected_param !== 'post' ) ? 'disabled style="display:none;"' : '';
        echo '    <input type="number" class="tka-val-select tka-val-post" name="' . esc_attr( $prefix . '[value]' ) . '" value="' . esc_attr( $selected_value ) . '" placeholder="e.g. 42" ' . $disabled . '>';

        // 5. User Role Select
        $disabled = ( $selected_param !== 'user_role' ) ? 'disabled style="display:none;"' : '';
        echo '    <select class="tka-val-select tka-val-user_role" name="' . esc_attr( $prefix . '[value]' ) . '" ' . $disabled . '>';
        foreach ( $options['user_role'] as $role_slug => $role_name ) {
            echo '      <option value="' . esc_attr( $role_slug ) . '" ' . selected( $selected_value, $role_slug, false ) . '>' . esc_html( $role_name ) . '</option>';
        }
        echo '    </select>';

        echo '  </td>';

        // Actions
        echo '  <td class="actions">';
        echo '    <a href="#" class="tka-remove-rule acf-icon -minus small"></a>';
        echo '    <a href="#" class="tka-add-rule acf-icon -plus small"></a>';
        echo '  </td>';

        echo '</tr>';
    }

    /**
     * JS Templates used to generate group/row markup dynamically.
     */
    private function render_js_templates( $base_name, $options ) {
        echo '<template class="tka-tpl-rule-row">';
        $this->render_rule_row_html( $base_name, '{group_idx}', '{rule_idx}', 'post_type', '==', '', $options );
        echo '</template>';
    }

    /**
     * Hook to prepare the Flexible Content field, filtering layouts based on rules.
     */
    public function prepare_flexible_layouts_rules( $field ) {
        if ( ! is_admin() || empty( $field['layouts'] ) ) {
            return $field;
        }

        // Determine current editing context
        $post_id = $_GET['post'] ?? ( $_POST['post_ID'] ?? null );
        if ( ! $post_id ) {
            // Check post-new screen
            global $pagenow;
            if ( $pagenow === 'post-new.php' ) {
                $post_type = $_GET['post_type'] ?? 'post';
            } else {
                return $field;
            }
        } else {
            $post_id   = intval( $post_id );
            $post_type = get_post_type( $post_id );
        }

        $current_user  = wp_get_current_user();
        $user_roles    = $current_user->roles;
        $page_template = $post_id ? get_post_meta( $post_id, '_wp_page_template', true ) : '';
        if ( empty( $page_template ) ) {
            $page_template = 'default';
        }
        $post_parent = $post_id ? wp_get_post_parent_id( $post_id ) : 0;

        $restricted_layouts = array();

        foreach ( $field['layouts'] as $layout ) {
            if ( empty( $layout['visibility_rules'] ) ) {
                continue;
            }

            // Rules structure: OR of AND groups
            $layout_allowed = false;

            foreach ( $layout['visibility_rules'] as $group ) {
                if ( ! is_array( $group ) || empty( $group ) ) {
                    continue;
                }

                $group_matched = true;

                foreach ( $group as $rule ) {
                    $param    = $rule['param'] ?? '';
                    $operator = $rule['operator'] ?? '==';
                    $val      = $rule['value'] ?? '';

                    $rule_result = false;

                    switch ( $param ) {
                        case 'post_type':
                            $rule_result = ( $post_type === $val );
                            break;
                        case 'page_template':
                            $rule_result = ( $page_template === $val );
                            break;
                        case 'page_parent':
                            $rule_result = ( intval( $post_parent ) === intval( $val ) );
                            break;
                        case 'post':
                            $rule_result = ( intval( $post_id ) === intval( $val ) );
                            break;
                        case 'user_role':
                            $rule_result = in_array( $val, $user_roles, true );
                            break;
                        default:
                            $rule_result = true;
                            break;
                    }

                    if ( $operator === '!=' ) {
                        $rule_result = ! $rule_result;
                    }

                    if ( ! $rule_result ) {
                        $group_matched = false;
                        break; // Fail early in AND group
                    }
                }

                if ( $group_matched ) {
                    $layout_allowed = true;
                    break; // Pass early in OR groups
                }
            }

            if ( ! $layout_allowed ) {
                $restricted_layouts[] = $layout['name'];
            }
        }

        if ( ! empty( $restricted_layouts ) ) {
            // Append restricted layouts names so JS can remove them from layout picker
            $existing_restrictions = $field['wrapper']['data-tka-restricted-layouts'] ?? '';
            $all_restrictions = array_filter( array_merge( 
                explode( ',', $existing_restrictions ), 
                $restricted_layouts 
            ) );
            
            $field['wrapper']['data-tka-restricted-layouts'] = implode( ',', array_unique( $all_restrictions ) );
        }

        return $field;
    }
}

new TKA_ACF_Layout_Rules();
