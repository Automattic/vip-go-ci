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
final class ReportCreateScanDetailsSoftwareVersionsTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

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
	 * Clean up options variable.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test function with most reporting disabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_software_versions
	 *
	 * @return void
	 */
	public function testCreateDetails1(): void {
		$this->options['lint']         = false;
		$this->options['phpcs']        = false;
		$this->options['repo-options'] = false;
		$this->options['svg-checks']   = false;

		$actual_output = vipgoci_report_create_scan_details_software_versions(
			$this->options
		);

		$this->assertStringContainsString(
			'Software versions',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li><a href="https://github.com/Automattic/vip-go-ci">vip-go-ci</a> version: <code>' . VIPGOCI_VERSION . '</code></li>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li>PHP runtime version for vip-go-ci: <code>' . phpversion() . '</code></li>',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'PHP runtime version for PHP linting',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'PHP runtime version for PHPCS',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'PHPCS version',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'SVG scanner',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Options file enabled: ' . PHP_EOL . '<code>false</code></p>',
			$actual_output
		);
	}

	/**
	 * Test function with most reporting enabled.
	 *
	 * @covers ::vipgoci_report_create_scan_details_software_versions
	 *
	 * @return void
	 */
	public function testCreateDetails2(): void {
		$this->options['lint']                   = true;
		$this->options['lint-php-version-paths'] = array(
			'7.3' => '/usr/bin/php7.3',
			'7.4' => '/usr/bin/php7.4',
			'8.0' => '/usr/bin/php8.0',
			'8.1' => '/usr/bin/php8.1',
		);
		$this->options['lint-php-versions']      = array( '7.4', '8.1' );
		$this->options['phpcs']                  = true;
		$this->options['phpcs-path']             = '/usr/bin/phpcs';
		$this->options['phpcs-php-path']         = '/usr/bin/php7.3';
		$this->options['svg-checks']             = true;
		$this->options['svg-php-path']           = '/usr/bin/php8.0';
		$this->options['repo-options']           = true;
		$this->options['repo-options-set']       = array(
			'a' => 1,
			'b' => 2,
		);
		$this->options['repo-options-allowed']   = array( 'opt1', 'opt2' );

		$actual_output = vipgoci_report_create_scan_details_software_versions(
			$this->options
		);

		$this->assertStringContainsString(
			'<li>PHP runtime version for PHPCS: <code>7.3.1</code></li>',
			$actual_output
		);

		$this->assertStringContainsString(
			'Software versions',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li><a href="https://github.com/Automattic/vip-go-ci">vip-go-ci</a> version: <code>' . VIPGOCI_VERSION . '</code></li>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li>PHP runtime version for vip-go-ci: <code>' . phpversion() . '</code></li>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li>PHP runtime for linting:',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li>PHP 7.4: <code>7.4.2</code></li>',
			$actual_output
		);

		$this->assertStringNotContainsString(
			'PHP 8.0',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li>PHP 8.1: <code>8.1.4</code></li>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li>PHP runtime version for PHPCS: <code>7.3.1</code></li>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li>PHPCS version: <code>3.5.5</code></li>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<li>PHP runtime version for SVG scanner: <code>8.0.3</code></li>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Options file enabled: ' . PHP_EOL . '<code>true</code></p>',
			$actual_output
		);

		$this->assertStringContainsString(
			'<p>Options altered:</p>' . PHP_EOL .
				'<ul>' . PHP_EOL .
				'<li><code>a</code>set to<code>1</code></li><li><code>b</code>set to<code>2</code></li></ul>' . PHP_EOL,
			$actual_output
		);
	}
}
