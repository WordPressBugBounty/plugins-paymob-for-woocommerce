<?php
class Paymob_Filter_currency {

	public static function filter_payment_gateways_based_on_currency( $available_gateways ) {
		global $wpdb;

		// Retrieve all gateways from the paymob_gateways table.
		$gateways                   = PaymobAutoGenerate::get_db_gateways_data();
		$paymob_options             = get_option( 'woocommerce_paymob-main_settings' );
		$default_enabled            = isset( $paymob_options['enabled'] ) ? $paymob_options['enabled'] : 'no';
		$mismatched_ids             = array();
		$mismatched_currencies      = array();
		$mismatched_integration_ids = array();

		foreach ( $gateways as $gateway ) {
			$integration_ids = explode( ',', $gateway->integration_id );
			$gateway_id      = $gateway->gateway_id;

			// Check each integration ID individually.
			foreach ( $integration_ids as $integration_id ) {
				check_integration_id(
					trim( $integration_id ),
					$gateway_id,
					$mismatched_ids,
					$mismatched_currencies,
					$mismatched_integration_ids
				);
			}
		}

		// Filter out only the non-Paymob gateways that are mismatched or have default settings as 'no'.
		foreach ( $available_gateways as $gateway_id => $gateway ) {
			if ( ! in_array( $gateway_id, array( 'paymob' ), true ) &&
				( in_array( $gateway_id, array_column( $gateways, 'gateway_id' ), true ) &&
				( in_array( $gateway_id, $mismatched_ids, true ) || 'no' === $default_enabled ) )
			) {
				unset( $available_gateways[ $gateway_id ] );
			}
		}

		// Check if the Paymob gateway should be shown.
		if ( isset( $available_gateways['paymob'] ) ) {
			$paymob_gateway = $available_gateways['paymob'];
			$integration_id = $paymob_gateway->integration_id;
			// Check if the integration ID matches the store currency.
			if ( ! check_integration_id_match( $integration_id, get_woocommerce_currency() ) || 'no' === $default_enabled ) {
				unset( $available_gateways['paymob'] );
			}
		}

		static $error_message_displayed = false;

		if ( ! $error_message_displayed &&
			( 'paymob-main' === Paymob::filterVar( 'section' ) ||
			'paymob_add_gateway' === Paymob::filterVar( 'section' ) ||
			'paymob_list_gateways' === Paymob::filterVar( 'section' ) )
		) {
			if ( ! empty( $mismatched_integration_ids ) ) {
				$mismatched_ids_string        = implode( ', ', array_unique( $mismatched_integration_ids ) );
				$mismatched_currencies_string = implode( ', ', array_unique( $mismatched_currencies ) ); // Use unique to avoid duplicate currency entries.

				$message = sprintf(
					/* translators: %1$s is a comma-separated list of integration IDs. %2$s is a comma-separated list of currencies. */
					__( 'Payment Method(s) with the Integration ID(s)', 'paymob-woocommerce'). ' (%1$s) '. __( 'require(s) the store currency to be set to:', 'paymob-woocommerce'). ' %2$s.',
					$mismatched_ids_string,
					$mismatched_currencies_string
				);

				add_action(
					'admin_notices',
					function () use ( $message ) {
						echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
					}
				);

				$error_message_displayed = true;
			}
		}

		return $available_gateways;
	}
}
