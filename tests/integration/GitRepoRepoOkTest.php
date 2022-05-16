<?php
/**
 * Test vipgoci_gitrepo_ok() function.
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
final class GitRepoRepoOkTest extends TestCase {
	/**
	 * Git options.
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
	 * Git repo test options.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git_repo_tests = array(
		'commit-test-repo-ok-1' => null,
	);

	/**
	 * Setup function. Require files. Init configuration.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		require_once __DIR__ . '/../unit/helper/IndicateTestId.php';

		vipgoci_unittests_indicate_test_id( 'GitRepoRepoOkTest' );
 
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

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);
	}

	/**
	 * Teardown function. Remove local git repository, clear variables.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		vipgoci_unittests_remove_indication_for_test_id( 'GitRepoRepoOkTest' );

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
	 * Test normal usage of the vipgoci_gitrepo_ok() function,
	 * should succeed.
	 *
	 * @covers ::vipgoci_gitrepo_ok
	 */
	public function testRepoOkSuccess() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-ok-1'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);
		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					vipgoci_unittests_output_get()
			);
		}

		ob_start();

		vipgoci_gitrepo_ok(
			$this->options['commit-test-repo-ok-1'],
			$this->options['local-git-repo']
		);

                $printed_data = ob_get_contents();

                ob_end_clean();

		$this->assertEmpty( $printed_data );
	}

	/**
	 * Test invalid usage of the vipgoci_gitrepo_ok() function,
	 * should not succeed.
	 *
	 * @covers ::vipgoci_gitrepo_ok
	 */
	public function testRepoOkFailure() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-repo-ok-1'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);
		vipgoci_unittests_output_unsuppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					vipgoci_unittests_output_get()
			);
		}

		ob_start();

		vipgoci_gitrepo_ok(
			'1b88f2b8a70ff5125947badc98995405f15dd468', // Invalid commit ID.
			$this->options['local-git-repo']
		);

                $printed_data = ob_get_contents();

                ob_end_clean();

		$this->assertTrue(
			str_contains(
				$printed_data,
				'Can not use local Git repository, seems not to be in sync with current commit or does not exist'
			)
		);
	}
}
