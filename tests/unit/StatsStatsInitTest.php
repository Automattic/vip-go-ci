<?php
/**
 * Test vipgoci_stats_init() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class StatsStatsInitTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../statistics.php';
	}

	/**
	 * Test the vipgoci_stats_init() function with fairly typical values.
	 *
	 * @covers ::vipgoci_stats_init
	 *
	 * @return void
	 */
	public function testStatsInit() :void {
		$pr_item1         = new stdClass();
		$pr_item1->number = 100;

		$pr_item2         = new stdClass();
		$pr_item2->number = 110;

		$stats_arr = array();

		vipgoci_stats_init(
			array(
				'phpcs'      => true,
				'lint'       => true,
				'test-api'   => false,
				'wpscan-api' => true,
			),
			array(
				$pr_item1,
				$pr_item2,
			),
			$stats_arr
		);

		$expected = array(
			'issues'        => array(
				100 => array(),
				110 => array(),
			),
			'skipped-files' => array(
				100 => array(
					'issues' => array(),
					'total'  => 0,
				),
				110 => array(
					'issues' => array(),
					'total'  => 0,
				),
			),
			'stats'         => array(
				VIPGOCI_STATS_PHPCS      => array(
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
				),
				VIPGOCI_STATS_LINT       => array(
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
					// no test-api; not supposed to initialize that.
				),

				/*
				 * No hashes-api; is not supposed to initialize that.
				 */

				VIPGOCI_STATS_WPSCAN_API => array(
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
					// no test-api; not supposed to initialize that.
				),
			),
		);

		$this->assertSame(
			$expected,
			$stats_arr
		);
	}
}

