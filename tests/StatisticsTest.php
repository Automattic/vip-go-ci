<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once( __DIR__ . '/../defines.php' );
require_once( __DIR__ . '/../statistics.php' );

final class StatsTests extends TestCase {
	/**
	 * @covers vipgoci_stats_init
	 */
	public function testStatsInit() {
		$pr_item1 = new stdClass();
		$pr_item1->number = 100;

		$pr_item2 = new stdClass();
		$pr_item2->number = 110;

		$stats_arr = array();

		vipgoci_stats_init(
			array(
				'phpcs' => true,
				'lint' => true,
				'hashes-api' => false
			),
			array(
				$pr_item1,
				$pr_item2
			),
			$stats_arr
		);

		return $this->assertEquals(
			array(
				'issues' => array(
					100 =>
						array(),

					110 =>
						array(),
				),

				'stats' => array(
					VIPGOCI_STATS_PHPCS => array(
						100 => array(
							'error' => 0,
							'warning' => 0,
							'info' => 0,
						),

						110 => array(
							'error' => 0,
							'warning' => 0,
							'info' => 0,
						),
						// no hashes-api; not supposed to initialize that
					),

					VIPGOCI_STATS_LINT => array(
						100 => array(
							'error' => 0,
							'warning' => 0,
							'info' => 0,
						),

						110 => array(
							'error' => 0,
							'warning' => 0,
							'info' => 0,
						),
						// no hashes-api; not supposed to initialize that
					),
				)
			),
			$stats_arr
		);
	}


	/**
	 * @covers vipgoci_runtime_measure
	 */
	function testRuntimeMeasure1() {
		return $this->assertEquals(
			vipgoci_runtime_measure( 'illegalaction', 'mytimer1' ),
			false
		);
	}

	/**
	 * @covers vipgoci_runtime_measure
	 */
	function testRuntimeMeasure2() {
		return $this->assertEquals(
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'mytimer2' ),
			false
		);
	}

	/**
	 * @covers vipgoci_runtime_measure
	 */
	function testRuntimeMeasure3() {
		$this->assertEquals(
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'mytimer3' ),
			true
		);

		sleep( 2 );

		$this->assertGreaterThanOrEqual(
			1,
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'mytimer3' )
		);
	
		$runtime_stats = vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_DUMP
		);

		$this->assertGreaterThanOrEqual(
			1,
			$runtime_stats[ 'mytimer3' ]
		);
	}

	/**
	 * @cover vipgoci_runtime_measure
	 */
	function testRuntimeMeasure4() {
		$this->assertEquals(
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'mytimer4' ),
			true
		);

		sleep( 2 );

		$runtime_stats = vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_DUMP
		);

		$this->assertGreaterThanOrEqual(
			1,
			$runtime_stats[ 'mytimer3' ]
		);

	}


	/**
	 * @covers vipgoci_counter_report
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
	 * @covers vipgoci_counter_report
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
	 * @covers vipgoci_counter_update_with_issues_found
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

