<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . './../../other-web-services.php';

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class A00IrcApiAlertQueueTest extends TestCase {
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
