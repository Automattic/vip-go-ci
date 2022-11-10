<?php
/**
 * Test vipgoci_wpscan_get_altered_addons_data_and_slugs() function.
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
final class WpscanGetAlteredAddonsDataAndSlugsTest extends TestCase {
	/**
	 * Variable for WPScan API configuration.
	 *
	 * @var $options_wpscan_api_scan
	 */
	private array $options_wpscan_api_scan = array(
		'wpscan-pr-1-commit-id'      => null,
		'wpscan-pr-1-branch-ref'     => null,
		'wpscan-pr-1-dirs-scan'      => null,
		'wpscan-pr-1-dirs-altered'   => null,
		'wpscan-pr-1-plugin-key'     => null,
		'wpscan-pr-1-plugin-name'    => null,
		'wpscan-pr-1-plugin-slug'    => null,
		'wpscan-pr-1-plugin-path'    => null,
		'wpscan-pr-1-plugin-version' => null,
		'wpscan-pr-1-theme-name'     => null,
		'wpscan-pr-1-theme-key'      => null,
		'wpscan-pr-1-theme-dir'      => null,
		'wpscan-pr-1-theme-slug'     => null,
		'wpscan-pr-1-theme-path'     => null,
		'wpscan-pr-1-theme-version'  => null,
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
	 * Variable for skipped files.
	 *
	 * @var $commit_skipped_files
	 */
	private array $commit_skipped_files = array();

	/**
	 * Setup function. Require files, set up options variable, etc.
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

		$this->options['local-git-repo'] = false;

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;

		$this->options['wpscan-api-paths'] = explode(
			',',
			$this->options['wpscan-pr-1-dirs-scan']
		);

		$this->options['wpscan-api-skip-folders'] = array();

		$this->options['wpscan-api-plugin-file-extensions'] = array( 'php' );

		$this->options['wpscan-api-theme-file-extensions'] = array( 'css' );
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
	 * Get array of altered files for pull request.
	 *
	 * @return array Files altered.
	 */
	private function getFilesAffectedByPR() :array {
		$pr_git_diff = vipgoci_git_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['token'],
			$this->options['wpscan-pr-1-branch-ref'],
			$this->options['commit'],
			true,
			false,
			true,
			array(
				'skip_folders' => $this->options['wpscan-api-skip-folders'],
			),
		);

		return array(
			'all' => array_keys( $pr_git_diff['files'] ),
		);
	}

	/**
	 * Test common usage of the function. No results expected
	 * as plugin and theme were not altered.
	 *
	 * @covers ::vipgoci_wpscan_get_altered_addons_data_and_slugs
	 *
	 * @return void
	 */
	public function testNoResultsFound(): void {
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

		// No files changed.
		$files_affected_by_commit_by_pr = array(
			'all' => array(),
		);

		$results_actual = vipgoci_wpscan_get_altered_addons_data_and_slugs(
			$this->options,
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			),
			$files_affected_by_commit_by_pr
		);

		vipgoci_unittests_output_unsuppress();

		$results_expected = array(
			'plugins/hello'          => array(),
			'plugins/not-a-plugin'   => array(),
			'themes/twentytwentyone' => array(),
		);

		$this->assertSame(
			$results_expected,
			$results_actual
		);
	}

	/**
	 * Test common usage of the function. Only one result expected
	 * as theme was altered, not plugin.
	 *
	 * @covers ::vipgoci_wpscan_get_altered_addons_data_and_slugs
	 *
	 * @return void
	 */
	public function testOneResultFound(): void {
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

		$this->options['wpscan-api-skip-folders'] = array( 'plugins' );

		$files_affected_by_commit_by_pr = $this->getFilesAffectedByPR();

		$results_actual = vipgoci_wpscan_get_altered_addons_data_and_slugs(
			$this->options,
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			),
			$files_affected_by_commit_by_pr
		);

		vipgoci_unittests_output_unsuppress();

		$results_expected = array(
			'plugins/hello'        => array(),
			'plugins/not-a-plugin' => array(),
			'themes/' . $this->options['wpscan-pr-1-theme-key'] => array(
				'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] => array(
					'type'             => 'vipgoci-addon-theme',
					'addon_headers'    => array(
						'Name'       => $this->options['wpscan-pr-1-theme-name'],
						'ThemeURI'   => 'https://wordpress.org/themes/' . $this->options['wpscan-pr-1-theme-slug'] . '/',
						'Version'    => $this->options['wpscan-pr-1-theme-version'],
						'Template'   => '',
						'Status'     => '',
						'TextDomain' => $this->options['wpscan-pr-1-theme-slug'],
						'DomainPath' => '',
						'UpdateURI'  => '',
						'Title'      => $this->options['wpscan-pr-1-theme-name'],
					),
					'name'             => $this->options['wpscan-pr-1-theme-name'],
					'version_detected' => $this->options['wpscan-pr-1-theme-version'],
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-theme-path'],
					'slug'             => $this->options['wpscan-pr-1-theme-slug'],
					'url'              => 'https://wordpress.org/themes/' . $this->options['wpscan-pr-1-theme-slug'] . '/',
				),
			),
		);

		$this->assertIsString(
			$results_actual
				[ $this->options['wpscan-pr-1-theme-dir'] ]
				[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
				['new_version']
		);

		unset(
			$results_actual
				[ $this->options['wpscan-pr-1-theme-dir'] ]
				[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
				['new_version']
		);

		$this->assertStringContainsString(
			'.zip',
			$results_actual
				[ $this->options['wpscan-pr-1-theme-dir'] ]
				[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
				['package']
		);

		unset(
			$results_actual
				[ $this->options['wpscan-pr-1-theme-dir'] ]
				[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
				['package']
		);

		foreach ( array( 'Description', 'Author', 'AuthorURI', 'RequiresWP', 'RequiresPHP', 'AuthorName' ) as $field_name ) {
			$this->assertIsString(
				$results_actual
					[ $this->options['wpscan-pr-1-theme-dir'] ]
					[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
					['addon_headers']
					[ $field_name ]
			);

			unset(
				$results_actual
					[ $this->options['wpscan-pr-1-theme-dir'] ]
					[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
					['addon_headers']
					[ $field_name ]
			);
		}

		$this->assertSame(
			$results_expected,
			$results_actual
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpscan_get_altered_addons_data_and_slugs
	 *
	 * @return void
	 */
	public function testTwoResultsFound(): void {
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

		$files_affected_by_commit_by_pr = $this->getFilesAffectedByPR();

		$results_actual = vipgoci_wpscan_get_altered_addons_data_and_slugs(
			$this->options,
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			),
			$files_affected_by_commit_by_pr
		);

		vipgoci_unittests_output_unsuppress();

		$results_expected = array(
			'plugins/hello'          => array(
				'vipgoci-addon-plugin-hello.php' => array(
					'type'             => 'vipgoci-addon-plugin',
					'addon_headers'    => array(
						'Name'        => $this->options['wpscan-pr-1-plugin-name'],
						'PluginURI'   => 'http://wordpress.org/plugins/' . $this->options['wpscan-pr-1-plugin-slug'] . '/',
						'Version'     => $this->options['wpscan-pr-1-plugin-version'],
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI'   => 'http://ma.tt/',
						'TextDomain'  => '',
						'DomainPath'  => '',
						'Network'     => '',
						'RequiresWP'  => '',
						'RequiresPHP' => '',
						'UpdateURI'   => 'http://wordpress.org/plugins/' . $this->options['wpscan-pr-1-plugin-slug'] . '/',
						'Title'       => $this->options['wpscan-pr-1-plugin-name'],
						'AuthorName'  => 'Matt Mullenweg',
					),
					'name'             => $this->options['wpscan-pr-1-plugin-name'],
					'version_detected' => $this->options['wpscan-pr-1-plugin-version'],
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-plugin-path'],
					'slug'             => $this->options['wpscan-pr-1-plugin-slug'],
					'new_version'      => '1.7.2', // FIXME.
					'package'          => 'https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip',
					'url'              => 'https://wordpress.org/plugins/' . $this->options['wpscan-pr-1-plugin-slug'] . '/',
					'id'               => 'w.org/plugins/' . $this->options['wpscan-pr-1-plugin-slug'],
					'plugin'           => $this->options['wpscan-pr-1-plugin-key'],
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-plugin-path'],
				),
			),
			'plugins/not-a-plugin'   => array(),
			'themes/twentytwentyone' => array(
				'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-key'] => array(
					'type'             => 'vipgoci-addon-theme',
					'addon_headers'    => array(
						'Name'       => $this->options['wpscan-pr-1-theme-name'],
						'ThemeURI'   => 'https://wordpress.org/themes/' . $this->options['wpscan-pr-1-theme-slug'] . '/',
						'Version'    => $this->options['wpscan-pr-1-theme-version'],
						'Template'   => '',
						'Status'     => '',
						'TextDomain' => $this->options['wpscan-pr-1-theme-slug'],
						'DomainPath' => '',
						'UpdateURI'  => '',
						'Title'      => $this->options['wpscan-pr-1-theme-name'],
					),
					'name'             => $this->options['wpscan-pr-1-theme-name'],
					'version_detected' => $this->options['wpscan-pr-1-theme-version'],
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-theme-path'],
					'slug'             => $this->options['wpscan-pr-1-theme-slug'],
					'url'              => 'https://wordpress.org/themes/' . $this->options['wpscan-pr-1-theme-slug'] . '/',
				),
			),
		);

		$this->assertIsString(
			$results_actual
				[ $this->options['wpscan-pr-1-theme-dir'] ]
				[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
				['new_version']
		);

		unset(
			$results_actual
				[ $this->options['wpscan-pr-1-theme-dir'] ]
				[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
				['new_version']
		);

		$this->assertStringContainsString(
			'.zip',
			$results_actual
				[ $this->options['wpscan-pr-1-theme-dir'] ]
				[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
				['package']
		);

		unset(
			$results_actual
				[ $this->options['wpscan-pr-1-theme-dir'] ]
				[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
				['package']
		);

		foreach ( array( 'Description', 'Author', 'AuthorURI', 'RequiresWP', 'RequiresPHP', 'AuthorName' ) as $field_name ) {
			$this->assertIsString(
				$results_actual
					[ $this->options['wpscan-pr-1-theme-dir'] ]
					[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
					['addon_headers']
					[ $field_name ]
			);

			unset(
				$results_actual
					[ $this->options['wpscan-pr-1-theme-dir'] ]
					[ 'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-slug'] ]
					['addon_headers']
					[ $field_name ]
			);
		}

		$this->assertSame(
			$results_expected,
			$results_actual
		);
	}
}
