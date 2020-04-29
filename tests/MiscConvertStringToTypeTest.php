<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscConvertStringToTypeTest extends TestCase {
	/**
	 * @covers ::vipgoci_convert_string_to_type
	 */
	public function testConvert1() {
		$this->assertEquals(
			true,
			vipgoci_convert_string_to_type('true')
		);

		$this->assertEquals(
			false,
			vipgoci_convert_string_to_type('false')
		);

		$this->assertEquals(
			null,
			vipgoci_convert_string_to_type('null')
		);

		$this->assertEquals(
			'somestring',
			vipgoci_convert_string_to_type('somestring')
		);
	}
}
