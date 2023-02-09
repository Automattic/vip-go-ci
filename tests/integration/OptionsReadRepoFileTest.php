<?php
/**
 * Test vipgoci_options_read_repo_file().
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
final class OptionsReadRepoFileTest extends TestCase {
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
	 * Commit related options.
	 *
	 * @var $options_options
	 */
	private array $options_options = array(
		'commit-test-options-read-repo-file-no-file'     => null,
		'commit-test-options-read-repo-file-with-file'   => null,
		'commit-test-options-read-repo-file-with-file-2' => null,
		'commit-test-options-read-repo-file-with-file-3' => null,
		'commit-test-options-read-repo-file-with-file-5' => null,
	);

	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array(
		'git-path'        => null,
		'github-repo-url' => null,
		'repo-name'       => null,
		'repo-owner'      => null,
	);

	/**
	 * Set up function. Requires files, sets variables.
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
			'options',
			$this->options_options
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_options
		);

		$this->options['repo-options'] = true;

		if ( empty( $this->options['token'] ) ) {
			$this->options['token'] = '';
		}

		$this->options['repo-options-allowed'] = array(
			'post-generic-pr-support-comments',
			'phpcs-severity',
			'phpcs-sniffs-exclude',
		);
	}

	/**
	 * Tear down function, remove local git repository, clear variables.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if (
			( ! empty( $this->options['local-git-repo'] ) ) &&
			( false !== $this->options['local-git-repo'] )
		) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options );
		unset( $this->options_git );
		unset( $this->options_options );
	}

	/**
	 * Tests integer values read from repository config files
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileIntTest1() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-no-file'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['phpcs-severity'] = -100;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'phpcs-severity' => array(
					'type'         => 'integer',
					'valid_values' => array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			-100, // Should remain unchanged, no option file exists.
			$this->options['phpcs-severity']
		);

		$this->assertSame(
			array(),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests integer values read from repository config files
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileIntTest2() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['phpcs-severity'] = -100;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'phpcs-severity' => array(
					'type'         => 'integer',
					'valid_values' => array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1, // Options file should change this to 1.
			$this->options['phpcs-severity']
		);

		$this->assertSame(
			array( 'phpcs-severity' => 1 ),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests integer values read from repository config files.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileIntTest3() :void {
		$this->options['repo-options'] = false;

		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['phpcs-severity'] = -100;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'phpcs-severity' => array(
					'type'         => 'integer',
					'valid_values' => array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			-100, // Should remain unchanged, feature is turned off.
			$this->options['phpcs-severity']
		);

		$this->assertSame(
			array(),
			$this->options['repo-options-set']
		);
	}

	/*
	 * The following are tests to make sure internal
	 * checks in the vipgoci_options_read_repo_file() function
	 * work correctly.
	 */

	/**
	 * Tests integer values read from repository config files.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileIntTest4() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['phpcs-severity'] = -100;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'phpcs-severity' => array(
					'type'         => 'integerrr', // Invalid type here.
					'valid_values' => array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			-100, // Should remain unchanged, as checks failed.
			$this->options['phpcs-severity']
		);

		$this->assertSame(
			array(),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests integer values read from repository config files
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileIntTest5() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['phpcs-severity'] = -100;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'phpcs-severity' => array(
					'type'         => 'integer',
					'valid_values' => array( 500 ), // The value in options-file out of range.
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			-100, // Should remain unchanged.
			$this->options['phpcs-severity']
		);

		$this->assertSame(
			array(),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests integer values read from repository config files.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileIntTest6() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['phpcs-severity'] = -100;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'phpcs-severity' => array(
					'type' => 'integer',
					// Skipping valid values.
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			-100, // Should remain unchanged.
			$this->options['phpcs-severity']
		);

		$this->assertSame(
			array(),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests for boolean options in repository config files
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileBoolTest1() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-2'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['post-generic-pr-support-comments'] = false;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'post-generic-pr-support-comments' => array(
					'type' => 'boolean',
					// Skipping valid values.
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			false, // Should remain unchanged.
			$this->options['post-generic-pr-support-comments']
		);

		$this->assertSame(
			array(),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests for boolean options in repository config files.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileBoolTest2() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-2'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		// Turning support off.
		$this->options['repo-options'] = false;

		$this->options['post-generic-pr-support-comments'] = false;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'post-generic-pr-support-comments' => array(
					'type'         => 'boolean',
					'valid_values' => array( false, true ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			false, // Should not have changed.
			$this->options['post-generic-pr-support-comments']
		);

		$this->assertSame(
			array(),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests for boolean options in repository config files
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileBoolTest3() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-2'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['post-generic-pr-support-comments'] = false;

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'post-generic-pr-support-comments' => array(
					'type'         => 'boolean',
					'valid_values' => array( false, true ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			true, // Should have changed, repo setting is true.
			$this->options['post-generic-pr-support-comments']
		);

		$this->assertSame(
			array( 'post-generic-pr-support-comments' => true ),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests for array options in repository config files
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileArrayTest1() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-5'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['phpcs-sniffs-exclude'] = array(
			'MySniff1',
			'MySniff2',
		);

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'phpcs-sniffs-exclude' => array(
					'type'   => 'array',
					'append' => true, // Append what was read.
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'MySniff1',
				'MySniff2',
				'OtherSniff1',
				'OtherSniff2',
			),
			$this->options['phpcs-sniffs-exclude']
		);

		$this->assertSame(
			array(
				'phpcs-sniffs-exclude' => array(
					'OtherSniff1',
					'OtherSniff2',
				),
			),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests for array options in repository config files
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileArrayTest2() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-5'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		$this->options['phpcs-sniffs-exclude'] = array(
			'MySniff1',
			'MySniff2',
		);

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'phpcs-sniffs-exclude' => array(
					'type'   => 'array',
					'append' => false, // Do not append what was read.
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'OtherSniff1',
				'OtherSniff2',
			),
			$this->options['phpcs-sniffs-exclude']
		);

		$this->assertSame(
			array(
				'phpcs-sniffs-exclude' => array(
					'OtherSniff1',
					'OtherSniff2',
				),
			),
			$this->options['repo-options-set']
		);
	}

	/**
	 * Tests against the --repo-options-allowed option.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 */
	public function testOptionsReadRepoFileOptionAllowedTest1() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-3'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();
		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository'
			);
		}

		// Limit options configurable to only this one.
		$this->options['repo-options-allowed'] = array(
			'post-generic-pr-support-comments',
		);

		$this->options['post-generic-pr-support-comments'] = false;

		$this->options['phpcs-severity']       = -100;
		$this->options['phpcs-sniffs-exclude'] = array( 'MySniff.MySniffName' );

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_file(
			$this->options,
			VIPGOCI_OPTIONS_FILE_NAME,
			array(
				'post-generic-pr-support-comments' => array(
					'type'         => 'boolean',
					'valid_values' => array( false, true ),
				),

				// phpcs-severity set up, but not allowed to be configured.
				'phpcs-severity'                   => array(
					'type'         => 'integer',
					'valid_values' => array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
				),
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			true, // Should have changed, repo setting is true.
			$this->options['post-generic-pr-support-comments']
		);

		$this->assertSame(
			-100, // Should not have changed, repo setting is set, but cannot be set.
			$this->options['phpcs-severity']
		);

		$this->assertSame(
			array( 'MySniff.MySniffName' ), // Should not have changed, is not configured.
			$this->options['phpcs-sniffs-exclude']
		);

		$this->assertSame(
			array( 'post-generic-pr-support-comments' => true ),
			$this->options['repo-options-set']
		);
	}
}

