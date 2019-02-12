<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once( __DIR__ . '/../defines.php' );
require_once( __DIR__ . '/../misc.php' );

final class MiscMarkdownCommentAddPagebreakTest extends TestCase {
	/**
	 * @covers ::vipgoci_markdown_comment_add_pagebreak
	 */
	public function testPageBreak1() {
		$mycomment = 'Here is my text. ' . "\n\r";

		vipgoci_markdown_comment_add_pagebreak(
			$mycomment,
			'***'
		);

		$this->assertEquals(
			'Here is my text. ' . "\n\r" . '***' . "\n\r",
			$mycomment 
		);
	}
}
