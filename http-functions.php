<?php
/**
 * HTTP request functions.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * This function collects headers when
 * called as a callback function and returns
 * the headers collected when not invoked as a callback.
 *
 * The difference is that the '$ch' argument is non-null
 * when called as a callback.
 *
 * @param null|CurlHandle $ch     cURL handle.
 * @param null|string     $header HTTP header to process, or null.
 *
 * @return int|array
 */
function vipgoci_curl_headers(
	null|CurlHandle $ch,
	null|string $header
) :int|array {
	static $resp_headers = array();

	if ( null === $ch ) {
		/*
		 * If $ch is null, we are being called to
		 * return whatever headers we have collected.
		 *
		 * Make sure to empty the headers collected.
		 */
		$ret          = $resp_headers;
		$resp_headers = array();

		/*
		 * 'Fix' the status header before returning;
		 * we want the value to be an array such as:
		 * array(
		 *	0 => 201, // Status-code
		 *	1 => 'Created' // Status-string
		 * )
		 */
		if ( isset( $ret['status'] ) ) {
			$ret['status'] = explode(
				' ',
				$ret['status'][0]
			);
		}

		return $ret;
	}

	/*
	 * Process callback requests.
	 */

	/*
	 * Get header length
	 */
	$header_len = strlen( $header );

	/*
	 * Construct 'status' HTTP header based on the
	 * HTTP status code. This used to be provided
	 * by GitHub, but is not anymore.
	 */

	if (
		( strpos( $header, 'HTTP/' ) === 0 ) &&
		( true === str_contains( $header, ' ' ) )
	) {
		$header = explode(
			' ',
			$header
		);

		$header = 'Status: ' . $header[1] . "\n\r";
	}

	/*
	 * Turn the header into an array
	 */
	$header = explode( ':', $header, 2 );

	if ( count( $header ) < 2 ) {
		/*
		 * Should there be less than two values
		 * in the array, simply return, as the header is
		 * invalid.
		 */
		return $header_len;
	}

	/*
	 * Save the header as a key => value
	 * in our associative array.
	 */
	$key = strtolower( trim( $header[0] ) );

	if ( ! array_key_exists( $key, $resp_headers ) ) {
		$resp_headers[ $key ] = array();
	}

	$resp_headers[ $key ][] = trim(
		$header[1]
	);

	return $header_len;
}

/**
 * Set a few options for cURL that enhance security.
 *
 * @param CurlHandle $ch cURL handle.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_curl_set_security_options(
	CurlHandle $ch
) :void {
	/*
	 * Maximum number of redirects to zero.
	 */
	curl_setopt(
		$ch,
		CURLOPT_MAXREDIRS,
		0
	);

	/*
	 * Do not follow any "Location" headers.
	 */
	curl_setopt(
		$ch,
		CURLOPT_FOLLOWLOCATION,
		false
	);
}

/**
 * Log a warning if a Sunset HTTP header is
 * found in array of response headers, as this indicates
 * that the API feature will become deprecated in the
 * future. Will log the URL called, but without query
 * component, as it may contain sensitive information.
 *
 * Information on Sunset HTTP headers:
 * https://datatracker.ietf.org/doc/html/draft-wilde-sunset-header-03
 *
 * @param string $http_url     HTTP URL for identification.
 * @param array  $resp_headers HTTP response headers.
 *
 * @return void
 */
function vipgoci_http_resp_sunset_header_check(
	string $http_url,
	array $resp_headers
) :void {
	/*
	 * Only do detection in 20% of cases, to limit
	 * amount of logging. In case of unit-testing this
	 * will be 100%.
	 */
	if ( ( ! defined( 'VIPGOCI_UNIT_TESTING' ) ) || ( true !== VIPGOCI_UNIT_TESTING ) ) {
		if ( rand( 1, 5 ) > 1 ) {
			return;
		}
	}

	/*
	 * If no sunset header is found, do nothing.
	 */
	if (
		( ! isset( $resp_headers['sunset'][0] ) ) ||
		( strlen( $resp_headers['sunset'][0] ) <= 0 )
	) {
		return;
	}

	$sunset_date = $resp_headers['sunset'];

	/*
	 * To minimize likelihood of data-leaks via the URL being
	 * logged, remove any query parameters and leave
	 * only the base URL.
	 */

	$http_url_parsed = parse_url( $http_url );

	$http_url_clean = '';

	if (
		( ! isset( $http_url_parsed['scheme'] ) ) ||
		( ! isset( $http_url_parsed['host'] ) )
	) {
		vipgoci_log(
			'Warning: Invalid HTTP URL detected while processing sunset headers',
			array(
				'http_url' => $http_url,
			)
		);
	}

	if ( isset( $http_url_parsed['scheme'] ) ) {
		$http_url_clean .=
			$http_url_parsed['scheme'] . '://';
	}

	if ( isset( $http_url_parsed['host'] ) ) {
		$http_url_clean .=
			$http_url_parsed['host'];
	}

	if ( isset( $http_url_parsed['port'] ) ) {
		$http_url_clean .= ':' . (int) $http_url_parsed['port'];
	}

	if ( isset( $http_url_parsed['path'] ) ) {
		$http_url_clean .= '/' . $http_url_parsed['path'];
	}

	vipgoci_log(
		'Warning: Sunset HTTP header detected, feature will become unavailable',
		array(
			'http_url_clean' => $http_url_clean,
			'sunset_date'    => $sunset_date,
		),
		0,
		true // Log to IRC.
	);
}

/**
 * Detect if we exceeded the GitHub rate-limits,
 * and if so, exit with error.
 *
 * @param string $github_url   GitHub URL used.
 * @param array  $resp_headers HTTP response headers.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_github_rate_limits_check(
	string $github_url,
	array $resp_headers
) :void {
	if (
		( isset( $resp_headers['x-ratelimit-remaining'][0] ) ) &&
		( $resp_headers['x-ratelimit-remaining'][0] <= 1 )
	) {
		vipgoci_sysexit(
			'Ran out of request limits for GitHub, cannot ' .
				'continue without making further requests.',
			array(
				'github_url'            => $github_url,
				'x-ratelimit-remaining' => $resp_headers['x-ratelimit-remaining'][0],
				'x-ratelimit-limit'     => $resp_headers['x-ratelimit-limit'][0],
			),
			VIPGOCI_EXIT_GITHUB_PROBLEM,
			true // Log to IRC.
		);
	}
}

/**
 * Calculate HMAC-SHA1 signature for OAuth 1.0 HTTP
 * request. Follows the standard on this but to a
 * limited extent only. For instance, this function
 * does not support having two parameters with the
 * same name.
 *
 * See here for background:
 * https://oauth.net/core/1.0a/#signing_process
 *
 * @param string $http_method    HTTP method.
 * @param string $request_url    HTTP request URL.
 * @param array  $parameters_arr Parameters for HTTP request.
 *
 * @return string Base64 encoded string.
 */
function vipgoci_oauth1_signature_get_hmac_sha1(
	string $http_method,
	string $request_url,
	array $parameters_arr
) :string {
	/*
	 * Start constructing the 'base string' --
	 * a crucial part of the signature.
	 */
	$base_string  = strtoupper( $http_method ) . '&';
	$base_string .= rawurlencode( $request_url ) . '&';

	/*
	 * New array for parameters, temporary
	 * so we can alter them freely.
	 */
	$parameters_arr_new = array();

	/*
	 * In case this parameter is present, it
	 * should not be part of the signature according
	 * to the standard.
	 */
	if ( isset( $parameters_arr['realm'] ) ) {
		unset( $parameters_arr['realm'] );
	}

	/*
	 * Add parameters to the new array, these
	 * need to be encoded in a certain way.
	 */
	foreach ( $parameters_arr as $key => $value ) {
		$parameters_arr_new[ rawurlencode( $key ) ] =
			rawurlencode( $value );
	}

	/*
	 * Also these two should not be part of the
	 * signature.
	 */
	unset( $parameters_arr_new['oauth_token_secret'] );
	unset( $parameters_arr_new['oauth_consumer_secret'] );

	/*
	 * Sort the parameters alphabetically.
	 */
	ksort( $parameters_arr_new );

	/*
	 * Loop through the parameters, and add them
	 * to a temporary 'base string' according to the standard.
	 */

	$delimiter       = '';
	$base_string_tmp = '';

	foreach ( $parameters_arr_new as $key => $value ) {
		$base_string_tmp .= $delimiter . $key . '=' . $value;

		$delimiter = '&';
	}

	/*
	 * Then add the temporary 'base string' to the
	 * permanent 'base string'.
	 */
	$base_string .= rawurlencode(
		$base_string_tmp
	);

	/*
	 * Now calculate hash, using the
	 * 'base string' as input, and
	 * secrets as key.
	 */
	$hash_raw = hash_hmac(
		'sha1',
		$base_string,
		$parameters_arr['oauth_consumer_secret'] . '&' .
			$parameters_arr['oauth_token_secret'],
		true
	);

	/*
	 * Return it base64 encoded.
	 */
	return base64_encode( $hash_raw );
}


/**
 * Create and set HTTP header for OAuth 1.0a requests,
 * including timestamp, nonce, signature method
 * (all part of the header) and then actually sign
 * the request. Returns with a full HTTP header for
 * a OAuth 1.0a HTTP request.
 *
 * @param string       $http_method  HTTP method.
 * @param string       $github_url   HTTP request URL.
 * @param string|array $github_token Access token.
 *
 * @return string Resulting HTTP header string.
 */
function vipgoci_oauth1_headers_get(
	string $http_method,
	string $github_url,
	string|array $github_token
) :string {
	/*
	 * Set signature-method header, static.
	 */
	$github_token['oauth_signature_method'] =
		'HMAC-SHA1';

	/*
	 * Set timestamp and nonce.
	 */
	$github_token['oauth_timestamp'] = (string) ( time() - 1 );

	$github_token['oauth_nonce'] = (string) md5(
		openssl_random_pseudo_bytes( 100 )
	);

	/*
	 * Get the signature for the header.
	 */
	$github_token['oauth_signature'] =
		vipgoci_oauth1_signature_get_hmac_sha1(
			$http_method,
			$github_url,
			$github_token
		);

	/*
	 * Those are not needed after this point,
	 * so we remove them to limit any risk
	 * of information leakage.
	 */
	unset( $github_token['oauth_token_secret'] );
	unset( $github_token['oauth_consumer_secret'] );

	/*
	 * Actually create the full HTTP header
	 */

	$res_header = 'OAuth ';
	$sep        = '';

	foreach (
		$github_token as
			$github_token_key => $github_token_value
	) {
		if ( strpos(
			$github_token_key,
			'oauth_'
		) !== 0 ) {
			/*
			 * If the token_key does not
			 * start with 'oauth_' we skip to
			 * avoid information-leakage.
			 */
			continue;
		}

		$res_header .=
			$sep .
			$github_token_key . '="' .
			rawurlencode( $github_token_value ) .
			'"';

		$sep = ', ';
	}

	/*
	 * Return the header.
	 */
	return $res_header;
}

/**
 * Make a GET request to GitHub, for the URL
 * provided, using the access-token specified.
 *
 * Will return the raw-data returned by GitHub,
 * or halt execution on repeated errors.
 *
 * @param string       $github_url             HTTP request URL.
 * @param string|array $github_token           Access token to use.
 * @param bool         $fatal_error_on_failure If to exit on failure or return.
 *
 * @return string|null String containing results on success, null on failure (if set not to exit).
 */
function vipgoci_github_fetch_url(
	string $github_url,
	string|array $github_token,
	bool $fatal_error_on_failure = true
) :string|null {
	$curl_retries = 0;

	/*
	 * Attempt to send request -- retry if
	 * it fails.
	 */
	do {
		$ch = curl_init();

		curl_setopt(
			$ch,
			CURLOPT_URL,
			$github_url
		);

		curl_setopt(
			$ch,
			CURLOPT_RETURNTRANSFER,
			1
		);

		curl_setopt(
			$ch,
			CURLOPT_CONNECTTIMEOUT,
			20
		);

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		if (
			( is_string( $github_token ) ) &&
			( strlen( $github_token ) > 0 )
		) {
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array( 'Authorization: token ' . $github_token )
			);
		} elseif ( is_array( $github_token ) ) {
			if (
				( isset( $github_token['oauth_consumer_key'] ) ) &&
				( isset( $github_token['oauth_consumer_secret'] ) ) &&
				( isset( $github_token['oauth_token'] ) ) &&
				( isset( $github_token['oauth_token_secret'] ) )
			) {
				$github_auth_header = vipgoci_oauth1_headers_get(
					'GET',
					$github_url,
					$github_token
				);

				curl_setopt(
					$ch,
					CURLOPT_HTTPHEADER,
					array(
						'Authorization: ' .
							$github_auth_header,
					)
				);
			}
		}

		vipgoci_curl_set_security_options(
			$ch
		);

		// Make sure to pause between GitHub-requests.
		vipgoci_github_wait();

		/*
		 * Execute query to GitHub, keep
		 * record of how long time it took,
		 * and also keep count of how many we do.
		 */
		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'github_api_get' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'github_api_request_get',
			1
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'github_api_get' );

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		/*
		 * Detect and process possible errors
		 */
		if (
			( false === $resp_data ) ||
			( curl_errno( $ch ) ) ||
			(
				// Detect internal server errors (HTTP 50X).
				( isset( $resp_headers['status'][0] ) ) &&
				( 500 <= (int) $resp_headers['status'][0] ) &&
				( 600 > (int) $resp_headers['status'][0] )
			)
		) {
			vipgoci_log(
				'Sending request to GitHub failed, will ' .
					'retry in a bit... ',
				array(
					'github_url'          => $github_url,
					'curl_retries'        => $curl_retries,
					'curl_errno'          => curl_errno(
						$ch
					),
					'curl_errormsg'       => curl_strerror(
						curl_errno( $ch )
					),
					'http_status'         =>
						isset( $resp_headers['status'] ) ?
						$resp_headers['status'] : null,
					'http_response'       => $resp_data,
					'x-github-request-id' =>
						isset( $resp_headers['x-github-request-id'] ) ?
						$resp_headers['x-github-request-id'] : null,
				),
				0,
				true // Log to IRC.
			);

			$resp_data = false;

			sleep( 10 );
		}

		vipgoci_github_rate_limits_check(
			$github_url,
			$resp_headers
		);

		vipgoci_http_resp_sunset_header_check(
			$github_url,
			$resp_headers
		);

		curl_close( $ch );

	} while (
		( false === $resp_data ) &&
		( $curl_retries++ < 4 )
	);

	if (
		( true === $fatal_error_on_failure ) &&
		( false === $resp_data )
	) {
		vipgoci_sysexit(
			'Gave up retrying request to GitHub, cannot continue',
			array(),
			VIPGOCI_EXIT_GITHUB_PROBLEM,
			true // Log to IRC.
		);
	} elseif (
		( false === $fatal_error_on_failure ) &&
		( false === $resp_data )
	) {
		return null;
	}

	return $resp_data;
}

/**
 * Send a POST/DELETE request to GitHub -- attempt
 * to retry if errors were encountered.
 *
 * Note that the '$http_delete' parameter will determine
 * if a POST or DELETE request will be sent.
 *
 * @param string $github_url        HTTP request URL.
 * @param array  $github_postfields HTTP request fields.
 * @param string $github_token      Access token to use.
 * @param bool   $http_delete       If to perform HTTP DELETE instead of POST.
 *
 * @return int Zero (0) on success, -1 on failure. Failures will be logged.
 *
 * @codeCoverageIgnore
 */
function vipgoci_github_post_url(
	string $github_url,
	array $github_postfields,
	string $github_token,
	bool $http_delete = false
) :null|int {
	/*
	 * Actually send a request to GitHub -- make sure
	 * to retry if something fails.
	 */
	do {
		/*
		 * By default, assume request went through okay.
		 */
		$ret_val = 0;

		/*
		 * By default, do not retry the request.
		 */
		$retry_req = false;

		/*
		 * Initialize and send request.
		 */
		$ch = curl_init();

		curl_setopt(
			$ch,
			CURLOPT_URL,
			$github_url
		);

		curl_setopt(
			$ch,
			CURLOPT_RETURNTRANSFER,
			1
		);

		curl_setopt(
			$ch,
			CURLOPT_CONNECTTIMEOUT,
			20
		);

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		if ( false === $http_delete ) {
			curl_setopt(
				$ch,
				CURLOPT_POST,
				1
			);
		} else {
			curl_setopt(
				$ch,
				CURLOPT_CUSTOMREQUEST,
				'DELETE'
			);
		}

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $github_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_token )
		);

		vipgoci_curl_set_security_options(
			$ch
		);

		// Make sure to pause between GitHub-requests.
		vipgoci_github_wait();

		/*
		 * Execute query to GitHub, keep
		 * record of how long time it took,
		 * and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'github_api_post' );

		vipgoci_counter_report( VIPGOCI_COUNTERS_DO, 'github_api_request_post', 1 );

		$resp_data = curl_exec( $ch );
		// @todo: Retry request when $resp_data === false
		// @todo: maximum retries.

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'github_api_post' );

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		/*
		 * Allow certain statuses, depending on type of request
		 */
		if (
			(
				( false === $http_delete ) &&
				( intval( $resp_headers['status'][0] ) !== 200 ) &&
				( intval( $resp_headers['status'][0] ) !== 201 ) &&
				( intval( $resp_headers['status'][0] ) !== 100 )
			)

			||

			(
				( true === $http_delete ) &&
				( intval( $resp_headers['status'][0] ) !== 204 ) &&
				( intval( $resp_headers['status'][0] ) !== 200 )
			)
		) {
			/*
			 * Set default wait period between requests
			 */
			$retry_sleep = 10;

			/*
			 * Set default return value.
			 */
			$ret_val = -1;

			/*
			 * Figure out if to retry.
			 */

			// Decode JSON.
			$resp_data = json_decode( $resp_data );

			if (
				( false !== $resp_data ) &&
				( isset( $resp_data->message ) )
			) {
				$resp_data->message = trim( $resp_data->message );
			}

			if (
				( false !== $resp_data ) &&
				( isset( $resp_headers['retry-after'] ) ) &&
				( intval( $resp_headers['retry-after'] ) > 0 )
			) {
				$retry_req   = true;
				$retry_sleep = intval(
					$resp_headers['retry-after']
				);
			} elseif (
				( false !== $resp_data ) &&
				( isset( $resp_data->message ) ) &&
				( isset( $resp_data->errors[0] ) ) &&
				( 'Validation Failed' === $resp_data->message ) &&
				( 'was submitted too quickly after a previous comment' === $resp_data->errors[0] )
			) {
				/*
				 * Here we cannot retry, as submission
				 * has been labelled as "spam".
				 */
				$retry_req = false;
			} elseif (
				( false !== $resp_data ) &&
				( isset( $resp_data->message ) ) &&
				( 'Validation Failed' === $resp_data->message )
			) {
				$retry_req = false;
			} elseif (
				( false !== $resp_data ) &&
				( isset( $resp_data->message ) ) &&
				( 'Server Error' === $resp_data->message )
			) {
				$retry_req = false;
			}

			vipgoci_log(
				'GitHub reported an error' .
					( true === $retry_req ?
					' will retry request in ' .
					(string) $retry_sleep . ' seconds' :
					'' ),
				array(
					'http_url'
						=> $github_url,

					'http_response_headers'
						=> $resp_headers,

					'http_reponse_body'
						=> $resp_data,
				)
			);

			sleep( $retry_sleep + 1 );
		}

		vipgoci_github_rate_limits_check(
			$github_url,
			$resp_headers
		);

		vipgoci_http_resp_sunset_header_check(
			$github_url,
			$resp_headers
		);

		curl_close( $ch );

	} while ( true === $retry_req );

	return $ret_val;
}

/**
 * Submit PUT request to the GitHub API.
 *
 * @param string $github_url        HTTP request URL.
 * @param array  $github_postfields HTTP request fields.
 * @param string $github_token      Access token to use.
 *
 * @codeCoverageIgnore
 *
 * @return int Returns zero (0) on success, -1 on failure.
 */
function vipgoci_github_put_url(
	string $github_url,
	array $github_postfields,
	string $github_token
) :int {
	/*
	 * Actually send a request to GitHub -- make sure
	 * to retry if something fails.
	 */
	do {
		// By default, assume request went through okay.
		$ret_val = 0;

		// By default, do not retry the request.
		$retry_req = false;

		/*
		 * Initialize and send request.
		 */
		$ch = curl_init();

		curl_setopt(
			$ch,
			CURLOPT_URL,
			$github_url
		);

		curl_setopt(
			$ch,
			CURLOPT_RETURNTRANSFER,
			1
		);

		curl_setopt(
			$ch,
			CURLOPT_CONNECTTIMEOUT,
			20
		);

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch,
			CURLOPT_CUSTOMREQUEST,
			'PUT'
		);

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $github_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_token )
		);

		vipgoci_curl_set_security_options(
			$ch
		);

		// Make sure to pause between GitHub-requests.
		vipgoci_github_wait();

		/*
		 * Execute query to GitHub, keep
		 * record of how long time it took,
		 * and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'github_api_put' );

		vipgoci_counter_report( VIPGOCI_COUNTERS_DO, 'github_api_request_put', 1 );

		$resp_data = curl_exec( $ch );

		// @todo: Retry request when $resp_data === false.

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'github_api_put' );

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		/*
		 * Assume 200 for success, everything else for failure.
		 */
		if (
			( isset( $resp_headers['status'][0] ) ) &&
			( intval( $resp_headers['status'][0] ) !== 200 )
		) {
			// Set default wait period between requests.
			$retry_sleep = 10;

			// Set default return value.
			$ret_val = -1;

			/*
			 * Figure out if to retry...
			 */
			if ( false !== $resp_data ) {
				// Decode JSON.
				$resp_data = json_decode( $resp_data );
			}

			if ( isset( $resp_data->message ) ) {
				$resp_data->message = trim( $resp_data->message );
			}

			if (
				( isset( $resp_headers['retry-after'] ) ) &&
				( intval( $resp_headers['retry-after'] ) > 0 )
			) {
				$retry_req   = true;
				$retry_sleep = intval(
					$resp_headers['retry-after']
				);
			} elseif (
				( isset( $resp_data->message ) ) &&
				( 'Validation Failed' === $resp_data->message ) &&
				( 'was submitted too quickly after a previous comment' === $resp_data->errors[0] )
			) {
				/*
				 * Do not retry, submission has been labelled as "spam".
				 */
				$retry_req = false;
			} elseif (
				( isset( $resp_data->message ) ) &&
				( 'Validation Failed' === $resp_data->message )
			) {
				$retry_req = false;
			} elseif (
				( isset( $resp_data->message ) ) &&
				( 'Server Error' === $resp_data->message )
			) {
				$retry_req = false;
			}

			vipgoci_log(
				'GitHub reported an error' .
					( true === $retry_req ?
					' will retry request in ' .
					(string) $retry_sleep . ' seconds' :
					'' ),
				array(
					'http_url'              => $github_url,
					'http_response_headers' => $resp_headers,
					'http_reponse_body'     => $resp_data,
				)
			);

			sleep( $retry_sleep + 1 );
		}

		vipgoci_github_rate_limits_check(
			$github_url,
			$resp_headers
		);

		vipgoci_http_resp_sunset_header_check(
			$github_url,
			$resp_headers
		);

		curl_close( $ch );

	} while ( true === $retry_req );

	return $ret_val;
}

