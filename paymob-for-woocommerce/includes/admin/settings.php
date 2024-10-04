<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$required = '<span class="dashicon dashicons dashicons-star-filled" style="color:red; font-size:8px;"></span>';
return array(
	'enabled'               => array(
		'title'   => __( 'Enable', 'woocommerce' ),
		'type'    => 'checkbox',
		'default' => 'no',
		'label'   => __( 'Enable Paymob payment method', 'paymob-woocommerce' ),
	),
	'config_note'           => array(
		'title'       => __( 'Merchant configuration', 'paymob-woocommerce' ),
		'description' => '<div class="loader_paymob"></div>'
			. '<div class="success_load dashicons dashicons-yes-alt"></div>'
			. '<div class="dashicons dashicons-dismiss failed_load"></div>'
			. __( 'Please, ensure using the secret and public keys that are exists in Paymob <a href="https://accept.paymob.com/portal2/en/settings">merchant account. </a><br/>For testing purposes, you can use secret and public test keys that exist in the same account', 'paymob-woocommerce' ),
		'type'        => 'title',
	),
	'sec_key'               => array(
		'title'             => __( 'Secret Key', 'paymob-woocommerce' ) . $required,
		'type'              => 'text',
		'sanitize_callback' => 'sanitize_text_field',
	),
	'pub_key'               => array(
		'title'             => __( 'Public Key', 'paymob-woocommerce' ) . $required,
		'type'              => 'text',
		'sanitize_callback' => 'sanitize_text_field',
	),
	'api_key'               => array(
		'title'             => __( 'API Key', 'paymob-woocommerce' ) . $required,
		'type'              => 'text',
		'description'       => '<br/> <div class="span-align"><span id="accept-login" class="button-primary">' . __( 'Validate PayMob API Key', 'paymob-woocommerce' ) . '</span> '
			. '<span class="dashicons dashicons-yes-alt paymob-valid" id="paymob-valid" ></span>'
			. '<span class="dashicons dashicons-dismiss paymob-not-valid" id="paymob-not-valid"></span></div>',
		'sanitize_callback' => 'sanitize_text_field',
	),
	'hmac'                  => array(
		'title'             => __( 'HMAC Key', 'paymob-woocommerce' ) . $required,
		'type'              => 'text',
		'disabled'          => true,
		'sanitize_callback' => 'sanitize_text_field',
	),
	'integration_id'        => array(
		'title'   => __( 'Integration ID(s)', 'paymob-woocommerce' ) . $required,
		'type'    => 'multiselect',
		'options' => $this->get_option( 'integration_id' ),
	),
	'title'                 => array(
		'title'             => __( 'Title', 'paymob-woocommerce' ),
		'type'              => 'text',
		'description'       => __( 'This controls the title which the user sees during checkout.', 'paymob-woocommerce' ),
		'default'           => __( 'Pay with Paymob', 'paymob-woocommerce' ),
		'sanitize_callback' => 'sanitize_text_field',
	),
	'description'           => array(
		'title'             => __( 'Description', 'paymob-woocommerce' ),
		'type'              => 'textarea',
		'default'           => __( 'Pay with Paymob', 'paymob-woocommerce' ),
		'description'       => __( 'This controls the description which the user sees during checkout.', 'paymob-woocommerce' ),
		'sanitize_callback' => 'sanitize_text_field',
	),
	'logo'                  => array(
		'title'             => __( 'Logo URL', 'paymob-woocommerce' ),
		'default'           => plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png',
		'type'              => 'text',
		'description'       => __( 'Add a Logo URL for checkout icon.', 'paymob-woocommerce' ),
		'sanitize_callback' => 'sanitize_url',
	),
	'callback'              => array(
		'title'       => __( 'Integration Callback', 'paymob-woocommerce' ),
		'label'       => '<span id="cburl" class="button-secondary callback_copy">' . add_query_arg( array( 'wc-api' => 'paymob_callback' ), home_url() ) . '</span>',
		'description' => __( 'Please click on this icon ', 'paymob-woocommerce' ) . '<span style="cursor:pointer;" id="cpicon" class="dashicons dashicons-clipboard"></span>' . __( ' to copy the callback URLs and paste it into paymob account.', 'paymob-woocommerce' ),
		'css'         => 'display:none',
		'type'        => 'checkbox',
	),
	'empty_cart'            => array(
		'title'       => __( 'Empty cart items', 'paymob-woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable empty cart items', 'paymob-woocommerce' ),
		'description' => __( 'You can check this option in case you need to clear the cart items before completing the payment. (not recommended)', 'paymob-woocommerce' ),
		'default'     => 'no',
	),
	'debug'                 => array(
		'title'       => __( 'Debug Log', 'paymob-woocommerce' ),
		'label'       => __( 'Enable debug log', 'paymob-woocommerce' ),
		'type'        => 'checkbox',
		'description' => __( 'Log file will be saved in ', 'paymob-woocommerce' ) . ( defined( 'WC_LOG_DIR' ) ? WC_LOG_DIR : WC()->plugin_path() . '/logs/' ),
		'default'     => 'yes',
	),

	'integration_id_hidden' => array(
		'type' => 'hidden',
	),
	'hmac_hidden'           => array(
		'type' => 'hidden',
	),
);
