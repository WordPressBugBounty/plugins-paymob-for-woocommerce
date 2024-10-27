<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Paymob_Main_Gateway extends Paymob_Payment {


	public $id;
	public $method_title;
	public $method_description;
	public $has_fields;
	public $callback_note;
	public $has_items_note;
	public $extra_note;
	public $save_changes_note;
	public function __construct() {

		// config
		$this->id                 = 'paymob-main';
		$this->method_title       = $this->title = __( 'Paymob', 'paymob-woocommerce' );
		$this->method_description = __( 'Accept payment through Paymob payment provider.', 'paymob-woocommerce' );
		$this->description        = __( 'Main Configuration', 'paymob-woocommerce' );
		parent::__construct();
		// config
		$this->init_settings();
		// fields
		foreach ( $this->settings as $key => $val ) {
			$this->$key = $val;
		}
		// add_action( 'wp_ajax_reset_paymob_gateways', array( $this, 'reset_paymob_gateways' ), 1 );
	}
	public function init_form_fields() {
		$this->form_fields = include PAYMOB_PLUGIN_PATH . 'includes/admin/paymob-main.php';
	}
	/**
	 * Return whether or not Paymob payment method requires setup.
	 *
	 * @return bool
	 */
	public function needs_setup() {
		if ( empty( $this->pub_key ) || empty( $this->sec_key ) || empty( $this->api_key ) ) {
			return true;
		}
		return false;
	}
	public function process_admin_options() {
		global $wpdb;
		// Fetch the posted values
		$post_data = $this->get_post_data();

		// Get current settings
		$paymobOptions   = get_option( 'woocommerce_paymob-main_settings' );
		$default_enabled = isset( $paymobOptions['enabled'] ) ? $paymobOptions['enabled'] : '';
		$pubKey          = isset( $paymobOptions['pub_key'] ) ? $paymobOptions['pub_key'] : '';
		$apiKey          = isset( $paymobOptions['api_key'] ) ? $paymobOptions['api_key'] : '';
		$secKey          = isset( $paymobOptions['sec_key'] ) ? $paymobOptions['sec_key'] : '';
		$empty_cart      = isset( $post_data['woocommerce_paymob-main_empty_cart'] ) ? sanitize_text_field( $post_data['woocommerce_paymob-main_empty_cart'] ) : '';
		$debug           = isset( $post_data['woocommerce_paymob-main_debug'] ) ? sanitize_text_field( $post_data['woocommerce_paymob-main_debug'] ) : '';
		// Validate and sanitize the keys
		$conf['pubKey'] = isset( $post_data['woocommerce_paymob-main_pub_key'] ) ? sanitize_text_field( $post_data['woocommerce_paymob-main_pub_key'] ) : '';
		$conf['secKey'] = isset( $post_data['woocommerce_paymob-main_sec_key'] ) ? sanitize_text_field( $post_data['woocommerce_paymob-main_sec_key'] ) : '';
		$conf['apiKey'] = isset( $post_data['woocommerce_paymob-main_api_key'] ) ? sanitize_text_field( $post_data['woocommerce_paymob-main_api_key'] ) : '';
		// echo Paymob::getCountryCode($conf['pubKey']);exit;
		// Check if all keys are provided
		if ( empty( $conf['pubKey'] ) || empty( $conf['secKey'] ) || empty( $conf['apiKey'] ) ) {
			WC_Admin_Settings::add_error( __( 'Please ensure you are entering API, public, and secret keys.', 'paymob-woocommerce' ) );
			return false;
		}
		try {
			if ($conf['pubKey'] !== $pubKey || $conf['apiKey'] !== $apiKey || $conf['secKey'] !== $secKey) {
				// Handle the logic to get and validate the data from Paymob
				$addlog    = WC_LOG_DIR . 'paymob.log';
				$paymobReq = new Paymob( $debug, $addlog );
				$result    = $paymobReq->authToken( $conf );

				Paymob::addLogs( $debug, $addlog, __( 'Merchant configuration: ', 'paymob-woocommerce' ), $result );
				
				$this->unset_old_settings();
				$gatewayData = $paymobReq->getPaymobGateways( $conf['secKey'], PAYMOB_PLUGIN_PATH . 'assets/img/' );
				update_option( 'woocommerce_paymob_gateway_data', $gatewayData );
				update_option( 'woocommerce_paymob_country', Paymob::getCountryCode( $conf['pubKey'] ) );
				
				PaymobAutoGenerate::create_gateways( $result, 1, $gatewayData );

				// Handle the logic for integration ID and HMAC update
				$ids                   = array();
				$integration_id_hidden = array();
				foreach ( $result['integrationIDs'] as $value ) {
					$text                    = $value['id'] . ' : ' . $value['name'] . ' (' . $value['type'] . ' : ' . $value['currency'] . ' )';
					$integration_id_hidden[] = $text . ',';
					$ids[]                   = trim( $value['id'] );
				}
				if ( 'yes' === $default_enabled ) {
					PaymobAutoGenerate::register_framework( $ids, $debug ? 'yes' : 'no' );
				}
				if ( ! empty( $ids ) ) {
					$integration_id_hidden   = implode( "\n", $integration_id_hidden );
					$paymob_default_settings = array(
						'enabled'               => 'no',
						'sec_key'               => $conf['secKey'],
						'pub_key'               => $conf['pubKey'],
						'api_key'               => $conf['apiKey'],
						'title'                 => 'Pay with Paymob',
						'description'           => 'Pay with Paymob',
						'integration_id'        => $ids,
						'integration_id_hidden' => $integration_id_hidden,
						'hmac_hidden'           => $result['hmac'],
						'empty_cart'            => $empty_cart ? 'yes' : 'no',
						'debug'                 => $debug ? 'yes' : 'no',
						'logo'                  => plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png',
					);
					update_option( 'woocommerce_paymob_settings', $paymob_default_settings );
				} else {
					$wpdb->delete( $wpdb->prefix . 'paymob_gateways', array( 'gateway_id' => 'paymob' ) );
				}
			}
			// Save the rest of the settings using the parent method
			parent::process_admin_options();
			// Redirect after saving settings
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paymob_list_gateways' ) );
			exit;
		} catch ( \Exception $e ) {
			WC_Admin_Settings::add_error( __( $e->getMessage(), 'paymob-woocommerce' ) );
		}
		return true;
	}
	public function unset_old_settings() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'paymob_gateways';
		$gateways   = PaymobAutoGenerate::get_db_gateways_data();
		// Track if any gateway deletion fails
		$all_deleted = true;

		foreach ( $gateways as $gateway ) {
			$gateway_file_path       = PAYMOB_PLUGIN_PATH . 'includes/gateways/' . $gateway->file_name;
			$gateway_block_file_path = PAYMOB_PLUGIN_PATH . 'includes/blocks/' . $gateway->gateway_id . '-block.php';
			$gateway_js_file_path    = PAYMOB_PLUGIN_PATH . 'assets/js/blocks/' . $gateway->gateway_id . '_block.js';

			// Unlink the files if they exist and are not the main paymob gateway
			if ( $gateway->gateway_id != 'paymob' ) {
				if ( file_exists( $gateway_file_path ) ) {
					if ( ! unlink( $gateway_file_path ) ) {
						$all_deleted = false;
					}
				}

				if ( file_exists( $gateway_block_file_path ) ) {
					if ( ! unlink( $gateway_block_file_path ) ) {
						$all_deleted = false;
					}
				}

				if ( file_exists( $gateway_js_file_path ) ) {
					if ( ! unlink( $gateway_js_file_path ) ) {
						$all_deleted = false;
					}
				}

				// Delete the gateway record from the database
				$wpdb->delete( $table_name, array( 'gateway_id' => $gateway->gateway_id ) );
				// Delete the gateway settings from WooCommerce options
				if ( ! delete_option( 'woocommerce_' . $gateway->gateway_id . '_settings' ) ) {
					$all_deleted = false;
				}
			}
		}
		// Return true if all deletions were successful, otherwise false
		return $all_deleted;
	}
}
function enqueue_paymob_accordion_scripts( $hook ) {
	if ( ( Paymob::filterVar( 'section' ) ) && Paymob::filterVar( 'section' ) == 'paymob-main' ) {
		Paymob_Scripts::paymob_accordion();

	}
}
add_action( 'admin_enqueue_scripts', 'enqueue_paymob_accordion_scripts' );
function check_paymob_main_gateway_enabled( $old_value, $new_value ) {
	// Check if the 'enabled' option exists in both old and new values.
	if ( isset( $new_value['enabled'] ) && $new_value['enabled'] === 'yes' ) {
		// If the old value was either 'no' or not set, this means the gateway was just enabled.
		if ( ! isset( $old_value['enabled'] ) || $old_value['enabled'] === 'no' ) {
			$paymob_options = get_option( 'woocommerce_paymob-main_settings' );
			$debug          = isset( $paymob_options['debug'] ) ? sanitize_text_field( $paymob_options['debug'] ) : '0';
			try {
				$conf['pubKey'] = isset( $paymob_options['pub_key'] ) ? sanitize_text_field( $paymob_options['pub_key'] ) : '';
				$conf['secKey'] = isset( $paymob_options['sec_key'] ) ? sanitize_text_field( $paymob_options['sec_key'] ) : '';

				$conf['apiKey'] = isset( $paymob_options['api_key'] ) ? sanitize_text_field( $paymob_options['api_key'] ) : '';

				$addlog    = WC_LOG_DIR . 'paymob.log';
				$paymobReq = new Paymob( $debug, $addlog );
				$result    = $paymobReq->authToken( $conf );
				$ids       = array();
				foreach ( $result['integrationIDs'] as $value ) {
					$ids[] = trim( $value['id'] );
				}
				PaymobAutoGenerate::register_framework( $ids, $debug ? 'yes' : 'no' );
			} catch ( \Exception $e ) {
				WC_Admin_Settings::add_error( __( $e->getMessage(), 'paymob-woocommerce' ) );
			}
		}
	}
}
add_action( 'update_option_woocommerce_paymob-main_settings', 'check_paymob_main_gateway_enabled', 10, 2 );
