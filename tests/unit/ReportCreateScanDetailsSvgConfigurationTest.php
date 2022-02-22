<?php
/**
 * Test vipgoci_report_create_scan_details_svg_configuration(),
 * which outputs HTML code.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ReportCreateScanDetailsSvgConfigurationTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../reports.php';
		require_once __DIR__ . '/../../output-security.php';
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/helper/ReportCreateScanDetails.php';

		$this->options = array();
	}

	/**
	 * Clean up options variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test function with most reporting disabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_svg_configuration
	 */
	public function testCreateDetails1(): void {
		$this->options['svg-checks'] = false;

		$actual_output = vipgoci_report_create_scan_details_svg_configuration(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'SVG configuration'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>SVG scanning enabled: ' . PHP_EOL .
				'<code>false</code></p>'
			)
		);
	}

	/**
	 * Test function with reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_svg_configuration
	 */
	public function testCreateDetails2(): void {
		$this->options['svg-checks'] = true;

		$actual_output = vipgoci_report_create_scan_details_svg_configuration(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'SVG configuration'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>SVG scanning enabled: ' . PHP_EOL .
				'<code>true</code></p>'
			)
		);
	}
}
