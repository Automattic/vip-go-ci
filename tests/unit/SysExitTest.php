<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . '/helper/IndicateTestId.php' );
require_once( __DIR__ . '/helper/CheckIrcApiAlertQueue.php' );

require_once( __DIR__ . './../../defines.php' );
require_once( __DIR__ . './../../misc.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class SysExitTest extends TestCase {
	protected function setUp(): void {
		/*
		 * Ensure this file is required on execution
		 * of the test itself. This test is run in separate
		 * process so other tests are unaffected 
		 * by this require. This is needed to ensure function
		 * declarations are not attempted multiple times.
		 */
		require_once( __DIR__ . '/../../other-web-services.php' );

		/*
		 * Indicate that this particular test is running,
		 * needed so that vipgoci_sysexit() can return
		 * with exit status instead of exiting. See the
		 * function itself.
		 */
		vipgoci_unittests_indicate_test_id( 'SysExitTest' );

		vipgoci_irc_api_alert_queue( null, true ); // Empty IRC queue
	}

	protected function tearDown(): void {
		/*
	 	 * We are no longer running the test,
		 * remove the indication.
		 */
		vipgoci_unittests_remove_indication_for_test_id( 'SysExitTest' );
	}

	/**
	 * Check if vipgoci_sysexit() returns
	 * with correct exit status, check
	 * if it prints the expected data, and
	 * if it logs to the IRC queue.
	 * 
	 * @covers ::vipgoci_sysexit
	 */
	public function testSysExit1() {
		ob_start();

		$sysexit_status = vipgoci_sysexit(
			'Missing parameter',
			array(
				'key1' => 'value1',
			),
			VIPGOCI_EXIT_USAGE_ERROR,
			true
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		/*
		 * Check for correct exit status
		 */
		$this->assertSame(
			VIPGOCI_EXIT_USAGE_ERROR,
			$sysexit_status
		);

		/*
		 * Check if expected string was printed
		 * as well as debug data.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Usage: Missing parameter;'
		);

		$this->assertTrue(
			$printed_data_found !== false
		);

		$printed_data_found = strpos(
			$printed_data,
			'"key1": "value1"'
		);

		$this->assertTrue(
			$printed_data_found !== false
		);

		/*
		 * Check IRC queue.
		 */
		$found_in_irc_msg_queue = vipgoci_unittests_check_irc_api_alert_queue(
			'Usage: Missing parameter'
		);

		$this->assertTrue(
			$found_in_irc_msg_queue
		);
	}
}
