<?php
/**
 * Test vipgoci_counter_update_with_issues_found().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test counter functionality.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class StatisticsCounterUpdateWithIssuesFoundTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../defines.php';
		require_once __DIR__ . './../../statistics.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_counter_update_with_issues_found
	 *
	 * @return void
	 */
	public function testCounterUpdateWithIssuesFound1() :void {
		$results = array(
			'stats' => array(
				'unique_issue' => array(
					120 => array(
						'errors'   => 1,
						'warnings' => 1,
					),

					121 => array(
						'errors'   => 2,
						'warnings' => 1,
					),
				),
			),
		);

		vipgoci_counter_update_with_issues_found(
			$results
		);

		$report = vipgoci_counter_report(
			VIPGOCI_COUNTERS_DUMP
		);

		$this->assertSame(
			array(
				'github_pr_unique_issue_issues' => 3,
			),
			$report
		);
	}
}

