<?php
/**
 * Test vipgoci_report_create_scan_details_auto_approve_configuration(),
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
final class ReportCreateScanDetailsAutoApproveConfigurationTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../reports.php';
		require_once __DIR__ . '/../../output-security.php';
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/helper/ReportCreateScanDetails.php';

		$this->options = array();
	}

	/**
	 * Clean up after running.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test function with most reporting disabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_auto_approve_configuration
	 *
	 * @return void
	 */
	public function testCreateDetails1(): void {
		$this->options['autoapprove'] = false;

		$actual_output = vipgoci_report_create_scan_details_auto_approve_configuration(
			$this->options
		);

		$this->assertStringContainsString(
			'Auto-approval configuration',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Auto-approvals enabled:' . PHP_EOL .
				'<code>false</code></p>',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'Non-functional changes auto-approved',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'Files with file extensions to consider for non-functional change auto-approval',
			$actual_output,
		);

		$this->assertStringNotContainsString(
			'Auto-approved file-types',
			$actual_output
		);
	}

	/**
	 * Test function with most reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_auto_approve_configuration
	 *
	 * @return void
	 */
	public function testCreateDetails2(): void {
		$this->options['autoapprove']                           = true;
		$this->options['autoapprove-php-nonfunctional-changes'] = true;
		$this->options['autoapprove-php-nonfunctional-changes-file-extensions'] = array( 'php' );
		$this->options['autoapprove-filetypes']                 = array( 'txt', 'ini' );

		$actual_output = vipgoci_report_create_scan_details_auto_approve_configuration(
			$this->options
		);

		$this->assertStringContainsString(
			'Auto-approval configuration',
			$actual_output,
		);

		$this->assertStringContainsString(
			'<p>Auto-approvals enabled:' . PHP_EOL .
				'<code>true</code></p>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Non-functional changes auto-approved:' . PHP_EOL .
				'<code>true</code></p>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Files with file extensions to consider for non-functional change auto-approval:' . PHP_EOL .
				'<code>php</code></p>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Auto-approved file-types:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>txt</code></li><li><code>ini</code></li></ul>',
			$actual_output
		);
	}
}
