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
	 * @covers ::vipgoci_report_create_scan_details_phpcs_configuration
	 */
	public function testCreateDetails1(): void {
		$this->options['phpcs'] = false;

		$actual_output = vipgoci_report_create_scan_details_phpcs_configuration(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'PHPCS configuration'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>PHPCS scanning enabled: ' . PHP_EOL .
				'<code>false</code></p>'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Standard(s) used'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Runtime set'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Custom sniffs included'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Custom sniffs excluded'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'Directories not PHPCS scanned'
			)
		);
	}

	/**
	 * Test function with most reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_phpcs_configuration
	 */
	public function testCreateDetails2(): void {
		$this->options['phpcs']                = true;
		$this->options['phpcs-severity']       = 3;
		$this->options['phpcs-standard-file']  = false;
		$this->options['phpcs-standard']       = array( 'WordPress', 'WordPress-VIP-Go' );
		$this->options['phpcs-runtime-set']    = array( array( 'opt1', 'key1' ) );
		$this->options['phpcs-sniffs-include'] = array();
		$this->options['phpcs-sniffs-exclude'] = array();
		$this->options['phpcs-skip-folders']   = array( 'path1', 'path2' );

		$actual_output = vipgoci_report_create_scan_details_phpcs_configuration(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'PHPCS configuration'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>PHPCS scanning enabled: ' . PHP_EOL .
				'<code>true</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>PHPCS severity level: ' . PHP_EOL .
				'<code>3</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Standard(s) used:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>WordPress</code></li><li><code>WordPress-VIP-Go</code></li></ul>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Runtime set:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>opt1 key1</code></li></ul>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Custom sniffs included:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li>None</li></ul>' . PHP_EOL
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Custom sniffs excluded:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li>None</li></ul>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Directories not PHPCS scanned:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>path1</code></li><li><code>path2</code></li></ul>'
			)
		);
	}

	/**
	 * Test function with all reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_phpcs_configuration
	 */
	public function testCreateDetails3(): void {
		$this->options['phpcs']                   = true;
		$this->options['phpcs-severity']          = 3;
		$this->options['phpcs-standard-file']     = true;
		$this->options['phpcs-standard']          = array( 'WordPress', 'WordPress-VIP-Go' );
		$this->options['phpcs-standard-original'] = $this->options['phpcs-standard'];
		$this->options['phpcs-runtime-set']       = array( array( 'opt1', 'key1' ) );
		$this->options['phpcs-sniffs-include']    = array( 'AdditionalStandard1' );
		$this->options['phpcs-sniffs-exclude']    = array( 'SniffExclude1' );
		$this->options['phpcs-skip-folders']      = array( 'path1', 'path2' );

		$actual_output = vipgoci_report_create_scan_details_phpcs_configuration(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'PHPCS configuration'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>PHPCS scanning enabled: ' . PHP_EOL .
				'<code>true</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>PHPCS severity level: ' . PHP_EOL .
				'<code>3</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Standard(s) used:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>WordPress</code></li><li><code>WordPress-VIP-Go</code></li></ul>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Runtime set:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>opt1 key1</code></li></ul>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Custom sniffs included:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>AdditionalStandard1</code></li></ul>' . PHP_EOL
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Custom sniffs excluded:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>SniffExclude1</code></li></ul>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Directories not PHPCS scanned:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>path1</code></li><li><code>path2</code></li></ul>'
			)
		);
	}
}
