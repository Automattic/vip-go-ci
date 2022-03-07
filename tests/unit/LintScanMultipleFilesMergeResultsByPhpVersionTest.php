<?php
/**
 * Test vipgoci_lint_scan_multiple_files_merge_results_by_php_version() function.
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
final class LintScanMultipleFilesMergeResultsByPhpVersionTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../lint-scan.php';
		require_once __DIR__ . '/../../output-security.php';
	}

	/**
	 * Test if merged results are as expected.
	 *
	 * @covers ::vipgoci_lint_scan_multiple_files_merge_results_by_php_version
	 */
	public function testMergeResultsByPhpVersion(): void {
		$current_file_intermediary_results = array(
			3  => array(
				'10e774fb3cd64c708f1d60183b7f156aae7a234bca806fe66b886769dd230108' => array(
					'versions' => array(
						'7.3',
						'7.4',
					),
					'item'     => array(
						'message'  => 'syntax error, unexpected ";"',
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),

				'2f60ba150b548dd32563a07e8492e61c6db5ab1c3c296efa93eff9d50ee254e2' => array(
					'versions' => array(
						'8.0',
					),
					'item'     => array(
						'message'  => "syntax error, unexpected ';'", // Note: ' instead of " above.
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),
			),

			10 => array(
				'2f60ba150b548dd32563a07e8492e61c6db5ab1c3c296efa93eff9d50ee254e2' => array(
					'versions' => array(
						'8.0',
					),
					'item'     => array(
						'message'  => "syntax error, unexpected ';'",
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),
			),
		);

		$file_issues_arr = vipgoci_lint_scan_multiple_files_merge_results_by_php_version(
			$current_file_intermediary_results
		);

		$this->assertSame(
			array(
				3 => array(
					array(
						'message'  => 'Linting with PHP 7.3, 7.4 turned up: <code>syntax error, unexpected &quot;;&quot;</code>',
						'level'    => 'ERROR',
						'severity' => 5,
					),
					array(
						'message'  => 'Linting with PHP 8.0 turned up: <code>syntax error, unexpected &#039;;&#039;</code>',
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),

				10 => array(
					array(
						'message'  => 'Linting with PHP 8.0 turned up: <code>syntax error, unexpected &#039;;&#039;</code>',
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),
			),
			$file_issues_arr
		);

	}
}
