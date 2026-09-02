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
 *
 * @param string $method HTTP method.
 * @param string $url    Request URL.
 * @param array  $body   Request body.
 *
 * @return string JSON response.
 */
function vipgoci_unittests_github_request( string $method, string $url, array $body = array() ): string {
	global $vipgoci_test_http_requests, $vipgoci_test_http_responses;

	$request = $method . ' ' . parse_url( $url, PHP_URL_PATH );

	$vipgoci_test_http_requests[] = array(
		'request' => $request,
		'body'    => $body,
	);

	return json_encode( $vipgoci_test_http_responses[ $request ] ?? array() );
}

/**
 * Replace the external GET transport, keeping API parsing and caching real.
 *
 * @param string            $http_api_url           Request URL.
 * @param null|string|array $http_api_token         Token, unused.
 * @param bool              $fatal_error_on_failure Failure policy, unused.
 * @param int               $curl_retries_max       Retry limit, unused.
 *
 * @return string JSON response.
 */
function vipgoci_http_api_fetch_url(
	string $http_api_url,
	null|string|array $http_api_token,
	bool $fatal_error_on_failure = true,
	int $curl_retries_max = 4
): string {
	return vipgoci_unittests_github_request( 'GET', $http_api_url );
}

/**
 * Record review submissions without posting to GitHub.
 *
 * @param string            $http_api_url        Request URL.
 * @param array             $http_api_postfields Request body.
 * @param null|string|array $http_api_token      Token, unused.
 *
 * @return string JSON response.
 */
function vipgoci_http_api_post_url(
	string $http_api_url,
	array $http_api_postfields,
	null|string|array $http_api_token
): string {
	return vipgoci_unittests_github_request( 'POST', $http_api_url, $http_api_postfields );
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
