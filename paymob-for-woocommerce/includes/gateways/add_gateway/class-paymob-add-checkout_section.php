<?php

class Paymob_Checkout_Section {

	public static function add_paymob_checkout_section( $sections ) {

		global $wpdb;
		$sections    = array();
		$gateway_ids = array();
		$gateways    = PaymobAutoGenerate::get_db_gateways_data();
		foreach ( $gateways as $gateway ) {
			$gateway_ids[] = $gateway->gateway_id;
		}
		if (
			Paymob::filterVar( 'section' ) && ( in_array( Paymob::filterVar( 'section' ), $gateway_ids, true ) || 'paymob-main' === Paymob::filterVar( 'section' ) ||
				'paymob_add_gateway' === Paymob::filterVar( 'section' ) ||
				'paymob_list_gateways' === Paymob::filterVar( 'section' ) )
		) {

			$sections = include PAYMOB_PLUGIN_PATH . 'includes/admin/paymob_checkout_sections.php';
		}
		return $sections;
	}
}
