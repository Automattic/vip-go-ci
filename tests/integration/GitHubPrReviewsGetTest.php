<?php
/**
 * Test function vipgoci_github_pr_reviews_get().
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
final class GitHubPrReviewsGetTest extends TestCase {
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
	}

	protected function tearDown(): void {
		unset( $this->options_git_repo_tests );
		unset( $this->options_git );
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_github_pr_reviews_get
	 */
	public function testGitHubPrReviewsGet1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$reviews_actual = vipgoci_github_pr_reviews_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-github-pr-reviews-get-1'],
			$this->options['github-token'],
			array()
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $reviews_actual )
		);

		$this->assertSame(
			212601504,
			$reviews_actual[0]->id
		);


		$this->assertSame(
			'Test review',
			$reviews_actual[0]->body
		);

		$this->assertSame(
			'COMMENTED',
			$reviews_actual[0]->state
		);
	}
}
