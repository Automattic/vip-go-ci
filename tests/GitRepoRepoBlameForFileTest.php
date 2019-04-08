<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitRepoRepoBlameForFileTest extends TestCase {
	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
		'repo-owner'			=> null,
		'repo-name'			=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-repo-blame-for-file-1'	=> null,
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
	
		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);
	}

	protected function tearDown() {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}
	}

	/**
	 * @covers ::vipgoci_gitrepo_blame_for_file
	 */
	public function testRepoFetchTree1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-blame-for-file-1'];

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


		$ret = vipgoci_gitrepo_blame_for_file(
			$this->options['commit'],
			'README.md',
			$this->options['local-git-repo']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			'[{"commit_id":"4869335189752462325aaef4838c9761d56195ce","file_name":"README.md","line_no":1},{"commit_id":"45b9e6479dfba4d54b584d53ace1814ce155d35e","file_name":"README.md","line_no":2}]',
			json_encode( $ret )
		);
	}
}
