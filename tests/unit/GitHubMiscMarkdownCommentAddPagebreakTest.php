<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . './../../misc.php' );

use PHPUnit\Framework\TestCase;

final class GitHubMiscMarkdownCommentAddPagebreakTest extends TestCase {
	/**
	 * @covers ::vipgoci_markdown_comment_add_pagebreak
	 */
	public function testPageBreak1() {
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
