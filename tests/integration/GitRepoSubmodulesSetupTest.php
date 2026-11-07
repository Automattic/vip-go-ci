<?php
/**
 * Test function vipgoci_gitrepo_submodules_setup().
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
final class GitRepoSubmodulesSetupTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	var $options_git = array(
		'git-path'			=> null,
		'github-repo-url'		=> null,
	);

	var $options_git_repo_tests = array(
		'commit-test-submodule-list-get-1'	=> null,
	);


	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		if ( 'true' !== getenv( 'VIPGOCI_TESTING_LIVE_API' ) ) {
			// Submodule objects are already present in the full project checkout.
			// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Configure only this isolated Git fixture process.
			$config_count = (int) ( getenv( 'GIT_CONFIG_COUNT' ) ?: 0 );
			putenv( 'GIT_CONFIG_KEY_' . $config_count . '=url.' . dirname( __DIR__, 2 ) . '.insteadOf' );
			putenv( 'GIT_CONFIG_VALUE_' . $config_count . '=https://github.com/automattic/vip-go-ci.git' );
			putenv( 'GIT_CONFIG_KEY_' . ( $config_count + 1 ) . '=protocol.file.allow' );
			putenv( 'GIT_CONFIG_VALUE_' . ( $config_count + 1 ) . '=always' );
			putenv( 'GIT_CONFIG_COUNT=' . ( $config_count + 2 ) );
			// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
		}


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
	}

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
	 * @covers ::vipgoci_gitrepo_submodules_setup
	 */
	public function testSubmoduleSetup1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['commit-test-submodule-list-get-1'];

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

		vipgoci_unittests_output_unsuppress();

		/*
		 * Init and checkout submodules
		 */
		$ret = vipgoci_gitrepo_submodules_setup(
			$this->options['local-git-repo']
		);

		$this->assertTrue(
			( false !== strpos(
				$ret,
				'Submodule path'
			) )
			&&
			( false !== strpos(
				$ret,
				'checked out'
			) )
			&&
			( false !== strpos(
				$ret,
				'Cloning into'
			) )	
		);
	}
}
