<?php

return '<div style="width:60%"><div id="extra-accordion">
            <h3>' . __( 'Step 5: Miscellaneous ( Optional )', 'paymob-woocommerce' ) . '</h3>
            <div>
                <ul>
                    <li>' . __( 'Enabling the Debug Log checkbox in this section will log all actions in Paymob files. These files will be saved in the directory', 'paymob-woocommerce' ) . ' <b>' . ( defined( 'WC_LOG_DIR' ) ? WC_LOG_DIR : WC()->plugin_path() . '/logs/' ) . '</b></li>

                    <li>' . __( 'Enabling the Empty cart items checkbox in this section will clear the cart items before completing the payment. (not recommended).', 'paymob-woocommerce' ) . '</li> 
                </ul>
            </div>			
        </div>
        </div>';
