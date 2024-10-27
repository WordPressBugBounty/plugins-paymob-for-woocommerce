<?php

class Paymob_List_Gateways_Settings {

	public static function paymob_list_gateways_section_settings( $settings, $current_section ) {
		global $wpdb;

		if ( 'paymob_list_gateways' === $current_section ) {
			$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}paymob_gateways ORDER BY ordering", OBJECT );

			$custom_settings = include PAYMOB_PLUGIN_PATH . 'includes/admin/paymob-custom_list_setting.php';

			$table_body = '';

			if ( ! empty( $results ) ) {
				foreach ( $results as $gateway ) {

					$table_body .= Paymob_List_Gateways::paymob_list_gateways_table( $gateway );
				}
			} else {
				$table_body .= Paymob_List_Gateways::paymob_not_found_record_table();
			}

			echo '<script>window.paymob_gateways_table_body = ' . wp_json_encode( $table_body ) . ';</script>';
			include_once PAYMOB_PLUGIN_PATH . '/includes/admin/views/htmlsviews/html_loader_paymob.php';
			$settings = array_merge( $settings, $custom_settings );
		}

		return $settings;
	}
}
