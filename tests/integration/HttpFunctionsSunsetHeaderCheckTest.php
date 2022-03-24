<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class HttpFunctionsSunsetHeaderCheckTest extends TestCase {
	protected function setUp(): void {
		vipgoci_irc_api_alert_queue( null, true ); // Empty IRC queue
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

		$found = vipgoci_unittests_check_irc_api_alert_queue(
			'Warning: Sunset HTTP header detected, feature will become unavailable'
		);

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

		$found = vipgoci_unittests_check_irc_api_alert_queue(
			'Warning: Sunset HTTP header detected, feature will become unavailable'
		);

		$this->assertFalse(
			$found
		);
	}
}
