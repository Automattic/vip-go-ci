<?php
/**
 * Test vipgoci_markdown_comment_add_pagebreak().
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
final class GitHubMiscMarkdownCommentAddPagebreakTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../github-misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_markdown_comment_add_pagebreak
	 *
	 * @return void
	 */
	public function testPageBreak1() :void {
		$mycomment = 'Here is my text. ' . "\n\r";

		vipgoci_markdown_comment_add_pagebreak(
			$mycomment,
			'***'
		);

		$this->assertSame(
			'Here is my text. ' . "\n\r" . '***' . "\n\r",
			$mycomment
		);
	}
}
