<?php
return '<div style="width:60%"><div id="config-note-accordion">
 <h3>' . __( 'Step 1: Register with Paymob', 'paymob-woocommerce' ) . '</h3>
 <div>
     <ol><li>  <a href="https://onboarding.paymob.com/?partner=woocommerce" target="_blank">' . __( 'Click here', 'paymob-woocommerce' ) . '</a>
     ' . __( ' to register, upload your business documents and select the payment methods you wish to integrate with .Process has few steps, and you can always resume from where you left.', 'paymob-woocommerce' ) . '</li>
     <li>' . __( 'After you complete all the steps , Document verification will take up to 3 days and then you can start accepting payments.
   If there are any issues with documentation, our sales representative will reach out to assist you.', 'paymob-woocommerce' ) . '</li>
   <li>' . __( 'After 3 days, please ', 'paymob-woocommerce' ) . ' <a href="https://onboarding.paymob.com/?partner=woocommerce" target="_blank">' . __( 'click here ', 'paymob-woocommerce' ) . '</a>
     ' . __( ' to check if your documents have been approved. If you encounter any issues, please email us at support@paymob.com for assistance.', 'paymob-woocommerce' ) . '</li>
     <li>' . __( 'While waiting for verification to complete, you can follow the installation steps provided below.', 'paymob-woocommerce' ) . '</li>    
     <li>' . __( 'Once verification is complete, please refer to the “Main Configuration”, starting from Step 2 to configure the plugin.', 'paymob-woocommerce' ) . '</li>
     
 </div>
 <h3>' . __( 'Step 2: Key Configurations', 'paymob-woocommerce' ) . '</h3>
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
</div></div>';
