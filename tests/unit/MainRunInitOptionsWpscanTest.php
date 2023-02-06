<?php
/**
 * Test vipgoci_run_init_options_wpscan() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Check if WPScan API related options are handled correctly.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsWpscanTest extends TestCase {
	/**
	 * Require files. Set up variable.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/../../defines.php';

		$this->options = array();
	}

	/**
	 * Clear variable.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Check if WPScan API default options are correctly
	 * parsed and provided.
	 *
	 * @covers ::vipgoci_run_init_options_wpscan
	 *
	 * @return void
	 */
	public function testRunInitOptionsWpscanOptionsDefault() :void {
		$this->options = array(
			'wpscan-api'              => null,
			'wpscan-api-dry-mode'     => null,
			'wpscan-api-token'        => '123456789',
			'wpscan-api-paths'        => '/plugins/,themes/,/custom-path/custom-plugins',
			'wpscan-api-skip-folders' => 'dir1,dir2,dir3',
		);

		vipgoci_run_init_options_wpscan( $this->options );

		$this->assertSame(
			array(
				'wpscan-api'                        => false,
				'wpscan-api-dry-mode'               => true,
				'wpscan-api-token'                  => '123456789',
				'wpscan-api-paths'                  => array(
					'plugins',
					'themes',
					'custom-path/custom-plugins',
				),
				'wpscan-api-skip-folders'           => array(
					'dir1',
					'dir2',
					'dir3',
				),
				'wpscan-api-skip-folders-in-repo-options-file' => false,
				'wpscan-api-plugin-file-extensions' => array(
					'php',
				),
				'wpscan-api-theme-file-extensions'  => array(
					'css',
				),
				'wpscan-api-report-end-msg'         => '',
			),
			$this->options
		);
	}

	/**
	 * Check if WPScan API custom options are correctly
	 * parsed and provided.
	 *
	 * @covers ::vipgoci_run_init_options_wpscan
	 *
	 * @return void
	 */
	public function testRunInitOptionsWpscanOptionsCustom() :void {
		$this->options = array(
			'wpscan-api'                                   => 'true',
			'wpscan-api-dry-mode'                          => 'false',
			'wpscan-api-token'                             => '123456789',
			'wpscan-api-paths'                             => '/plugins/,themes/,/custom-path/custom-plugins',
			'wpscan-api-skip-folders'                      => 'dir1,dir2,dir3',
			'wpscan-api-skip-folders-in-repo-options-file' => 'true',
			'wpscan-api-plugin-file-extensions'            => 'php,inc',
			'wpscan-api-theme-file-extensions'             => 'css,css2',
			'wpscan-api-report-end-msg'                    => 'abc',
		);

		vipgoci_run_init_options_wpscan( $this->options );

		$this->assertSame(
			array(
				'wpscan-api'                        => true,
				'wpscan-api-dry-mode'               => false,
				'wpscan-api-token'                  => '123456789',
				'wpscan-api-paths'                  => array(
					'plugins',
					'themes',
					'custom-path/custom-plugins',
				),
				'wpscan-api-skip-folders'           => array(
					'dir1',
					'dir2',
					'dir3',
				),
				'wpscan-api-skip-folders-in-repo-options-file' => true,
				'wpscan-api-plugin-file-extensions' => array(
					'php',
					'inc',
				),
				'wpscan-api-theme-file-extensions'  => array(
					'css',
					'css2',
				),
				'wpscan-api-report-end-msg'         => 'abc',
			),
			$this->options
		);
	}
}
