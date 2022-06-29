<?php
/**
 * Test vipgoci_wpscan_report_end().
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
final class WpscanReportEndTest extends TestCase {
	/**
	 * Setup function. Require files.
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
	 * @covers ::vipgoci_wpscan_report_end
	 *
	 * @return void
	 */
	public function testWpscanReportEndPlugin(): void {
		$report_end = vipgoci_wpscan_report_end(
			VIPGOCI_WPSCAN_PLUGIN
		);

		$this->assertStringContainsString(
			'Incorrect plugins?',
			$report_end
		);

		$this->assertStringNotContainsString(
			'themes',
			$report_end
		);
	}

	/**
	 * Test common usage of the function.
	 * Test if it returns expected strings.
	 *
	 * @covers ::vipgoci_wpscan_report_end
	 *
	 * @return void
	 */
	public function testWpscanReportEndTheme(): void {
		$report_end = vipgoci_wpscan_report_end(
			VIPGOCI_WPSCAN_THEME
		);

		$this->assertStringContainsString(
			'Incorrect themes?',
			$report_end
		);

		$this->assertStringNotContainsString(
			'plugins',
			$report_end
		);
	}
}
