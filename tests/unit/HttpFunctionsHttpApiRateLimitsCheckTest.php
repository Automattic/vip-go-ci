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
	 * Data for the test.
	 *
	 * @var $TEST_DATA
	 */
	private const TEST_DATA = array(
		array(
			'url'     => 'https://api.github.com/v1',
			'headers' => array(),
			'output'  => '',
		),
		array(
			'url'     => 'https://api.github.com/v1',
			'headers' => array( 'x-ratelimit-remaining' => array( '100' ) ),
			'output'  => '',
		),
		array(
			'url'     => 'https://api.github.com/v1',
			'headers' => array( 'x-ratelimit-remaining' => array( '0' ) ),
			'output'  => '"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
		),
		array(
			'url'     => 'https://api.github.com/v1',
			'headers' => array( 'x-ratelimit-remaining' => array( '-1' ) ),
			'output'  => '"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
		),
		array(
			'url'     => 'https://api.github.com/v1',
			'headers' => array( 'x-ratelimit-remaining' => array( 'abc' ) ), // Invalid, not numeric.
			'output'  => '',
		),
		array(
			'url'     => 'https://wpscan.com/api/v3/plugins/test',
			'headers' => array(),
			'output'  => '',
		),
		array(
			'url'     => 'https://wpscan.com/api/v3/plugins/test',
			'headers' => array( 'x-ratelimit-remaining' => array( '100' ) ),
			'output'  => '',
		),
		array(
			'url'     => 'https://wpscan.com/api/v3/plugins/test',
			'headers' => array( 'x-ratelimit-remaining' => array( '0' ) ),
			'output'  => '"Exceeded rate limit for HTTP API, unable to continue without making further requests."' . PHP_EOL,
		),
		array(
			'url'     => 'https://wpscan.com/api/v3/plugins/test',
			'headers' => array( 'x-ratelimit-remaining' => array( '-1' ) ),
			'output'  => '',
		),
		array(
			'url'     => 'https://wpscan.com/api/v3/plugins/test',
			'headers' => array( 'x-ratelimit-remaining' => array( 'abc' ) ), // Invalid, not numeric.
			'output'  => '',
		),
	);

	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../http-functions.php';
		require_once __DIR__ . '/helper/HttpFunctionsHttpApiRateLimitsCheck.php';
	}

	/**
	 * Test different ratelimits headers when calling the function.
	 *
	 * @covers ::vipgoci_http_api_rate_limit_check
	 *
	 * @return void
	 */
	public function testRateLimits(): void {
		foreach ( self::TEST_DATA as $test_item ) {
			ob_start();

			vipgoci_http_api_rate_limit_check(
				$test_item['url'],
				$test_item['headers']
			);

			$printed_data = ob_get_contents();
			ob_end_clean();

			$this->assertSame(
				$test_item['output'],
				$printed_data,
				'Failed verifying with data: ' . json_encode( $test_item )
			);
		}
	}
}
