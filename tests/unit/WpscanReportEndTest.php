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
		require_once __DIR__ . '/../../github-misc.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../output-security.php';
		require_once __DIR__ . '/../../wpscan-reports.php';

		require_once __DIR__ . '/../integration/IncludesForTestsOutputControl.php';
		require_once __DIR__ . '/helper/IndicateTestId.php';
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
			VIPGOCI_WPSCAN_PLUGIN,
			'Type: %addon_type%. Message ends.'
		);

		$this->assertStringContainsString(
			'Type: plugin.',
			$report_end
		);

		$this->assertStringNotContainsString(
			'theme',
			$report_end
		);

		$this->assertStringEndsWith(
			'Message ends.' . "\n\r",
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
			VIPGOCI_WPSCAN_THEME,
			'Type: %addon_type%. Message ends.'
		);

		$this->assertStringContainsString(
			'Type: theme.',
			$report_end
		);

		$this->assertStringNotContainsString(
			'plugin',
			$report_end
		);

		$this->assertStringEndsWith(
			'Message ends.' . "\n\r",
			$report_end
		);
	}

	/**
	 * Test invalid usage of the function.
	 *
	 * @covers ::vipgoci_wpscan_report_end
	 *
	 * @return void
	 */
	public function testWpscanReportEndInvalid(): void {
		vipgoci_unittests_indicate_test_id( 'WpscanReportEndTest' );

		ob_start();

		vipgoci_wpscan_report_end(
			'invalid', // Invalid usage.
			'Message ends.'
		);

		vipgoci_unittests_remove_indication_for_test_id( 'WpscanReportEndTest' );

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
