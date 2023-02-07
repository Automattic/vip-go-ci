<?php
/**
 * Test function vipgoci_runtime_measure().
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
final class StatisticsRuntimeMeasureTest extends TestCase {
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
	 * Test invalid usage of the function.
	 *
	 * @covers ::vipgoci_runtime_measure
	 *
	 * @return void
	 */
	public function testRuntimeMeasure1() :void {
		$this->assertSame(
			false,
			vipgoci_runtime_measure( 'illegalaction', 'mytimer1' )
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_runtime_measure
	 *
	 * @return void
	 */
	public function testRuntimeMeasure2() :void {
		$this->assertSame(
			false,
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'mytimer2' )
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_runtime_measure
	 *
	 * @return void
	 */
	public function testRuntimeMeasure3() :void {
		$this->assertSame(
			true,
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'mytimer3' )
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
			$runtime_stats['mytimer3']
		);

		$this->assertSame(
			true,
			vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'mytimer4' )
		);

		sleep( 2 );

		$runtime_stats = vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_DUMP
		);

		$this->assertGreaterThanOrEqual(
			1,
			$runtime_stats['mytimer3']
		);
	}
}

