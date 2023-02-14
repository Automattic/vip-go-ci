<?php
/**
 * Test vipgoci_github_transform_to_emojis().
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
final class GitHubMiscGitHubEmojisTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../defines.php';
		require_once __DIR__ . './../../github-misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_github_transform_to_emojis
	 *
	 * @return void
	 */
	public function testGitHubEmojis1() :void {
		$this->assertSame(
			'',
			vipgoci_github_transform_to_emojis(
				'exclamation'
			)
		);

		$this->assertSame(
			':warning:',
			vipgoci_github_transform_to_emojis(
				'warning'
			)
		);
	}
}
