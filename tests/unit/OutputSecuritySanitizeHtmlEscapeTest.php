<?php
/**
 * Test vipgoci_output_html_escape(), which
 * encodes outputted strings so they are safe
 * to use in HTML code.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test output sanitization function.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OutputSecuritySanitizeHtmlEscapeTest extends TestCase {
	/**
	 * Setup function. Require file needed.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../output-security.php';
	}

	/**
	 * Test if the function really HTML encodes input string.
	 *
	 * @covers ::vipgoci_output_sanitize_version_number
	 */
	public function testSanitizeVersionNumber(): void {
		$encoded_output = vipgoci_output_html_escape(
			'789 <test id="abc">text</test> 123'
		);

		$this->assertSame(
			'789 &lt;test id=&quot;abc&quot;&gt;text&lt;/test&gt; 123',
			$encoded_output
		);
	}
}
