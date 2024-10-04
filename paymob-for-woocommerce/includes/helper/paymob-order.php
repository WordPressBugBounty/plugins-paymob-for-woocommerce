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
		$price = (int) (string) $this->config->amount_cents;
		$items = $this->getInvoiceItems( $price );
		$data  = array(
			'amount'            => $price,
			'currency'          => $this->order->get_currency(),
			'payment_methods'   => $this->getIntegrationIds(),
			'billing_data'      => $this->billing,
			'items'             => $items,
			'expires_at'        => $this->getExpiryTime(),
			'extras'            => array( 'merchant_intention_id' => $this->order->get_id() . '_' . time() ),
			'special_reference' => $this->order->get_id() . '_' . time(),
		);

		$paymobReq = new Paymob( $this->config->debug, $this->config->addlog );
		return $paymobReq->createIntention( $this->config->sec_key, $data, $this->order->get_id() );
	}

	private function getIntegrationIds() {
		$integration_id_hidden = explode( ',', $this->config->integration_id_hidden );

		$matching_ids    = array();
		$integration_ids = array();

		foreach ( $integration_id_hidden as $entry ) {
			$parts = explode( ':', $entry );
			$id    = trim( $parts[0] );
			if ( isset( $parts[2] ) ) {
				$currency = trim( substr( $parts[2], strpos( $parts[2], '(' ) + 1, -2 ) );
				if ( in_array( $id, $this->config->integration_id ) && $currency === $this->order->get_currency() ) {
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
			foreach ( $this->config->integration_id as $id ) {
				$id = (int) $id;
				if ( $id > 0 ) {
					array_push( $integration_ids, $id );
				}
			}
		}
		return $integration_ids;
	}

	public function getInvoiceItems( $price ) {
		$items[] = array(
			'name'     => 'Order # ' . $this->order->get_id(),
			'amount'   => $price,
			'quantity' => 1,
		);

		return $items;
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

	public static function validateOrderInfo( $orderId ) {
		if ( empty( $orderId ) || is_null( $orderId ) || false === $orderId || '' === $orderId ) {
			wp_die( esc_html( __( 'Ops. you are accessing wrong order.', 'paymob-woocommerce' ) ) );
		}
		$order = self::getOrder( $orderId );

		$paymentMethod = $order->get_payment_method();
		if ( 'paymob' != $paymentMethod ) {
			die( esc_html( __( 'Ops. you are accessing wrong order.', 'paymob-woocommerce' ) ) );
		}
		return $order;
	}
}
