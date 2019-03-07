<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrsCommitsListTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-prs-commits-list-1'	=> null,
		'pr-test-repo-prs-commits-list-1'	=> null,
	);

	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
		'github-token'		=> null,
	);

	protected function setUp() {
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

		$this->options['skip-folders'] = array();

		$this->options['branches-ignore'] = array();
	}

	protected function tearDown() {
		$this->options_git_repo_tests = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_prs_commits_list
	 */
	public function testGitHubPrsCommitsList1() {
		foreach( array_keys( $this->options ) as $option_key ) {
			if ( 'github-token' === $option_key ) {
				continue;
			}

			if ( null === $this->options[ $option_key ] ) {
				$this->markTestSkipped(
					'Skipping test, not configured correctly, missing option ' . $option_key
				);

				return;
			}
		}

		ob_start();

		$commits_list = vipgoci_github_prs_commits_list(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-repo-prs-commits-list-1'],
			$this->options['github-token']
		);

		ob_end_clean();

		$this->assertEquals(
			array(
				$this->options['commit-test-repo-prs-commits-list-1']
			),

			$commits_list
		);
	}
}
