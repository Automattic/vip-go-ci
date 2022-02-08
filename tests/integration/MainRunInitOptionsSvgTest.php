<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

require_once __DIR__ . '/IncludesForTests.php';

use PHPUnit\Framework\TestCase;

/**
 * Test vipgoci_run_init_options_svg() function.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsSvgTest extends TestCase {
	/**
	 * SVG scanner path.
	 *
	 * @var $svg_scanner_path
	 */
	private string $svg_scanner_path = '';

	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set up all variables.
	 */
	protected function setUp() :void {
		$this->svg_scanner_path = vipgoci_unittests_get_config_value(
			'svg-scan',
			'svg-scanner-path',
			false
		);

		$this->options = array();
	}

	/**
	 * Clean up all variables.
	 */
	protected function tearDown() :void {
		unset( $this->svg_scanner_path );
		unset( $this->options );
	}

	/**
	 * Test defaults for SVG options.
	 *
	 * @covers ::vipgoci_run_init_options_svg
	 */
	public function testRunInitOptionsSvgDefault() :void {
		vipgoci_run_init_options_svg(
			$this->options
		);

		$this->assertSame(
			array(
				'svg-checks'       => false,
				'svg-scanner-path' => null,
			),
			$this->options
		);
	}

	/**
	 * Test customizations for SVG options.
	 *
	 * @covers ::vipgoci_run_init_options_svg
	 */
	public function testRunInitOptionsSvgCustom() :void {
		if ( empty( $this->svg_scanner_path ) ) {
			$this->markTestSkipped(
				'Skipping test, not configured correctly, as some options are missing (svg-scanner-path)'
			);

			return;
		}

		$this->options['svg-checks']       = 'true';
		$this->options['svg-scanner-path'] = $this->svg_scanner_path;

		vipgoci_run_init_options_svg(
			$this->options
		);

		$this->assertSame(
			array(
				'svg-checks'       => true,
				'svg-scanner-path' => $this->svg_scanner_path,
			),
			$this->options
		);
	}
}
