<?php

class Paymob_Toggle_Gateway {

	public static function toggle_gateway() {
		check_ajax_referer( 'toggle_gateway_nonce', 'security' );

		$gateway_id     = sanitize_text_field( Paymob::filterVar( 'gateway_id', 'POST' ) );
		$current_status = get_option( 'woocommerce_' . $gateway_id . '_settings' )['enabled'];
		$enabled        = ( 'yes' === $current_status ) ? 'no' : 'yes';

		if ( 'yes' === $enabled ) {
			$paymob_options = get_option( 'woocommerce_paymob_settings' );
			$pub_key        = isset( $paymob_options['pub_key'] ) ? $paymob_options['pub_key'] : '';
			$sec_key        = isset( $paymob_options['sec_key'] ) ? $paymob_options['sec_key'] : '';
			$api_key        = isset( $paymob_options['api_key'] ) ? $paymob_options['api_key'] : '';

			if ( empty( $pub_key ) || empty( $sec_key ) || empty( $api_key ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'msg'     => 'Please ensure you are entering API, public, and secret keys in the main Paymob configuration.',
					)
				);
			}

			// $integration_id = sanitize_text_field( Paymob::filterVar( 'integration_id', 'POST' ) );
			// $ids            = array();
			// if ( isset( $paymob_options['integration_id_hidden'] ) && ! empty( $paymob_options['integration_id_hidden'] ) ) {
			// 	$integration_id_hidden = explode( ',', $paymob_options['integration_id_hidden'] );
			// 	$currency_errors       = array();
			// 	//verify_integration_id( $integration_id_hidden, $integration_id, $currency_errors, $ids, $gateway_id );

			// 	if ( ! empty( $currency_errors ) ) {
			// 		wp_send_json_error(
			// 			array(
			// 				'success' => false,
			// 				'msg'     => 'Payment Method(s) with the Integration ID(s) ' . implode( ', ', array_unique( $ids ) ) . ' require(s) the store currency to be set to: ' . implode( ', ', array_unique( $currency_errors ) ),
			// 			)
			// 		);
			// 	}
			// }
		}

		$settings = get_option( 'woocommerce_' . $gateway_id . '_settings', array() );
		// Merge the new status with the existing settings.
		$settings['enabled'] = $enabled;
		// Update the gateway settings with the new status.
		update_option( 'woocommerce_' . $gateway_id . '_settings', $settings );
		// Register the Framework into Paymob if enabled.
		register_frameworks();

		wp_send_json_success(
			array(
				'success' => true,
				'msg'     => __( 'Payment Method status updated successfully.','paymob-woocommerce'),
			)
		);

		wp_die();
	}
}
