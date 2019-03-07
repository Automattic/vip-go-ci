<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrFilesChangedTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-pr-files-changed-1'	=> null,
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
	 * @covers ::vipgoci_github_prs_implicated
	 */
	public function testGitHubPrFilesChanged1() {
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

		$this->options['commit'] =
			$this->options['commit-test-repo-pr-files-changed-1'];

		ob_start();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		ob_end_clean();

		$this->assertEquals(
			259078544,
			$prs_implicated[9]->id
		);

		$this->assertEquals(
			'5d57f5ba2c299cc6cb170e57d5c1139e41b86b80',
			$prs_implicated[9]->merge_commit_sha
		);

		$this->assertEquals(
			'open',
			$prs_implicated[9]->state
		);

		unset( $this->options['commit'] );
	}
}
