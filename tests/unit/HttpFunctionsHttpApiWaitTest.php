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
	 * @return void
	 *
	 * @covers ::vipgoci_http_api_wait
	 */
	public function testGitHubWait(): void {
		for ( $i = 0; $i < 4; $i++ ) {
			$time_start = time();

			// Should wait in case of one of the APIs specified in this array constant.
			vipgoci_http_api_wait( VIPGOCI_HTTP_API_WAIT_APIS_ARRAY[0] );

			$time_end = time();

			$time_spent = $time_end - $time_start;

			if ( 0 === $i ) {
				// If first run, should return instantly. Allow for some variation.
				$this->assertTrue(
					( ( $time_spent >= 0 ) && ( $time_spent <= VIPGOCI_HTTP_API_WAIT_TIME_SECONDS ) ),
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

	/**
	 * Test the vipgoci_http_api_wait() function by
	 * calling it repeatedly and ensure it does not wait
	 * as the HTTP API is not on the list of APIs which
	 * it should wait for.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_http_api_wait
	 */
	public function testGitHubDoNotWait(): void {
		for ( $i = 0; $i < 4; $i++ ) {
			$time_start = time();

			// Should not wait in this case.
			vipgoci_http_api_wait( 'localhost-477-939-460-523.local' );

			$time_end = time();

			$time_spent = $time_end - $time_start;

			// Should return instantly. Allow for some variation.
			$this->assertTrue(
				( ( $time_spent >= 0 ) && ( $time_spent <= VIPGOCI_HTTP_API_WAIT_TIME_SECONDS ) ),
				'vipgoci_http_api_wait() returned earlier or later than expected'
			);
		}
	}
}
