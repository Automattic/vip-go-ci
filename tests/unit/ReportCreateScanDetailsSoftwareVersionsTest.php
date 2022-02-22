<?php
/**
 * Test vipgoci_report_create_scan_details_software_versions(),
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
final class ReportCreateScanDetailsSoftwareVersions extends TestCase {
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

	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test function with most reporting disabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_software_versions
	 */
	public function testCreateDetails1(): void {
		$this->options['lint']          = false;
		$this->options['phpcs']         = false;
		$this->options['repo-options']  = false;

		$actual_output = vipgoci_report_create_scan_details_software_versions(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'Software versions'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<li>vip-go-ci version: <code>' . VIPGOCI_VERSION . '</code></li>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<li>PHP runtime version for vip-go-ci: <code>' . phpversion() . '</code></li>'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'PHP runtime version for PHP linting'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'PHP runtime version for PHPCS'
			)
		);

		$this->assertFalse(
			strpos(
				$actual_output,
				'PHPCS version'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Options file enabled: ' . PHP_EOL  . '<code>false</code></p>'
			)
		);
	}

	/**
	 * Test function with most reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_software_versions
	 */
	public function testCreateDetails2(): void {
		$this->options['lint']                 = true;
		$this->options['lint-php-path']        = '/usr/bin/php';
		$this->options['phpcs']                = true;
		$this->options['phpcs-path']           = '/usr/bin/phpcs';
		$this->options['phpcs-php-path']       = '/usr/bin/php';
		$this->options['repo-options']         = true;
		$this->options['repo-options-set']     = array( 'a' => 1, 'b' => 2 );
		$this->options['repo-options-allowed'] = array( 'opt1', 'opt2' );

		$actual_output = vipgoci_report_create_scan_details_software_versions(
			$this->options
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<li>PHP runtime version for PHPCS: <code>7.4.3</code></li>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'Software versions'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<li>vip-go-ci version: <code>' . VIPGOCI_VERSION . '</code></li>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<li>PHP runtime version for vip-go-ci: <code>' . phpversion() . '</code></li>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<li>PHP runtime version for PHP linting: <code>7.4.3</code></li>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<li>PHP runtime version for PHPCS: <code>7.4.3</code></li>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<li>PHPCS version: <code>3.5.5</code></li>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Options file enabled: ' . PHP_EOL  . '<code>true</code></p>'
			)
		);

		$this->assertNotFalse(
			strpos(
				$actual_output,
				'<p>Options altered:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>a</code>set to<code>1</code></li><li><code>b</code>set to<code>2</code></li></ul>' . PHP_EOL
			)
		);
	}
}
