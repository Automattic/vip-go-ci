<?php
/**
 * Test vipgoci_wpscan_report_comment_format_result() function.
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
final class WpscanReportCommentFormatResultTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../output-security.php';
		require_once __DIR__ . '/../../github-misc.php';
		require_once __DIR__ . '/../../wpscan-reports.php';
	}

	/**
	 * Test common usage when reporting results for a plugin.
	 *
	 * @covers ::vipgoci_wpscan_report_comment_format_result
	 *
	 * @return void
	 */
	public function testReportResultPlugin(): void {
		$report_str = vipgoci_wpscan_report_comment_format_result(
			'repo_owner',
			'repo_name',
			'commit12345id',
			'file-1.php',
			250,
			array(
				'security' => VIPGOCI_WPSCAN_OBSOLETE,
				'message'  => 'My Plugin',
				'details'  => array(
					'plugin_uri'          => 'https://wordpress.org/plugins/my-plugin',
					'installed_location'  => 'plugins/my-plugin',
					'version_detected'    => '1.0.0',
					'latest_version'      => '2.0.0',
					'latest_download_uri' => 'https://downloads.wordpress.org/plugins/my-plugin-2.0.0.zip',
					'vulnerabilities'     => array(
						array(
							'id'    => '0x100',
							'title' => 'Security problem in My Plugin',
						),
					),
				),
			),
			VIPGOCI_WPSCAN_PLUGIN
		);

		$this->assertStringContainsString(
			'Obsolete',
			$report_str
		);

		$this->assertStringNotContainsString(
			'Vulnerable',
			$report_str
		);

		$this->assertStringNotContainsString(
			'Theme',
			$report_str
		);

		$this->assertStringContainsString(
			'Plugin information',
			$report_str
		);

		$this->assertStringContainsString(
			'Plugin Name',
			$report_str
		);

		$this->assertStringContainsString(
			'My Plugin',
			$report_str
		);

		$this->assertStringContainsString(
			'Plugin URI',
			$report_str
		);

		$this->assertStringContainsString(
			'https://wordpress.org/plugins/my-plugin',
			$report_str
		);

		$this->assertStringContainsString(
			'Installed location',
			$report_str
		);

		$this->assertStringContainsString(
			'plugins/my-plugin',
			$report_str
		);

		$this->assertStringContainsString(
			'Version observed',
			$report_str
		);

		$this->assertStringContainsString(
			'1.0.0',
			$report_str
		);

		$this->assertStringContainsString(
			'Latest version available',
			$report_str
		);

		$this->assertStringContainsString(
			'2.0.0',
			$report_str
		);

		$this->assertStringContainsString(
			'Latest version download URI',
			$report_str
		);

		$this->assertStringContainsString(
			'https://downloads.wordpress.org/plugins/my-plugin-2.0.0.zip',
			$report_str
		);

		$this->assertStringContainsString(
			'Title',
			$report_str
		);

		$this->assertStringContainsString(
			'Security problem in My Plugin',
			$report_str
		);

		$this->assertStringContainsString(
			'Details',
			$report_str
		);

		$this->assertStringContainsString(
			VIPGOCI_WPSCAN_BASE_URL . '/vulnerability/0x100',
			$report_str
		);

		$this->assertStringContainsString(
			'Severity',
			$report_str
		);

		$this->assertStringContainsString(
			'MEDIUM',
			$report_str
		);
	}

	/**
	 * Test common usage when reporting results for a theme.
	 *
	 * @covers ::vipgoci_wpscan_report_comment_format_result
	 *
	 * @return void
	 */
	public function testReportResultTheme(): void {
		$report_str = vipgoci_wpscan_report_comment_format_result(
			'repo_owner',
			'repo_name',
			'commit12345id',
			'file-1.php',
			250,
			array(
				'security' => VIPGOCI_WPSCAN_VULNERABLE,
				'message'  => 'My Theme',
				'details'  => array(
					'theme_uri'           => 'https://wordpress.org/themes/my-theme',
					'installed_location'  => 'themes/my-theme',
					'version_detected'    => '1.0.0',
					'latest_version'      => '2.0.0',
					'latest_download_uri' => 'https://downloads.wordpress.org/themes/my-theme-2.0.0.zip',
					'vulnerabilities'     => array(
						array(
							'id'    => '0x100',
							'title' => 'Security problem in My Theme',
						),
					),
				),
			),
			VIPGOCI_WPSCAN_THEME
		);

		$this->assertStringContainsString(
			'Vulnerable',
			$report_str
		);

		$this->assertStringNotContainsString(
			'Obsolete',
			$report_str
		);

		$this->assertStringNotContainsString(
			'Plugin',
			$report_str
		);

		$this->assertStringContainsString(
			'Theme information',
			$report_str
		);

		$this->assertStringContainsString(
			'Theme Name',
			$report_str
		);

		$this->assertStringContainsString(
			'My Theme',
			$report_str
		);

		$this->assertStringContainsString(
			'Theme URI',
			$report_str
		);

		$this->assertStringContainsString(
			'https://wordpress.org/themes/my-theme',
			$report_str
		);

		$this->assertStringContainsString(
			'Installed location',
			$report_str
		);

		$this->assertStringContainsString(
			'themes/my-theme',
			$report_str
		);

		$this->assertStringContainsString(
			'Version observed',
			$report_str
		);

		$this->assertStringContainsString(
			'1.0.0',
			$report_str
		);

		$this->assertStringContainsString(
			'Latest version available',
			$report_str
		);

		$this->assertStringContainsString(
			'2.0.0',
			$report_str
		);

		$this->assertStringContainsString(
			'Latest version download URI',
			$report_str
		);

		$this->assertStringContainsString(
			'https://downloads.wordpress.org/themes/my-theme-2.0.0.zip',
			$report_str
		);

		$this->assertStringContainsString(
			'Title',
			$report_str
		);

		$this->assertStringContainsString(
			'Security problem in My Theme',
			$report_str
		);

		$this->assertStringContainsString(
			'Details',
			$report_str
		);

		$this->assertStringContainsString(
			VIPGOCI_WPSCAN_BASE_URL . '/vulnerability/0x100',
			$report_str
		);

		$this->assertStringContainsString(
			'Severity',
			$report_str
		);

		$this->assertStringContainsString(
			'MEDIUM',
			$report_str
		);
	}
}
