<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class HttpRespSunsetHeaderCheckTest extends TestCase {
	protected function setUp(): void {
		vipgoci_irc_api_alert_queue( null, true ); // Empty IRC queue
	}

	private function _searchIrcMsgQueue() :bool {
		$found = false;

		$irc_msg_queue = vipgoci_irc_api_alert_queue( null, true );

		foreach( $irc_msg_queue as $irc_msg_queue_item ) {
			if ( false !== strpos(
				$irc_msg_queue_item,
				'Warning: Sunset HTTP header detected, feature will become unavailable'
			) ) {
				$found = true;
			}
		}

		return $found;
	}

	/**
	 * @covers ::vipgoci_http_resp_sunset_header_check
	 */
	public function testSunsetHeaderExists() {
		vipgoci_unittests_output_suppress();

		/*
		 * Do a header check, test if anything ends in IRC queue.
		 */
		vipgoci_http_resp_sunset_header_check(
			'https://mytest.localdomain:5000/test/foo?test1=test2',
			array(
				'test1'		=> 'data',
				'test2'		=> 'data2',
				'sunset'	=> 'Tue 10 Aug 17:21:00 GMT 2051',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$found = $this->_searchIrcMsgQueue();

		$this->assertTrue(
			$found
		);
	}

	/**
	 * @covers ::vipgoci_http_resp_sunset_header_check
	 */
	public function testSunsetHeaderNotExisting() {
		vipgoci_unittests_output_suppress();

		/*
		 * Do a header check, test if anything ends in IRC queue.
		 */
		vipgoci_http_resp_sunset_header_check(
			'https://mytest.localdomain:5000/test/foo?test1=test2',
			array(
				'test1'		=> 'data',
				'test2'		=> 'data2',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$found = $this->_searchIrcMsgQueue();

		$this->assertFalse(
			$found
		);
	}
}
