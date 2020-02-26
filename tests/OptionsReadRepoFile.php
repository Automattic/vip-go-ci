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
		'commit-test-options-read-repo-file-with-file-2'=> null,
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

		$this->options[ 'repo-options' ] = true;

		$this->options['token'] = null;

		$this->options['repo-options-allowed'] = array(
			'post-generic-pr-support-comments',
			'phpcs-severity',
		);
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
	 *
	 * Tests integer values read from repository config files
	 */
	public function testOptionsReadRepoFileIntTest1() {
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
			-100, // Should remain unchanged, no option file exists.
			$this->options['phpcs-severity']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests integer values read from repository config files
	 */
	public function testOptionsReadRepoFileIntTest2() {
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


	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests integer values read from repository config files
	 */
	public function testOptionsReadRepoFileIntTest3() {
		$this->options['repo-options'] = false;

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
			-100, // Should remain unchanged, feature is turned off.
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
	 *
	 * Tests integer values read from repository config files
	 */
	public function testOptionsReadRepoFileIntTest4() {
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
					'type'		=> 'integerrr', // invalid type here
					'valid_values'	=> array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			-100, // Should remain unchanged, as checks failed
			$this->options['phpcs-severity']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests integer values read from repository config files
	 */
	public function testOptionsReadRepoFileIntTest5() {
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
					'valid_values'	=> array( 500 ), // the value in options-file out of range
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			-100, // Should remain unchanged
			$this->options['phpcs-severity']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests integer values read from repository config files
	 */
	public function testOptionsReadRepoFileIntTest6() {
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
					// Skipping valid values
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			-100, // Should remain unchanged
			$this->options['phpcs-severity']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests for boolean options in repository config files
	 */
	public function testOptionsReadRepoFileBoolTest1() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-2'];

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


		$this->options['post-generic-pr-support-comments'] = false;

		vipgoci_options_read_repo_file(
			$this->options,
			'.vipgoci_options',
			array(
				'post-generic-pr-support-comments' => array(
					'type'		=> 'boolean',
					// skipping valid values
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			false, // Should remain unchanged
			$this->options['post-generic-pr-support-comments']
		);
	}

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests for boolean options in repository config files
	 */
	public function testOptionsReadRepoFileBoolTest2() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-2'];

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

		// Turning support off
		$this->options['repo-options'] = false;

		$this->options['post-generic-pr-support-comments'] = false;

		vipgoci_options_read_repo_file(
			$this->options,
			'.vipgoci_options',
			array(
				'post-generic-pr-support-comments' => array(
					'type'		=> 'boolean',
					'valid_values'	=> array( false, true ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			false, // Should not have changed
			$this->options['post-generic-pr-support-comments']
		);
	}


	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests for boolean options in repository config files
	 */
	public function testOptionsReadRepoFileBoolTest3() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-2'];

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


		$this->options['post-generic-pr-support-comments'] = false;

		vipgoci_options_read_repo_file(
			$this->options,
			'.vipgoci_options',
			array(
				'post-generic-pr-support-comments' => array(
					'type'		=> 'boolean',
					'valid_values'	=> array( false, true ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			true, // Should have changed, repo setting is true
			$this->options['post-generic-pr-support-comments']
		);
	}

	
	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests against the --repo-options-allowed option.
	 */
	public function testOptionsReadRepoFileOptionAllowedTest1() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-2'];

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

		// Limit options configurable to only this one.
		$this->options['repo-options-allowed'] = array(
			'post-generic-pr-support-comments',
		);

		$this->options['post-generic-pr-support-comments'] = false;
		$this->options['phpcs-severity'] = -100;

		vipgoci_options_read_repo_file(
			$this->options,
			'.vipgoci_options',
			array(
				'post-generic-pr-support-comments' => array(
					'type'		=> 'boolean',
					'valid_values'	=> array( false, true ),
				),

				// phpcs-severity set up, but not allowed to be configured
				'phpcs-severity' => array(
					'type'		=> 'integer',
					'valid_values'	=> array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				)
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			true, // Should have changed, repo setting is true
			$this->options['post-generic-pr-support-comments']
		);

		$this->assertEquals(
			-100, // Should not have changed, repo setting is set, but cannot be set
			$this->options['phpcs-severity']
		);
	}
}

