<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class StatsTests extends TestCase {
	/**
	 * @covers ::vipgoci_counter_report
	 */
	function testCounterReport1() {
		$this->assertEquals(
			vipgoci_counter_report(
				'illegalaction',
				'mycounter1',
				100
			),
			false
		);

		$this->assertEquals(
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DUMP
			),
			array()
		);
	}

	/**
	 * @covers ::vipgoci_counter_report
	 */
	function testCounterReport2() {
		$this->assertEquals(
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'mycounter2',
				100
			),
			true
		);

		$this->assertEquals(
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'mycounter2',
				1
			),
			true
		);

		$this->assertEquals(
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DUMP
			),
			array(
				'mycounter2' => 101,
			)
		);
	}


	/*
	 * @covers ::vipgoci_counter_update_with_issues_found
	 */
	function testCounterUpdateWithIssuesFound1() {
		$results = array(
			'stats' => array(
				'unique_issue' => array(
					120 => array(
						'errors' => 1,
						'warnings' => 1,
					),

					121 => array(
						'errors' => 2,
						'warnings' => 1,
					),
				)
			)
		);


		vipgoci_counter_update_with_issues_found(
			$results
		);

		$report = vipgoci_counter_report(
			VIPGOCI_COUNTERS_DUMP
		);


		unset( $report['mycounter2'] );

	
		$this->assertEquals(
			$report,
			array(
				'github_pr_unique_issue_issues' => 3,
			)
		);	
	}
}

