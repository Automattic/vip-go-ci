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
final class StatisticsRuntimeMeasureShellExecWithRetryTest extends TestCase {
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
	 * @covers ::vipgoci_runtime_measure_shell_exec_with_retry
	 */
	public function testShellExecRuntimeMeasure() :void {
		vipgoci_unittests_output_suppress();

		$return = vipgoci_runtime_measure_shell_exec_with_retry(
			'sleep 1 ; echo -n "test_string"',
			'mytimer10'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'test_string',
			$return
		);

		$runtime_stats = vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_DUMP
		);

		$this->assertGreaterThanOrEqual(
			1,
			$runtime_stats['mytimer10']
		);
	}

	/**
	 * Test if the function retries execution up on failure
	 * and if the output is correct when that occurs. Will also
	 * check if the function really attempts retries.
	 *
	 * @covers ::vipgoci_runtime_measure_shell_exec_with_retry
	 */
	public function testShellExecRetry() :void {
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

		vipgoci_unittests_output_suppress();

		$return = vipgoci_runtime_measure_shell_exec_with_retry(
			escapeshellcmd( 'php ' ) . escapeshellcmd( $path_to_cli ) . ' ' . escapeshellarg( $path_to_temp_for_cli ) . ' 2>/dev/null',
			'mytimer20',
			2 // Retry twice (three times in total).
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'Success' . PHP_EOL,
			$return
		);

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
}

