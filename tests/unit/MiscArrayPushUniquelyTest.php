<?php
/**
 * Test vipgoci_array_push_uniquely() function.
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
final class MiscArrayPushUniquelyTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_array_push_uniquely
	 *
	 * @return void
	 */
	public function testArrayPushUniquely(): void {
		$arr = array();

		vipgoci_array_push_uniquely(
			$arr,
			'test1'
		);

		vipgoci_array_push_uniquely(
			$arr,
			'test1'
		);

		vipgoci_array_push_uniquely(
			$arr,
			'test1'
		);

		vipgoci_array_push_uniquely(
			$arr,
			'test2'
		);

		vipgoci_array_push_uniquely(
			$arr,
			'test2'
		);

		vipgoci_array_push_uniquely(
			$arr,
			'test3'
		);

		vipgoci_array_push_uniquely(
			$arr,
			'test4'
		);

		$this->assertSame(
			array(
				'test1',
				'test2',
				'test3',
				'test4',
			),
			$arr
		);
	}
}
