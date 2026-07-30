<?php
/**
 * Extension Name: ACF Dynamic Table
 * Description: Dynamic table field with fixed/locked rows and columns, prepopulated data, multi-level headers, file selectors, and custom styling options.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'acf/include_field_types', function() {

	class tka_acf_field_dynamic_table extends acf_field {

		public $name;
		public $label;
		public $category;
		public $defaults;

		public function initialize() {
			$this->name     = 'tka_dynamic_table';
			$this->label    = __( 'Dynamic Table (TKA)', 'tka-site-utilities' );
			$this->category = 'layout';
			$this->defaults = array(
				'use_header'          => 1,
				'header_type'         => 'single',
				'use_caption'         => 0,
				'lock_columns'        => 0,
				'fixed_columns_count' => 6,
				'min_columns'         => 1,
				'max_columns'         => '',
				'lock_rows'           => 0,
				'fixed_rows_count'    => 2,
				'min_rows'            => 1,
				'max_rows'            => '',
				'preset_headers'      => '',
				'preset_top_headers'  => '',
				'preset_sub_headers'  => '',
				'allow_header_edit'   => 1,
				'prepopulated_data'   => '',
				'return_format'       => 'array',
			);
		}

		public static function get_column_slug( $label, $index ) {
			$slug = sanitize_title( $label );
			if ( empty( $slug ) ) {
				return 'col-' . ( $index + 1 );
			}
			return 'col-' . $slug;
		}

		public static function get_leaf_column_types( $headers, $header_type = 'single' ) {
			$types = array();
			if ( $header_type === 'multi' && is_array( $headers ) && isset( $headers['top'] ) ) {
				$sub_rows = $headers['sub'] ?? array();
				$sub_idx  = 0;
				foreach ( $headers['top'] as $th ) {
					$rowspan  = is_array( $th ) ? intval( $th['rowspan'] ?? 1 ) : 1;
					$colspan  = is_array( $th ) ? intval( $th['colspan'] ?? 1 ) : 1;
					$top_type = is_array( $th ) ? ( $th['type'] ?? 'text' ) : 'text';

					if ( $rowspan > 1 ) {
						$types[] = $top_type;
					} else {
						for ( $c = 0; $c < $colspan; $c++ ) {
							$sub_item = $sub_rows[ $sub_idx ] ?? array();
							$sub_type = is_array( $sub_item ) ? ( $sub_item['type'] ?? 'text' ) : 'text';
							$types[]  = ( $top_type === 'file' ) ? 'file' : $sub_type;
							$sub_idx++;
						}
					}
				}
			} elseif ( is_array( $headers ) ) {
				foreach ( $headers as $th ) {
					$types[] = is_array( $th ) ? ( $th['type'] ?? 'text' ) : 'text';
				}
			}
			return $types;
		}

		public static function parse_top_headers( $str ) {
			if ( empty( $str ) ) {
				return array();
			}

			// Split by comma ONLY when NOT inside parentheses
			$items = preg_split( '/,\s*(?![^()]*\))/', str_replace( array( "\r\n", "\n" ), ',', trim( $str ) ) );
			if ( ! is_array( $items ) ) {
				return array();
			}

			$top = array();
			foreach ( $items as $item ) {
				$item = trim( $item );
				if ( empty( $item ) ) {
					continue;
				}
				$label   = $item;
				$colspan = 1;
				$rowspan = 0;
				$type    = 'text';

				if ( preg_match( '/^(.*?)\((.*?)\)$/', $item, $m ) ) {
					$label = trim( $m[1] );
					$meta  = $m[2];
					if ( preg_match( '/colspan\s*=\s*(\d+)/i', $meta, $cm ) ) {
						$colspan = intval( $cm[1] );
					}
					if ( preg_match( '/rowspan\s*=\s*(\d+)/i', $meta, $rm ) ) {
						$rowspan = intval( $rm[1] );
					}
					if ( preg_match( '/type\s*=\s*(file|text)/i', $meta, $tm ) ) {
						$type = strtolower( $tm[1] );
					}
				}

				if ( $rowspan === 0 ) {
					$rowspan = ( $colspan > 1 ) ? 1 : 2;
				}

				$top[] = array(
					'label'   => $label,
					'colspan' => max( 1, $colspan ),
					'rowspan' => max( 1, $rowspan ),
					'type'    => $type,
				);
			}
			return $top;
		}

		public static function parse_preset_headers( $headers_str ) {
			if ( empty( $headers_str ) ) {
				return array();
			}
			$lines = explode( "\n", str_replace( "\r\n", "\n", trim( $headers_str ) ) );
			if ( count( $lines ) > 1 ) {
				return array_map( 'trim', $lines );
			}
			return array_map( 'trim', explode( ',', $headers_str ) );
		}

		public static function parse_prepopulated_rows( $data_str ) {
			if ( empty( $data_str ) ) {
				return array();
			}

			// Try JSON decode first
			$json = json_decode( $data_str, true );
			if ( is_array( $json ) ) {
				return $json;
			}

			// Fallback: CSV / newline separated
			$rows  = array();
			$lines = explode( "\n", str_replace( "\r\n", "\n", trim( $data_str ) ) );
			foreach ( $lines as $line ) {
				if ( trim( $line ) === '' ) {
					continue;
				}
				$rows[] = array_map( 'trim', str_getcsv( $line ) );
			}
			return $rows;
		}

		public function render_field_settings( $field ) {
			// Structure settings
			acf_render_field_setting( $field, array(
				'label'        => __( 'Header Row', 'tka-site-utilities' ),
				'instructions' => __( 'Display a table header row?', 'tka-site-utilities' ),
				'type'         => 'true_false',
				'name'         => 'use_header',
				'ui'           => 1,
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Header Mode', 'tka-site-utilities' ),
				'instructions' => __( 'Single-level header or Multi-level header with merged category columns.', 'tka-site-utilities' ),
				'type'         => 'select',
				'name'         => 'header_type',
				'choices'      => array(
					'single' => __( 'Single Header Row', 'tka-site-utilities' ),
					'multi'  => __( 'Multi-level Header (Parent Categories + Sub-columns)', 'tka-site-utilities' ),
				),
				'conditions'   => array(
					array(
						array(
							'field'    => 'use_header',
							'operator' => '==',
							'value'    => '1',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Allow Header Editing in Page Editor', 'tka-site-utilities' ),
				'instructions' => __( 'Allow page editors to rename category titles, adjust sub-column counts (colspan), and set column cell types (Text / File Selector).', 'tka-site-utilities' ),
				'type'         => 'true_false',
				'name'         => 'allow_header_edit',
				'ui'           => 1,
				'default_value'=> 1,
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Preset Top Headers (Multi-level)', 'tka-site-utilities' ),
				'instructions' => __( 'Enter top category headers separated by commas. Format: Title (colspan=X, rowspan=Y, type=file|text). E.g. Raum (rowspan=2), Ansicht (rowspan=2, type=file), Bestuhlung (colspan=2), Grundriss (rowspan=2, type=file)', 'tka-site-utilities' ),
				'type'         => 'textarea',
				'name'         => 'preset_top_headers',
				'rows'         => 2,
				'conditions'   => array(
					array(
						array(
							'field'    => 'header_type',
							'operator' => '==',
							'value'    => 'multi',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Preset Sub Headers (Multi-level)', 'tka-site-utilities' ),
				'instructions' => __( 'Enter sub-column headers separated by commas (e.g. Bankett, Empfang)', 'tka-site-utilities' ),
				'type'         => 'textarea',
				'name'         => 'preset_sub_headers',
				'rows'         => 2,
				'conditions'   => array(
					array(
						array(
							'field'    => 'header_type',
							'operator' => '==',
							'value'    => 'multi',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Preset Header Titles (Single-level)', 'tka-site-utilities' ),
				'instructions' => __( 'Comma or line-separated list of default header column titles', 'tka-site-utilities' ),
				'type'         => 'textarea',
				'name'         => 'preset_headers',
				'rows'         => 2,
				'conditions'   => array(
					array(
						array(
							'field'    => 'header_type',
							'operator' => '!=',
							'value'    => 'multi',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Enable Caption', 'tka-site-utilities' ),
				'instructions' => __( 'Allow an optional table caption input field?', 'tka-site-utilities' ),
				'type'         => 'true_false',
				'name'         => 'use_caption',
				'ui'           => 1,
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Return Format', 'tka-site-utilities' ),
				'instructions' => __( 'Specify the returned value format in templates', 'tka-site-utilities' ),
				'type'         => 'select',
				'name'         => 'return_format',
				'choices'      => array(
					'array' => __( 'Structured Array (header, body, caption)', 'tka-site-utilities' ),
					'html'  => __( 'HTML Markup (<table>...</table>)', 'tka-site-utilities' ),
					'json'  => __( 'JSON String', 'tka-site-utilities' ),
				),
			) );

			// Column Rules
			acf_render_field_setting( $field, array(
				'label'        => __( 'Lock Column Count', 'tka-site-utilities' ),
				'instructions' => __( 'When locked, users in the post editor cannot add or remove columns.', 'tka-site-utilities' ),
				'type'         => 'true_false',
				'name'         => 'lock_columns',
				'ui'           => 1,
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Fixed Columns Count', 'tka-site-utilities' ),
				'instructions' => __( 'Exact number of sub-columns when column count is locked.', 'tka-site-utilities' ),
				'type'         => 'number',
				'name'         => 'fixed_columns_count',
				'default_value'=> 6,
				'min'          => 1,
				'conditions'   => array(
					array(
						array(
							'field'    => 'lock_columns',
							'operator' => '==',
							'value'    => '1',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Minimum Columns', 'tka-site-utilities' ),
				'instructions' => __( 'Minimum allowed columns when unlocked.', 'tka-site-utilities' ),
				'type'         => 'number',
				'name'         => 'min_columns',
				'default_value'=> 1,
				'min'          => 1,
				'conditions'   => array(
					array(
						array(
							'field'    => 'lock_columns',
							'operator' => '!=',
							'value'    => '1',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Maximum Columns', 'tka-site-utilities' ),
				'instructions' => __( 'Maximum allowed columns (leave blank for no limit).', 'tka-site-utilities' ),
				'type'         => 'number',
				'name'         => 'max_columns',
				'min'          => 1,
				'conditions'   => array(
					array(
						array(
							'field'    => 'lock_columns',
							'operator' => '!=',
							'value'    => '1',
						),
					),
				),
			) );

			// Row Rules
			acf_render_field_setting( $field, array(
				'label'        => __( 'Lock Row Count', 'tka-site-utilities' ),
				'instructions' => __( 'When locked, users in the post editor cannot add or remove rows.', 'tka-site-utilities' ),
				'type'         => 'true_false',
				'name'         => 'lock_rows',
				'ui'           => 1,
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Fixed Rows Count', 'tka-site-utilities' ),
				'instructions' => __( 'Exact number of rows when row count is locked.', 'tka-site-utilities' ),
				'type'         => 'number',
				'name'         => 'fixed_rows_count',
				'default_value'=> 2,
				'min'          => 1,
				'conditions'   => array(
					array(
						array(
							'field'    => 'lock_rows',
							'operator' => '==',
							'value'    => '1',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Minimum Rows', 'tka-site-utilities' ),
				'instructions' => __( 'Minimum allowed rows when unlocked.', 'tka-site-utilities' ),
				'type'         => 'number',
				'name'         => 'min_rows',
				'default_value'=> 1,
				'min'          => 1,
				'conditions'   => array(
					array(
						array(
							'field'    => 'lock_rows',
							'operator' => '!=',
							'value'    => '1',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Maximum Rows', 'tka-site-utilities' ),
				'instructions' => __( 'Maximum allowed rows (leave blank for no limit).', 'tka-site-utilities' ),
				'type'         => 'number',
				'name'         => 'max_rows',
				'min'          => 1,
				'conditions'   => array(
					array(
						array(
							'field'    => 'lock_rows',
							'operator' => '!=',
							'value'    => '1',
						),
					),
				),
			) );

			acf_render_field_setting( $field, array(
				'label'        => __( 'Prepopulated Row Data', 'tka-site-utilities' ),
				'instructions' => __( 'Prepopulate default row values using JSON array format or CSV lines (one row per line).', 'tka-site-utilities' ),
				'type'         => 'textarea',
				'name'         => 'prepopulated_data',
				'rows'         => 4,
			) );
		}

		public function input_admin_enqueue_scripts() {
			if ( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}

			$assets_url  = TKA_SITE_UTILITIES_URL . 'includes/AcfExtensions/assets/acf-dynamic-table/';
			$assets_path = TKA_SITE_UTILITIES_PATH . 'includes/AcfExtensions/assets/acf-dynamic-table/';

			$css_ver = file_exists( $assets_path . 'css/acf-dynamic-table.css' ) ? filemtime( $assets_path . 'css/acf-dynamic-table.css' ) . rand( 100, 999 ) : TKA_SITE_UTILITIES_VERSION;
			$js_ver  = file_exists( $assets_path . 'js/acf-dynamic-table.js' ) ? filemtime( $assets_path . 'js/acf-dynamic-table.js' ) . rand( 100, 999 ) : TKA_SITE_UTILITIES_VERSION;

			wp_enqueue_style( 'tka-acf-dynamic-table-css', $assets_url . 'css/acf-dynamic-table.css', array(), $css_ver );
			wp_enqueue_script( 'tka-acf-dynamic-table-js', $assets_url . 'js/acf-dynamic-table.js', array( 'jquery' ), $js_ver, true );
		}

		public function load_value( $value, $post_id, $field ) {
			if ( is_string( $value ) && ! empty( $value ) ) {
				$decoded = json_decode( $value, true );
				if ( is_array( $decoded ) ) {
					return $decoded;
				}
			}
			return $value;
		}

		public function prepare_field( $field ) {
			$value = $field['value'] ?? null;

			// If value is string (JSON or HTML), attempt decoding or fetch raw postmeta
			if ( is_string( $value ) && ! empty( $value ) ) {
				$decoded = json_decode( $value, true );
				if ( is_array( $decoded ) ) {
					$value = $decoded;
				} else {
					$post_id = get_the_ID();
					if ( $post_id ) {
						$meta_key = $field['name'] ?? $field['_name'] ?? '';
						if ( $meta_key ) {
							$raw_meta = get_post_meta( $post_id, $meta_key, true );
							if ( is_array( $raw_meta ) ) {
								$value = $raw_meta;
							}
						}
					}
				}
			}

			$header_type = $field['header_type'] ?? 'single';

			if ( empty( $value ) || ! is_array( $value ) ) {
				$value = array(
					'header'  => array(),
					'body'    => array(),
					'caption' => '',
				);
			}

			$has_header_data = ! empty( $value['header'] ) && is_array( $value['header'] );

			// Ensure header structure matches selected header_type
			if ( $header_type === 'multi' ) {
				$has_saved_multi = $has_header_data && ( isset( $value['header']['top'] ) || isset( $value['header']['sub'] ) );
				if ( ! $has_saved_multi ) {
					$top_preset = self::parse_top_headers( $field['preset_top_headers'] ?? '' );
					$sub_preset_labels = self::parse_preset_headers( $field['preset_sub_headers'] ?? '' );

					if ( empty( $top_preset ) ) {
						$top_preset = array(
							array( 'label' => __( 'Raum', 'tka-site-utilities' ), 'colspan' => 1, 'rowspan' => 2, 'type' => 'text' ),
							array( 'label' => __( 'Fläche', 'tka-site-utilities' ), 'colspan' => 1, 'rowspan' => 2, 'type' => 'text' ),
							array( 'label' => __( 'Ansicht', 'tka-site-utilities' ), 'colspan' => 1, 'rowspan' => 2, 'type' => 'file' ),
							array( 'label' => __( 'Bestuhlung', 'tka-site-utilities' ), 'colspan' => 2, 'rowspan' => 1, 'type' => 'text' ),
							array( 'label' => __( 'Grundriss', 'tka-site-utilities' ), 'colspan' => 1, 'rowspan' => 2, 'type' => 'file' ),
						);
					}

					$sub_preset = array();
					if ( ! empty( $sub_preset_labels ) ) {
						foreach ( $sub_preset_labels as $sl ) {
							$sub_preset[] = array( 'label' => $sl, 'type' => 'text' );
						}
					} else {
						$sub_preset = array(
							array( 'label' => __( 'Bankett', 'tka-site-utilities' ), 'type' => 'text' ),
							array( 'label' => __( 'Empfang', 'tka-site-utilities' ), 'type' => 'text' ),
						);
					}

					$value['header'] = array(
						'top' => $top_preset,
						'sub' => $sub_preset,
					);
				} else {
					if ( ! isset( $value['header']['sub'] ) || ! is_array( $value['header']['sub'] ) ) {
						$value['header']['sub'] = array();
					}
				}
			} else {
				if ( ! $has_header_data || isset( $value['header']['top'] ) ) {
					$preset_headers = self::parse_preset_headers( $field['preset_headers'] ?? '' );
					$cols_count     = ! empty( $field['lock_columns'] ) ? intval( $field['fixed_columns_count'] ?? 3 ) : 3;
					if ( ! empty( $preset_headers ) ) {
						$cols_count = max( $cols_count, count( $preset_headers ) );
					}

					$header = array();
					if ( ! empty( $field['use_header'] ) ) {
						for ( $c = 0; $c < $cols_count; $c++ ) {
							$lbl = ( isset( $preset_headers[ $c ] ) && $preset_headers[ $c ] !== '' ) ? $preset_headers[ $c ] : ( __( 'Column ', 'tka-site-utilities' ) . ( $c + 1 ) );
							$header[] = array(
								'label' => $lbl,
								'type'  => 'text',
							);
						}
					}
					$value['header'] = $header;
				}
			}

			// Calculate real target column count from headers
			$target_cols = 6;
			if ( $header_type === 'multi' && isset( $value['header']['top'] ) && is_array( $value['header']['top'] ) ) {
				$top_sum = 0;
				foreach ( $value['header']['top'] as $th_item ) {
					$top_sum += max( 1, intval( $th_item['colspan'] ?? 1 ) );
				}
				if ( $top_sum > 0 ) {
					$target_cols = $top_sum;
				}
			} elseif ( $header_type === 'single' && is_array( $value['header'] ) ) {
				$target_cols = max( 1, count( $value['header'] ) );
			}

			// Ensure body is populated with prepopulated rows or defaults if empty
			if ( empty( $value['body'] ) || ! is_array( $value['body'] ) ) {
				$prepopulated = self::parse_prepopulated_rows( $field['prepopulated_data'] ?? '' );
				if ( ! empty( $prepopulated ) ) {
					$value['body'] = $prepopulated;
				} else {
					$rows_count = ! empty( $field['lock_rows'] ) ? intval( $field['fixed_rows_count'] ?? 2 ) : 2;
					$body = array(
						array( 'Restaurant Arcade', '85m²', '', '60', '80', '' ),
						array( 'Gewölbekeller', '200m²', '', '60', '150', '' ),
					);
					$value['body'] = $body;
				}
			}

			// Ensure every body row matches target_cols
			if ( ! empty( $value['body'] ) && is_array( $value['body'] ) ) {
				foreach ( $value['body'] as &$r_cells ) {
					if ( is_array( $r_cells ) ) {
						if ( count( $r_cells ) < $target_cols ) {
							$r_cells = array_pad( $r_cells, $target_cols, '' );
						} elseif ( count( $r_cells ) > $target_cols ) {
							$r_cells = array_slice( $r_cells, 0, $target_cols );
						}
					}
				}
			}

			$field['value'] = $value;
			return $field;
		}

		public function render_field( $field ) {
			$value       = $field['value'];
			$use_header  = ! empty( $field['use_header'] );
			$header_type = $field['header_type'] ?? 'single';
			$use_caption = ! empty( $field['use_caption'] );

			$lock_columns  = ! empty( $field['lock_columns'] ) ? 1 : 0;
			$fixed_columns = intval( $field['fixed_columns_count'] ?? 3 );
			$min_columns   = intval( $field['min_columns'] ?? 1 );
			$max_columns   = intval( $field['max_columns'] ?? 0 );

			$lock_rows  = ! empty( $field['lock_rows'] ) ? 1 : 0;
			$fixed_rows = intval( $field['fixed_rows_count'] ?? 2 );
			$min_rows   = intval( $field['min_rows'] ?? 1 );
			$max_rows   = intval( $field['max_rows'] ?? 0 );

			$headers    = isset( $value['header'] ) && is_array( $value['header'] ) ? $value['header'] : array();
			$body       = isset( $value['body'] ) && is_array( $value['body'] ) ? $value['body'] : array();
			$caption    = isset( $value['caption'] ) ? $value['caption'] : '';
			$leaf_types = self::get_leaf_column_types( $headers, $header_type );

			echo '<div class="acf-dynamic-table-wrapper" ' .
				'data-field-name="' . esc_attr( $field['name'] ) . '" ' .
				'data-use-header="' . esc_attr( $use_header ? 1 : 0 ) . '" ' .
				'data-header-type="' . esc_attr( $header_type ) . '" ' .
				'data-allow-header-edit="1" ' .
				'data-use-caption="' . esc_attr( $use_caption ? 1 : 0 ) . '" ' .
				'data-lock-columns="' . esc_attr( $lock_columns ) . '" ' .
				'data-fixed-columns="' . esc_attr( $fixed_columns ) . '" ' .
				'data-min-columns="' . esc_attr( $min_columns ) . '" ' .
				'data-max-columns="' . esc_attr( $max_columns ) . '" ' .
				'data-lock-rows="' . esc_attr( $lock_rows ) . '" ' .
				'data-fixed-rows="' . esc_attr( $fixed_rows ) . '" ' .
				'data-min-rows="' . esc_attr( $min_rows ) . '" ' .
				'data-max-rows="' . esc_attr( $max_rows ) . '">';

			if ( $use_caption ) {
				echo '<div class="acf-dynamic-table-caption-wrapper">';
				echo '<label>' . esc_html__( 'Table Caption', 'tka-site-utilities' ) . '</label>';
				echo '<input type="text" class="acf-dynamic-table-caption-input" name="' . esc_attr( $field['name'] ) . '[caption]" value="' . esc_attr( $caption ) . '" placeholder="' . esc_attr__( 'Enter table caption...', 'tka-site-utilities' ) . '" />';
				echo '</div>';
			}

			echo '<div class="acf-dynamic-table-container">';
			echo '<table class="acf-dynamic-table">';

			// Render Header
			if ( $use_header ) {
				echo '<thead>';
				if ( $header_type === 'multi' && is_array( $headers ) && isset( $headers['top'] ) ) {
					// Top Header Row
					echo '<tr class="tr-header-top">';
					echo '<th class="th-row-actions" rowspan="2">';
					if ( $lock_rows ) {
						echo '<span class="dashicons dashicons-lock" title="' . esc_attr__( 'Rows are locked', 'tka-site-utilities' ) . '"></span>';
					}
					echo '</th>';

					foreach ( $headers['top'] as $c_idx => $th_data ) {
						$label   = is_array( $th_data ) ? ( $th_data['label'] ?? '' ) : $th_data;
						$colspan = is_array( $th_data ) ? intval( $th_data['colspan'] ?? 1 ) : 1;
						$rowspan = is_array( $th_data ) ? intval( $th_data['rowspan'] ?? ( $colspan > 1 ? 1 : 2 ) ) : 2;
						$type    = is_array( $th_data ) ? ( $th_data['type'] ?? 'text' ) : 'text';

						echo '<th class="th-data-col th-top-header col-idx-' . esc_attr( $c_idx ) . '" data-col-type="' . esc_attr( $type ) . '" colspan="' . esc_attr( $colspan ) . '" rowspan="' . esc_attr( $rowspan ) . '">';
						echo '<div class="acf-dynamic-table-header-cell">';
						
						// Main Row: Category Title + Delete Category Button
						echo '<div class="acf-dynamic-table-header-row-main">';
						echo '<input type="text" class="acf-dynamic-table-input input-label" name="' . esc_attr( $field['name'] ) . '[header][top][' . esc_attr( $c_idx ) . '][label]" value="' . esc_attr( $label ) . '" placeholder="' . esc_attr__( 'Category title', 'tka-site-utilities' ) . '" />';
						echo '<button type="button" class="acf-dynamic-table-btn btn-remove-col" title="' . esc_attr__( 'Remove Category', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-no-alt"></span></button>';
						echo '</div>';

						// Meta Row: Controls for Sub-columns vs Cell Type
						echo '<div class="acf-dynamic-table-header-row-meta">';
						if ( $rowspan === 1 ) {
							echo '<label>' . esc_html__( 'Sub-cols:', 'tka-site-utilities' ) . ' ';
							echo '<input type="number" min="1" max="20" class="acf-dynamic-table-span-input input-colspan" name="' . esc_attr( $field['name'] ) . '[header][top][' . esc_attr( $c_idx ) . '][colspan]" value="' . esc_attr( $colspan ) . '" title="' . esc_attr__( 'Number of sub-columns', 'tka-site-utilities' ) . '" />';
							echo '</label>';
							echo '<button type="button" class="acf-dynamic-table-btn btn-add-sub-col" title="' . esc_attr__( 'Add Sub-column', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-plus-alt2"></span> + Sub</button>';
							echo '<input type="hidden" class="select-rowspan" name="' . esc_attr( $field['name'] ) . '[header][top][' . esc_attr( $c_idx ) . '][rowspan]" value="1" />';
							echo '<input type="hidden" class="select-col-type" name="' . esc_attr( $field['name'] ) . '[header][top][' . esc_attr( $c_idx ) . '][type]" value="text" />';
						} else {
							echo '<select class="acf-dynamic-table-type-select select-col-type" name="' . esc_attr( $field['name'] ) . '[header][top][' . esc_attr( $c_idx ) . '][type]">';
							echo '<option value="text" ' . selected( $type, 'text', false ) . '>' . esc_html__( 'Text', 'tka-site-utilities' ) . '</option>';
							echo '<option value="file" ' . selected( $type, 'file', false ) . '>' . esc_html__( 'File Selector', 'tka-site-utilities' ) . '</option>';
							echo '</select>';
							echo '<button type="button" class="acf-dynamic-table-btn btn-add-sub-col" title="' . esc_attr__( 'Add Sub-column Group', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-plus-alt2"></span> + Sub</button>';
							echo '<input type="hidden" class="input-colspan" name="' . esc_attr( $field['name'] ) . '[header][top][' . esc_attr( $c_idx ) . '][colspan]" value="1" />';
							echo '<input type="hidden" class="select-rowspan" name="' . esc_attr( $field['name'] ) . '[header][top][' . esc_attr( $c_idx ) . '][rowspan]" value="2" />';
						}
						echo '</div>';

						echo '</div>';
						echo '</th>';
					}
					echo '</tr>';

					// Sub Header Row (ONLY for top headers that have rowspan === 1)
					echo '<tr class="tr-header-sub">';
					if ( isset( $headers['sub'] ) && is_array( $headers['sub'] ) ) {
						$sub_idx = 0;
						foreach ( $headers['top'] as $top_th ) {
							$rs = is_array( $top_th ) ? intval( $top_th['rowspan'] ?? 1 ) : 1;
							$cs = is_array( $top_th ) ? intval( $top_th['colspan'] ?? 1 ) : 1;

							if ( $rs === 1 ) {
								for ( $sub_c = 0; $sub_c < $cs; $sub_c++ ) {
									$sub_data = $headers['sub'][ $sub_idx ] ?? array();
									$label    = is_array( $sub_data ) ? ( $sub_data['label'] ?? '' ) : $sub_data;
									$type     = is_array( $sub_data ) ? ( $sub_data['type'] ?? 'text' ) : 'text';

									echo '<th class="th-data-col th-sub-header sub-col-idx-' . esc_attr( $sub_idx ) . '" data-col-type="' . esc_attr( $type ) . '">';
									echo '<div class="acf-dynamic-table-header-cell">';
									echo '<div class="acf-dynamic-table-header-row-main">';
									echo '<input type="text" class="acf-dynamic-table-input input-label" name="' . esc_attr( $field['name'] ) . '[header][sub][' . esc_attr( $sub_idx ) . '][label]" value="' . esc_attr( $label ) . '" placeholder="' . esc_attr__( 'Sub-column title', 'tka-site-utilities' ) . '" />';
									echo '<button type="button" class="acf-dynamic-table-btn btn-remove-sub-col" title="' . esc_attr__( 'Remove Sub-column', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-no-alt"></span></button>';
									echo '</div>';

									echo '<div class="acf-dynamic-table-header-row-meta">';
									echo '<select class="acf-dynamic-table-type-select select-col-type" name="' . esc_attr( $field['name'] ) . '[header][sub][' . esc_attr( $sub_idx ) . '][type]">';
									echo '<option value="text" ' . selected( $type, 'text', false ) . '>' . esc_html__( 'Text', 'tka-site-utilities' ) . '</option>';
									echo '<option value="file" ' . selected( $type, 'file', false ) . '>' . esc_html__( 'File Selector', 'tka-site-utilities' ) . '</option>';
									echo '</select>';
									echo '</div>';

									echo '</div>';
									echo '</th>';

									$sub_idx++;
								}
							}
						}
					}
					echo '</tr>';
				} else {
					// Single Header Row
					echo '<tr>';
					echo '<th class="th-row-actions">';
					if ( $lock_rows ) {
						echo '<span class="dashicons dashicons-lock" title="' . esc_attr__( 'Rows are locked', 'tka-site-utilities' ) . '"></span>';
					}
					echo '</th>';

					$header_list = is_array( $headers ) ? $headers : array();
					foreach ( $header_list as $c_idx => $th_val ) {
						$label = is_array( $th_val ) ? ( $th_val['label'] ?? '' ) : $th_val;
						$type  = is_array( $th_val ) ? ( $th_val['type'] ?? 'text' ) : 'text';

						echo '<th class="th-data-col col-idx-' . esc_attr( $c_idx ) . '" data-col-type="' . esc_attr( $type ) . '">';
						echo '<div class="acf-dynamic-table-header-cell">';
						echo '<div class="acf-dynamic-table-header-row-main">';
						echo '<input type="text" class="acf-dynamic-table-input" name="' . esc_attr( $field['name'] ) . '[header][' . esc_attr( $c_idx ) . '][label]" value="' . esc_attr( $label ) . '" />';
						echo '<button type="button" class="acf-dynamic-table-btn btn-remove-col" title="' . esc_attr__( 'Remove Column', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-no-alt"></span></button>';
						echo '</div>';

						echo '<div class="acf-dynamic-table-header-row-meta">';
						echo '<select class="acf-dynamic-table-type-select select-col-type" name="' . esc_attr( $field['name'] ) . '[header][' . esc_attr( $c_idx ) . '][type]">';
						echo '<option value="text" ' . selected( $type, 'text', false ) . '>' . esc_html__( 'Text', 'tka-site-utilities' ) . '</option>';
						echo '<option value="file" ' . selected( $type, 'file', false ) . '>' . esc_html__( 'File Selector', 'tka-site-utilities' ) . '</option>';
						echo '</select>';
						echo '</div>';

						echo '</div>';
						echo '</th>';
					}
					echo '</tr>';
				}
				echo '</thead>';
			}

			// Render Body Rows
			echo '<tbody>';
			foreach ( $body as $r_idx => $row_cells ) {
				echo '<tr>';
				echo '<td class="td-row-actions">';
				echo '<div class="acf-dynamic-table-row-controls">';
				echo '<span class="acf-dynamic-table-row-index">' . esc_html( $r_idx + 1 ) . '</span> ';
				echo '<button type="button" class="acf-dynamic-table-btn btn-move-row-up" title="' . esc_attr__( 'Move Up', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-arrow-up-alt2"></span></button>';
				echo '<button type="button" class="acf-dynamic-table-btn btn-move-row-down" title="' . esc_attr__( 'Move Down', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-arrow-down-alt2"></span></button>';
				if ( ! $lock_rows ) {
					echo '<button type="button" class="acf-dynamic-table-btn btn-remove-row" title="' . esc_attr__( 'Remove Row', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-no-alt"></span></button>';
				}
				echo '</div>';
				echo '</td>';

				if ( is_array( $row_cells ) ) {
					foreach ( $row_cells as $c_idx => $cell_val ) {
						$is_file = ( ( $leaf_types[ $c_idx ] ?? 'text' ) === 'file' );
						echo '<td class="td-data-cell col-idx-' . esc_attr( $c_idx ) . '">';
						if ( $is_file ) {
							$file_url = trim( (string) $cell_val );
							$filename = ! empty( $file_url ) ? basename( parse_url( $file_url, PHP_URL_PATH ) ) : '';
							$has_file = ! empty( $file_url );
							$cell_class = $has_file ? 'has-file' : 'empty';

							echo '<div class="acf-dynamic-table-file-cell ' . esc_attr( $cell_class ) . '">';
							echo '<input type="hidden" class="acf-dynamic-table-input file-url-input" name="' . esc_attr( $field['name'] ) . '[body][' . esc_attr( $r_idx ) . '][' . esc_attr( $c_idx ) . ']" value="' . esc_attr( $file_url ) . '" />';
							echo '<button type="button" class="button button-small btn-select-file" title="' . esc_attr( $has_file ? $file_url : __( 'Select File', 'tka-site-utilities' ) ) . '">';
							echo '<span class="dashicons dashicons-paperclip"></span>';
							echo '<span class="file-name-label">' . esc_html( $has_file ? $filename : __( 'Add File', 'tka-site-utilities' ) ) . '</span>';
							echo '</button>';
							echo '<button type="button" class="acf-dynamic-table-btn btn-clear-file" title="' . esc_attr__( 'Clear File', 'tka-site-utilities' ) . '"><span class="dashicons dashicons-no-alt"></span></button>';
							echo '</div>';
						} else {
							echo '<input type="text" class="acf-dynamic-table-input" name="' . esc_attr( $field['name'] ) . '[body][' . esc_attr( $r_idx ) . '][' . esc_attr( $c_idx ) . ']" value="' . esc_attr( $cell_val ) . '" />';
						}
						echo '</td>';
					}
				}
				echo '</tr>';
			}
			echo 'tbody';
			echo '</table>';
			echo '</div>'; // .acf-dynamic-table-container

			// Render Toolbar
			echo '<div class="acf-dynamic-table-toolbar">';
			if ( ! $lock_rows ) {
				echo '<button type="button" class="button btn-add-row"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__( 'Add Row', 'tka-site-utilities' ) . '</button>';
			} else {
				echo '<span class="acf-dynamic-table-lock-badge"><span class="dashicons dashicons-lock"></span> ' . esc_html__( 'Rows count is locked', 'tka-site-utilities' ) . '</span>';
			}

			if ( $header_type === 'multi' ) {
				echo '<button type="button" class="button btn-add-category"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__( 'Add Column Category', 'tka-site-utilities' ) . '</button>';
				echo '<button type="button" class="button btn-toggle-column-settings"><span class="dashicons dashicons-admin-generic"></span> ' . esc_html__( 'Column Cell Types', 'tka-site-utilities' ) . '</button>';
			} else {
				if ( ! $lock_columns ) {
					echo '<button type="button" class="button btn-add-col"><span class="dashicons dashicons-plus-alt2"></span> ' . esc_html__( 'Add Column', 'tka-site-utilities' ) . '</button>';
				} else {
					echo '<span class="acf-dynamic-table-lock-badge"><span class="dashicons dashicons-lock"></span> ' . esc_html__( 'Columns count is locked', 'tka-site-utilities' ) . '</span>';
				}
			}
			echo '</div>'; // .acf-dynamic-table-toolbar

			echo '</div>'; // .acf-dynamic-table-wrapper
		}

		public function update_value( $value, $post_id, $field ) {
			if ( empty( $value ) || ! is_array( $value ) ) {
				if ( is_string( $value ) && ! empty( $value ) ) {
					$decoded = json_decode( $value, true );
					if ( is_array( $decoded ) ) {
						$value = $decoded;
					}
				}
				if ( empty( $value ) || ! is_array( $value ) ) {
					return '';
				}
			}

			$clean_value = array(
				'header'  => array(),
				'body'    => array(),
				'caption' => '',
			);

			if ( isset( $value['caption'] ) && is_string( $value['caption'] ) ) {
				$clean_value['caption'] = sanitize_text_field( $value['caption'] );
			}

			if ( ! empty( $value['header'] ) && is_array( $value['header'] ) ) {
				if ( isset( $value['header']['top'] ) || isset( $value['header']['sub'] ) ) {
					// Multi-level Header
					$clean_header = array( 'top' => array(), 'sub' => array() );
					if ( ! empty( $value['header']['top'] ) && is_array( $value['header']['top'] ) ) {
						foreach ( $value['header']['top'] as $top_cell ) {
							$clean_header['top'][] = array(
								'label'   => sanitize_text_field( $top_cell['label'] ?? '' ),
								'colspan' => max( 1, intval( $top_cell['colspan'] ?? 1 ) ),
								'rowspan' => max( 1, intval( $top_cell['rowspan'] ?? 1 ) ),
								'type'    => sanitize_text_field( $top_cell['type'] ?? 'text' ),
							);
						}
					}
					if ( ! empty( $value['header']['sub'] ) && is_array( $value['header']['sub'] ) ) {
						foreach ( $value['header']['sub'] as $sub_cell ) {
							$clean_header['sub'][] = array(
								'label' => sanitize_text_field( $sub_cell['label'] ?? '' ),
								'type'  => sanitize_text_field( $sub_cell['type'] ?? 'text' ),
							);
						}
					}
					$clean_value['header'] = $clean_header;
				} else {
					// Single Header
					foreach ( $value['header'] as $th ) {
						$label = is_array( $th ) ? ( $th['label'] ?? '' ) : $th;
						$type  = is_array( $th ) ? ( $th['type'] ?? 'text' ) : 'text';
						$clean_value['header'][] = array(
							'label' => sanitize_text_field( $label ),
							'type'  => sanitize_text_field( $type ),
						);
					}
				}
			}

			if ( ! empty( $value['body'] ) && is_array( $value['body'] ) ) {
				foreach ( $value['body'] as $row ) {
					if ( is_array( $row ) ) {
						$clean_row = array();
						foreach ( $row as $cell ) {
							$clean_row[] = wp_kses_post( $cell );
						}
						$clean_value['body'][] = $clean_row;
					}
				}
			}

			return $clean_value;
		}

		public function format_value( $value, $post_id, $field ) {
			if ( empty( $value ) || ! is_array( $value ) ) {
				return array();
			}

			// In WP Admin editor, always return the raw structured array so ACF form rendering receives raw data
			if ( is_admin() && ! wp_doing_ajax() ) {
				return $value;
			}

			$format = $field['return_format'] ?? 'array';
			if ( $format === 'json' ) {
				return wp_json_encode( $value );
			}

			if ( $format === 'html' ) {
				$html = '<div class="tka-table-responsive" style="width:100%; overflow-x:auto;"><table class="tka-dynamic-table" style="min-width:900px; width:100%; border-collapse:collapse;">';

				if ( ! empty( $value['caption'] ) ) {
					$html .= '<caption>' . esc_html( $value['caption'] ) . '</caption>';
				}

				// Collect leaf column labels & types
				$leaf_labels = array();
				$leaf_types  = array();

				if ( ! empty( $value['header'] ) && is_array( $value['header'] ) ) {
					$header_type = ( isset( $value['header']['top'] ) || isset( $value['header']['sub'] ) ) ? 'multi' : 'single';
					$leaf_types  = self::get_leaf_column_types( $value['header'], $header_type );

					$html .= '<thead>';
					if ( $header_type === 'multi' ) {
						$top_rows = $value['header']['top'] ?? array();
						$sub_rows = $value['header']['sub'] ?? array();

						// Top Row
						$html .= '<tr class="tr-header-top">';
						$sub_idx_offset = 0;
						foreach ( $top_rows as $c_idx => $th_item ) {
							$label   = is_array( $th_item ) ? ( $th_item['label'] ?? '' ) : $th_item;
							$colspan = is_array( $th_item ) ? intval( $th_item['colspan'] ?? 1 ) : 1;
							$rowspan = is_array( $th_item ) ? intval( $th_item['rowspan'] ?? 1 ) : 1;

							$col_slug  = self::get_column_slug( $label, $c_idx );
							$data_attr = str_replace( 'col-', '', $col_slug );

							$html .= '<th colspan="' . esc_attr( $colspan ) . '" rowspan="' . esc_attr( $rowspan ) . '" class="' . esc_attr( $col_slug . ' col-idx-' . $c_idx ) . '" data-col="' . esc_attr( $data_attr ) . '">';
							$html .= esc_html( $label );
							$html .= '</th>';

							if ( $rowspan > 1 ) {
								$leaf_labels[ $sub_idx_offset ] = $label;
								$sub_idx_offset++;
							}
						}
						$html .= '</tr>';

						// Sub Row
						if ( ! empty( $sub_rows ) ) {
							$html .= '<tr class="tr-header-sub">';
							$sub_render_idx = 0;
							foreach ( $top_rows as $top_item ) {
								$rs = is_array( $top_item ) ? intval( $top_item['rowspan'] ?? 1 ) : 1;
								$cs = is_array( $top_item ) ? intval( $top_item['colspan'] ?? 1 ) : 1;

								if ( $rs === 1 ) {
									for ( $sc = 0; $sc < $cs; $sc++ ) {
										$th_sub    = $sub_rows[ $sub_render_idx ] ?? array();
										$sub_label = is_array( $th_sub ) ? ( $th_sub['label'] ?? '' ) : $sub_label;
										$col_slug  = self::get_column_slug( $sub_label, $sub_render_idx );
										$data_attr = str_replace( 'col-', '', $col_slug );

										while ( isset( $leaf_labels[ $sub_idx_offset ] ) ) {
											$sub_idx_offset++;
										}
										$leaf_labels[ $sub_idx_offset ] = $sub_label;

										$html .= '<th class="' . esc_attr( $col_slug . ' sub-col-idx-' . $sub_render_idx ) . '" data-col="' . esc_attr( $data_attr ) . '">';
										$html .= esc_html( $sub_label );
										$html .= '</th>';

										$sub_render_idx++;
										$sub_idx_offset++;
									}
								}
							}
							$html .= '</tr>';
						}
					} else {
						// Single Header
						$html .= '<tr>';
						foreach ( $value['header'] as $c_idx => $th ) {
							$label = is_array( $th ) ? ( $th['label'] ?? '' ) : $th;
							$leaf_labels[ $c_idx ] = $label;

							$col_slug  = self::get_column_slug( $label, $c_idx );
							$data_attr = str_replace( 'col-', '', $col_slug );

							$html .= '<th class="' . esc_attr( $col_slug . ' col-idx-' . $c_idx ) . '" data-col="' . esc_attr( $data_attr ) . '">';
							$html .= esc_html( $label );
							$html .= '</th>';
						}
						$html .= '</tr>';
					}
					$html .= '</thead>';
				}

				if ( ! empty( $value['body'] ) && is_array( $value['body'] ) ) {
					$html .= '<tbody>';
					foreach ( $value['body'] as $row ) {
						if ( is_array( $row ) ) {
							$html .= '<tr>';
							foreach ( $row as $c_idx => $cell ) {
								$col_label = $leaf_labels[ $c_idx ] ?? ( 'Column ' . ( $c_idx + 1 ) );
								$col_type  = $leaf_types[ $c_idx ] ?? 'text';
								$col_slug  = self::get_column_slug( $col_label, $c_idx );
								$data_attr = str_replace( 'col-', '', $col_slug );

								$cell_class = esc_attr( $col_slug . ' col-idx-' . $c_idx );
								$cell_data  = esc_attr( $data_attr );

								$html .= '<td class="' . $cell_class . '" data-col="' . $cell_data . '">';

								$cell_trim = trim( (string) $cell );
								if ( $col_type === 'file' || ( ! empty( $cell_trim ) && ( preg_match( '#^https?://#i', $cell_trim ) || preg_match( '#^/#i', $cell_trim ) ) ) ) {
									if ( ! empty( $cell_trim ) ) {
										$link_text = esc_html( $col_label );
										if ( strpos( strtolower( $col_slug ), 'ansicht' ) !== false ) {
											$link_text = __( 'Ansehen', 'tka-site-utilities' );
										} elseif ( strpos( strtolower( $col_slug ), 'grundriss' ) !== false ) {
											$link_text = __( 'Download', 'tka-site-utilities' );
										}
										$html .= '<a href="' . esc_url( $cell_trim ) . '" target="_blank" class="tka-table-file-link">' . $link_text . '</a>';
									}
								} else {
									$html .= wp_kses_post( $cell );
								}

								$html .= '</td>';
							}
							$html .= '</tr>';
						}
					}
					$html .= '</tbody>';
				}

				$html .= '</table></div>';
				return $html;
			}

			return $value;
		}
	}

	new tka_acf_field_dynamic_table();
} );
