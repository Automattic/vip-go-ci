<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class OptionsGenericSupportCommentsProcess extends TestCase {
	public function setUp() {
		$this->options = array();
	}

	public function tearDown() {
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_process
	 */
	public function testOptionGenericSupportCommentProcessBoolean() {
		$this->options['myoption1'] =
			'1:false|||5:true|||10:false|||15:true';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption1',
			'boolean'
		);

		$this->assertEquals(
			array(
				1	=> false,
				5	=> true,
				10	=> false,
				15	=> true,
			),
			$this->options['myoption1']
		);
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_process
	 */
	public function testOptionGenericSupportCommentProcessStringString() {
		$this->options['myoption2'] =
			'3:bar|||6:foo|||9:bar|||12:foo|||15:false';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption2',
			'string'
		);

		$this->assertEquals(
			array(
				3	=> 'bar',
				6	=> 'foo',
				9	=> 'bar',
				12	=> 'foo',
				15	=> 'false',
			),
			$this->options['myoption2']
		);
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_process
	 */
	public function testOptionGenericSupportCommentProcessArray() {
		$this->options['myoption3'] =
			'3:foo,bar,test|||6:test,foo,foo|||9:aaa,bbb,ccc|||12:ddd|||15:';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption3',
			'array'
		);

		$this->assertEquals(
			array(
				3	=> array(
					'foo', 'bar', 'test'
				),
				6	=> array(
					'test', 'foo', 'foo',
				),
				9	=> array(
					'aaa', 'bbb', 'ccc',
				),
				12	=> array(
					'ddd',
				),
				15	=> array(				
				)
			),
			$this->options['myoption3']
		);
	}
}
