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
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/helper/OptionsArrayHandle.php';
	}

	/**
	 * Teardown function.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
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
			array( 'myvalue' ),
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
			array( 'myvalue' ),
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

	/**
	 * Test forbidden values. No errors, as no forbidden value is used.
	 *
	 * @covers ::vipgoci_option_array_handle
	 *
	 * @return void
	 */
	public function testOptionsArrayHandle5() :void {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,MYVALUE3',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			array( 'myvalue' ),
			array( 'myvalue4' ),
			',',
			true // To lower case.
		);

		$this->assertSame(
			array(
				'myvalue1',
				'myvalue2',
				'myvalue3',
			),
			$options['mytestoption']
		);
	}

	/**
	 * Test forbidden values. No errors, as no forbidden value is used.
	 *
	 * @covers ::vipgoci_option_array_handle
	 *
	 * @return void
	 */
	public function testOptionsArrayHandle6() :void {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,MYVALUE3',
		);

		vipgoci_option_array_handle(
			$options,
			'mytestoption',
			array( 'myvalue' ),
			array( 'myvalue3' ), // Note: Different case than input, is allowed.
			',',
			false // Do not transform to lower case.
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

	/**
	 * Test forbidden values. Error, as forbidden value is used.
	 *
	 * @covers ::vipgoci_option_array_handle
	 *
	 * @return void
	 */
	public function testOptionsArrayHandle7() :void {
		$options = array(
			'mytestoption' => 'myvalue1,myvalue2,MYVALUE3',
		);

		$error_msg = '';

		try {
			vipgoci_option_array_handle(
				$options,
				'mytestoption',
				array( 'myvalue' ),
				array( 'myvalue3' ),
				',',
				true // Transform to lower case.
			);
		} catch ( \ErrorException $error ) {
			$error_msg = $error->getMessage();
		}

		$this->assertSame(
			'vipgoci_sysexit() was called; message=Parameter --mytestoption can not contain \'"myvalue3"\' as one of the values',
			$error_msg,
			'vipgoci_sysexit() not called when it should have'
		);
	}
}

