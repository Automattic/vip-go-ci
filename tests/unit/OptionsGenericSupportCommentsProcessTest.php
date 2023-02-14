<?php
/**
 * Test function vipgoci_option_generic_support_comments_process().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OptionsGenericSupportCommentsProcessTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../misc.php';
		require_once __DIR__ . './../../options.php';

		$this->options = array();
	}

	/**
	 * Teardown function. Clean up.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_generic_support_comments_process
	 *
	 * @return void
	 */
	public function testOptionGenericSupportCommentProcessBoolean() :void {
		$this->options['myoption1'] =
			'1:false|||5:true|||10:false|||15:trUE';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption1',
			'boolean'
		);

		$this->assertSame(
			array(
				1  => false,
				5  => true,
				10 => false,
				15 => true,
			),
			$this->options['myoption1']
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_generic_support_comments_process
	 *
	 * @return void
	 */
	public function testOptionGenericSupportCommentProcessStringStringNotLower() :void {
		$this->options['myoption2'] =
			'3:bar|||6:foo|||9:bar|||12:foo|||15:false|||20:AbCdEfG';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption2',
			'string',
			false
		);

		$this->assertSame(
			array(
				3  => 'bar',
				6  => 'foo',
				9  => 'bar',
				12 => 'foo',
				15 => 'false',
				20 => 'AbCdEfG',
			),
			$this->options['myoption2']
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_generic_support_comments_process
	 *
	 * @return void
	 */
	public function testOptionGenericSupportCommentProcessStringStringLower() :void {
		$this->options['myoption2'] =
			'3:bar|||6:foo|||9:bar|||12:foo|||15:false|||20:AbCdEfG';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption2',
			'string',
			true
		);

		$this->assertSame(
			array(
				3  => 'bar',
				6  => 'foo',
				9  => 'bar',
				12 => 'foo',
				15 => 'false',
				20 => 'abcdefg',
			),
			$this->options['myoption2']
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_generic_support_comments_process
	 *
	 * @return void
	 */
	public function testOptionGenericSupportCommentProcessArrayNotLower() :void {
		$this->options['myoption3'] =
			'3:foo,bar,test|||6:test,foo,foo|||9:aaa,bbb,ccc|||12:ddd|||15:|||20:AbCdEfG';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption3',
			'array',
			false
		);

		$this->assertSame(
			array(
				3  => array( 'foo', 'bar', 'test' ),
				6  => array( 'test', 'foo', 'foo' ),
				9  => array( 'aaa', 'bbb', 'ccc' ),
				12 => array( 'ddd' ),
				15 => array(),
				20 => array( 'AbCdEfG' ),
			),
			$this->options['myoption3']
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_generic_support_comments_process
	 *
	 * @return void
	 */
	public function testOptionGenericSupportCommentProcessArrayLower() :void {
		$this->options['myoption3'] =
			'3:foo,bar,test|||6:test,foo,foo|||9:aaa,bbb,ccc|||12:ddd|||15:|||20:AbCdEfG';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption3',
			'array',
			true
		);

		$this->assertSame(
			array(
				3  => array( 'foo', 'bar', 'test' ),
				6  => array( 'test', 'foo', 'foo' ),
				9  => array( 'aaa', 'bbb', 'ccc' ),
				12 => array( 'ddd' ),
				15 => array(),
				20 => array( 'abcdefg' ),
			),
			$this->options['myoption3']
		);
	}
}
