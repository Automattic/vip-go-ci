<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/helper/IndicateTestId.php';
require_once __DIR__ . '/helper/CheckIrcApiAlertQueue.php';

require_once __DIR__ . './../../defines.php';
require_once __DIR__ . './../../log.php';

use PHPUnit\Framework\TestCase;

/**
 * Test if vipgoci_sysexit() function works correctly.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MiscSysExitTest extends TestCase {
	/**
	 * Require file, clear IRC queue, and set up indication.
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../other-web-services.php';

		vipgoci_unittests_indicate_test_id( 'MiscSysExitTest' );

		vipgoci_irc_api_alert_queue( null, true ); // Empty IRC queue.
	}

	/**
	 * Remove indication.
	 */
	protected function tearDown(): void {
		vipgoci_unittests_remove_indication_for_test_id( 'MiscSysExitTest' );
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
			false !== $printed_data_found
		);

		$printed_data_found = strpos(
			$printed_data,
			'"key1": "value1"'
		);

		$this->assertTrue(
			false !== $printed_data_found
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
