<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/helper/IndicateTestId.php';

require_once __DIR__ . './../../defines.php';
require_once __DIR__ . './../../main.php';
require_once __DIR__ . './../../log.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if skip execution functionality works.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunScanSkipExecutionTest extends TestCase {
	/**
	 * Set up variable, indicate what test is running.
	 */
	protected function setUp(): void {
		/*
		 * Indicate that this particular test is running,
		 * needed so that vipgoci_sysexit() can return
		 * instead of exiting. See the function itself.
		 */
		vipgoci_unittests_indicate_test_id( 'MainRunScanSkipExecutionTest' );

		$this->options = array();
	}

	/**
	 * Remove the indication and clear variable.
	 */
	protected function tearDown(): void {
		vipgoci_unittests_remove_indication_for_test_id( 'MainRunScanSkipExecutionTest' );

		unset( $this->options );
	}

	/**
	 * Check if vipgoci_run_scan_skip_execution() attempts to
	 * exit.
	 *
	 * @covers ::vipgoci_run_scan_skip_execution
	 */
	public function testRunScanSkipExecution() :void {
		$this->options['skip-execution'] = true;

		ob_start();

		vipgoci_run_scan_skip_execution(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		/*
		 * Check if expected string was printed
		 */
		$printed_data_found = strpos(
			$printed_data,
			'"Skipping scanning entirely, as determined by configuration";'
		);

		$this->assertNotFalse(
			$printed_data_found
		);
	}

	/**
	 * Check if vipgoci_run_scan_skip_execution() returns
	 * with exit-status.
	 *
	 * @covers ::vipgoci_run_scan_skip_execution
	 */
	public function testRunScanDoesNotSkipExecution() :void {
		$this->options['skip-execution'] = false;

		ob_start();

		vipgoci_run_scan_skip_execution(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		/*
		 * Check if nothing was printed.
		 */
		$this->assertEmpty(
			$printed_data
		);
	}
}
