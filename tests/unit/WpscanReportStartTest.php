<?php
/**
 * Test vipgoci_wpscan_report_start().
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
final class WpscanReportStartTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../wpscan-reports.php';
		require_once __DIR__ . '/../../github-misc.php';
	}

	/**
	 * Test common usage of the function.
	 * Test if it returns expected strings.
	 *
	 * @covers ::vipgoci_wpscan_report_start
	 *
	 * @return void
	 */
	public function testWpscanReportStartPlugin(): void {
		$report_start = vipgoci_wpscan_report_start(
			VIPGOCI_WPSCAN_PLUGIN
		);

		$this->assertStringContainsString(
			VIPGOCI_WPSCAN_API_ERROR,
			$report_start
		);

		$this->assertStringContainsString(
			'Automated scanning has identified',
			$report_start
		);

		$this->assertStringContainsString(
			'plugins',
			$report_start
		);

		$this->assertStringNotContainsString(
			'theme',
			$report_start
		);
	}

	/**
	 * Test common usage of the function.
	 * Test if it returns expected strings.
	 *
	 * @covers ::vipgoci_wpscan_report_start
	 *
	 * @return void
	 */
	public function testWpscanReportStartTheme(): void {
		$report_start = vipgoci_wpscan_report_start(
			VIPGOCI_WPSCAN_THEME
		);

		$this->assertStringContainsString(
			VIPGOCI_WPSCAN_API_ERROR,
			$report_start
		);

		$this->assertStringContainsString(
			'Automated scanning has identified',
			$report_start
		);

		$this->assertStringContainsString(
			'themes',
			$report_start
		);

		$this->assertStringNotContainsString(
			'plugin',
			$report_start
		);
	}


}
