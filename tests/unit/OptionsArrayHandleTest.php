<?php
/**
 * Test vipgoci_option_array_handle() function, which
 * parses options and places into an array.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements all the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OptionsArrayHandleTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../options.php';
	}

	/**
	 * Test when option is an empty string.
	 *
	 * @covers ::vipgoci_option_array_handle
	 *
	 * @return void
	 */
	public function testOptionsArrayHandle1() :void {
		$options = array(
			'mytestoption' => '',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			array(),
			null,
			','
		);

		$this->assertSame(
			array(),
			$options['mytestoption']
		);
	}

	/**
	 * Test when option is empty.
	 *
	 * @covers ::vipgoci_option_array_handle
	 *
	 * @return void
	 */
	public function testOptionsArrayHandle2() :void {
		$options = array();

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			array( 'myvalue' ),
			null,
			','
		);

		$this->assertSame(
			array(
				'myvalue',
			),
			$options['mytestoption']
		);
	}

	/**
	 * Test when there are multiple values, comma separated,
	 * and comma is the separator.
	 *
	 * @covers ::vipgoci_option_array_handle
	 *
	 * @return void
	 */
	public function testOptionsArrayHandle3() :void {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,MYVALUE3',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			'myvalue',
			null,
			','
		);

		$this->assertSame(
			array(
				'myvalue1',
				'myvalue2',
				'myvalue3', // Should be transformed to lower-case by default.
			),
			$options['mytestoption']
		);
	}

	/**
	 * Test if array is handled just like
	 * in the test above, but values should
	 * not be transformed to lower case.
	 *
	 * @covers ::vipgoci_option_array_handle
	 *
	 * @return void
	 */
	public function testOptionsArrayHandle4() :void {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,MYVALUE3',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			'myvalue',
			null,
			',',
			false // Do not strtolower() values.
		);

		$this->assertSame(
			array(
				'myvalue1',
				'myvalue2',
				'MYVALUE3',
			),
			$options['mytestoption']
		);
	}
}
