<?php
/**
 * Test vipgoci_report_create_scan_details_php_lint_options(),
 * which outputs HTML code.
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
final class ReportCreateScanDetailsPhpLintOptionsTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../reports.php';
		require_once __DIR__ . '/../../output-security.php';
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/helper/ReportCreateScanDetails.php';

		$this->options = array();
	}

	/**
	 * Clean up options variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test function with most reporting disabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_php_lint_options
	 */
	public function testCreateDetails1(): void {
		$this->options['lint'] = false;

		$actual_output = vipgoci_report_create_scan_details_php_lint_options(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'PHP lint options'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>PHP lint files enabled: ' . PHP_EOL .
				'<code>false</code></p>'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Lint modified files only'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Directories not PHP linted'
			)
		);
	}

	/**
	 * Test function with most reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_php_lint_options
	 */
	public function testCreateDetails2(): void {
		$this->options['lint']                     = true;
		$this->options['lint-modified-files-only'] = true;
		$this->options['lint-skip-folders']        = array( 'path1', 'path2' );

		$actual_output = vipgoci_report_create_scan_details_php_lint_options(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'PHP lint options'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>PHP lint files enabled: ' . PHP_EOL .
				'<code>true</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Lint modified files only: ' . PHP_EOL .
				'<code>true</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Directories not PHP linted:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>path1</code></li><li><code>path2</code></li></ul>'
			)
		);
	}
}
