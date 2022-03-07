<?php
/**
 * Test vipgoci_lint_parse_results() function.
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
final class LintParseResultsTest extends TestCase {
	/**
	 * Require file.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../lint-scan.php';
	}

	/**
	 * Test function when there are no linting issues.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_lint_parse_results
	 */
	public function testLintGetIssues1() :void {
		$lint_issues = array(
			'No syntax errors detected in \/tmp\/test-lint-get-issues-1EZEWOz.php',
		);

		$lint_issues_parsed = vipgoci_lint_parse_results(
			'php-file-name.php',
			'/tmp/test-lint-get-issues-1EZEWOz.php',
			$lint_issues
		);

		$this->assertSame(
			array(),
			$lint_issues_parsed
		);
	}

	/**
	 * Test function when there are linting issues.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_lint_parse_results
	 */
	public function testLintDoScan2() :void {
		$lint_issues = array(
			'PHP Parse error:  syntax error, unexpected end of file, expecting "," or ";" in \/tmp\/test-lint-get-issues-2BW7UGg.php on line 3',
			'Errors parsing \/tmp\/test-lint-get-issues-2BW7UGg.php',
		);

		$lint_issues_parsed = vipgoci_lint_parse_results(
			'php-file-name.php',
			'\/tmp\/test-lint-get-issues-2BW7UGg.php',
			$lint_issues
		);

		$this->assertSame(
			array(
				3 => array(
					array(
						'message'  => 'syntax error, unexpected end of file, expecting "," or ";"',
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),
			),
			$lint_issues_parsed
		);
	}
}
