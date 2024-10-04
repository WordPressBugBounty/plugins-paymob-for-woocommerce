<?php

/**
 * Plugin Name: Paymob for WooCommerce
 * Description: PayMob Payment Gateway Integration for WooCommerce.
 * Version: 1.0.10
 * Author: Paymob
 * Author URI: https://paymob.com
 *
 * Text Domain: paymob-woocommerce
 * Domain Path: /i18n/languages
 *
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 8.7
 * Tested up to: 6.5
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Copyright: © 2023 Paymob
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PAYMOB_VERSION' ) ) {
	define( 'PAYMOB_VERSION', '1.0.10' );
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

class Init_Paymob {

	protected static $instance = null;

	public function __construct() {
		add_filter( 'plugin_row_meta', array( $this, 'add_row_meta' ), 10, 2 );
		add_action( 'activate_plugin', array( $this, 'install' ), 0 );
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
		// load WooCommerce block.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'paymob_woocommerce_block_support' ) );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param mixed $links Plugin Row Meta.
	 * @param mixed $file  Plugin Base file.
	 *
	 * @return array
	 */
	public static function add_row_meta( $links, $file ) {

		if ( PAYMOB_PLUGIN === $file ) {
			$row_meta = array(
				'apidocs' => '<a href="' . esc_url( 'https://docs.paymob.com' ) . '" aria-label="' . esc_attr__( 'API documentation', 'paymob-woocommerce' ) . '">' . esc_html__( 'API docs', 'woocommerce' ) . '</a>',
				'support' => '<a href="' . esc_url( 'https://support.paymob.com/support/home' ) . '" aria-label="' . esc_attr__( 'Customer support', 'paymob-woocommerce' ) . '">' . esc_html__( 'Customer support', 'paymob-woocommerce' ) . '</a>',
			);
			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	public static function install() {
		if ( is_dir( WP_LANG_DIR . '/plugins/' ) ) {
			$arTrans         = 'paymob-woocommerce-ar';
			$transPath       = WP_LANG_DIR . '/plugins/' . $arTrans;
			$pluginTransPath = PAYMOB_PLUGIN_PATH . 'i18n/languages/' . $arTrans;
			copy( $pluginTransPath . '.mo', $transPath . '.mo' );
			copy( $pluginTransPath . '.po', $transPath . '.po' );
		}
		// Require parent plugin
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && ! array_key_exists( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins' ) ) ) ) {
			wp_die( esc_html__( 'Sorry, PayMob plugin requires WooCommerce to be installed and active.', 'paymob-woocommerce' ) );
		}
	}

	public function paymob_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) && class_exists( 'Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry' ) ) {
			require_once __DIR__ . '/checkout-block.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$container = Automattic\WooCommerce\Blocks\Package::container();
					$container->register(
						WC_Paymob_Blocks::class,
						function () {
							return new WC_Paymob_Blocks();
						}
					);
					$payment_method_registry->register( $container->get( WC_Paymob_Blocks::class ) );
				}
			);
		}
	}

	public static function load() {
		// load translation
		load_plugin_textdomain( 'paymob-woocommerce', false, PAYMOB_PLUGIN_NAME . '/i18n/languages' );
	}
}

new Init_Paymob();
if ( ! class_exists( 'Paymob' ) ) {
	include_once 'includes/helper/paymob.php';
}

if ( ! class_exists( 'Paymob_WooCommerce' ) ) {
	include_once 'paymob-for-woocommerce.php';
	new Paymob_WooCommerce();
}

if ( ! class_exists( 'Paymob_Order' ) ) {
	include_once 'includes/helper/paymob-order.php';
}
