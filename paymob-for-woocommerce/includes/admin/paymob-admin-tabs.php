<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$current_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'paymob-main';

return '<div class="paymob-admin-tab">
  <a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=paymob-main') . '" class="tablinks ' . ($current_section === 'paymob-main' ? 'active' : '') . '">' . __('Main Configuration', 'paymob-woocommerce') . '</a>
  <a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=paymob_list_gateways') . '" class="tablinks ' . ($current_section === 'paymob_list_gateways' ? 'active' : '') . '">' . __('Payment Integrations', 'paymob-woocommerce') . '</a>
  <a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=paymob_pixel') . '" class="tablinks ' . ($current_section === 'paymob_pixel' ? 'active' : '') . '">' . __('Card Embedded Settings', 'paymob-woocommerce') . '</a>
  <a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=paymob_add_gateway') . '" class="tablinks ' . ($current_section === 'paymob_add_gateway' ? 'active' : '') . '">' . __('Add Payment Integration', 'paymob-woocommerce') . '</a>

</div>';

/**   <a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=valu_widget') . '" class="tablinks ' . ($current_section === 'valu_widget' ? 'active' : '') . '">' . __('ValU Widget Settings', 'paymob-woocommerce') . '</a> 
 * **/