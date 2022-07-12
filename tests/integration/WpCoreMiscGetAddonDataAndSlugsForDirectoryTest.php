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
	 * Temporary directory.
	 *
	 * @var $temp_dir
	 */
	private $temp_dir = '';

	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		$this->temp_dir = sys_get_temp_dir() .
			'/directory_for_addons-' .
			hash( 'sha256', random_bytes( 2048 ) );

		if ( true !== mkdir( $this->temp_dir ) ) {
			echo 'Unable to create temporary directory.';

			$this->temp_dir = '';
		}
	}

	/**
	 * Tear down function. Clean up temporary files.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		if ( ! empty( $this->temp_dir ) ) {
			if ( false === exec(
				escapeshellcmd( 'rm' ) .
				' -rf ' .
				escapeshellarg( $this->temp_dir )
			) ) {
				echo 'Unable to remove temporary directory' . PHP_EOL;
			}
		}
	}

	/**
	 * Test common usage of the function. Scans subdirectories.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory
	 *
	 * @return void
	 */
	public function testGetAddonDataAndSlugsForDirectoryWithSubdirectories(): void {
		if ( empty( $this->temp_dir ) ) {
			$this->markTestSkipped(
				'Temporary directory not existing.'
			);

			return;
		}

		$cp_cmd = escapeshellcmd( 'cp' ) .
			' -R ' .
			escapeshellarg( __DIR__ . '/helper-files/WpCoreMiscGetAddonDataAndSlugsForDirectoryTest' ) .
			' ' .
			escapeshellarg( $this->temp_dir );

		if ( false === exec( $cp_cmd ) ) {
			$this->markTestSkipped(
				'Unable to extract tar file'
			);

			return;
		}

		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory(
			$this->temp_dir . '/WpCoreMiscGetAddonDataAndSlugsForDirectoryTest',
			true
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Ensure hello/hello.php is in results.
		 */
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

		/*
		 * Ensure this-is-a-plugin.php is in results.
		 */
		$this->assertNotEmpty(
			$actual_results['this-is-a-plugin.php']
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['id'] )
		);

		$this->assertSame(
			'This is a plugin.',
			$actual_results['this-is-a-plugin.php']['name']
		);

		$this->assertFalse(
			isset( $actual_results['his-is-a-plugin.php']['slug'] )
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['plugin'] )
		);

		$this->assertSame(
			'15.1.0',
			$actual_results['this-is-a-plugin.php']['version_detected']
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['new_version'] )
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['url'] )
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['package'] )
		);
	}

	/**
	 * Test common usage of the function. Does not scan subdirectories.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory
	 *
	 * @return void
	 */
	public function testGetAddonDataAndSlugsForDirectorySkipSubdirectories(): void {
		if ( empty( $this->temp_dir ) ) {
			$this->markTestSkipped(
				'Temporary directory not existing.'
			);

			return;
		}

		$cp_cmd = escapeshellcmd( 'cp' ) .
			' -R ' .
			escapeshellarg( __DIR__ . '/helper-files/WpCoreMiscGetAddonDataAndSlugsForDirectoryTest' ) .
			' ' .
			escapeshellarg( $this->temp_dir );

		if ( false === exec( $cp_cmd ) ) {
			$this->markTestSkipped(
				'Unable to extract tar file'
			);

			return;
		}

		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory(
			$this->temp_dir . '/WpCoreMiscGetAddonDataAndSlugsForDirectoryTest',
			false
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Ensure hello/hello.php is not in results.
		 */
		$this->assertFalse(
			isset( $actual_results['hello/hello.php'] )
		);

		/*
		 * Ensure this-is-a-plugin.php is in results.
		 */
		$this->assertNotEmpty(
			$actual_results['this-is-a-plugin.php']
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['id'] )
		);

		$this->assertSame(
			'This is a plugin.',
			$actual_results['this-is-a-plugin.php']['name']
		);

		$this->assertFalse(
			isset( $actual_results['his-is-a-plugin.php']['slug'] )
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['plugin'] )
		);

		$this->assertSame(
			'15.1.0',
			$actual_results['this-is-a-plugin.php']['version_detected']
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['new_version'] )
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['url'] )
		);

		$this->assertFalse(
			isset( $actual_results['this-is-a-plugin.php']['package'] )
		);
	}
}
