<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Paymob_Gateway extends WC_Payment_Gateway {

	public $has_fields;
	public $id;
	public $method_title;
	public $method_description;
	public $supports;
	public $title;
	public $description;
	public $sec_key;
	public $pub_key;
	public $api_key;
	public $integration_id;
	public $integration_id_hidden;
	public $hmac_hidden;
	public $debug;
	public $empty_cart;
	public $logo;
	public $addlog;
	public $cents;
	public $notify_url;

	public function __construct() {
		// config
		$this->id                 = 'paymob';
		$this->has_fields         = true;
		$this->method_title       = __( 'Pay With Paymob', 'paymob-woocommerce' );
		$this->method_description = __( 'Paymob payment.', 'paymob-woocommerce' );
		$this->supports           = array( 'products' );

		$this->init_settings();
		$this->init_form_fields();

		// fields
		$this->title                 = $this->get_option( 'title' );
		$this->description           = $this->get_option( 'description' );
		$this->sec_key               = $this->get_option( 'sec_key' );
		$this->pub_key               = $this->get_option( 'pub_key' );
		$this->api_key               = $this->get_option( 'api_key' );
		$this->integration_id        = $this->get_option( 'integration_id' );
		$this->integration_id_hidden = $this->get_option( 'integration_id_hidden' );
		$this->hmac_hidden           = $this->get_option( 'hmac_hidden' );
		$this->debug                 = ( $this->get_option( 'debug' ) == 'yes' ) ? '1' : '0';
		$this->empty_cart            = $this->get_option( 'empty_cart' );
		$this->logo                  = $this->get_option( 'logo' );
		$this->addlog                = WC_LOG_DIR . $this->id . '.log';
		$this->cents                 = 100;

		// callback
		$this->notify_url = WC()->api_request_url( 'wc-paymob-card' );
		add_action( 'admin_enqueue_scripts', array( $this, 'paymob_admin_enqueue' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function paymob_admin_enqueue() {
		$params = array(
			'gateway'            => $this->id,
			'integration_id'     => $this->integration_id,
			'integration_hidden' => $this->integration_id_hidden,
			'hmac_hidden'        => $this->hmac_hidden,
			'callback_url'       => $this->notify_url,
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
		);
		wp_enqueue_script( 'paymob-admin-js', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/admin.js', array( 'jquery' ), PAYMOB_VERSION, true );
		wp_enqueue_script( 'color-picker', admin_url() . 'js/color-picker.min.js', array(), '1.0', true );

		wp_localize_script( 'paymob-admin-js', 'ajax_object', $params );
		wp_enqueue_style( 'paymob-admin-css', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/css/admin.css' );
	}

	public function admin_options() {
		parent::admin_options();
	}

	public function init_form_fields() {
		$this->form_fields = include PAYMOB_PLUGIN_PATH . 'includes/admin/settings.php';
	}

	/**
	 * Return the gateway's title.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_gateway_title', $this->title, $this->id );
	}

	/**
	 * Return the gateway's icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon = '<img id="paymob-logo" src="' . $this->logo . '"/>';
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	public function process_payment( $orderId ) {
		$paymobOrder = new PaymobOrder( $orderId, $this );
		$status      = $paymobOrder->createPayment();
		if ( ! $status['success'] ) {
			$errorMsg = $status['message'];
			if ( 'Unsupported currency' == $errorMsg ) {
				$integration_id_hidden = explode( ',', $this->integration_id_hidden );
				$currencies            = array(); // Initialize array to store matching values
				// Loop through each entry in the second array
				foreach ( $integration_id_hidden as $entry ) {
					// Split the entry by ':'
					$parts = explode( ':', $entry );
					$id    = trim( $parts[0] );
					if ( isset( $parts[2] ) ) {
						if ( in_array( $id, $this->integration_id ) ) {
							$currencies[] = trim( substr( $parts[2], strpos( $parts[2], '(' ) + 1, -2 ) );
						}
					}
				}
				$errorMsg = __( 'Given currency is not supported. ', 'paymob-woocommerce' );
				if ( ! empty( $currencies ) ) {
					$errorMsg .= __( 'Currency supported : ', 'paymob-woocommerce' ) . implode( ',', array_unique( $currencies ) );
				}
			}
			return $paymobOrder->throwErrors( $errorMsg );
		}

		$paymobReq   = new Paymob( $this );
		$countryCode = $paymobReq->getCountryCode( $this->pub_key );
		$apiUrl      = $paymobReq->getApiUrl( $countryCode );
		$cs          = $status['cs'];

		$to    = $apiUrl . "unifiedcheckout/?publicKey=$this->pub_key&clientSecret=$cs";
		$order = wc_get_order( $orderId );
		$order->update_meta_data( 'PaymobIntentionId', $status['intentionId'] );
		$order->update_meta_data( 'PaymobCentsAmount', $status['centsAmount'] );
		$order->save();

		$paymobOrder->processOrder();

		return array(
			'result'   => 'success',
			'redirect' => $to,
		);
	}

	public function payment_fields() {
		include_once PAYMOB_PLUGIN_PATH . 'templates/flash.php';
	}

	public function get_parent_payment_fields() {
		parent::payment_fields();
	}

	/**
	 * Don't enable Paymob payment method, if there is no public and secret keys
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function validate_enabled_field( $key, $value ) {
		if ( is_null( $value ) ) {
			return 'no';
		}
		$pubKey         = $this->get_field_value( 'pub_key', $this->form_fields['pub_key'] );
		$secKey         = $this->get_field_value( 'sec_key', $this->form_fields['sec_key'] );
		$apiKey         = $this->get_field_value( 'api_key', $this->form_fields['api_key'] );
		$integrationIds = $this->get_field_value( 'integration_id', $this->form_fields['integration_id'] );
		if ( empty( $pubKey ) || empty( $secKey ) || empty( $apiKey ) || empty( $integrationIds ) ) {
			WC_Admin_Settings::add_error( __( 'Please ensure you are entering API, public and secret keys. Also, ensure to select at least one of the integration IDs..', 'paymob-woocommerce' ) );
			return 'no';
		}
		return 'yes';
	}

	/**
	 * Return whether or not Paymob payment method requires setup.
	 *
	 * @return bool
	 */
	public function needs_setup() {
		if ( empty( $this->pub_key ) || empty( $this->sec_key ) || empty( $this->api_key ) || empty( $this->integration_id ) ) {
			return true;
		}

		return false;
	}
}
