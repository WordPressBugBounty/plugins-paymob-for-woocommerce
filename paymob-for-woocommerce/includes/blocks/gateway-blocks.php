<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class Paymob_Gateway_Blocks extends AbstractPaymentMethodType {

	public $name;
	public function initialize() {
		$this->settings = get_option( 'woocommerce_' . $this->name . '_settings', array() );
	}
	public function is_active() {
		return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
	}
	public function get_payment_method_script_handles() {
		wp_register_script(
			$this->name . '-blocks-integration',
			plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/blocks/' . $this->name . '_block.js',
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

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $this->name . '-blocks-integration' );
		}

		return array( $this->name . '-blocks-integration' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => isset( $this->settings['title'] ) ? ucwords( $this->settings['title'] ) : '',
			'description' => isset( $this->settings['description'] ) ? $this->settings['description'] : '',
			'icon'        => isset( $this->settings['logo'] ) ? $this->settings['logo'] : plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png',
		);
	}
}
