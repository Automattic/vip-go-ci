<?php
/**
 * Test vipgoci_options_read_repo_skip_files() function.
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
final class OptionsReadRepoSkipFilesTest extends TestCase {
	/**
	 * Git options array.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'git-path'        => null,
		'github-repo-url' => null,
		'repo-owner'      => null,
		'repo-name'       => null,
	);

	/**
	 * Git test options array.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git_repo_tests = array(
		'commit-test-options-read-repo-skip-files-1' => null,
		'commit-test-options-read-repo-skip-files-2' => null,
	);

	/**
	 * Set up all variables, require file, etc.
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

		$this->options['phpcs-skip-folders-in-repo-options-file'] = false;

		$this->options['phpcs-skip-folders'] = array(
			'qqq-75x-n/plugins',
		);

		$this->options['lint-skip-folders-in-repo-options-file'] = false;

		$this->options['lint-skip-folders'] = array(
			'mmm-300/800',
		);

		$this->options['wpscan-api-skip-folders-in-repo-options-file'] = false;

		$this->options['wpscan-api-skip-folders'] = array(
			'ppp-400/900',
		);

		if ( empty( $this->options['token'] ) ) {
			$this->options['token'] = '';
		}
	}

	/**
	 * Tear down git repository, clean up all variables.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->tearDownLocalGitrepo();

		unset( $this->options );
		unset( $this->options_git_repo_tests );
		unset( $this->options_git );
	}

	/**
	 * Set up local git repository.
	 *
	 * @return void
	 */
	protected function setUpLocalGitRepo() :void {
		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);
	}

	/**
	 * Tear down local git repository.
	 *
	 * @return void
	 */
	protected function tearDownLocalGitrepo() :void {
		if ( ! empty( $this->options['local-git-repo'] ) ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}
	}

	/**
	 * Tests when options files are present, but not configured
	 * to read them.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFilePhpcsTest1() :void {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}

	/**
	 * Uses commit without options files for skip-folders,
	 * is configured to read one of them.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFilePhpcsTest2() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-2'];

		$this->options['phpcs-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}

	/**
	 * Commit with valid skip-folders options file, configured to read
	 * in skip-folders folders for PHPCS.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFilePhpcsTest3() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->options['phpcs-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
				'bar-34/751-508x',
				'foo-79/m-250',
				'foo-82/l-folder-450',
				'foo-m/folder-b',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}

	/**
	 * Configuration file available for PHP Lint folder skipping,
	 * but not configured to read it.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFileLintTest1() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}

	/**
	 * Uses commit without options files for PHP linting skip-folders,
	 * is configured to read it.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFileLintTest2() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-2'];

		$this->options['lint-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}

	/**
	 * Uses commit with options files for skip-folders,
	 * is configured to read it.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFileLintTest3() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->options['lint-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
				'foo-bar-1/750-500x',
				'bar-foo-3/m-900',
				'foo-foo-9/t-folder-750',
				'foo-test/folder7',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}

	/**
	 * Configuration file available for WPScan API folder skipping,
	 * but not configured to read it.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFileWPScanApiTest1() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}

	/**
	 * Uses commit without options files for WPScan API skip-folders,
	 * is configured to read it.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFileWpscanApiTest2() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-2'];

		$this->options['wpscan-api-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}

	/**
	 * Uses commit with options files for skip-folders,
	 * is configured to read it.
	 *
	 * @covers ::vipgoci_options_read_repo_skip_files
	 *
	 * @return void
	 */
	public function testOptionsReadRepoFileWpscanApiTest3() {
		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-skip-files-1'];

		$this->options['wpscan-api-skip-folders-in-repo-options-file'] = true;

		$this->setUpLocalGitRepo();

		vipgoci_unittests_output_suppress();

		vipgoci_options_read_repo_skip_files(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'qqq-75x-n/plugins',
			),
			$this->options['phpcs-skip-folders']
		);

		$this->assertSame(
			array(
				'mmm-300/800',
			),
			$this->options['lint-skip-folders']
		);

		$this->assertSame(
			array(
				'ppp-400/900',
				'test-300-120',
				'test-900-750',
				'test-abc-123',
			),
			$this->options['wpscan-api-skip-folders']
		);
	}
}
