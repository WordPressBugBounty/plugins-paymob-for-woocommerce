<?php
/**
 * Paymob Redirect Url
 */
class WC_Paymob_RedirectUrl
{
		// Check the redirect flag and perform redirect if true
        public static function redirect_after_activation() {
            $gatewayData = get_option('woocommerce_paymob_gateway_data');
            $mainOptions = get_option('woocommerce_paymob-main_settings');
            if (empty($gatewayData) && empty($mainOptions)) {
                // Check and delete the flag to prevent future redirects
                if ( get_option( 'paymob_activation_redirect', false ) ) {
                    delete_option( 'paymob_activation_redirect' );
                    $url= WC_Paymob_RedirectUrl::get_current_url();
                    $encoded_url=urlencode($url);
                    // Replace with your desired custom URL
                    wp_redirect( 'https://onboarding.paymob.com/auth/country-selection?partner=woocommerce&redirect_url='.$encoded_url );
                    exit;
                }
            }
            
           
        }
    
       public static function get_current_url() {
            // Get the protocol
            $protocol = is_ssl() ? 'https://' : 'http://';
        
            // Get the host and request URI
            $host = $_SERVER['HTTP_HOST'];
            $request_uri = $_SERVER['REQUEST_URI'];
        
            // Combine to form the full URL
            return $protocol . $host . $request_uri;
        }
}
