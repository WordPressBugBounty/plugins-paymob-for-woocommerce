<?php
/**
 * Paymob Loading Data
 */
class WC_Paymob_UnInstall {

	public static function uninstall() {
		global $wpdb;
		delete_option( 'woocommerce_paymob-main_settings' );

		$gateways = PaymobAutoGenerate::get_db_gateways_data();
		foreach ( $gateways as $gateway ) {
			if ( 'paymob' !== $gateway->gateway_id ) {
				delete_option( 'woocommerce_' . $gateway->gateway_id . '_settings' );
			}
		}
		delete_option( 'paymob_gateway_order' );
		delete_option( 'woocommerce_paymob_country' );
		delete_option( 'woocommerce_paymob_gateway_data' );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}paymob_gateways" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}paymob_cards_token" );
	}
}
