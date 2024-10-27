<?php
/**
 * Paymob WooCommerce Class
 */
class Paymob_WooCommerce {


	/**
	 * Constructor
	 */
	public $gateway;
	public $id;
	public $hmac_hidden;

	public function __construct( $id ) {
		$this->id      = $id;
		$this->gateway = ucwords( str_replace( '-', '_', $id ), '_' ) . '_Gateway';
		// filters
		add_filter( 'plugin_action_links_' . PAYMOB_PLUGIN, array( $this, 'add_plugin_links' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register' ), 0 );
		add_action( 'woocommerce_api_paymob_callback', array( $this, 'callback' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_enqueue_scripts' ) );
		add_action( 'admin_head', array( $this, 'hide_block_main_gateway' ) );
		$paymob_u_Options  = get_option( 'woocommerce_paymob_settings' );
		$this->hmac_hidden = isset( $paymob_u_Options['hmac_hidden'] ) ? sanitize_text_field( $paymob_u_Options['hmac_hidden'] ) : '';
	}
	/**
	 * Register the gateway to WooCommerce
	 */
	public function register( $gateways ) {
		include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/class-paymob-payment.php';
		include_once PAYMOB_PLUGIN_PATH . '/includes/gateways/class-gateway-' . sanitize_file_name( $this->id ) . '.php';
		if ( ! isset( $gateways[ $this->id ] ) ) {
			$gateways[] = $this->gateway;
		}
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
		$paymobSetting = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paymob-main' ) . '">' . __( 'PayMob Settings', 'paymob-woocommerce' ) . '</a>';
		$plugin_links  = array( __( 'Paymob Settings', 'paymob-woocommerce' ) => $paymobSetting );
		return ( array_merge( $links, $plugin_links ) );
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
		// var_dump($json_data);die();

		$country = Paymob::getCountryCode( $this->gateway->sec_key );
		$url     = Paymob::getApiUrl( $country );
		if ( isset( $json_data['type'] ) && Paymob::filterVar( 'hmac', 'REQUEST' ) && 'TRANSACTION' === $json_data['type'] ) {
			$this->acceptWebhook( $json_data, $url );
		} elseif ( isset( $json_data['type'] ) && 'TOKEN' === $json_data['type'] ) {
			$this->saveCardToken( $json_data );
		} else {
			$this->flashWebhook( $json_data, $url, $country );
		}
	}

	public function acceptWebhook( $json_data, $url ) {

		$obj     = $json_data['obj'];
		$type    = $json_data['type'];
		$orderId = substr( $obj['order']['merchant_order_id'], 0, -11 );
		if ( Paymob::verifyHmac( $this->hmac_hidden, $json_data, null, Paymob::filterVar( 'hmac', 'REQUEST' ) ) ) {

			$order           = wc_get_order( $orderId );
			$PaymobPaymentId = $order->get_meta( 'PaymobPaymentId', true );
			$addlog          = WC_LOG_DIR . $PaymobPaymentId . '.log';
			Paymob::addLogs( $this->gateway->debug, $addlog, ' In Webhook action, for order# ' . $orderId, wp_json_encode( $json_data ) );
			$order  = PaymobOrder::validateOrderInfo( $orderId, $PaymobPaymentId );
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
				Paymob::addLogs( $this->gateway->debug, $addlog, $msg );
				$note .= "<br/>Payment Method ID: { $integrationId } <br/>Transaction done by: { $type } / { $subType }</br> Transaction ID:  <b style='color:DodgerBlue;'>{ $transaction }</b></br> Order ID: <b style='color:DodgerBlue;'>{ $paymobId }</b> </br> <a href=' {$url} portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";
				$order->add_order_note( $note );
				$order->payment_complete( $orderId );
				$paymentMethod      = $order->get_payment_method();
				$paymentMethodTitle = 'Paymob - ' . ucwords( $type );
				$order->set_payment_method_title( $paymentMethodTitle );
			} else {
				$order->update_status( 'failed' );
				$note = __( 'Paymob Webhook: Payment is not completed ', 'paymob-woocommerce' );
				$msg  = $msg . ' ' . $note;
				Paymob::addLogs( $this->gateway->debug, $addlog, $msg );
				$note .= "<br/>Payment Method ID: { $integrationId } <br/>Transaction done by: { $type } / { $subType }</br> Transaction ID:  <b style='color:DodgerBlue;'>{ $transaction }</b></br> Order ID: <b style='color:DodgerBlue;'>{ $paymobId }</b> </br> <a href=' {$url} portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";
				$order->add_order_note( $note );
			}
			$order->update_meta_data( 'PaymobTransactionId', $transaction );
			$order->save();
			die( esc_html( "Order updated: $orderId" ) );
		} else {
			die( esc_html( "can not verify order: $orderId" ) );
		}
	}

	public function flashWebhook( $json_data, $url, $country ) {
		$orderId          = Paymob::getIntentionId( $json_data['intention']['extras']['creation_extras']['merchant_intention_id'] );
		$order            = wc_get_order( $orderId );
		$OrderIntensionId = $order->get_meta( 'PaymobIntentionId', true );
		$OrderAmount      = $order->get_meta( 'PaymobCentsAmount', true );
		$PaymobPaymentId  = $order->get_meta( 'PaymobPaymentId', true );
		$addlog           = WC_LOG_DIR . $PaymobPaymentId . '.log';

		Paymob::addLogs( $this->gateway->debug, $addlog, ' In Webhook action, for order# ' . $orderId, wp_json_encode( $json_data ) );

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
				$this->hmac_hidden,
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

		$order  = PaymobOrder::validateOrderInfo( $orderId, $PaymobPaymentId );
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
				Paymob::addLogs( $this->gateway->debug, $addlog, $msg );
				$transaction = $json_data['transaction']['id'];
				$paymobId    = $json_data['transaction']['order']['id'];
				$note       .= "<br/>Payment Method IDs: { $integrationId } <br/>Transaction done by: { $type } / { $subType }</br> Transaction ID:  <b style='color:DodgerBlue;'>{ $transaction }</b></br> Order ID: <b style='color:DodgerBlue;'>{ $paymobId }</b> </br> <a href=' {$url} portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";
				$order->add_order_note( $note );
				$order->payment_complete( $orderId );
				$paymentMethod = $order->get_payment_method();

				$paymentMethodTitle = 'Paymob - ' . ucwords( $type );
				$order->set_payment_method_title( $paymentMethodTitle );

			} elseif (
				false === $trans['success'] &&
				true === $trans['is_refunded'] &&
				false === $trans['is_voided'] &&
				false === $trans['is_capture']
			) {
				$order->update_status( 'refunded' );
				$note = __( 'Paymob  Webhook: Payment Refunded', 'paymob-woocommerce' );
				$msg  = $msg . ' ' . $note;
				Paymob::addLogs( $this->gateway->debug, $addlog, $msg );
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
				Paymob::addLogs( $this->gateway->debug, $addlog, $msg );
				$transaction = $json_data['transaction']['id'];
				$paymobId    = $json_data['transaction']['order']['id'];
				$note       .= "<br/>Payment Method ID: { $integrationId } <br/>Transaction done by: { $type } / { $subType }</br> Transaction ID:  <b style='color:DodgerBlue;'>{ $transaction }</b></br> Order ID: <b style='color:DodgerBlue;'>{ $paymobId }</b> </br> <a href=' {$url} portal2/en/transactions' target='_blank'>Visit Paymob Dashboard</a>";
				$order->add_order_note( $note );
			}
			$order->update_meta_data( 'PaymobTransactionId', $transaction );
			$order->save();
			die( esc_html( "Order updated: $orderId" ) );
		}
	}
	public function saveCardToken( $json_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'paymob_cards_token';
		$obj        = $json_data['obj'];
		$addlog     = WC_LOG_DIR . 'paymob.log';
		Paymob::addLogs( $this->gateway->debug, $addlog, ' In save Card Token Webhook , for User -- ' . $obj['email'], wp_json_encode( $json_data ) );
		$user = get_user_by( 'email', $obj['email'] );
		if ( $user ) {
			$token = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}paymob_cards_token WHERE user_id = %d AND card_subtype = %s AND masked_pan = %s",
					$user->ID,
					$obj['card_subtype'],
					$obj['masked_pan']
				),
				OBJECT
			);
			if ( ! $token ) {
				$wpdb->insert(
					$table_name,
					array(
						'user_id'      => $user->ID,
						'token'        => $obj['token'],
						'masked_pan'   => $obj['masked_pan'],
						'card_subtype' => $obj['card_subtype'],
					)
				);
			} else {
				$wpdb->update(
					$table_name,
					array(
						'token' => $obj['token'],
					),
					array(
						'user_id'      => $user->ID,
						'card_subtype' => $obj['card_subtype'],
						'masked_pan'   => $obj['masked_pan'],
					)
				);
			}
			die( esc_html( "Token Saved: user id: $user->ID, user email: " . $obj['email'] ) );
		} else {
			die( esc_html( 'No User Found with this email: ' . $obj['email'] ) );
		}
	}

	public function callReturnAction() {
		$orderId         = Paymob::getIntentionId( Paymob::filterVar( 'merchant_order_id' ) );
		$order           = wc_get_order( $orderId );
		$PaymobPaymentId = $order->get_meta( 'PaymobPaymentId', true );
		$addlog          = WC_LOG_DIR . $PaymobPaymentId . '.log';

		if ( ! Paymob::verifyHmac( $this->hmac_hidden, Paymob::sanitizeVar() ) ) {
			wc_add_notice( __( 'Sorry, you are accessing wrong data', 'paymob-woocommerce' ), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit();
		}

		Paymob::addLogs( $this->gateway->debug, $addlog, ' In Callback action, for order# ' . $orderId, wp_json_encode( Paymob::sanitizeVar() ) );

		$order         = PaymobOrder::validateOrderInfo( $orderId, $PaymobPaymentId );
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
			Paymob::addLogs( $this->gateway->debug, $addlog, $msg );
			$order->add_order_note( $note . $info );
			$order->payment_complete( $orderId );
			$paymentMethod      = $order->get_payment_method();
			$paymentMethodTitle = 'Paymob - ' . ucwords( $type );
			$order->set_payment_method_title( $paymentMethodTitle );
			$redirect_url = $order->get_checkout_order_received_url();
		} else {
			$redirect_url = wc_get_checkout_url();
			if ( 'yes' == $this->gateway->empty_cart ) {
				$redirect_url = $order->get_checkout_payment_url();
			}
			$gatewayError = Paymob::filterVar( 'data_message' );
			$error        = __( 'Payment is not completed due to ', 'paymob-woocommerce' ) . $gatewayError;
			$msg          = __( 'In callback action, for order #', 'paymob-woocommerce' ) . ' ' . $orderId . ' ' . $error;
			Paymob::addLogs( $this->gateway->debug, $addlog, $msg );
			$order->update_status( 'failed' );
			$order->add_order_note( 'Paymob :' . $error . $info );
			wc_add_notice( $error, 'error' );
		}
		$order->update_meta_data( 'PaymobTransactionId', $id );
		$order->save();
		wp_safe_redirect( $redirect_url );
		exit();
	}
	public function add_enqueue_scripts() {

		Paymob_Style::paymob_enqueue();
	}

	public function hide_block_main_gateway() {

		Paymob_Style::hide_main_gateway_enqueue();
	}
}
