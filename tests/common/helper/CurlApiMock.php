<?php
/**
 * Replace disabled cURL entry points in offline test processes only.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

use Vipgoci\Tests\Common\IntegrationApiMock;

foreach ( array( 'curl_exec', 'curl_setopt', 'curl_setopt_array', 'curl_getinfo', 'sleep' ) as $function ) {
	if ( function_exists( $function ) ) {
		throw new RuntimeException( 'Offline API tests require tests/run-tests.sh. Set VIPGOCI_TESTING_LIVE_API=true only for explicit live testing.' );
	}
}

if ( ! function_exists( 'curl_exec' ) ) {
	/**
	 * Capture options rather than applying them to a real network request.
	 *
	 * @param CurlHandle $handle Handle used by the production client.
	 * @param int        $option cURL option.
	 * @param mixed      $value Option value.
	 * @return bool Always true for captured options.
	 */
	function curl_setopt( CurlHandle $handle, int $option, mixed $value ): bool {
		$GLOBALS['vipgoci_curl_mock_options'] ??= new WeakMap();
		$options                                = $GLOBALS['vipgoci_curl_mock_options'][ $handle ] ?? array();
		$options[ $option ]                     = $value;
		$GLOBALS['vipgoci_curl_mock_options'][ $handle ] = $options;
		return true;
	}

	/**
	 * Apply a set of options through the same recording boundary.
	 *
	 * @param CurlHandle $handle Handle used by the production client.
	 * @param array      $options Options to capture.
	 * @return bool Always true.
	 */
	function curl_setopt_array( CurlHandle $handle, array $options ): bool {
		foreach ( $options as $option => $value ) {
			curl_setopt( $handle, $option, $value );
		}
		return true;
	}

	/**
	 * Execute only against the in-memory fixture service.
	 *
	 * @param CurlHandle $handle Handle configured by the real client.
	 * @return string|bool Fixture body, or false for a simulated transport failure.
	 * @throws RuntimeException If the request has no explicit URL.
	 */
	function curl_exec( CurlHandle $handle ): string|bool {
		$options = $GLOBALS['vipgoci_curl_mock_options'][ $handle ] ?? array();
		$url     = $options[ CURLOPT_URL ] ?? '';
		if ( '' === $url ) {
			throw new RuntimeException( 'Fixture cURL requests must set CURLOPT_URL explicitly.' );
		}
		$method = $options[ CURLOPT_CUSTOMREQUEST ] ?? ( empty( $options[ CURLOPT_POST ] ) ? 'GET' : 'POST' );
		$body   = $options[ CURLOPT_POSTFIELDS ] ?? array();
		if ( is_string( $body ) ) {
			if ( str_starts_with( $body, '{' ) || str_starts_with( $body, '[' ) ) {
				$body = json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
			} else {
				parse_str( $body, $body );
			}
		}
		$GLOBALS['vipgoci_curl_mock_requests'][] = array(
			'method'  => $method,
			'url'     => $url,
			'options' => $options,
		);
		$response                                = IntegrationApiMock::request( $method, $url, $options[ CURLOPT_HTTPHEADER ] ?? array(), $body );
		$descriptor                              = json_decode( $response, true );
		$status                                  = 200;
		$headers                                 = array( 'Content-Type' => 'application/json' );
		if ( is_array( $descriptor ) && isset( $descriptor['__http'] ) ) {
			$status   = $descriptor['__http']['status'] ?? 200;
			$headers  = $descriptor['__http']['headers'] ?? $headers;
			$response = $descriptor['__http']['body'];
		}
		$GLOBALS['vipgoci_curl_mock_status']          ??= new WeakMap();
		$GLOBALS['vipgoci_curl_mock_status'][ $handle ] = $status;
		if ( false !== $response && isset( $options[ CURLOPT_HEADERFUNCTION ] ) ) {
			$options[ CURLOPT_HEADERFUNCTION ]( $handle, 'HTTP/1.1 ' . $status . " OK\r\n" );
			foreach ( $headers as $name => $value ) {
				$options[ CURLOPT_HEADERFUNCTION ]( $handle, $name . ': ' . $value . "\r\n" );
			}
			$options[ CURLOPT_HEADERFUNCTION ]( $handle, "\r\n" );
		}
		return $response;
	}

	/**
	 * Supply the response status used by release checks.
	 *
	 * @param CurlHandle $handle Handle configured by the real client.
	 * @param int|null   $option Information field requested.
	 * @return mixed Fixture information.
	 * @throws RuntimeException If a new information field needs a fixture.
	 */
	function curl_getinfo( CurlHandle $handle, ?int $option = null ): mixed {
		if ( CURLINFO_HTTP_CODE === $option ) {
			return $GLOBALS['vipgoci_curl_mock_status'][ $handle ] ?? 0;
		}
		throw new RuntimeException( 'Unexpected curl_getinfo option in fixture transport.' );
	}

	/**
	 * Record API pacing/backoff without delaying fixture requests.
	 *
	 * Ordinary sleeps retain real time so runtime-measurement tests still work.
	 *
	 * @param int $seconds Requested duration.
	 * @return int Zero on completion.
	 * @throws ValueError If the duration is negative.
	 */
	function sleep( int $seconds ): int {
		if ( 0 > $seconds ) {
			throw new ValueError( 'Sleep duration must not be negative.' );
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Record API backoff without changing general timing tests.
		$trace  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
		$caller = $trace[1]['function'] ?? '';
		if ( ! empty( $GLOBALS['vipgoci_mock_api_waits'] ) && ( str_starts_with( $caller, 'vipgoci_http_api_' ) || str_starts_with( $caller, 'vipgoci_github_' ) ) ) {
			$GLOBALS['vipgoci_api_mock_delays'][] = $seconds;
			return 0;
		}
		usleep( $seconds * 1000000 );
		return 0;
	}

}
