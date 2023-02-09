<?php
/**
 * Test vipgoci_gitrepo_get_file_at_commit() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class GitRepoRepoGetFileAtCommitTest extends TestCase {
	/**
	 * Git options.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'repo-owner'      => null,
		'repo-name'       => null,
		'git-path'        => null,
		'github-repo-url' => null,
	);

	/**
	 * Git repo tests options.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git_repo_tests = array(
		'commit-test-repo-get-file-at-commit-1' => null,
		'commit-test-repo-get-file-at-commit-2' => null,
	);

	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Setup function. Require files, check out git repository, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

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
			$this->options_git_repo_tests,
		);

		$this->options['commit'] =
			$this->options['commit-test-repo-get-file-at-commit-2'];

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);
	}

	/**
	 * Tear down function, clean up variables, etc.
	 */
	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options );
		unset( $this->options_git );
		unset( $this->options_git_repo_tests );
	}

	/**
	 * Test fetching files as they were at a particular commit.
	 * Ensure different commit-IDs return correct content.
	 *
	 * @covers ::vipgoci_gitrepo_get_file_at_commit
	 */
	public function testGetFileWithData() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					vipgoci_unittests_output_get()
			);
		}

		/*
		 * Get file1.php from two different
		 * commits, check SHA1 sum.
		 */

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-1'],
			'file1.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'04f338f924cabbe47994043660304e58a5a3f78f',
			sha1( $file_content )
		);

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-2'],
			'file1.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'c4587f2e42de3ab1ecdf51c993a3135bb1314b68',
			sha1( $file_content )
		);

		/*
		 * Same with file2.php.
		 */

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-1'],
			'file2.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'04f338f924cabbe47994043660304e58a5a3f78f',
			sha1( $file_content )
		);

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-2'],
			'file2.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'f8c824b9bc01a5655e77a10a3f2e5fa704a58f9c',
			sha1( $file_content )
		);
	}

	/**
	 * Test asking for an empty file.
	 *
	 * @covers ::vipgoci_gitrepo_get_file_at_commit
	 */
	public function testGetEmptyFile() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					vipgoci_unittests_output_get()
			);
		}

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-2'],
			'file4.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			'',
			$file_content
		);
	}

	/**
	 * Test if asking for non-existent file returns correct value.
	 *
	 * @covers ::vipgoci_gitrepo_get_file_at_commit
	 */
	public function testGetNonExistentFile() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					vipgoci_unittests_output_get()
			);
		}

		vipgoci_unittests_output_suppress();

		$file_content = vipgoci_gitrepo_get_file_at_commit(
			$this->options['commit-test-repo-get-file-at-commit-2'],
			'file-does-not-exist.php',
			$this->options['local-git-repo'],
			$this->options['commit']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNull(
			$file_content
		);
	}
}
