<?php

class Paymob_Apply_Gateway_Order {

	public static function apply_paymob_gateway_order( $available_gateways ) {
		$paymob_options  = get_option( 'woocommerce_paymob-main_settings' );
		$default_enabled = isset( $paymob_options['enabled'] ) ? $paymob_options['enabled'] : 'no';

		if ( is_checkout() && 'yes' === $default_enabled ) {
			$order         = get_option( 'paymob_gateway_order', array() );
			$gateway_order = (array) get_option( 'woocommerce_gateway_order' );

			if ( ! empty( $order ) ) {
				$sorted_gateways   = array();
				$paymob_main_index = array_search( 'paymob-main', array_keys( $available_gateways ), true );

				// Sort gateways according to the saved order.
				foreach ( $order as $gateway_id ) {
					if ( isset( $available_gateways[ $gateway_id ] ) ) {
						$sorted_gateways[ $gateway_id ] = $available_gateways[ $gateway_id ];
						unset( $available_gateways[ $gateway_id ] );
					}
				}

				// Add paymob-main at the top and sub-gateways next.
				if ( false !== $paymob_main_index ) {
					$available_gateways = array_slice( $available_gateways, 0, $paymob_main_index, true ) +
						array( 'paymob-main' => $available_gateways['paymob-main'] ) +
						$sorted_gateways +
						array_slice( $available_gateways, $paymob_main_index + 1, null, true );
				} else {
					$available_gateways = array( 'paymob-main' => $available_gateways['paymob-main'] ) + $sorted_gateways + $available_gateways;
				}
			}

			// Update the order in WooCommerce settings.
			$gateway_order    = 0;
			$ordered_gateways = array();
			foreach ( $available_gateways as $index => $gateway_id ) {
				$ordered_gateways[ $index ] = $gateway_order;
				++$gateway_order;
			}
			// Update the WooCommerce gateway order option.
			update_option( 'woocommerce_gateway_order', $ordered_gateways );

			// Ensure that paymob-main is unset from the list after reordering.
			if ( isset( $available_gateways['paymob-main'] ) ) {
				unset( $available_gateways['paymob-main'] );
			}
		}

		return $available_gateways;
	}
}
