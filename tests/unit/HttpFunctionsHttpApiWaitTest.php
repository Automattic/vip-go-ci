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
		require_once __DIR__ . '/../../misc.php';

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
			$time_start = microtime( true );

			// Should wait in case of one of the APIs specified in this array constant.
			vipgoci_http_api_wait( 'https://' . VIPGOCI_HTTP_API_WAIT_APIS_ARRAY[0] . '/test' );

			$time_spent = microtime( true ) - $time_start;

			if ( 0 === $i ) {
				// If first run, should return instantly. Allow for some variation.
				$this->assertLessThan(
					VIPGOCI_HTTP_API_WAIT_TIME_SECONDS / 2,
					$time_spent,
					'vipgoci_http_api_wait() returned later than expected'
				);
			} else {
				// On later runs, should wait for a bit. Allow for some variation.
				$this->assertGreaterThan(
					VIPGOCI_HTTP_API_WAIT_TIME_SECONDS * 0.9,
					$time_spent,
					'vipgoci_http_api_wait() returned earlier than expected'
				);

				$this->assertLessThan(
					VIPGOCI_HTTP_API_WAIT_TIME_SECONDS * 1.5,
					$time_spent,
					'vipgoci_http_api_wait() returned later than expected'
				);
			}
		}
	}

	/**
	 * Test the vipgoci_http_api_wait() function only
	 * waits for the time remaining since the last call.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_http_api_wait
	 */
	public function testGitHubWaitOnlyRemainingTime(): void {
		$http_api_url = 'https://' . VIPGOCI_HTTP_API_WAIT_APIS_ARRAY[0] . '/test';

		vipgoci_http_api_wait( $http_api_url );

		$time_first_call = microtime( true );

		usleep( (int) ( VIPGOCI_HTTP_API_WAIT_TIME_SECONDS * 600000 ) );

		$time_start = microtime( true );

		vipgoci_http_api_wait( $http_api_url );

		$time_end = microtime( true );

		$this->assertLessThan(
			VIPGOCI_HTTP_API_WAIT_TIME_SECONDS * 0.7,
			$time_end - $time_start,
			'vipgoci_http_api_wait() waited longer than the time remaining'
		);

		$this->assertGreaterThanOrEqual(
			VIPGOCI_HTTP_API_WAIT_TIME_SECONDS,
			$time_end - $time_first_call,
			'vipgoci_http_api_wait() did not wait for the time remaining'
		);
	}

	/**
	 * Test the vipgoci_http_api_retry_wait() function
	 * only sleeps if the request will be retried.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_http_api_retry_wait
	 */
	public function testRetryWaitOnlySleepsWhenRetrying(): void {
		$time_start = microtime( true );

		// Not retrying, and retries used up.
		vipgoci_http_api_retry_wait( false, 0, 4, 2 );
		vipgoci_http_api_retry_wait( true, 4, 4, 2 );

		$this->assertLessThan( 1, microtime( true ) - $time_start );

		$time_start = microtime( true );

		vipgoci_http_api_retry_wait( true, 0, 4, 1 );

		$this->assertGreaterThanOrEqual( 0.9, microtime( true ) - $time_start );
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
			vipgoci_http_api_wait( 'https://localhost-477-939-460-523.local' );

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
