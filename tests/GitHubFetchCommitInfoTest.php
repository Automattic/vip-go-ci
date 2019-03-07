<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubFetchCommitInfoTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-fetch-commit-info-1'	=> null,
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
	 * @covers ::vipgoci_github_fetch_commit_info
	 */
	public function testLintDoScan1() {
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
			$this->options['commit-test-repo-fetch-commit-info-1'];

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

			return;
		}

		$commit_info = vipgoci_github_fetch_commit_info(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			null
		);

		ob_end_clean();

		$this->assertEquals(
			'2533219d08192025f3209a17ddcf9ff21845a08c',
			$commit_info->sha
		);

		unset(
			$commit_info->files[0]->blob_url,
			$commit_info->files[0]->raw_url,
			$commit_info->files[0]->contents_url
		);

		$this->assertEquals(
			array(
				'sha'		=> '524acfffa760fd0b8c1de7cf001f8dd348b399d8',
				'filename'	=> 'test1.txt',
				'status'	=> 'added',
				'additions'	=> 1,
				'deletions'	=> 0,
				'changes'	=> 1,
				'patch'		=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
			),
			(array) $commit_info->files[0]
		);


		unset( $this->options['commit'] );
	}
}
