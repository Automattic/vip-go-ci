<?php
/**
 * Autoload for PHPUnit tests.
 *
 * @package Automattic/vip-go-ci
 */

if ( 'true' !== getenv( 'VIPGOCI_TESTING_LIVE_API' ) && ! function_exists( 'curl_exec' ) ) {
	require_once __DIR__ . '/tests/common/helper/IntegrationApiMock.php';
	require_once __DIR__ . '/tests/common/helper/CurlApiMock.php';
}
