<?php
/**
 * Test function vipgoci_file_extension_get().
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
final class MiscFileExtensionTest extends TestCase {
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
	 * @covers ::vipgoci_file_extension_get
	 *
	 * @return void
	 */
	public function testFileExtension1() :void {
		$file_name = 'myfile.exe';

		$file_extension = vipgoci_file_extension_get(
			$file_name
		);

		$this->assertSame(
			'exe',
			$file_extension
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_file_extension_get
	 *
	 * @return void
	 */
	public function testFileExtension2() :void {
		$file_name = 'myfile.EXE';

		$file_extension = vipgoci_file_extension_get(
			$file_name
		);

		$this->assertSame(
			'exe',
			$file_extension
		);
	}

	/**
	 * Test without extension.
	 *
	 * @covers ::vipgoci_file_extension_get
	 *
	 * @return void
	 */
	public function testFileExtension3() :void {
		$file_name = 'myfile';

		$file_extension = vipgoci_file_extension_get(
			$file_name
		);

		$this->assertSame(
			null,
			$file_extension
		);
	}
}
