<?php
/**
 * Test vipgoci_wpscan_report_format_cvss_score() function.
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
final class WpscanReportFormatCvssScoreTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../wpscan-reports.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpscan_report_format_cvss_score
	 *
	 * @return void
	 */
	public function testFormatCvssScore(): void {
		$this->assertSame(
			'UNKNOWN',
			vipgoci_wpscan_report_format_cvss_score( 11.0 )
		);

		$this->assertSame(
			'CRITICAL',
			vipgoci_wpscan_report_format_cvss_score( 9.1 )
		);

		$this->assertSame(
			'HIGH',
			vipgoci_wpscan_report_format_cvss_score( 8.9 )
		);

		$this->assertSame(
			'LOW',
			vipgoci_wpscan_report_format_cvss_score( 0.1 )
		);

		$this->assertSame(
			'NONE',
			vipgoci_wpscan_report_format_cvss_score( 0.0 )
		);

		$this->assertSame(
			'UNKNOWN',
			vipgoci_wpscan_report_format_cvss_score( -1.1 )
		);
	}
}
