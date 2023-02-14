<?php
/**
 * Test vipgoci_github_fetch_commit_info() function.
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
final class GitHubFetchCommitInfoTest extends TestCase {
	/**
	 * Variable for commit info.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git_repo_tests = array(
		'commit-test-repo-fetch-commit-info-1' => null,
	);

	/**
	 * Variable for GitHub info.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'repo-name'  => null,
		'repo-owner' => null,
	);

	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set up function. Require files, set variables, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
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

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['branches-ignore'] = array();
	}

	/**
	 * Tear down function, remove git repo, etc.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $this->options_git_repo_tests );
		unset( $this->options_git );
		unset( $this->options );
	}

	/**
	 * Call the function, verify if correct information is returned.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_github_fetch_commit_info
	 */
	public function testFetchCommitInfo1() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-fetch-commit-info-1'];

		vipgoci_unittests_output_suppress();

		$commit_info = vipgoci_github_fetch_commit_info(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			null
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'2533219d08192025f3209a17ddcf9ff21845a08c',
			$commit_info->sha
		);

		unset(
			$commit_info->files[0]->blob_url,
			$commit_info->files[0]->raw_url,
			$commit_info->files[0]->contents_url
		);

		$this->assertSame(
			array(
				'sha'       => '524acfffa760fd0b8c1de7cf001f8dd348b399d8',
				'filename'  => 'test1.txt',
				'status'    => 'added',
				'additions' => 1,
				'deletions' => 0,
				'changes'   => 1,
				'patch'     => '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
			),
			(array) $commit_info->files[0]
		);

		unset( $this->options['commit'] );
	}
}
