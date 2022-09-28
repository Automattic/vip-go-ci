<?php
/**
 * Test vipgoci_directory_found_in_file_list() function.
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
final class MiscDirectoryFoundInFileListTest extends TestCase {
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
	 * @covers ::vipgoci_directory_found_in_file_list
	 *
	 * @return void
	 */
	public function testCommonUsage1(): void {
		$this->assertTrue(
			vipgoci_directory_found_in_file_list(
				array(
					'dir1/subdir1/file1.txt',
					'dir2/subdir2/file1.txt'
				),
				'dir2/subdir2'
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_directory_found_in_file_list
	 *
	 * @return void
	 */
	public function testCommonUsage2(): void {
		$this->assertTrue(
			vipgoci_directory_found_in_file_list(
				array(
					'dir1/subdir1/file1.txt',
					'dir2/subdir2/subsubdir3/file1.txt'
				),
				'dir2/subdir2'
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_directory_found_in_file_list
	 *
	 * @return void
	 */
	public function testCommonUsage3(): void {
		$this->assertFalse(
			vipgoci_directory_found_in_file_list(
				array(
					'dir1/subdir1/file1.txt',
					'dir2/subdir2/file1.txt'
				),
				'dir2/subdir'
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_directory_found_in_file_list
	 *
	 * @return void
	 */
	public function testCommonUsage4(): void {
		$this->assertFalse(
			vipgoci_directory_found_in_file_list(
				array(
					'dir1/subdir1/file1.txt',
					'dir2/subdir2/file1.txt'
				),
				'dir'
			)
		);
	}
}
