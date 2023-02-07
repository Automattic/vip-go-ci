<?php
/**
 * Test function vipgoci_option_integer_handle().
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
final class OptionsIntegerHandleTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../options.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_integer_handle
	 *
	 * @return void
	 */
	public function testOptionsIntegerHandle1() :void {
		$options = array();

		vipgoci_option_integer_handle(
			$options,
			'mytestoption',
			5
		);

		$this->assertSame(
			array(
				'mytestoption' => 5,
			),
			$options
		);
	}
}
