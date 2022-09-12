<?php
/**
 * Test vipgoci_scandir_git_repo().
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
final class MiscScandirGitRepoTest extends TestCase {
	/**
	 * Git options.
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
	 * Options for git repo tests.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $git_repo_tests = array(
		'commit-test-scandir-repo-test-1' => null,
		'commit-test-scandir-repo-test-2' => null,
	);

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->git_repo_tests
		);

		$this->options = array_merge(
			$this->options_git,
			$this->git_repo_tests
		);

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		$this->options['token'] =
			$this->options['github-token'];
	}

	/**
	 * Clean up function, remove repository and unset variables.
	 */
	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options_git );
		unset( $this->git_repo_tests );
		unset( $this->options );
	}

	/**
	 * Test function with subdirectories enabled and no filter.
	 *
	 * @covers ::vipgoci_scandir_git_repo
	 */
	public function testScandirRepoTestWithSubdirectoriesAndNoFilter() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-scandir-repo-test-1'];

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

		$ret = vipgoci_scandir_git_repo(
			$this->options['local-git-repo'],
			true,
			null
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'README.md',
				'myfile1.txt',
				'myfile2.txt',
				'myfolder5/myotherfolder6/somefile2.txt',
				'myfolder5/somefile1.txt',
			),
			$ret
		);
	}

	/**
	 * Test function with subdirectories enabled and filter applied.
	 *
	 * @covers ::vipgoci_scandir_git_repo
	 */
	public function testScandirRepoTestWithSubdirectoriesAndFilter() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-scandir-repo-test-2'];

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

		$ret = vipgoci_scandir_git_repo(
			$this->options['local-git-repo'],
			true,
			array(
				'file_extensions' => array( 'md' ),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'README.md',
			),
			$ret
		);
	}
}
