<?php
/**
 * Test vipgoci_wpcore_misc_scan_directory_for_addons() function.
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
final class WpCoreMiscScanDirectoryForAdddonsTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Check if function detects plugins and themes.
	 *
	 * @covers ::vipgoci_wpcore_misc_scan_directory_for_addons
	 *
	 * @return void
	 */
	public function testWpcoreMiscScanDirectoryForAdddons(): void {
		$temp_dir =
			sys_get_temp_dir() .
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
			escapeshellarg( __DIR__ . '/helper-files/WpCoreMiscScanDirectoryForAdddonsTest' ) .
			' ' .
			escapeshellarg( $temp_dir );

		if ( false === exec( $cp_cmd ) ) {
			$this->markTestSkipped(
				'Unable to extract tar file'
			);

			return;
		}

		$results_expected = array(
			'addon2/style.css' => array(
				'type'             => 'vipgoci-wpscan-theme',
				'addon_headers'    => array(
					'Name'        => 'My Package',
					'ThemeURI'    => 'http://wordpress.org/test/my-package/',
					'Description' => 'My text.',
					'Author'      => 'Author Name',
					'AuthorURI'   => 'http://wordpress.org/author/test123',
					'Version'     => '1.0.0',
					'Template'    => '',
					'Status'      => '',
					'TextDomain'  => '',
					'DomainPath'  => '',
					'RequiresWP'  => '',
					'RequiresPHP' => '',
					'UpdateURI'   => '',
					'Title'       => 'My Package',
					'AuthorName'  => 'Author Name',
				),
				'name'             => 'My Package',
				'version_detected' => '1.0.0',
				'file_name'        => $temp_dir . '/WpCoreMiscScanDirectoryForAdddonsTest/addon2/style.css',
			),
			'addon1/file2.php' => array(
				'type'             => 'vipgoci-wpscan-plugin',
				'addon_headers'    => array(
					'Name'        => 'My <h1>Other</h1> Package',
					'PluginURI'   => 'http://wordpress.org/test/my-other-package/',
					'Version'     => '1.1.0',
					'Description' => 'My text.',
					'Author'      => 'Author Name',
					'AuthorURI'   => 'http://wordpress.org/author/test123',
					'TextDomain'  => '',
					'DomainPath'  => '',
					'Network'     => '',
					'RequiresWP'  => '',
					'RequiresPHP' => '',
					'UpdateURI'   => '',
					'Title'       => 'My <h1>Other</h1> Package',
					'AuthorName'  => 'Author Name',
				),
				'name'             => 'My <h1>Other</h1> Package',
				'version_detected' => '1.1.0',
				'file_name'        => $temp_dir . '/WpCoreMiscScanDirectoryForAdddonsTest/addon1/file2.php',
			),
		);

		vipgoci_unittests_output_suppress();

		$results_actual = vipgoci_wpcore_misc_scan_directory_for_addons(
			$temp_dir . '/WpCoreMiscScanDirectoryForAdddonsTest'
		);

		vipgoci_unittests_output_unsuppress();

		if ( false === exec( 'rm -rf ' . escapeshellarg( $temp_dir ) ) ) {
			$this->markTestSkipped(
				'Unable to remove temporary directory'
			);

			return;
		}

		/*
		 * Different systems will return files in different
		 * order; use assertEquals() to avoid failures due to this.
		 */
		$this->assertEquals(
			$results_expected,
			$results_actual
		);
	}
}

