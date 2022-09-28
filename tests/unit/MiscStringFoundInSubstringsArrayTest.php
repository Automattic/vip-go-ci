<?php
/**
 * Test vipgoci_string_found_in_substrings_array() function.
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
final class MiscStringFoundInSubstringsArrayTest extends TestCase {
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
	 * @covers ::vipgoci_string_found_in_substrings_array
	 *
	 * @return void
	 */
	public function testFoundSubstringInArray1(): void {
		$this->assertTrue(
			vipgoci_string_found_in_substrings_array(
				array(
					'teststring1',
					'teststring2',
					'teststring3',
					'otherstring',
				),
				'test',
				true
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_string_found_in_substrings_array
	 *
	 * @return void
	 */
	public function testFoundSubstringInArray2(): void {
		$this->assertTrue(
			vipgoci_string_found_in_substrings_array(
				array(
					'TESTstring1',
					'TESTstring2',
					'TESTstring3',
					'otherstring',
				),
				'test',
				true
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_string_found_in_substrings_array
	 *
	 * @return void
	 */
	public function testFoundSubstringInArray3(): void {
		$this->assertFalse(
			vipgoci_string_found_in_substrings_array(
				array(
					'teststring1',
					'teststring2',
					'teststring3',
					'otherstring',
				),
				'string2',
				true
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_string_found_in_substrings_array
	 *
	 * @return void
	 */
	public function testFoundSubstringInArray4(): void {
		$this->assertFalse(
			vipgoci_string_found_in_substrings_array(
				array(
					'teststring1',
					'teststring2',
					'teststring3',
					'otherstring',
				),
				'invalid',
				true
			)
		);
	}


	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_string_found_in_substrings_array
	 *
	 * @return void
	 */
	public function testFoundSubstringInArray5(): void {
		$this->assertTrue(
			vipgoci_string_found_in_substrings_array(
				array(
					'teststring1',
					'teststring2',
					'teststring3',
					'otherstring',
				),
				'test',
				false
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_string_found_in_substrings_array
	 *
	 * @return void
	 */
	public function testFoundSubstringInArray6(): void {
		$this->assertTrue(
			vipgoci_string_found_in_substrings_array(
				array(
					'TESTstring1',
					'TESTstring2',
					'TESTstring3',
					'otherstring',
				),
				'test',
				false
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_string_found_in_substrings_array
	 *
	 * @return void
	 */
	public function testFoundSubstringInArray7(): void {
		$this->assertTrue(
			vipgoci_string_found_in_substrings_array(
				array(
					'teststring1',
					'teststring2',
					'teststring3',
					'otherstring',
				),
				'string2',
				false
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_string_found_in_substrings_array
	 *
	 * @return void
	 */
	public function testFoundSubstringInArray8(): void {
		$this->assertFalse(
			vipgoci_string_found_in_substrings_array(
				array(
					'teststring1',
					'teststring2',
					'teststring3',
					'otherstring',
				),
				'invalid',
				false
			)
		);
	}
}
