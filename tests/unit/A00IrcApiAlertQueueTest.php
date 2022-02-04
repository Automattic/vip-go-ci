<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;


use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class A00IrcApiAlertQueueTest extends TestCase {
	public function setUp() :void {
		/*
		 * Ensure this file is required on execution
		 * of the test itself. This test is run in separate
		 * process so other tests are unaffected 
		 * by this require. This is needed to ensure function
		 * declarations are not attempted multiple times.
		 */
		require_once( __DIR__ . '/../../other-web-services.php' );
	}

	/**
	 * @covers ::vipgoci_irc_api_alert_queue
	 */
	public function testIrcQueue1() {
		vipgoci_irc_api_alert_queue(
			'mymessage1'
		);

		vipgoci_irc_api_alert_queue(
			'mymessage2'
		);

		$queue = vipgoci_irc_api_alert_queue(
			null,
			true
		);

		$this->assertSame(
			array(
				'mymessage1',
				'mymessage2',
			),
			$queue
		);
	}
}
