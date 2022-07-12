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
final class MainRunInitOptionsWpscan extends TestCase {
	/**
	 * Require files. Set up variable.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/../../defines.php';

		$this->options = array();
	}

	/**
	 * Clear variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Check if WPScan API default options are correctly
	 * parsed and provided.
	 *
	 * @covers ::vipgoci_run_init_options_wpscan
	 */
	public function testRunInitOptionsWpscanOptionsDefault() :void {
		$this->options = array(
			'wpscan-api'              => null,
			'wpscan-api-url'          => null,
			'wpscan-api-token'        => '123456789',
			'wpscan-api-paths'        => '/plugins/,themes/,/custom-path/custom-plugins',
			'wpscan-api-skip-folders' => 'dir1,dir2,dir3',
		);

		vipgoci_run_init_options_wpscan( $this->options );

		$this->assertSame(
			array(
				'wpscan-api'              => false,
				'wpscan-api-url'          => 'https://wpscan.com/api/v3',
				'wpscan-api-token'        => '123456789',
				'wpscan-api-paths'        => array(
					'plugins',
					'themes',
					'custom-path/custom-plugins',
				),
				'wpscan-api-skip-folders' => array(
					'dir1',
					'dir2',
					'dir3',
				),
			),
			$this->options
		);
	}

	/**
	 * Check if WPScan API custom options are correctly
	 * parsed and provided.
	 *
	 * @covers ::vipgoci_run_init_options_wpscan
	 */
	public function testRunInitOptionsWpscanOptionsCustom() :void {
		$this->options = array(
			'wpscan-api'              => 'true',
			'wpscan-api-url'          => 'https://127.0.0.1/api',
			'wpscan-api-token'        => '123456789',
			'wpscan-api-paths'        => '/plugins/,themes/,/custom-path/custom-plugins',
			'wpscan-api-skip-folders' => 'dir1,dir2,dir3',
		);

		vipgoci_run_init_options_wpscan( $this->options );

		$this->assertSame(
			array(
				'wpscan-api'              => true,
				'wpscan-api-url'          => 'https://127.0.0.1/api',
				'wpscan-api-token'        => '123456789',
				'wpscan-api-paths'        => array(
					'plugins',
					'themes',
					'custom-path/custom-plugins',
				),
				'wpscan-api-skip-folders' => array(
					'dir1',
					'dir2',
					'dir3',
				),
			),
			$this->options
		);
	}
}
