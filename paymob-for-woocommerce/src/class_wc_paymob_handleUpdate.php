<?php
/**
 * Handel Plugin Update
 */
class WC_Paymob_HandelUpdate {

	public static function handle_plugin_update( $gateways ) {
		// Retrieve the main settings
		$mainOptions = get_option( 'woocommerce_paymob-main_settings' );
		// Check if main settings are empty
		if ( empty( $mainOptions ) ) {
			// Retrieve the Paymob settings
			$paymobSettings = get_option( 'woocommerce_paymob_settings' );
			// Check if Paymob settings are not empty
			if ( ! empty( $paymobSettings ) ) {
				// Prepare the main settings with values from Paymob settings
				$mainSettings = array(
					'enabled'    => isset( $paymobSettings['enabled'] ) ? $paymobSettings['enabled'] : '',
					'sec_key'    => isset( $paymobSettings['sec_key'] ) ? $paymobSettings['sec_key'] : '',
					'pub_key'    => isset( $paymobSettings['pub_key'] ) ? $paymobSettings['pub_key'] : '',
					'api_key'    => isset( $paymobSettings['api_key'] ) ? $paymobSettings['api_key'] : '',
					'empty_cart' => isset( $paymobSettings['empty_cart'] ) ? $paymobSettings['empty_cart'] : '',
					'debug'      => isset( $paymobSettings['debug'] ) ? $paymobSettings['debug'] : '',
					'has_items'  => 'no',
				);
				// Update the main settings
				update_option( 'woocommerce_paymob-main_settings', $mainSettings );

				$paymob_default_settings = array(
					'enabled'               => 'no',
					'sec_key'               => isset( $paymobSettings['sec_key'] ) ? $paymobSettings['sec_key'] : '',
					'pub_key'               => isset( $paymobSettings['pub_key'] ) ? $paymobSettings['pub_key'] : '',
					'api_key'               => isset( $paymobSettings['api_key'] ) ? $paymobSettings['api_key'] : '',
					'title'                 => isset( $paymobSettings['title'] ) ? $paymobSettings['title'] : '',
					'description'           => isset( $paymobSettings['description'] ) ? $paymobSettings['description'] : '',
					'integration_id'        => isset( $paymobSettings['integration_id'] ) ? $paymobSettings['integration_id'] : '',
					'integration_id_hidden' => isset( $paymobSettings['integration_id_hidden'] ) ? $paymobSettings['integration_id_hidden'] : '',
					'hmac_hidden'           => isset( $paymobSettings['hmac_hidden'] ) ? $paymobSettings['hmac_hidden'] : '',
					'empty_cart'            => isset( $paymobSettings['empty_cart'] ) ? $paymobSettings['empty_cart'] : '',
					'debug'                 => isset( $paymobSettings['debug'] ) ? $paymobSettings['debug'] : '',
					'logo'                  => plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png',
				);
				update_option( 'woocommerce_paymob_settings', $paymob_default_settings );
			}
		}
		// display enabled gateways count
		if ( ! empty( $mainOptions ) ) {
			PaymobAutoGenerate::enabled_gateways_count( $gateways );
		}

		$debug = isset( $mainOptions['debug'] ) ? $mainOptions['debug'] : '';
		$debug = 'yes' === $debug ? '1' : '0';
		// Load integrations IDs
		$conf['apiKey'] = isset( $mainOptions['api_key'] ) ? $mainOptions['api_key'] : '';
		$conf['pubKey'] = isset( $mainOptions['pub_key'] ) ? $mainOptions['pub_key'] : '';
		$conf['secKey'] = isset( $mainOptions['sec_key'] ) ? $mainOptions['sec_key'] : '';
		if ( ! empty( $conf['apiKey'] ) && ! empty( $conf['pubKey'] ) && ! empty( $conf['secKey'] ) ) {

			try {
				$paymob_country = get_option( 'woocommerce_paymob_country' );
				if ( empty( $paymob_country ) ) {
					$paymobReq = new Paymob( $debug, WC_LOG_DIR . 'paymob.log' );
					update_option( 'woocommerce_paymob_country', Paymob::getCountryCode( $conf['pubKey'] ) );
					$result = $paymobReq->authToken( $conf );
					$ids    = array();
					foreach ( $result['integrationIDs'] as $value ) {
						$ids[] = trim( $value['id'] );
					}
					PaymobAutoGenerate::register_framework( $ids );
					$gatewayData = get_option( 'woocommerce_paymob_gateway_data' );
					if ( empty( $gatewayData ) ) {
						$gatewayData = $paymobReq->getPaymobGateways( $conf['secKey'], PAYMOB_PLUGIN_PATH . 'assets/img/' );
						update_option( 'woocommerce_paymob_gateway_data', $gatewayData );
					}
					
					PaymobAutoGenerate::create_gateways( $result, 0, $gatewayData );
				}
			} catch ( \Exception $e ) {
				WC_Admin_Settings::add_error( __( $e->getMessage(), 'paymob-woocommerce' ) );
			}
		}
		// Load gateways from db
		foreach ( $gateways as $gateway ) {
			// Check if properties are set and provide a default value if not
			$class_name                = isset( $gateway->class_name ) ? $gateway->class_name : '';
			$payment_integrations_type = isset( $gateway->gateway_id ) ? $gateway->gateway_id : '';
			$checkout_title            = isset( $gateway->checkout_title ) ? $gateway->checkout_title : '';
			$checkout_description      = isset( $gateway->checkout_description ) ? $gateway->checkout_description : '';
			$file_name                 = isset( $gateway->file_name ) ? $gateway->file_name : '';
			$f_array                   = array(
				'class_name'           => $class_name,
				'gateway_id'           => $payment_integrations_type,
				'checkout_title'       => $checkout_title,
				'checkout_description' => $checkout_description,
				'file_name'            => $file_name,
			);
			PaymobAutoGenerate::generate_files( $f_array );
		}
	}
}
