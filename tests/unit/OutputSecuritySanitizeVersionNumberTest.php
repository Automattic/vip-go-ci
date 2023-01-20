<?php
/**
 * Test vipgoci_output_sanitize_version_number(), which
 * sanitizes version number strings.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Implements testing of vipgoci_output_sanitize_version_number().
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OutputSecuritySanitizeVersionNumberTest extends TestCase {
	/**
	 * Setup function. Require file needed.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../output-security.php';
	}

	/**
	 * Check if function really sanitizes input string.
	 *
	 * @covers ::vipgoci_output_sanitize_version_number
	 */
	public function testSanitizeVersionNumber(): void {
		$sanitized_number = vipgoci_output_sanitize_version_number(
			PHP_EOL . ' 1.50.30.3b,  - 5b  ' . "\t" . PHP_EOL
		);

		$this->assertSame(
			'1.50.30.3b-5b',
			$sanitized_number
		);
	}
}
