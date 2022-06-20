<?php
/**
 * Test vipgoci_stats_per_file() function.
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
final class StatsStatsPerFileTest extends TestCase {
	/**
	 * Options for git.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'git-path'        => null,
		'github-repo-url' => null,
		'repo-owner'      => null,
		'repo-name'       => null,
	);

	/**
	 * Options for git repo tests.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git_repo_tests = array(
		'commit-test-repo-fetch-committed-file-1' => null,
	);

	/**
	 * Require files, set variable values.
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
			$this->options_git_repo_tests
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_git_repo_tests
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
	}

	/**
	 * Clean up, remove git repository, remove variable.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options_git );
		unset( $this->options_git_repo_tests );
		unset( $this->options );
	}

	/**
	 * Test if statistics collection for file works correctly.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_stats_per_file
	 */
	public function testStatsPerFile1() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-fetch-committed-file-1'];

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
		}

		$this->options['token'] =
			$this->options['github-token'];

		vipgoci_stats_per_file(
			$this->options,
			'file-1.txt',
			'myspecifictest'
		);

		vipgoci_unittests_output_unsuppress();

		$stats = vipgoci_counter_report(
			VIPGOCI_COUNTERS_DUMP
		);

		$this->assertTrue(
			( isset( $stats['github_pr_files_myspecifictest'] ) ) &&
			( 1 === $stats['github_pr_files_myspecifictest'] )
		);

		$this->assertTrue(
			( isset( $stats['github_pr_lines_myspecifictest'] ) ) &&
			( 2 === $stats['github_pr_lines_myspecifictest'] )
		);
	}
}
