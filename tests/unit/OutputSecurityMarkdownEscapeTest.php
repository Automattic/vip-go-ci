<?php
/**
 * Test vipgoci_output_markdown_escape() function.
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
final class OutputSecurityMarkdownEscapeTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../output-security.php';
	}

	/**
	 * Test escaping function.
	 *
	 * @covers ::vipgoci_output_markdown_escape
	 *
	 * @return void
	 */
	public function testMarkdownEscape(): void {
		$this->assertSame(
			'\\\\ \- \# \* \+ \` \. \[ \] \( \) \! \&\#38; \&\#60; \&\#62; \_ \{ \} a b c def 1 2 3 456',
			vipgoci_output_markdown_escape(
				'\\ - # * + ` . [ ] ( ) ! & < > _ { } a b c def 1 2 3 456'
			)
		);
	}
}
