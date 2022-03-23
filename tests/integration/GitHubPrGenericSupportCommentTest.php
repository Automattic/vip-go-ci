<?php
/**
 * Test vipgoci_report_submit_pr_generic_support_comment() function.
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
final class GitHubPrGenericSupportCommentTest extends TestCase {
	/**
	 * Git options.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'repo-owner' => null,
		'repo-name'  => null,
	);

	/**
	 * Git repo tests options.
	 *
	 * @var $options_git_repo_tests
	 */
	private array $options_git_repo_tests = array(
		'test-github-pr-generic-support-comment-1' => null,
	);

	/**
	 * Setup function. Require file, set up variables, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

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

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		$this->options['token'] = $this->options['github-token'];

		$this->options['post-generic-pr-support-comments'] = true;

		$this->options['post-generic-pr-support-comments-on-drafts'] =
			array(
				2 => false,
			);

		$this->options['post-generic-pr-support-comments-string'] =
			array(
				2 => 'This is a generic support message from `vip-go-ci`. We hope this is useful.',
			);

		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		$this->options['post-generic-pr-support-comments-repo-meta-match'] =
			array();

		$this->options = array_merge(
			$this->options_git,
			$this->options_git_repo_tests,
			$this->options
		);

		$this->options['commit'] =
			$this->options['test-github-pr-generic-support-comment-1'];

		/*
		 * Try to fetch information about current user,
		 * but only if we have a token. This info will
		 * be re-used.
		 */
		if (
			( empty( $this->current_user_info ) ) &&
			( ! empty( $this->options['github-token'] ) )
		) {
			$this->current_user_info = vipgoci_github_authenticated_user_get(
				$this->options['github-token']
			);
		}

		/*
		 * Don't attempt cleanup if not configured.
		 */

		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 !== $options_test ) {
			$this->clearOldSupportComments();
		}
	}

	/**
	 * Tear down function. Remove variables, etc.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 !== $options_test ) {
			$this->clearOldSupportComments();
		}

		unset( $this->options );
		unset( $this->options_git );
		unset( $this->options_git_repo_tests );
	}

	/**
	 * Get pull requests implicated.
	 *
	 * @return array Pull requests.
	 */
	protected function getPrsImplicated() :array {
		vipgoci_unittests_output_suppress();

		$ret = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			array()
		);

		vipgoci_unittests_output_unsuppress();

		return $ret;
	}

	/**
	 * Get generic comments made to a Pull-Request
	 * from GitHub, uncached.
	 *
	 * @param int $pr_number Pull request number.
	 *
	 * @return array Comments.
	 */
	protected function getPrGenericComments(
		int $pr_number
	) :array {
		$pr_comments_ret = array();

		$page     = 1;
		$per_page = 100;

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $this->options['repo-owner'] ) . '/' .
				rawurlencode( $this->options['repo-name'] ) . '/' .
				'issues/' .
				rawurlencode( (string) $pr_number ) . '/' .
				'comments' .
				'?page=' . rawurlencode( (string) $page ) . '&' .
				'per_page=' . rawurlencode( (string) $per_page );

			$pr_comments_raw = json_decode(
				vipgoci_http_api_fetch_url(
					$github_url,
					$this->options['github-token']
				)
			);

			foreach ( $pr_comments_raw as $pr_comment ) {
				$pr_comments_ret[] = $pr_comment;
			}

			$page++;

			$pr_comments_raw_cnt = count( $pr_comments_raw );
		} while ( $pr_comments_raw_cnt >= $per_page );

		return $pr_comments_ret;
	}

	/**
	 * Clear away any old support comments
	 * left behind by us. Do this by looping
	 * through any Pull-Requests implicated and
	 * check if each one has any comments, then
	 * remove them if they were made by us and
	 * are support comments.
	 *
	 * @return void
	 */
	protected function clearOldSupportComments() :void {
		$prs_implicated = $this->getPrsImplicated();

		foreach ( $prs_implicated as $pr_item ) {
			// Check if any comments already exist.
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			foreach ( $pr_comments as $pr_comment ) {
				if ( $pr_comment->user->login !== $this->current_user_info->login ) {
					continue;
				}

				// Look for a support-comment.
				foreach (
					array_values(
						$this->options['post-generic-pr-support-comments-string']
					)
					as $tmp_support_comment_string
				) {
					// Check if the comment contains the support-comment.
					if ( strpos(
						$pr_comment->body,
						$tmp_support_comment_string
					) === 0 ) {
						// Remove comment, submitted by us, is support comment.
						vipgoci_github_pr_generic_comment_delete(
							$this->options['repo-owner'],
							$this->options['repo-name'],
							$this->options['github-token'],
							$pr_comment->id
						);

						break;
					}
				}
			}
		}
	}

	/**
	 * Count number of support comments posted
	 * by the current token-holder.
	 *
	 * @param array $pr_comments Comments to inspect.
	 *
	 * @return int Number of comments posted by token-holder.
	 */
	protected function countSupportCommentsFromUs(
		array $pr_comments
	) :int {
		$valid_comments_found = 0;

		foreach ( $pr_comments as $pr_comment ) {
			if ( $pr_comment->user->login !== $this->current_user_info->login ) {
				continue;
			}

			// Check if the comment contains the support-comment.
			foreach (
				array_values(
					$this->options['post-generic-pr-support-comments-string']
				)
				as $tmp_support_comment_string
			) {
				// Check if the comment contains the support-comment.
				if ( strpos(
					$pr_comment->body,
					$tmp_support_comment_string
				) === 0 ) {
					// We have found support comment posted by us.
					$valid_comments_found++;
					break;
				}
			}
		}

		return $valid_comments_found;
	}

	/**
	 * Should not post generic support comments.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_report_submit_pr_generic_support_comment
	 */
	public function testPostingNotConfigured() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we can post against.
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		// Should not post generic support comments.
		$this->options['post-generic-pr-support-comments'] = false;

		// Get pull requests.
		$prs_implicated = $this->getPrsImplicated();

		// Check we have at least one PR.
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		/*
		 * vipgoci_report_submit_pr_generic_support_comment() will
		 * call vipgoci_github_pr_generic_comments_get_all() that
		 * caches results, causing it to give back wrong
		 * results when called again. Clear the internal cache
		 * here to circumvent this.
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Try to submit support comment.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);

			$this->assertSame(
				0,
				$this->countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
	}

	/**
	 * Should post generic support comments to all branches,
	 * but not drafts.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_report_submit_pr_generic_support_comment
	 */
	public function testPostingWorksAnyBranch() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we can post against.
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		// Get pull requests.
		$prs_implicated = $this->getPrsImplicated();

		// Check we have at least one PR.
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			if ( true === $pr_item->draft ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			} else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Try re-posting.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			if ( true === $pr_item->draft ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			} else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}
	}

	/**
	 * Should post generic support comments to specific branches.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_report_submit_pr_generic_support_comment
	 */
	public function testPostingWorksSpecificBranch() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we allow posting against.
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'master' ),
			);

		// Get pull requests.
		$prs_implicated = $this->getPrsImplicated();

		// Check we have at least one PR.
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			if ( true === $pr_item->draft ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			} else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Try re-posting.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			if ( true === $pr_item->draft ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			} else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}
	}

	/**
	 * Posting of support comment skipped, branch invalid.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_report_submit_pr_generic_support_comment
	 */
	public function testPostingSkippedInvalidBranch() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches to post against.
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'myinvalidbranch0xfff' ),
			);

		// Get pull requests.
		$prs_implicated = $this->getPrsImplicated();

		// Check we have at least one PR.
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded -- should not have, as branch is invalid.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				$this->countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Try re-posting.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed the second time.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				$this->countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}
	}

	/**
	 * Posting of support comment succeeds with draft pull request.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_report_submit_pr_generic_support_comment
	 */
	public function testPostingWorksWithDraftPRs() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we can post against.
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		// Get pull requests.
		$prs_implicated = $this->getPrsImplicated();

		// Check we have at least one PR.
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			if ( true === $pr_item->draft ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			} else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Try re-posting.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			if ( true === $pr_item->draft ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			} else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}

		/*
		 * Re-configure to post on drafts too and re-run
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Post on draft PRs.
		$this->options['post-generic-pr-support-comments-on-drafts'] = array(
			2 => true,
		);

		// Try re-posting.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did succeed.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) > 0
			);

			$this->assertSame(
				1,
				$this->countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}
	}

	/**
	 * Should skip submitting support comment when label is in place.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_report_submit_pr_generic_support_comment
	 */
	public function testPostingWorksWithLabels() :void {
		$test_label = 'my-random-label-1596640824';

		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we can post against.
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		$this->options['post-generic-pr-support-comments-skip-if-label-exists'] = array(
			2 => $test_label,
		);

		// Get pull requests.
		$prs_implicated = $this->getPrsImplicated();

		// Check we have at least one PR.
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		foreach ( $prs_implicated as $pr_item ) {
			// Make sure there are no comments.
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);

			// Add label to make sure no comment is posted.
			vipgoci_github_label_add_to_pr(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				$this->options['token'],
				$pr_item->number,
				$test_label
			);
		}

		// Try to submit support comment.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// Make sure commenting did not succeed.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);

			$this->assertSame(
				0,
				$this->countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Try re-posting.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);

			$this->assertSame(
				0,
				$this->countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * Re-configure to post on drafts too and re-run
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Post on draft PRs.
		$this->options['post-generic-pr-support-comments-on-drafts'] = array(
			2 => true,
		);

		// Try re-posting.
		vipgoci_report_submit_pr_generic_support_comment(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed.
		foreach ( $prs_implicated as $pr_item ) {
			$pr_comments = $this->getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);

			$this->assertSame(
				0,
				$this->countSupportCommentsFromUs(
					$pr_comments
				)
			);

			vipgoci_github_pr_label_remove(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				$this->options['token'],
				$pr_item->number,
				$test_label
			);
		}
	}
}
