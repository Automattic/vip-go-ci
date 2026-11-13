<?php
/**
 * Test function vipgoci_sanitize_path_prefix().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_sanitize_path_prefix' )]
final class MiscSanitizePathPrefixTest extends TestCase {
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
	 * @return void
	 */
	public function testSanitizePathPrefix1() :void {
		$path = vipgoci_sanitize_path_prefix(
			'a/folder1',
			array( 'a/' )
		);

		$this->assertSame(
			'folder1',
			$path
		);
	}
	/**
	 * Test common usage of the function.
	 *
	 * @return void
	 */
	public function testSanitizePathPrefix2() :void {
		$path = vipgoci_sanitize_path_prefix(
			'a/b/folder1',
			array( 'a/', 'b/' )
		);

		$this->assertSame(
			'b/folder1',
			$path
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @return void
	 */
	public function testSanitizePathPrefix3() :void {
		$path = vipgoci_sanitize_path_prefix(
			'a/folder1',
			array( 'b/' )
		);

		$this->assertSame(
			'a/folder1',
			$path
		);
	}
}
