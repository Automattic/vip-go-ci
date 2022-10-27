<?php
/**
 * Helper function implementation for
 * OtherWebServicesSendStatsToPixelApiTest test.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
/**
 * Print URL when called instead of performing HTTP request.
 *
 * @param string            $http_api_url           HTTP request URL.
 * @param null|string|array $http_api_token         Not used.
 * @param bool              $fatal_error_on_failure Not used.
 * @param int               $curl_retries_max       Not used.
 *
 * @return string|null Returns URL.
 */
function vipgoci_http_api_fetch_url(
        string $http_api_url,
        null|string|array $http_api_token,
        bool $fatal_error_on_failure = true,
        int $curl_retries_max = 4
) :string|null {
	echo json_encode( $http_api_url ) . PHP_EOL;

	return $http_api_url;
}

// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

