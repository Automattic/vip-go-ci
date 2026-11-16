<?php
/**
 * Supply one raw cURL result to the release CLI without network access.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Stop with a status distinct from the release CLI's expected failure.
 *
 * @param string $message Fixture setup error.
 * @return never
 */
function vipgoci_release_fixture_fail( string $message ): never {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 254 );
}

if ( ! function_exists( 'curl_init' ) || function_exists( 'curl_exec' ) || function_exists( 'curl_setopt' ) ) {
	vipgoci_release_fixture_fail( 'Release CLI fixtures require the offline test runner with native curl_init.' );
}

if ( ! function_exists( 'curl_exec' ) ) {
	/**
	 * Record the endpoint chosen by the CLI.
	 *
	 * @param CurlHandle $handle cURL handle.
	 * @param int        $option cURL option.
	 * @param mixed      $value Option value.
	 * @return bool Always true.
	 */
	function curl_setopt( CurlHandle $handle, int $option, mixed $value ): bool {
		if ( CURLOPT_URL === $option ) {
			$GLOBALS['vipgoci_release_fixture_url'] = $value;
		}
		return true;
	}

	/**
	 * Return the response supplied as process arguments.
	 *
	 * @param CurlHandle $handle cURL handle.
	 * @return string|bool Raw response or transport failure.
	 */
	function curl_exec( CurlHandle $handle ): string|bool {
		if ( 'https://api.github.com/repos/automattic/vip-go-ci/releases' !== ( $GLOBALS['vipgoci_release_fixture_url'] ?? null ) ) {
			vipgoci_release_fixture_fail( 'Unexpected release CLI endpoint.' );
		}
		global $argv;
		if ( '--fixture-false' === ( $argv[1] ?? null ) ) {
			return false;
		}
		if ( '--fixture-body' !== ( $argv[1] ?? null ) || ! isset( $argv[2] ) ) {
			vipgoci_release_fixture_fail( 'Missing release CLI fixture.' );
		}
		return $argv[2];
	}
}
