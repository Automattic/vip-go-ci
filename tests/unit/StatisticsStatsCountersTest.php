<?php
/**
 * Test vipgoci_counter_report().
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
final class A00StatsCountersTest extends TestCase {
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
	 * Test with invalid parameters.
	 *
	 * @covers ::vipgoci_counter_report
	 *
	 * @return void
	 */
	public function testCounterReport1() :void {
		$this->assertSame(
			vipgoci_counter_report(
				'illegalaction',
				'mycounter1',
				100
			),
			false
		);

		$this->assertSame(
			array(),
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DUMP,
				null,
				null
			)
		);
	}

	/**
	 * Test with valid parameters.
	 *
	 * @covers ::vipgoci_counter_report
	 *
	 * @return void
	 */
	public function testCounterReport2() :void {
		$this->assertSame(
			true,
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'mycounter2',
				100
			)
		);

		$this->assertSame(
			true,
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'mycounter2',
				1
			)
		);

		$this->assertSame(
			array(
				'mycounter2' => 101,
			),
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DUMP
			)
		);
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

		unset( $report['mycounter2'] );

		$this->assertSame(
			array(
				'github_pr_unique_issue_issues' => 3,
			),
			$report
		);
	}
}

