<?php
/**
 * Test vipgoci_irc_api_alert_queue().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test IRC queue function.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OtherWebServicesIrcApiAlertQueueTest extends TestCase {
	/**
	 * Require correct file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
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
	 * Test if IRC queue functions correctly.
	 *
	 * @covers ::vipgoci_irc_api_alert_queue
	 *
	 * @return void
	 */
	public function testIrcQueue1() :void {
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
