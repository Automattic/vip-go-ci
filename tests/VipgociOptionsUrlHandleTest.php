<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociOptionsUrlHandleTest extends TestCase {
	/**
	 * @covers ::vipgoci_option_url_handle
	 */
	public function testOptionsUrlHandle1() {
		$options = array(
		);

		vipgoci_option_url_handle(
			$options,
			'mytestoption',
			5
		);

		$this->assertEquals(
			$options,
			array(
				'mytestoption'	=> 5
			)
		);
	}
}
