<?php
/**
 * Plugin Name: Paymob for WooCommerce
 * Description: PayMob Payment Gateway Integration for WooCommerce.
 * Version: 2.0.0
 * Author: Paymob
 * Author URI: https://paymob.com
 * Text Domain: paymob-woocommerce
 * Domain Path: /i18n/languages
 *
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Requires Plugins: woocommerce
 * WC requires at least: 4.0
 * WC tested up to: 9.3
 * Tested up to: 6.6
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Copyright: © 2024 Paymob
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PAYMOB_VERSION' ) ) {
	define( 'PAYMOB_VERSION', '2.0.0' );
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
		if ( PAYMOB_PLUGIN === $file ) {
			$row_meta = array(
				'apidocs' => '<a href="' . esc_url( 'https://docs.paymob.com' ) . '" aria-label="' . esc_attr__( 'API documentation', 'paymob-woocommerce' ) . '">' . esc_html__( 'API docs', 'paymob-woocommerce' ) . '</a>',
				'support' => '<a href="' . esc_url( 'https://support.paymob.com/support/home' ) . '" aria-label="' . esc_attr__( 'Customer support', 'paymob-woocommerce' ) . '">' . esc_html__( 'Customer support', 'paymob-woocommerce' ) . '</a>',
			);
			return array_merge( $links, $row_meta );
		}
		return (array) $links;
	}
	public static function install() {
		global $wpdb;
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
		self::create_paymob_gateways_table();
	}
	public static function uninstall() {
		global $wpdb;
		delete_option( 'woocommerce_paymob-main_settings' );

		$gateways = PaymobAutoGenerate::get_db_gateways_data();
		foreach ( $gateways as $gateway ) {
			if ( 'paymob' !== $gateway->gateway_id ) {
				delete_option( 'woocommerce_' . $gateway->gateway_id . '_settings' );
			}
		}
		delete_option( 'paymob_gateway_order' );
		delete_option( 'woocommerce_paymob_country' );
		delete_option( 'woocommerce_paymob_gateway_data' );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}paymob_gateways" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}paymob_cards_token" );
	}
	public function load() {
		global $wpdb;

		// Load translation
		load_plugin_textdomain( 'paymob-woocommerce', false, PAYMOB_PLUGIN_NAME . '/i18n/languages' );
		// Create table
		self::create_paymob_gateways_table();
		// Gateways Files Creation on Updates
		$gateways = PaymobAutoGenerate::get_db_gateways_data();
		$this->handle_plugin_update( $gateways );
		$this->getPaymobGatewayData();
		foreach ( $gateways as $gateway ) {
			new Paymob_WooCommerce( $gateway->gateway_id );
		}
	}
	public function getPaymobGatewayData() {
		$gatewayData = get_option( 'woocommerce_paymob_gateway_data' );
		if ( empty( $gatewayData ) ) {
			$mainOptions = get_option( 'woocommerce_paymob-main_settings' );
			if ( ! empty( $mainOptions ) ) {
				$debug          = isset( $mainOptions['debug'] ) ? $mainOptions['debug'] : '';
				$debug          = 'yes' === $debug ? '1' : '0';
				$paymobReq      = new Paymob( $debug, WC_LOG_DIR . 'paymob.log' );
				$conf['secKey'] = isset( $mainOptions['sec_key'] ) ? $mainOptions['sec_key'] : '';

				$gatewayData = $paymobReq->getPaymobGateways( $conf['secKey'], PAYMOB_PLUGIN_PATH . 'assets/img/' );

				update_option( 'woocommerce_paymob_gateway_data', $gatewayData );
			}
		} else {
			foreach ( $gatewayData as $key => $gateway ) {
				$logoPath = PAYMOB_PLUGIN_PATH . 'assets/img/' . strtolower( $key ) . '.png';
				// Skip downloading the logo if the logo URL is empty
				if ( ! empty( $gateway['logo'] ) ) {
					if ( ! file_exists( $logoPath ) ) {
						$ch = curl_init();
						curl_setopt( $ch, CURLOPT_HEADER, 0 );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
						curl_setopt( $ch, CURLOPT_URL, $gateway['logo'] );
						$data = curl_exec( $ch );
						curl_close( $ch );
						file_put_contents( $logoPath, $data );
					}
				}
			}
		}
	}
	public static function create_paymob_gateways_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}paymob_gateways (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            gateway_id varchar(100) NOT NULL,
            file_name varchar(100) DEFAULT '' NOT NULL,
            class_name varchar(100) DEFAULT '' NOT NULL,
            checkout_title varchar(100) DEFAULT '' NOT NULL,
            checkout_description LONGTEXT DEFAULT '' NOT NULL,
            integration_id varchar(3000) DEFAULT '' NOT NULL,
            is_manual varchar(56) DEFAULT '' NOT NULL,
            ordering int(10) DEFAULT 0 NOT NULL,
            PRIMARY KEY (id),
            KEY gateway_id (gateway_id),
            UNIQUE (gateway_id)
        ) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}paymob_cards_token (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			token varchar(56) DEFAULT '' NOT NULL,
			masked_pan varchar(19) DEFAULT '' NOT NULL,
			card_subtype varchar(56) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id)
		) $charset_collate;";
		dbDelta( $sql );
	}
	public function handle_plugin_update( $gateways ) {
		// Retrieve the main settings
		$mainOptions = get_option( 'woocommerce_paymob-main_settings' );
		// Check if main settings are empty
		if ( empty( $mainOptions ) ) {
			// Retrieve the Paymob settings
			$paymobSettings = get_option( 'woocommerce_paymob_settings' );
			// Check if Paymob settings are not empty
			if ( ! empty( $paymobSettings ) ) {
				// Prepare the main settings with values from Paymob settings
				$mainSettings = array(
					'enabled'    => isset( $paymobSettings['enabled'] ) ? $paymobSettings['enabled'] : '',
					'sec_key'    => isset( $paymobSettings['sec_key'] ) ? $paymobSettings['sec_key'] : '',
					'pub_key'    => isset( $paymobSettings['pub_key'] ) ? $paymobSettings['pub_key'] : '',
					'api_key'    => isset( $paymobSettings['api_key'] ) ? $paymobSettings['api_key'] : '',
					'empty_cart' => isset( $paymobSettings['empty_cart'] ) ? $paymobSettings['empty_cart'] : '',
					'debug'      => isset( $paymobSettings['debug'] ) ? $paymobSettings['debug'] : '',
					'has_items'  => 'no',
				);
				// Update the main settings
				update_option( 'woocommerce_paymob-main_settings', $mainSettings );

				$paymob_default_settings = array(
					'enabled'               => 'no',
					'sec_key'               => isset( $paymobSettings['sec_key'] ) ? $paymobSettings['sec_key'] : '',
					'pub_key'               => isset( $paymobSettings['pub_key'] ) ? $paymobSettings['pub_key'] : '',
					'api_key'               => isset( $paymobSettings['api_key'] ) ? $paymobSettings['api_key'] : '',
					'title'                 => isset( $paymobSettings['title'] ) ? $paymobSettings['title'] : '',
					'description'           => isset( $paymobSettings['description'] ) ? $paymobSettings['description'] : '',
					'integration_id'        => isset( $paymobSettings['integration_id'] ) ? $paymobSettings['integration_id'] : '',
					'integration_id_hidden' => isset( $paymobSettings['integration_id_hidden'] ) ? $paymobSettings['integration_id_hidden'] : '',
					'hmac_hidden'           => isset( $paymobSettings['hmac_hidden'] ) ? $paymobSettings['hmac_hidden'] : '',
					'empty_cart'            => isset( $paymobSettings['empty_cart'] ) ? $paymobSettings['empty_cart'] : '',
					'debug'                 => isset( $paymobSettings['debug'] ) ? $paymobSettings['debug'] : '',
					'logo'                  => plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png',
				);
				update_option( 'woocommerce_paymob_settings', $paymob_default_settings );
			}
		}
		// display enabled gateways count
		if ( ! empty( $mainOptions ) ) {
			PaymobAutoGenerate::enabled_gateways_count( $gateways );
		}

		$debug = isset( $mainOptions['debug'] ) ? $mainOptions['debug'] : '';
		$debug = 'yes' === $debug ? '1' : '0';
		// Load integrations IDs
		$conf['apiKey'] = isset( $mainOptions['api_key'] ) ? $mainOptions['api_key'] : '';
		$conf['pubKey'] = isset( $mainOptions['pub_key'] ) ? $mainOptions['pub_key'] : '';
		$conf['secKey'] = isset( $mainOptions['sec_key'] ) ? $mainOptions['sec_key'] : '';
		if ( ! empty( $conf['apiKey'] ) && ! empty( $conf['pubKey'] ) && ! empty( $conf['secKey'] ) ) {

			try {
				$paymob_country = get_option( 'woocommerce_paymob_country' );
				if ( empty( $paymob_country ) ) {
					$paymobReq = new Paymob( $debug, WC_LOG_DIR . 'paymob.log' );

					$result = $paymobReq->authToken( $conf );
					$ids    = array();
					foreach ( $result['integrationIDs'] as $value ) {
						$ids[] = trim( $value['id'] );
					}
					PaymobAutoGenerate::register_framework( $ids );
					$gatewayData = get_option( 'woocommerce_paymob_gateway_data' );
					if ( empty( $gatewayData ) ) {
						$gatewayData = $paymobReq->getPaymobGateways( $conf['secKey'], PAYMOB_PLUGIN_PATH . 'assets/img/' );
						update_option( 'woocommerce_paymob_gateway_data', $gatewayData );
					}
					update_option( 'woocommerce_paymob_country', Paymob::getCountryCode( $conf['pubKey'] ) );
					PaymobAutoGenerate::create_gateways( $result, 0, $gatewayData );
				}
			} catch ( \Exception $e ) {
				WC_Admin_Settings::add_error( __( $e->getMessage(), 'paymob-woocommerce' ) );
			}
		}
		// Load gateways from db
		foreach ( $gateways as $gateway ) {
			// Check if properties are set and provide a default value if not
			$class_name                = isset( $gateway->class_name ) ? $gateway->class_name : '';
			$payment_integrations_type = isset( $gateway->gateway_id ) ? $gateway->gateway_id : '';
			$checkout_title            = isset( $gateway->checkout_title ) ? $gateway->checkout_title : '';
			$checkout_description      = isset( $gateway->checkout_description ) ? $gateway->checkout_description : '';
			$file_name                 = isset( $gateway->file_name ) ? $gateway->file_name : '';
			$f_array                   = array(
				'class_name'           => $class_name,
				'gateway_id'           => $payment_integrations_type,
				'checkout_title'       => $checkout_title,
				'checkout_description' => $checkout_description,
				'file_name'            => $file_name,
			);
			PaymobAutoGenerate::generate_files( $f_array );
		}
	}
}

register_uninstall_hook( __FILE__, array( 'Init_Paymob', 'uninstall' ) );
new Init_Paymob();

if ( ! class_exists( 'PaymobAutoGenerate' ) ) {
	include_once 'includes/helper/paymob-auto-generate.php';
}
require_once 'includes/helper/toggle-paymob-gateways.php';
require_once 'includes/helper/save-cards.php';
require_once 'includes/gateways/add-paymob-gateway.php';

if ( ! class_exists( 'Checkout_Blocks' ) ) {
	include_once 'includes/blocks/checkout-blocks.php';
}

if ( ! class_exists( 'Paymob' ) ) {
	include_once 'includes/helper/paymob.php';
}

if ( ! class_exists( 'Paymob_WooCommerce' ) ) {
	include_once 'paymob-for-woocommerce.php';
	new Paymob_WooCommerce( 'paymob-main' );
}

if ( ! class_exists( 'Paymob_Order' ) ) {
	include_once 'includes/helper/paymob-order.php';
}
