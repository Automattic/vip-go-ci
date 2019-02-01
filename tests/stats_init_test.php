<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require( __DIR__ . '/../defines.php' );
require( __DIR__ . '/../statistics.php' );

final class StatsTests extends TestCase {
	/*
	 * Test vipgoci_stats_init(
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

	/*
	 * Test vipgoci_runtime_measure()
	 */
	function testRuntimeMeasure1() {
		return $this->assertEquals(
			vipgoci_runtime_measure( 'illegalaction', 'mytimer1' ),
			false
		);
	}

	function testRuntimeMeasure2() {
		return $this->assertEquals(
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'mytimer2' ),
			false
		);
	}

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


	/*
	 * Test vipgoci_counter_report()
	 */

}	function testCounterReport1() {
		$this->assertEqual(
			vipgoci_counter_report(
				'illegalaction',
				'mycounter1',
				100
			),
			false
		);

		$this->assertEqual(
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DUMP
			),
			array()
		);
	}

	function testCounterReport2() {
		$this->assertEqual(
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'mycounter2',
				100
			),
			true
		);

		$this->assertEqual(
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DO,
				'mycounter2',
				1
			),
			true
		);

		$this->assertEqual(
			vipgoci_counter_report(
				VIPGOCI_COUNTERS_DUMP
			),
			array(
				'mycounter2' => 101,
			)
		);
	}






