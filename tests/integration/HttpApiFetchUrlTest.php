<?php
/**
 * Test function vipgoci_http_api_fetch_url().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class HttpApiFetchUrlTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * @covers ::vipgoci_http_api_fetch_url
	 */
	public function testGitHubFetchUrl1() {
		$ret = vipgoci_http_api_fetch_url(
			'https://api.github.com/rate_limit',
			''
		);

		$ret = json_decode(
			$ret,
			false
		);

		$this->assertTrue(
			isset(
				$ret->rate->limit
			)
		);

		$this->assertTrue(
			isset(
				$ret->resources->core->remaining
			)
		);		
	}
}
