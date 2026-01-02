<?php
/**
 * ALM ACF Adapter
 *
 * Provides an abstraction layer for registering ACF fields for devices.
 *
 * @package AssetLendingManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Adapter that adds custom ACF fields.
 */
class ALM_ACF_Device_Adapter {

	/**
	 * This functions is never used.
	 * It simply allow the translations of custom fields.
	 *
	 * @return void
	 */
	private function define_custom_field_labels() {
		define(
			'ALM_CUSTOM_FIELD_LABELS',
			array(
				__( 'Manufacturer', 'asset-lending-manager' ),
				__( 'Model', 'asset-lending-manager' ),
				__( 'Purchase date', 'asset-lending-manager' ),
				__( 'Cost', 'asset-lending-manager' ),
				__( 'Dimensions', 'asset-lending-manager' ),
				__( 'Weight', 'asset-lending-manager' ),
				__( 'Location', 'asset-lending-manager' ),
				__( 'Components', 'asset-lending-manager' ),
				__( 'User manual', 'asset-lending-manager' ),
				__( 'Technical data sheet', 'asset-lending-manager' ),
				__( 'Serial number', 'asset-lending-manager' ),
				__( 'External code', 'asset-lending-manager' ),
				__( 'Notes', 'asset-lending-manager' ),
			)
		);
	}

	/**
	 * Register device fields via ACF.
	 *
	 * @return void
	 */
	public function register_device_fields() {
		// Check if ACF is active.
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// BEGIN Custom fields list.
		acf_add_local_field_group(
			array(
				'key' => 'group_694a655eddefc',
				'title' => 'ALM Device Fields',
				'fields' => array(
					array(
						'key' => 'field_694a656243fca',
						'label' => 'Manufacturer',
						'name' => 'manufacturer',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'allow_in_bindings' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_694a65e643fcb',
						'label' => 'Model',
						'name' => 'model',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'allow_in_bindings' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_694a65fa43fcc',
						'label' => 'Purchase date',
						'name' => 'data_acquisto',
						'aria-label' => '',
						'type' => 'date_picker',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'display_format' => 'd/m/Y',
						'return_format' => 'd/m/Y',
						'first_day' => 1,
						'default_to_current_date' => 0,
						'allow_in_bindings' => 0,
					),
					array(
						'key' => 'field_694a665243fcd',
						'label' => 'Cost',
						'name' => 'cost',
						'aria-label' => '',
						'type' => 'number',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'min' => '',
						'max' => '',
						'allow_in_bindings' => 0,
						'placeholder' => '',
						'step' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_694a668743fce',
						'label' => 'Dimensions',
						'name' => 'dimensions',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'allow_in_bindings' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_694a66a443fcf',
						'label' => 'Weight',
						'name' => 'weight',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'allow_in_bindings' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_694a66be43fd0',
						'label' => 'Location',
						'name' => 'location',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'allow_in_bindings' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_694a66df43fd1',
						'label' => 'Components',
						'name' => 'components',
						'aria-label' => '',
						'type' => 'post_object',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'post_type' => array(
							0 => 'alm_device',
						),
						'post_status' => '',
						'taxonomy' => '',
						'return_format' => 'object',
						'multiple' => 1,
						'allow_null' => 0,
						'allow_in_bindings' => 0,
						'bidirectional' => 0,
						'ui' => 1,
						'bidirectional_target' => array(
						),
					),
					array(
						'key' => 'field_694a672043fd2',
						'label' => 'User manual',
						'name' => 'user_manual',
						'aria-label' => '',
						'type' => 'file',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'return_format' => 'array',
						'library' => 'all',
						'min_size' => '',
						'max_size' => '',
						'mime_types' => '',
						'allow_in_bindings' => 0,
					),
					array(
						'key' => 'field_694a675043fd3',
						'label' => 'Technical data sheet',
						'name' => 'technical_data_sheet',
						'aria-label' => '',
						'type' => 'file',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'return_format' => 'array',
						'library' => 'all',
						'min_size' => '',
						'max_size' => '',
						'mime_types' => '',
						'allow_in_bindings' => 0,
					),
					array(
						'key' => 'field_694a677143fd4',
						'label' => 'Serial number',
						'name' => 'serial_number',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'allow_in_bindings' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_694a677b43fd5',
						'label' => 'External code',
						'name' => 'external_code',
						'aria-label' => '',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'allow_in_bindings' => 0,
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_694a679543fd6',
						'label' => 'Notes',
						'name' => 'notes',
						'aria-label' => '',
						'type' => 'textarea',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'maxlength' => '',
						'allow_in_bindings' => 0,
						'rows' => '',
						'placeholder' => '',
						'new_lines' => '',
					),
				),
				'location' => array(
					array(
						array(
							'param' => 'post_type',
							'operator' => '==',
							'value' => 'alm_device',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
				'show_in_rest' => 0,
				'display_title' => '',
			)
		);
		// END Custom fields list.

	}
}
