<?php
/**
 * HTTP transport doubles used only by isolated offline API tests.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

use Vipgoci\Tests\Common\HttpApiMock;

// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * Return a queued GET response without executing cURL.
 *
 * @param string            $http_api_url           URL.
 * @param null|string|array $http_api_token         Authentication.
 * @param bool              $fatal_error_on_failure Failure policy.
 * @param int               $curl_retries_max       Retry limit.
 *
 * @return string|null Response or failure.
 */
function vipgoci_http_api_fetch_url(
	string $http_api_url,
	null|string|array $http_api_token,
	bool $fatal_error_on_failure = true,
	int $curl_retries_max = 4
): string|null {
	return HttpApiMock::request( 'GET', $http_api_url, $http_api_token );
}

/**
 * Validate a queued POST or DELETE without writing to an external API.
 *
 * @param string            $http_api_url        URL.
 * @param array             $http_api_postfields Payload.
 * @param null|string|array $http_api_token      Authentication.
 * @param bool              $http_delete         Use DELETE.
 * @param bool              $json_encode         Encoding option.
 * @param int               $http_version        HTTP version.
 * @param string            $http_content_type   Content type.
 * @param int               $retry_max           Retry limit.
 * @param int               $timeout             Timeout.
 *
 * @return string|int Response or failure.
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
	return HttpApiMock::request( $http_delete ? 'DELETE' : 'POST', $http_api_url, $http_api_token, $http_api_postfields );
}

/**
 * Validate a queued PUT without writing to an external API.
 *
 * @param string $http_api_url        URL.
 * @param array  $http_api_postfields Payload.
 * @param string $http_api_token      Authentication.
 * @param int    $retry_max           Retry limit.
 *
 * @return int Status.
 */
function vipgoci_http_api_put_url(
	string $http_api_url,
	array $http_api_postfields,
	string $http_api_token,
	int $retry_max = 4
): int {
	return HttpApiMock::request( 'PUT', $http_api_url, $http_api_token, $http_api_postfields );
}
