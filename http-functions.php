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
 * @return int|array When collecting header, returns length of
 *                   header saved. Returns array of headers otherwise.
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
	 * HTTP status code.
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
 * Detect if we exceeded the API rate limit,
 * and if so, exit with error.
 *
 * @param string $http_api_url API URL used.
 * @param array  $resp_headers HTTP response headers.
 *
 * @return void
 */
function vipgoci_http_api_rate_limit_check(
	string $http_api_url,
	array $resp_headers
) :void {
	/*
	 * Special case for WPScan API: Unlimited requests
	 * are indicated with a negative number for
	 * x-ratelimit-remaining header. Here we ignore
	 * such headers for WPScan API responses.
	 */
	if (
		( true === str_starts_with(
			$http_api_url,
			VIPGOCI_WPSCAN_API_BASE_URL,
		) ) &&
		( isset( $resp_headers['x-ratelimit-remaining'][0] ) ) &&
		( is_numeric( $resp_headers['x-ratelimit-remaining'][0] ) ) &&
		( $resp_headers['x-ratelimit-remaining'][0] < 0 )
	) {
		return;
	}

	/*
	 * Look for ratelimit header.
	 */
	if (
		( isset( $resp_headers['x-ratelimit-remaining'][0] ) ) &&
		( is_numeric( $resp_headers['x-ratelimit-remaining'][0] ) ) &&
		( $resp_headers['x-ratelimit-remaining'][0] <= 1 )
	) {
		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'http_api_request_limit_reached',
			1
		);

		vipgoci_sysexit(
			'Exceeded rate limit for HTTP API, unable to ' .
				'continue without making further requests.',
			array(
				'http_api_url'          => $http_api_url,
				'x-ratelimit-remaining' => $resp_headers['x-ratelimit-remaining'][0],
				'x-ratelimit-limit'     => isset( $resp_headers['x-ratelimit-limit'][0] )
					? $resp_headers['x-ratelimit-limit'][0] : null,
			),
			VIPGOCI_EXIT_HTTP_API_ERROR,
			true // Log to IRC.
		);
	}
}

/**
 * Save or get saved HTTP API rate limit information and return.
 *
 * @param string $http_api_url          HTTP request URL.
 * @param array  $http_headers_response All HTTP headers from HTTP response as array.
 *
 * @return array|null Results as array, null when no results are cached or invalid HTTP URL is provided.
 */
function vipgoci_http_api_rate_limit_usage(
	string $http_api_url = '',
	array $http_headers_response = array()
) :array|null {
	static $ratelimit_usage = array();

	if (
		( empty( $http_api_url ) ) ||
		( empty( $http_headers_response ) )
	) {
		if ( empty( $ratelimit_usage ) ) {
			return null;
		} else {
			return $ratelimit_usage;
		}
	}

	if ( true === str_starts_with(
		$http_api_url,
		VIPGOCI_GITHUB_BASE_URL
	) ) {
		$service = 'github';
	} elseif ( true === str_starts_with(
		$http_api_url,
		VIPGOCI_WPSCAN_API_BASE_URL
	) ) {
		$service = 'wpscan';
	} else {
		return null;
	}

	foreach ( array(
		'x-ratelimit-limit',
		'x-ratelimit-remaining',
		'x-ratelimit-reset',
		'x-ratelimit-used',
		'x-ratelimit-resource',
	) as $key ) {
		if ( isset( $http_headers_response[ $key ][0] ) ) {
			$key_short = str_replace(
				'x-ratelimit-',
				'',
				$key
			);

			if ( is_numeric( $http_headers_response[ $key ][0] ) ) {
				$ratelimit_usage[ $service ][ $key_short ] =
					(int) $http_headers_response[ $key ][0];
			} else {
				$ratelimit_usage[ $service ][ $key_short ] =
					$http_headers_response[ $key ][0];
			}
		}
	}

	return $ratelimit_usage;
}

/**
 * Make sure to wait between requests to HTTP APIs,
 * but only for certain APIs and when needed.
 *
 * This function should be called just before
 * sending a request to a HTTP API -- that is the most
 * effective usage. Will only wait if not enough time
 * has passed between calls to this function and if the
 * HTTP API URL specified matches one of the URLs in
 * VIPGOCI_HTTP_API_WAIT_APIS_ARRAY.
 *
 * See here for background for GitHub API requests:
 * https://developer.github.com/v3/guides/best-practices-for-integrators/#dealing-with-abuse-rate-limits
 *
 * @param string $http_api_url The HTTP API URL being called.
 *
 * @return void
 */
function vipgoci_http_api_wait( string $http_api_url ) :void {
	static $last_request_time = null;

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'http_api_forced_wait' );

	/*
	 * Only wait in case of certain APIs being called.
	 */
	$http_api_host = parse_url(
		$http_api_url,
		PHP_URL_HOST
	);

	$maybe_wait = false;

	if ( ! empty( $http_api_host ) ) {
		$maybe_wait = vipgoci_string_found_in_substrings_array(
			VIPGOCI_HTTP_API_WAIT_APIS_ARRAY,
			$http_api_host,
			false
		);
	}

	if ( false === $maybe_wait ) {
		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'http_api_forced_wait' );
		return;
	}

	/*
	 * We should maybe wait.
	 */

	if ( null !== $last_request_time ) {
		/*
		 * Only sleep if less than specified time
		 * has elapsed from last request.
		 */
		if (
			( time() - $last_request_time ) <
			VIPGOCI_HTTP_API_WAIT_TIME_SECONDS
		) {
			sleep( VIPGOCI_HTTP_API_WAIT_TIME_SECONDS );
		}
	}

	$last_request_time = time();

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'http_api_forced_wait' );
}

/**
 * Make a GET request to HTTP API, for the URL
 * provided, using the access-token specified.
 *
 * Will return the raw-data returned by the HTTP API,
 * or halt execution on repeated errors.
 *
 * @param string            $http_api_url           HTTP request URL.
 * @param null|string|array $http_api_token         Access token to use as string or array, null to skip.
 * @param bool              $fatal_error_on_failure If to exit on failure or return.
 * @param int               $curl_retries_max       How often to retry request in case of failure.
 *
 * @return string|null String containing results on success, null on failure (if set not to exit).
 */
function vipgoci_http_api_fetch_url(
	string $http_api_url,
	null|string|array $http_api_token,
	bool $fatal_error_on_failure = true,
	int $curl_retries_max = 4
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
			$http_api_url
		);

		curl_setopt(
			$ch,
			CURLOPT_RETURNTRANSFER,
			1
		);

		curl_setopt(
			$ch,
			CURLOPT_CONNECTTIMEOUT,
			VIPGOCI_HTTP_API_LONG_TIMEOUT
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

		/*
		 * Set HTTP headers.
		 */
		$tmp_http_headers_arr = array();

		if (
			( is_string( $http_api_token ) ) &&
			( strlen( $http_api_token ) > 0 )
		) {
			$tmp_http_headers_arr[] = 'Authorization: token ' . $http_api_token;
		} elseif (
			( is_array( $http_api_token ) ) &&
			( isset( $http_api_token['wpscan_token'] ) )
		) {
			$tmp_http_headers_arr[] = 'Authorization: Token token=' .
				$http_api_token['wpscan_token'];
		}

		vipgoci_github_api_version_header_maybe_add(
			$http_api_url,
			$tmp_http_headers_arr
		);

		if ( ! empty( $tmp_http_headers_arr ) ) {
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				$tmp_http_headers_arr
			);
		}

		vipgoci_curl_set_security_options(
			$ch
		);

		// Make sure to wait if needed.
		vipgoci_http_api_wait( $http_api_url );

		/*
		 * Execute query to API, keep
		 * record of how long time it took,
		 * and also keep count of how many we do.
		 */
		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'http_api_request_get' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'http_api_request_get',
			1
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'http_api_request_get' );

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
			) ||
			(
				( isset( $resp_headers['retry-after'] ) ) &&
				( intval( $resp_headers['retry-after'] ) > 0 )
			)
		) {
			if (
				( isset( $resp_headers['retry-after'] ) ) &&
				( intval( $resp_headers['retry-after'] ) > 0 )
			) {
				$retry_sleep = intval( $resp_headers['retry-after'] ) + 1;
			} else {
				$retry_sleep = 10;
			}

			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'http_api_request_failure',
				1
			);

			vipgoci_log(
				'Sending GET request to HTTP API failed' .
					( ( $curl_retries < $curl_retries_max ) ?
					', will retry in ' . (string) $retry_sleep . ' second(s)' :
					'' ),
				array(
					'http_api_url'          => $http_api_url,
					'curl_retries'          => $curl_retries,
					'curl_errno'            => curl_errno(
						$ch
					),
					'curl_errormsg'         => curl_strerror(
						curl_errno( $ch )
					),
					'http_status'           =>
						isset( $resp_headers['status'] ) ?
						$resp_headers['status'] : null,
					'x-github-request-id'   =>
						isset( $resp_headers['x-github-request-id'] ) ?
						$resp_headers['x-github-request-id'] : null,
					'http_response_headers' => $resp_headers,
					'http_response_body'    => $resp_data,
				),
				0,
				true // Log to IRC.
			);

			$resp_data = false;

			sleep( $retry_sleep );
		}

		vipgoci_http_api_rate_limit_usage(
			$http_api_url,
			$resp_headers
		);

		vipgoci_http_api_rate_limit_check(
			$http_api_url,
			$resp_headers
		);

		vipgoci_http_resp_sunset_header_check(
			$http_api_url,
			$resp_headers
		);

		curl_close( $ch );

	} while (
		( false === $resp_data ) &&
		( $curl_retries++ < $curl_retries_max )
	);

	if (
		( true === $fatal_error_on_failure ) &&
		( false === $resp_data )
	) {
		vipgoci_sysexit(
			'Gave up retrying request to HTTP API, can not continue',
			array(),
			VIPGOCI_EXIT_HTTP_API_ERROR,
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
 * Send a POST/DELETE request to HTTP API -- attempt
 * to retry if errors were encountered.
 *
 * Note that the '$http_delete' parameter will determine
 * if a POST or DELETE request will be sent.
 *
 * @param string            $http_api_url        HTTP request URL.
 * @param array             $http_api_postfields HTTP request postfields.
 * @param null|string|array $http_api_token      Access token to use as string or array, null to skip.
 * @param bool              $http_delete         When true, performs HTTP DELETE instead of POST.
 * @param bool              $json_encode         If true, will JSON encode $http_api_postfields using json_encode()
 *                                               before sending request, else uses http_build_query() to
 *                                               generate URL-encoded query-string from $http_api_postfields.
 * @param int               $http_version        What HTTP protocol version to use with cURL, by default lets cURL decide.
 * @param string            $http_content_type   The HTTP Content-Type header value to use. 'application/json' is the default.
 * @param int               $retry_max           How often to retry request in case of failure.
 * @param int               $timeout             Connection timeout, by default VIPGOCI_HTTP_API_LONG_TIMEOUT.
 *
 * @return string|int Request body as string on success, -1 on failure. Failures will be logged.
 *
 * @codeCoverageIgnore
 */
function vipgoci_http_api_post_url(
	string $http_api_url,
	array $http_api_postfields,
	null|string|array $http_api_token,
	bool $http_delete = false,
	bool $json_encode = true,
	int $http_version = CURL_HTTP_VERSION_NONE,
	string $http_content_type = VIPGOCI_HTTP_API_CONTENT_TYPE_APPLICATION_JSON,
	int $retry_max = 4,
	int $timeout = VIPGOCI_HTTP_API_LONG_TIMEOUT
) :string|int {
	/*
	 * Actually send a request to HTTP API -- make sure
	 * to retry if something fails.
	 */
	$retry_cnt = 0;

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
			$http_api_url
		);

		curl_setopt(
			$ch,
			CURLOPT_RETURNTRANSFER,
			1
		);

		curl_setopt(
			$ch,
			CURLOPT_CONNECTTIMEOUT,
			$timeout
		);

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTP_VERSION,
			$http_version
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

		// Encode postfields as JSON if requested, else generate URL-encoded query string.
		if ( true === $json_encode ) {
			$tmp_postfields = json_encode(
				$http_api_postfields
			);
		} else {
			$tmp_postfields = http_build_query(
				$http_api_postfields
			);
		}

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			$tmp_postfields
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		/*
		 * Set HTTP headers.
		 */
		$tmp_http_headers_arr = array();

		if (
			( is_string( $http_api_token ) ) &&
			( strlen( $http_api_token ) > 0 )
		) {
			$tmp_http_headers_arr[] = 'Authorization: token ' . $http_api_token;
		} elseif (
			( is_array( $http_api_token ) ) &&
			( isset( $http_api_token['bearer'] ) )
		) {
			$tmp_http_headers_arr[] = 'Authorization: Bearer ' . $http_api_token['bearer'];
		}

		if ( strlen( $http_content_type ) > 0 ) {
			$tmp_http_headers_arr[] = 'Content-Type: ' . $http_content_type;
		}

		vipgoci_github_api_version_header_maybe_add(
			$http_api_url,
			$tmp_http_headers_arr
		);

		if ( ! empty( $tmp_http_headers_arr ) ) {
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				$tmp_http_headers_arr
			);
		}

		unset( $tmp_http_headers_arr );

		vipgoci_curl_set_security_options(
			$ch
		);

		// Make sure to wait if needed.
		vipgoci_http_api_wait( $http_api_url );

		/*
		 * Execute query to HTTP API, keep
		 * record of how long time it took,
		 * and keep count of how many requests we do.
		 */

		if ( false === $http_delete ) {
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'http_api_request_post' );

			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'http_api_request_post',
				1
			);
		} else {
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'http_api_request_delete' );

			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'http_api_request_delete',
				1
			);
		}

		$resp_data = curl_exec( $ch );

		if ( false === $http_delete ) {
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'http_api_request_post' );
		} else {
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'http_api_request_delete' );
		}

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		if ( false === $resp_data ) {
			// Request failed, retry.
			$retry_req = true;

			// Retry in one second.
			$retry_sleep = 1;

			// Indicate request failed.
			$ret_val = -1;
		} elseif (
			// Allow certain statuses, depending on type of request.
			(
				( false === $http_delete ) &&
				( isset( $resp_headers['status'][0] ) ) &&
				( intval( $resp_headers['status'][0] ) !== 200 ) &&
				( intval( $resp_headers['status'][0] ) !== 201 ) &&
				( intval( $resp_headers['status'][0] ) !== 100 )
			)
			||
			(
				( true === $http_delete ) &&
				( isset( $resp_headers['status'][0] ) ) &&
				( intval( $resp_headers['status'][0] ) !== 204 ) &&
				( intval( $resp_headers['status'][0] ) !== 200 )
			)
		) {
			// Indicate request failed.
			$ret_val = -1;

			// Set wait period between requests. May be altered.
			$retry_sleep = 10;

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
				) + 1;
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
		}

		// On failure, log message.
		if ( -1 === $ret_val ) {
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'http_api_request_failure',
				1
			);

			vipgoci_log(
				( false === $resp_data ?
					'Sending POST request to HTTP API failed' :
					'HTTP API reported an error during POST request'
				) .
				( ( true === $retry_req ) && ( $retry_cnt < $retry_max ) ?
					', will retry request in ' . (string) $retry_sleep . ' second(s)' :
					''
				),
				array(
					'http_api_url'          => $http_api_url,
					'retry_cnt'             => $retry_cnt,
					'curl_errno'            => curl_errno(
						$ch
					),
					'curl_errormsg'         => curl_strerror(
						curl_errno( $ch )
					),
					'http_status'           =>
						isset( $resp_headers['status'] ) ?
						$resp_headers['status'] : null,
					'x-github-request-id'   =>
						isset( $resp_headers['x-github-request-id'] ) ?
						$resp_headers['x-github-request-id'] : null,
					'http_response_headers' => $resp_headers,
					'http_response_body'    => $resp_data,
				),
				0,
				true // Log to IRC.
			);

			sleep( $retry_sleep );
		}

		vipgoci_http_api_rate_limit_check(
			$http_api_url,
			$resp_headers
		);

		vipgoci_http_api_rate_limit_usage(
			$http_api_url,
			$resp_headers
		);

		vipgoci_http_resp_sunset_header_check(
			$http_api_url,
			$resp_headers
		);

		curl_close( $ch );
	} while (
		( true === $retry_req ) &&
		( $retry_cnt++ < $retry_max )
	);

	if ( 0 === $ret_val ) {
		return $resp_data;
	} else {
		return $ret_val;
	}
}

/**
 * Submit PUT request to the HTTP API.
 *
 * @param string $http_api_url        HTTP request URL.
 * @param array  $http_api_postfields HTTP request fields.
 * @param string $http_api_token      Access token to use.
 * @param int    $retry_max           How often to retry request in case of failure.
 *
 * @return int Returns zero (0) on success, -1 on failure.
 *
 * @codeCoverageIgnore
 */
function vipgoci_http_api_put_url(
	string $http_api_url,
	array $http_api_postfields,
	string $http_api_token,
	int $retry_max = 4
) :int {
	$retry_cnt = 0;

	/*
	 * Actually send a request to HTTP API -- make sure
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
			$http_api_url
		);

		curl_setopt(
			$ch,
			CURLOPT_RETURNTRANSFER,
			1
		);

		curl_setopt(
			$ch,
			CURLOPT_CONNECTTIMEOUT,
			VIPGOCI_HTTP_API_LONG_TIMEOUT
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
			json_encode( $http_api_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		/*
		 * Set HTTP headers.
		 */
		$tmp_http_headers_arr = array();

		if (
			( is_string( $http_api_token ) ) &&
			( strlen( $http_api_token ) > 0 )
		) {
			$tmp_http_headers_arr[] = 'Authorization: token ' . $http_api_token;
		}

		vipgoci_github_api_version_header_maybe_add(
			$http_api_url,
			$tmp_http_headers_arr
		);

		if ( ! empty( $tmp_http_headers_arr ) ) {
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				$tmp_http_headers_arr
			);
		}

		vipgoci_curl_set_security_options(
			$ch
		);

		// Make sure to wait if needed.
		vipgoci_http_api_wait( $http_api_url );

		/*
		 * Execute query to HTTP API, keep
		 * record of how long time it took,
		 * and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'http_api_put' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'http_api_request_put',
			1
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'http_api_put' );

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		if ( false === $resp_data ) {
			// Indicate failure.
			$ret_val = -1;

			// Retry in one second.
			$retry_sleep = 1;

			// Set to retry.
			$retry_req = true;
		} elseif (
			( isset( $resp_headers['status'][0] ) ) &&
			( intval( $resp_headers['status'][0] ) !== 200 )
		) {
			/*
			 * We assume status 200 for success, everything else for failure.
			 */

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
				) + 1;
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
		}

		if ( -1 === $ret_val ) {
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'http_api_request_failure',
				1
			);

			vipgoci_log(
				( false === $resp_data ?
					'Sending PUT request to HTTP API failed' :
					'HTTP API reported an error during PUT request'
				) .
				( ( true === $retry_req ) && ( $retry_cnt < $retry_max ) ?
				', will retry request in ' . (string) $retry_sleep . ' second(s)' :
				'' ),
				array(
					'http_api_url'          => $http_api_url,
					'retry_cnt'             => $retry_cnt,
					'curl_errno'            => curl_errno(
						$ch
					),
					'curl_errormsg'         => curl_strerror(
						curl_errno( $ch )
					),
					'http_status'           =>
						isset( $resp_headers['status'] ) ?
						$resp_headers['status'] : null,
					'x-github-request-id'   =>
						isset( $resp_headers['x-github-request-id'] ) ?
						$resp_headers['x-github-request-id'] : null,
					'http_response_headers' => $resp_headers,
					'http_reponse_body'     => $resp_data,
				),
				0,
				true // Log to IRC.
			);

			sleep( $retry_sleep );
		}

		vipgoci_http_api_rate_limit_check(
			$http_api_url,
			$resp_headers
		);

		vipgoci_http_api_rate_limit_usage(
			$http_api_url,
			$resp_headers
		);

		vipgoci_http_resp_sunset_header_check(
			$http_api_url,
			$resp_headers
		);

		curl_close( $ch );

	} while (
		( true === $retry_req ) &&
		( $retry_cnt++ < $retry_max )
	);

	return $ret_val;
}

