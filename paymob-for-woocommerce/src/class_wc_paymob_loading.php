<?php
/**
 * Paymob Loading Data
 */
class WC_Paymob_Loading {

	/**
	 * Whether load_gateways() has already run this request.
	 *
	 * @var bool
	 */
	private static $gateways_loaded = false;

	public static function load() {
		WC_Paymob_Tables::maybe_ensure_tables();

		load_plugin_textdomain( 'paymob-for-woocommerce', false, PAYMOB_PLUGIN_NAME . '/i18n/languages' );

		// Safety net: if bootstrap runs after WC collects gateway classes, append them here.
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'register_gateways_fallback' ), 999 );

		if ( is_admin() ) {
			Paymob_Main_Partner_Info::partner_info();
		}

		if ( Paymob_Context::should_bootstrap_early() ) {
			self::load_gateways();
			return;
		}

		add_action( 'woocommerce_init', array( __CLASS__, 'maybe_load_gateways' ), -1 );
		add_action( 'wp', array( __CLASS__, 'maybe_load_gateways' ), 1 );
	}

	/**
	 * Load gateway hooks, sync, and per-gateway bootstrap when context requires it.
	 */
	public static function maybe_load_gateways() {
		if ( self::$gateways_loaded ) {
			return;
		}

		if ( ! Paymob_Context::should_load_gateways() ) {
			return;
		}

		self::load_gateways();
	}

	/**
	 * Heavy bootstrap: DB gateway rows, migration sync, API metadata, gateway instances.
	 */
	public static function load_gateways() {
		if ( self::$gateways_loaded ) {
			return;
		}

		self::$gateways_loaded = true;

		$gateways = PaymobAutoGenerate::get_db_gateways_data();
		WC_Paymob_HandelUpdate::handle_plugin_update( $gateways );
		WC_Paymob_GatewayData::getPaymobGatewayData();

		foreach ( $gateways as $gateway ) {
			if ( empty( $gateway->gateway_id ) || 'paymob-main' === $gateway->gateway_id ) {
				continue;
			}
			new Paymob_WooCommerce( $gateway->gateway_id );
		}
	}

	/**
	 * Fallback registration when bootstrap timing missed woocommerce_payment_gateways collection.
	 *
	 * @param array $gateways Registered gateway class names.
	 * @return array
	 */
	public static function register_gateways_fallback( $gateways ) {
		if ( ! Paymob_Context::should_load_gateways() ) {
			return $gateways;
		}

		self::load_gateways();

		$db_gateways = PaymobAutoGenerate::get_db_gateways_data();
		foreach ( $db_gateways as $row ) {
			if ( empty( $row->gateway_id ) || 'paymob-main' === $row->gateway_id ) {
				continue;
			}

			$class_name = ucwords( str_replace( '-', '_', $row->gateway_id ), '_' ) . '_Gateway';
			if ( in_array( $class_name, $gateways, true ) ) {
				continue;
			}

			$file = PAYMOB_PLUGIN_PATH . '/includes/gateways/class-gateway-' . $row->gateway_id . '.php';
			if ( ! file_exists( $file ) ) {
				continue;
			}

			include_once __DIR__ . '/../includes/gateways/class-paymob-payment.php';
			include_once $file;
			$gateways[] = $class_name;
		}

		return $gateways;
	}
}
