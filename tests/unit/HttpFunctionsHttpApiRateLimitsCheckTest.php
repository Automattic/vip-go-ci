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
				'https://api.github.com/v1',
				array(),
				'',
				null,
			),
			array(
				'https://api.github.com/v1',
				array( 'x-ratelimit-remaining' => array( '100' ) ),
				'',
				null,
			),
			array(
				'https://api.github.com/v1',
				array( 'x-ratelimit-remaining' => array( '0' ) ),
				'"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
				1,
			),
			array(
				'https://api.github.com/v1',
				array( 'x-ratelimit-remaining' => array( '-1' ) ),
				'"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
				1,
			),
			array(
				'https://api.github.com/v1',
				array( 'x-ratelimit-remaining' => array( 'abc' ) ), // Invalid, not numeric.
				'',
				null,
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array(),
				'',
				null,
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array( 'x-ratelimit-remaining' => array( '100' ) ),
				'',
				null,
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array( 'x-ratelimit-remaining' => array( '0' ) ),
				'"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
				1,
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array( 'x-ratelimit-remaining' => array( '-1' ) ),
				'',
				null,
			),
			array(
				'https://wpscan.com/api/v3/plugins/test',
				array( 'x-ratelimit-remaining' => array( 'abc' ) ), // Invalid, not numeric.
				'',
				null,
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
		string $url,
		array $headers,
		string $output,
		null|int $counter_status
	): void {
		ob_start();

		vipgoci_http_api_rate_limit_check(
			$url,
			$headers
		);

		$printed_data = ob_get_contents();
		ob_end_clean();

		$counters_report = vipgoci_counter_report(
			VIPGOCI_COUNTERS_DUMP,
			null,
			null
		);

		$log_detail = array(
			'url'            => $url,
			'headers'        => $headers,
			'output'         => $output,
			'counter_status' => $counter_status,
		); 

		$this->assertSame(
			$output,
			$printed_data,
			'Verification failed using data: ' . json_encode( $log_detail )
		);

		if ( null !== $counter_status ) {
			$this->assertSame(
				array( 'http_api_request_limit_reached' => $counter_status ),
				$counters_report,
				'Failed verifying with data: ' . json_encode( $log_detail )
			);
		} else {
			$this->assertSame(
				array(),
				$counters_report
			);
		}
	}
}
