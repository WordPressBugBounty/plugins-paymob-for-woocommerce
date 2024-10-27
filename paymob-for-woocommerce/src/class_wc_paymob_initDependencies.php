<?php
// add  init paymob Dependencies

if ( ! class_exists( 'PaymobAutoGenerate' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/paymob-auto-generate.php';
}
require_once PAYMOB_PLUGIN_PATH . '/includes/helper/toggle-paymob-gateways.php';
require_once PAYMOB_PLUGIN_PATH . '/includes/helper/save-cards.php';
require_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add-paymob-gateway.php';

if ( ! class_exists( 'Checkout_Blocks' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/blocks/checkout-blocks.php';
}

if ( ! class_exists( 'Paymob' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/paymob.php';
}

if ( ! class_exists( 'Paymob_WooCommerce' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/src/paymob-for-woocommerce.php';
	new Paymob_WooCommerce( 'paymob-main' );
}

if ( ! class_exists( 'Paymob_Order' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/paymob-order.php';
}

if ( ! class_exists( 'WC_Paymob_Loading' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/src/class_wc_paymob_loading.php';
}
if ( ! class_exists( 'WC_Paymob_Tables' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/src/class_wc_paymob_paymobTable.php';
}
if ( ! class_exists( 'WC_Paymob_HandelUpdate' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/src/class_wc_paymob_handleUpdate.php';
}

if ( ! class_exists( 'WC_Paymob_GatewayData' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/src/class_wc_paymob_gatewayData.php';
}

if ( ! class_exists( 'WC_Paymob_Install' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/src/class_wc_paymob_Install.php';
}
if ( ! class_exists( 'WC_Paymob_UnInstall' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/src/class_wc_paymob_uninstall.php';
}

if ( ! class_exists( 'WC_Paymob_Row_Meta' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/src/class_wc_paymob_rowMeta.php';
}
// add html admin views init Dependencies

if ( ! class_exists( 'MainHtmlInclude' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/admin/views/includehtml.php';
}

if ( ! class_exists( 'Paymob_List_Gateways' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/admin/paymob-list_gateway_table.php';
}
// add Script init Dependencies

if ( ! class_exists( 'Paymob_Scripts' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/admin/scripts/paymob_admin_scripts.php';
}
// add Style init Dependencies

if ( ! class_exists( 'Paymob_Style' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/admin/styles/paymob_admin_styles.php';
}

// add add_gateway init Dependencies

if ( ! class_exists( 'Paymob_Gateway_Settings' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-add-gateway-settings.php';
}

if ( ! class_exists( 'Paymob_Checkout_Section' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-add-checkout_section.php';
}

if ( ! class_exists( 'Paymob_Save_Gateway_Settings' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-save-gateway_settings.php';
}

if ( ! class_exists( 'Paymob_Verify_IntegrationID' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-verify-integrationID.php';
}

if ( ! class_exists( 'Paymob_Url_Exists' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-url-exists.php';
}

if ( ! class_exists( 'Paymob_Append_Gateway' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-append-gateway-to-paymob-order.php';
}

if ( ! class_exists( 'Paymob_List_Gateways_Settings' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-list-gateways-settings.php';
}

if ( ! class_exists( 'Paymob_Custom_Gateways_Table' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-create-custom-gateways-table.php';
}
if ( ! class_exists( 'Paymob_Reset_gateways' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-reset-gateways.php';
}
if ( ! class_exists( 'Paymob_Save_Gateway_Order' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-save-paymob-gateway-order.php';
}
if ( ! class_exists( 'Paymob_Apply_Gateway_Order' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-apply-paymob-gateway-order.php';
}

if ( ! class_exists( 'Paymob_Delete_Gateway' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-delete-gateway.php';
}

if ( ! class_exists( 'Paymob_Remove_Gateway_From_Order' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-remove-gateway-from-paymob-order.php';
}

if ( ! class_exists( 'Paymob_Toggle_Gateway' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-toggle-gateway.php';
}

if ( ! class_exists( 'Paymob_Register_Frameworks' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-register-frameworks.php';
}

if ( ! class_exists( 'Paymob_Gateways_HideCss' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-gateways-to-hide-css.php';
}

if ( ! class_exists( 'Paymob_List_Gateways_Style' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-list-gateways-styles.php';
}

if ( ! class_exists( 'Paymob_Admin_Scripts' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-enqueue-admin-scripts.php';
}

if ( ! class_exists( 'Paymob_Hide_Save_Button' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/add_gateway/class-paymob-hide-save-changes-button.php';
}
// add Save Cards init Dependencies

if ( ! class_exists( 'Paymob_Delete_Confirmation_Model' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/save_cards/class-paymob-add-delete-confirmation-modal.php';
}
if ( ! class_exists( 'Paymob_Save_Cards_Endpoints' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/save_cards/class-paymob-add-saved-cards-endpoints.php';
}
if ( ! class_exists( 'Paymob_Save_Cards_Query_Vars' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/save_cards/class-paymob-add-saved-cards-query-vars.php';
}
if ( ! class_exists( 'Paymob_Save_Cards_Tab' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/save_cards/class-paymob-add-saved-cards-tab.php';
}
if ( ! class_exists( 'Paymob_Display_Save_Cards' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/save_cards/class-paymob-display-saved-cards.php';
}
if ( ! class_exists( 'Paymob_Enqueue_Style' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/save_cards/class-paymob-enqueue-saved-cards-styles.php';
}
if ( ! class_exists( 'Paymob_Handle_Card_Deletion' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/save_cards/class-paymob-handle-card-deletion.php';
}
if ( ! class_exists( 'Paymob_Save_Cards_Title' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/save_cards/class-paymob-saved-cards-title.php';
}

// add Toggle Gateway init Dependencies
if ( ! class_exists( 'Paymob_Custom_Model' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/toggle_gateways/class-paymob-add-custom-modal-html.php';
}
if ( ! class_exists( 'Paymob_Check_IntergrationID' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/toggle_gateways/class-paymob-check-integrationID.php';
}
if ( ! class_exists( 'Paymob_Check_IntergrationMatch' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/toggle_gateways/class-paymob-check-intergation_match.php';
}
if ( ! class_exists( 'Paymob_Disable_Confirmation' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/toggle_gateways/class-paymob-disable-gateway-confirmation-script.php';
}
if ( ! class_exists( 'Paymob_Filter_currency' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/toggle_gateways/class-paymob-filter-on-currency.php';
}
if ( ! class_exists( 'Paymob_Handel_Toggle' ) ) {
	include_once PAYMOB_PLUGIN_PATH . '/includes/helper/toggle_gateways/class-paymob-handle-toggle-gateway.php';
}


require_once ABSPATH . 'wp-admin/includes/upgrade.php';
