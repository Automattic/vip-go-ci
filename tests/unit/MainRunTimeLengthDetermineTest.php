<?php
/**
 * Test vipgoci_run_time_length_determine() function.
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
final class MainRunTimeLengthDetermineTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../statistics.php';
		require_once __DIR__ . '/../../main.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @param int    $length           Length.
	 * @param string $expected_counter Expected counter.
	 *
	 * @dataProvider dataDetermineRunTimeLength
	 *
	 * @covers ::vipgoci_run_time_length_determine
	 *
	 * @return void
	 */
	public function testDetermineRunTimeLength(
		int $length,
		string $expected_counter
	): void {
		vipgoci_run_time_length_determine(
			$length
		);

		$counter_report = vipgoci_counter_report(
			VIPGOCI_COUNTERS_DUMP,
			null,
			null
		);

		$this->assertSame(
			array( 'vipgoci_runtime_' . $expected_counter => 1 ),
			$counter_report,
			'Run time length ' . $length . ' did not result in counter increment for vipgoci_runtime_' . $expected_counter
		);
	}

	/**
	 * Data provider for testDetermineRunTimeLength() function.
	 *
	 * @return array
	 */
	public function dataDetermineRunTimeLength(): array {
		return array(
			array( 1, 'short' ),
			array( 60, 'short' ),
			array( 119, 'short' ),
			array( 120, 'medium' ),
			array( 239, 'medium' ),
			array( 240, 'long' ),
			array( 500, 'long' ),
		);
	}
}
