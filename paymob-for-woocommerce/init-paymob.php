<?php
/**
 * Plugin Name: Paymob for WooCommerce
 * Description: PayMob Payment Gateway Integration for WooCommerce.
 * Version: 2.0.4
 * Author: Paymob
 * Author URI: https://paymob.com
 * Text Domain: paymob-woocommerce
 * Domain Path: /i18n/languages
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Requires Plugins: woocommerce
 * WC requires at least: 4.0
 * WC tested up to: 9.3
 * Tested up to: 6.7
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Copyright: © 2024 Paymob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PAYMOB_VERSION' ) ) {
	define( 'PAYMOB_VERSION', '2.0.4' );
}
if ( ! defined( 'PAYMOB_PLUGIN' ) ) {
	define( 'PAYMOB_PLUGIN', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'PAYMOB_PLUGIN_PATH' ) ) {
	define( 'PAYMOB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'PAYMOB_PLUGIN_NAME' ) ) {
	define( 'PAYMOB_PLUGIN_NAME', dirname( PAYMOB_PLUGIN ) );
}

require_once PAYMOB_PLUGIN_PATH . '/src/class_wc_paymob_initDependencies.php';
class Init_Paymob {
	protected static $instance = null;
	protected $gateways;

	public function __construct() {
		add_filter( 'plugin_row_meta', array( $this, 'add_row_meta' ), 10, 2 );
		add_action( 'activate_' . PAYMOB_PLUGIN, array( $this, 'install' ), 0 );
		add_action( 'plugins_loaded', array( $this, 'load' ), 0 );

		// Set redirect flag upon activation of PayMob plugin
		add_action( 'activated_plugin', array( $this, 'set_redirect_flag_on_activation' ) );
		// Check redirect flag and perform redirect with high priority
		add_action( 'admin_init', array( $this, 'redirect_after_activation' ), 1 );

		// Declare compatibility with WooCommerce features
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
				}
			}
		);
	}

	public static function add_row_meta( $links, $file ) {
		return WC_Paymob_Row_Meta::add_row_meta( $links, $file );
	}

	public static function install() {
		return WC_Paymob_Install::install();
	}

	// Set a flag in options table to trigger redirect after PayMob plugin activation
	function set_redirect_flag_on_activation( $plugin ) {
		return WC_Paymob_RedirectFlag::set_redirect_flag_on_activation($plugin);
	}

	// Check the redirect flag and perform redirect if true
	function redirect_after_activation() {
		
		return WC_Paymob_RedirectUrl::redirect_after_activation();
	}

	

	public static function uninstall() {
		return WC_Paymob_UnInstall::uninstall();
	}

	public function load() {
		return WC_Paymob_Loading::load();
	}
}

register_uninstall_hook( __FILE__, array( 'Init_Paymob', 'uninstall' ) );
new Init_Paymob();



