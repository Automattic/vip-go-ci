<?php
/**
 * Test function vipgoci_find_fields_in_array().
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
final class MiscFindFieldsInArrayTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_find_fields_in_array
	 *
	 * @return void
	 */
	public function testFindFields1() :void {
		$this->assertSame(
			array(
				0 => false,
				1 => true,
				2 => true,
				3 => true,
				4 => false,
				5 => false,
				6 => false,
				7 => false,
			),
			vipgoci_find_fields_in_array(
				array(
					'a' => array(
						920,
						100000,
					),
					'b' => array(
						700,
					),
				),
				array(
					array(
						'a' => 920,
						'b' => 500,
						'c' => 0,
						'd' => 1,
					),
					array(
						'a' => 920,
						'b' => 700,
						'c' => 0,
						'd' => 2,
					),
					array(
						'a' => 100000,
						'b' => 700,
						'c' => 0,
						'd' => 2,
					),
					array(
						'a' => 920,
						'b' => 700,
						'c' => 0,
						'd' => 2,
					),
					array(
						'a' => 900,
						'b' => 720,
						'c' => 0,
						'd' => 2,
					),
					array(
						'a' => 900,
					),
					array(
						'b' => 900,
					),
					array(
						'c' => 920,
						'd' => 700,
					),
				)
			)
		);
	}
}
