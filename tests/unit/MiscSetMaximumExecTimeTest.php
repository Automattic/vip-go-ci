<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/helper/IndicateTestId.php';
require_once __DIR__ . '/helper/CheckPcntlSupport.php';
require_once __DIR__ . '/../integration/IncludesForTestsOutputControl.php';

require_once __DIR__ . '/../../defines.php';
require_once __DIR__ . '/../../misc.php';
require_once __DIR__ . '/../../log.php';

use PHPUnit\Framework\TestCase;

/**
 * Run tests in separate process to ensure
 * static value in vipgoci_set_maximum_exec_time() is
 * not inherited from another test.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MiscSetMaximumExecTimeTest extends TestCase {
	/**
	 * Require file and set indication.
	 */
	protected function setUp(): void {
		/*
		 * Indicate that this particular test is running,
		 * needed so that vipgoci_sysexit() can return
		 * instead of exiting. See the function itself.
		 */
		vipgoci_unittests_indicate_test_id( 'MiscSetMaximumExecTimeTest' );

		/*
		 * Ensure this file is required on execution
		 * of the test itself. This test is run in separate
		 * process so other tests are unaffected
		 * by this require. This is needed to ensure function
		 * declarations are not attempted multiple times.
		 */
		require_once __DIR__ . '/../../other-web-services.php';
	}

	/**
	 * Remove indication.
	 */
	protected function tearDown(): void {
		vipgoci_unittests_remove_indication_for_test_id( 'MiscSetMaximumExecTimeTest' );
	}

	/**
	 * Check if a particular string was outputted
	 * indicating that alarm was raised.
	 *
	 * @covers ::vipgoci_set_maximum_exec_time
	 */
	public function testSetMaxExecTime1() :void {
		if ( ! vipgoci_unittests_pcntl_supported() ) {
			$this->markTestSkipped(
				'PCNTL support is missing'
			);

			return;
		}

		ob_start();

		// Set alarm in 8 seconds.
		vipgoci_set_maximum_exec_time( 8, 'MAX_EXEC_ALARM_ABCDE' );

		// Wait for 2 seconds, alarm should not trigger meanwhile.
		sleep( 2 );

		// String should not have been printed.
		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		// Check if expected string was not printed.
		$printed_data_found = strpos(
			$printed_data,
			'MAX_EXEC_ALARM_ABCDE'
		);

		$this->assertFalse(
			$printed_data_found
		);

		ob_start();

		// Now wait for 10 seconds, alarm should trigger meanwhile.
		sleep( 10 );

		// String should have been printed.
		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		// Check if expected string was printed.
		$printed_data_found = strpos(
			$printed_data,
			'MAX_EXEC_ALARM_ABCDE'
		);

		$this->assertNotFalse(
			$printed_data_found
		);
	}
}
