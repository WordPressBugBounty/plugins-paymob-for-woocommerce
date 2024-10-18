<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return array(
	'config_note'       => array(
		'title'       => __( 'Main Configuration', 'paymob-woocommerce' ),
		'description' => '
		<div style="width:60%"><div id="config-note-accordion">
				<h3>' . __( 'Step 1: Register with Paymob', 'paymob-woocommerce' ) . '</h3>
				<div>
					<p>' . __( 'Before beginning the configuration, you must have a Paymob Account. Please register or login to your account using the below link:', 'paymob-woocommerce' ) . '</p>
					<ol>
						<li><a href="https://accept.paymob.com/portal2/en/register?accept_sales_owner=WooCommerce" target="_blank">Egypt</a></li>
						<li><a href="https://uae.paymob.com/portal2/en/register?accept_sales_owner=WooCommerce" target="_blank">UAE</a></li>
						<li><a href="https://oman.paymob.com/portal2/en/register?accept_sales_owner=WooCommerce" target="_blank">Oman</a></li>
						<li><a href="https://ksa.paymob.com/portal2/en/register?accept_sales_owner=WooCommerce" target="_blank">KSA</a></li>
						<li><a href="https://pakistan.paymob.com/portal2/en/register?accept_sales_owner=WooCommerce" target="_blank">Pakistan</a></li>
					</ol>
					<p>' . __( 'Once registered or logged in, you will gain access to the Test Mode Environment on the Merchant Dashboard.', 'paymob-woocommerce' ) . '</p>
				</div>

				<h3>' . __( 'Step 2: Contact Paymob Support', 'paymob-woocommerce' ) . '</h3>
				<div>
					<p>' . __( 'Email Paymob at <a href="mailto:support@paymob.com">support@paymob.com</a> to get assistance from the Sales Team for further onboarding.', 'paymob-woocommerce' ) . '</p>
				</div>

				<h3>' . __( 'Step 3: Key Configurations', 'paymob-woocommerce' ) . '</h3>
				<div>
					<p>' . __( 'Your dashboard has Test Mode and Live Mode options. Live Mode will be activated only when you have at least one live payment method integration.', 'paymob-woocommerce' ) . '</p>
					<ol>
						<li>' . __( 'Test Mode: Use this to perform test transactions.', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Live Mode: Use this for live transactions.', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'How to Access the Keys:', 'paymob-woocommerce' ) . '</li>
						<ol>
							<li>' . __( 'Log in to the Merchant Dashboard.', 'paymob-woocommerce' ) . '</li>
							<li>' . __( 'Click on the "Settings" tab and navigate to the "Account Info" section.', 'paymob-woocommerce' ) . '</li>
							<li>' . __( 'Click the "view" button next to each key (API Key, Public Key, Secret Key) to reveal them.', 'paymob-woocommerce' ) . '</li>
							<li>' . __( 'Copy and paste these keys into the Configuration Page.', 'paymob-woocommerce' ) . '</li>
						</ol>
					</ol>
					<p>' . __( 'Note: API Key, Public Key, and Secret Key differ between Test and Live Modes. Always use LIVE Keys for live transactions and TEST Keys for test transactions.', 'paymob-woocommerce' ) . '</p>
				</div>
			</div></div>',
		'type'        => 'title',
	),
	'api_key'           => array(
		'title'             => __( 'API Key', 'paymob-woocommerce' ),
		'type'              => 'text',
		'sanitize_callback' => 'sanitize_text_field',
		'custom_attributes' => array( 'required' => 'required' ),
	),
	'sec_key'           => array(
		'title'             => __( 'Secret Key', 'paymob-woocommerce' ),
		'type'              => 'text',
		'sanitize_callback' => 'sanitize_text_field',
		'custom_attributes' => array( 'required' => 'required' ),
	),
	'pub_key'           => array(
		'title'             => __( 'Public Key', 'paymob-woocommerce' ),
		'type'              => 'text',
		'sanitize_callback' => 'sanitize_text_field',
		'custom_attributes' => array( 'required' => 'required' ),
	),
	'callback_note'     => array(
		'title'       => '',
		'description' => '
			<div style="width:60%"><div id="callback-accordion">
				<h3>' . __( 'Step 4: Configure Callback URL', 'paymob-woocommerce' ) . '</h3>
				<div>
					<ol>
						<li>' . __( 'Click the icon <span style="cursor:pointer;" id="cpicon" class="dashicons dashicons-clipboard"></span> to copy the callback URL.', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Log in to the Paymob Merchant Dashboard.', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Go to the "Developers" section and select "Payment Integrations."', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Click on the ID of each payment method integration, select "Edit," and paste the URL into both the "Transaction Processed Callback" and "Transaction Response Callback" fields.', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Click "Submit."', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Repeat these steps for each payment integration. If you add new payment methods in the future, ensure you update the URL accordingly.', 'paymob-woocommerce' ) . '</li>
					</ol>
				</div>			
			</div></div>',
		'type'        => 'title',
	),
	'callback'          => array(
		'title'       => __( 'Integration Callback', 'paymob-woocommerce' ),
		'label'       => '<span id="cburl" class="button-secondary callback_copy">' . add_query_arg( array( 'wc-api' => 'paymob_callback' ), home_url() ) . '</span>',
		'description' => '',
		'css'         => 'display:none',
		'type'        => 'checkbox',
	),
	'has_items_note'    => array(
		'title'       => '',
		'description' => '
			<div style="width:60%"><div id="has-items-accordion">
				<h3>' . __( 'Step 5: Show Item/Product Details on Paymob Checkout ( Optional )', 'paymob-woocommerce' ) . '</h3>
				<div>
					<ol>
						<li>' . __( 'Enable the checkbox in this section.', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Log in to the Paymob Merchant Dashboard.', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Navigate to "Checkout Customization" → "Payment Methods."', 'paymob-woocommerce' ) . '</li>
						<li>' . __( 'Under the "Additional Information" section, enable the "Show Item/Product" option and click "Apply Changes."', 'paymob-woocommerce' ) . '</li>
					</ol>
				</div>			
			</div></div>',
		'type'        => 'title',
	),
	'has_items'         => array(
		'title'   => __( 'Pass Item Data', 'paymob-woocommerce' ),
		'label'   => ' ',
		'type'    => 'checkbox',
		'default' => 'no',
	),
	'extra_note'        => array(
		'title'       => '',
		'description' => '
			<div style="width:60%"><div id="extra-accordion">
				<h3>' . __( 'Step 6 - Miscellaneous ( Optional )', 'paymob-woocommerce' ) . '</h3>
				<div>
					<ul>
						<li>' . __( 'Enabling the Debug Log checkbox in this section will log all actions in Paymob files. These files will be saved in the directory', 'paymob-woocommerce' ) . ' <b>' . ( defined( 'WC_LOG_DIR' ) ? WC_LOG_DIR : WC()->plugin_path() . '/logs/' ) . '</b></li>

						<li>' . __( 'Enabling the Empty cart items checkbox in this section will clear the cart items before completing the payment. (not recommended).', 'paymob-woocommerce' ) . '</li> 
					</ul>
				</div>			
			</div>
		</div>',
		'type'        => 'title',
	),
	'debug'             => array(
		'title'   => __( 'Debug Log', 'paymob-woocommerce' ),
		'label'   => __( 'Enable debug log', 'paymob-woocommerce' ),
		'type'    => 'checkbox',
		'default' => 'yes',
	),
	'empty_cart'        => array(
		'title'   => __( 'Empty cart items', 'paymob-woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable empty cart items', 'paymob-woocommerce' ),
		'default' => 'no',
	),
	'save_changes_note' => array(
		'title'       => '',
		'description' => '
			<div style="width:60%"><div id="save-changes-accordion">
				<h3>' . __( 'Step 7: Save Changes', 'paymob-woocommerce' ) . '</h3>
				<div>
					<p>' . __( 'Click "Save Changes." After saving, you will be redirected to the "Payment Integrations Page," where you can view all the payment methods integrated with Paymob.', 'paymob-woocommerce' ) . '</p>
				</div>				
			</div></div>',
		'type'        => 'title',
	),
);
