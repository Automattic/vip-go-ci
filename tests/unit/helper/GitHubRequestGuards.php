<?php
/**
 * HTTP boundary doubles for GitHubRequestGuardsTest.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

/**
 * Record a request and return the configured response without using the network.
 * Prefer method + path + query fixtures, falling back to method + path fixtures.
 * Encode successful responses as JSON; preserve null and -1 failure sentinels.
 *
 * @param string $method HTTP method.
 * @param string $url    Request URL.
 * @param array  $body   Request body.
 *
 * @return string|int|null JSON response or a transport failure sentinel.
 */
function vipgoci_unittests_github_request( string $method, string $url, array $body = array() ): string|int|null {
	global $vipgoci_test_http_requests, $vipgoci_test_http_responses;

	$request = $method . ' ' . parse_url( $url, PHP_URL_PATH );
	$query   = parse_url( $url, PHP_URL_QUERY );
	$fixture = $request . ( null === $query ? '' : '?' . $query );

	$vipgoci_test_http_requests[] = array(
		'request' => $request,
		'url'     => $url,
		'body'    => $body,
	);

	if ( ! array_key_exists( $fixture, $vipgoci_test_http_responses ) ) {
		$fixture = $request;
	}

	$response = array_key_exists( $fixture, $vipgoci_test_http_responses ) ?
		$vipgoci_test_http_responses[ $fixture ] : array();

	return ( null === $response || -1 === $response ) ? $response : json_encode( $response );
}

/**
 * Replace the external GET transport, keeping API parsing and caching real.
 *
 * @param string            $http_api_url           Request URL.
 * @param null|string|array $http_api_token         Token, unused.
 * @param bool              $fatal_error_on_failure Failure policy, unused.
 * @param int               $curl_retries_max       Retry limit, unused.
 *
 * @return string|null JSON response or null on failure.
 */
function vipgoci_http_api_fetch_url(
	string $http_api_url,
	null|string|array $http_api_token,
	bool $fatal_error_on_failure = true,
	int $curl_retries_max = 4
): string|null {
	return vipgoci_unittests_github_request( 'GET', $http_api_url );
}

/**
 * Record POST and DELETE requests without changing GitHub.
 *
 * @param string            $http_api_url        Request URL.
 * @param array             $http_api_postfields Request body.
 * @param null|string|array $http_api_token      Token, unused.
 * @param bool              $http_delete         Whether to use DELETE instead of POST.
 * @param bool              $json_encode         Encoding option, unused.
 * @param int               $http_version        HTTP version, unused.
 * @param string            $http_content_type   Content type, unused.
 * @param int               $retry_max           Retry limit, unused.
 * @param int               $timeout             Timeout, unused.
 *
 * @return string|int JSON response or -1 on failure.
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
): string|int {
	return vipgoci_unittests_github_request( $http_delete ? 'DELETE' : 'POST', $http_api_url, $http_api_postfields );
}

/**
 * Record dismissals without changing GitHub reviews.
 *
 * @param string $http_api_url        Request URL.
 * @param array  $http_api_postfields Request body.
 * @param string $http_api_token      Token, unused.
 * @param int    $retry_max           Retry limit, unused.
 *
 * @return int Success status.
 */
function vipgoci_http_api_put_url(
	string $http_api_url,
	array $http_api_postfields,
	string $http_api_token,
	int $retry_max = 4
): int {
	vipgoci_unittests_github_request( 'PUT', $http_api_url, $http_api_postfields );
	return 0;
}

// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter
