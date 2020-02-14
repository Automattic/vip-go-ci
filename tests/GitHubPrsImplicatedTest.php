<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrsImplicatedTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-pr-files-changed-1'	=> null,
	);

	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
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

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

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
	public function testGitHubPrsImplicated1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-pr-files-changed-1'];

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			259078544,
			$prs_implicated[9]->id
		);

		$this->assertEquals(
			'80ebd6d65db88e87665b6ff1aa045f68d17ddeb7',
			$prs_implicated[9]->merge_commit_sha
		);

		$this->assertEquals(
			'open',
			$prs_implicated[9]->state
		);

		unset( $this->options['commit'] );
	}
}
