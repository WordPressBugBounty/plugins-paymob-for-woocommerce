<?php
class Paymob_Pixel_Update_Intention {
	public static function update_intention($order_id,$order)
	{
		$payment_method_id = $order->get_payment_method();
		// Retrieve plugin options
		$paymobOptions = get_option('woocommerce_paymob-main_settings', []);
		$secKey = $paymobOptions['sec_key'] ?? '';
		$debug = !empty($paymobOptions['debug']) ? '1' : '0';
		$log_file = WC_LOG_DIR . $payment_method_id . '.log';
		$intention_order_id = WC()->session->get('intention_order_id');
		$cs = WC()->session->get('cs');
		// Retrieve the order object
		
		$billing = [
			'email' => $order->get_billing_email(),
			'first_name' => $order->get_billing_first_name() ?: 'NA',
			'last_name' => $order->get_billing_last_name() ?: 'NA',
			'street' => $order->get_billing_address_1() . ' - ' . ($order->get_billing_address_2() ?: ''),
			'phone_number' => $order->get_billing_phone() ?: 'NA',
			'city' => $order->get_billing_city() ?: 'NA',
			'country' => $order->get_billing_country() ?: 'NA',
			'state' => $order->get_billing_state() ?: 'NA',
			'postal_code' => $order->get_billing_postcode() ?: 'NA',
		];

		// Calculate the amount in cents
		$country = Paymob::getCountryCode($secKey);
		$cents_multiplier = $country === 'omn' ? 1000 : 100;
		$precision = $country === 'omn' ? 3 : 2;
		
		// Prepare data for Paymob API
		$data = [
			'accept_order_id' => $intention_order_id,
			'billing_data' => $billing,
			// 'special_reference' => $order_id . '_' . time(),
		];
        $final_total = WC()->session->get('paymob_final_total');
        $final_cents_session = (int) WC()->session->get( 'paymob_final_cents' );
        $discount_value = WC()->session->get('paymob_discount');
        $discount_cents_session = (int) WC()->session->get( 'paymob_discount_cents' );
        $ir_enabled = '1' === (string) WC()->session->get( 'paymob_instant_refund_enabled', '0' );
        $instant_refund_fee = $ir_enabled
            ? (float) WC()->session->get( 'paymob_instant_refund_fee', 0 )
            : 0;
        $fee_cents_session = $ir_enabled ? (int) WC()->session->get( 'paymob_instant_refund_fee_cents', 0 ) : 0;

        if ( $final_cents_session > 0 || ( $final_total && $final_total > 0 ) ) {
            // Prefer exact cents from the discount/IR payload (avoids 0.5 → 1 float drift).
            $amount = $final_cents_session > 0
                ? $final_cents_session
                : ( function_exists( 'paymob_pixel_amount_to_cents' )
                    ? paymob_pixel_amount_to_cents( (float) $final_total, $cents_multiplier )
                    : (int) round( (float) $final_total * (int) $cents_multiplier ) );
            $data['amount'] = $amount;

            if ( $discount_cents_session <= 0 && $discount_value && (float) $discount_value > 0 ) {
                $discount_cents_session = function_exists( 'paymob_pixel_amount_to_cents' )
                    ? paymob_pixel_amount_to_cents( (float) $discount_value, $cents_multiplier )
                    : (int) round( (float) $discount_value * (int) $cents_multiplier );
            }
            if ( $fee_cents_session <= 0 && $ir_enabled && $instant_refund_fee > 0 ) {
                $fee_cents_session = function_exists( 'paymob_pixel_amount_to_cents' )
                    ? paymob_pixel_amount_to_cents( (float) $instant_refund_fee, $cents_multiplier )
                    : (int) round( (float) $instant_refund_fee * (int) $cents_multiplier );
            }

            // Keep Woo total / discount at Paymob precision (never shop 0-decimal rounding).
            if ( function_exists( 'paymob_pixel_apply_order_amounts' ) ) {
                paymob_pixel_apply_order_amounts(
                    $order,
                    $amount,
                    $discount_cents_session,
                    $ir_enabled ? $fee_cents_session : 0,
                    $ir_enabled
                );
            } else {
                if ( function_exists( 'paymob_pixel_begin_precise_amounts' ) ) {
                    paymob_pixel_begin_precise_amounts( $precision );
                }
                $synced_total = round( $amount / $cents_multiplier, $precision );
                $order->set_total( $synced_total );
                if ( $discount_cents_session > 0 ) {
                    $order->set_discount_total( round( $discount_cents_session / $cents_multiplier, $precision ) );
                }
                $order->update_meta_data( '_paymob_pixel_total_synced', 1 );
                $order->update_meta_data( '_paymob_paid_amount_cents', $amount );
                if ( function_exists( 'paymob_pixel_end_precise_amounts' ) ) {
                    paymob_pixel_end_precise_amounts();
                }
            }

            // Instant Refund notes (only when shopper enabled the toggle).
            if ( $ir_enabled && $fee_cents_session > 0 ) {
                if ( ! $order->get_meta( '_paymob_instant_refund_note_added' ) ) {
                    $fee_major = function_exists( 'paymob_pixel_cents_to_major' )
                        ? paymob_pixel_cents_to_major( $fee_cents_session, $cents_multiplier )
                        : round( $fee_cents_session / $cents_multiplier, $precision );
                    $fee_html = function_exists( 'paymob_pixel_format_price' )
                        ? paymob_pixel_format_price( $fee_major, $order->get_currency() )
                        : wc_price( $fee_major, array( 'currency' => $order->get_currency(), 'decimals' => $precision ) );
                    $order->add_order_note(
                        sprintf(
                            /* translators: %s: formatted fee amount */
                            __( 'Paymob Instant Refund Fee: %s (non-refundable)', 'paymob-woocommerce' ),
                            $fee_html
                        )
                    );
                    $order->update_meta_data( '_paymob_instant_refund_note_added', 1 );
                }
            } else {
                $order->delete_meta_data( '_paymob_instant_refund_fees' );
                $order->delete_meta_data( '_paymob_instant_refund' );
                $order->delete_meta_data( '_paymob_instant_refund_note_added' );
                $order->delete_meta_data( '_paymob_instant_refund_applied' );
                $order->delete_meta_data( '_paymob_instant_refund_handled' );
            }
            $order->save();
        }
		
		// Send the request to Paymob
		$paymobReq = new Paymob($debug, $log_file);
		$response = $paymobReq->createIntention($secKey, $data, $order_id, $cs,'PUT');
		return $response;
	}
}
