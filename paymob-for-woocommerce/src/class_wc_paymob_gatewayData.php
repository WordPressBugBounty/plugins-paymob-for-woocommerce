<?php
/**
 * Paymob Gateway Data
 */
class WC_Paymob_GatewayData
{

	public static function getPaymobGatewayData()
	{
		$gatewayData = get_option('woocommerce_paymob_gateway_data');
		$lastFailure = get_option('woocommerce_paymob_gateway_data_failure');

		// Only proceed if there's no cached data and no recent failure
		if (empty($gatewayData) && empty($lastFailure)) {
			$mainOptions = get_option('woocommerce_paymob-main_settings');
			if (!empty($mainOptions)) {
				$debug = isset($mainOptions['debug']) ? $mainOptions['debug'] : '';
				$debug = 'yes' === $debug ? '1' : '0';
				try {
					$paymobReq = new Paymob($debug, WC_LOG_DIR . 'paymob-auth.log');
					$conf['secKey'] = isset($mainOptions['sec_key']) ? $mainOptions['sec_key'] : '';
					$gatewayData = $paymobReq->getPaymobGateways($conf['secKey'], PAYMOB_PLUGIN_PATH . 'assets/img/');
					update_option('woocommerce_paymob_gateway_data', $gatewayData);
					delete_option( 'woocommerce_paymob_gateway_data_failure' );
				} catch (\Exception $e) {
					WC_Admin_Settings::add_error(__($e->getMessage(), 'paymob-woocommerce'));
					update_option('woocommerce_paymob_gateway_data_failure', current_time('timestamp')); // Record failure time
				}
			}
		}else {
			if (!empty($gatewayData)) {
				foreach ($gatewayData as $key => $gateway) {
					if (!empty($gateway['logo'])) {
						Paymob::maybeDownloadGatewayLogo($key, $gateway['logo'], PAYMOB_PLUGIN_PATH . 'assets/img/');
					}
				}
			}
		}
	}
}