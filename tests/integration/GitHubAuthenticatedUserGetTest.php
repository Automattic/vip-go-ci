<?php
/**
 * Test function vipgoci_github_authenticated_user_get().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\tests;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class GitHubAuthenticatedUserGetTest extends TestCase {
	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		$this->options = array();

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];
	}

	protected function tearDown(): void {
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_authenticated_user_get
	 */
	public function testGitHubAuthenticatedUserGet1 () {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$gh_result = vipgoci_github_authenticated_user_get(
			$this->options['github-token']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertTrue(
			isset(
				$gh_result->login
			)
			&&
			( strlen(
				$gh_result->login
			) > 0 )
		);
	}
}

