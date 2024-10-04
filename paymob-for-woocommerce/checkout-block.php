<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Paymob_Blocks extends AbstractPaymentMethodType {

	protected $name = 'paymob';

	public function initialize() {
		$this->settings = get_option( 'woocommerce_paymob_settings', array() );
	}

	public function get_payment_method_script_handles() {
		wp_register_script(
			'paymob-blocks-integration',
			plugin_dir_url( __FILE__ ) . 'assets/js/checkout_block.js',
			array(
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			null,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'paymob-blocks-integration' );
		}

		return array( 'paymob-blocks-integration' );
	}

	public function get_payment_method_data() {
		return array(
			'title'       => isset( $this->settings['title'] ) ? $this->settings['title'] : 'Paymob',
			'description' => isset( $this->settings['description'] ) ? $this->settings['description'] : '',
		);
	}
}
