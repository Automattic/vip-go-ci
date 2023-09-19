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
	 * Temporary file for contents of defines.php.
	 *
	 * @var $temp_file_name
	 */
	private mixed $temp_file_name = '';

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		$this->temp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci-defines-php-file'
		);
	}

	/**
	 * Clean up.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		if ( false !== $this->temp_file_name ) {
			unlink( $this->temp_file_name );
		}
	}

	/**
	 * Verify that return value from the script matches
	 * real version number. Also verify that the format
	 * is correct.
	 *
	 * @return void
	 */
	public function testResults(): void {
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		if ( false === $this->temp_file_name ) {
			$this->markTestSkipped(
				'Unable to create temporary file'
			);

			return;
		}

		/*
		 * Get 'defines.php' from latest branch,
		 * put contents of the file into temporary file
		 * and then retrieve the version number
		 * by including the file.
		 */
		exec( 'git fetch origin latest && git -C . show latest:defines.php > ' . $this->temp_file_name );

		require_once $this->temp_file_name;

		$correct_version_number = VIPGOCI_VERSION;

		/*
		 * Run latest-release.php to get latest version number.
		 */
		$returned_version_number = exec( 'php latest-release.php' );

		/*
		 * Verify format of version number is correct.
		 */
		$version_number_preg = '/^(\d+\.)?(\d+\.)?(\*|\d+)$/';

		$this->assertSame(
			1,
			preg_match(
				$version_number_preg,
				$correct_version_number
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
			$correct_version_number,
			$returned_version_number
		);
		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
	}
}
