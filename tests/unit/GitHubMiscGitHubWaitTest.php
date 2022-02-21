<?php
/**
 * Tests the vipgoci_github_wait() function.
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
final class GitHubMiscGitHubWaitTest extends TestCase {
	/**
	 * Setup function. Require files.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../github-misc.php';
		require_once __DIR__ . '/../../defines.php';

		require_once __DIR__ . '/helper/RuntimeMeasure.php';
	}

	/**
	 * Test the vipgoci_github_wait() function by
	 * calling it repeatedly and check if it successfully
	 * returns after a defined time when it should do so.
	 *
	 * @covers ::vipgoci_github_wait
	 */
	public function testGitHubWait(): void {
		for ( $i = 0; $i < 4; $i++ ) {
			$time_start = time();

			vipgoci_github_wait();

			$time_end = time();

			$time_spent = $time_end - $time_start;

			if ( 0 === $i ) {
				// If first run, should return instantly.
				$this->assertTrue(
					( ( $time_spent >= 0 ) && ( $time_spent <= 1 ) ),
					'vipgoci_github_wait() returned earlier or later than expected'
				);
			} else {
				// On later runs, should wait for a bit.
				$this->assertTrue(
					( ( $time_spent >= 1 ) && ( $time_spent <= VIPGOCI_GITHUB_WAIT_TIME_SECONDS ) ),
					'vipgoci_github_wait() returned earlier or later than expected'
				);
			}
		}
	}
}
