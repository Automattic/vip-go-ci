<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscScandirGitRepoTest extends TestCase {
	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
		'github-token'		=> null,
	);

	var $git_repo_tests = array(
		'commit-test-scandir-repo-test-1'	=> null,
		'commit-test-scandir-repo-test-2'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->git_repo_tests
		);

		$this->options = array_merge(
			$this->options_git,
			$this->git_repo_tests
		);

		$this->options['token'] =
			$this->options['github-token'];
	}

	protected function tearDown() {
		$this->options_git = null;
		$this->git_repo_tests = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_svg_scan_single_file
	 */
	public function testSvgScandirRepoTest1() {
		$this->options['commit'] =
			$this->options['commit-test-scandir-repo-test-1'];

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

		$ret = vipgoci_scandir_git_repo(
			$this->options['local-git-repo'],
			null
		);

		ob_end_clean();

		$this->assertEquals(
			$ret,
			array(
				'README.md',
    				'myfile1.txt',	
				'myfile2.txt',
				'myfolder5/myotherfolder6/somefile2.txt',
				'myfolder5/somefile1.txt'
			)
		);
	}

	/**
	 * @covers ::vipgoci_svg_scan_single_file
	 */
	public function testSvgScandirRepoTest2() {
		$this->options['commit'] =
			$this->options['commit-test-scandir-repo-test-2'];

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

		$ret = vipgoci_scandir_git_repo(
			$this->options['local-git-repo'],
			array(
				'file_extensions'	=> array( 'md' )
			)
		);

		ob_end_clean();

		$this->assertEquals(
			$ret,
			array(
				'README.md'
			)
		);
	}
}
