<?php
/**
 * Test vipgoci_irc_api_alert_queue_unique().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test if IRC queue returns unique entries on flush.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OtherWebServicesIrcApiAlertQueueUniqueTest extends TestCase {
	/**
	 * Require correct file.
	 *
	 * @return void
	 */
	public function setUp() :void {
		/*
		 * Ensure this file is required on execution
		 * of the test itself. This test is run in separate
		 * process so other tests are unaffected
		 * by this require. This is needed to ensure function
		 * declarations are not attempted multiple times.
		 */
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../statistics.php';
		require_once __DIR__ . '/../../other-web-services.php';
	}

	/**
	 * Test integrity first.
	 *
	 * @covers: vipgoci_irc_api_alert_queue_unique
	 *
	 * @return void
	 */
	public function testIrcQueueUnique1() :void {
		$msg_queue = array(
			'Msg 1',
			'Msg 2',
			'Msg 3',
		);

		$msg_queue_new = vipgoci_irc_api_alert_queue_unique(
			$msg_queue
		);

		$this->assertSame(
			array(
				'Msg 1',
				'Msg 2',
				'Msg 3',
			),
			$msg_queue_new
		);
	}

	/**
	 * Test if entries are made unique and prefixed correctly.
	 *
	 * @covers: vipgoci_irc_api_alert_queue_unique
	 *
	 * @return void
	 */
	public function testIrcQueueUnique2() :void {
		$msg_queue = array(
			'Msg 1',
			'Msg 2',
			'Msg 3',
			'Msg 3',
			'Msg 3',
			'Msg 3',
		);

		$msg_queue_new = vipgoci_irc_api_alert_queue_unique(
			$msg_queue
		);

		$this->assertSame(
			array(
				'Msg 1',
				'Msg 2',
				'(4x) Msg 3',
			),
			$msg_queue_new
		);
	}

	/**
	 * Test if entries are made unique and prefixed correctly.
	 *
	 * @covers: vipgoci_irc_api_alert_queue_unique
	 *
	 * @return void
	 */
	public function testIrcQueueUnique3() :void {
		$msg_queue = array(
			'Msg 1',
			'Msg 2',
			'Msg 2',
			'Msg 2',
			'Msg 2',
			'Msg 3',
			'Msg 3',
		);

		$msg_queue_new = vipgoci_irc_api_alert_queue_unique(
			$msg_queue
		);

		$this->assertSame(
			array(
				'Msg 1',
				'(4x) Msg 2',
				'(2x) Msg 3',
			),
			$msg_queue_new
		);
	}
}
