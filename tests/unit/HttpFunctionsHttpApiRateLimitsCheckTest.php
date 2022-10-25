<?php
/**
 * Test vipgoci_http_api_rate_limit_check() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class HttpFunctionsHttpApiRateLimitsCheckTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../statistics.php';
		require_once __DIR__ . '/../../http-functions.php';
		require_once __DIR__ . '/../../statistics.php';
		require_once __DIR__ . '/helper/HttpFunctionsHttpApiRateLimitsCheck.php';
	}

	/**
	 * Data for the test.
	 *
	 * @return array
	 */
	public function dataRateLimits() :array {
		return array(
			array(
				'https://api.github.com/v1', // Request URL (input).
				array(), // HTTP response header (input).
				'', // Expected log message outputted.
				array(), // Expected counters result array.
			),
			array(
				'https://api.github.com/v1',
				array( 'x-ratelimit-remaining' => array( '100' ) ),
				'',
				array(),
			),
			array(
				'https://api.github.com/v1',
				array( 'x-ratelimit-remaining' => array( '0' ) ),
				'"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
				array( 'http_api_request_limit_reached' => 1 ),
			),
			array(
				'https://api.github.com/v1',
				array( 'x-ratelimit-remaining' => array( '-1' ) ),
				'"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
				array( 'http_api_request_limit_reached' => 1 ),
			),
			array(
				'https://api.github.com/v1',
				array( 'x-ratelimit-remaining' => array( 'abc' ) ), // Invalid, not numeric.
				'',
				array(),
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array(),
				'',
				array(),
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array( 'x-ratelimit-remaining' => array( '100' ) ),
				'',
				array(),
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array( 'x-ratelimit-remaining' => array( '0' ) ),
				'"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
				array( 'http_api_request_limit_reached' => 1 ),
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array( 'x-ratelimit-remaining' => array( '-1' ) ),
				'',
				array(),
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array( 'x-ratelimit-remaining' => array( 'abc' ) ), // Invalid, not numeric.
				'',
				array(),
			),
		);
	}

	/**
	 * Test different ratelimits headers when calling the function.
	 *
	 * @dataProvider dataRateLimits
	 *
	 * @covers ::vipgoci_http_api_rate_limit_check
	 *
	 * @return void
	 */
	public function testRateLimits(
		string $url_input,
		array $headers_input,
		string $output_expected,
		array $counters_expected
	): void {
		ob_start();

		vipgoci_http_api_rate_limit_check(
			$url_input,
			$headers_input
		);

		$output_actual = ob_get_contents();
		ob_end_clean();

		$counters_actual = vipgoci_counter_report(
			VIPGOCI_COUNTERS_DUMP,
			null,
			null
		);

		$log_detail = array(
			'url_input'         => $url_input,
			'headers_input'     => $headers_input,
			'output_expected'   => $output_expected,
			'counters_expected' => $counters_expected,
		); 

		$this->assertSame(
			$output_expected,
			$output_actual,
			'Verification failed using data: ' . json_encode( $log_detail )
		);

		$this->assertSame(
			$counters_expected,
			$counters_actual,
			'Failed verifying with data: ' . json_encode( $log_detail )
		);
	}
}
