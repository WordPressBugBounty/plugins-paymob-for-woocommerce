<?php

class Paymob_Apply_Gateway_Order {

	public static function apply_paymob_gateway_order( $available_gateways ) {
		$paymob_options  = get_option( 'woocommerce_paymob-main_settings' );
		$default_enabled = isset( $paymob_options['enabled'] ) ? $paymob_options['enabled'] : 'no';

		if ( is_checkout() && 'yes' === $default_enabled ) {
			$order = get_option( 'paymob_gateway_order', array() );

			// Collect Paymob child gateways (except main)
			$paymob_children = array();
			foreach ( $order as $gateway_id ) {
				if (
					isset( $available_gateways[ $gateway_id ] )
					&& $gateway_id !== 'paymob-main'
				) {
					$paymob_children[ $gateway_id ] = $available_gateways[ $gateway_id ];
					unset( $available_gateways[ $gateway_id ] ); // temporarily remove them
				}
			}

			// Now build a new array, inserting children right after 'paymob-main'
			$new_gateways = array();
			foreach ( $available_gateways as $id => $gateway ) {
				if ( $id === 'paymob-main' ) {
					// Insert the children after 'paymob-main'
					$new_gateways += $paymob_children;
					// Skip adding 'paymob-main' itself
					continue;
				}
				$new_gateways[ $id ] = $gateway;
			}

			return $new_gateways;
		}

		return $available_gateways;
	}
}





