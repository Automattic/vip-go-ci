<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';
require_once __DIR__ . '/../../misc.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if Pixel API default options are correctly provided.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsPixelApiTest extends TestCase {
	/**
	 * Set up variable.
	 */
	protected function setUp() :void {
		$this->options = array();
	}

	/**
	 * Clean up variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Check if Pixel API default options are correctly provided.
	 *
	 * @covers ::vipgoci_run_init_options_pixel_api
	 */
	public function testRunInitOptionsPixelApiDefault() :void {
		vipgoci_run_init_options_pixel_api(
			$this->options
		);

		$this->assertSame(
			array(
				'pixel-api-url' => null,
			),
			$this->options
		);
	}

	/**
	 * Check if Pixel API options are correctly parsed.
	 *
	 * @covers ::vipgoci_run_init_options_pixel_api
	 */
	public function testRunInitOptionsPixelApiCustom() :void {
		$this->options = array(
			'pixel-api-url'         => 'https://api.test.local/v1/api  ',
			'pixel-api-groupprefix' => '  _prefix1  ',
		);

		vipgoci_run_init_options_pixel_api(
			$this->options
		);

		$this->assertSame(
			array(
				'pixel-api-url'         => 'https://api.test.local/v1/api',
				'pixel-api-groupprefix' => '_prefix1',
			),
			$this->options
		);
	}
}
