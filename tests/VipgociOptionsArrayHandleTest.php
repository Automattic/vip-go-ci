<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociOptionsArrayHandleTest extends TestCase {
	/**
	 * @covers ::vipgoci_option_array_handle
	 */
	public function testOptionsArrayHandle1() {
		$options = array(
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			array( 'myvalue' ),
			null,
			','
		);

		$this->assertEquals(
			$options['mytestoption'],
			array(
				'myvalue',
			)
		);
	}

	/**
	 * @covers ::vipgoci_option_array_handle
	 */
	public function testOptionsArrayHandle2() {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,myvalue3',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			'myvalue',
			null,
			','
		);

		$this->assertEquals(
			$options['mytestoption'],
			array(
				'myvalue1',
				'myvalue2',
				'myvalue3',
			)
		);
	}
}
