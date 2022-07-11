<?php
/**
 * Test vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory() function.
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
final class WpCoreMiscGetAddonDataAndSlugsForDirectoryTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory
	 *
	 * @return void
	 */
	public function testGetAddonDataAndSlugsForDirectory(): void {
		$temp_dir = sys_get_temp_dir() .
			'/directory_for_addons-' .
			hash( 'sha256', random_bytes( 2048 ) );

		if ( true !== mkdir( $temp_dir ) ) {
			$this->markTestSkipped(
				'Unable to create temporary directory.'
			);

			return;
		}

		$cp_cmd = escapeshellcmd( 'cp' ) .
			' -R ' .
			escapeshellarg( __DIR__ . '/helper-files/WpCoreMiscGetAddonDataAndSlugsForDirectoryTest' ) .
			' ' .
			escapeshellarg( $temp_dir );

		if ( false === exec( $cp_cmd ) ) {
			$this->markTestSkipped(
				'Unable to extract tar file'
			);

			return;
		}

		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory(
			$temp_dir . '/WpCoreMiscGetAddonDataAndSlugsForDirectoryTest'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$actual_results['hello/hello.php']
		);

		$this->assertSame(
			'w.org/plugins/hello-dolly',
			$actual_results['hello/hello.php']['id']
		);

		$this->assertSame(
			'Hello Dolly',
			$actual_results['hello/hello.php']['name']
		);

		$this->assertSame(
			'hello-dolly',
			$actual_results['hello/hello.php']['slug']
		);

		$this->assertSame(
			'hello/hello.php',
			$actual_results['hello/hello.php']['plugin']
		);

		$this->assertTrue(
			version_compare(
				$actual_results['hello/hello.php']['new_version'],
				'0.0.0',
				'>='
			)
		);

		$this->assertStringContainsString(
			'/plugins/hello-dolly',
			$actual_results['hello/hello.php']['url']
		);

		$this->assertStringContainsString(
			'/hello-dolly.',
			$actual_results['hello/hello.php']['package']
		);

		if ( false === exec(
			escapeshellcmd( 'rm' ) .
			' -rf ' .
			escapeshellarg( $temp_dir )
		) ) {
			$this->markTestSkipped(
				'Unable to remove temporary directory'
			);

			return;
		}
	}
}
