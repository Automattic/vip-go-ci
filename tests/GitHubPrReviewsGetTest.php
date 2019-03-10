<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrReviewsGetTest extends TestCase {
	var $options_git_repo_tests = array(
		'pr-test-github-pr-reviews-get-1'	=> null
	);

	var $options_git = array(
		'repo-owner'				=> null,
		'repo-name'				=> null,
		'github-token'				=> null,
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
	}

	protected function tearDown() {
		$this->options_git_repo_tests = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_pr_reviews_get
	 */
	public function testGitHubPrReviewsGet1() {
		ob_start();

		$reviews_actual = vipgoci_github_pr_reviews_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-github-pr-reviews-get-1'],
			$this->options['github-token'],
			array()
		);

		ob_end_clean();

		$this->assertEquals(
			1,
			count( $reviews_actual )
		);

		$this->assertEquals(
			212601504,
			$reviews_actual[0]->id
		);


		$this->assertEquals(
			'Test review',
			$reviews_actual[0]->body
		);

		$this->assertEquals(
			'COMMENTED',
			$reviews_actual[0]->state
		);
	}
}
