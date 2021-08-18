<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class SysExitTest extends TestCase {
	protected function setUp(): void {
		global $_vipgoci_sysexit_test;

		$_vipgoci_sysexit_test = true;

		vipgoci_irc_api_alert_queue( null, true ); // Empty IRC queue
	}

	protected function tearDown(): void {
		global $_vipgoci_sysexit_test;

		$_vipgoci_sysexit_test = false;

		unset( $_vipgoci_sysexit_test );
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
			VIPGOCI_EXIT_CODE_ISSUES,
			true
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		/*
		 * Check for correct exit status
		 */
		$this->assertSame(
			VIPGOCI_EXIT_CODE_ISSUES,
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
