<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Paymob_List_Gateways {

	public static function paymob_list_gateways_table( $gateway ) {
		$table_body     = '';
		$edit_url       = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $gateway->gateway_id );
		$gateway_id     = $gateway->gateway_id;
		$gateway_option = get_option( 'woocommerce_' . $gateway_id . '_settings' );
		$title          = isset( $gateway_option['title'] ) ? $gateway_option['title'] : 's';
		$description    = isset( $gateway_option['description'] ) ? $gateway_option['description'] : 'z';
		$logo           = isset( $gateway_option['logo'] ) ? $gateway_option['logo'] : plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png';
		$integration_id = ( 'paymob' === $gateway_id ) ? $gateway->integration_id : $gateway_option['single_integration_id'];
		$enabled        = isset( $gateway_option['enabled'] ) ? $gateway_option['enabled'] : 'no';
		$checked        = 'yes' === $enabled ? 'checked' : '';

		// Handling long descriptions.
		$short_description = strlen( $description ) > 100 ? substr( $description, 0, 100 ) . '...' : $description;
		$show_more_link    = strlen( $description ) > 100 ? '<a href="javascript:void(0);" class="show-more">Show More</a>' : '';

		$row_html  = '<tr data-gateway-id="' . esc_attr( $gateway_id ) . '">';
		$row_html .= '<td style="cursor: move;"><span class="dashicons dashicons-editor-justify"></span></td>';
		$row_html .= '<td><input type="checkbox" class="enable-checkbox" data-gateway-id="' . $gateway_id . '" data-integration-id="' . $integration_id . '" ' . $checked . ' /></td>';
		$row_html .= '<td>' . esc_html( $gateway_id ) . '</td>';
		$row_html .= '<td>' . esc_html( $title ) . '</td>';
		$row_html .= '<td><span class="short-description">' . esc_html( $short_description ) . '</span><span class="full-description" style="display:none;">' . esc_html( $description ) . '</span>' . $show_more_link . '</td>';
		$row_html .= '<td>' . esc_html( $integration_id ) . '</td>';
		$row_html .= '<td><img style="max-width: 70px;" src="' . esc_url( $logo ) . '" /></td>';
		$row_html .= '<td><a href="' . esc_url( $edit_url ) . '" class="button button-secondary">' . __( 'Edit', 'paymob-woocommerce' ) . '</a> ';

		if ( '0' === $gateway->is_manual ) {
			$row_html .= ' <button type="button" class="button" disabled="disabled">' . __( 'Remove', 'paymob-woocommerce' ) . '</button></td>';
		} else {
			$row_html .= ' <button type="button" class="button remove-button button-primary" data-gateway-id="' . $gateway_id . '">Remove</button></td>';
		}
		$row_html   .= '</tr>';
		$table_body .= $row_html;
		return $table_body;
	}

	public static function paymob_not_found_record_table() {
		$table_body  = '';
		$table_body .= '<tr><td colspan="8">' . __( 'No gateways found.', 'paymob-woocommerce' ) . '</td></tr>';
		return $table_body;
	}
}
