<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrGenericSupportCommentTest extends TestCase {
	var $options_git = array(
		'repo-owner'	=> null,
		'repo-name'	=> null,
	);

	var $options_git_repo_tests = array(
		'test-github-pr-generic-support-comment-1'	=> null,
	);

	protected function setUp() {
		/*
		 * Many of the functions called
		 * make use of caching, clear the cache
		 * so the testing will not rely on
		 * old data by accident.
		 */
		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->options_git_repo_tests
		);

		$this->options = array();

		$this->options['token'] =
		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		// We set this variable, but it has no functional implications
		$this->options['post-generic-pr-support-comments'] = true;

		$this->options['post-generic-pr-support-comments-string'] =
			'This is a generic support message from `vip-go-ci`. We hope this is useful.';

		$this->options['post-generic-pr-support-comments-branches'] =
			array();

		$this->options = array_merge(
			$this->options_git,
			$this->options_git_repo_tests,
			$this->options,
		);

		$this->options['commit'] =
			$this->options['test-github-pr-generic-support-comment-1'];

		if ( empty( $this->current_user_info ) ) {
			$this->current_user_info = vipgoci_github_authenticated_user_get(
				$this->options['github-token']
			);
		}

		$this->_clearOldSupportComments();
	}

	protected function tearDown() {
		$this->_clearOldSupportComments();

		$this->options = null;
		$this->options_git = null;
		$this->options_git_repo_tests = null;
	}

	/*
	 * Get Pull-Requests implicated.
	 */
	protected function _getPrsImplicated() {
		return vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			array()
		);
	}

	/*
	 * Get generic comments made to a Pull-Request
	 * from GitHub, uncached.
	 */
	protected function _getPrGenericComments(
		$pr_number
	) {
		$pr_comments_ret = array();

		$page = 1;
		$per_page = 100;

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $this->options['repo-owner'] ) . '/' .
				rawurlencode( $this->options['repo-name'] ) . '/' .
				'issues/' .
				rawurlencode( $pr_number ) . '/' .
				'comments' .
				'?page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );


	                $pr_comments_raw = json_decode(
	                        vipgoci_github_fetch_url(
        	                        $github_url,
                	                $this->options['github-token']
                        	)
	                );

	                foreach ( $pr_comments_raw as $pr_comment ) {
	                        $pr_comments_ret[] = $pr_comment;
        	        }

	                $page++;
		} while ( count( $pr_comments_raw ) >= $per_page );

		return $pr_comments_ret;
	}

	/*
	 * Clear away any old support comments
	 * left behind by us. Do this by looping
	 * through any Pull-Requests implicated and
	 * check if each one has any comments, then
	 * remove them if they were made by us and
	 * are support comments.
	 */
	protected function _clearOldSupportComments() {
		$prs_implicated = $this->_getPrsImplicated();

		foreach( $prs_implicated as $pr_item ) {
			// Check if any comments already exist
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			foreach ( $pr_comments as $pr_comment ) {
				if ( $pr_comment->user->login !== $this->current_user_info->login ) {
					continue;
				}

				// Check if the comment contains the support-comment
				if ( strpos(
					$pr_comment->body,
					$this->options['post-generic-pr-support-comments-string']
				) !== 0 ) {
					continue;
				}

				// Remove comment, submitted by us, is support comment.
				vipgoci_github_pr_generic_comment_delete(
					$this->options['repo-owner'],
					$this->options['repo-name'],
					$this->options['github-token'],
					$pr_comment->id
				);
			}
		}
	}

	/*
	 * Count number of support comments posted
	 * by the current token-holder.
	 */
	protected function _countSupportCommentsFromUs(
		$pr_comments
	) {	
		$valid_comments_found = 0;

		foreach( $pr_comments as $pr_comment ) {
			if ( $pr_comment->user->login !== $this->current_user_info->login ) {
				continue;
			}

			// Check if the comment contains the support-comment
			if ( strpos(
				$pr_comment->body,
				$this->options['post-generic-pr-support-comments-string']
			) !== 0 ) {
				continue;
			}

			// We have found support comment posted by us
			$valid_comments_found++;
		}

		return $valid_comments_found;
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment
	 */
	public function testPostingWorksAnyBranch() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we can post against
		$this->options['post-generic-pr-support-comments-branches'] =
			array( 'any' );

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) > 0
			);

			$this->assertEquals(
				1,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * vipgoci_github_pr_generic_support_comment() will
		 * call vipgoci_github_pr_generic_comments_get() that
		 * caches results, causing it to give back wrong
		 * results when called again. Clear the internal cache
		 * here to circumvent this.
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
		
		// Try re-posting
		vipgoci_github_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) > 0
			);

			$this->assertEquals(
				1,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment
	 */
	public function testPostingWorksSpecificBranch() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we allow posting against
		$this->options['post-generic-pr-support-comments-branches'] =
			array( 'master' );

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) > 0
			);

			$this->assertEquals(
				1,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * vipgoci_github_pr_generic_support_comment() will
		 * call vipgoci_github_pr_generic_comments_get() that
		 * caches results, causing it to give back wrong
		 * results when called again. Clear the internal cache
		 * here to circumvent this.
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
		
		// Try re-posting
		vipgoci_github_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) > 0
			);

			$this->assertEquals(
				1,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment
	 */
	public function testPostingSkippedInvalidBranch() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches to post against
		$this->options['post-generic-pr-support-comments-branches'] =
			array( 'myinvalidbranch0xfff' );

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded -- should not have, as branch is invalid
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertEquals(
				0,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * vipgoci_github_pr_generic_support_comment() will
		 * call vipgoci_github_pr_generic_comments_get() that
		 * caches results, causing it to give back wrong
		 * results when called again. Clear the internal cache
		 * here to circumvent this.
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
		
		// Try re-posting
		vipgoci_github_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed the second time
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertEquals(
				0,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}
	}
}
