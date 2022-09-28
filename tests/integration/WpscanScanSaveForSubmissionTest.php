<?php
/**
 * Test vipgoci_wpscan_scan_save_for_submission() function.
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
final class WpscanScanSaveForSubmissionTest extends TestCase {
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
	 * Variable for problematic addons.
	 *
	 * @var $problematic_addons_found
	 */
	private array $problematic_addons_found = array(
		'plugins/hello'          => array(
			'hello.php' => array(
				'security_type'      => 'obsolete',
				'wpscan_results'     => array(
					'friendly_name'   => 'Hello Dolly',
					'latest_version'  => '1.7.2',
					'last_updated'    => '2021-09-16T00:40:00.000Z',
					'popular'         => true,
					'vulnerabilities' => array(),
				),
				'addon_data_for_dir' => array(
					'type'             => 'vipgoci-addon-plugin',
					'addon_headers'    => array(
						'Name'        => 'Hello Dolly',
						'PluginURI'   => 'http://wordpress.org/plugins/hello-dolly/',
						'Version'     => '1.6',
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly<\/cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI'   => 'http://ma.tt\/',
						'TextDomain'  => '',
						'DomainPath'  => '',
						'Network'     => '',
						'RequiresWP'  => '',
						'RequiresPHP' => '',
						'UpdateURI'   => '',
						'Title'       => 'Hello Dolly',
						'AuthorName'  => 'Matt Mullenweg',
					),
					'name'             => 'Hello Dolly',
					'version_detected' => '1.6',
					'file_name'        => '/tmp/plugins/hello/hello.php',
					'id'               => 'w.org/plugins/hello-dolly',
					'slug'             => 'hello-dolly',
					'new_version'      => '1.7.2',
					'plugin'           => 'hello.php',
					'package'          => 'https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip',
					'url'              => 'https://wordpress.org/plugins/hello-dolly/',
				),
			),
		),
		'themes/twentytwentyone' => array(
			'style.css' => array(
				'security_type'      => 'vulnerable',
				'wpscan_results'     => array(
					'friendly_name'   => 'Twenty Twenty-One',
					'latest_version'  => '1.6',
					'last_updated'    => '2022-05-24T00:40:00.000Z',
					'popular'         => true,
					'vulnerabilities' => array(),
				),
				'addon_data_for_dir' => array(
					'type'             => 'vipgoci-addon-theme',
					'addon_headers'    => array(
						'Name'        => 'Twenty Twenty-One',
						'PluginURI'   => 'http://wordpress.org/themes/twentytwentyone/',
						'Version'     => '1.6',
						'Description' => 'Twenty Twenty-One is a blank canvas for your ideas and it makes the block editor your best brush. With new block patterns, which allow you to create a beautiful layout in a matter of seconds, this theme’s soft colors and eye-catching — yet timeless — design will let your work shine. Take it for a spin! See how Twenty Twenty-One elevates your portfolio, business website, or personal blog.',
						'Author'      => 'WordPress.org',
						'AuthorURI'   => 'https://wordpress.org',
						'TextDomain'  => '',
						'DomainPath'  => '',
						'Network'     => '',
						'RequiresWP'  => '',
						'RequiresPHP' => '',
						'UpdateURI'   => '',
						'Title'       => 'Twenty Twenty-One',
						'AuthorName'  => 'WordPress.org',
					),
					'name'             => 'Twenty Twenty-One',
					'version_detected' => '1.0',
					'file_name'        => '/tmp/themes/twentytwentyone/style.css',
					'id'               => 'w.org/themes/twentytwentyone',
					'slug'             => 'twentytwentyone',
					'new_version'      => '1.6',
					'package'          => 'https://downloads.wordpress.org/themes/twentytwentyone.1.6.zip',
					'url'              => 'https://wordpress.org/themes/twentytwentyone/',
				),
			),
		),
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
	 * Add 'skip-wpscan' label to pull request
	 * used in scanning.
	 *
	 * @return void
	 */
	private function prLabelAdd() :void {
		vipgoci_github_label_add_to_pr(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			(int) $this->options['wpscan-pr-number'],
			VIPGOCI_WPSCAN_SKIP_SCAN_PR_LABEL
		);
	}

	/**
	 * Remove 'skip-wpscan' label from pull request.
	 *
	 * @return void
	 */
	private function prLabelRemove() :void {
		vipgoci_github_pr_label_remove(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			(int) $this->options['wpscan-pr-number'],
			VIPGOCI_WPSCAN_SKIP_SCAN_PR_LABEL
		);
	}

	/**
	 * Test function when a 'skip-wpscan' label is associated with
	 * pull request, so results should not be added.
	 *
	 * @covers ::vipgoci_wpscan_scan_save_for_submission
	 *
	 * @return void
	 */
	public function testSaveForSubmissionWithLabel(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
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
			'error'   => 0,
		);

		// Add label.
		$this->prLabelAdd();

		// Wait for a bit while updates take place on GitHub.
		sleep( 10 );

		vipgoci_wpscan_scan_save_for_submission(
			$this->options,
			$commit_issues_submit,
			$commit_issues_stats,
			$commit_skipped_files,
			$this->problematic_addons_found
		);

		// Remove label.
		$this->prLabelRemove();

		$this->assertSame(
			array(
				$this->options['wpscan-pr-1-number'] => array(
					'warning' => 0,
					'error'   => 0,
				),
			),
			$commit_issues_stats
		);

		$this->assertSame(
			array(
				$this->options['wpscan-pr-1-number'] => array(),
			),
			$commit_issues_submit
		);
	}

	/**
	 * Test function when 'skip-wpscan' label is not associated with
	 * pull request, so results should be added.
	 *
	 * @covers ::vipgoci_wpscan_scan_save_for_submission
	 *
	 * @return void
	 */
	public function testSaveForSubmissionNoLabel(): void {
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
			'error'   => 0,
		);

		vipgoci_wpscan_scan_save_for_submission(
			$this->options,
			$commit_issues_submit,
			$commit_issues_stats,
			$commit_skipped_files,
			$this->problematic_addons_found
		);

		$this->assertSame(
			array(
				$this->options['wpscan-pr-1-number'] => array(
					'warning' => 1,
					'error'   => 1,
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
							'addon_type' => 'vipgoci-addon-plugin',
							'message'    => 'Hello Dolly',
							'level'      => 'warning',
							'security'   => 'obsolete',
							'severity'   => 7,
							'details'    => array(
								'url'                 => 'https://wordpress.org/plugins/hello-dolly/',
								'installed_location'  => 'plugins/hello',
								'version_detected'    => '1.6',
								'latest_version'      => '1.7.2',
								'latest_download_uri' => 'https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip',
								'vulnerabilities'     => array(),
							),
						),
					),
					array(
						'type'      => 'wpscan-api',
						'file_name' => 'themes/twentytwentyone/style.css',
						'file_line' => 1,
						'issue'     => array(
							'addon_type' => 'vipgoci-addon-theme',
							'message'    => 'Twenty Twenty-One',
							'level'      => 'error',
							'security'   => 'vulnerable',
							'severity'   => 10,
							'details'    => array(
								'url'                 => 'https://wordpress.org/themes/twentytwentyone/',
								'installed_location'  => 'themes/twentytwentyone',
								'version_detected'    => '1.0',
								'latest_version'      => '1.6',
								'latest_download_uri' => 'https://downloads.wordpress.org/themes/twentytwentyone.1.6.zip',
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

