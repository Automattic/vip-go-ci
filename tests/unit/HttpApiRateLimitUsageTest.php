<?php
/**
 * Test vipgoci_http_api_rate_limit_usage() function.
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
final class GitHubRateLimitUsageTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../integration/IncludesForTests.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_http_api_rate_limit_usage
	 *
	 * @return void
	 */
	public function testHttpApiRateLimitUsage1() :void {
		$result = vipgoci_http_api_rate_limit_usage(
			'https://api.github.com/v1',
			array()
		);

		$this->assertNull( $result );

		$result = vipgoci_http_api_rate_limit_usage(
			'https://api.github.com/v1',
			array(
				'x-ratelimit-limit'     => array( '5000' ),
				'x-ratelimit-remaining' => array( '4500' ),
				'x-ratelimit-reset'     => array( '12345' ),
				'x-ratelimit-used'      => array( '500' ),
				'x-ratelimit-resource'  => array( 'core' ),
			)
		);

		$this->assertSame(
			array(
				'github' => array(
					'limit'     => 5000,
					'remaining' => 4500,
					'reset'     => 12345,
					'used'      => 500,
					'resource'  => 'core',
				),
			),
			$result
		);

		$result = vipgoci_http_api_rate_limit_usage(
			'https://wpscan.com/api/v3',
			array(
				'x-ratelimit-limit'     => array( '50' ),
				'x-ratelimit-remaining' => array( '45' ),
				'x-ratelimit-reset'     => array( '23456' ),
				'x-ratelimit-used'      => array( '5' ),
			)
		);

		$this->assertSame(
			array(
				'github' => array(
					'limit'     => 5000,
					'remaining' => 4500,
					'reset'     => 12345,
					'used'      => 500,
					'resource'  => 'core',
				),
				'wpscan' => array(
					'limit'     => 50,
					'remaining' => 45,
					'reset'     => 23456,
					'used'      => 5,
				),
			),
			$result
		);

		$result = vipgoci_http_api_rate_limit_usage(
			'https://api.github.com/v1',
			array(
				'x-ratelimit-limit'     => array( '5000' ),
				'x-ratelimit-remaining' => array( '4000' ),
				'x-ratelimit-reset'     => array( '25500' ),
				'x-ratelimit-used'      => array( '1000' ),
				'x-ratelimit-resource'  => array( 'core' ),
			)
		);

		$this->assertSame(
			array(
				'github' => array(
					'limit'     => 5000,
					'remaining' => 4000,
					'reset'     => 25500,
					'used'      => 1000,
					'resource'  => 'core',
				),
				'wpscan' => array(
					'limit'     => 50,
					'remaining' => 45,
					'reset'     => 23456,
					'used'      => 5,
				),
			),
			$result
		);

		$this->assertSame(
			vipgoci_http_api_rate_limit_usage(),
			$result
		);
	}
}

