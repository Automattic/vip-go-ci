<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitRepoDiffsFetchUnfilteredTest extends TestCase {
	var $options_git_repo_tests = array(
		'commit-test-repo-pr-diffs-1-a'	=> null,
		'commit-test-repo-pr-diffs-1-b'	=> null,
		'commit-test-repo-pr-diffs-1-c'	=> null,
		'commit-test-repo-pr-diffs-1-d'	=> null,
		'commit-test-repo-pr-diffs-1-e'	=> null,
		'commit-test-repo-pr-diffs-1-f' => null,
		'commit-test-repo-pr-diffs-1-g' => null,
		'commit-test-repo-pr-diffs-2-a' => null,
		'commit-test-repo-pr-diffs-2-b' => null,
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
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch1() {
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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-b']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'files' => array(
					'README.md'			=> array(
						/* Only permission is changed, no content change */
						'filename'	=> 'README.md',
						'patch'		=> '',
						'status'	=> 'modified',
						'additions'	=> 0,
						'deletions'	=> 0,
						'changes'	=> 0,
					),

					'content-changed-file.txt'	=> array(
						/* New file, content is added */
						'filename'	=> 'content-changed-file.txt',
						'patch'		=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
						'status'	=> 'added',
						'additions'	=> 1,
						'deletions'	=> 0,
						'changes'	=> 1,
					)
				),

				'statistics' => array(
					'additions'	=> 1,
					'deletions'	=> 0,
					'changes'	=> 1,
				)
			),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch2() {
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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-c']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'files' => array(
					'content-changed-file.txt'	=> array(
						/* New file, content is added */
						'filename'	=> 'content-changed-file.txt',
						'patch'		=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
						'status'	=> 'added',
						'additions'	=> 1,
						'deletions'	=> 0,
						'changes'	=> 1,
					),
					'renamed-file.txt'		=> array(
						/* Renamed file, no content change */
						'filename'		=> 'renamed-file.txt',
						'patch'			=> '',
						'status'		=> 'renamed',
						'additions'		=> 0,
						'deletions'		=> 0,
						'changes'		=> 0,
						'previous_filename'	=> 'README.md',
					),
				),

				'statistics' => array(
					'additions'	=> 1,
					'deletions'	=> 0,
					'changes'	=> 1,
				)
			),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch3() {
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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-a'],
			$this->options['commit-test-repo-pr-diffs-1-e']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'files' => array(
					'README.md'	=> array(
						/* Removed file */
						'filename'	=> 'README.md',
						'patch'		=> '@@ -1,2 +0,0 @@' . PHP_EOL .'-# vip-go-ci-testing' . PHP_EOL . '-Pull-Requests, commits and data to test <a href="https://github.com/automattic/vip-go-ci/">vip-go-ci</a>\'s functionality. Please do not remove or alter unless you\'ve contacted the VIP Team first. ',
						'status'	=> 'removed',
						'additions'	=> 0,
						'deletions'	=> 2,
						'changes'	=> 2,
					),
					'content-changed-file.txt'		=> array(
						/* Renamed file, no content change */
						'filename'	=> 'content-changed-file.txt',
						'patch'		=> '@@ -0,0 +1 @@' . PHP_EOL . '+Test file',
						'status'	=> 'added',
						'additions'	=> 1,
						'deletions'	=> 0,
						'changes'	=> 1,
					),
				),
	
				'statistics' => array(
					'additions'	=> 1,
					'deletions'	=> 2,
					'changes'	=> 3,
				)
			),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch4() {
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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-e'],
			$this->options['commit-test-repo-pr-diffs-1-f']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'files' => array(
					'content-changed-file.txt'		=> array(
						/* Adding content to existing file */
						'filename'	=> 'content-changed-file.txt',
						'patch'		=> '@@ -1 +1,2 @@' . PHP_EOL . ' Test file' . PHP_EOL . '+New text',
						'status'	=> 'modified',
						'additions'	=> 1,
						'deletions'	=> 0,
						'changes'	=> 1,
					),
				),

				'statistics' => array(
					'additions'	=> 1,
					'deletions'	=> 0,
					'changes'	=> 1,
				)
			),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch5() {
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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-1-f'],
			$this->options['commit-test-repo-pr-diffs-1-g']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'files' => array(
					'content-changed-file.txt'		=> array(
						/* Removing content from existing file */
						'filename'	=> 'content-changed-file.txt',
						'patch'		=> '@@ -1,2 +1 @@' . PHP_EOL . '-Test file' . PHP_EOL . ' New text',
						'status'	=> 'modified',
						'additions'	=> 0,
						'deletions'	=> 1,
						'changes'	=> 1,
					),
				),
	
				'statistics' => array(
					'additions'	=> 0,
					'deletions'	=> 1,
					'changes'	=> 1,
				)
			),
			$diff
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch6() {
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

		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-2-a'],
			$this->options['commit-test-repo-pr-diffs-2-b']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'files' => array(
					'a/new-file.txt'		=> array(
						/* File added, starting with name 'a/' */
						'filename'	=> 'a/new-file.txt',
						'patch'		=> '@@ -0,0 +1,2 @@' . PHP_EOL . '+Line 1' . PHP_EOL . '+Line 2',
						'status'	=> 'added',
						'additions'	=> 2,
						'deletions'	=> 0,
						'changes'	=> 2,
					),

					'b/new-file.txt'		=> array(
						/* File added, starting with name 'b/' */
						'filename'	=> 'b/new-file.txt',
						'patch'		=> '@@ -0,0 +1,3 @@' . PHP_EOL . '+Test 1' . PHP_EOL . '+Test 2' . PHP_EOL . '+Test 3',
						'status'	=> 'added',
						'additions'	=> 3,
						'deletions'	=> 0,
						'changes'	=> 3,
					),

					'new-file-permissions-changed.txt'	=> array(
						/* New file, with permissions changed */
						'filename'	=> 'new-file-permissions-changed.txt',
						'patch'		=> '@@ -0,0 +1,4 @@' . PHP_EOL . '+This is also a new file.' . PHP_EOL . '+Line 2' . PHP_EOL . '+Line 3' . PHP_EOL . '+Line 4',
						'status'	=> 'added',
						'additions'	=> 4,
						'deletions'	=> 0,
						'changes'	=> 4,
					),

					'new-file.txt'			=> array(
						/* New file */
						'filename'	=> 'new-file.txt',
						'patch'		=> '@@ -0,0 +1,4 @@' . PHP_EOL . '+This is a new file.' . PHP_EOL . '+Line 2' . PHP_EOL . '+Line 3' . PHP_EOL . '+Line 4',
						'status'	=> 'added',
						'additions'	=> 4,
						'deletions'	=> 0,
						'changes'	=> 4,
					),

					'permission-changed.txt'	=> array(
						/* Permission changed */
						'filename'	=> 'permission-changed.txt',
						'patch'		=> '',
						'status'	=> 'modified',
						'additions'	=> 0,
						'deletions'	=> 0,
						'changes'	=> 0,
					),

					'to-be-added-to.txt'		=> array(
						/* File was added to */
						'filename'	=> 'to-be-added-to.txt',
						'patch'		=> '@@ -1,2 +1,4 @@' . PHP_EOL . ' File to be added to.' . PHP_EOL . ' Line 2' . PHP_EOL . '+Line 3' . PHP_EOL . '+Line 4',
						'status'	=> 'modified',
						'additions'	=> 2,
						'deletions'	=> 0,
						'changes'	=> 2,
					),

					'to-be-removed-from.txt'	=> array(
						/* File to be removed from */
						'filename'	=> 'to-be-removed-from.txt',
						'patch'		=> '@@ -1,3 +1 @@' . PHP_EOL . ' Content of file' . PHP_EOL . '-Line 2' . PHP_EOL . '-Line 3',
						'status'	=> 'modified',
						'additions'	=> 0,
						'deletions'	=> 2,
						'changes'	=> 2,
					),

					'to-be-renamed.txt'		=> array(
						/* File was actually renamed, but git also indicates removal */
						'filename'	=> 'to-be-renamed.txt',
						'patch'		=> '@@ -1,4 +0,0 @@' . PHP_EOL . '-This file will be renamed.' . PHP_EOL . '-Line 2' . PHP_EOL . '-Line 3' . PHP_EOL . '-Line 4',
						'status'	=> 'removed',
						'additions'	=> 0,
						'deletions'	=> 4,
						'changes'	=> 4,
					),

					'to-be-renamed2.txt'		=> array(
						/* File was renamed */
						'filename'		=> 'to-be-renamed2.txt',
						'patch'			=> '',
						'status'		=> 'renamed',
						'additions'		=> 0,
						'deletions'		=> 0,
						'changes'		=> 0,
						'previous_filename'	=> 'to-be-removed.txt',
					),
				),
		
				'statistics' => array(
					'additions'	=> 15,
					'deletions'	=> 6,
					'changes'	=> 21,
				)
			),
			$diff
		);
	
		/*
		 * As an additional check, verify that caching is OK.
		 */
		$diff_same = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-2-a'],
			$this->options['commit-test-repo-pr-diffs-2-b']
		);

		$this->assertSame(
			$diff,
			$diff_same
		);
	}

	/**
	 * Check diff between commits.
	 *
	 * @covers ::vipgoci_gitrepo_diffs_fetch_unfiltered
	 */
	public function testGitRepoDiffsFetch7() {
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

		/*
		 * Try with invalid object, should
		 * return with null
		 */
		$diff = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$this->options['local-git-repo'],
			$this->options['commit-test-repo-pr-diffs-2-a'],
			'1111111111111111111111111111111111111111'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			null,
			$diff
		);
	}
}

