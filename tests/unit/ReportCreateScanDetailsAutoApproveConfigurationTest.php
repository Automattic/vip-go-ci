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
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test function with most reporting disabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_auto_approve_configuration
	 */
	public function testCreateDetails1(): void {
		$this->options['autoapprove'] = false;

		$actual_output = vipgoci_report_create_scan_details_auto_approve_configuration(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'Auto-approval configuration'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Auto-approvals enabled:' . PHP_EOL .
				'<code>false</code></p>'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Non-functional changes auto-approved'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Files with file extensions to consider for non-functional change auto-approval'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Auto-approved file-types'
			)
		);
	}

	/**
	 * Test function with most reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_auto_approve_configuration
	 */
	public function testCreateDetails2(): void {
		$this->options['autoapprove']                           = true;
		$this->options['autoapprove-php-nonfunctional-changes'] = true;
		$this->options['autoapprove-php-nonfunctional-changes-file-extensions'] = array( 'php' );
		$this->options['autoapprove-filetypes']                 = array( 'txt', 'ini' );

		$actual_output = vipgoci_report_create_scan_details_auto_approve_configuration(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'Auto-approval configuration'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Auto-approvals enabled:' . PHP_EOL .
				'<code>true</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Non-functional changes auto-approved:' . PHP_EOL .
				'<code>true</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Files with file extensions to consider for non-functional change auto-approval:' . PHP_EOL .
				'<code>php</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Auto-approved file-types:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>txt</code></li><li><code>ini</code></li></ul>'
			)
		);
	}
}
