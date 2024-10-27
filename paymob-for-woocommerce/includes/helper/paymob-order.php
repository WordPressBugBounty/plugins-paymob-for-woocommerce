<?php

class PaymobOrder {


	public $config;
	public $gateway;
	public $order;
	public $billing;

	public function __construct( $orderId, $config ) {
		$this->config = $config;
		$this->order  = self::getOrder( $orderId );
		$country      = Paymob::getCountryCode( $this->config->sec_key );
		$cents        = 100;
		$round        = 2;
		if ( 'omn' == $country ) {
			$cents = 1000;
		}

		$this->config->amount_cents = round( $this->order->get_total(), $round ) * $cents;

		$this->billing = array(
			'email'        => $this->order->get_billing_email(),
			'first_name'   => ( $this->order->get_billing_first_name() ) ? $this->order->get_billing_first_name() : 'NA',
			'last_name'    => ( $this->order->get_billing_last_name() ) ? $this->order->get_billing_last_name() : 'NA',
			'street'       => ( $this->order->get_billing_address_1() ) ? $this->order->get_billing_address_1() . ' - ' . $this->order->get_billing_address_2() : 'NA',
			'phone_number' => ( $this->order->get_billing_phone() ) ? $this->order->get_billing_phone() : 'NA',
			'city'         => ( $this->order->get_billing_city() ) ? $this->order->get_billing_city() : 'NA',
			'country'      => ( $this->order->get_billing_country() ) ? $this->order->get_billing_country() : 'NA',
			'state'        => ( $this->order->get_billing_state() ) ? $this->order->get_billing_state() : 'NA',
			'postal_code'  => ( $this->order->get_billing_postcode() ) ? $this->order->get_billing_postcode() : 'NA',
		);

		$this->gateway = new Paymob_Gateway();
	}

	public static function getOrder( $orderId ) {
		if ( function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $orderId );
		} else {
			$order = new WC_Order( $orderId );
		}
		if ( empty( $order ) ) {
			die( 'can not verify order' );
		}
		return $order;
	}

	public function processOrder() {
		global $woocommerce;
		$this->order->add_order_note( __( 'Paymob : Awaiting Payment', 'paymob-woocommerce' ) );
		$this->order->save();
		if ( 'yes' == $this->config->empty_cart ) {
			$woocommerce->cart->empty_cart();
		}
	}

	public function throwErrors( $error ) {
		if ( Paymob::filterVar( 'pay_for_order', 'REQUEST' ) ) {
			wc_add_notice( $error, 'error' );
		} else {
			throw new Exception( $error );
		}
	}

	public function createPayment() {
		if (sizeof($this->getUserTokens()) > 3) {
			$url = wc_get_endpoint_url('saved-cards', '', get_permalink(wc_get_page_id('myaccount')));
			$url = '<a href="' . $url . '">'.esc_html( __( 'Paymob Saved Cards', 'paymob-woocommerce' ) ).'</a>';
			$url = esc_html( __( 'Please remove your cards from', 'paymob-woocommerce' ) ).' ' . $url . ' '.esc_html( __( 'to complete your purchase', 'paymob-woocommerce' ) );
			$msg = esc_html( __( 'Ops,Max number of card tokens is 3.', 'paymob-woocommerce' ) ).'<br>'.$url;
			return [ 'message' => $msg ];
		} else {
		
			$totalAmount = (int) (string) $this->config->amount_cents;
			$itemsArr    = null;
			if ( 'yes' == $this->config->has_items ) {
				$items       = $this->getInvoiceItems();
				$itemsArr    = $items['items'];
				$totalAmount = $items['total'];
			}
			$data = array(
				'amount'            => $totalAmount,
				'currency'          => $this->order->get_currency(),
				'payment_methods'   => $this->getIntegrationIds(),
				'billing_data'      => $this->billing,
				'expires_at'        => $this->getExpiryTime(),
				'extras'            => array( 'merchant_intention_id' => $this->order->get_id() . '_' . time() ),
				'special_reference' => $this->order->get_id() . '_' . time(),
			);

			if ( ! empty( $items ) ) {
				$data['items'] = $itemsArr;
			}
			$data['card_tokens'] = $this->getUserTokens();
			$paymobReq = new Paymob( $this->config->debug, $this->config->addlog );
			return $paymobReq->createIntention( $this->config->sec_key, $data, $this->order->get_id() );
		}
	}
	public function getUserTokens() {
		$tokens = array();
		if ( is_user_logged_in() ) {
			global $wpdb;
			$current_user = wp_get_current_user();
			$user_id      = $current_user->ID;
			$results      = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}paymob_cards_token WHERE user_id = %d",
					$user_id
				),
				OBJECT
			);
			if ( $results ) {
				foreach ( $results as $value ) {
					$tokens[] = $value->token;
				}
			}
		}
		return $tokens;
	}

	private function getIntegrationIds() {
		$omannet = strpos( $this->config->id, 'omannet' );
		if ( false !== $omannet ) {
			// get migs or vpc IDs
			$omannetArr[] = (int) $this->config->single_integration_id;
			$gateways     = PaymobAutoGenerate::get_db_gateways_data();
			foreach ( $gateways as $gateway ) {
				if ( ( false !== strpos( $gateway->gateway_id, 'vpc' ) || false !== strpos( $gateway->gateway_id, 'migs' ) )
				&& false === strpos( $gateway->gateway_id, 'apple-pay' )
				&& false === strpos( $gateway->gateway_id, 'google-pay' )
				&& '0' === $gateway->is_manual ) {
					$omannetArr[] = (int) $gateway->integration_id;
				}
			}
			return $omannetArr;
		}
		if ( 'paymob' != $this->config->id ) {
			return array( (int) $this->config->single_integration_id );
		}
		$paymobOptions   = get_option( 'woocommerce_paymob_settings' );
		$integration_id_hidden = explode( ',', $paymobOptions['integration_id_hidden'] );
		$matching_ids          = array();
		$integration_ids       = array();

		foreach ( $integration_id_hidden as $entry ) {
			$parts = explode( ':', $entry );
			$id    = trim( $parts[0] );
			if ( isset( $parts[2] ) ) {
				$currency = trim( substr( $parts[2], strpos( $parts[2], '(' ) + 1, -2 ) );
				if ( in_array( $id, $paymobOptions['integration_id'] ) && $currency === $this->order->get_currency() ) {
					$matching_ids[] = $id;
				}
			}
		}
		if ( ! empty( $matching_ids ) ) {
			foreach ( $matching_ids as $id ) {
				$id = (int) $id;
				if ( $id > 0 ) {
					array_push( $integration_ids, $id );
				}
			}
		}

		if ( empty( $integration_ids ) ) {
			foreach ( $paymobOptions['integration_id'] as $id ) {
				$id = (int) $id;
				if ( $id > 0 ) {
					array_push( $integration_ids, $id );
				}
			}
		}
		return $integration_ids;
	}

	public function getInvoiceItems() {
		$country = Paymob::getCountryCode( $this->config->sec_key );
		$cents   = 100;
		$round   = 2;
		if ( 'omn' === $country ) {
			$round = 3;
			$cents = 1000;
		}
		$Items  = array();
		$amount = 0;

		// Product items
		$items = $this->order->get_items();
		foreach ( $items as $item ) {
			$itemName          = esc_html( mb_strimwidth( $item->get_name(), 0, 50, '...' ) );
			$itemSubtotalPrice = $this->order->get_line_subtotal( $item, false );

			if ( ! is_numeric( $itemSubtotalPrice ) ) {
				$errMsg = sprintf( __( 'The "%s" Item has a non-numeric unit price.', 'woocommerce' ), $itemName );
				throw new Exception( $errMsg );
			}

			$itemPrice = round( $itemSubtotalPrice / $item->get_quantity(), $round );
			$amount   += round( $itemPrice * $cents, $round ) * $item->get_quantity();
			$Items[]   = array(
				'name'     => $itemName,
				'quantity' => $item->get_quantity(),
				'amount'   => round( $itemPrice * $cents, $round ),  // Ensure it's an integer
			);
		}

		// Shipping
		$shipping = round( $this->order->get_shipping_total(), $round );
		if ( $shipping ) {
			$rateLabel = esc_html( mb_strimwidth( $this->order->get_shipping_method(), 0, 50, '...' ) );
			$amount   += round( $shipping * $cents, $round );
			$Items[]   = array(
				'name'     => $rateLabel,
				'quantity' => '1',
				'amount'   => round( $shipping * $cents, $round ),  // Ensure it's an integer
			);
		}

		// Discounts and Coupons
		$discount = round( $this->order->get_discount_total(), $round );
		if ( $discount ) {
			$amount -= round( $discount * $cents, $round );
			$Items[] = array(
				'name'     => __( 'Discount', 'woocommerce' ),
				'quantity' => '1',
				'amount'   => round( -$discount * $cents, $round ),  // Ensure it's an integer
			);
		}

		// Other Fees
		foreach ( $this->order->get_items( 'fee' ) as $item_fee ) {
			$total_fees = round( $item_fee->get_total(), $round );
			$amount    += round( $total_fees * $cents, $round );
			$Items[]    = array(
				'name'     => esc_html( mb_strimwidth( $item_fee->get_name(), 0, 50, '...' ) ),
				'quantity' => '1',
				'amount'   => round( $total_fees * $cents, $round ),  // Ensure it's an integer
			);
		}

		// Gift Cards
		foreach ( $this->order->get_items( 'pw_gift_card' ) as $line ) {
			$gifPrice   = round( $line->get_amount(), $round );
			$giftAmount = round( -$gifPrice * $cents, $round );
			$amount    -= $giftAmount;
			$Items[]    = array(
				'name'     => __( 'Gift Card', 'woocommerce' ),
				'quantity' => '1',
				'amount'   => $giftAmount,  // Ensure it's an integer
			);
		}
		// Tax
		$tax = round( $this->order->get_total() - ( $amount / $cents ), $round );
		if ( $tax ) {
			$amount += round( $tax * $cents, $round );
			$Items[] = array(
				'name'     => __( 'Remaining Cart Items Amount', 'woocommerce' ),
				'quantity' => '1',
				'amount'   => round( $tax * $cents, $round ),  // Ensure it's an integer
			);
		}
		return array(
			'items' => array_reverse( $Items ),
			'total' => $amount,
		);
	}
	public function getExpiryTime() {
		$expiryDate = '';

		if ( class_exists( 'WC_Admin_Settings' ) ) {
			$country         = Paymob::getCountryCode( $this->config->sec_key );
			$date            = new DateTime( 'now', new DateTimeZone( Paymob::getTimeZone( $country ) ) );
			$currentDateTime = $date->format( 'Y-m-d\TH:i:s\Z' );

			if ( 'egy' === $country ) {
				$currentDateTime = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '3 hours' ) );
			}

			$stock_minutes = get_option( 'woocommerce_hold_stock_minutes' ) ? get_option( 'woocommerce_hold_stock_minutes' ) : 60;

			$expiresAt  = strtotime( "$currentDateTime + $stock_minutes minutes" );
			$expiryDate = gmdate( 'Y-m-d\TH:i:s\Z', $expiresAt );
		}
		return $expiryDate;
	}

	public static function validateOrderInfo( $orderId, $PaymentId ) {
		if ( empty( $orderId ) || is_null( $orderId ) || false === $orderId || '' === $orderId ) {
			wp_die( esc_html( __( 'Ops. you are accessing wrong order.', 'paymob-woocommerce' ) ) );
		}
		$order = self::getOrder( $orderId );

		$paymentMethod = $order->get_payment_method();
		if ( $PaymentId != $paymentMethod ) {
			die( esc_html( __( 'Ops. you are accessing wrong order.', 'paymob-woocommerce' ) ) );
		}
		return $order;
	}
}
