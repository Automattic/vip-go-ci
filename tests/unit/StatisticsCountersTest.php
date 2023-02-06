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
final class StatisticsCountersTest extends TestCase {
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
}

