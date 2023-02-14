<?php
/**
 * Test function vipgoci_options_get_starting_with().
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
final class OptionsGetStartingWithTest extends TestCase {
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
	 * @covers ::vipgoci_options_get_starting_with
	 *
	 * @return void
	 */
	public function testOptionsStartingWith() :void {
		$expected = array(
			'test0' => 't0',
			'test2' => 't9',
		);

		$result = vipgoci_options_get_starting_with(
			array(
				'test1'  => 't1',
				'test0'  => 't0',
				'test2'  => 't9',
				'atest3' => '999',
				'atest4' => '888',
				'atest0' => '777',
			),
			'test',
			array(
				'test1',
			)
		);

		$this->assertsame(
			$expected,
			$result
		);
	}
}
