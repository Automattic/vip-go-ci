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
		require_once __DIR__ . '/../../github-misc.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../wpscan-reports.php';

		require_once __DIR__ . '/../integration/IncludesForTestsOutputControl.php';
		require_once __DIR__ . '/helper/IndicateTestId.php';
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

	/**
	 * Test invalid usage of the function.
	 *
	 * @covers ::vipgoci_wpscan_report_start
	 *
	 * @return void
	 */
	public function testWpscanReportStartInvalid(): void {
		vipgoci_unittests_indicate_test_id( 'WpscanReportStartTest' );

		ob_start();

		vipgoci_wpscan_report_start(
			'invalid' // Invalid usage.
		);

		vipgoci_unittests_remove_indication_for_test_id( 'WpscanReportStartTest' );

		// String should have been printed.
		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		// Check if expected string was printed.
		$this->assertStringContainsString(
			'Internal error: Invalid $issue_type in ',
			$printed_data,
		);
	}
}
