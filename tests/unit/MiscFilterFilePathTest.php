<?php
/**
 * Test function vipgoci_filter_file_path().
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
final class MiscFilterFilePathTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../log.php';
		require_once __DIR__ . './../../misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_filter_file_path
	 *
	 * @return void
	 */
	public function testFilterFilePath1() :void {
		$file_name = 'folder1/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'file_extensions' => array(
						'txt',
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'file_extensions' => array(
						'ini',
					),
				)
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_filter_file_path
	 *
	 * @return void
	 */
	public function testFilterFilePath2() :void {
		$file_name = 'folder1/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'file_extensions' => array(
						'txt',
						'ini',
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'file_extensions' => array(
						'ini',
						'sys',
					),
				)
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_filter_file_path
	 *
	 * @return void
	 */
	public function testFilterFilePath3() :void {
		$file_name = 'folder1/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'folder2',
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'folder1',
					),
				)
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_filter_file_path
	 *
	 * @return void
	 */
	public function testFilterFilePath4() :void {
		$file_name = 'folder1/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders'    => array(
						'folder2',
					),

					'file_extensions' => array(
						'txt',
						'ini',
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders'    => array(
						'folder1',
					),

					'file_extensions' => array(
						'ini',
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders'    => array(
						'folder1',
					),

					'file_extensions' => array(
						'txt',
						'ini',
					),
				)
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_filter_file_path
	 *
	 * @return void
	 */
	public function testFilterFilePath5() :void {
		$file_name = 'my/unit-tests/folder1/subfolder/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'folder200',
						'folder3000',
						'folder4000/folder5000/folder6000',
						'SubFolder', // Note: capital 'F'.
					),
				)
			)
		);

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'unit-tests/folder1/subfolder', // Note: not at root level.
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'somefoldertesting/otherfolder/foobar123',
						'somefoldertesting/otherfolder/foobar321',
						'my/unit-tests/folder1/subfolder',
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'my/unit-tests',
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'my',
					),
				)
			)
		);

	}
	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_filter_file_path
	 *
	 * @return void
	 */
	public function testFilterFilePath6() :void {
		$file_name = 'folder1/file1.txt';

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'include_folders' => array(
						'folder2',
					),
				)
			)
		);

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'include_folders' => array(
						'folder1',
					),
				)
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_filter_file_path
	 *
	 * @return void
	 */
	public function testFilterFilePath7() :void {
		$file_name = 'my/unit-tests/folder1/subfolder/file1.txt';

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'include_folders' => array(
						'folder200',
						'folder3000',
						'folder4000/folder5000/folder6000',
						'SubFolder', // Note: capital 'F'.
					),
				)
			)
		);

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'include_folders' => array(
						'unit-tests/folder1/subfolder', // Note: Unlike skip_folders, this is allowed when it's not at root level.
					),
				)
			)
		);

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'include_folders' => array(
						'somefoldertesting/otherfolder/foobar123',
						'somefoldertesting/otherfolder/foobar321',
						'my/unit-tests/folder1/subfolder',
					),
				)
			)
		);

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'include_folders' => array(
						'my/unit-tests',
					),
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'include_folders' => array(
						'test',
					),
				)
			)
		);

	}
}
