<?php
/**
 * Test vipgoci_gitrepo_diffs_clean_extra_whitespace() function.
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
#[CoversFunction( 'vipgoci_gitrepo_diffs_clean_extra_whitespace' )]
final class GitRepoDiffsCleanExtraWhitespaceTest extends TestCase {
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
	 * @return void
	 */
	public function testCleanExtraWhitespace1(): void {
		$this->assertSame(
			array( "a\t" ),
			vipgoci_gitrepo_diffs_clean_extra_whitespace(
				array( "a\t" )
			)
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @return void
	 */
	public function testCleanExtraWhitespace2(): void {
		$this->assertSame(
			array( "a\t", "b\t", 'c.php' ),
			vipgoci_gitrepo_diffs_clean_extra_whitespace(
				array( "a\t", "b\t", "c.php\t" )
			)
		);
	}
}
