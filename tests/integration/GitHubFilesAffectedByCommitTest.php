<?php
/**
 * Test vipgoci_github_files_affected_by_commit() function.
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
final class GitHubFilesAffectedByCommitTest extends TestCase {
	/**
	 * Options for git.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git_repo_tests = array(
		'commit-test-github-files-affected-by-commit-2' => null,
		'commit-test-github-files-affected-by-commit-4' => null,
		'pr-test-github-files-affected-by-commit-a'     => null,
		'pr-test-github-files-affected-by-commit-b'     => null,
		'pr-test-github-files-affected-by-commit-c'     => null,
	);

	/**
	 * Options for git repo tests.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git = array(
		'git-path'        => null,
		'github-repo-url' => null,
		'repo-owner'      => null,
		'repo-name'       => null,
	);

	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Setup function. Require file, set up variables.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->options_git_repo_tests
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		$this->options = array_merge(
			$this->options_git_repo_tests,
			$this->options_git,
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

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;

		$this->options['local-git-repo'] = false;
	}

	/**
	 * Remove local git repository, unset variables.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options_git_repo_tests );
		unset( $this->options_git );
		unset( $this->options );
	}

	/**
	 * Test simple usage of the function.
	 *
	 * Test usage with no filter applied, and all
	 * pull request IDs present in results.
	 *
	 * @covers ::vipgoci_github_files_affected_by_commit
	 *
	 * @return void
	 */
	public function testGitHubFilesAffectedByCommitSimple(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] = $this->options['commit-test-github-files-affected-by-commit-2'];

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

		$commit_skipped_files = array(); // No skipped files.

		$files_affected_by_pr = vipgoci_github_files_affected_by_commit(
			$this->options,
			$this->options['commit'],
			$commit_skipped_files, // Reference.
			true,
			true,
			true,
			null, // No filter.
			true // Include all pull request IDs in results.
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'all' => array(
					0 => 'README.md',
					1 => 'tests1/some_phpcs_issues.php',
				),
				$this->options['pr-test-github-files-affected-by-commit-b'] => array(
					0 => 'README.md',
					1 => 'tests1/some_phpcs_issues.php',
				),
				$this->options['pr-test-github-files-affected-by-commit-a'] => array(
					0 => 'README.md',
				),
			),
			$files_affected_by_pr
		);
	}

	/**
	 * Test usage with filter applied, do not ask
	 * for all pull request IDs to be present.
	 *
	 * @covers ::vipgoci_github_files_affected_by_commit
	 *
	 * @return void
	 */
	public function testGitHubFilesAffectedByCommitWithFilter1(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] = $this->options['commit-test-github-files-affected-by-commit-2'];

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

		$commit_skipped_files = array(); // No skipped files.

		$files_affected_by_pr = vipgoci_github_files_affected_by_commit(
			$this->options,
			$this->options['commit'],
			$commit_skipped_files, // Reference.
			true,
			true,
			true,
			array(
				'file_extensions' => array( 'php' ), // Filter applied.
			),
			false // Do not define all pull request IDs.
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'all' => array(
					0 => 'tests1/some_phpcs_issues.php',
				),
				$this->options['pr-test-github-files-affected-by-commit-b'] => array(
					0 => 'tests1/some_phpcs_issues.php',
				),
			),
			$files_affected_by_pr
		);
	}

	/**
	 * Test usage with filter applied and ask for all
	 * pull request IDs to be present in results.
	 *
	 * @covers ::vipgoci_github_files_affected_by_commit
	 *
	 * @return void
	 */
	public function testGitHubFilesAffectedByCommitWithFilter2(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] = $this->options['commit-test-github-files-affected-by-commit-2'];

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

		$commit_skipped_files = array(); // No skipped files.

		$files_affected_by_pr = vipgoci_github_files_affected_by_commit(
			$this->options,
			$this->options['commit'],
			$commit_skipped_files, // Reference.
			true,
			true,
			true,
			array(
				'file_extensions' => array( 'php' ), // Filter applied.
			),
			true // All pull request IDs to be present.
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'all' => array(
					0 => 'tests1/some_phpcs_issues.php',
				),
				$this->options['pr-test-github-files-affected-by-commit-b'] => array(
					0 => 'tests1/some_phpcs_issues.php',
				),
				$this->options['pr-test-github-files-affected-by-commit-a'] => array(),
			),
			$files_affected_by_pr,
		);
	}

	/**
	 * Test usage with no filter applied, and a file
	 * that should be skipped due to length.
	 *
	 * @covers ::vipgoci_github_files_affected_by_commit
	 *
	 * @return void
	 */
	public function testGitHubFilesAffectedByCommitWithSkippedFile(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] = $this->options['commit-test-github-files-affected-by-commit-2'];

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

		// File to be skipped.
		$commit_skipped_files = array(
			$this->options['pr-test-github-files-affected-by-commit-a'] => array(
				'issues' => array(
					VIPGOCI_VALIDATION_MAXIMUM_LINES => array(
						'README.md',
					),
				),
			),
		);

		$files_affected_by_pr = vipgoci_github_files_affected_by_commit(
			$this->options,
			$this->options['commit'],
			$commit_skipped_files, // Reference.
			true,
			true,
			true,
			array(), // No filter applied.
			true // All pull request IDs should be present in results.
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'all' => array(
					0 => 'README.md',
					1 => 'tests1/some_phpcs_issues.php',
				),
				$this->options['pr-test-github-files-affected-by-commit-b'] => array(
					0 => 'README.md',
					1 => 'tests1/some_phpcs_issues.php',
				),
				$this->options['pr-test-github-files-affected-by-commit-a'] => array(),
			),
			$files_affected_by_pr
		);
	}

	/**
	 * Test usage with no filter applied, without removed
	 * files included in results, and all pull request IDs present
	 * in results.
	 *
	 * @covers ::vipgoci_github_files_affected_by_commit
	 *
	 * @return void
	 */
	public function testGitHubFilesAffectedByCommitWithoutRemovedFiles(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] = $this->options['commit-test-github-files-affected-by-commit-4'];

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

		$commit_skipped_files = array(); // No skipped files.

		$files_affected_by_pr = vipgoci_github_files_affected_by_commit(
			$this->options,
			$this->options['commit'],
			$commit_skipped_files, // Reference.
			true,
			false, // No removed files.
			true,
			array(), // No filter.
			true // All pull request IDs should be present in results.
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'all' => array(),
				$this->options['pr-test-github-files-affected-by-commit-c'] => array(),
			),
			$files_affected_by_pr
		);
	}

}
