<?php
/**
 * Test vipgoci_report_create_scan_details_phpcs_configuration(),
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
final class ReportCreateScanDetailsPhpcsConfigurationTest extends TestCase {
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
	 * @covers ::vipgoci_report_create_scan_details_phpcs_configuration
	 *
	 * @return void
	 */
	public function testCreateDetails1(): void {
		$this->options['phpcs'] = false;

		$actual_output = vipgoci_report_create_scan_details_phpcs_configuration(
			$this->options
		);

		$this->assertStringContainsString(
			'PHPCS configuration',
			$actual_output,
		);

		$this->assertStringContainsString(
			'<p>PHPCS scanning enabled: ' . PHP_EOL .
				'<code>false</code></p>',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'Standard(s) used',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'Runtime set',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'file extensions',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'Custom sniffs included',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'Custom sniffs excluded',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'Directories not PHPCS scanned',
			$actual_output
		);
	}

	/**
	 * Test function with most reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_phpcs_configuration
	 *
	 * @return void
	 */
	public function testCreateDetails2(): void {
		$this->options['phpcs']                 = true;
		$this->options['phpcs-severity']        = 3;
		$this->options['phpcs-standard-file']   = false;
		$this->options['phpcs-standard']        = array( 'WordPress', 'WordPress-VIP-Go' );
		$this->options['phpcs-runtime-set']     = array( array( 'opt1', 'key1' ) );
		$this->options['phpcs-file-extensions'] = array( 'php', 'js', 'twig' );
		$this->options['phpcs-sniffs-include']  = array();
		$this->options['phpcs-sniffs-exclude']  = array();
		$this->options['phpcs-skip-folders']    = array( 'path1', 'path2' );

		$actual_output = vipgoci_report_create_scan_details_phpcs_configuration(
			$this->options
		);

		$this->assertStringContainsString(
			'PHPCS configuration',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>PHPCS scanning enabled: ' . PHP_EOL .
				'<code>true</code></p>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>PHPCS severity level: ' . PHP_EOL .
				'<code>3</code></p>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Standard(s) used:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>WordPress</code></li><li><code>WordPress-VIP-Go</code></li></ul>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Runtime set:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>opt1 key1</code></li></ul>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Scan added/modified files with file extensions:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>php</code></li><li><code>js</code></li><li><code>twig</code></li></ul>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Custom sniffs included:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li>None</li></ul>' . PHP_EOL,
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Custom sniffs excluded:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li>None</li></ul>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Directories not PHPCS scanned:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>path1</code></li><li><code>path2</code></li></ul>',
			$actual_output
		);
	}

	/**
	 * Test function with all reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_phpcs_configuration
	 *
	 * @return void
	 */
	public function testCreateDetails3(): void {
		$this->options['phpcs']                   = true;
		$this->options['phpcs-severity']          = 3;
		$this->options['phpcs-standard-file']     = true;
		$this->options['phpcs-standard']          = array( 'WordPress', 'WordPress-VIP-Go' );
		$this->options['phpcs-standard-original'] = $this->options['phpcs-standard'];
		$this->options['phpcs-runtime-set']       = array( array( 'opt1', 'key1' ) );
		$this->options['phpcs-file-extensions']   = array( 'php', 'js', 'twig' );
		$this->options['phpcs-sniffs-include']    = array( 'AdditionalStandard1' );
		$this->options['phpcs-sniffs-exclude']    = array( 'SniffExclude1' );
		$this->options['phpcs-skip-folders']      = array( 'path1', 'path2' );

		$actual_output = vipgoci_report_create_scan_details_phpcs_configuration(
			$this->options
		);

		$this->assertStringContainsString(
			'PHPCS configuration',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>PHPCS scanning enabled: ' . PHP_EOL .
				'<code>true</code></p>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>PHPCS severity level: ' . PHP_EOL .
				'<code>3</code></p>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Standard(s) used:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>WordPress</code></li><li><code>WordPress-VIP-Go</code></li></ul>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Runtime set:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>opt1 key1</code></li></ul>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Scan added/modified files with file extensions:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>php</code></li><li><code>js</code></li><li><code>twig</code></li></ul>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Custom sniffs included:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>AdditionalStandard1</code></li></ul>' . PHP_EOL,
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Custom sniffs excluded:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>SniffExclude1</code></li></ul>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Directories not PHPCS scanned:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>path1</code></li><li><code>path2</code></li></ul>',
			$actual_output
		);
	}
}
