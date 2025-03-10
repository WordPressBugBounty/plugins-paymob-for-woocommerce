<?php

class Paymob_Apply_Gateway_Order {

	public static function apply_paymob_gateway_order( $available_gateways ) {
		$paymob_options  = get_option( 'woocommerce_paymob-main_settings' );
		$default_enabled = isset( $paymob_options['enabled'] ) ? $paymob_options['enabled'] : 'no';
	
		if ( is_checkout() && 'yes' === $default_enabled ) {
			$order = get_option( 'paymob_gateway_order', array() );
	
			// Initialize sorted gateways array
			$sorted_gateways = array();
			
			// Get Paymob gateways
			$paymob_main  = isset( $available_gateways['paymob-main'] ) ? $available_gateways['paymob-main'] : null;
			$paymob_pixel = isset( $available_gateways['paymob-pixel'] ) ? $available_gateways['paymob-pixel'] : null;
	
			// Remove Paymob gateways from the original list to reposition them later
			unset( $available_gateways['paymob-main'], $available_gateways['paymob-pixel'] );
	
			// Reorder based on saved order
			foreach ( $order as $gateway_id ) {
				if ( isset( $available_gateways[ $gateway_id ] ) ) {
					$sorted_gateways[ $gateway_id ] = $available_gateways[ $gateway_id ];
					unset( $available_gateways[ $gateway_id ] );
				}
			}
	
			// Start the new gateway order
			$new_gateways = array();
	
			//  Ensure `paymob-pixel` is first
			if ( $paymob_pixel ) {
				$new_gateways['paymob-pixel'] = $paymob_pixel;
			}
	
			// Ensure `paymob-main` is second
			if ( $paymob_main ) {
				$new_gateways['paymob-main'] = $paymob_main;
			}
	
			//  Append other sorted gateways + remaining gateways
			$available_gateways = $new_gateways + $sorted_gateways + $available_gateways;
	
			// Update WooCommerce gateway order settings
			$gateway_order = 0;
			$ordered_gateways = array();
			foreach ( array_keys( $available_gateways ) as $index ) {
				$ordered_gateways[ $index ] = $gateway_order;
				$gateway_order++;
			}
			// Ensure that paymob-main is unset from the list after reordering.
			if ( isset( $available_gateways['paymob-main'] ) ) {
				unset( $available_gateways['paymob-main'] );
			}
			update_option( 'woocommerce_gateway_order', $ordered_gateways );
		}
	
		return $available_gateways;
	}
	
}


