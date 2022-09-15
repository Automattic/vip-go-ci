<?php
/**
 * Test vipgoci_gitrepo_get_file_path_from_diff() function.
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
final class GitRepoGetFilePathFromDiffTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../git-repo.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_gitrepo_get_file_path_from_diff
	 *
	 * @return void
	 */
	public function testGetFilePathFromDiff(): void {
		$this->assertSame(
			'b/file 30  test   b.php',
			vipgoci_gitrepo_get_file_path_from_diff(
				array( '+++', 'b/file', '30', '', 'test', '', '', 'b.php' ),
				1
			)
		);
	}
}
