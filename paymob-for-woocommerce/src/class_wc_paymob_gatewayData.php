<?php
/**
 * Paymob Gateway Data
 */
class WC_Paymob_GatewayData {

	public static function getPaymobGatewayData() {
		$gatewayData = get_option( 'woocommerce_paymob_gateway_data' );
		if ( empty( $gatewayData ) ) {
			$mainOptions = get_option( 'woocommerce_paymob-main_settings' );
			if ( ! empty( $mainOptions ) ) {
				$debug          = isset( $mainOptions['debug'] ) ? $mainOptions['debug'] : '';
				$debug          = 'yes' === $debug ? '1' : '0';
				try {
					$paymobReq      = new Paymob( $debug, WC_LOG_DIR . 'paymob.log' );
					$conf['secKey'] = isset( $mainOptions['sec_key'] ) ? $mainOptions['sec_key'] : '';
					$gatewayData = $paymobReq->getPaymobGateways( $conf['secKey'], PAYMOB_PLUGIN_PATH . 'assets/img/' );
					update_option( 'woocommerce_paymob_gateway_data', $gatewayData );
				} catch ( \Exception $e ) {
					WC_Admin_Settings::add_error( __( $e->getMessage(), 'paymob-woocommerce' ) );
				}
			}
		} else {
			foreach ( $gatewayData as $key => $gateway ) {
				$logoPath = PAYMOB_PLUGIN_PATH . 'assets/img/' . strtolower( $key ) . '.png';
				// Skip downloading the logo if the logo URL is empty
				if ( ! empty( $gateway['logo'] ) ) {
					if ( ! file_exists( $logoPath ) ) {
						$ch = curl_init();
						curl_setopt( $ch, CURLOPT_HEADER, 0 );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
						curl_setopt( $ch, CURLOPT_URL, $gateway['logo'] );
						$data = curl_exec( $ch );
						curl_close( $ch );
						file_put_contents( $logoPath, $data );
					}
				}
			}
		}
	}
}
