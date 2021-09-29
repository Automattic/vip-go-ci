<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . './../../defines.php' );
require_once( __DIR__ . './../../statistics.php' );

use PHPUnit\Framework\TestCase;
use stdClass;

// phpcs:disable PSR1.Files.SideEffects

final class StatsStatsInitTest extends TestCase {
	/**
	 * @covers ::vipgoci_stats_init
	 */
	public function testStatsInit() {
		$pr_item1         = new stdClass();
		$pr_item1->number = 100;

		$pr_item2         = new stdClass();
		$pr_item2->number = 110;

		$stats_arr = array();

		vipgoci_stats_init(
			array(
				'phpcs'      => true,
				'lint'       => true,
				'hashes-api' => false
			),
			array(
				$pr_item1,
				$pr_item2
			),
			$stats_arr
		);

		$expected = array(
			'issues'        => array(
				100 => array(),
				110 => array(),
			),
			'skipped-files' => array(
				100 => array( 'issues' => array(), 'total' => 0 ),
				110 => array( 'issues' => array(), 'total' => 0 ),
			),
			'stats'         => array(
				VIPGOCI_STATS_PHPCS => array(
					100 => array(
						'error'   => 0,
						'warning' => 0,
						'info'    => 0,
					),
					110 => array(
						'error'   => 0,
						'warning' => 0,
						'info'    => 0,
					),
					// no hashes-api; not supposed to initialize that
				),

				VIPGOCI_STATS_LINT => array(
					100 => array(
						'error'   => 0,
						'warning' => 0,
						'info'    => 0,
					),
					110 => array(
						'error'   => 0,
						'warning' => 0,
						'info'    => 0,
					),
					// no hashes-api; not supposed to initialize that
				),
			),
		);

		return $this->assertSame(
			$expected,
			$stats_arr
		);
	}
}

