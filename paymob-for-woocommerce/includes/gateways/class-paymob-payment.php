<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Paymob_Payment extends WC_Payment_Gateway {


	public $has_fields;
	public $id;
	public $method_title;
	public $method_description;
	public $supports;
	public $title;
	public $description;
	public $config_note;
	public $callback;
	public $sec_key;
	public $pub_key;
	public $api_key;
	public $integration_id;
	public $integration_id_hidden;
	public $single_integration_id;
	public $hmac;
	public $hmac_hidden;
	public $debug;
	public $empty_cart;
	public $logo;
	public $addlog;
	public $cents;
	public $notify_url;
	public $amount_cents;
	public $has_items;

	public function __construct() {
		// config
		$this->has_fields = true;
		$this->supports   = array( 'products', 'refunds' );

		$this->init_settings();
		$this->init_form_fields();
		// fields
		foreach ( $this->settings as $key => $val ) {
			$this->$key = $val;
		}

		$paymobOptions = get_option( 'woocommerce_paymob-main_settings' );
		if ( $paymobOptions ) {
			$this->sec_key    = isset( $paymobOptions['sec_key'] ) ? $paymobOptions['sec_key'] : '';
			$this->pub_key    = isset( $paymobOptions['pub_key'] ) ? $paymobOptions['pub_key'] : '';
			$this->api_key    = isset( $paymobOptions['api_key'] ) ? $paymobOptions['api_key'] : '';
			$this->empty_cart = isset( $paymobOptions['empty_cart'] ) ? $paymobOptions['empty_cart'] : '';
			$this->has_items  = isset( $paymobOptions['has_items'] ) ? $paymobOptions['has_items'] : '';
			$this->debug      = ( isset( $paymobOptions['debug'] ) && $paymobOptions['debug'] == 'yes' ) ? '1' : '0';
		}
		$this->description           = $this->get_option( 'description' );
		$this->logo                  = $this->get_option( 'logo' );
		$this->single_integration_id = $this->get_option( 'single_integration_id' );
		$this->addlog                = WC_LOG_DIR . $this->id . '.log';
		$this->cents                 = 100;
		// callback
		$this->notify_url = WC()->api_request_url( 'wc-paymob-card' );
		add_action( 'admin_enqueue_scripts', array( $this, 'paymob_admin_enqueue' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'paymob_frontend_enqueue' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function paymob_frontend_enqueue() {
		if ( is_checkout() ) {
			Paymob_Scripts::paymob_frontend();
		}
	}

	public function paymob_admin_enqueue() {
		$params = array(
			'gateway'      => $this->id,
			'callback_url' => $this->notify_url,
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
		);

		$gateway_ids = array();
		$gateways    = PaymobAutoGenerate::get_db_gateways_data();
		foreach ( $gateways as $gateway ) {
			$gateway_ids[] = $gateway->gateway_id;
		}
		if ( ( Paymob::filterVar( 'section' ) ) && ( in_array( Paymob::filterVar( 'section' ), $gateway_ids ) || Paymob::filterVar( 'section' ) == 'paymob-main' || Paymob::filterVar( 'section' ) == 'paymob_add_gateway' || Paymob::filterVar( 'section' ) == 'paymob_list_gateways' ) ) {
			Paymob_Scripts::paymob_admin( $params );
			Paymob_Style::paymob_admin();
		}
	}

	public function admin_options() {
		parent::admin_options();
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
			if ( 'Unsupported currency' == $errorMsg) {
				$paymobOptions   = get_option( 'woocommerce_paymob_settings' );
				$integration_ids = explode( ',', $paymobOptions['integration_id_hidden'] );
				$currencies      = array(); // Initialize array to store matching values
				// Loop through each entry in the second array
				foreach ( $integration_ids as $entry ) {
					// Split the entry by ':'
					$parts = explode( ':', $entry );
					$id    = trim( $parts[0] );
					if ( isset( $parts[2] ) ) {
						if ( in_array( $id, $paymobOptions['integration_id'] ) ) {
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
		$order->update_meta_data( 'PaymobPaymentId', $this->id );
		$order->save();

		$paymobOrder->processOrder();

		return array(
			'result'   => 'success',
			'redirect' => $to,
		);
	}
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		$country = Paymob::getCountryCode( $this->sec_key );
		$cents   = 100;
		$round   = 2;
		if ( 'omn' === $country ) {
			$round = 3;
			$cents = 1000;
		}
		$order = wc_get_order( $order_id );
		// Check if the order exists
		if ( ! $order ) {
			return new WP_Error( 'invalid_order', __( 'Order not found.', 'woocommerce' ) );
		}

		$transactionId   = $order->get_meta( 'PaymobTransactionId', true );
		$PaymobPaymentId = $order->get_meta( 'PaymobPaymentId', true );
		$addlog          = WC_LOG_DIR . $PaymobPaymentId . '.log';
		$data            = array(
			'transaction_id' => $transactionId,
			'amount_cents'   => round( $amount, $round ) * $cents,
		);
		$paymobReq       = new Paymob( $this->debug, $addlog );
		$status          = $paymobReq->refundPayment( $this->sec_key, $data );

		if ( ! $status['success'] ) {
			return new WP_Error( 'error', __( 'Refund failed: ', 'paymob-woocommerce' ) . $status['message'] );
		} else {
			$paymob_refund_id = $status['refund_id'];
			$msg              = $this->can_refund_orders( $order, $amount, $paymob_refund_id );
			Paymob::addLogs( $this->debug, $addlog, 'For Order # ' . $order_id . ' ' . $msg );
		}
		// If the refund is successful, return true.
		return true;
	}

	public function can_refund_orders( $order, $amount, $paymob_refund_id ) {
		$refunds = $order->get_refunds(); // Get all refunds associated with the order
		if ( ! empty( $refunds ) ) {
			usort(
				$refunds,
				function ( $a, $b ) {
					return strtotime( $b->get_date_created() ) - strtotime( $a->get_date_created() );
				}
			);
			$recent_refund    = reset( $refunds );
			$recent_refund_id = $recent_refund->get_id();
		}

			$order_total = $order->get_total();
		if ( $amount < $order_total ) {
			// Partial refund
			$msg = __( 'Paymob : Partial refund of ', 'paymob-woocommerce' ) . $amount;

		} elseif ( $amount == $order_total ) {
			// Full refund
			$msg = __( 'Paymob : Full refund of ', 'paymob-woocommerce' ) . $amount;
		}
			$info = "<br/>Woo Order Refund ID: {$recent_refund_id}<br/>Transaction Refund ID : {$paymob_refund_id}";
			$order->add_order_note( $msg . $info );
			return $msg;
	}
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( esc_html( $this->description ) ) );
		}
	}

	public function init_form_fields() {
		$this->form_fields = include PAYMOB_PLUGIN_PATH . 'includes/admin/paymob-single-gateway.php';
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
		$paymobOptions = get_option( 'woocommerce_paymob-main_settings' );
		$pubKey        = $paymobOptions['pub_key'];
		$secKey        = $paymobOptions['sec_key'];
		$apiKey        = $paymobOptions['api_key'];
		if ( empty( $pubKey ) || empty( $secKey ) || empty( $apiKey ) ) {
			WC_Admin_Settings::add_error( __( 'Please ensure you are entering API, public and secret keys in the main Paymob configuration.', 'paymob-woocommerce' ) );
			return 'no';
		}

		$integrationId = $this->get_field_value( 'single_integration_id', $this->form_fields['single_integration_id'] );
		if ( empty( $integrationId ) ) {
			WC_Admin_Settings::add_error( __( 'Please, ensure adding (' . $this->method_title . ') integration ID.', 'paymob-woocommerce' ) );
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
		$paymobOptions = get_option( 'woocommerce_paymob-main_settings' );
		$pubKey        = $paymobOptions['pub_key'];
		$secKey        = $paymobOptions['sec_key'];
		$apiKey        = $paymobOptions['api_key'];
		if ( empty( $pubKey ) || empty( $secKey ) || empty( $apiKey ) ) {
			return true;
		}

		return false;
	}
}
