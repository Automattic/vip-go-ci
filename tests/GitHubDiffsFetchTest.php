<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubDiffsFetchTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-pr-diffs-1-a'	=> null,
		'commit-test-repo-pr-diffs-1-b'	=> null,
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
	 * @covers ::vipgoci_github_diffs_fetch
	 */
	public function testGitHubDiffsFetch1() {
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

		$diff = vipgoci_github_diffs_fetch(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-b'],
			true
		);

		ob_end_clean();

		$this->assertEquals(
			array(
				'test1.txt'	=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
			),
			$diff
		);
	}
}
