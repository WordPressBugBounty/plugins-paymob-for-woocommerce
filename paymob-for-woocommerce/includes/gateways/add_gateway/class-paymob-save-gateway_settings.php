<?php

class Paymob_Save_Gateway_Settings {
	public static function save_paymob_add_gateway_settings() {

		global $current_section, $wpdb;

		if ( 'paymob_add_gateway' !== $current_section ) {
			return;
		}

		$paymob_options = get_option( 'woocommerce_paymob_settings' );
		$pub_key        = isset( $paymob_options['pub_key'] ) ? $paymob_options['pub_key'] : '';
		$sec_key        = isset( $paymob_options['sec_key'] ) ? $paymob_options['sec_key'] : '';
		$api_key        = isset( $paymob_options['api_key'] ) ? $paymob_options['api_key'] : '';

		if ( empty( $pub_key ) || empty( $sec_key ) || empty( $api_key ) ) {
			WC_Admin_Settings::add_error( __( 'Please ensure you are entering API, public and secret keys in the main Paymob configuration.', 'paymob-woocommerce' ) );
		} else {
			// $currency_errors       = array();
			$ids                   = array();
			//$integration_id_hidden = explode( ',', $paymob_options['integration_id_hidden'] );
			$integration_id        = Paymob::filterVar( 'integration_id', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'integration_id', 'POST' ) ) : '';

			// verify_integration_id( $integration_id_hidden, $integration_id, $currency_errors, $ids );
			// if ( ! empty( $currency_errors ) ) {
			// 	WC_Admin_Settings::add_error(
			// 		sprintf(
			// 		/* translators: %1$s is a comma-separated list of integration IDs. %2$s is a comma-separated list of currencies. */
			// 			__( 'Payment Method(s) with the Integration ID(s) %1$s require(s) the store currency to be set to: %2$s', 'paymob-woocommerce' ),
			// 			implode( ', ', $ids ),
			// 			$currency_errors[0]
			// 		)
			// 	);
			// 	return;
			// }

			$payment_enabled           = Paymob::filterVar( 'payment_enabled', 'POST' ) ? 'yes' : 'no';
			$payment_integrations_type = Paymob::filterVar( 'payment_integrations_type', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'payment_integrations_type', 'POST' ) ) : '';
			$payment_logo              = Paymob::filterVar( 'payment_logo', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'payment_logo', 'POST' ) ) : '';
			$checkout_title            = Paymob::filterVar( 'checkout_title', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'checkout_title', 'POST' ) ) : '';
			$checkout_description      = Paymob::filterVar( 'checkout_description', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'checkout_description', 'POST' ) ) : '';
			$default_url               = plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png';
			$logo                      = url_exists( $payment_logo ) ? $payment_logo : $default_url;

			$class_name                = 'Paymob_' . preg_replace( '/[^a-zA-Z0-9]+/', '_', ucwords( $payment_integrations_type ) );
			$payment_integrations_type = 'paymob-' . preg_replace( '/[^a-zA-Z0-9]+/', '-', strtolower( $payment_integrations_type ) );
			$gateway                   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}paymob_gateways WHERE gateway_id = %s", $payment_integrations_type ), OBJECT );

			$file_name = 'class-gateway-' . sanitize_file_name( $payment_integrations_type ) . '.php';

			if ( ! $gateway && ! empty( $payment_integrations_type ) ) {
				append_gateway_to_paymob_order( $payment_integrations_type );
				$ordering = $wpdb->get_var( "SELECT max(ordering) FROM {$wpdb->prefix}paymob_gateways" );
				++$ordering;

				$inserted = $wpdb->insert(
					$wpdb->prefix . 'paymob_gateways',
					array(
						'gateway_id'           => $payment_integrations_type,
						'file_name'            => $file_name,
						'class_name'           => sanitize_text_field( $class_name ),
						'checkout_title'       => sanitize_text_field( $checkout_title ),
						'checkout_description' => sanitize_text_field( $checkout_description ),
						'integration_id'       => $integration_id,
						'is_manual'            => '1',
						'ordering'             => $ordering,
					)
				);

				if ( false !== $inserted ) {
					$f_array = array(
						'class_name'           => $class_name,
						'gateway_id'           => $payment_integrations_type,
						'checkout_title'       => $checkout_title,
						'checkout_description' => $checkout_description,
						'file_name'            => $file_name,
					);
					PaymobAutoGenerate::generate_files( $f_array );

					// Clear the add gateway form.
					$options_to_update = array(
						'payment_enabled'           => 'no',
						'payment_integrations_type' => '',
						'payment_logo'              => $default_url,
						'checkout_title'            => '',
						'checkout_description'      => '',
						'integration_id'            => '',
					);
					foreach ( $options_to_update as $option_name => $option_value ) {
						update_option( $option_name, $option_value );
					}

					// Save default settings for the new gateway.
					$default_settings = array(
						'enabled'               => $payment_enabled,
						'single_integration_id' => $integration_id,
						'title'                 => $checkout_title,
						'description'           => $checkout_description,
						'logo'                  => $logo,
					);
					update_option( 'woocommerce_' . $payment_integrations_type . '_settings', $default_settings );

					// Redirect to the list of gateways page.
					wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paymob_list_gateways' ) );
					exit;
				} else {
					WC_Admin_Settings::add_error( __( 'Failed to insert gateway into database.', 'paymob-woocommerce' ) );
				}
			} else {
				WC_Admin_Settings::add_error( __( 'Gateway Already Exist.', 'paymob-woocommerce' ) );
			}
		}
	}
}
