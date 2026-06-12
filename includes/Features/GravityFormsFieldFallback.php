<?php

if (!class_exists('acf_field_forms_fallback') && class_exists('acf_field_select')) {
	class acf_field_forms_fallback extends \acf_field_select
	{
		public function initialize()
		{
			parent::initialize();
			$this->name = 'forms';
			$this->label = __('Gravity Forms', 'tka-wp-utils');
			$this->category = 'relational';
			$this->defaults = array_merge($this->defaults, [
				'multiple'      => 0,
				'allow_null'    => 0,
				'return_format' => 'id',
				'choices'       => [],
				'default_value' => '',
			]);
		}

		public function update_field($field)
		{
			// The parent select class expects 'choices' to be defined during save.
			// Since we don't render a choices input, we ensure it's set to an empty array.
			$field['choices'] = [];
			return $field;
		}

		public function render_field_settings($field)
		{
			acf_render_field_setting($field, [
				'label'			=> __('Return Value', 'acf'),
				'instructions'	=> __('Specify the returned value on front end', 'acf'),
				'type'			=> 'radio',
				'name'			=> 'return_format',
				'layout'		=> 'horizontal',
				'choices'		=> [
					'id'		=> __('Form ID', 'acf'),
					'object'	=> __('Form Object', 'acf'),
				]
			]);

			acf_render_field_setting($field, [
				'label'			=> __('Allow Null?', 'acf'),
				'instructions'	=> '',
				'type'			=> 'true_false',
				'name'			=> 'allow_null',
				'ui'			=> 1,
			]);

			acf_render_field_setting($field, [
				'label'			=> __('Select Multiple?', 'acf'),
				'instructions'	=> '',
				'type'			=> 'true_false',
				'name'			=> 'multiple',
				'ui'			=> 1,
			]);
		}
	}
}
