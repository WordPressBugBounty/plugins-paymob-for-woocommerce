<?php
/**
 * Filter available payment gateways based on currency and other conditions.
 *
 * @package Paymob_WooCommerce
 */

add_filter( 'woocommerce_available_payment_gateways', 'filter_payment_gateways_based_on_currency' );

/**
 * Filter available payment gateways based on currency and other conditions.
 *
 * @param array $available_gateways List of available payment gateways.
 * @return array Filtered list of available payment gateways.
 */
function filter_payment_gateways_based_on_currency( $available_gateways ) {
	global $wpdb;

	// Retrieve all gateways from the paymob_gateways table.
	$gateways                   = PaymobAutoGenerate::get_db_gateways_data();
	$paymob_options             = get_option( 'woocommerce_paymob-main_settings' );
	$default_enabled            = isset( $paymob_options['enabled'] ) ? $paymob_options['enabled'] : 'no';
	$mismatched_ids             = array();
	$mismatched_currencies      = array();
	$mismatched_integration_ids = array();

	foreach ( $gateways as $gateway ) {
		$integration_ids = explode( ',', $gateway->integration_id );
		$gateway_id      = $gateway->gateway_id;

		// Check each integration ID individually.
		foreach ( $integration_ids as $integration_id ) {
			check_integration_id(
				trim( $integration_id ),
				$gateway_id,
				$mismatched_ids,
				$mismatched_currencies,
				$mismatched_integration_ids
			);
		}
	}

	// Filter out only the non-Paymob gateways that are mismatched or have default settings as 'no'.
	foreach ( $available_gateways as $gateway_id => $gateway ) {
		if ( ! in_array( $gateway_id, array( 'paymob' ), true ) &&
			( in_array( $gateway_id, array_column( $gateways, 'gateway_id' ), true ) &&
			( in_array( $gateway_id, $mismatched_ids, true ) || 'no' === $default_enabled ) )
		) {
			unset( $available_gateways[ $gateway_id ] );
		}
	}

	// Check if the Paymob gateway should be shown.
	if ( isset( $available_gateways['paymob'] ) ) {
		$paymob_gateway = $available_gateways['paymob'];
		$integration_id = $paymob_gateway->integration_id;
		// Check if the integration ID matches the store currency.
		if ( ! check_integration_id_match( $integration_id, get_woocommerce_currency() ) || 'no' === $default_enabled ) {
			unset( $available_gateways['paymob'] );
		}
	}

	static $error_message_displayed = false;

	if ( ! $error_message_displayed &&
		( 'paymob-main' === Paymob::filterVar( 'section' ) ||
		'paymob_add_gateway' === Paymob::filterVar( 'section' ) ||
		'paymob_list_gateways' === Paymob::filterVar( 'section' ) )
	) {
		if ( ! empty( $mismatched_integration_ids ) ) {
			$mismatched_ids_string        = implode( ', ', array_unique( $mismatched_integration_ids ) );
			$mismatched_currencies_string = implode( ', ', array_unique( $mismatched_currencies ) ); // Use unique to avoid duplicate currency entries.

			$message = sprintf(
				/* translators: %1$s is a comma-separated list of integration IDs. %2$s is a comma-separated list of currencies. */
				__( 'Payment Method(s) with the Integration ID(s) (%1$s) require(s) the store currency to be set to: %2$s.', 'paymob-woocommerce' ),
				$mismatched_ids_string,
				$mismatched_currencies_string
			);

			add_action(
				'admin_notices',
				function () use ( $message ) {
					echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
				}
			);

			$error_message_displayed = true;
		}
	}

	return $available_gateways;
}
/**
 * Checks if the integration ID matches the store currency and updates mismatch arrays accordingly.
 *
 * @param string $integration_id The integration ID to check.
 * @param string $gateway_id The gateway ID associated with the integration ID.
 * @param array  &$mismatched_ids Array to collect gateway IDs with mismatched integration IDs.
 * @param array  &$mismatched_currencies Array to collect currencies associated with mismatched integration IDs.
 * @param array  &$mismatched_integration_ids Array to collect mismatched integration IDs.
 */
function check_integration_id( $integration_id, $gateway_id, &$mismatched_ids, &$mismatched_currencies, &$mismatched_integration_ids ) {
	$paymob_options   = get_option( 'woocommerce_paymob_settings' );
	$currency_matched = false; // Initialize a flag to check currency match.

	if ( isset( $paymob_options['integration_id_hidden'] ) && ! empty( $paymob_options['integration_id_hidden'] ) ) {
		$integration_id_hidden = explode( ',', $paymob_options['integration_id_hidden'] );

		foreach ( $integration_id_hidden as $entry ) {
			$parts = explode( ':', $entry );

			// Check if parts are set correctly.
			if ( count( $parts ) < 3 ) {
				continue; // Skip this entry if it doesn't have enough parts.
			}

			$id       = trim( $parts[0] );
			$currency = isset( $parts[2] ) ? trim( substr( $parts[2], strpos( $parts[2], '(' ) + 1, -2 ) ) : '';

			if ( $id === $integration_id ) {
				if ( get_woocommerce_currency() === $currency ) {
					$currency_matched = true;
					break; // Currency matched, no need to check further.
				} else {
					$currency_matched             = false;
					$mismatched_integration_ids[] = $integration_id; // Add mismatched ID to the array.
					$mismatched_ids[]             = $gateway_id; // Add gateway ID to the array.
					$mismatched_currencies[]      = $currency; // Add the associated currency to the array.
					break; // Exit the loop if currency mismatch is found.
				}
			}
		}
	}

	// If no currency matched, consider it a mismatch.
	if ( ! $currency_matched ) {
		$mismatched_integration_ids[] = $integration_id;
		$mismatched_ids[]             = $gateway_id;
	}
}
/**
 * Checks if the integration ID matches the store's current currency.
 *
 * @param string $integration_id The integration ID to check.
 * @param string $currency The currency of the store.
 * @return bool True if the integration ID matches the store currency, false otherwise.
 */
function check_integration_id_match( $integration_id, $currency ) {

	$paymob_options        = get_option( 'woocommerce_paymob_settings' );
	$integration_id_hidden = explode( ',', $paymob_options ['integration_id_hidden'] );
	foreach ( $integration_id_hidden as $entry ) {
		$parts = explode( ':', $entry );
		if ( count( $parts ) < 3 ) {
			continue; // Skip this entry if it doesn't have enough parts.
		}

		$id             = trim( $parts[0] );
		$entry_currency = isset( $parts[2] ) ? trim( substr( $parts[2], strpos( $parts[2], '(' ) + 1, -2 ) ) : '';
		if ( in_array( $id, $integration_id, true ) ) {
			return $entry_currency === $currency;
		}
	}
	return false;
}

/**
 * Enqueues the confirmation popup CSS and JavaScript for disabling a payment gateway.
 *
 * This function checks if the 'tab' parameter in the URL is 'checkout', and if so, it enqueues
 * the necessary CSS and JavaScript files for displaying a confirmation popup when disabling
 * a payment gateway. It also localizes the script with relevant data.
 */
function enqueue_disable_gateway_confirmation_script() {
	if ( ( Paymob::filterVar( 'tab' ) ) && 'checkout' === Paymob::filterVar( 'tab' ) ) {
		wp_enqueue_style( 'paymob-css', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/css/confirmation-popup.css', array(), PAYMOB_VERSION );
		// Enqueue the custom JavaScript file.
		wp_enqueue_script(
			'confirmation-popup',
			plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/confirmation-popup.js', // Adjust the path as necessary.
			array( 'jquery' ),
			PAYMOB_VERSION,
			true
		);
		$paymob_options = get_option( 'woocommerce_paymob-main_settings' );
		$pub_key        = isset( $paymob_options['pub_key'] ) ? esc_attr( $paymob_options['pub_key'] ) : '';
		$sec_key        = isset( $paymob_options['sec_key'] ) ? esc_attr( $paymob_options['sec_key'] ) : '';
		$api_key        = isset( $paymob_options['api_key'] ) ? esc_attr( $paymob_options['api_key'] ) : '';
		$exist          = ( $pub_key && $sec_key && $api_key ) ? 1 : 0;
		wp_localize_script(
			'confirmation-popup',
			'wc_admin_settings',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'your_nonce_action' ),
				'exist'                => $exist,
				'paymob_list_gateways' => admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paymob_list_gateways' ),
			)
		);
	}
}
add_action( 'admin_footer', 'enqueue_disable_gateway_confirmation_script' );

// Hook into the admin_footer action to include custom modal HTML.
add_action( 'admin_footer', 'add_custom_modal_html' );
/**
 * Adds custom modal HTML to the admin footer for confirmation before disabling the Paymob gateway.
 *
 * This function hooks into the 'admin_footer' action and includes the HTML for a confirmation modal
 * that appears on the WooCommerce settings page when the 'checkout' tab is selected.
 */
function add_custom_modal_html() {
	$screen = get_current_screen();
	global $wpdb;
	$gateways = PaymobAutoGenerate::get_db_gateways_data();

	if ( 'woocommerce_page_wc-settings' === $screen->id && Paymob::filterVar( 'tab' ) && 'checkout' === Paymob::filterVar( 'tab' ) ) {
		?>
		<!-- Custom Confirmation Modal -->
		<div id="confirmationModal">
			<h2>Disable Paymob Gateway</h2>
			<p>If you disable this gateway, all Paymob gateways will be disabled. Do you want to continue?</p>
			<ul>
				<?php
				foreach ( $gateways as $gateway ) {
					$options = get_option( 'woocommerce_' . $gateway->gateway_id . '_settings', array() );
					$enabled = isset( $options['enabled'] ) && 'yes' === $options['enabled'];
					if ( $enabled ) {
						$logo  = isset( $options['logo'] ) ? esc_url( $options['logo'] ) : '';
						$title = isset( $options['title'] ) ? esc_html( $options['title'] ) : 'Unknown Gateway';
						?>
						<li>
							<img src="<?php echo esc_url( $logo ); ?>" width="36" height="23" alt="<?php echo esc_attr( $title ); ?>">
							<?php echo esc_html( $title ); ?>
						</li>
						<?php
					}
				}
				?>
			</ul>
			<button id="confirmDisable">Disable</button>
			<button id="confirmCancel">Cancel</button>
		</div>
		<div id="overlay"></div>
		<?php
	}
}

// Hook into the AJAX action to handle gateway toggling.
add_action( 'wp_ajax_paymob_toggle_gateway', 'handle_toggle_gateway' );
/**
 * Handles AJAX requests to toggle the Paymob gateway.
 *
 * This function hooks into the 'wp_ajax_paymob_toggle_gateway' action and processes the AJAX
 * request to enable or disable the Paymob gateway based on the provided parameters.
 */
function handle_toggle_gateway() {
	// Check nonce for security.
	check_ajax_referer( 'your_nonce_action', '_ajax_nonce' );
	// Get the gateway ID and action from the AJAX request.
	$gateway_id = ( Paymob::filterVar( 'gateway_id', 'POST' ) ) ? sanitize_text_field( Paymob::filterVar( 'gateway_id', 'POST' ) ) : '';

	if ( $gateway_id ) {
		// Ensure settings is an array.
		$options            = get_option( 'woocommerce_' . $gateway_id . '_settings', array() );
		$options['enabled'] = 'no';
		update_option( 'woocommerce_' . $gateway_id . '_settings', $options );
		PaymobAutoGenerate::register_framework( $ids    = array() );
		wp_send_json_success( 'Gateway disabled' );
	} else {
		// Send an error response if gateway ID or action is not provided.
		wp_send_json_error( 'Gateway ID or toggle action not provided' );
	}
	wp_die();
}