<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class OptionsReadRepoSkipFilesTest extends TestCase {
	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-owner'		=> null,
		'repo-name'		=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-options-read-repo-skip-files-1'	=> null,
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

		$this->options['phpcs-skip-folders-in-repo-options-file'] = false;

		$this->options['lint-skip-folders-in-repo-options-file'] = false;

		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
		);

		$this->options['token'] = null;
	}

	protected function tearDown() {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		$this->options = null;
		$this->options_git_repo_tests = null;
		$this->options_git = null;
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * Tests reading 'skip-options' from file in repository
	 */
	public function testOptionsReadRepoFilePhpcsTest1() {
		$this->options['phpcs-skip-folders-in-repo-options-file'] = false;
	
		$this->options['phpcs-skip-folders'] = array(
			'qqq-75x-n/plugins'
		);

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertEquals(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 */

	public function testOptionsReadRepoFilePhpcsTest2() {
		$this->options['phpcs-skip-folders-in-repo-options-file'] = true;

		$this->options['phpcs-skip-folders'] = array(
			'qqq-75x-n/plugins'
		);

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertEquals(
			array(
				'qqq-75x-n/plugins',
				'bar-34/751-508x',
				'foo-79/m-250',
				'foo-82/l-folder-450',
				'foo-m/folder-b',
			),
			$this->options['phpcs-skip-folders']
		);		
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 */

	public function testOptionsReadRepoFileLintTest1() {
		$this->options['lint-skip-folders-in-repo-options-file'] = false;


		$this->options['lint-skip-folders'] = array(
			'qqq-94x-L/plugins'
		);

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertEquals(
			array(
				'qqq-94x-L/plugins',
			),
			$this->options['lint-skip-folders']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_skip_files
	 */

	public function testOptionsReadRepoFileLintTest2() {
		$this->options['lint-skip-folders-in-repo-options-file'] = true;

		$this->options['lint-skip-folders'] = array(
			'qqq-94x-L/plugins'
		);

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		$this->assertEquals(
			array(
				'qqq-94x-L/plugins',
				'foo-bar-1/750-500x',
				'bar-foo-3/m-900',
				'foo-foo-9/t-folder-750',
				'foo-test/folder7',
			),
			$this->options['lint-skip-folders']
		);
	}
}

