<?php
/**
 * Tests function that executes command in shell,
 * measures the time taken, and retries on
 * failure.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class StatisticsRuntimeMeasureExecWithRetryTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Test if execution time measured is correct
	 * and that output returned is correct
	 * as well.
	 *
	 * @covers ::vipgoci_runtime_measure_exec_with_retry
	 */
	public function testExecSuccessRuntimeMeasure() :void {
		$output_2    = '';
		$result_code = -255;

		vipgoci_unittests_output_suppress();

		$output = vipgoci_runtime_measure_exec_with_retry(
			'sleep 1 ; echo -n "test_string"',
			array( 0 ),
			$output_2,
			$result_code,
			'mytimer10'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'test_string',
			$output
		);

		$this->assertSame(
			'test_string',
			$output_2
		);

		$this->assertSame(
			0,
			$result_code
		);

		$runtime_stats = vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_DUMP
		);

		$this->assertTrue(
			( 1 <= $runtime_stats['mytimer10'] ) &&
			( 10 > $runtime_stats['mytimer10'] )
		);
	}

	/**
	 * Ensure that empty output results in an empty string
	 * and result code is correct.
	 *
	 * @covers: vipgoci_runtime_measure_exec_with_retry
	 */
	public function testExecSuccessWithEmptyReturn() :void {
		$output_2    = '';
		$result_code = -255;

		vipgoci_unittests_output_suppress();

		$output = vipgoci_runtime_measure_exec_with_retry(
			'echo -n ""',
			array( 0 ),
			$output_2,
			$result_code,
			'mytimer20'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'',
			$output
		);

		$this->assertSame(
			'',
			$output_2
		);

		$this->assertSame(
			0,
			$result_code
		);
	}

	/**
	 * Test if the function retries execution when status code
	 * is unexpected and if the output is correct when that occurs.
	 * Will also check if the function really attempts retries.
	 *
	 * @covers ::vipgoci_runtime_measure_exec_with_retry
	 */
	public function testExecRetry() :void {
		$path_to_cli = tempnam(
			sys_get_temp_dir(),
			'cli-predictably-fails-script'
		);

		$this->assertNotFalse(
			$path_to_cli
		);

		$this->assertTrue(
			copy(
				__DIR__ . '/helper-scripts/cli-predictably-fails.php',
				$path_to_cli
			)
		);

		$path_to_temp_for_cli = tempnam(
			sys_get_temp_dir(),
			'vipgoci-cli-predictably-fails-dir'
		);

		$this->assertNotFalse(
			$path_to_temp_for_cli
		);

		$this->assertNotFalse(
			unlink( $path_to_temp_for_cli )
		);

		$this->assertNotFalse(
			mkdir( $path_to_temp_for_cli )
		);

		$this->assertNotFalse(
			chmod( $path_to_temp_for_cli, 0700 )
		);

		$output_2    = '';
		$result_code = -255;

		vipgoci_unittests_output_suppress();

		$output = vipgoci_runtime_measure_exec_with_retry(
			escapeshellcmd( 'php' ) . ' ' .
				escapeshellcmd( $path_to_cli ) . ' ' .
				escapeshellarg( $path_to_temp_for_cli ) . ' 2>/dev/null',
			array( 0 ),
			$output_2,
			$result_code,
			'mytimer30',
			false, // Do not get standard error.
			true, // Retry when status code does not match expected ones.
			2 // Retry twice (three times in total).
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'Success' . PHP_EOL,
			$output
		);

		$this->assertSame(
			'Success' . PHP_EOL,
			$output_2
		);

		$this->assertSame(
			0,
			$result_code
		);

		/*
		 * Ensure all files that should have been
		 * created by helper script were really created.
		 * If not, it indicates that retries were not attempted.
		 * Remove these files afterwards.
		 */
		$this->assertSame(
			'0',
			file_get_contents( $path_to_temp_for_cli . DIRECTORY_SEPARATOR . '0' )
		);

		unlink( $path_to_temp_for_cli . DIRECTORY_SEPARATOR . '0' );

		$this->assertSame(
			'1',
			file_get_contents( $path_to_temp_for_cli . DIRECTORY_SEPARATOR . '1' )
		);

		unlink( $path_to_temp_for_cli . DIRECTORY_SEPARATOR . '1' );

		$this->assertFalse(
			@file_get_contents( $path_to_temp_for_cli . DIRECTORY_SEPARATOR . '2' ) // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		);

		unlink( $path_to_cli );

		rmdir( $path_to_temp_for_cli );
	}

	/**
	 * Test if the function returns correct status code on failure.
	 * Check if the output string obtained is correct.
	 *
	 * @covers ::vipgoci_runtime_measure_exec_with_retry
	 */
	public function testExecAlwaysFails() :void {
		$output_2    = '';
		$result_code = -255;

		vipgoci_unittests_output_suppress();

		$output = vipgoci_runtime_measure_exec_with_retry(
			'( echo -n "test123" ; exit 124 )',
			array( 0 ),
			$output_2,
			$result_code,
			'mytimer40',
			false, // Do not get standard error.
			true, // Retry when status code does not match expected ones.
			2 // Retry twice (three times in total).
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			null,
			$output
		);

		$this->assertSame(
			'test123',
			$output_2
		);

		$this->assertSame(
			124,
			$result_code
		);
	}

	/**
	 * Check if the output string obtained is correct, check if standard
	 * error is included.
	 *
	 * @covers ::vipgoci_runtime_measure_exec_with_retry
	 */
	public function testExecAlwaysFailsCheckStderr() :void {
		$output_2    = '';
		$result_code = -255;

		$path_to_cli = tempnam(
			sys_get_temp_dir(),
			'cli-prints-and-exits-script-'
		);

		$this->assertNotFalse(
			$path_to_cli
		);

		$this->assertTrue(
			copy(
				__DIR__ . '/helper-scripts/cli-prints-and-exits.php',
				$path_to_cli
			)
		);

		$this->assertNotFalse(
			chmod( $path_to_cli, 0700 )
		);

		vipgoci_unittests_output_suppress();

		$output = vipgoci_runtime_measure_exec_with_retry(
			$path_to_cli,
			array( 125 ),
			$output_2,
			$result_code,
			'mytimer50',
			true, // Do get standard error.
			true, // Retry when status code does not match expected ones.
			2 // Retry twice (three times in total).
		);

		vipgoci_unittests_output_unsuppress();

		unlink( $path_to_cli );

		$this->assertSame(
			'test123test456',
			$output
		);

		$this->assertSame(
			'test123test456',
			$output_2
		);

		$this->assertSame(
			125,
			$result_code
		);
	}
}

