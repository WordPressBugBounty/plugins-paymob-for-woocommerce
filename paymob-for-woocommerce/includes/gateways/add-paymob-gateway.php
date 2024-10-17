<?php
/**
 * Adds custom sections to WooCommerce checkout settings for Paymob integrations.
 *
 * This file hooks into WooCommerce to add additional sections to the checkout settings,
 * allowing management of Paymob payment gateways.
 *
 * @package Paymob_WooCommerce
 */
add_filter( 'woocommerce_get_sections_checkout', 'add_paymob_checkout_section', 20 );
/**
 * Add custom sections to WooCommerce checkout settings
 *
 * @param array $sections Existing sections in the WooCommerce checkout settings.
 * @return array Modified sections including Paymob configurations.
 */
function add_paymob_checkout_section( $sections ) {
	global $wpdb;
	$sections    = array();
	$gateway_ids = array();
	$gateways    = PaymobAutoGenerate::get_db_gateways_data();
	foreach ( $gateways as $gateway ) {
		$gateway_ids[] = $gateway->gateway_id;
	}
	if (
		Paymob::filterVar( 'section' ) && ( in_array( Paymob::filterVar( 'section' ), $gateway_ids, true ) || 'paymob-main' === Paymob::filterVar( 'section' ) ||
			'paymob_add_gateway' === Paymob::filterVar( 'section' ) ||
			'paymob_list_gateways' === Paymob::filterVar( 'section' ) )
	) {
		$sections['paymob-main']          = __( 'Main configuration', 'paymob-woocommerce' );
		$sections['paymob_list_gateways'] = __( 'Payment Integrations', 'paymob-woocommerce' );
		$sections['paymob_add_gateway']   = __( 'Add Payment Integration', 'paymob-woocommerce' );
	}
	return $sections;
}

// Add fields to the paymob_add_gateway section.
add_filter( 'woocommerce_get_settings_checkout', 'paymob_add_gateway_settings', 10, 2 );
/**
 * Add custom settings to WooCommerce payment gateway.
 *
 * @param array  $settings The WooCommerce payment gateway settings.
 * @param string $current_section The current section being processed.
 * @return array The updated settings array
 */
function paymob_add_gateway_settings( $settings, $current_section ) {
	if ( 'paymob_add_gateway' === $current_section ) {
		$custom_settings = array(
			array(
				'type' => 'title',
				'name' => __( 'Add Payment Integration', 'paymob-woocommerce' ),
			),
			array(
				'name'     => __( 'Enable', 'paymob-woocommerce' ),
				'type'     => 'checkbox',
				'id'       => 'payment_enabled',
				'desc_tip' => true,
				'default'  => 'no',
			),
			array(
				'name'              => __( 'Payment Method', 'paymob-woocommerce' ),
				'type'              => 'text',
				'id'                => 'payment_integrations_type',
				'desc_tip'          => true,
				'custom_attributes' => array( 'required' => 'required' ),
			),
			array(
				'name'              => __( 'Paymob Integration ID', 'paymob-woocommerce' ),
				'type'              => 'select',
				'id'                => 'integration_id',
				'desc_tip'          => true,
				'custom_attributes' => array( 'required' => 'required' ),
				'options'           => PaymobAutoGenerate::get_integration_ids(), // Dynamically loaded options.
			),
			array(
				'name'              => __( 'Payment Method -  Title', 'paymob-woocommerce' ),
				'type'              => 'text',
				'id'                => 'checkout_title',
				'desc_tip'          => true,
				'custom_attributes' => array( 'required' => 'required' ),
			),
			array(
				'name'              => __( 'Payment Method -  Description', 'paymob-woocommerce' ),
				'type'              => 'textarea',
				'id'                => 'checkout_description',
				'desc_tip'          => true,
				'custom_attributes' => array( 'required' => 'required' ),
			),
			array(
				'name'              => __( 'Payment Method - Logo URL', 'paymob-woocommerce' ),
				'type'              => 'text',
				'id'                => 'payment_logo',
				'desc_tip'          => true,
				'default'           => plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png',
				'custom_attributes' => array( 'required' => 'required' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'paymob_add_gateway',
			),
		);

		// Merge custom settings with existing settings.
		$settings = array_merge( $settings, $custom_settings );
	}

	return $settings;
}
// Save custom gateway settings.
add_action( 'woocommerce_update_options_checkout', 'save_paymob_add_gateway_settings' );
/**
 * Save paymob_add_gateway settings.
 *
 * @return void
 */
function save_paymob_add_gateway_settings() {
	global $current_section, $wpdb;

	if ( 'paymob_add_gateway' !== $current_section ) {
		return;
	}

	$paymob_options = get_option( 'woocommerce_paymob_settings' );
	$pub_key        = isset( $paymob_options['pub_key'] ) ? $paymob_options['pub_key'] : '';
	$sec_key        = isset( $paymob_options['sec_key'] ) ? $paymob_options['sec_key'] : '';
	$api_key        = isset( $paymob_options['api_key'] ) ? $paymob_options['api_key'] : '';

	if ( empty( $pub_key ) || empty( $sec_key ) || empty( $api_key ) ) {
		WC_Admin_Settings::add_error( __( 'Please ensure you are entering API, public and secret keys in the main Paymob configuration.', 'paymob-woocommerce' ) );
	} else {
		$currency_errors       = array();
		$ids                   = array();
		$integration_id_hidden = explode( ',', $paymob_options['integration_id_hidden'] );
		$integration_id        = Paymob::filterVar( 'integration_id', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'integration_id', 'POST' ) ) : '';

		verify_integration_id( $integration_id_hidden, $integration_id, $currency_errors, $ids );
		if ( ! empty( $currency_errors ) ) {
			WC_Admin_Settings::add_error(
				sprintf(
					/* translators: %1$s is a comma-separated list of integration IDs. %2$s is a comma-separated list of currencies. */
					__( 'Payment Method(s) with the Integration ID(s) %1$s require(s) the store currency to be set to: %2$s', 'paymob-woocommerce' ),
					implode( ', ', $ids ),
					$currency_errors[0]
				)
			);
			return;
		}

		$payment_enabled           = Paymob::filterVar( 'payment_enabled', 'POST' ) ? 'yes' : 'no';
		$payment_integrations_type = Paymob::filterVar( 'payment_integrations_type', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'payment_integrations_type', 'POST' ) ) : '';
		$payment_logo              = Paymob::filterVar( 'payment_logo', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'payment_logo', 'POST' ) ) : '';
		$checkout_title            = Paymob::filterVar( 'checkout_title', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'checkout_title', 'POST' ) ) : '';
		$checkout_description      = Paymob::filterVar( 'checkout_description', 'POST' ) ? sanitize_text_field( Paymob::filterVar( 'checkout_description', 'POST' ) ) : '';
		$default_url               = plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png';
		$logo                      = url_exists( $payment_logo ) ? $payment_logo : $default_url;

		$class_name                = 'Paymob_' . preg_replace( '/[^a-zA-Z0-9]+/', '_', ucwords( $payment_integrations_type ) );
		$payment_integrations_type = 'paymob-' . preg_replace( '/[^a-zA-Z0-9]+/', '-', strtolower( $payment_integrations_type ) );
		$gateway                   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}paymob_gateways WHERE gateway_id = %s", $payment_integrations_type ), OBJECT );

		$file_name = 'class-gateway-' . sanitize_file_name( $payment_integrations_type ) . '.php';

		if ( ! $gateway && ! empty( $payment_integrations_type ) ) {
			append_gateway_to_paymob_order( $payment_integrations_type );
			$ordering = $wpdb->get_var( "SELECT max(ordering) FROM {$wpdb->prefix}paymob_gateways" );
			++$ordering;

			$inserted = $wpdb->insert(
				$wpdb->prefix . 'paymob_gateways',
				array(
					'gateway_id'           => $payment_integrations_type,
					'file_name'            => $file_name,
					'class_name'           => sanitize_text_field( $class_name ),
					'checkout_title'       => sanitize_text_field( $checkout_title ),
					'checkout_description' => sanitize_text_field( $checkout_description ),
					'integration_id'       => $integration_id,
					'is_manual'            => '1',
					'ordering'             => $ordering,
				)
			);

			if ( false !== $inserted ) {
				$f_array = array(
					'class_name'           => $class_name,
					'gateway_id'           => $payment_integrations_type,
					'checkout_title'       => $checkout_title,
					'checkout_description' => $checkout_description,
					'file_name'            => $file_name,
				);
				PaymobAutoGenerate::generate_files( $f_array );

				// Clear the add gateway form.
				$options_to_update = array(
					'payment_enabled'           => 'no',
					'payment_integrations_type' => '',
					'payment_logo'              => $default_url,
					'checkout_title'            => '',
					'checkout_description'      => '',
					'integration_id'            => '',
				);
				foreach ( $options_to_update as $option_name => $option_value ) {
					update_option( $option_name, $option_value );
				}

				// Save default settings for the new gateway.
				$default_settings = array(
					'enabled'               => $payment_enabled,
					'single_integration_id' => $integration_id,
					'title'                 => $checkout_title,
					'description'           => $checkout_description,
					'logo'                  => $logo,
				);
				update_option( 'woocommerce_' . $payment_integrations_type . '_settings', $default_settings );

				// Redirect to the list of gateways page.
				wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=paymob_list_gateways' ) );
				exit;
			} else {
				WC_Admin_Settings::add_error( __( 'Failed to insert gateway into database.', 'paymob-woocommerce' ) );
			}
		} else {
			WC_Admin_Settings::add_error( __( 'Gateway Already Exist.', 'paymob-woocommerce' ) );
		}
	}
}
/**
 * Verifies if the integration ID is valid based on the hidden integration ID and currency.
 *
 * @param array  $integration_id_hidden The hidden integration IDs.
 * @param string $integration_id        The integration ID to verify.
 * @param array  &$currency_errors      Reference to the array storing currency errors.
 * @param array  &$ids                  Reference to the array storing IDs.
 * @param string $gateway_id            The gateway ID (optional).
 */
function verify_integration_id( $integration_id_hidden, $integration_id, &$currency_errors, &$ids, $gateway_id = null ) {
	foreach ( $integration_id_hidden as $entry ) {
		$parts = explode( ':', $entry );
		$id    = trim( $parts[0] );
		if ( isset( $parts[2] ) ) {
			$currency = trim( substr( $parts[2], strpos( $parts[2], '(' ) + 1, -2 ) );
			if ( 'paymob' === $gateway_id ) {
				$paymob_options          = get_option( 'woocommerce_paymob_settings' );
				$unified_integration_ids = isset( $paymob_options['integration_id'] ) ? $paymob_options['integration_id'] : '';
				foreach ( $unified_integration_ids as $unified_integration_id ) {
					if ( $unified_integration_id === $id && get_woocommerce_currency() !== $currency ) {
						$currency_errors[] = $currency;
						$ids[]             = $id;
					}
				}
			} elseif ( $integration_id === $id && get_woocommerce_currency() !== $currency ) {
				$currency_errors[] = $currency;
				$ids[]             = $integration_id;
			}
		}
	}
}

/**
 * Checks if a URL exists by validating the headers.
 *
 * @param string $url The URL to check.
 * @return bool True if the URL exists, false otherwise.
 */
function url_exists( $url ) {
	$headers = get_headers( $url, 1 ); // Avoid silencing errors, use 1 to fetch headers as an array.
	return $headers && false !== strpos( $headers[0], '200' );
}
/**
 * Appends a new gateway ID to the Paymob gateway order.
 *
 * @param string $new_gateway_id The ID of the new gateway to append.
 */
function append_gateway_to_paymob_order( $new_gateway_id ) {
	// Retrieve the current paymob_gateway_order array.
	$order = get_option( 'paymob_gateway_order', array() );

	// Append the new gateway ID to the array.
	$order[] = $new_gateway_id;

	// Update the paymob_gateway_order option with the new array.
	update_option( 'paymob_gateway_order', $order );
}

// Add fields to the paymob_list_gateways section.
add_filter( 'woocommerce_get_settings_checkout', 'paymob_list_gateways_settings', 10, 2 );

/**
 * Adds custom Paymob gateway fields to the WooCommerce settings page.
 *
 * @param array  $settings The current WooCommerce settings.
 * @param string $current_section The current settings section.
 * @return array The updated settings with custom Paymob gateway fields.
 */
function paymob_list_gateways_settings( $settings, $current_section ) {
	global $wpdb;
	if ( 'paymob_list_gateways' === $current_section ) {
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}paymob_gateways ORDER BY ordering", OBJECT );

		$custom_settings = array(
			array(
				'type' => 'title',
				'name' => __( 'List of Payment Method Integrations', 'paymob-woocommerce' ),
			),
			array(
				'type' => 'table',
				'id'   => 'paymob_custom_gateways',
				'css'  => ' ',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'paymob_list_gateways',
			),
		);

		$table_body = '';

		if ( ! empty( $results ) ) {
			foreach ( $results as $gateway ) {
				$edit_url       = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $gateway->gateway_id );
				$gateway_id     = $gateway->gateway_id;
				$gateway_option = get_option( 'woocommerce_' . $gateway_id . '_settings' );
				$title          = isset( $gateway_option['title'] ) ? $gateway_option['title'] : 's';
				$description    = isset( $gateway_option['description'] ) ? $gateway_option['description'] : 'z';
				$logo           = isset( $gateway_option['logo'] ) ? $gateway_option['logo'] : plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png';
				$integration_id = ( 'paymob' === $gateway_id ) ? $gateway->integration_id : $gateway_option['single_integration_id'];
				$enabled        = isset( $gateway_option['enabled'] ) ? $gateway_option['enabled'] : 'no';
				$checked        = 'yes' === $enabled ? 'checked' : '';

				// Handling long descriptions.
				$short_description = strlen( $description ) > 100 ? substr( $description, 0, 100 ) . '...' : $description;
				$show_more_link    = strlen( $description ) > 100 ? '<a href="javascript:void(0);" class="show-more">Show More</a>' : '';

				$row_html  = '<tr data-gateway-id="' . esc_attr( $gateway_id ) . '">';
				$row_html .= '<td style="cursor: move;"><span class="dashicons dashicons-editor-justify"></span></td>';
				$row_html .= '<td><input type="checkbox" class="enable-checkbox" data-gateway-id="' . $gateway_id . '" data-integration-id="' . $integration_id . '" ' . $checked . ' /></td>';
				$row_html .= '<td>' . esc_html( $gateway_id ) . '</td>';
				$row_html .= '<td>' . esc_html( $title ) . '</td>';
				$row_html .= '<td><span class="short-description">' . esc_html( $short_description ) . '</span><span class="full-description" style="display:none;">' . esc_html( $description ) . '</span>' . $show_more_link . '</td>';
				$row_html .= '<td>' . esc_html( $integration_id ) . '</td>';
				$row_html .= '<td><img style="max-width: 70px;" src="' . esc_url( $logo ) . '" /></td>';
				$row_html .= '<td><a href="' . esc_url( $edit_url ) . '" class="button button-secondary">Edit</a> ';

				if ( '0' === $gateway->is_manual ) {
					$row_html .= ' <button type="button" class="button" disabled="disabled">Remove</button></td>';
				} else {
					$row_html .= ' <button type="button" class="button remove-button button-primary" data-gateway-id="' . $gateway_id . '">Remove</button></td>';
				}
				$row_html   .= '</tr>';
				$table_body .= $row_html;
			}
		} else {
			$table_body .= '<tr><td colspan="8">' . __( 'No gateways found.', 'paymob-woocommerce' ) . '</td></tr>';
		}

		echo '<script>window.paymob_gateways_table_body = ' . wp_json_encode( $table_body ) . ';</script>';
		echo '<div id="confirmation-modal" style="display:none;">
            <div id="confirmation-modal-content">
                <h2 id="confirmation-modal-title"></h2>
                <p id="confirmation-modal-message"></p>
                <button type="button" id="confirmation-modal-confirm" class="button button-primary">Confirm</button>
                <button type="button" id="confirmation-modal-cancel" class="button">Cancel</button>
            </div>
        </div> <div class="loader_paymob"></div>';
		$settings = array_merge( $settings, $custom_settings );
	}

	return $settings;
}
// Handle the 'table' field type.
add_action( 'woocommerce_admin_field_table', 'create_paymob_custom_gateways_table' );
/**
 * Generates the HTML for the Paymob custom gateways table in the WooCommerce settings.
 *
 * @param array $value The value array containing table configuration data.
 */
function create_paymob_custom_gateways_table( $value ) {
	?>
	<table id="paymob_custom_gateways" class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Re-Order', 'paymob-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Enable / Disable', 'paymob-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Payment Method', 'paymob-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Title', 'paymob-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Description', 'paymob-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Integration ID', 'paymob-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Logo', 'paymob-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Action', 'paymob-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="5"><?php esc_html_e( 'Loading...', 'paymob-woocommerce' ); ?></td>
			</tr>
		</tbody>
	</table>
	<p><a href="#" style="cursor:pointer;"
			id="reset-paymob-gateways"><?php esc_html_e( 'Click here', 'paymob-woocommerce' ); ?></a>
		<?php esc_html_e( 'to re-authenticate your Paymob configuration to get the new updated payment methods.', 'paymob-woocommerce' ); ?>
	</p>

	<?php
}
// Add AJAX handler for resetting the gateways.
add_action( 'wp_ajax_reset_paymob_gateways', 'reset_paymob_gateways' );
/**
 * AJAX handler to reset the Paymob gateways.
 *
 * Retrieves the necessary keys from the Paymob settings and attempts.
 * to reset the available payment methods by making API requests.
 */
function reset_paymob_gateways() {
	// Verify nonce for security.
	check_ajax_referer( 'reset_paymob_gateways', 'security' );

	// Retrieve the main Paymob options.
	$main_options    = get_option( 'woocommerce_paymob-main_settings' );
	$default_enabled = isset( $main_options['enabled'] ) ? $main_options['enabled'] : '';
	// Retrieve the debug setting.
	$debug_ = isset( $main_options['debug'] ) ? $main_options['debug'] : '';
	$debug  = 'yes' === $debug_ ? '1' : '0';
	// Load integration keys.
	$conf['apiKey'] = isset( $main_options['api_key'] ) ? $main_options['api_key'] : '';
	$conf['pubKey'] = isset( $main_options['pub_key'] ) ? $main_options['pub_key'] : '';
	$conf['secKey'] = isset( $main_options['sec_key'] ) ? $main_options['sec_key'] : '';

	// Check if all keys are present.
	if ( ! empty( $conf['apiKey'] ) && ! empty( $conf['pubKey'] ) && ! empty( $conf['secKey'] ) ) {
		// Instantiate the Paymob request handler.
		$paymob_req = new Paymob( $debug, WC_LOG_DIR . 'paymob.log' );
		// Get the auth token and gateway data.
		$result       = $paymob_req->authToken( $conf );
		$gateway_data = $paymob_req->getPaymobGateways( $conf['secKey'], PAYMOB_PLUGIN_PATH . 'assets/img/' );
		update_option( 'woocommerce_paymob_gateway_data', $gateway_data );
		// Auto-generate the gateways.
		PaymobAutoGenerate::create_gateways( $result, 1, $gateway_data );
		$integration_id_hidden = array();
		$ids                   = array();
		foreach ( $result['integrationIDs'] as $value ) {
			$text                    = $value['id'] . ' : ' . $value['name'] . ' (' . $value['type'] . ' : ' . $value['currency'] . ' )';
			$integration_id_hidden[] = $text . ',';
			$ids[]                   = trim( $value['id'] );
		}
		if ( 'yes' === $default_enabled ) {
			PaymobAutoGenerate::register_framework( $ids, $debug_ ? 'yes' : 'no' );
		}
		$paymob_existing_settings = get_option( 'woocommerce_paymob_settings', array() );
		// Update only the specific fields we need.
		$paymob_existing_settings['integration_id_hidden'] = implode( "\n", $integration_id_hidden );

		// Save the updated settings back to the database.
		update_option( 'woocommerce_paymob_settings', $paymob_existing_settings );
		// Return success message.
		wp_send_json_success( array( 'message' => __( 'Payment methods have been reset successfully.', 'paymob-woocommerce' ) ) );
	}
}

add_action( 'wp_ajax_save_paymob_gateway_order', 'save_paymob_gateway_order' );
/**
 * Saves the custom order of Paymob payment gateways.
 *
 * This function is responsible for handling the AJAX request to save the new
 * order of Paymob gateways, ensuring that the updated order is stored in the
 * appropriate WooCommerce settings.
 */
function save_paymob_gateway_order() {
	check_ajax_referer( 'save_gateway_order', 'security' );
	if ( ( Paymob::filterVar( 'order', 'POST' ) ) && is_array( Paymob::filterVar( 'order', 'POST' ) ) ) {
		global $wpdb;
		// Update the ordering column.
		$order = Paymob::filterVar( 'order', 'POST' );
		foreach ( $order as $index => $gateway_id ) {
			$wpdb->update(
				$wpdb->prefix . 'paymob_gateways',
				array( 'ordering' => $index ),
				array( 'gateway_id' => sanitize_text_field( $gateway_id ) ),
				array( '%d' ),
				array( '%s' )
			);
		}
		$order = array_map( 'sanitize_text_field', $order );
		update_option( 'paymob_gateway_order', $order );
		wp_send_json_success();
	} else {
		wp_send_json_error( 'Invalid order data.' );
	}
}
add_filter( 'woocommerce_available_payment_gateways', 'apply_paymob_gateway_order' );

/**
 * Reorders Paymob gateways based on a custom saved order.
 *
 * This function applies the saved order of the Paymob gateways on the checkout page. It ensures
 * that Paymob gateways are listed in the desired order and updates the WooCommerce gateway
 * order settings accordingly.
 *
 * @param array $available_gateways The available payment gateways.
 * @return array The reordered payment gateways.
 */
function apply_paymob_gateway_order( $available_gateways ) {
	$paymob_options  = get_option( 'woocommerce_paymob-main_settings' );
	$default_enabled = isset( $paymob_options['enabled'] ) ? $paymob_options['enabled'] : 'no';

	if ( is_checkout() && 'yes' === $default_enabled ) {
		$order         = get_option( 'paymob_gateway_order', array() );
		$gateway_order = (array) get_option( 'woocommerce_gateway_order' );

		if ( ! empty( $order ) ) {
			$sorted_gateways   = array();
			$paymob_main_index = array_search( 'paymob-main', array_keys( $available_gateways ), true );

			// Sort gateways according to the saved order.
			foreach ( $order as $gateway_id ) {
				if ( isset( $available_gateways[ $gateway_id ] ) ) {
					$sorted_gateways[ $gateway_id ] = $available_gateways[ $gateway_id ];
					unset( $available_gateways[ $gateway_id ] );
				}
			}

			// Add paymob-main at the top and sub-gateways next.
			if ( false !== $paymob_main_index ) {
				$available_gateways = array_slice( $available_gateways, 0, $paymob_main_index, true ) +
					array( 'paymob-main' => $available_gateways['paymob-main'] ) +
					$sorted_gateways +
					array_slice( $available_gateways, $paymob_main_index + 1, null, true );
			} else {
				$available_gateways = array( 'paymob-main' => $available_gateways['paymob-main'] ) + $sorted_gateways + $available_gateways;
			}
		}

		// Update the order in WooCommerce settings.
		$gateway_order    = 0;
		$ordered_gateways = array();
		foreach ( $available_gateways as $index => $gateway_id ) {
			$ordered_gateways[ $index ] = $gateway_order;
			++$gateway_order;
		}
		// Update the WooCommerce gateway order option.
		update_option( 'woocommerce_gateway_order', $ordered_gateways );

		// Ensure that paymob-main is unset from the list after reordering.
		if ( isset( $available_gateways['paymob-main'] ) ) {
			unset( $available_gateways['paymob-main'] );
		}
	}

	return $available_gateways;
}
// AJAX to handle gateway deletion.
add_action( 'wp_ajax_delete_gateway', 'delete_gateway' );
/**
 * Handles the AJAX request to delete a payment gateway.
 *
 * This function handles the deletion of payment gateway files and database records. It performs
 * the following actions:
 * - Deletes JavaScript and PHP files associated with the gateway.
 * - Removes the gateway record from the database.
 * - Deletes the gateway settings option.
 * - Removes the gateway from the Paymob order list.
 *
 * @return void
 */
function delete_gateway() {
	global $wpdb;

	// Verify the nonce for security.
	check_ajax_referer( 'delete_gateway_nonce', 'security' );
	// Sanitize the gateway ID from the request.
	$gateway_id = sanitize_text_field( Paymob::filterVar( 'gateway_id', 'POST' ) );

	if ( ! empty( $gateway_id ) ) {
		$js_file   = PAYMOB_PLUGIN_PATH . 'assets/js/blocks/' . $gateway_id . '_block.js';
		$blc_file  = PAYMOB_PLUGIN_PATH . 'includes/blocks/' . $gateway_id . '-block.php';
		$file_path = PAYMOB_PLUGIN_PATH . 'includes/gateways/class-gateway-' . $gateway_id . '.php';

		// Unlink the files if they exist.
		if ( file_exists( $js_file ) ) {
			wp_delete_file( $js_file );
		}
		if ( file_exists( $blc_file ) ) {
			wp_delete_file( $blc_file );
		}
		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}

		// Remove the gateway from the database.
		$wpdb->delete(
			$wpdb->prefix . 'paymob_gateways',
			array( 'gateway_id' => $gateway_id ),
			array( '%s' )
		);

		// Delete the gateway settings option.
		delete_option( 'woocommerce_' . $gateway_id . '_settings' );

		// Remove the gateway from the Paymob order list.
		remove_gateway_from_paymob_order( $gateway_id );

		// Send a success response.
		wp_send_json_success( array( 'status' => 'success' ) );
	} else {
		// Send an error response if the gateway ID is invalid.
		wp_send_json_error(
			array(
				'status'  => 'error',
				'message' => 'Invalid gateway ID.',
			)
		);
	}

	wp_die();
}
/**
 * Removes a specified gateway from the Paymob order array.
 *
 * This function retrieves the current order array of Paymob gateways, removes the specified gateway ID
 * if it exists, and then updates the order array without gaps in keys.
 *
 * @param string $gateway_id_to_remove The ID of the gateway to remove from the order array.
 * @return void
 */
function remove_gateway_from_paymob_order( $gateway_id_to_remove ) {
	// Retrieve the current paymob_gateway_order array.
	$order = get_option( 'paymob_gateway_order', array() );

	// Search for the gateway ID and remove it if it exists.
	$index = array_search( $gateway_id_to_remove, $order, true );
	if ( false !== $index ) {
		unset( $order[ $index ] );
	}

	// Reindex the array to prevent gaps in the keys.
	$order = array_values( $order );

	// Update the paymob_gateway_order option with the new array.
	update_option( 'paymob_gateway_order', $order );
}

// AJAX to handle gateway enabling/disabling.
add_action( 'wp_ajax_toggle_gateway', 'toggle_gateway' );
/**
 * Toggles the status of a payment gateway.
 *
 * This function handles the AJAX request to enable or disable a payment gateway.
 * It checks the current status, validates required API keys, and updates the gateway's status accordingly.
 *
 * @return void
 */
function toggle_gateway() {
	check_ajax_referer( 'toggle_gateway_nonce', 'security' );

	$gateway_id     = sanitize_text_field( Paymob::filterVar( 'gateway_id', 'POST' ) );
	$current_status = get_option( 'woocommerce_' . $gateway_id . '_settings' )['enabled'];
	$enabled        = ( 'yes' === $current_status ) ? 'no' : 'yes';

	if ( 'yes' === $enabled ) {
		$paymob_options = get_option( 'woocommerce_paymob_settings' );
		$pub_key        = isset( $paymob_options['pub_key'] ) ? $paymob_options['pub_key'] : '';
		$sec_key        = isset( $paymob_options['sec_key'] ) ? $paymob_options['sec_key'] : '';
		$api_key        = isset( $paymob_options['api_key'] ) ? $paymob_options['api_key'] : '';

		if ( empty( $pub_key ) || empty( $sec_key ) || empty( $api_key ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'msg'     => 'Please ensure you are entering API, public, and secret keys in the main Paymob configuration.',
				)
			);
		}

		$integration_id = sanitize_text_field( Paymob::filterVar( 'integration_id', 'POST' ) );
		$ids            = array();
		if ( isset( $paymob_options['integration_id_hidden'] ) && ! empty( $paymob_options['integration_id_hidden'] ) ) {
			$integration_id_hidden = explode( ',', $paymob_options['integration_id_hidden'] );
			$currency_errors       = array();
			verify_integration_id( $integration_id_hidden, $integration_id, $currency_errors, $ids, $gateway_id );

			if ( ! empty( $currency_errors ) ) {
				wp_send_json_error(
					array(
						'success' => false,
						'msg'     => 'Payment Method(s) with the Integration ID(s) ' . implode( ', ', array_unique( $ids ) ) . ' require(s) the store currency to be set to: ' . implode( ', ', array_unique( $currency_errors ) ),
					)
				);
			}
		}
	}

	$settings = get_option( 'woocommerce_' . $gateway_id . '_settings', array() );
	// Merge the new status with the existing settings.
	$settings['enabled'] = $enabled;
	// Update the gateway settings with the new status.
	update_option( 'woocommerce_' . $gateway_id . '_settings', $settings );
	// Register the Framework into Paymob if enabled.
	register_frameworks();

	wp_send_json_success(
		array(
			'success' => true,
			'msg'     => 'Payment Method status updated successfully.',
		)
	);

	wp_die();
}
/**
 * Registers the framework with Paymob if enabled.
 *
 * This function checks the main Paymob settings and, if the gateway is enabled, collects
 * integration IDs from all active gateways and registers the framework with Paymob.
 *
 * @return void
 */
function register_frameworks() {
	$paymob_options  = get_option( 'woocommerce_paymob-main_settings' );
	$default_enabled = isset( $paymob_options['enabled'] ) ? $paymob_options['enabled'] : '';
	if ( 'yes' === $default_enabled ) {
		$gateways = PaymobAutoGenerate::get_db_gateways_data();
		$ids      = array();

		foreach ( $gateways as $gateway ) {
			$options = get_option( 'woocommerce_' . $gateway->gateway_id . '_settings', array() );

			if ( isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
				// Collect single_integration_id.
				if ( isset( $options['single_integration_id'] ) ) {
					$ids[] = (int) $options['single_integration_id'];
				}

				// Collect and merge integration_id array.
				if ( isset( $options['integration_id'] ) && is_array( $options['integration_id'] ) ) {
					$ids = array_merge( $ids, array_map( 'intval', $options['integration_id'] ) );
				}
			}
		}
		PaymobAutoGenerate::register_framework( $ids );
	}
}

/**
 * Adds custom CSS to hide specific gateways and elements.
 *
 * This function generates and outputs CSS to hide specific payment gateways and
 * certain elements if the tab is 'checkout'. The CSS is fetched based on gateway IDs
 * and additional styles are added for specific elements.
 *
 * @return void
 */
function gateways_to_hide_css() {
	if ( ( Paymob::filterVar( 'tab' ) ) && Paymob::filterVar( 'tab' ) === 'checkout' ) {
		// Fetch gateway IDs from your custom table.
		$results = PaymobAutoGenerate::get_db_gateways_data();
		$css     = '';

		foreach ( $results as $result ) {
			$css .= 'tr[data-gateway_id="' . esc_attr( $result->gateway_id ) . '"] { display: none; }';
		}

		if ( ! empty( $css ) ) {
			echo '<style>' . wp_kses( $css, array() ) . '</style>';
		}
	}
	echo '<style>a[href*="section=paymob-main"].wc-block-editor-components-external-link-card {
                    display: none;
                }
                .wc-blocks-incompatible-extensions-notice.components-notice.is-warning.is-dismissible {
                    display: none !important;
                }
        </style>';
}
add_action( 'admin_head', 'gateways_to_hide_css' );

add_action( 'admin_enqueue_scripts', 'enqueue_paymob_list_gateways_styles' );
/**
 * Enqueue custom CSS for Paymob gateways list in WooCommerce admin.
 */
function enqueue_paymob_list_gateways_styles() {
	$current_section = Paymob::filterVar( 'section' ) ? sanitize_text_field( Paymob::filterVar( 'section' ) ) : '';
	if ( 'paymob_list_gateways' === $current_section ) {
		wp_enqueue_style( 'paymob_list_gateways', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/css/paymob_list_gateways.css', array(), PAYMOB_VERSION );
	}
}

add_action( 'admin_enqueue_scripts', 'enqueue_paymob_admin_scripts' );
/**
 * Enqueue JavaScript to handle AJAX for gateway deletion and enabling/disabling.
 */
function enqueue_paymob_admin_scripts() {
	$current_section = ( Paymob::filterVar( 'section' ) ) ? sanitize_text_field( Paymob::filterVar( 'section' ) ) : '';
	if ( 'paymob_list_gateways' === $current_section ) {
		wp_enqueue_script( 'paymob-admin-scripts', plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/js/paymob_list_gateways.js', array( 'jquery' ), PAYMOB_VERSION, true );
		wp_localize_script(
			'paymob-admin-scripts',
			'paymob_admin_ajax',
			array(
				'ajax_url'                    => admin_url( 'admin-ajax.php' ),
				'delete_nonce'                => wp_create_nonce( 'delete_gateway_nonce' ),
				'toggle_nonce'                => wp_create_nonce( 'toggle_gateway_nonce' ),
				'save_gateway_order_nonce'    => wp_create_nonce( 'save_gateway_order' ),
				'reset_paymob_gateways_nonce' => wp_create_nonce( 'reset_paymob_gateways' ),
			)
		);
	}
}

add_action( 'admin_head', 'hide_save_changes_button_in_paymob_list_gateways_section' );
/**
 * Hide save changes button in paymob_list_gateways section.
 */
function hide_save_changes_button_in_paymob_list_gateways_section() {
	$current_section = ( Paymob::filterVar( 'section' ) ) ? sanitize_text_field( Paymob::filterVar( 'section' ) ) : '';
	if ( 'paymob_list_gateways' === $current_section ) {
		echo '<style>#mainform .submit { display: none; }</style>';
	}
}
