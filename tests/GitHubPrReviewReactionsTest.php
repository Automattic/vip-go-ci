<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrReviewReactionsTest extends TestCase {
	var $options_git = array(
		'repo-owner'		=> null,
		'repo-name'		=> null,
	);

	var $options_git_repo_tests = array(
		'comment-test-pr-review-reactions-get-1'	=> null,
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

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options[ 'github-token' ];
	}

	protected function tearDown() {
		$this->options_git		= null;
		$this->options_git_repo_tests	= null;
		$this->options			= null;
	}

	/**
	 * @covers ::vipgoci_github_pr_review_reactions_get
	 *
	 * Test with no filters.
	 */
	public function testGitHubPRReviewReactionsGet1() {
		vipgoci_unittests_output_suppress();

		$reactions_arr = vipgoci_github_pr_review_reactions_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['comment-test-pr-review-reactions-get-1'],
			$this->options['token']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			2,
			$reactions_arr
		);	

		$this->assertEquals(
			67509020,
			$reactions_arr[0]->id
		);

		$this->assertEquals(
			'gudmdharalds',
			$reactions_arr[0]->user->login
		);

		$this->assertEquals(
			'+1',
			$reactions_arr[0]->content
		);

		$this->assertEquals(
			67513318,
			$reactions_arr[1]->id
		);

		$this->assertEquals(
			'gudmdharalds',
			$reactions_arr[1]->user->login
		);

		$this->assertEquals(
			'rocket',
			$reactions_arr[1]->content
		);
	}

	/**
	 * @covers ::vipgoci_github_pr_review_reactions_get
	 *
	 * Test with filter that should give two results.
	 */
	public function testGitHubPRReviewReactionsGet2() {
		vipgoci_unittests_output_suppress();

		$reactions_arr = vipgoci_github_pr_review_reactions_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['comment-test-pr-review-reactions-get-1'],
			$this->options['token'],
			array(
				'login'		=> 'gudmdharalds',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			2,
			$reactions_arr
		);	

		$this->assertEquals(
			67509020,
			$reactions_arr[0]->id
		);

		$this->assertEquals(
			'gudmdharalds',
			$reactions_arr[0]->user->login
		);

		$this->assertEquals(
			'+1',
			$reactions_arr[0]->content
		);
	
		$this->assertEquals(
			67513318,
			$reactions_arr[1]->id
		);

		$this->assertEquals(
			'gudmdharalds',
			$reactions_arr[1]->user->login
		);

		$this->assertEquals(
			'rocket',
			$reactions_arr[1]->content
		);
	}

	/**
	 * @covers ::vipgoci_github_pr_review_reactions_get
	 *
	 * Test with filter that should give no result.
	 */
	public function testGitHubPRReviewReactionsGet3() {
		vipgoci_unittests_output_suppress();

		$reactions_arr = vipgoci_github_pr_review_reactions_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['comment-test-pr-review-reactions-get-1'],
			$this->options['token'],
			array(
				'login'		=> 'gudmdharalds-notexisting',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			0,
			$reactions_arr
		);	
	}

	/**
	 * @covers ::vipgoci_github_pr_review_reactions_get
	 *
	 * Test with filter that should give one result.
	 */
	public function testGitHubPRReviewReactionsGet4() {
		vipgoci_unittests_output_suppress();

		$reactions_arr = vipgoci_github_pr_review_reactions_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['comment-test-pr-review-reactions-get-1'],
			$this->options['token'],
			array(
				'content'		=> '+1',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			1,
			$reactions_arr
		);	

		$this->assertEquals(
			67509020,
			$reactions_arr[0]->id
		);

		$this->assertEquals(
			'gudmdharalds',
			$reactions_arr[0]->user->login
		);

		$this->assertEquals(
			'+1',
			$reactions_arr[0]->content
		);
	}

	/**
	 * @covers ::vipgoci_github_pr_review_reactions_get
	 *
 	 * Test with filter that should give no results.
	 */
	public function testGitHubPRReviewReactionsGet5() {
		vipgoci_unittests_output_suppress();

		$reactions_arr = vipgoci_github_pr_review_reactions_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['comment-test-pr-review-reactions-get-1'],
			$this->options['token'],
			array(
				'content'		=> '-1',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			0,
			$reactions_arr
		);	
	}

	/**
	 * @covers ::vipgoci_github_pr_review_reactions_get
	 *
	 * Test with filters that should give one result.
	 */
	public function testGitHubPRReviewReactionsGet6() {
		vipgoci_unittests_output_suppress();

		$reactions_arr = vipgoci_github_pr_review_reactions_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['comment-test-pr-review-reactions-get-1'],
			$this->options['token'],
			array(
				'login'		=> 'gudmdharalds',
				'content'	=> '+1',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			1,
			$reactions_arr
		);	

		$this->assertEquals(
			67509020,
			$reactions_arr[0]->id
		);

		$this->assertEquals(
			'gudmdharalds',
			$reactions_arr[0]->user->login
		);

		$this->assertEquals(
			'+1',
			$reactions_arr[0]->content
		);
	}

	/**
	 * @covers ::vipgoci_github_pr_review_reactions_get
	 *
	 * Test with filters that should give no results.
	 */
	public function testGitHubPRReviewReactionsGet7() {
		vipgoci_unittests_output_suppress();

		$reactions_arr = vipgoci_github_pr_review_reactions_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['comment-test-pr-review-reactions-get-1'],
			$this->options['token'],
			array(
				'login'		=> 'gudmdharalds',
				'content'	=> '-1',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertCount(
			0,
			$reactions_arr
		);	
	}
}
