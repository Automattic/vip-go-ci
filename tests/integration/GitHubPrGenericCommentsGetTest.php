<?php
/**
 * Test function vipgoci_github_pr_generic_comments_get().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class GitHubPrGenericCommentsGetTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	var $options_git_repo_tests = array(
		'pr-test-github-pr-reviews-get-1'	=> null
	);

	var $options_git = array(
		'repo-owner'				=> null,
		'repo-name'				=> null,
	);

	protected function setUp(): void {
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

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options['token'] =
			$this->options['github-token'];
	}

	protected function tearDown(): void {
		unset( $this->options_git_repo_tests );
		unset( $this->options_git );
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_comments_get
	 */
	public function testGitHubPrGenericCommentsGet1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$pr_comments = array();

		vipgoci_unittests_output_suppress();

		$pr_comments = vipgoci_github_pr_generic_comments_get_all(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-github-pr-reviews-get-1'],
			$this->options['github-token']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( array_keys( $pr_comments ) )
		);

		$this->assertSame(
			471306810,
			$pr_comments[0]->id
		);

		$this->assertSame(
			'Testing of generic comments.',
			$pr_comments[0]->body
		);
	}
}
