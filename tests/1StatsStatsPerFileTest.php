<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class StatsStatsPerFileTest extends TestCase {
	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'github-token'			=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-repo-fetch-committed-file-1'	=> null,
	);

	protected function setUp() {
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
	}

	/**
	 * @covers ::vipgoci_stats_per_file
	 */
	public function testStatsPerFile1() {
		foreach ( $this->options as $option_key => $option_value ) {
			if ( 'github-token' === $option_key ) {
				continue;
			}

			if ( null === $option_value ) {
				$this->markTestSkipped(
					'Skipping test, not configured correctly'
				);

				return;
			}
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-fetch-committed-file-1'];

		ob_start();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);


		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					ob_get_flush()
			);
		}


		$this->options['token'] =
			$this->options['github-token'];

		vipgoci_stats_per_file(
			$this->options,
			'file-1.txt',
			'myspecifictest'
		);

		ob_end_clean();

		$stats = vipgoci_counter_report(
			VIPGOCI_COUNTERS_DUMP
		);

		$this->assertTrue(
			( isset( $stats['github_pr_files_myspecifictest'] ) ) &&
			( $stats['github_pr_files_myspecifictest'] == 1 )
		);

		$this->assertTrue(
			( isset( $stats['github_pr_lines_myspecifictest'] ) ) &&
			( $stats['github_pr_lines_myspecifictest'] == 2 )
		);
	}
}
