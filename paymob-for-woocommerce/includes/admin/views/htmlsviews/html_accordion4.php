<?php

return '<div style="width:60%"><div id="callback-accordion">
            <h3>' . __( 'Step 3: Configure Callback URL', 'paymob-woocommerce' ) . '</h3>
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
        </div></div>';
