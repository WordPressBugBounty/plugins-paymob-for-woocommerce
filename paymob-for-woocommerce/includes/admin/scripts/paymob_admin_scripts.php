<?php
class Paymob_Scripts {

	public static function paymob_list_gateways() {
		wp_enqueue_script( 'paymob-admin-scripts', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/paymob_list_gateways.js', array( 'jquery' ), PAYMOB_VERSION, true );
		wp_localize_script(
			'paymob-admin-scripts',
			'paymob_admin_ajax',
			array(
				'ajax_url'                    => admin_url( 'admin-ajax.php' ),
				'delete_nonce'                => wp_create_nonce( 'delete_gateway_nonce' ),
				'toggle_nonce'                => wp_create_nonce( 'toggle_gateway_nonce' ),
				'save_gateway_order_nonce'    => wp_create_nonce( 'save_gateway_order' ),
				'reset_paymob_gateways_nonce' => wp_create_nonce( 'reset_paymob_gateways' ),
				'rg' =>__('Remove Gateway', 'paymob-woocommerce'),
				'ays' => __('Are you sure you want to remove this gateway?', 'paymob-woocommerce'),
				'ay' => __('Are you sure you want to ', 'paymob-woocommerce'),
				'tg' => __(' this gateway?', 'paymob-woocommerce'),
				'gat' => __(' Gateway', 'paymob-woocommerce'),
				'rp' => __('Reset Payment Methods', 'paymob-woocommerce'),
				'arp' => __('Are you sure you want to reset the payment methods?', 'paymob-woocommerce'),
			)
		);
	}

	public static function paymob_admin( $params ) {
		wp_enqueue_script( 'paymob-admin-js', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/admin.js', array( 'jquery' ), PAYMOB_VERSION, true );
		wp_enqueue_script( 'color-picker', admin_url() . 'js/color-picker.min.js', array(), PAYMOB_VERSION, true );
		wp_localize_script( 'paymob-admin-js', 'ajax_object', $params );
	}

	public static function confirmation_popup() {
		wp_enqueue_script(
			'confirmation-popup',
			plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/confirmation-popup.js', // Adjust the path as necessary.
			array( 'jquery' ),
			PAYMOB_VERSION,
			true
		);
	}

	public static function confirmation_popup_localize( $exist ) {
		wp_localize_script(
			'confirmation-popup',
			'wc_admin_settings',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'your_nonce_action' ),
				'exist'                => $exist,
				'paymob_list_gateways' => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paymob_list_gateways' ),
			)
		);
	}

	public static function paymob_accordion() {
		wp_enqueue_script( 'jquery-ui-accordion' );
		wp_enqueue_script(
			'paymob-accordion-script',
			plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/accordion.js',
			array( 'jquery', 'jquery-ui-accordion' ),
			PAYMOB_VERSION,
			true
		);
	}

	public static function paymob_frontend() {
		wp_enqueue_script( 'paymob-frontend-js', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/apple-users.js', array( 'jquery' ), PAYMOB_VERSION, true );
	}

	public static function method_script( $name ) {
		wp_register_script(
			$name . '-blocks-integration',
			plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/blocks/' . $name . '_block.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			PAYMOB_VERSION,
			true
		);
	}

	public static function get_save_card_confirmation_model_script() {
		wp_enqueue_script(
			'paymob-save-card',
			plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/save-card.js',
			array( 'jquery' ),
			PAYMOB_VERSION,
			true
		);
	}
}
