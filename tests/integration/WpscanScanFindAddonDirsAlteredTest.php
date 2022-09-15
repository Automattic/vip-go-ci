<?php
/**
 * Test vipgoci_wpscan_find_addon_dirs_altered() function.
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
final class WpscanScanFindAddonDirsAlteredTest extends TestCase {
	/**
	 * Variable for WPScan API scanning.
	 *
	 * @var $options_wpscan_api_scan
	 */
	private array $options_wpscan_api_scan = array(
		'wpscan-pr-1-commit-id' => null,
		'wpscan-pr-1-dirs-scan' => null,
		'wpscan-pr-2-commit-id' => null,
		'wpscan-pr-2-dirs-scan' => null,
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

		$this->options['wpscan-api-skip-folders'] = array();

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;
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
	 * Test when addon is added to pull request.
	 *
	 * @covers ::vipgoci_wpscan_find_addon_dirs_altered
	 *
	 * @return void
	 */
	public function testFindDirsAlteredAddonAdded(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$this->options['commit'] =
			$this->options['wpscan-pr-1-commit-id'];

		$this->options['wpscan-api-paths'] = explode(
			',',
			$this->options['wpscan-pr-1-dirs-scan']
		);

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

		$commit_skipped_files = array();

		$results_actual = vipgoci_wpscan_find_addon_dirs_altered(
			$this->options,
			$commit_skipped_files
		);

		$results_expected = array(
			'plugins',
			'plugins/hello',
			'plugins/not-a-plugin',
			'themes/twentytwentyone',
		);

		$this->assertSame(
			$results_expected,
			$results_actual
		);
	}

	/**
	 * Test when addon is updated in pull request.
	 *
	 * @covers ::vipgoci_wpscan_find_addon_dirs_altered
	 *
	 * @return void
	 */
	public function testFindDirsAlteredAddonUpdated(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$this->options['commit'] =
			$this->options['wpscan-pr-2-commit-id'];

		$this->options['wpscan-api-paths'] = explode(
			',',
			$this->options['wpscan-pr-2-dirs-scan']
		);

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

		$commit_skipped_files = array();

		$results_actual = vipgoci_wpscan_find_addon_dirs_altered(
			$this->options,
			$commit_skipped_files
		);

		$results_expected = array(
			'plugins/hello',
		);

		$this->assertSame(
			$results_expected,
			$results_actual
		);
	}
}
