<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require( __DIR__ . '/../defines.php' );
require( __DIR__ . '/../statistics.php' );

final class StatsTests extends TestCase {
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
}


