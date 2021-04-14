<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitRepoDiffsFetchTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-pr-diffs-1-a'	=> null,
		'commit-test-repo-pr-diffs-1-b'	=> null,
		'commit-test-repo-pr-diffs-1-c'	=> null,
		'commit-test-repo-pr-diffs-1-d'	=> null,
		'commit-test-repo-pr-diffs-1-e'	=> null,
	);

	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
	);

	protected function setUp(): void {
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

		/* By default checkout 'master' branch */
		$this->options['commit'] = 'master';

		$this->options['local-git-repo'] = false;
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options_git_repo_tests = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * Check diff between commits; do not ask
	 * for renamed files, removed files or files
	 * that had permissions changed to be included.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-b'],
			false,
			false,
			false
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files' => array(
					'content-changed-file.txt'	=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
				),

				'statistics' => array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 1,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 1,
				),
			),
			$diff
		);
	}

	/**
 	 * Test diff between commits; do ask for
	 * files with changed permissions to be included
	 * in the results.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-b'],
			false,
			false,
			true
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'	=> array(
					'content-changed-file.txt'	=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
					'README.md'			=> null,
				),

				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 1,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 1,
				),
			),
			$diff
		);
	}

	/**
 	 * Test diff between commits; do ask for
	 * renamed files to be included
	 * in the results.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-c'],
			$this->options['commit-test-repo-pr-diffs-1-d'],
			true,
			false,
			false
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'		=> array(
					'renamed-file2.txt'			=> null,
				),

				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 0,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 0,
				),
			),
			$diff
		);
	}

	/**
 	 * Test diff between commits; do not ask
	 * for renamed files to be included
	 * in the results.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch4() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-c'],
			$this->options['commit-test-repo-pr-diffs-1-d'],
			false,
			false,
			false
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'		=> array(
				),

				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 0,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 0,
				),
			),
			$diff
		);
	}

	/**
 	 * Test diff between commits; do ask for
	 * removed files to be included
	 * in the results.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch5() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-d'],
			$this->options['commit-test-repo-pr-diffs-1-e'],
			false,
			true,
			false
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'		=> array(
					'renamed-file2.txt'	=>
						'@@ -1,2 +0,0 @@' . PHP_EOL .
						'-# vip-go-ci-testing' . PHP_EOL .
						'-Pull-Requests, commits and data to test <a href="https://github.com/automattic/vip-go-ci/">vip-go-ci</a>\'s functionality. Please do not remove or alter unless you\'ve contacted the VIP Team first. ',
				),

				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 0,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 2,
					'changes'				=> 2,
				),
			),
			$diff
		);
	}

	/**
 	 * Test diff between commits; do not ask for
	 * removed files to be included
	 * in the results.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch6() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-d'],
			$this->options['commit-test-repo-pr-diffs-1-e'],
			false,
			false,
			false
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'		=> array(
				),
				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 0,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 0,
				),
			),
			$diff
		);
	}


	/**
 	 * Test diff between commits; do ask for
	 * all files to be included.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch7() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-d'],
			true,
			true,
			true
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'		=> array(
					'content-changed-file.txt' => '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
					'renamed-file2.txt' => null,
				),

				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 1,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 1,
				),
			),
			$diff
		);
	}

	/**
 	 * Test diff between commits; do ask for
	 * all files to be included. Test filtering
	 * of files.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch8() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-d'],
			true,
			true,
			true,
			array(
				'file_extensions' => array(
					'ini'
				)
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'		=> array(
				),
				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 0,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 0,
				),
			),
			$diff
		);
	}

	/**
 	 * Test diff between commits; do ask for
	 * all files to be included. Test filtering
	 * of files.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch9() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-d'],
			true,
			true,
			true,
			array(
				'file_extensions' => array(
					'txt'
				)
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'		=> array(
					'content-changed-file.txt'	=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
					'renamed-file2.txt'		=> null,
				),

				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 1,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 1,
				),
			),
			$diff
		);
	}

	/**
 	 * Test diff between commits; do ask for
	 * some files to be included. Test filtering
	 * of files. Also, test interaction between
	 * filtering and files to be included.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch
	 */
	public function testGitHubDiffsFetch10() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

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

			return;
		}

		$diff = vipgoci_gitrepo_diffs_fetch(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-d'],
			false,
			true,
			true,
			array(
				'file_extensions' => array(
					'txt'
				)
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			array(
				'files'		=> array(
					'content-changed-file.txt' => '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
				),

				'statistics'	=> array(
					VIPGOCI_GIT_DIFF_CALC_CHANGES['+']	=> 1,
					VIPGOCI_GIT_DIFF_CALC_CHANGES['-']	=> 0,
					'changes'				=> 1,
				),
			),
			$diff
		);
	}
}
