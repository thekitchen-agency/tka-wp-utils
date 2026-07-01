<?php
/*
 * Extension Name: Gravity Forms Field
 * Description: Enables a Gravity Form Dropdown Selector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!class_exists('ACFGravityformsField\Field')) {
	add_filter('acf/load_field/type=forms', 'tka_site_utilities_gf_fallback_load_field');
	add_filter('acf/format_value', 'tka_site_utilities_gf_fallback_format_value', 10, 3);
	add_action('acf/include_field_types', 'tka_site_utilities_gf_fallback_register_field_type');
}

function tka_site_utilities_gf_fallback_load_field(array $field): array
{
	$field['type'] = 'select';
	$field['original_type'] = 'forms';
	$field['ui'] = 1; // Enable Select2 search UI
	$field['ajax'] = 0; // Fix: Prevent undefined array key 'ajax' error in acf_field_select->render_field
	$field['choices'] = [];

	if (class_exists('GFAPI')) {
		$forms = \GFAPI::get_forms(true, false, 'title');
		if (!empty($forms)) {
			foreach ($forms as $form) {
				$field['choices'][$form['id']] = $form['title'];
			}
		}
	}

	return $field;
}

function tka_site_utilities_gf_fallback_format_value($value, $post_id, array $field)
{
	if (isset($field['original_type']) && $field['original_type'] === 'forms') {
		if (empty($value)) {
			return $value;
		}

		$return_format = $field['return_format'] ?? 'id';
		if ($return_format === 'id') {
			return is_array($value) ? array_map('intval', $value) : (int) $value;
		}

		if (class_exists('GFAPI')) {
			if (is_array($value)) {
				$formatted = [];
				foreach ($value as $val) {
					$form = \GFAPI::get_form($val);
					if ($form && !is_wp_error($form)) {
						$formatted[] = $form;
					}
				}
				return $formatted;
			} else {
				$form = \GFAPI::get_form($value);
				return ($form && !is_wp_error($form)) ? $form : false;
			}
		}
	}

	return $value;
}

function tka_site_utilities_gf_fallback_register_field_type(): void
{
	if (class_exists('acf_field_select') && !class_exists('acf_field_forms_fallback', false)) {
		class acf_field_forms_fallback extends \acf_field_select
		{
			public function initialize()
			{
				parent::initialize();
				$this->name = 'forms';
				$this->label = __('Gravity Forms', 'tka-site-utilities');
				$this->category = 'relational';
				$this->defaults = array_merge($this->defaults, [
					'multiple' => 0,
					'allow_null' => 0,
					'return_format' => 'id',
					'choices' => [],
					'default_value' => '',
				]);
			}

			public function update_field($field)
			{
				$field['choices'] = [];
				return $field;
			}

			public function render_field_settings($field)
			{
				acf_render_field_setting($field, [
					'label' => __('Return Value', 'tka-site-utilities'),
					'instructions' => __('Specify the returned value on front end', 'tka-site-utilities'),
					'type' => 'radio',
					'name' => 'return_format',
					'layout' => 'horizontal',
					'choices' => [
						'id' => __('Form ID', 'tka-site-utilities'),
						'object' => __('Form Object', 'tka-site-utilities'),
					]
				]);

				acf_render_field_setting($field, [
					'label' => __('Allow Null?', 'tka-site-utilities'),
					'instructions' => '',
					'type' => 'true_false',
					'name' => 'allow_null',
					'ui' => 1,
				]);

				acf_render_field_setting($field, [
					'label' => __('Select Multiple?', 'tka-site-utilities'),
					'instructions' => '',
					'type' => 'true_false',
					'name' => 'multiple',
					'ui' => 1,
				]);
			}
		}
		acf_register_field_type('acf_field_forms_fallback');
	}
}
