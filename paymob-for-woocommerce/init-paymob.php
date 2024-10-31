<?php
/**
 * Plugin Name: Paymob for WooCommerce
 * Description: PayMob Payment Gateway Integration for WooCommerce.
 * Version: 2.0.2
 * Author: Paymob
 * Author URI: https://paymob.com
 * Text Domain: paymob-woocommerce
 * Domain Path: /i18n/languages
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Requires Plugins: woocommerce
 * WC requires at least: 4.0
 * WC tested up to: 9.3
 * Tested up to: 6.6
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Copyright: © 2024 Paymob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PAYMOB_VERSION' ) ) {
	define( 'PAYMOB_VERSION', '2.0.2' );
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
		// Declare compatibility with custom order tables for WooCommerce.
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				}
			}
		);
		// Declare compatibility with checkout blocks for WooCommerce.
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
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

	public static function uninstall() {
		return WC_Paymob_UnInstall::uninstall();
	}

	public function load() {
		return WC_Paymob_Loading::load();
	}
}

register_uninstall_hook( __FILE__, array( 'Init_Paymob', 'uninstall' ) );
new Init_Paymob();
