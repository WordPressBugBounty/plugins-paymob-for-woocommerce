<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Checkout_Blocks {

	public function __construct() {
		add_action( 'woocommerce_blocks_loaded', array( $this, 'paymob_woocommerce_block_support' ) );
	}

	public function paymob_woocommerce_block_support() {
		global $wpdb;

		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' )
			|| ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry' ) ) {
			return;
		}

		if ( class_exists( 'PaymobAutoGenerate' ) ) {
			PaymobAutoGenerate::ensure_generated_gateway_files();
		}

		foreach ( glob( PAYMOB_PLUGIN_PATH . 'includes/blocks/' . '*-block.php' ) as $filename ) {
			require_once $filename;
		}

		$gateways = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'paymob_gateways', OBJECT );
		if ( empty( $gateways ) ) {
			return;
		}

		foreach ( $gateways as $gateway ) {
			if ( empty( $gateway->class_name ) || empty( $gateway->gateway_id ) ) {
				continue;
			}

			$gateway_class = 'WC_' . $gateway->class_name . '_Blocks';

			if ( ! class_exists( $gateway_class ) ) {
				$block_file = PAYMOB_PLUGIN_PATH . 'includes/blocks/' . $gateway->gateway_id . '-block.php';
				if ( file_exists( $block_file ) ) {
					require_once $block_file;
				}
			}

			if ( ! class_exists( $gateway_class ) ) {
				continue;
			}

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) use ( $gateway_class ) {
					if ( ! class_exists( $gateway_class ) ) {
						return;
					}
					$container = Automattic\WooCommerce\Blocks\Package::container();
					$container->register(
						$gateway_class,
						function () use ( $gateway_class ) {
							if ( ! class_exists( $gateway_class ) ) {
								return null;
							}
							return new $gateway_class();
						}
					);
					$instance = $container->get( $gateway_class );
					if ( $instance ) {
						$payment_method_registry->register( $instance );
					}
				}
			);
		}
	}
}

new Checkout_Blocks();
