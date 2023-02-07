<?php
/**
 * Test function vipgoci_convert_string_to_type().
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
final class MiscConvertStringToTypeTest extends TestCase {
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
	 * @covers ::vipgoci_convert_string_to_type
	 *
	 * @return void
	 */
	public function testConvert1() :void {
		$this->assertSame(
			true,
			vipgoci_convert_string_to_type( 'true' )
		);

		$this->assertSame(
			false,
			vipgoci_convert_string_to_type( 'false' )
		);

		$this->assertSame(
			null,
			vipgoci_convert_string_to_type( 'null' )
		);

		$this->assertSame(
			'somestring',
			vipgoci_convert_string_to_type( 'somestring' )
		);
	}
}
