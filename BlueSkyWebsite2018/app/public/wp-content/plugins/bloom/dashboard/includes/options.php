<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//Array of all sections. All sections will be added into sidebar navigation except for the 'header' section.
$all_sections = array(
	'optin'  => array(
		'title'    => esc_html__( 'Optin Configuration', 'bloom' ),
		'contents' => array(
			'setup'   => esc_html__( 'Setup', 'bloom' ),
			'premade' => esc_html__( 'Premade Layouts', 'bloom' ),
			'design'  => esc_html__( 'Design', 'bloom' ),
			'display' => esc_html__( 'Display Settings', 'bloom' ),
			'success_action' => esc_html__( 'Success Action', 'bloom' )
		),
	),
	'header' => array(
		'contents' => array(
			'settings'     => esc_html__( 'Bloom Settings', 'bloom' ),
			'updates'      => esc_html__( 'Bloom Updates', 'bloom' ),
			'stats'        => esc_html__( 'Statistics', 'bloom' ),
			'accounts'     => esc_html__( 'Email Accounts', 'bloom' ),
			'importexport' => esc_html__( 'Import & Export', 'bloom' ),
			'home'         => esc_html__( 'Optin Forms', 'bloom' ),
			'edit_account' => esc_html__( 'Edit Account', 'bloom' ),
		),
	),
);

$providers       = ET_Core_API_Email_Providers::instance();
$email_providers = $providers->names_by_slug();

$name_field_only_support = array_keys( $providers->names_by_slug( 'all', 'name_field_only' ) );
$last_name_field_support = array_diff( array_keys( $email_providers ), $name_field_only_support );
$custom_fields_support   = array_keys( $providers->names_by_slug( 'all', 'custom_fields' ) );

// Sort alphabetically.
ksort( $email_providers );

// add the Select One... option as first option now that the list has been alpha sorted
$email_providers = array(
	'empty' => esc_html__( 'Select One...', 'bloom' ),
) + $email_providers;

// add this option to the bottom of the list always
$email_providers['custom_html'] = esc_html__( 'Custom HTML Form', 'bloom' );


/**
 * Array of all options
 * General format for options:
 * '<option_name>' => array(
 *							'type' => ...,
 *							'name' => ...,
 *							'default' => ...,
 *							'validation_type' => ...,
 *							etc
 *						)
 * <option_name> - just an identifier to add the option into $assigned_options array
 * Array of parameters may contain different attributes depending on option type.
 * 'type' is the required attribute for all options. All other attributes depends on the option type.
 * 'validation_type' and 'name' are required attribute for the option which should be saved into DataBase.
 *
 */
$dashboard_options_all = array(
	'optin_name' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Optin name', 'bloom' ),
		),

		'option' => array(
			'type'            => 'text',
			'rows'            => '1',
			'name'            => 'optin_name',
			'placeholder'     => esc_html__( 'MyNewOptin', 'bloom' ),
			'default'         => esc_html__( 'MyNewOptin', 'bloom' ),
			'validation_type' => 'simple_text',
		),
	),

	'form_integration' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Form Integration', 'bloom' ),
			'class' => 'et_dashboard_child_hidden',
		),
		'email_provider' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Select Email Provider', 'bloom' ),
			'name'            => 'email_provider',
			'value'           => $email_providers,
			'default'         => 'empty',
			'conditional'     => 'mailchimp_account#aweber_account#constant_contact_account#custom_html#display_name#name_fields#enable_dbl_optin#custom_fields_section',
			'validation_type' => 'simple_text',
			'class'           => 'et_dashboard_select_provider',
		),
		'select_account' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Select Account', 'bloom' ),
			'name'            => 'account_name',
			'value'           => array(
				'empty'       => esc_html__( 'Select One...', 'bloom' ),
				'add_account' => esc_html__( 'Add Account', 'bloom' ) ),
			'default'         => 'empty',
			'validation_type' => 'simple_text',
			'class'           => 'et_dashboard_select_account',
		),
		'email_list' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Select Email List', 'bloom' ),
			'name'            => 'email_list',
			'value'           => array(
				'empty' => esc_html__( 'Select One...', 'bloom' )
			),
			'default'         => 'empty',
			'validation_type' => 'simple_text',
			'class'           => 'et_dashboard_select_list',
		),
		'custom_html' => array(
			'type'            => 'text',
			'rows'            => '4',
			'name'            => 'custom_html',
			'placeholder'     => esc_html__( 'Insert HTML', 'bloom' ),
			'default'         => '',
			'display_if'      => 'custom_html',
			'validation_type' => 'html',
		),
		'ip_address' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Include IP Address', 'bloom' ),
			'name'            => 'ip_address',
			'default'         => 'true',
			'validation_type' => 'simple_text',
			'hint_text'       => esc_html__( "Include the subscriber's ip address in the data sent to your email provider.", 'bloom' ),
		),
		'enable_dbl_optin' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Enable Double Optin', 'bloom' ),
			'name'            => 'enable_dbl_optin',
			'default'         => false,
			'display_if'      => 'icontact',
			'conditional'     => 'message_id',
			'validation_type' => 'boolean',
			'hint_text'       => esc_html__( 'Enables Double optin', 'bloom' ),
		),
		'message_id' => array(
			'type'            => 'input_field',
			'subtype'         => 'text',
			'placeholder'     => '',
			'title'           => esc_html__( 'Message ID', 'bloom' ),
			'name'            => 'message_id',
			'default'         => '',
			'display_if'      => 'true',
			'validation_type' => 'simple_text',
			'hint_text'       => esc_html__( 'ID of the Confirmation Message which will be sent to subscribers', 'bloom' ),
		),
	),

	'optin_title' => array(
		'section_start' => array(
			'type'     => 'section_start',
			'title'    => esc_html__( 'Optin title', 'bloom' ),
			'subtitle' => esc_html__( 'No title will appear if left blank', 'bloom' ),
		),

		'option' => array(
			'type'            => 'text',
			'rows'            => '1',
			'name'            => 'optin_title',
			'class'           => 'et_dashboard_optin_title et_dashboard_mce',
			'placeholder'     => esc_html__( 'Insert Text', 'bloom' ),
			'default'         => esc_html__( 'Subscribe To Our Newsletter', 'bloom' ),
			'validation_type' => 'html',
			'is_wpml_string'  => true,
		),
	),

	'optin_message' => array(
		'section_start' => array(
			'type'     => 'section_start',
			'title'    => esc_html__( 'Optin message', 'bloom' ),
			'subtitle' => esc_html__( 'No message will appear if left blank', 'bloom' ),
		),

		'option' => array(
			'type'            => 'text',
			'rows'            => '3',
			'name'            => 'optin_message',
			'class'           => 'et_dashboard_optin_message et_dashboard_mce',
			'placeholder'     => esc_html__( 'Insert Text', 'bloom' ),
			'default'         => esc_html__( 'Join our mailing list to receive the latest news and updates from our team.', 'bloom' ),
			'validation_type' => 'html',
			'is_wpml_string'  => true,
		),
	),

	'image_settings' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Image Settings', 'bloom' ),
		),
		'image_orientation' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Image Orientation', 'bloom' ),
			'name'            => 'image_orientation',
			'value'           => array(
				'no_image' => esc_html__( 'No Image', 'bloom' ),
				'above'    => esc_html__( 'Image Above Text', 'bloom' ),
				'below'    => esc_html__( 'Image Below Text', 'bloom' ),
				'right'    => esc_html__( 'Image Right of Text', 'bloom' ),
				'left'     => esc_html__( 'Image Left of Text', 'bloom' ),
			),
			'default'         => 'no_image',
			'conditional'     => 'image_upload',
			'validation_type' => 'simple_text',
			'class'           => 'et_bloom_hide_for_widget et_dashboard_image_orientation',
		),
		'image_orientation_widget' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Image Orientation', 'bloom' ),
			'name'            => 'image_orientation_widget',
			'value'           => array(
				'no_image' => esc_html__( 'No Image', 'bloom' ),
				'above'    => esc_html__( 'Image Above Text', 'bloom' ),
				'below'    => esc_html__( 'Image Below Text', 'bloom' ),
			),
			'default'         => 'no_image',
			'conditional'     => 'image_upload',
			'validation_type' => 'simple_text',
			'class'           => 'et_bloom_widget_only_option et_dashboard_image_orientation_widget',
		),
	),

	'image_upload' => array(
		'section_start' => array(
			'type'       => 'section_start',
			'name'       => 'image_upload',
			'class'      => 'et_no_top_space',
			'display_if' => 'above#below#right#left',
		),
		'image_url' => array(
			'type'            => 'image_upload',
			'title'           => esc_html__( 'Image URL', 'bloom' ),
			'name'            => 'image_url',
			'class'           => 'et_dashboard_upload_image',
			'button_text'     => esc_html__( 'Upload an Image', 'bloom' ),
			'wp_media_title'  => esc_html__( 'Choose an Optin Image', 'bloom' ),
			'wp_media_button' => esc_html__( 'Set as Optin Image', 'bloom' ),
			'validation_type' => 'simple_array',
		),
		'image_animation' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Image Load-In Animation', 'bloom' ),
			'name'            => 'image_animation',
			'value'           => array(
				'no_animation' => esc_html__( 'No Animation', 'bloom' ),
				'fadein'       => esc_html__( 'Fade In', 'bloom' ),
				'slideright'   => esc_html__( 'Slide Right', 'bloom' ),
				'slidedown'    => esc_html__( 'Slide Down', 'bloom' ),
				'slideup'      => esc_html__( 'Slide Up', 'bloom' ),
				'lightspeedin' => esc_html__( 'Light Speed', 'bloom' ),
				'zoomin'       => esc_html__( 'Zoom In', 'bloom' ),
				'flipinx'      => esc_html__( 'Flip', 'bloom' ),
				'bounce'       => esc_html__( 'Bounce', 'bloom' ),
				'swing'        => esc_html__( 'Swing', 'bloom' ),
				'tada'         => esc_html__( 'Tada!', 'bloom' ),
			),
			'hint_text'       => esc_html__( 'Define the animation that is used to load the image', 'bloom' ),
			'default'         => 'slideup',
			'validation_type' => 'simple_text',
		),
		'hide_mobile' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Hide image on mobile', 'bloom' ),
			'name'            => 'hide_mobile',
			'default'         => false,
			'validation_type' => 'boolean',
		),
	),

	'form_setup' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Form setup', 'bloom' ),
		),
		'form_orientation' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Form Orientation', 'bloom' ),
			'name'            => 'form_orientation',
			'value'           => array(
				'bottom' => esc_html__( 'Form On Bottom', 'bloom' ),
				'right'  => esc_html__( 'Form On Right', 'bloom' ),
				'left'   => esc_html__( 'Form On Left', 'bloom' ),
			),
			'default'         => 'bottom',
			'validation_type' => 'simple_text',
			'class'           => 'et_bloom_hide_for_widget et_dashboard_form_orientation',
		),
		'display_name' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Display Name Field', 'bloom' ),
			'name'            => 'display_name',
			'class'           => 'et_dashboard_name_checkbox',
			'default'         => false,
			'conditional'     => 'single_name_text',
			'validation_type' => 'boolean',
			'display_if'      => implode( '#', $name_field_only_support ),
		),
		'name_fields' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Name Field(s)', 'bloom' ),
			'name'            => 'name_fields',
			'class'           => 'et_dashboard_name_fields',
			'value'           => array(
				'no_name'         => esc_html__( 'No Name Field', 'bloom' ),
				'single_name'     => esc_html__( 'Single Name Field', 'bloom' ),
				'first_last_name' => esc_html__( 'First + Last Name Fields', 'bloom' ),
			),
			'default'         => 'no_name',
			'conditional'     => 'name_text#last_name#single_name_text#name_fullwidth#last_name_fullwidth#email_fullwidth',
			'validation_type' => 'simple_text',
			'display_if'      => implode( '#', $last_name_field_support ),
		),
		'name_text' => array(
			'type'            => 'input_field',
			'subtype'         => 'text',
			'name'            => 'name_text',
			'class'           => 'et_dashboard_name_text',
			'title'           => esc_html__( 'Name Text', 'bloom' ),
			'placeholder'     => esc_html__( 'First Name', 'bloom' ),
			'default'         => '',
			'display_if'      => 'first_last_name',
			'validation_type' => 'simple_text',
			'is_wpml_string'  => true,
		),
		'single_name_text' => array(
			'type'            => 'input_field',
			'subtype'         => 'text',
			'name'            => 'single_name_text',
			'class'           => 'et_dashboard_name_text_single',
			'title'           => esc_html__( 'Name Text', 'bloom' ),
			'placeholder'     => esc_html__( 'Name', 'bloom' ),
			'default'         => '',
			'display_if'      => 'single_name#true',
			'validation_type' => 'simple_text',
			'is_wpml_string'  => true,
		),
		'name_fullwidth' => array(
			'type'            => 'react-portal-container',
			'name'            => 'name_fullwidth',
			'title'           => esc_html__( 'Name Fullwidth', 'bloom' ),
			'display_if'      => 'first_last_name#single_name',
			'validation_type' => 'simple_text',
			'default'         => 'off',
		),
		'last_name' => array(
			'type'            => 'input_field',
			'subtype'         => 'text',
			'name'            => 'last_name',
			'class'           => 'et_dashboard_last_name_text',
			'title'           => esc_html__( 'Last Name Text', 'bloom' ),
			'placeholder'     => esc_html__( 'Last Name', 'bloom' ),
			'default'         => '',
			'display_if'      => 'first_last_name',
			'validation_type' => 'simple_text',
			'is_wpml_string'  => true,
		),
		'last_name_fullwidth' => array(
			'type'            => 'react-portal-container',
			'name'            => 'last_name_fullwidth',
			'title'           => esc_html__( 'Last Name Fullwidth', 'bloom' ),
			'display_if'      => 'first_last_name',
			'validation_type' => 'simple_text',
			'default'         => 'off',
		),
		'email_text' => array(
			'type'            => 'input_field',
			'subtype'         => 'text',
			'name'            => 'email_text',
			'class'           => 'et_dashboard_email_text',
			'title'           => esc_html__( 'Email Text', 'bloom' ),
			'placeholder'     => esc_html__( 'Email', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
			'is_wpml_string'  => true,
		),
		'email_fullwidth' => array(
			'type'            => 'react-portal-container',
			'name'            => 'email_fullwidth',
			'title'           => esc_html__( 'Email Fullwidth', 'bloom' ),
			'validation_type' => 'simple_text',
			'default'         => 'off',
		),
		'button_text' => array(
			'type'            => 'input_field',
			'subtype'         => 'text',
			'name'            => 'button_text',
			'class'           => 'et_dashboard_button_text',
			'title'           => esc_html__( 'Button Text', 'bloom' ),
			'placeholder'     => esc_html__( 'SUBSCRIBE!', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
			'is_wpml_string'  => true,
		),
		'button_text_color' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Button Text Color', 'bloom' ),
			'name'            => 'button_text_color',
			'class'           => 'et_dashboard_field_button_text_color',
			'value'           => array(
				'light' => esc_html__( 'Light', 'bloom' ),
				'dark'  => esc_html__( 'Dark', 'bloom' ),
			),
			'default'         => 'light',
			'validation_type' => 'simple_text',
		),
	),

	'custom_fields' => array(
		'section_start'     => array(
			'type'       => 'section_start',
			'title'      => esc_html__( 'Custom Fields', 'bloom' ),
			'display_if' => implode( '#', $custom_fields_support ),
			'name'       => 'custom_fields_section',
		),
		'use_custom_fields' => array(
			'name'            => 'use_custom_fields',
			'type'            => 'use_custom_fields',
			'validation_type' => 'simple_text',
		),
		'custom_fields'     => array(
			'name'            => 'custom_fields',
			'type'            => 'custom_fields',
			'validation_type' => 'simple_text',
		),
	),

	'optin_styling' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Optin Styling', 'bloom' ),
		),
		'header_bg_color' => array(
			'type'            => 'color_picker',
			'title'           =>  esc_html__( 'Background Color', 'bloom' ),
			'name'            => 'header_bg_color',
			'class'           => 'et_dashboard_optin_bg',
			'placeholder'     => esc_html__( 'Hex Value', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
		),
		'header_font' => array(
			'type'            => 'font_select',
			'title'           => esc_html__( 'Header Font', 'bloom' ),
			'name'            => 'header_font',
			'class'           => 'et_dashboard_header_font',
			'validation_type' => 'simple_text',
		),
		'body_font' => array(
			'type'            => 'font_select',
			'title'           => esc_html__( 'Body Font', 'bloom' ),
			'name'            => 'body_font',
			'class'           => 'et_dashboard_body_font',
			'validation_type' => 'simple_text',
		),
		'header_text_color' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Text Color', 'bloom' ),
			'name'            => 'header_text_color',
			'class'           => 'et_dashboard_text_color',
			'value'           => array(
				'light' => esc_html__( 'Light Text', 'bloom' ),
				'dark'  => esc_html__( 'Dark Text', 'bloom' ),
			),
			'default'         => 'dark',
			'validation_type' => 'simple_text',
		),
		'corner_style' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Corner Style', 'bloom' ),
			'name'            => 'corner_style',
			'class'           => 'et_dashboard_corner_style',
			'value'           => array(
				'squared' => esc_html__( 'Squared Corners', 'bloom' ),
				'rounded' => esc_html__( 'Rounded Corners', 'bloom' ),
			),
			'default'         => 'squared',
			'validation_type' => 'simple_text',
		),
		'border_orientation' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Border Orientation', 'bloom' ),
			'name'            => 'border_orientation',
			'class'           => 'et_dashboard_border_orientation',
			'value'           => array(
				'no_border'  => esc_html__( 'No Border', 'bloom' ),
				'full'       => esc_html__( 'Full Border', 'bloom' ),
				'top'        => esc_html__( 'Top Border', 'bloom' ),
				'right'      => esc_html__( 'Right Border', 'bloom' ),
				'bottom'     => esc_html__( 'Bottom Border', 'bloom' ),
				'left'       => esc_html__( 'Left Border', 'bloom' ),
				'top_bottom' => esc_html__( 'Top + Bottom Border', 'bloom' ),
				'left_right' => esc_html__( 'Left + Right Border', 'bloom' ),
			),
			'default'         => 'no_border',
			'conditional'     => 'border_color#border_style',
			'validation_type' => 'simple_text',
		),
		'border_color' => array(
			'type'            => 'color_picker',
			'title'           =>  esc_html__( 'Border Color', 'bloom' ),
			'name'            => 'border_color',
			'class'           => 'et_dashboard_border_color',
			'placeholder'     => esc_html__( 'Hex Value', 'bloom' ),
			'default'         => '',
			'display_if'      => 'full#top#left#right#bottom#top_bottom#left_right',
			'validation_type' => 'simple_text',
		),
	),

	'form_styling' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Form Styling', 'bloom' ),
		),
		'field_orientation' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Form Field Orientation', 'bloom' ),
			'name'            => 'field_orientation',
			'value'           => array(
				'stacked' => esc_html__( 'Stacked Form Fields', 'bloom' ),
				'inline'  => esc_html__( 'Inline Form Fields', 'bloom' ),
			),
			'default'         => 'inline',
			'validation_type' => 'simple_text',
			'class'           => 'et_bloom_hide_for_widget et_dashboard_field_orientation',
		),
		'field_corner' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Form Field Corner Style', 'bloom' ),
			'name'            => 'field_corner',
			'class'           => 'et_dashboard_field_corners',
			'value'           => array(
				'squared' => esc_html__( 'Squared Corners', 'bloom' ),
				'rounded' => esc_html__( 'Rounded Corners', 'bloom' ),
			),
			'default'         => 'rounded',
			'validation_type' => 'simple_text',
		),
		'text_color' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Form Text Color', 'bloom' ),
			'name'            => 'text_color',
			'class'           => 'et_dashboard_form_text_color',
			'value'           => array(
				'light' => esc_html__( 'Light Text', 'bloom' ),
				'dark'  => esc_html__( 'Dark Text', 'bloom' ),
			),
			'default'         => 'dark',
			'validation_type' => 'simple_text',
		),
		'form_bg_color' => array(
			'type'            => 'color_picker',
			'title'           =>  esc_html__( 'Form Background Color', 'bloom' ),
			'name'            => 'form_bg_color',
			'class'           => 'et_dashboard_form_bg_color',
			'placeholder'     => esc_html__( 'Hex Value', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
		),
		'form_button_color' => array(
			'type'            => 'color_picker',
			'title'           =>  esc_html__( 'Button Color', 'bloom' ),
			'name'            => 'form_button_color',
			'class'           => 'et_dashboard_form_button_color',
			'placeholder'     => esc_html__( 'Hex Value', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
		),
	),

	'edge_style' => array(
		'type'            => 'select_shape',
		'title'           => esc_html__( 'Choose form edge style', 'bloom' ),
		'name'            => 'edge_style',
		'value'           => array(
			'basic_edge',
			'carrot_edge',
			'wedge_edge',
			'curve_edge',
			'zigzag_edge',
			'breakout_edge',
		),
		'default'         => 'basic_edge',
		'class'           => 'et_dashboard_optin_edge',
		'validation_type' => 'simple_text',
	),

	'border_style' => array(
		'type'            => 'select_shape',
		'title'           => esc_html__( 'Choose border style', 'bloom' ),
		'name'            => 'border_style',
		'class'           => 'et_dashboard_border_style',
		'value'           => array(
			'solid',
			'dashed',
			'double',
			'inset',
			'letter',
		),
		'default'         => 'solid',
		'display_if'      => 'full#top#left#right#bottom#top_bottom#left_right',
		'validation_type' => 'simple_text',
	),

	'footer_text' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' =>esc_html__( 'Form Footer Text', 'bloom' ),
		),
		'option' => array(
			'type'            => 'text',
			'rows'            => '3',
			'name'            => 'footer_text',
			'class'           => 'et_dashboard_footer_text et_dashboard_mce',
			'placeholder'     => esc_html__( 'Insert your footer text', 'bloom' ),
			'default'         => '',
			'validation_type' => 'html',
			'is_wpml_string'  => true,
		),
	),

	'success_message' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' =>esc_html__( 'Success Message Text', 'bloom' ),
		),
		'option' => array(
			'type'            => 'text',
			'rows'            => '1',
			'name'            => 'success_message',
			'class'           => 'et_dashboard_success_text',
			'placeholder'     => esc_html__( 'You have Successfully Subscribed!', 'bloom' ),
			'default'         => '',
			'validation_type' => 'html',
			'is_wpml_string'  => true,
		),
	),

	'custom_css' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' =>esc_html__( 'Custom CSS', 'bloom' ),
		),
		'option' => array(
			'type'            => 'text',
			'rows'            => '7',
			'name'            => 'custom_css',
			'placeholder'     => esc_html__( 'insert your custom CSS code', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
		),
	),

	'load_in' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Load-in settings', 'bloom' ),
			'class' => 'et_dashboard_for_popup',
		),
		'load_animation' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Intro Animation', 'bloom' ),
			'name'            => 'load_animation',
			'value'           => array(
				'no_animation' => esc_html__( 'No Animation', 'bloom' ),
				'fadein'       => esc_html__( 'Fade In', 'bloom' ),
				'slideright'   => esc_html__( 'Slide Right', 'bloom' ),
				'slideup'      => esc_html__( 'Slide Up', 'bloom' ),
				'slidedown'    => esc_html__( 'Slide Down', 'bloom' ),
				'lightspeedin' => esc_html__( 'Light Speed', 'bloom' ),
				'zoomin'       => esc_html__( 'Zoom In', 'bloom' ),
				'flipinx'      => esc_html__( 'Flip', 'bloom' ),
				'bounce'       => esc_html__( 'Bounce', 'bloom' ),
				'swing'        => esc_html__( 'Swing', 'bloom' ),
				'tada'         => esc_html__( 'Tada!', 'bloom' ),
			),
			'hint_text'       => esc_html__( 'Define the animation that is used, when you load the page.', 'bloom' ),
			'class'           => 'et_bloom_load_in_animation',
			'default'         => 'fadein',
			'validation_type' => 'simple_text',
		),
		'trigger_auto' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Trigger After Time Delay', 'bloom' ),
			'name'            => 'trigger_auto',
			'default'         => '1',
			'conditional'     => 'load_delay',
			'validation_type' => 'boolean',
		),
		'load_delay' => array(
			'type'            => 'input_field',
			'subtype'         => 'number',
			'title'           => esc_html__( 'Delay (in seconds)', 'bloom' ),
			'name'            => 'load_delay',
			'hint_text'       => esc_html__( 'Define how many seconds you want to wait before the pop up appears on the screen.', 'bloom' ),
			'default'         => '20',
			'display_if'      => 'true',
			'validation_type' => 'number',
		),
		'trigger_idle' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Trigger After Inactivity', 'bloom' ),
			'name'            => 'trigger_idle',
			'default'         => false,
			'conditional'     => 'idle_timeout',
			'validation_type' => 'boolean',
		),
		'idle_timeout' => array(
			'type'            => 'input_field',
			'subtype'         => 'number',
			'title'           => esc_html__( 'Idle Timeout ( in seconds )', 'bloom' ),
			'name'            => 'idle_timeout',
			'hint_text'       => esc_html__( 'Define how many seconds user should be inactive before the pop up appears on screen.', 'bloom' ),
			'default'         => '15',
			'display_if'      => 'true',
			'validation_type' => 'number',
		),
		'post_bottom' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Trigger At The Bottom of Post', 'bloom' ),
			'name'            => 'post_bottom',
			'default'         => '1',
			'validation_type' => 'boolean',
		),
		'comment_trigger' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Trigger After Commenting', 'bloom' ),
			'name'            => 'comment_trigger',
			'default'         => false,
			'validation_type' => 'boolean',
		),
		'trigger_scroll' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Trigger After Scrolling', 'bloom' ),
			'name'            => 'trigger_scroll',
			'default'         => false,
			'conditional'     => 'scroll_pos',
			'validation_type' => 'boolean',
		),
		'scroll_pos' => array(
			'type'            => 'input_field',
			'subtype'         => 'number',
			'title'           => esc_html__( 'Percentage Down The Page', 'bloom' ),
			'name'            => 'scroll_pos',
			'hint_text'       => esc_html__( 'Define the % of the page to be scrolled before the pop up appears on the screen.', 'bloom' ),
			'default'         => '50',
			'display_if'      => 'true',
			'validation_type' => 'number',
		),
		'purchase_trigger' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Trigger After Purchasing', 'bloom' ),
			'name'            => 'purchase_trigger',
			'default'         => false,
			'hint_text'       => esc_html__( 'Display on "Thank you" page of WooCommerce after purchase', 'bloom' ),
			'validation_type' => 'boolean',
		),
		'session' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Display Once Per Session', 'bloom' ),
			'name'            => 'session',
			'default'         => false,
			'validation_type' => 'boolean',
			'conditional'     => 'session_duration',
		),
		'session_duration' => array(
			'type'            => 'input_field',
			'subtype'         => 'number',
			'title'           => esc_html__( 'Session Duration (in days)', 'bloom' ),
			'name'            => 'session_duration',
			'hint_text'       => esc_html__( 'Define the length of time (in days) that a session lasts for. For example, if you input 2 a user will only see a popup on your site every two days.', 'bloom' ),
			'default'         => '1',
			'validation_type' => 'number',
			'display_if'      => 'true',
		),
		'trigger_click' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Trigger On Click', 'bloom' ),
			'name'            => 'trigger_click',
			'default'         => false,
			'validation_type' => 'boolean',
			'conditional'     => 'trigger_click_selector',
		),
		'trigger_click_selector' => array(
			'type'            => 'input_field',
			'subtype'         => 'text',
			'placeholder'     => '',
			'title'           => esc_html__( 'CSS Selector (string)', 'bloom' ),
			'name'            => 'trigger_click_selector',
			'hint_text'       => esc_html__( 'A CSS selector string for a trigger element of your choosing. This opt-in will be triggered by clicking the element. Example: a.trigger_popup', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
			'display_if'      => 'true',
		),
		'hide_mobile' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Hide on Mobile', 'bloom' ),
			'name'            => 'hide_mobile_optin',
			'default'         => false,
			'validation_type' => 'boolean',
		),
		'auto_close' => array(
			'type'            => 'checkbox',
			'title'           => esc_html__( 'Auto Close After Subscribe', 'bloom' ),
			'name'            => 'auto_close',
			'default'         => false,
			'validation_type' => 'boolean',
		),
	),

	'success_action' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => '',
		),
		'success_action_type' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Success Action Type', 'bloom' ),
			'name'            => 'success_action_type',
			'class'           => 'et_dashboard_form_success_action_type',
			'value'           => array(
				'default'      => esc_html__( 'Success Message', 'bloom' ),
				'redirect_url' => esc_html__( 'Redirect To URL', 'bloom' ),
			),
			'default'         => 'default',
			'validation_type' => 'simple_text',
			'conditional'     => 'success_action_info',
			'hint_text'       => esc_html__( 'This action displays a success message.', 'bloom' ),
		),
		'success_action_info' => array(
			'type'            => 'input_field',
			'subtype'         => 'text',
			'placeholder'     => '',
			'title'           => esc_html__( 'URL', 'bloom' ),
			'name'            => 'success_action_info',
			'hint_text'       => esc_html__( 'This action redirects the subscriber to a URL of your choosing.', 'bloom' ),
			'default'         => '',
			'validation_type' => 'url',
			'display_if'      => 'redirect_url',
		),
	),

	'flyin_orientation' => array(
		'section_start' => array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Fly-In Orientation', 'bloom' ),
			'class' => 'et_dashboard_for_flyin',
		),
		'flyin_orientation' => array(
			'type'            => 'select',
			'title'           => esc_html__( 'Choose Orientation', 'bloom' ),
			'name'            => 'flyin_orientation',
			'value'           => array(
				'right'  => esc_html__( 'Right', 'bloom' ),
				'left'   => esc_html__( 'Left', 'bloom' ),
				'center' => esc_html__( 'Center', 'bloom' ),
			),
			'default'         => 'right',
			'validation_type' => 'simple_text',
		),
	),

	'post_types' => array(
		array(
			'type'  => 'section_start',
			'title' => esc_html__( 'Display on', 'bloom' ),
			'class' => 'et_dashboard_child_hidden display_on_section',
		),
		array(
			'type'            => 'checkbox_set',
			'name'            => 'display_on',
			'value'           => array(
				'everything' => esc_html__( 'Everything', 'bloom' ),
				'home'       => esc_html__( 'Homepage', 'bloom' ),
				'blog'  => esc_html__( 'Blog Page', 'bloom' ),
				'archive'    => esc_html__( 'Archives', 'bloom' ),
				'category'   => esc_html__( 'Categories', 'bloom' ),
				'tags'       => esc_html__( 'Tags', 'bloom' ),
			),
			'default'         => array( '' ),
			'validation_type' => 'simple_array',
			'conditional'     => array(
				'everything' => 'pages_exclude_section#posts_exclude_section#pages_include_section#posts_include_section',
				'category'   => 'categories_include_section',
			),
			'class'           => 'display_on_checkboxes',
		),
		array(
			'type'            => 'checkbox_posts',
			'subtype'         => 'post_types',
			'name'            => 'post_types',
			'default'         => array( 'post' ),
			'validation_type' => 'simple_array',
			'conditional'     => array(
				'page'     => 'pages_exclude_section',
				'post'     => 'categories_include_section#posts_exclude_section',
				'any_post' => 'posts_exclude_section#categories_include_section',
			),
		),
	),

	'post_categories' => array(
		array(
			'type'       => 'section_start',
			'title'      => esc_html__( 'Display on these categories', 'bloom' ),
			'class'      => 'et_dashboard_child_hidden categories_include_section',
			'name'       => 'categories_include_section',
			'display_if' => 'true',
		),
		array(
			'type'            => 'checkbox_posts',
			'subtype'         => 'post_cats',
			'name'            => 'post_categories',
			'include_custom'  => true,
			'default'         => array(),
			'validation_type' => 'simple_array',
		),
	),

	'pages_exclude' => array(
		array(
			'type'       => 'section_start',
			'title'      => esc_html__( 'Do not display on these pages', 'bloom' ),
			'class'      => 'et_dashboard_child_hidden',
			'name'       => 'pages_exclude_section',
			'display_if' => 'true',
		),
		array(
			'type'            => 'live_search',
			'name'            => 'pages_exclude',
			'post_type'       => 'only_pages',
			'placeholder'     => esc_html__( 'start typing page name...', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
		),
	),

	'pages_include' => array(
		array(
			'type'       => 'section_start',
			'title'      => esc_html__( 'Display on these pages', 'bloom' ),
			'subtitle'   => esc_html__( 'Pages defined below will override all settings above', 'bloom' ),
			'class'      => 'et_dashboard_child_hidden',
			'name'       => 'pages_include_section',
			'display_if' => 'false',
		),
		array(
			'type'            => 'live_search',
			'name'            => 'pages_include',
			'post_type'       => 'only_pages',
			'placeholder'     => esc_html__( 'start typing page name...', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
		),
	),

	'posts_exclude' => array(
		array(
			'type'       => 'section_start',
			'title'      => esc_html__( 'Do not display on these posts', 'bloom' ),
			'class'      => 'et_dashboard_child_hidden',
			'name'       => 'posts_exclude_section',
			'display_if' => 'true',
		),
		array(
			'type'            => 'live_search',
			'name'            => 'posts_exclude',
			'post_type'       => 'only_posts',
			'placeholder'     => esc_html__( 'start typing post name...', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
		),
	),

	'posts_include' => array(
		array(
			'type'       => 'section_start',
			'title'      => esc_html__( 'Display on these posts', 'bloom' ),
			'subtitle'   => esc_html__( 'Posts defined below will override all settings above', 'bloom' ),
			'class'      => 'et_dashboard_child_hidden',
			'name'       => 'posts_include_section',
			'display_if' => 'false',
		),
		array(
			'type'            => 'live_search',
			'name'            => 'posts_include',
			'post_type'       => 'only_posts',
			'placeholder'     => esc_html__( 'start typing post name...', 'bloom' ),
			'default'         => '',
			'validation_type' => 'simple_text',
		),
	),

	'authorization' => array(
		'authorization_title' => array(
			'type'  => 'main_title',
			'title' => esc_html__( 'Setup your accounts', 'bloom' ),
		),

		'sub_section_mailchimp' => array(
			'type'        => 'section_start',
			'sub_section' => true,
			'title'       => esc_html__( 'MailChimp', 'bloom' ),
		),

		'mailchimp_key' => array(
			'type'                 => 'input_field',
			'subtype'              => 'text',
			'name'                 => 'mailchimp_key',
			'title'                => esc_html__( 'MailChimp API Key', 'bloom' ),
			'default'              => '',
			'class'                => 'api_option api_option_key',
			'hide_contents'        => true,
			'hint_text'            => sprintf(
				'<a href="%2$s" target="_blank">%1$s</a>',
				esc_html__( 'Click here for more information', 'bloom' ),
				esc_url( 'http://www.elegantthemes.com' )
			),
			'hint_text_with_links' => 'on',
			'validation_type'      => 'simple_text',
		),
		'mailchimp_button' => array(
			'type'      => 'button',
			'title'     => esc_html__( 'Authorize', 'Monarch' ),
			'link'      => '#',
			'class'     => 'et_dashboard_authorize',
			'action'    => 'mailchimp',
			'authorize' => true,
		),

		'sub_section_aweber' => array(
			'type'        => 'section_start',
			'sub_section' => true,
			'title'       => esc_html__( 'AWeber', 'bloom' ),
		),

		'aweber_key' => array(
			'type'                 => 'input_field',
			'subtype'              => 'text',
			'name'                 => 'aweber_key',
			'title'                => esc_html__( 'AWeber authorization code', 'bloom' ),
			'default'              => '',
			'class'                => 'api_option api_option_key',
			'hide_contents'        => true,
			'hint_text'            => sprintf(
				'<a href="%2$s" target="_blank">%1$s</a>',
				esc_html__( 'Click here for more information', 'bloom' ),
				esc_url( 'http://www.elegantthemes.com' )
			),
			'hint_text_with_links' => 'on',
			'validation_type'      => 'simple_text',
		),
		'aweber_button' => array(
			'type'      => 'button',
			'title'     => esc_html__( 'Authorize', 'Monarch' ),
			'link'      => '#',
			'class'     => 'et_dashboard_authorize',
			'action'    => 'aweber',
			'authorize' => true,
		),
	),

	'optin_type' => array(
		'type'            => 'hidden_option',
		'subtype'         => 'string',
		'name'            => 'optin_type',
		'validation_type' => 'simple_text',
	),

	'optin_status' => array(
		'type'            => 'hidden_option',
		'subtype'         => 'string',
		'name'            => 'optin_status',
		'validation_type' => 'simple_text',
	),

	'test_status' => array(
		'type'            => 'hidden_option',
		'subtype'         => 'string',
		'name'            => 'test_status',
		'validation_type' => 'simple_text',
	),

	'next_optin' => array(
		'type'            => 'hidden_option',
		'subtype'         => 'string',
		'name'            => 'next_optin',
		'default'         => '-1',
		'validation_type' => 'simple_text',
	),

	'child_of' => array(
		'type'            => 'hidden_option',
		'subtype'         => 'string',
		'name'            => 'child_of',
		'validation_type' => 'simple_text',
	),

	'child_optins' => array(
		'type'            => 'hidden_option',
		'subtype'         => 'array',
		'name'            => 'child_optins',
		'validation_type' => 'simple_array',
	),

	'setup_title' => array(
		'type'     => 'main_title',
		'title'    => esc_html__( 'Setup your optin form', 'bloom' ),
		'subtitle' => esc_html__( 'Name your optin and configure your form integration.', 'bloom' ),
	),

	'design_title' => array(
		'type'     => 'main_title',
		'title'    => esc_html__( 'Design your optin form', 'bloom' ),
		'subtitle' => esc_html__( 'Configure your content, layout, and optin styling below.', 'bloom' ),
		'class'    => 'et_dashboard_design_title',
	),

	'display_title' => array(
		'type'     => 'main_title',
		'title'    => esc_html__( 'Display Settings', 'bloom' ),
		'subtitle' => esc_html__( 'Define when and where to display this optin on your website.', 'bloom' ),
	),

	'success_action_title' => array(
		'type'     => 'main_title',
		'title'    => esc_html__( 'Success Action', 'bloom' ),
		'subtitle' => esc_html__( 'Control what happens when someone successfully subscribes to your mailing list using this opt-in.', 'bloom' ),
	),

	'import_export' => array(
		'type'  => 'import_export',
		'title' => esc_html__( 'Import/Export', 'bloom' ),
	),

	'home' => array(
		'type'  => 'home',
		'title' => esc_html__( 'Home', 'bloom' ),
	),

	'stats' => array(
		'type'  => 'stats',
		'title' => esc_html__( 'Optin Stats', 'bloom' ),
	),

	'updates' => array(
		'type'  => 'updates',
		'title' => esc_html__( 'Bloom Updates', 'bloom' ),
	),
	
	'settings' => array(
		'type'  => 'settings',
		'title' => esc_html__( 'Bloom Settings', 'bloom' ),
	),

	'accounts' => array(
		'type'  => 'account',
		'title' => esc_html__( 'Accounts', 'bloom' ),
	),

	'edit_account' => array(
		'type'  => 'edit_account',
		'title' => esc_html__( 'Edit Account', 'bloom' ),
	),

	'preview_optin' => array(
		'type'  => 'preview_optin',
		'title' => esc_html__( 'Preview', 'bloom' ),
	),

	'premade_templates_start' => array(
		'type'     => 'main_title',
		'title'    => esc_html__( 'Choose a template', 'bloom' ),
		'subtitle' => esc_html__( 'These are just starting points that you can full customize on the next step.', 'bloom' ),
	),

	'premade_templates_main' => array(
		'type'  => 'premade_templates',
		'title' => esc_html__( 'Choose a template', 'bloom' ),
	),

	'end_of_section' => array(
		'type' => 'section_end',
	),

	'end_of_sub_section' => array(
		'type'        => 'section_end',
		'sub_section' => 'true',
	),
);

/**
 * Array of options assigned to sections. Format of option key is following:
 * 	<section>_<sub_section>_options
 * where:
 *	<section> = $all_sections -> $key
 *	<sub_section> = $all_sections -> $value['contents'] -> $key
 *
 * Note: name of this array shouldn't be changed. $assigned_options variable is being used in ET_Dashboard class as options container.
 */
$assigned_options = array(
	'optin_setup_options' => array(
		$dashboard_options_all[ 'setup_title' ],
		$dashboard_options_all[ 'optin_type' ],
		$dashboard_options_all[ 'optin_status' ],
		$dashboard_options_all[ 'test_status' ],
		$dashboard_options_all[ 'child_of' ],
		$dashboard_options_all[ 'child_optins' ],
		$dashboard_options_all[ 'next_optin' ],
		$dashboard_options_all[ 'optin_name' ][ 'section_start' ],
			$dashboard_options_all[ 'optin_name' ][ 'option' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'form_integration' ][ 'section_start' ],
			$dashboard_options_all[ 'form_integration' ][ 'email_provider' ],
			$dashboard_options_all[ 'form_integration' ][ 'select_account' ],
			$dashboard_options_all[ 'form_integration' ][ 'email_list' ],
			$dashboard_options_all[ 'form_integration' ][ 'custom_html' ],
			$dashboard_options_all[ 'form_integration' ][ 'ip_address' ],
			$dashboard_options_all[ 'form_integration' ][ 'enable_dbl_optin' ],
			$dashboard_options_all[ 'form_integration' ][ 'message_id' ],
		$dashboard_options_all[ 'end_of_section' ],
	),
	'optin_premade_options' => array(
		$dashboard_options_all[ 'premade_templates_start' ],
		$dashboard_options_all[ 'premade_templates_main' ],
	),
	'optin_design_options' => array(
		$dashboard_options_all[ 'preview_optin' ],
		$dashboard_options_all[ 'design_title' ],
		$dashboard_options_all[ 'optin_title' ][ 'section_start' ],
			$dashboard_options_all[ 'optin_title' ][ 'option' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'optin_message' ][ 'section_start' ],
			$dashboard_options_all[ 'optin_message' ][ 'option' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'image_settings' ][ 'section_start' ],
			$dashboard_options_all[ 'image_settings' ][ 'image_orientation' ],
			$dashboard_options_all[ 'image_settings' ][ 'image_orientation_widget' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'image_upload' ][ 'section_start' ],
			$dashboard_options_all[ 'image_upload' ][ 'image_url' ],
			$dashboard_options_all[ 'image_upload' ][ 'image_animation' ],
			$dashboard_options_all[ 'image_upload' ][ 'hide_mobile' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'optin_styling' ][ 'section_start' ],
			$dashboard_options_all[ 'optin_styling' ][ 'header_bg_color' ],
			$dashboard_options_all[ 'optin_styling' ][ 'header_font' ],
			$dashboard_options_all[ 'optin_styling' ][ 'body_font' ],
			$dashboard_options_all[ 'optin_styling' ][ 'header_text_color' ],
			$dashboard_options_all[ 'optin_styling' ][ 'corner_style' ],
			$dashboard_options_all[ 'optin_styling' ][ 'border_orientation' ],
			$dashboard_options_all[ 'optin_styling' ][ 'border_color' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'border_style' ],
		$dashboard_options_all[ 'form_setup' ][ 'section_start' ],
			$dashboard_options_all[ 'form_setup' ][ 'form_orientation' ],
			$dashboard_options_all[ 'form_setup' ][ 'display_name' ],
			$dashboard_options_all[ 'form_setup' ][ 'name_fields' ],
			$dashboard_options_all[ 'form_setup' ][ 'name_text' ],
			$dashboard_options_all[ 'form_setup' ][ 'single_name_text' ],
			$dashboard_options_all[ 'form_setup' ][ 'name_fullwidth' ],
			$dashboard_options_all[ 'form_setup' ][ 'last_name' ],
			$dashboard_options_all[ 'form_setup' ][ 'last_name_fullwidth' ],
			$dashboard_options_all[ 'form_setup' ][ 'email_text' ],
			$dashboard_options_all[ 'form_setup' ][ 'email_fullwidth' ],
			$dashboard_options_all[ 'form_setup' ][ 'button_text' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all['custom_fields']['section_start'],
			$dashboard_options_all['custom_fields']['use_custom_fields'],
			$dashboard_options_all['custom_fields']['custom_fields'],
		$dashboard_options_all['end_of_section'],
		$dashboard_options_all[ 'form_styling' ][ 'section_start' ],
			$dashboard_options_all[ 'form_styling' ][ 'field_orientation' ],
			$dashboard_options_all[ 'form_styling' ][ 'field_corner' ],
			$dashboard_options_all[ 'form_styling' ][ 'text_color' ],
			$dashboard_options_all[ 'form_styling' ][ 'form_bg_color' ],
			$dashboard_options_all[ 'form_styling' ][ 'form_button_color' ],
			$dashboard_options_all[ 'form_setup' ][ 'button_text_color' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'edge_style' ],
		$dashboard_options_all[ 'footer_text' ][ 'section_start' ],
			$dashboard_options_all[ 'footer_text' ][ 'option' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'success_message' ][ 'section_start' ],
			$dashboard_options_all[ 'success_message' ][ 'option' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'custom_css' ][ 'section_start' ],
			$dashboard_options_all[ 'custom_css' ][ 'option' ],
		$dashboard_options_all[ 'end_of_section' ],
	),
	'optin_display_options' => array(
		$dashboard_options_all[ 'display_title' ],
		$dashboard_options_all[ 'flyin_orientation' ][ 'section_start' ],
			$dashboard_options_all[ 'flyin_orientation' ][ 'flyin_orientation' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'load_in' ][ 'section_start' ],
			$dashboard_options_all[ 'load_in' ][ 'load_animation' ],
			$dashboard_options_all[ 'load_in' ][ 'trigger_auto' ],
			$dashboard_options_all[ 'load_in' ][ 'load_delay' ],
			$dashboard_options_all[ 'load_in' ][ 'trigger_idle' ],
			$dashboard_options_all[ 'load_in' ][ 'idle_timeout' ],
			$dashboard_options_all[ 'load_in' ][ 'post_bottom' ],
			$dashboard_options_all[ 'load_in' ][ 'comment_trigger' ],
			$dashboard_options_all[ 'load_in' ][ 'trigger_scroll' ],
			$dashboard_options_all[ 'load_in' ][ 'scroll_pos' ],
			$dashboard_options_all[ 'load_in' ][ 'purchase_trigger' ],
			$dashboard_options_all[ 'load_in' ][ 'trigger_click' ],
			$dashboard_options_all[ 'load_in' ][ 'trigger_click_selector' ],
			$dashboard_options_all[ 'load_in' ][ 'session' ],
			$dashboard_options_all[ 'load_in' ][ 'session_duration' ],
			$dashboard_options_all[ 'load_in' ][ 'hide_mobile' ],
			$dashboard_options_all[ 'load_in' ][ 'auto_close' ],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'post_types' ][0],
			$dashboard_options_all[ 'post_types' ][1],
			$dashboard_options_all[ 'post_types' ][2],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'post_categories' ][0],
			$dashboard_options_all[ 'post_categories' ][1],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'pages_include' ][0],
			$dashboard_options_all[ 'pages_include' ][1],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'pages_exclude' ][0],
			$dashboard_options_all[ 'pages_exclude' ][1],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'posts_exclude' ][0],
			$dashboard_options_all[ 'posts_exclude' ][1],
		$dashboard_options_all[ 'end_of_section' ],
		$dashboard_options_all[ 'posts_include' ][0],
			$dashboard_options_all[ 'posts_include' ][1],
		$dashboard_options_all[ 'end_of_section' ],
	),

	'optin_success_action_options' => array(
		$dashboard_options_all[ 'success_action_title' ],
		$dashboard_options_all[ 'success_action' ][ 'section_start' ],
			$dashboard_options_all[ 'success_action' ][ 'success_action_type' ],
			$dashboard_options_all[ 'success_action' ][ 'success_action_info' ],
		$dashboard_options_all[ 'end_of_section' ]
	),

	'header_importexport_options' => array(
		$dashboard_options_all[ 'import_export' ],
	),
	'header_home_options' => array(
		$dashboard_options_all[ 'home' ],
	),
	'header_accounts_options' => array(
		$dashboard_options_all[ 'accounts' ],
	),
	'header_edit_account_options' => array(
		$dashboard_options_all[ 'edit_account' ],
	),
	'header_stats_options' => array(
		$dashboard_options_all[ 'stats' ],
	),
	'header_updates_options' => array(
		$dashboard_options_all[ 'updates' ],
	),
	'header_settings_options' => array(
		$dashboard_options_all[ 'settings' ],
	),
);
