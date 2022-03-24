<?php
/**
 * Tests the vipgoci_http_api_wait() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the test.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class HttpFunctionsHttpApiWaitTest extends TestCase {
	/**
	 * Setup function. Require files.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../http-functions.php';
		require_once __DIR__ . '/../../defines.php';

		require_once __DIR__ . '/helper/RuntimeMeasure.php';
	}

	/**
	 * Test the vipgoci_http_api_wait() function by
	 * calling it repeatedly and check if it successfully
	 * returns after a defined time when it should do so.
	 *
	 * @covers ::vipgoci_http_api_wait
	 */
	public function testGitHubWait(): void {
		for ( $i = 0; $i < 4; $i++ ) {
			$time_start = time();

			vipgoci_http_api_wait();

			$time_end = time();

			$time_spent = $time_end - $time_start;

			if ( 0 === $i ) {
				// If first run, should return instantly. Allow for some variation.
				$this->assertTrue(
					( ( $time_spent >= 0 ) && ( $time_spent <= 2 ) ),
					'vipgoci_http_api_wait() returned earlier or later than expected'
				);
			} else {
				// On later runs, should wait for a bit. Allow for some variation.
				$this->assertTrue(
					( ( $time_spent >= 1 ) && ( $time_spent <= ( VIPGOCI_HTTP_API_WAIT_TIME_SECONDS + 1 ) ) ),
					'vipgoci_http_api_wait() returned earlier or later than expected'
				);
			}
		}
	}
}
