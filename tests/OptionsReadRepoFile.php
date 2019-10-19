<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class OptionsReadRepoFile extends TestCase {
	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
	);

       var $options_patch_changed_lines = array(
	       'repo-owner'	=> null,
	       'repo-name'	=> null,
       );

	var $options_options = array(
		'commit-test-options-read-repo-file-no-file'	=> null,
		'commit-test-options-read-repo-file-with-file'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
		       'patch-changed-lines',
		       $this->options_patch_changed_lines
		);

		vipgoci_unittests_get_config_values(
			'options',
			$this->options_options
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_patch_changed_lines,
			$this->options_options
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
		$this->options_options = null;
		$this->options_patch_changed_lines = null;
		$this->options_git = null;
	}

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileTest1() {
		/*
		 * File does not exist in repository,
		 * so the test should not succeed.
		 */
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-no-file'];

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


		$this->options['phpcs-severity'] = -100;

		vipgoci_options_read_repo_file(
			$this->options,
			'.vipgoci_options',
			array(
				'phpcs-severity' => array(
					'type'		=> 'integer',
					'valid_values'	=> array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			-100,
			$this->options['phpcs-severity']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileTest2() {
		/*
		 * File does exist in repository,
		 * so the test should succeed.
		 */
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file'];

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


		$this->options['phpcs-severity'] = -100;

		vipgoci_options_read_repo_file(
			$this->options,
			'.vipgoci_options',
			array(
				'phpcs-severity' => array(
					'type'		=> 'integer',
					'valid_values'	=> array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			1, // Options file should change this to 1
			$this->options['phpcs-severity']
		);
	}

	/*
	 * The following are tests to make sure internal
	 * checks in the vipgoci_options_read_repo_file() function
	 * work correctly.
	 */

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileTest3() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file'];

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


		$this->options['phpcs-severity'] = 1;

		vipgoci_options_read_repo_file(
			$this->options,
			'.vipgoci_options',
			array(
				'phpcs-severity' => array(
					'type'		=> 'integerrr', // invalid type here
					'valid_values'	=> array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			1, // Should remain 1, as checks failed
			$this->options['phpcs-severity']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileTest4() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file'];

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


		$this->options['phpcs-severity'] = 1;

		vipgoci_options_read_repo_file(
			$this->options,
			'.vipgoci_options',
			array(
				'phpcs-severity' => array(
					'type'		=> 'integer',
					'valid_values'	=> array( 500 ), // the value in options-file out of range
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			1, // Should remain 1
			$this->options['phpcs-severity']
		);
	}
}

