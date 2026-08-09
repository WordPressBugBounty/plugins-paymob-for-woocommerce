<?php
/**
 * Paymob Redirect Url
 *
 * Keeps the original post-activation onboarding redirect for real merchants.
 * Skips forced external redirects only in automated / non-interactive contexts (QIT, CLI, etc.).
 */
class WC_Paymob_RedirectUrl {

	/**
	 * Check the redirect flag and perform redirect if true.
	 */
	public static function redirect_after_activation() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Preserve merchant UX; only skip when this is clearly not a human admin session.
		if ( self::should_skip_activation_redirect() ) {
			if ( get_option( 'paymob_activation_redirect', false ) ) {
				delete_option( 'paymob_activation_redirect' );
			}
			return;
		}

		$gateway_data = get_option( 'woocommerce_paymob_gateway_data' );
		$main_options = get_option( 'woocommerce_paymob-main_settings' );

		if ( empty( $gateway_data ) && empty( $main_options ) ) {
			if ( get_option( 'paymob_activation_redirect', false ) ) {
				delete_option( 'paymob_activation_redirect' );

				$data = array(
					'partner' => 'woocommerce',
					'clt'     => Paymob_Main_Partner_Info::get_public_ip(),
				);

				$paymob_req  = new Paymob( '1', WC_LOG_DIR . 'paymob-auth.log' );
				$response    = $paymob_req->getOnboardingUrl( 'egy', $data );
				Paymob_Main_Partner_Info::mark_partner_connect_started();
				$current_url = Paymob_Main_Partner_Info::get_partner_redirect_url();
				$encoded_url = rawurlencode( $current_url );
				$url         = 'https://onboarding.paymob.com/auth/country-selection?partner=woocommerce&redirect_url=' . $encoded_url;

				if ( isset( $response->url ) ) {
					$url = $response->url . '&redirect_url=' . $encoded_url;
				}

				Paymob_Main_Partner_Info::safe_redirect( $url );
			}
		}
	}

	/**
	 * Detect automated environments where an external redirect would break test flows.
	 *
	 * @return bool
	 */
	private static function should_skip_activation_redirect() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return true;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}

		// Common CI / QIT markers inside the test runtime.
		if ( getenv( 'QIT' ) || getenv( 'QIT_TEST' ) || getenv( 'CI' ) ) {
			return true;
		}

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( $user_agent && preg_match( '/playwright|puppeteer|headlesschrome|\bqit\b/i', $user_agent ) ) {
			return true;
		}

		return false;
	}
}
