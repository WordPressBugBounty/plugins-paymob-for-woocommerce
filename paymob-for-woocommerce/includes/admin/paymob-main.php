<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return array(
	'config_note'       => array(
		'title'       => __( 'Main Configuration', 'paymob-woocommerce' ),
		'description' => include PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_accordion1.php',
		'type'        => 'title',
	),
	'api_key'           => array(
		'title'             => __( 'API Key', 'paymob-woocommerce' ),
		'type'              => 'text',
		'sanitize_callback' => 'sanitize_text_field',
		'custom_attributes' => array( 'required' => 'required' ),
	),
	'sec_key'           => array(
		'title'             => __( 'Secret Key', 'paymob-woocommerce' ),
		'type'              => 'text',
		'sanitize_callback' => 'sanitize_text_field',
		'custom_attributes' => array( 'required' => 'required' ),
	),
	'pub_key'           => array(
		'title'             => __( 'Public Key', 'paymob-woocommerce' ),
		'type'              => 'text',
		'sanitize_callback' => 'sanitize_text_field',
		'custom_attributes' => array( 'required' => 'required' ),
	),
	'callback_note'     => array(
		'title'       => '',
		'description' => include PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_accordion4.php',
		'type'        => 'title',
	),
	'callback'          => array(
		'title'       => __( 'Integration Callback', 'paymob-woocommerce' ),
		'label'       => include PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_callbackurl.php',
		'description' => '',
		'css'         => 'display:none',
		'type'        => 'checkbox',
	),
	'has_items_note'    => array(
		'title'       => '',
		'description' => include PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_accordion5.php',
		'type'        => 'title',
	),
	'has_items'         => array(
		'title'   => __( 'Pass Item Data', 'paymob-woocommerce' ),
		'label'   => ' ',
		'type'    => 'checkbox',
		'default' => 'no',
	),
	'extra_note'        => array(
		'title'       => '',
		'description' => include PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_accordion6.php',
		'type'        => 'title',
	),
	'debug'             => array(
		'title'   => __( 'Debug Log', 'paymob-woocommerce' ),
		'label'   => __( 'Enable debug log', 'paymob-woocommerce' ),
		'type'    => 'checkbox',
		'default' => 'yes',
	),
	'empty_cart'        => array(
		'title'   => __( 'Empty cart items', 'paymob-woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable empty cart items', 'paymob-woocommerce' ),
		'default' => 'no',
	),
	'save_changes_note' => array(
		'title'       => '',
		'description' => include PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_accordion7.php',
		'type'        => 'title',
	),
);
