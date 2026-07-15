<?php

class Paymob {


	public $debug_order;
	public $file;

	public function __construct( $debug_order = false, $file = null ) {
		$this->debug_order = $debug_order;
		$this->file        = $file;
	}

	public function HttpRequest( $apiPath, $method, $header = array(), $data = array() ) {
		if ( ! in_array( 'curl', get_loaded_extensions() ) ) {
			throw new Exception( 'Curl extension is not loaded on your server, please check with server admin. Then try again!' );
		}
		$agent=self::filterVar('HTTP_USER_AGENT','SERVER');
		ini_set( 'precision', 14 );
		ini_set( 'serialize_precision', -1 );
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $apiPath );
		if ( 'GET' == $method ) {
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'GET' );
		}elseif('PUT' == $method)
		{
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');  // Correctly set PUT method
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));  // Set data as JSON
		} else {
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $data ) );
		}
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($curl, CURLOPT_USERAGENT, $agent);

		$response = curl_exec( $curl );
		$http_code = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		if ( false === $response ) {
			$curl_error = curl_error( $curl );
			curl_close( $curl );
			if ( class_exists( 'Paymob_Error_Logs' ) ) {
				Paymob_Error_Logs::log_http_raw_response(
					'cURL failed: ' . $curl_error,
					(string) $apiPath,
					(string) $method,
					0,
					''
				);
			}
			throw new Exception( 'Curl error: ' . $curl_error );
		}

		curl_close( $curl );

		$raw_body = (string) $response;

		$decoded  = json_decode( $raw_body, false );
		$json_err = json_last_error();

		if ( JSON_ERROR_NONE !== $json_err ) {
			if ( class_exists( 'Paymob_Error_Logs' ) ) {
				Paymob_Error_Logs::log_http_raw_response(
					'JSON decode failed: ' . json_last_error_msg() . ' — raw body stored below.',
					(string) $apiPath,
					(string) $method,
					$http_code,
					$raw_body
				);
			}
			return null;
		}

		if ( $http_code >= 400 ) {
			if ( class_exists( 'Paymob_Error_Logs' ) ) {
				Paymob_Error_Logs::log_http_raw_response(
					'HTTP ' . $http_code . ' — raw Paymob body stored below.',
					(string) $apiPath,
					(string) $method,
					$http_code,
					$raw_body
				);
			}
		}

		return $decoded;
	}

	public function authToken( $conf ) {
		$this->matchCountries( $conf );
		$this->addLogs( $this->debug_order, $this->file, ' Authenticate Paymob configuration' );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $conf['secKey'] ) );
		$tokenRes = $this->HttpRequest( $apiUrl . 'api/auth/tokens', 'POST', array( 'Content-Type: application/json' ), array( 'api_key' => $conf['apiKey'] ) );
		
		$this->addLogs( $this->debug_order, $this->file, ' In api/auth/tokens Response: ' . json_encode( $tokenRes ) );

		if ( isset( $tokenRes->token ) ) {
			$hmacRes     = $this->getHmac( $tokenRes->token, $apiUrl );
			$integIDsRes = $this->getIntegrationIDs( $tokenRes->token, $apiUrl, $this->matchMode( $conf ) );
			$data        = array(
				'hmac'           => $hmacRes,
				'integrationIDs' => $integIDsRes,
				'token'=>$tokenRes->token,
			);

			return $data;
		} else {
			throw new Exception( 'Cannot get Token from PayMob account' );
		}
	}

	public function getHmac( $token, $apiUrl ) {
		$hmacRes = $this->HttpRequest( $apiUrl . 'api/auth/hmac_secret/get_hmac', 'GET', array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token ) );
		$this->addLogs( $this->debug_order, $this->file, ' In api/auth/hmac_secret/get_hmac Response: ' . json_encode( $hmacRes ) );
		if ( isset( $hmacRes->hmac_secret ) ) {
			return $hmacRes->hmac_secret;
		} else {
			throw new Exception( 'Cannot get HMAC from PayMob account' );
		}
	}

	public function getIntegrationIDs( $token, $apiUrl, $isTest = false ) {
		$intRes = $this->HttpRequest( $apiUrl . 'api/ecommerce/integrations?is_plugin=true&is_next=yes&page_size=500&is_deprecated=false&is_standalone=false&is_shopify=false', 'GET', array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token ) );
		$this->addLogs( $this->debug_order, $this->file, ' In api/ecommerce/integrations Response: ' . json_encode( $intRes ) );

		if ( ! empty( $intRes ) ) {
			$IntegrationIDs = array();
			foreach ( $intRes->results as $key => $integration ) {
				$type = $integration->gateway_type;
				// var_dump($integration);
				if ( 'VPC' == $type ) {
					$type = 'Card';
				} elseif ( 'CAGG' == $type ) {
					$type = 'Aman';
				} elseif ( 'UIG' == $type ) {
					$type = 'Wallet';
				}
				if($integration->integration_type =="moto"){
					$is_moto="yes";
				}
				else{
					$is_moto="no";
				}

				if (
					($integration->gateway_type == 'VPC' ||$integration->gateway_type == 'MIGS' ) &&
					$integration->integration_type == 'online' &&
					$integration->is_auth == false&&
					$integration->installments==null
				) {
					$is_3DS = "yes";
				} else {
					$is_3DS = "no";
				}
				if ( false == $integration->is_standalone ) {
					if($integration->is_live==false){
						$mode='test';
					}
					else
					{
						$mode='live';
					}

					$IntegrationIDs[ $integration->id ] = array(
						'id'           => $integration->id,
						'type'         => $type,
						'gateway_type' => $integration->gateway_type,
						'name'         => empty( $integration->installments ) ? $integration->integration_name : 'bank-installments',
						'currency'     => $integration->currency,
						'mode'         => $mode,
						'is_moto'      =>$is_moto,
						'is_3DS'       =>$is_3DS

					);
				}
				
			}
			// die;
			return $IntegrationIDs;
		} else {
			throw new Exception( 'Cannot get available integration IDs from PayMob account' );
		}
	}

	public function createIntention( $secKey, $data, $orderId ,$cs,$method) {
		$flash  = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$header = array( 'Content-Type: application/json', 'Authorization: Token ' . $secKey );
		$this->addLogs( $this->debug_order, $this->file, print_r( $data, 1 ) );
		$intention = $this->HttpRequest( $flash . 'v1/intention/'.$cs, $method, $header, $data );
		$text= (!empty($cs)) ? 'Update ' : '';
		$note_i    = $text.'Intention response for order # ' . $orderId;
		$this->addLogs( $this->debug_order, $this->file, $note_i, print_r( $intention, 1 ) );
		if ( empty( $intention->payment_keys ) ) {
			$this->addLogs( $this->debug_order, $this->file, $note_i, $intention );
		}
		$status = array(
			'cs'      => null,
			'success' => false,
		);

		if ( isset( $intention->amount_cents ) ) {
			$status['message'] = $intention->amount_cents;
			return $status;
		}
		if ( isset( $intention->detail ) ) {
			$status['message'] = $intention->detail;
			return $status;
		}
		if ( isset( $intention->amount ) ) {
			$status['message'] = $intention->amount[0];
			return $status;
		}
		if ( isset( $intention->billing_data ) ) {
			$status['message'] = 'Ops, there is missing billing information!';
			return $status;
		}

		if ( isset( $intention->integrations ) ) {
			$status['message'] = $intention->integrations[0];
			return $status;
		}

		if ( isset( $intention->client_secret ) ) {
			$status['success']     = true;
			$status['cs']          = $intention->client_secret;
			$status['intentionId'] = $intention->id;
			$status['centsAmount'] = $intention->intention_detail->amount;
			if(empty($cs)){
				$status['intention_order_id'] = $intention->intention_order_id;
			}
		} else {
			$status['message'] = ( isset( $intention->code ) ) ? $intention->code : 'Something went wrong';
		}
		$this->addLogs( $this->debug_order, $this->file, $note_i, json_encode( $status ) );
		return $status;
	}
	/**
	 * Get a list of Paymob Gateways, their Logos, and names.
	 *
	 * @return array of Paymob data
	 */
	public function getPaymobGateways( $secKey, $path, $token = '' ) {
		// get gateways data with Bearer token auth (same auth flow as other endpoints)
		$flash = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$header = array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $token,
		);

		$getways = $this->HttpRequest( $flash . 'api/ecommerce/gateways', 'GET', $header );
		$this->addLogs( $this->debug_order, $this->file, 'In api/ecommerce/gateways Response: ', json_encode( $getways ) );
		// Handle invalid or empty responses
		if ( is_null( $getways ) || ! isset( $getways->result ) ) {
			$this->addLogs( $this->debug_order, $this->file, 'In api/ecommerce/gateways Response: Cannot get Gateways Data from PayMob account.' );
			throw new Exception( 'Cannot get Gateways Data from PayMob account' );
		}

		// Process the gateways data if available
		$gateways = json_decode( json_encode( $getways->result, true ), true );
		return $this->extractGatewaysData( $gateways, $path );
	}

	private static function sanitize_gateway_code( $code ) {
		$code = strtolower( (string) $code );
		return preg_replace( '/[^a-z0-9_-]/', '', $code );
	}

	private static function is_allowed_paymob_logo_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return false;
		}

		return (bool) preg_match( '/(^|\.)paymob\.com$/', strtolower( $host ) );
	}

	private static function is_valid_logo_image( $data ) {
		if ( empty( $data ) || ! function_exists( 'getimagesizefromstring' ) ) {
			return false;
		}

		$info = @getimagesizefromstring( $data );
		if ( false === $info ) {
			return false;
		}

		return in_array( $info[2], array( IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP ), true );
	}

	private static function get_safe_gateway_logo_path( $assets_dir, $code ) {
		$assets_dir = trailingslashit( wp_normalize_path( $assets_dir ) );
		$logo_path  = wp_normalize_path( $assets_dir . $code . '.png' );

		if ( 0 !== strpos( $logo_path, $assets_dir ) ) {
			return '';
		}

		$real_assets = realpath( $assets_dir );
		if ( false !== $real_assets ) {
			$parent_dir = dirname( $logo_path );
			if ( ! is_dir( $parent_dir ) ) {
				wp_mkdir_p( $parent_dir );
			}
			$real_parent = realpath( $parent_dir );
			if ( false === $real_parent || 0 !== strpos( wp_normalize_path( $real_parent ), wp_normalize_path( $real_assets ) ) ) {
				return '';
			}
		}

		return $logo_path;
	}

	private static function download_gateway_logo( $logo_url, $logo_path ) {
		if ( empty( $logo_url ) || empty( $logo_path ) || file_exists( $logo_path ) ) {
			return;
		}

		$response = wp_safe_remote_get(
			$logo_url,
			array(
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return;
		}

		$data = wp_remote_retrieve_body( $response );
		if ( ! self::is_valid_logo_image( $data ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing validated image bytes to plugin assets cache.
		file_put_contents( $logo_path, $data );
	}

	public static function extractGatewaysData( $gateways, $path ) {
		$gatewaysData = array();

		// Check if $gateways is an array or object before processing
		if ( ! is_array( $gateways ) && ! is_object( $gateways ) ) {
			return $gatewaysData; // Return empty data if $gateways is not valid
		}

		foreach ( $gateways as $gateway ) {
			$code = self::sanitize_gateway_code( isset( $gateway['code'] ) ? $gateway['code'] : '' );
			if ( '' === $code ) {
				continue;
			}

			$logoPath = self::get_safe_gateway_logo_path( $path, $code );
			$logo_url = '';

			if ( ! empty( $gateway['logo'] ) ) {
				$logo_url = esc_url_raw( $gateway['logo'], array( 'https' ) );
				if ( $logo_url && self::is_allowed_paymob_logo_url( $logo_url ) ) {
					self::download_gateway_logo( $logo_url, $logoPath );
				} else {
					$logoPath = '';
				}
			} else {
				$logoPath = '';
			}

			$gatewaysData[ $code ] = array(
				'title' => isset( $gateway['label'] ) ? $gateway['label'] : '',
				'desc'  => isset( $gateway['description'] ) ? $gateway['description'] : '',
				'logo'  => $logo_url ? $logo_url : ( isset( $gateway['logo'] ) ? $gateway['logo'] : '' ),
			);
		}

		return $gatewaysData;
	}
	public function registerFramework( $secKey, $data ) {
		$flash       = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$header      = array( 'Content-Type: application/json', 'Authorization: Token ' . $secKey );
		$registerRes = $this->HttpRequest( $flash . 'api/ecommerce/plugins', 'POST', $header, $data );
		$this->addLogs( $this->debug_order, $this->file, ' In api/ecommerce/plugins: ' . json_encode( $registerRes ) );
		return $registerRes;
	}
	public function refundPayment( $secKey, $data ) {
		$flash  = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$header = array( 'Content-Type: application/json', 'Authorization: Token ' . $secKey );
		$this->addLogs( $this->debug_order, $this->file, print_r( $data, 1 ) );
		$refundRes = $this->HttpRequest( $flash . 'api/acceptance/void_refund/refund', 'POST', $header, $data );
		$this->addLogs( $this->debug_order, $this->file, ' In api/acceptance/void_refund/refund: ' . json_encode( $refundRes ) );

		if ( isset( $refundRes->detail ) ) {
			$status['message'] = $refundRes->detail;
			return $status;
		}
		if ( isset( $refundRes->message ) ) {
			$status['message'] = $refundRes->message;
			return $status;
		}
		if ( isset( $refundRes->amount_cents[0] ) ) {
			$status['message'] = $refundRes->amount_cents[0] ;
			return $status;
		} 
		if ( isset( $refundRes->success ) ) {
			$status['success']   = true;
			$status['refund_id'] = $refundRes->id;
		} else {
			$status['success'] = false;
			$status['message'] = 'Something went wrong';
		}
		return $status;
	}
	public function getOnboardingUrl( $code, $data ) {
		$flash       = $this->getApiUrl($code);
		$header = array( 'Content-Type: application/json');
		$this->addLogs( $this->debug_order, $this->file, print_r( $data, 1 ) );
		$onboardingRes = $this->HttpRequest( $flash . 'api/onboarding/partners-utils/country_url', 'POST', $header, $data );
		$this->addLogs( $this->debug_order, $this->file, ' In api/onboarding/partners-utils/country_url: ' . json_encode( $onboardingRes ) );
		return $onboardingRes;
	}
	public function getPartnerInfo( $woo_code, $data ) {
		// return $this->getCountryCode( $woo_code );
		$flash       = $this->getApiUrl( strtolower($this->getCountryCode( $woo_code )) );
		$header      = array( 'Content-Type: application/json', 'Authorization:' . $woo_code );
		$this->addLogs( $this->debug_order, $this->file, print_r( $data, 1 ) );
		$partnerInfoRes = $this->HttpRequest( $flash . 'api/onboarding/partners-utils/merchant_info', 'POST', $header, $data );
		$this->addLogs( $this->debug_order, $this->file, ' In api/onboarding/partners-utils/merchant_info: ' . json_encode( $partnerInfoRes ) );
		return $partnerInfoRes;
	}
	public function matchMode( $conf ) {
		$pubKeyMode = $this->getMode( $conf['pubKey'] );
		$secKeyMode = $this->getMode( $conf['secKey'] );

		if ( $secKeyMode != $pubKeyMode ) {
			throw new Exception( 'Public and Secret Keys does not belong to the ( live/test ) mode' );
		}
		return ( 'live' == $pubKeyMode );
	}

	public function matchCountries( $conf ) {
		$pubKey = $this->getCountryCode( $conf['pubKey'] );
		$secKey = $this->getCountryCode( $conf['secKey'] );
		if ( $pubKey != $secKey ) {
			throw new Exception( 'Public and Secret Keys does not belong to the same country' );
		}
		return true;
	}

	public function getMode( $code ) {
		return substr( $code, 7, 4 );
	}

	public static function getCountryCode( $code ) {
		return substr( $code, 0, 3 );
	}

	public static function getIntentionId( $merchantIntentionId ) {
		return substr( $merchantIntentionId, 0, -11 );
	}

	public static function verifyHmac( $key, $data, $intention = null, $hmac = null,$is_subscription=false ) {
		if ( isset( $hmac) && $is_subscription==true  ) {
			return self::verifysubscriptionHmac( $key, $data, $hmac );
		}elseif(isset( $hmac) && $is_subscription==false ){
			return self::verifyAcceptHmac( $key, $data, $hmac );
		} else {
			return self::verifyFlashHmac( $key, $data, $intention );
		}
	}

	public static function verifysubscriptionHmac( $key, $subscription_data, $hmac ) {
		if (empty($subscription_data['trigger_type']) || empty($subscription_data['subscription_data']['id'])) {
			return false; // Invalid input
		}

		$concatenated_string = $subscription_data['trigger_type'] . 'for' . $subscription_data['subscription_data']['id'];

		// Step 2: Calculate HMAC using SHA-512 and the secret key
		$hash = hash_hmac('sha512', $concatenated_string, $key);

		return $hash === $hmac;
	}

	public static function verifyFlashHmac( $key, $data, $intention = null ) {

		if ( empty( $intention ) ) {
			// callback GET
			$str  = $data['amount_cents']
				. $data['created_at']
				. $data['currency']
				. $data['error_occured']
				. $data['has_parent_transaction']
				. $data['id']
				. $data['integration_id']
				. $data['is_3d_secure']
				. $data['is_auth']
				. $data['is_capture']
				. $data['is_refunded']
				. $data['is_standalone_payment']
				. $data['is_voided']
				. $data['order']
				. $data['owner']
				. $data['pending']
				. $data['source_data_pan']
				. $data['source_data_sub_type']
				. $data['source_data_type']
				. $data['success'];
			$hash = hash_hmac( 'sha512', $str, $key );
		} else {
			// webhook POST
			$amount = ( $intention['amount'] / $intention['cents'] );
			if ( is_float( $amount ) ) {
				$amountArr = explode( '.', $amount );
				if ( strlen( $amountArr[1] ) == 1 ) {
					$amount = $amount . '0';
				}
			} else {
				$amount = $amount . '.00';
			}
			$str  = $amount . $intention['id'];
			$hash = hash_hmac( 'sha512', $str, $key, false );
		}

		$hmac = $data['hmac'];

		return ( $hmac === $hash );
	}

	public static function verifyAcceptHmac( $key, $json_data, $hmac ) {
		$data                           = $json_data['obj'];
		$data['order']                  = $data['order']['id'];
		$data['is_3d_secure']           = ( true === $data['is_3d_secure'] ) ? 'true' : 'false';
		$data['is_auth']                = ( true === $data['is_auth'] ) ? 'true' : 'false';
		$data['is_capture']             = ( true === $data['is_capture'] ) ? 'true' : 'false';
		$data['is_refunded']            = ( true === $data['is_refunded'] ) ? 'true' : 'false';
		$data['is_standalone_payment']  = ( true === $data['is_standalone_payment'] ) ? 'true' : 'false';
		$data['is_voided']              = ( true === $data['is_voided'] ) ? 'true' : 'false';
		$data['success']                = ( true === $data['success'] ) ? 'true' : 'false';
		$data['error_occured']          = ( true === $data['error_occured'] ) ? 'true' : 'false';
		$data['has_parent_transaction'] = ( true === $data['has_parent_transaction'] ) ? 'true' : 'false';
		$data['pending']                = ( true === $data['pending'] ) ? 'true' : 'false';
		$data['source_data_pan']        = $data['source_data']['pan'];
		$data['source_data_type']       = $data['source_data']['type'];
		$data['source_data_sub_type']   = $data['source_data']['sub_type'];

		$str  = '';
		$str  = $data['amount_cents'] .
			$data['created_at'] .
			$data['currency'] .
			$data['error_occured'] .
			$data['has_parent_transaction'] .
			$data['id'] .
			$data['integration_id'] .
			$data['is_3d_secure'] .
			$data['is_auth'] .
			$data['is_capture'] .
			$data['is_refunded'] .
			$data['is_standalone_payment'] .
			$data['is_voided'] .
			$data['order'] .
			$data['owner'] .
			$data['pending'] .
			$data['source_data_pan'] .
			$data['source_data_sub_type'] .
			$data['source_data_type'] .
			$data['success'];
		$hash = hash_hmac( 'sha512', $str, $key );
		return $hash === $hmac;
	}

	public static function getApiUrl( $countryCode ) {
		$domain = 'paymob.com/';
		if ( 'are' == $countryCode || 'uae' == $countryCode ) {
			return 'https://uae.' . $domain;
		} elseif ( 'egy' == $countryCode ) {
			return 'https://accept.' . $domain;
		} elseif ( 'pak' == $countryCode ) {
			return 'https://pakistan.' . $domain;
		} elseif ( 'ksa' == $countryCode || 'sau' == $countryCode ) {
			return 'https://ksa.' . $domain;
		} elseif ( 'omn' == $countryCode ) {
			return 'https://oman.' . $domain;
		} else {
			throw new Exception( 'Another country' );
		}
	}

	public static function getTimeZone( $country ) {
		switch ( $country ) {
			case 'omn':
				return 'Asia/Muscat';
			case 'pak':
				return 'Asia/Karachi';
			case 'ksa':
			case 'sau':
				return 'Asia/Riyadh';
			case 'are':
			case 'uae':
				return 'Asia/Dubai';
			case 'egy':
			default:
				return 'Africa/Cairo';
		}
	}

	/**
	 * Filter the GLOBAL variables
	 *
	 * @param string $name The field name the need to be filter.
	 * @param string $global value could be (GET, POST, REQUEST, COOKIE, SERVER).
	 *
	 * @return string|null
	 */
	public static function filterVar( $name, $global = 'GET' ) {
		if ( isset( $GLOBALS[ '_' . $global ][ $name ] ) ) {
			if ( is_array( $GLOBALS[ '_' . $global ][ $name ] ) ) {
				return $GLOBALS[ '_' . $global ][ $name ];
			}
			return htmlspecialchars( $GLOBALS[ '_' . $global ][ $name ], ENT_QUOTES );
		}
		return null;
	}

	public static function sanitizeVar( $type = 'GET' ) {
		return $GLOBALS[ '_' . $type ];
	}

	public static function addLogs( $debug, $file, $note, $data = false ) {
		if ( is_bool( $data ) ) {
			( '1' === $debug ) ? error_log( PHP_EOL . gmdate( 'd.m.Y h:i:s' ) . ' - ' . $note, 3, $file ) : false;
		} else {
			( '1' === $debug ) ? error_log( PHP_EOL . gmdate( 'd.m.Y h:i:s' ) . ' - ' . $note . ' -- ' . json_encode( $data ), 3, $file ) : false;
		}
	}

	public function getIntegrationID( $conf,$IntegrationID ) {
		$this->matchCountries( $conf );
		$this->addLogs( $this->debug_order, $this->file, ' Authenticate Paymob configuration' );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $conf['secKey'] ) );

		$tokenRes = $this->HttpRequest( $apiUrl . 'api/auth/tokens', 'POST', array( 'Content-Type: application/json' ), array( 'api_key' => $conf['apiKey'] ) );
		if ( isset( $tokenRes->token ) )
		{
			$IntegrationID = $this->HttpRequest( $apiUrl . 'api/ecommerce/integrations/'.$IntegrationID, 'GET', array( 'Content-Type: application/json', 'Authorization: Bearer ' . $tokenRes->token ) );
			$this->addLogs( $this->debug_order, $this->file, ' In api/ecommerce/integrations Response: ' . json_encode( $IntegrationID ) );
			if ( ! empty( $IntegrationID ) ) 
			{
				return $IntegrationID;
			}else
			{
				throw new Exception( 'Cannot get this integration ID from PayMob account' );
			}

		}
		else 
		{
			throw new Exception( 'Cannot get Token from PayMob account' );
		}
		
	}

	public function updateWebHookUrl( $conf,$IntegrationID,$data ) {
		$this->matchCountries( $conf );
		$this->addLogs( $this->debug_order, $this->file, ' Authenticate Paymob configuration' );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $conf['secKey'] ) );
		$tokenRes = $this->HttpRequest( $apiUrl . 'api/auth/tokens', 'POST', array( 'Content-Type: application/json' ), array( 'api_key' => $conf['apiKey'] ) );
		if ( isset( $tokenRes->token ) )
		{ 
			$result = $this->HttpRequest( $apiUrl . 'api/ecommerce/integrations/'.$IntegrationID, 'PUT', array( 'Content-Type: application/json', 'Authorization: Bearer ' . $tokenRes->token ),$data);
			$this->addLogs( $this->debug_order, $this->file, ' In api/ecommerce/integrations Response: ' . json_encode( $result ) );
			if ( ! empty( $result ) ) 
			{
				return $result;
			}else
			{
				throw new Exception( 'Cannot update webhook url' );
			}

		}
		else 
		{
			throw new Exception( 'Cannot get Token from PayMob account' );
		}
		
	}

	public function createSubscriptionPlan($token,$secKey, $data)
	{
		$header = array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token );
		$this->addLogs( $this->debug_order, $this->file, print_r( $data, 1 ) );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$plans = $this->HttpRequest( $apiUrl.'api/acceptance/subscription-plans', 'POST', $header, $data );
		$note_i    = 'subscriptionPlans response ';
		$this->addLogs( $this->debug_order, $this->file, $note_i, print_r( $plans, 1 ) );
		if ( empty( $plans ) ) {
			$this->addLogs( $this->debug_order, $this->file, $note_i, $plans );
		}
		
		if ( isset( $plans ) ) {
			$plans = $plans;
		}
		else {
			$plans = ( isset( $plans ) ) ? $plans : 'Something went wrong';
		}
		$this->addLogs( $this->debug_order, $this->file, $note_i, json_encode( $plans ) );
		return $plans;
	}

	public function suspendSubscription($token,$secKey,$planId)
	{
		$header = array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$plans = $this->HttpRequest( $apiUrl.'api/acceptance/subscriptions/'.$planId.'/suspend', 'POST', $header );
		$note_i    = 'Suspend subscriptionPlans response ';
		$this->addLogs( $this->debug_order, $this->file, $note_i, print_r( $plans, 1 ) );
		if ( empty( $plans ) ) {
			$this->addLogs( $this->debug_order, $this->file, $note_i, $plans );
		}
		
		if ( isset( $plans ) ) {
			$plans = $plans;
		}
		else {
			$plans = ( isset( $plans ) ) ? $plans : 'Something went wrong';
		}
		$this->addLogs( $this->debug_order, $this->file, $note_i, json_encode( $plans ) );
		return $plans;
	}

	public function activateSubscription($token,$secKey,$planId)
	{
		$header = array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$plans = $this->HttpRequest( $apiUrl.'api/acceptance/subscriptions/'.$planId.'/resume', 'POST', $header);
		$note_i    = 'Activate subscriptionPlans response ';
		$this->addLogs( $this->debug_order, $this->file, $note_i, print_r( $plans, 1 ) );
		if ( empty( $plans ) ) {
			$this->addLogs( $this->debug_order, $this->file, $note_i, $plans );
		}
		
		if ( isset( $plans ) ) {
			$plans = $plans;
		}
		else {
			$plans = ( isset( $plans ) ) ? $plans : 'Something went wrong';
		}
		$this->addLogs( $this->debug_order, $this->file, $note_i, json_encode( $plans ) );
		return $plans;
	}


	public function cancelSubscription($token,$secKey, $planId)
	{
		$header = array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$plans = $this->HttpRequest( $apiUrl.'api/acceptance/subscriptions/'.$planId.'/cancel', 'POST', $header );
		$note_i    = 'Cancel subscriptionPlans response ';
		$this->addLogs( $this->debug_order, $this->file, $note_i, print_r( $plans, 1 ) );
		if ( empty( $plans ) ) {
			$this->addLogs( $this->debug_order, $this->file, $note_i, $plans );
		}
		
		if ( isset( $plans ) ) {
			$plans = $plans;
		}
		else {
			$plans = ( isset( $plans ) ) ? $plans : 'Something went wrong';
		}
		$this->addLogs( $this->debug_order, $this->file, $note_i, json_encode( $plans ) );
		return $plans;
	}
	
	public function updateSubscription($token,$secKey, $data, $subscriptionId)
	{
		$header = array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token );
		$this->addLogs( $this->debug_order, $this->file, print_r( $data, 1 ) );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$plans = $this->HttpRequest( $apiUrl.'api/acceptance/subscriptions/'.$subscriptionId, 'PUT', $header, $data );
		$note_i    = 'update subscription response ';
		$this->addLogs( $this->debug_order, $this->file, $note_i, print_r( $plans, 1 ) );
		if ( empty( $plans ) ) {
			$this->addLogs( $this->debug_order, $this->file, $note_i, $plans );
		}
		
		if ( isset( $plans ) ) {
			$plans = $plans;
		}
		else {
			$plans = ( isset( $plans ) ) ? $plans : 'Something went wrong';
		}
		$this->addLogs( $this->debug_order, $this->file, $note_i, json_encode( $plans ) );
		return $plans;
	}
	
	public function TransactionSubscriptionID($token,$secKey, $transactionID)
	{
		$header = array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$plans = $this->HttpRequest( $apiUrl.'api/acceptance/subscriptions?transaction='.$transactionID, 'GET', $header );
		$note_i    = ' Transaction subscriptionPlans ID response ';
		$this->addLogs( $this->debug_order, $this->file, $note_i, print_r( $plans, 1 ) );
		if ( empty( $plans ) ) {
			$this->addLogs( $this->debug_order, $this->file, $note_i, $plans );
		}
		
		if ( isset( $plans ) ) {
			$plans = $plans;
		}
		else {
			$plans = ( isset( $plans ) ) ? $plans : 'Something went wrong';
		}
		$this->addLogs( $this->debug_order, $this->file, $note_i, json_encode( $plans ) );
		return $plans;
	}

	public function updateSubscriptionPlan($token,$secKey, $data,$planId)
	{
		$header = array( 'Content-Type: application/json', 'Authorization: Bearer ' . $token );
		$this->addLogs( $this->debug_order, $this->file, print_r( $data, 1 ) );
		$apiUrl = $this->getApiUrl( $this->getCountryCode( $secKey ) );
		$plans = $this->HttpRequest( $apiUrl.'api/acceptance/subscription-plans/'.$planId, 'PUT', $header, $data );
		$note_i    = 'Update subscriptionPlans response ';
		$this->addLogs( $this->debug_order, $this->file, $note_i, print_r( $plans, 1 ) );
		if ( empty( $plans ) ) {
			$this->addLogs( $this->debug_order, $this->file, $note_i, $plans );
		}
		
		if ( isset( $plans ) ) {
			$plans = $plans;
		}
		else {
			$plans = ( isset( $plans ) ) ? $plans : 'Something went wrong';
		}
		$this->addLogs( $this->debug_order, $this->file, $note_i, json_encode( $plans ) );
		return $plans;
	}
}


