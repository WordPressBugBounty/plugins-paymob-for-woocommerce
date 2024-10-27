<?php
/**
 * Paymob Loading Data
 */
class WC_Paymob_Loading {

	public static function load() {
		global $wpdb;
		// Load translation
		load_plugin_textdomain( 'paymob-woocommerce', false, PAYMOB_PLUGIN_NAME . '/i18n/languages' );
		// Create table
		WC_Paymob_Tables::create_paymob_gateways_table();
		// Gateways Files Creation on Updates
		$gateways = PaymobAutoGenerate::get_db_gateways_data();
		WC_Paymob_HandelUpdate::handle_plugin_update( $gateways );
		WC_Paymob_GatewayData::getPaymobGatewayData();
		foreach ( $gateways as $gateway ) {
			new Paymob_WooCommerce( $gateway->gateway_id );

		}
	}
}
