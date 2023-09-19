<?php
/**
 * Verify that latest-release.php behaves as it should.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\E2E;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class LatestReleaseTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';

		$this->correct_version_number = VIPGOCI_VERSION;
	}

	/**
	 * Clean up.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->correct_version_number );
	}

	/**
	 * Verify that return value from the script matches
	 * real version number. Also verify that the format
	 * is correct.
	 *
	 * @return void
	 */
	public function testResults(): void {
		$returned_version_number = exec( 'php ../../latest-release.php' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

		/*
		 * Verify format of version number is correct.
		 */
		$version_number_preg = '/^(\d+\.)?(\d+\.)?(\*|\d+)$/';

		$this->assertSame(
			1,
			preg_match(
				$version_number_preg,
				$this->correct_version_number
			)
		);

		$this->assertSame(
			1,
			preg_match(
				$version_number_preg,
				$returned_version_number
			)
		);

		/*
		 * Verify both version numbers match.
		 */
		$this->assertSame(
			$this->correct_version_number,
			$returned_version_number
		);
	}
}
