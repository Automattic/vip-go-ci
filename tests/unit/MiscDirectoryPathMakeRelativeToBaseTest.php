<?php
/**
 * Test vipgoci_directory_path_make_relative_to_base() function.
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
final class MiscDirectoryPathMakeRelativeToBaseTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../misc.php';
	}

	/**
	 * Test various usage cases of the function.
	 *
	 * @covers ::vipgoci_directory_path_make_relative_to_base
	 *
	 * @return void
	 */
	public function testDirRelativeToBase(): void {
		$test_arr = array(

			/*
			 * Supported usage.
			 */
			array(
				'base_dir_path'   => 'plugins',
				'target_dir_path' => 'plugins/my-plugin',
				'expected_return' => 'plugins/my-plugin',
			),
			array(
				'base_dir_path'   => 'plugins',
				'target_dir_path' => 'plugins/my-plugin/dir1',
				'expected_return' => 'plugins/my-plugin',
			),
			array(
				'base_dir_path'   => 'plugins',
				'target_dir_path' => 'plugins/my-plugin/dir1/subdir1',
				'expected_return' => 'plugins/my-plugin',
			),
			array(
				'base_dir_path'   => 'plugins',
				'target_dir_path' => 'plugins/my-plugin/dir1/subdir1/subsubdir1',
				'expected_return' => 'plugins/my-plugin',
			),
			array(
				'base_dir_path'   => 'plugins',
				'target_dir_path' => 'plugins/other-plugin',
				'expected_return' => 'plugins/other-plugin',
			),
			array(
				'base_dir_path'   => 'plugins',
				'target_dir_path' => 'plugins/other-plugin/dir2',
				'expected_return' => 'plugins/other-plugin',
			),
			array(
				'base_dir_path'   => 'themes',
				'target_dir_path' => 'themes/my-theme',
				'expected_return' => 'themes/my-theme',
			),
			array(
				'base_dir_path'   => 'themes',
				'target_dir_path' => 'themes/my-theme/dir1',
				'expected_return' => 'themes/my-theme',
			),
			array(
				'base_dir_path'   => 'themes',
				'target_dir_path' => 'themes/my-theme/dir1/subdir1/subdir2/subdir3/subdir4',
				'expected_return' => 'themes/my-theme',
			),
			array(
				'base_dir_path'   => 'other/plugin-location-dir',
				'target_dir_path' => 'other/plugin-location-dir/plugin2',
				'expected_return' => 'other/plugin-location-dir/plugin2',
			),
			array(
				'base_dir_path'   => 'other/plugin-location-dir',
				'target_dir_path' => 'other/plugin-location-dir/plugin2/dir1',
				'expected_return' => 'other/plugin-location-dir/plugin2',
			),
			array(
				'base_dir_path'   => 'other/plugin-location-dir',
				'target_dir_path' => 'other/plugin-location-dir/plugin2/dir1/subdir1/subdir2/subdir3/subdir4',
				'expected_return' => 'other/plugin-location-dir/plugin2',
			),
			array(
				'base_dir_path'   => 'other/my-dir/my-dir2/plugin-location-dir',
				'target_dir_path' => 'other/my-dir/my-dir2/plugin-location-dir/plugin2/dir1/subdir1/subdir2/subdir3/subdir4',
				'expected_return' => 'other/my-dir/my-dir2/plugin-location-dir/plugin2',
			),

			/*
			 * Various failure conditions.
			 */
			array(
				'base_dir_path'   => 'test/dir',
				'target_dir_path' => 'test2/my-plugin',
				'expected_return' => null,
			),
			array(
				'base_dir_path'   => 'test/dir/subdir1',
				'target_dir_path' => 'test2/test/my-plugin',
				'expected_return' => null,
			),
			array(
				'base_dir_path'   => 'test',
				'target_dir_path' => 'test2/my-plugin',
				'expected_return' => null,
			),
			array(
				'base_dir_path'   => 'test',
				'target_dir_path' => 'test',
				'expected_return' => null,
			),
		);

		foreach ( $test_arr as $test_item ) {
			$this->assertSame(
				$test_item['expected_return'],
				vipgoci_directory_path_get_dir_and_include_base(
					$test_item['base_dir_path'],
					$test_item['target_dir_path']
				)
			);
		}
	}
}
