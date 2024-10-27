<?php
return '<div style="width:60%"><div id="config-note-accordion">
 <h3>' . __( 'Step 1: Register with Paymob', 'paymob-woocommerce' ) . '</h3>
 <div>
     <p>' . __( 'Before beginning the configuration, you must have a Paymob Account. Please register or login to your account using the below link:', 'paymob-woocommerce' ) . '</p>
     <ol>
         <li><a href="https://accept.paymob.com/portal2/en/register" target="_blank">Egypt</a></li>
         <li><a href="https://uae.paymob.com/portal2/en/register" target="_blank">UAE</a></li>
         <li><a href="https://oman.paymob.com/portal2/en/register" target="_blank">Oman</a></li>
         <li><a href="https://ksa.paymob.com/portal2/en/register" target="_blank">KSA</a></li>
         <li><a href="https://pakistan.paymob.com/portal2/en/register" target="_blank">Pakistan</a></li>
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
</div></div>';
