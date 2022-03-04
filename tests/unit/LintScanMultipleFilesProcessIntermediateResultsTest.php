<?php
/**
 * Test vipgoci_lint_scan_multiple_files_process_intermediate_results() function.
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
final class LintScanMultipleFilesProcessIntermediateResultsTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../lint-scan.php';
	}

	/**
	 * Call the function with intermediary results for processing.
	 * Check if the results match what is expected.
	 *
	 * @covers ::vipgoci_lint_scan_multiple_files_process_intermediate_results
	 */
	public function testProcessIntermediateResults(): void {
		$current_file_intermediary_results = array();

		vipgoci_lint_scan_multiple_files_process_intermediate_results(
			$current_file_intermediary_results,
			'7.3',
			array(
				3 => array(
					array(
						'message'  => 'syntax error, unexpected ";"',
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),
			)
		);

		vipgoci_lint_scan_multiple_files_process_intermediate_results(
			$current_file_intermediary_results,
			'7.4',
			array(
				3 => array(
					array(
						'message'  => 'syntax error, unexpected ";"',
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),
			)
		);

		vipgoci_lint_scan_multiple_files_process_intermediate_results(
			$current_file_intermediary_results,
			'8.0',
			array(
				3 => array(
					array(
						'message'  => "syntax error, unexpected ';'",
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),
			)
		);

		vipgoci_lint_scan_multiple_files_process_intermediate_results(
			$current_file_intermediary_results,
			'8.0',
			array(
				10 => array(
					array(
						'message'  => "syntax error, unexpected ';'",
						'level'    => 'ERROR',
						'severity' => 5,
					),
				),
			)
		);

		$this->assertSame(
			array(
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
			),
			$current_file_intermediary_results
		);
	}
}
