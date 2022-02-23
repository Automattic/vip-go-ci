<?php
/**
 * Test vipgoci_report_results_to_github_were_submitted() function.
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
final class ReportResultsToGithubWereSubmittedTest extends TestCase {
	/**
	 * Setup function. Require files.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../reports.php';
	}

	/**
	 * Call vipgoci_report_results_to_github_were_submitted() in different ways,
	 * test if it behaves as it should do.
	 *
	 * @covers ::vipgoci_report_results_to_github_were_submitted
	 */
	public function testReportResultsToGitHubSubmitted(): void {
		$this->assertFalse(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				1,
				false
			)
		);

		$this->assertFalse(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				1
			)
		);

		$this->assertFalse(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				2,
				false
			)
		);

		$this->assertFalse(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				2
			)
		);

		$this->assertTrue(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				1,
				true
			)
		);

		$this->assertTrue(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				1
			)
		);

		// Set PR number 1 again to false; should have no effect.
		$this->assertTrue(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				1,
				false
			)
		);

		// Test PR number 1 again.
		$this->assertTrue(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				1
			)
		);

		// Set to false before; should be false.
		$this->assertFalse(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				2
			)
		);

		// Never set before; should be false.
		$this->assertFalse(
			vipgoci_report_results_to_github_were_submitted(
				'test-owner',
				'test-name',
				3
			)
		);
	}
}
