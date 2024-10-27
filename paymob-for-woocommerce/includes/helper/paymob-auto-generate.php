<?php
/**
 * Class PaymobAutoGenerate
 *
 * This file contains the PaymobAutoGenerate class, which provides functionality
 * for auto-generating Paymob gateway files and handling integration details
 * such as creating gateways, updating orders, and managing integration settings
 * in WooCommerce.
 *
 * @package PaymobWooCommerce
 * @subpackage Classes
 */
class PaymobAutoGenerate {
	/**
	 * Generates the required files for Paymob integration.
	 *
	 * @param array $file_data An associative array containing the following keys:
	 *                       - 'file_name': The name of the file to generate.
	 *                       - 'gateway_id': The ID of the gateway.
	 *                       - 'class_name': The class name for the gateway.
	 *                       - 'checkout_title': The title for the checkout page.
	 *                       - 'checkout_description': The description for the checkout page.
	 *
	 * @return void
	 */
	public static function generate_files( $file_data ) {
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$gateway_file_path       = PAYMOB_PLUGIN_PATH . '/includes/gateways/' . $file_data['file_name'];
		$gateway_block_file_path = PAYMOB_PLUGIN_PATH . '/includes/blocks/' . $file_data['gateway_id'] . '-block.php';
		$gateway_js_file_path    = PAYMOB_PLUGIN_PATH . '/assets/js/blocks/' . $file_data['gateway_id'] . '_block.js';

		// Ensure the directories exist.
		$directories = array(
			dirname( $gateway_file_path ),
			dirname( $gateway_block_file_path ),
			dirname( $gateway_js_file_path ),
		);

		foreach ( $directories as $directory ) {
			if ( ! $wp_filesystem->is_dir( $directory ) ) {
				$wp_filesystem->mkdir( $directory );
			}
		}

		// Check if files already exist.
		if ( $wp_filesystem->exists( $gateway_file_path ) && $wp_filesystem->exists( $gateway_block_file_path ) && $wp_filesystem->exists( $gateway_js_file_path ) ) {
			// Files already exist, do not create them again.
			return;
		}

		$template_gateway_file       = PAYMOB_PLUGIN_PATH . '/templates/gateway.php';
		$template_gateway_block_file = PAYMOB_PLUGIN_PATH . '/templates/gateway_block.php';
		$template_gateway_js_file    = PAYMOB_PLUGIN_PATH . '/templates/gateway_block.js';

		// Ensure the template files exist.
		if ( ! $wp_filesystem->exists( $template_gateway_file ) || ! $wp_filesystem->exists( $template_gateway_block_file ) || ! $wp_filesystem->exists( $template_gateway_js_file ) ) {
			// Log an error or handle it as needed.
			return;
		}

		$content = $wp_filesystem->get_contents( $template_gateway_file );
		$content = str_replace( 'class_name', $file_data['class_name'], $content );
		$content = str_replace( 'gateway_id', $file_data['gateway_id'], $content );
		$content = str_replace( 'checkout_title', $file_data['checkout_title'], $content );
		$content = str_replace( 'checkout_description', $file_data['checkout_description'], $content );
		$wp_filesystem->put_contents( $gateway_file_path, $content );

		$cb_content = $wp_filesystem->get_contents( $template_gateway_block_file );
		$cb_content = str_replace( 'class_name', $file_data['class_name'], $cb_content );
		$cb_content = str_replace( 'gateway_id', $file_data['gateway_id'], $cb_content );
		$wp_filesystem->put_contents( $gateway_block_file_path, $cb_content );

		$jb_content = $wp_filesystem->get_contents( $template_gateway_js_file );
		$jb_content = str_replace( 'checkout_title', $file_data['checkout_title'], $jb_content );
		$jb_content = str_replace( 'gateway_id', $file_data['gateway_id'], $jb_content );
		if ( false !== strpos( $file_data['gateway_id'], 'apple-pay' ) ) {
			$jb_content = str_replace( '//check_a_pay', 'if (!typeof window.ApplePaySession)', $jb_content );
		} else {
			$jb_content = str_replace( '//check_a_pay', '', $jb_content );
		}
		$wp_filesystem->put_contents( $gateway_js_file_path, $jb_content );
	}
	/**
	 * Creates and updates Paymob gateways based on the provided data.
	 *
	 * @param array $result Result data containing integration IDs and other information.
	 * @param int   $paymob_setting Paymob setting status (e.g., 1 for enabled, 0 for disabled).
	 * @param array $gateway_data An associative array containing gateway-specific details.
	 *
	 * @return void
	 */
	public static function create_gateways( $result, $paymob_setting, $gateway_data ) {
		global $wpdb;
		$ids = array();
		if ( isset( $result['integrationIDs'] ) ) {
			foreach ( $result['integrationIDs'] as $value ) {
				$title                     = empty( $value['name'] ) ? $value['type'] : $value['name'];
				$ids[]                     = trim( $value['id'] );
				$payment_integrations_type = $value['id'] . ' ' . $title . ' ' . $value['gateway_type'] . ' ' . $value['currency'];
				$checkout_title            = isset( $gateway_data[ strtolower( $value['gateway_type'] ) ]['title'] ) ? $gateway_data[ strtolower( $value['gateway_type'] ) ]['title'] : $title;
				$checkout_desc             = isset( $gateway_data[ strtolower( $value['gateway_type'] ) ]['desc'] ) ? $gateway_data[ strtolower( $value['gateway_type'] ) ]['desc'] : $title;
				$class_name                = 'Paymob_' . preg_replace( '/[^a-zA-Z0-9]+/', '_', ucwords( $payment_integrations_type ) );
				$payment_integrations_type = 'paymob-' . preg_replace( '/[^a-zA-Z0-9]+/', '-', strtolower( $payment_integrations_type ) );
				$file_name                 = 'class-gateway-' . sanitize_file_name( $payment_integrations_type ) . '.php';
				$logo                      = file_exists( PAYMOB_PLUGIN_PATH . '/assets/img/' . strtolower( $value['gateway_type'] ) . '.png' ) ?
					plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/' . strtolower( $value['gateway_type'] ) . '.png'
					: plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/paymob.png';

				$ordering = 2;

				// in case Apple Pay / Google Pay / Installment
				$applePay = strpos( $payment_integrations_type, 'apple-pay' );
				if ( false !== $applePay ) {
					$checkout_title = __( 'Apple Pay', 'paymob-woocommerce' );
					$checkout_desc  = __( 'Secure Payment Via Paymob Checkout', 'paymob-woocommerce' );
					$logo           = plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/applepay.png';
				}

				$googlePay = strpos( $payment_integrations_type, 'google-pay' );
				if ( false !== $googlePay ) {
					$checkout_title = __( 'Google Pay', 'paymob-woocommerce' );
					$checkout_desc  = __( 'Secure Payment Via Paymob Checkout', 'paymob-woocommerce' );
					$logo           = plugins_url( PAYMOB_PLUGIN_NAME ) . '/assets/img/googlepay.png';
				}

				$bankInstallments = strpos( $payment_integrations_type, 'bank-installments' );
				if ( false !== $bankInstallments ) {
					$checkout_title = __( 'Bank Installments', 'paymob-woocommerce' );
					$checkout_desc  = __( 'Split your payment into monthly installments', 'paymob-woocommerce' );
				}
				// End Apple Pay / Google Pay / Installment

				if ( false !== stripos( strtolower( $checkout_title ), 'card' ) ) {
					$ordering = 1;
				}

				if ( false !== stripos( strtolower( $checkout_title ), 'kiosk' ) ) {
					$ordering = 29;
				}

				$row_count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}paymob_gateways WHERE gateway_id = %s",
						$payment_integrations_type
					)
				);

				if ( '0' === $row_count ) {
					$f_array = array(
						'class_name'           => $class_name,
						'gateway_id'           => $payment_integrations_type,
						'checkout_title'       => $checkout_title,
						'checkout_description' => $checkout_desc,
						'file_name'            => $file_name,
					);
					self::generate_files( $f_array );
					$wpdb->insert(
						$wpdb->prefix . 'paymob_gateways',
						array(
							'gateway_id'           => $payment_integrations_type,
							'file_name'            => $file_name,
							'class_name'           => sanitize_text_field( $class_name ),
							'checkout_title'       => sanitize_text_field( $checkout_title ),
							'checkout_description' => sanitize_text_field( $checkout_desc ),
							'integration_id'       => sanitize_text_field( $value['id'] ),
							'is_manual'            => '0',
							'ordering'             => $ordering,
						)
					);

					$enabled = 'no'; // Default to 'no'.

					if ( 1 == $paymob_setting && $value['currency'] === get_woocommerce_currency() ) {
						$enabled = 'yes';
					} elseif ( 1 != $paymob_setting && self::check_enabled( $value['id'], $value['currency'] ) ) {
						$enabled = 'yes';
					}

					// Save default settings for the new gateway.
					$default_settings = array(
						'enabled'               => $enabled,
						'single_integration_id' => $value['id'],
						'title'                 => $checkout_title,
						'description'           => $checkout_desc,
						'logo'                  => $logo,
					);
					update_option( 'woocommerce_' . $payment_integrations_type . '_settings', $default_settings );
				}
			}

			$existing_paymob_gateway = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}paymob_gateways WHERE gateway_id = %s", 'paymob' ), OBJECT );
			$integration_ids         = implode( ', ', array_unique( $ids ) ); // Combine unique IDs.

			if ( empty( $existing_paymob_gateway ) ) {
				// Insert Paymob gateway only if it does not exist.
				$wpdb->insert(
					$wpdb->prefix . 'paymob_gateways',
					array(
						'gateway_id'           => 'paymob',
						'file_name'            => 'class-gateway-paymob.php',
						'class_name'           => 'Paymob',
						'checkout_title'       => 'Pay with Paymob',
						'checkout_description' => 'Pay with Paymob',
						'integration_id'       => $integration_ids,
						'is_manual'            => '0',
						'ordering'             => 30,
					)
				);
			} elseif ( $existing_paymob_gateway[0]->integration_id !== $integration_ids ) {
					$wpdb->update(
						$wpdb->prefix . 'paymob_gateways',
						array(
							'integration_id' => $integration_ids,
							'ordering'       => 30,
						),
						array( 'gateway_id' => 'paymob' )
					);
			}
		}

		// Update the paymob_gateway_order option according to the ordering.
		self::update_paymob_gateway_order();
	}
	/**
	 * Updates the paymob_gateway_order option with the sorted gateway IDs.
	 */
	public static function update_paymob_gateway_order() {
		global $wpdb;
		// Fetch all gateways sorted by their ordering value.
		$gateways = $wpdb->get_results( "SELECT gateway_id FROM {$wpdb->prefix}paymob_gateways ORDER BY ordering ASC", ARRAY_A );
		// Extract gateway IDs.
		$gateway_order = array_column( $gateways, 'gateway_id' );
		// Update the paymob_gateway_order option.
		update_option( 'paymob_gateway_order', $gateway_order );
	}
	/**
	 * Checks if the given integration ID is enabled for the current currency.
	 *
	 * @param int    $id       The integration ID to check.
	 * @param string $currency The currency to check against.
	 *
	 * @return bool True if the integration ID is enabled, false otherwise.
	 */
	public static function check_enabled( $id, $currency ) {
		$options                 = get_option( 'woocommerce_paymob_settings', array() );
		$selected_integration_id = isset( $options['integration_id'] ) ? $options['integration_id'] : '';
		if ( ! empty( $selected_integration_id ) ) {
			foreach ( $selected_integration_id as $integration_id ) {
				if ( $id == $integration_id && get_woocommerce_currency() === $currency ) {
					return true;
				}
			}
		}
		// If none of the IDs match, return false
		return false;
	}
	/**
	 * Returns an array of integration IDs.
	 *
	 * @return array
	 */
	public static function get_integration_ids() {
		$integration_ids = array();
		if ( ( Paymob::filterVar( 'section' ) ) && Paymob::filterVar( 'section' ) !== 'paymob' ) {
			$integration_ids = array(
				'' => __( 'Select an Integration ID', 'paymob-woocommerce' ),
			);
		}
		$paymob_options = get_option( 'woocommerce_paymob_settings' );
		if ( isset( $paymob_options['integration_id_hidden'] ) && ! empty( $paymob_options['integration_id_hidden'] ) ) {
			$integration_id_hidden = explode( ',', $paymob_options['integration_id_hidden'] );
			foreach ( $integration_id_hidden as $entry ) {
				$parts = explode( ':', $entry );
				// Check if parts are set correctly.
				if ( count( $parts ) < 3 ) {
					continue; // Skip this entry if it doesn't have enough parts.
				}
				$id = trim( $parts[0] );
				if ( ( Paymob::filterVar( 'section' ) ) && 'paymob' === Paymob::filterVar( 'section' ) ) {
					if ( ! empty( trim( $entry ) ) ) {
						list($id, $label)               = explode( ' : ', trim( $entry ), 2 );
						$integration_ids[ trim( $id ) ] = trim( $id . ' : ' . $label );
					}
				} else {
					$integration_ids[ $id ] = $id;
				}
			}
		}
		return $integration_ids;
	}
	/**
	 * Returns the count of enabled gateways and updates the Paymob settings with the new title.
	 *
	 * @param array $gateways The array of gateways.
	 *
	 * @return bool True if the option is updated, false otherwise.
	 */
	public static function enabled_gateways_count( $gateways ) {
		$title = null;
		foreach ( $gateways as $gateway ) {
			$options = get_option( 'woocommerce_' . $gateway->gateway_id . '_settings', array() );
			if ( 'yes' === $options['enabled'] && isset( $options['title'] ) ) {
				$title .= ucwords( $options['title'] ) . ', ';
			}
		}

		if ( empty( $title ) ) {
			$title = 'At least one Payment Method should be enabled below Paymob.';
		} else {
			$title = 'Payment Methods (' . substr( $title, 0, -2 ) . ')';
		}
		$paymob_settings = get_option( 'woocommerce_paymob-main_settings', array() );
		// Merge the new title with the existing settings.
		$paymob_settings['title'] = $title;
		// Update the Paymob settings with the new title.
		return update_option( 'woocommerce_paymob-main_settings', $paymob_settings );
	}
	/**
	 * Retrieves the gateways data from the database.
	 *
	 * @return array The gateways data.
	 */
	public static function get_db_gateways_data() {
		global $wpdb;
		$gateways = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'paymob_gateways', OBJECT );
		return $gateways;
	}
	/**
	 * Disables the single integration ID field based on the gateway settings.
	 *
	 * @return array The field settings.
	 */
	public static function disable_single_integration_id_field() {
		global $current_section, $wpdb;

		// Fetch the result.
		$existing_paymob_gateway = $wpdb->get_results(
			$wpdb->prepare( "SELECT is_manual FROM {$wpdb->prefix}paymob_gateways WHERE gateway_id = %s", $current_section ),
			OBJECT
		);

		// Check if the result is not empty and determine the type and custom attributes.
		if ( isset( $existing_paymob_gateway[0]->is_manual ) && ! empty( $existing_paymob_gateway ) && '0' === $existing_paymob_gateway[0]->is_manual ) {
			$title             = '';
			$type              = 'hidden';
			$custom_attributes = array( 'required' => 'required' );
		} else {
			$title             = __( 'Paymob Integration ID', 'paymob-woocommerce' );
			$type              = 'select';
			$custom_attributes = array( 'required' => 'required' );
		}

		// Return an array with the type and custom attributes.
		return array(
			'title'             => $title,
			'type'              => $type,
			'custom_attributes' => $custom_attributes,
		);
	}
	/**
	 * Registers the framework with Paymob.
	 *
	 * @param array $ids The IDs of the integrations to register.
	 */
	public static function register_framework( $ids ) {
		try {
			$paymob_options = get_option( 'woocommerce_paymob-main_settings' );
			$debug          = isset( $paymob_options['debug'] ) ? sanitize_text_field( $paymob_options['debug'] ) : '0';

				$debug   = 'yes' === $debug ? '1' : '0';
				$sec_key = isset( $paymob_options['sec_key'] ) ? $paymob_options['sec_key'] : '';
				$add_log = WC_LOG_DIR . 'paymob.log';
				// Make sure all IDs are unique.
				$unique_ids = array_unique( $ids );
				$paymob_req = new Paymob( $debug, WC_LOG_DIR . 'paymob.log' );
				$data       = array(
					'url'                   => get_site_url(),
					'is_ssl'                => is_ssl(),
					'platform'              => 'WORDPRESS',
					'platform_version'      => WC_VERSION,
					'plugin_version'        => PAYMOB_VERSION,
					'selected_integrations' => $unique_ids, // Use the unique IDs.
					'info'                  => array(),
				);
				Paymob::addLogs( $debug, $add_log, 'Register Framework Request Data ', $data );
				$paymob_req->registerFramework( $sec_key, $data );
		} catch ( \Exception $e ) {
			WC_Admin_Settings::add_error( $e->getMessage() );
		}
	}
	/**
	 * Customize the Gateways Method Title.
	 */
	public static function gateways_method_title( $method_title, $setting, $single_integration_id = null ) {
		if ( ! empty( $single_integration_id ) ) {
			echo '<h2>' . esc_html__( 'Edit Payment Method - ', 'paymob-woocommerce' ) . esc_html( $method_title ) . ' ( ' . esc_html( $single_integration_id ) . ' ) ' .
			'<small class="wc-admin-breadcrumb"><a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '" aria-label="' . esc_attr__( 'Return to payments', 'paymob-woocommerce' ) . '">⤴︎</a></small></h2>';
		} else {
			echo '<h2>' . esc_html__( 'Edit Payment Method - ', 'paymob-woocommerce' ) . esc_html( $method_title ) .
			'<small class="wc-admin-breadcrumb"><a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '" aria-label="' . esc_attr__( 'Return to payments', 'paymob-woocommerce' ) . '">⤴︎</a></small></h2>';
		}
		echo '<table class="form-table">';
		$setting->generate_settings_html();
		echo '</table>';
	}
}
