<?php

/**
 * Paymob WooCommerce Class
 */
class Paymob_WooCommerce {

	/**
	 * Constructor
	 */
	public $gateway;

	public function __construct() {
		// filters
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register' ), 0 );
		add_filter( 'plugin_action_links_' . PAYMOB_PLUGIN, array( $this, 'add_plugin_links' ) );
		add_action( 'woocommerce_api_paymob_callback', array( $this, 'callback' ) );
		add_action( 'wp_ajax_get_paymob_info', array( $this, 'get_paymob_info' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_enqueue_scripts' ) );
	}

	/**
	 * Register the gateway to WooCommerce
	 */
	public function register( $gateways ) {
		include_once 'includes/payment/paymob-gateway.php';
		$gateways[] = 'Paymob_Gateway';

		return $gateways;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 *
	 * @return array
	 */
	public function add_plugin_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paymob' ) . '">' . __( 'PayMob Settings', 'paymob-woocommerce' ) . '</a>',
		);
		return array_merge( $links, $plugin_links );
	}

	public function callback() {
		$this->gateway = new Paymob_Gateway();
		if ( Paymob::filterVar( 'REQUEST_METHOD', 'SERVER' ) === 'POST' ) {
			$this->callWebhookAction();
		} elseif ( Paymob::filterVar( 'REQUEST_METHOD', 'SERVER' ) === 'GET' ) {
			$this->callReturnAction();
		}
	}

	public function callWebhookAction() {
		$post_data = file_get_contents( 'php://input' );
		$json_data = json_decode( $post_data, true );
		$country   = Paymob::getCountryCode( $this->gateway->sec_key );
		$url       = Paymob::getApiUrl( $country );
		if ( isset( $json_data['type'] ) && Paymob::filterVar( 'hmac', 'REQUEST' ) && 'TRANSACTION' === $json_data['type'] ) {
			$this->acceptWebhook( $json_data, $url );
		} else {
			$this->flashWebhook( $json_data, $url, $country );
		}
	}

	public function acceptWebhook( $json_data, $url ) {
		$obj     = $json_data['obj'];
		$type    = $json_data['type'];
		$orderId = substr( $obj['order']['merchant_order_id'], 0, -11 );
		if ( Paymob::verifyHmac( $this->gateway->hmac_hidden, $json_data, null, Paymob::filterVar( 'hmac', 'REQUEST' ) ) ) {
			$order  = PaymobOrder::validateOrderInfo( $orderId );
			$status = $order->get_status();

			if ( 'pending' != $status && 'failed' != $status && 'on-hold' != $status ) {
				die( esc_html( "can not change status of order: $orderId" ) );
			}

			$integrationId = $obj['integration_id'];
			$type          = $obj['source_data']['type'];
			$subType       = $obj['source_data']['sub_type'];
			$transaction   = $obj['id'];
			$paymobId      = $obj['order']['id'];

			$msg = __( 'Paymob  Webhook for Order #', 'paymob-woocommerce' ) . $orderId;
			if (
					true === $obj['success'] &&
					false === $obj['is_voided'] &&
					false === $obj['is_refunded'] &&
					false === $obj['pending'] &&
					false === $obj['is_void'] &&
					false === $obj['is_refund'] &&
					false === $obj['error_occured']
			) {
				$note = __( 'Paymob  Webhook: Transaction Approved', 'paymob-woocommerce' );
				$msg  = $msg . ' ' . $note;
				Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $msg );
				$note .= "<br/>Payment Method ID: { $integrationId } <br/>Transaction done by: { $type } / { $subType }</br> Transaction ID:  <b style='color:DodgerBlue;'>{ $transaction }</b></br> Order ID: <b style='color:DodgerBlue;'>{ $paymobId }</b> </br> <a href=' {$url} portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";
				$order->add_order_note( $note );
				$order->payment_complete( $orderId );
			} else {
				$order->update_status( 'failed' );
				$note = __( 'Paymob Webhook: Payment is not completed ', 'paymob-woocommerce' );
				$msg  = $msg . ' ' . $note;
				Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $msg );
				$note .= "<br/>Payment Method ID: { $integrationId } <br/>Transaction done by: { $type } / { $subType }</br> Transaction ID:  <b style='color:DodgerBlue;'>{ $transaction }</b></br> Order ID: <b style='color:DodgerBlue;'>{ $paymobId }</b> </br> <a href=' {$url} portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";
				$order->add_order_note( $note );
			}
			$order->save();
			die( esc_html( "Order updated: $orderId" ) );
		} else {
			die( esc_html( "can not verify order: $orderId" ) );
		}
	}

	public function flashWebhook( $json_data, $url, $country ) {
		$orderId = Paymob::getIntentionId( $json_data['intention']['extras']['creation_extras']['merchant_intention_id'] );
		Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, ' In Webhook action, for order# ' . $orderId, wp_json_encode( $json_data ) );
		$order            = wc_get_order( $orderId );
		$OrderIntensionId = $order->get_meta( 'PaymobIntentionId', true );
		$OrderAmount      = $order->get_meta( 'PaymobCentsAmount', true );

		if ( $OrderIntensionId != $json_data['intention']['id'] ) {
			die( esc_html( "intention ID is not matched for order: $orderId" ) );
		}

		if ( $OrderAmount != $json_data['intention']['intention_detail']['amount'] ) {
			die( esc_html( "intension amount are not matched for order : $orderId" ) );
		}

		$cents = 100;
		if ( 'omn' == $country ) {
			$cents = 1000;
		}
		if (
				! Paymob::verifyHmac(
					$this->gateway->hmac_hidden,
					$json_data,
					array(
						'id'     => $OrderIntensionId,
						'amount' => $OrderAmount,
						'cents'  => $cents,
					)
				)
		) {
			die( esc_html( "can not verify order: $orderId" ) );
		}

		$order  = PaymobOrder::validateOrderInfo( $orderId );
		$status = $order->get_status();

		if ( 'pending' != $status && 'failed' != $status && 'on-hold' != $status ) {
			die( esc_html( "can not change status of order: $orderId" ) );
		}
		$msg = __( 'Paymob  Webhook for Order #', 'paymob-woocommerce' ) . $orderId;
		if ( ! empty( $json_data['transaction'] ) ) {
			$trans         = $json_data['transaction'];
			$integrationId = $json_data['transaction']['integration_id'];
			$type          = $json_data['transaction']['source_data']['type'];
			$subType       = $json_data['transaction']['source_data']['sub_type'];
			if (
					true === $trans['success'] &&
					false === $trans['is_voided'] &&
					false === $trans['is_refunded'] &&
					false === $trans['is_capture']
			) {
				$note = __( 'Paymob  Webhook: Transaction Approved', 'paymob-woocommerce' );
				$msg  = $msg . ' ' . $note;
				Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $msg );
				$transaction = $json_data['transaction']['id'];
				$paymobId    = $json_data['transaction']['order']['id'];
				$note       .= "<br/>Payment Method IDs: { $integrationId } <br/>Transaction done by: { $type } / { $subType }</br> Transaction ID:  <b style='color:DodgerBlue;'>{ $transaction }</b></br> Order ID: <b style='color:DodgerBlue;'>{ $paymobId }</b> </br> <a href=' {$url} portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";
				$order->add_order_note( $note );
				$order->payment_complete( $orderId );
			} elseif (
					false === $trans['success'] &&
					true === $trans['is_refunded'] &&
					false === $trans['is_voided'] &&
					false === $trans['is_capture']
			) {
				$order->update_status( 'refunded' );
				$note = __( 'Paymob  Webhook: Payment Refunded', 'paymob-woocommerce' );
				$msg  = $msg . ' ' . $note;
				Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $msg );
				$order->add_order_note( $note );
			} elseif (
					false === $trans['success'] &&
					false === $trans['is_voided'] &&
					false === $trans['is_refunded'] &&
					false === $trans['is_capture']
			) {
				$order->update_status( 'failed' );
				$note = __( 'Paymob Webhook: Payment is not completed ', 'paymob-woocommerce' );
				$msg  = $msg . ' ' . $note;
				Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $msg );
				$transaction = $json_data['transaction']['id'];
				$paymobId    = $json_data['transaction']['order']['id'];
				$note       .= "<br/>Payment Method ID: { $integrationId } <br/>Transaction done by: { $type } / { $subType }</br> Transaction ID:  <b style='color:DodgerBlue;'>{ $transaction }</b></br> Order ID: <b style='color:DodgerBlue;'>{ $paymobId }</b> </br> <a href=' {$url} portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";
				$order->add_order_note( $note );
			}
			$order->save();
			die( esc_html( "Order updated: $orderId" ) );
		}
	}

	public function callReturnAction() {
		if ( ! Paymob::verifyHmac( $this->gateway->hmac_hidden, Paymob::sanitizeVar() ) ) {
			wc_add_notice( __( 'Sorry, you are accessing wrong data', 'paymob-woocommerce' ), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit();
		}
		$orderId = Paymob::getIntentionId( Paymob::filterVar( 'merchant_order_id' ) );
		Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, ' In Callback action, for order# ' . $orderId, wp_json_encode( Paymob::sanitizeVar() ) );

		$order         = PaymobOrder::validateOrderInfo( $orderId );
		$country       = Paymob::getCountryCode( $this->gateway->sec_key );
		$url           = Paymob::getApiUrl( $country );
		$integrationId = Paymob::filterVar( 'integration_id' );
		$type          = Paymob::filterVar( 'source_data_type' );
		$subType       = Paymob::filterVar( 'source_data_sub_type' );
		$id            = Paymob::filterVar( 'id' );
		$paymobOrdr    = Paymob::filterVar( 'order' );
		$info          = "<br/>Payment Method ID: {$integrationId}<br/>Transaction done by: {$type} /  {$subType}</br>Transaction ID: <b style='color:DodgerBlue;'>{$id}</b> </br> Order ID:  <b style='color:DodgerBlue;'>{$paymobOrdr}</b></br><a href='{$url}portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";

		if (
				'true' === Paymob::filterVar( 'success' ) &&
				'false' === Paymob::filterVar( 'is_voided' ) &&
				'false' === Paymob::filterVar( 'is_refunded' )
		) {
			$status = $order->get_status();
			if ( 'pending' !== $status && 'failed' !== $status && 'on-hold' !== $status ) {
				wp_safe_redirect( $order->get_checkout_order_received_url() );
				exit();
			}
			$note = __( 'Paymob : Transaction ', 'paymob-woocommerce' ) . Paymob::filterVar( 'data_message' );
			$msg  = __( 'In callback action, for order #', 'paymob-woocommerce' ) . ' ' . $orderId . ' ' . $note;
			Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $msg );
			$order->add_order_note( $note . $info );
			$order->payment_complete( $orderId );
			$redirect_url = $order->get_checkout_order_received_url();
		} else {
			$redirect_url = wc_get_checkout_url();
			if ( 'yes' == $this->gateway->empty_cart ) {
				$redirect_url = $order->get_checkout_payment_url();
			}
			$gatewayError = Paymob::filterVar( 'data_message' );
			$error        = __( 'Payment is not completed due to ', 'paymob-woocommerce' ) . $gatewayError;
			$msg          = __( 'In callback action, for order #', 'paymob-woocommerce' ) . ' ' . $orderId . ' ' . $error;
			Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $msg );
			$order->update_status( 'failed' );
			$order->add_order_note( 'Paymob :' . $error . $info );
			wc_add_notice( $error, 'error' );
		}
		$order->save();
		wp_safe_redirect( $redirect_url );
		exit();
	}

	public function get_paymob_info() {
		$conf['apiKey'] = Paymob::filterVar( 'api_key', 'POST' );
		$conf['pubKey'] = Paymob::filterVar( 'pub_key', 'POST' );
		$conf['secKey'] = Paymob::filterVar( 'sec_key', 'POST' );
		include_once 'includes/payment/paymob-gateway.php';
		$this->gateway = new Paymob_Gateway();

		try {
			$paymobReq = new Paymob( $this->gateway->debug, $this->gateway->addlog );
			$result    = $paymobReq->authToken( $conf );
			$note      = __( 'Merchant configuration: ', 'paymob-woocommerce' );
			Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $note, $result );
			wp_send_json_success(
				wp_json_encode(
					array(
						'success' => true,
						'data'    => $result,
					)
				)
			);
		} catch ( Exception $exc ) {
			Paymob::addLogs( $this->gateway->debug, $this->gateway->addlog, $exc->getMessage() );
			wp_send_json_error(
				wp_json_encode(
					array(
						'success' => false,
						'error'   => $exc->getMessage(),
					)
				)
			);
		}
	}

	public function add_enqueue_scripts() {
		wp_enqueue_style( 'paymob-css', plugins_url( 'assets/css/paymob.css', __FILE__ ), array(), PAYMOB_VERSION );
	}
}
