<?php
/**
 * Test vipgoci_option_bool_handle() function.
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
final class OptionsBoolHandleTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	public function setUp() :void {
		require_once __DIR__ . '/../../options.php';
	}

	/**
	 * Test common usage, no option value specified.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_option_bool_handle
	 */
	public function testOptionsBoolHandle1() :void {
		$options = array();

		vipgoci_option_bool_handle(
			$options,
			'mytestoption',
			'false'
		);

		$this->assertSame(
			false,
			$options['mytestoption']
		);
	}

	/**
	 * Test usage with option value specified.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_option_bool_handle
	 */
	public function testOptionsBoolHandle2() :void {
		$options = array(
			'mytestoption' => 'false',
		);

		vipgoci_option_bool_handle(
			$options,
			'mytestoption',
			'false'
		);

		$this->assertSame(
			false,
			$options['mytestoption']
		);
	}

	/**
	 * Test usage with option value specified.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_option_bool_handle
	 */
	public function testOptionsBoolHandle3() :void {
		$options = array(
			'mytestoption' => 'true',
		);

		vipgoci_option_bool_handle(
			$options,
			'mytestoption',
			'true'
		);

		$this->assertSame(
			true,
			$options['mytestoption']
		);
	}
}
