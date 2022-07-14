<?php
/**
 * Test vipgoci_wpscan_scan_commit() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WpscanScanCommitTest extends TestCase {
	/**
	 * Variable for WPScan API scanning.
	 *
	 * @var $options_wpscan_api_scan
	 */
	private array $options_wpscan_api_scan = array(
		'wpscan-pr-1-commit-id' => null,
		'wpscan-pr-1-dirs-scan' => null,
		'wpscan-pr-1-number'    => null,
	);

	/**
	 * Variable for git setup.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'git-path'        => null,
		'github-repo-url' => null,
		'repo-name'       => null,
		'repo-owner'      => null,
	);

	/**
	 * Setup function. Require files, prepare repository, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'wpscan-api-scan',
			$this->options_wpscan_api_scan
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		$this->options = array_merge(
			$this->options_wpscan_api_scan,
			$this->options_git
		);

		$this->options['commit'] =
			$this->options['wpscan-pr-1-commit-id'];

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;

		$this->options['wpscan-api'] = true;

		$this->options['wpscan-api-paths'] = explode(
			',',
			$this->options['wpscan-pr-1-dirs-scan']
		);

		$this->options['wpscan-api-url'] = VIPGOCI_WPSCAN_API_BASE_URL;

		$this->options['wpscan-api-skip-folders'] = array();

		$this->options['wpscan-api-token'] =
			vipgoci_unittests_get_config_value(
				'wpscan-api-scan',
				'access-token',
				true // Fetch from secrets file.
			);

		if ( empty( $this->options['wpscan-api-token'] ) ) {
			$this->options['wpscan-api-token'] = '';
		}

		$this->options['wpscan-pr-number'] = $this->options['wpscan-pr-1-number'];
	}

	/**
	 * Tear down function. Remove variables and temporary repository.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options_wpscan_api_scan );
		unset( $this->options_git );
		unset( $this->options );
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpscan_scan_commit
	 *
	 * @return void
	 */
	public function testScanCommit(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_unittests_output_unsuppress();

		$commit_issues_submit = array();
		$commit_issues_stats  = array();
		$commit_skipped_files = array();

		$commit_issues_submit[ $this->options['wpscan-pr-1-number'] ] = array();
		$commit_issues_stats[ $this->options['wpscan-pr-1-number'] ]  = array(
			'warning' => 0,
		);

		vipgoci_wpscan_scan_commit(
			$this->options,
			$commit_issues_submit,
			$commit_issues_stats,
			$commit_skipped_files
		);

		$this->assertSame(
			array(
				$this->options['wpscan-pr-1-number'] => array(
					'warning' => 1,
				),
			),
			$commit_issues_stats
		);

		$this->assertSame(
			array(
				$this->options['wpscan-pr-1-number'] => array(
					array(
						'type'      => 'wpscan-api',
						'file_name' => 'plugins/hello/hello.php',
						'file_line' => 1,
						'issue'     => array(
							'addon_type' => 'vipgoci-wpscan-plugin',
							'message'    => 'Hello Dolly',
							'level'      => 'warning',
							'security'   => 'obsolete',
							'severity'   => 10,
							'details'    => array(
								'plugin_uri'          => 'http://wordpress.org/plugins/hello-dolly/',
								'installed_location'  => 'plugins/hello/hello.php',
								'version_detected'    => '1.6',
								'latest_version'      => '1.7.2',
								'latest_download_uri' => 'https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip',
								'vulnerabilities'     => array(),
							),
						),
					),
				),
			),
			$commit_issues_submit
		);
	}
}

