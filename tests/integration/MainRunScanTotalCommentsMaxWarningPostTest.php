<?php
/**
 * Test vipgoci_run_scan_total_comments_max_warning_post() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @package Automattic/vip-go-ci
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunScanTotalCommentsMaxWarningPostTest extends TestCase {
	/**
	 * Git options array.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'repo-owner' => null,
		'repo-name'  => null,
	);

	/**
	 * Commit options array.
	 *
	 * @var $options_commit
	 */
	private array $options_commit = array(
		'commit-test-run-scan-total-comments-max-warning-post' => null,
		'pr-test-run-scan-total-comments-max-warning-post' => null,
	);

	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array(
		'git-path'        => null,
		'github-repo-url' => null,
		'repo-name'       => null,
		'repo-owner'      => null,
	);

	/**
	 * Set up all variables, etc.
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
			$this->options_commit
		);

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		$this->options = array_merge(
			$this->options,
			$this->options_git,
			$this->options_commit,
		);

		$this->options['commit'] = $this->options['commit-test-run-scan-total-comments-max-warning-post'];

		$this->options['pr_number'] = (int) $this->options['pr-test-run-scan-total-comments-max-warning-post'];

		if ( ! empty( $this->options['github-token'] ) ) {
			$this->options['token'] = $this->options['github-token'];

			vipgoci_unittests_output_suppress();

			$this->current_user_info = vipgoci_github_authenticated_user_get(
				$this->options['token']
			);

			vipgoci_unittests_output_unsuppress();

			$this->clearOldComments();
		}
	}

	/**
	 * Clean up variables, comments, etc.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ( ! empty( $this->options['token'] ) ) {
			$this->clearOldComments();
		}

		unset( $this->options );
		unset( $this->options_git );
		unset( $this->options_commit );
		unset( $this->current_user_info );
	}

	/**
	 * Fetch generic comments made to pull request.
	 *
	 * @return array Array with comments.
	 */
	private function fetchPrGenericComments(): array {
		// Clear internal cache so comments are fetched each time.
		vipgoci_cache( VIPGOCI_CACHE_CLEAR );

		vipgoci_unittests_output_suppress();

		$all_comments = vipgoci_github_pr_generic_comments_get_all(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr_number'],
			$this->options['token']
		);

		vipgoci_unittests_output_unsuppress();

		return $all_comments;
	}

	/**
	 * Verify that the current item, $comment_item, is posted by the
	 * current GitHub token holder.
	 *
	 * @param object $comment_item Current comment.
	 *
	 * @return bool
	 */
	private function verifyIsOurComment( object $comment_item ) :bool {
		if ( ( strpos(
			$comment_item->body,
			VIPGOCI_REVIEW_COMMENTS_TOTAL_MAX
		) ) === false ) {
			return false;
		}

		if ( $this->current_user_info->login === $comment_item->user->login ) {
			return true;
		}

		return false;
	}

	/**
	 * Clear old comments posted by us.
	 *
	 * @return void
	 */
	private function clearOldComments(): void {
		// Clear internal cache so comments are fetched each time.
		vipgoci_cache( VIPGOCI_CACHE_CLEAR );

		vipgoci_unittests_output_suppress();

		$all_comments = vipgoci_github_pr_generic_comments_get_all(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr_number'],
			$this->options['token']
		);

		foreach ( $all_comments as $comment_item ) {
			if ( true === $this->verifyIsOurComment( $comment_item ) ) {
				vipgoci_github_pr_generic_comment_delete(
					$this->options['repo-owner'],
					$this->options['repo-name'],
					$this->options['token'],
					$comment_item->id
				);
			}
		}

		vipgoci_unittests_output_unsuppress();

		// Clear internal cache so comments are fetched each time.
		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
	}

	/**
	 * Test function. Comments are expected to reach maximum.
	 *
	 * @covers ::vipgoci_run_scan_total_comments_max_warning_post
	 *
	 * @return void
	 */
	public function testTotalCommentsReachedMax(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['review-comments-total-max'] = 100;

		$prs_comments_maxed = array(
			$this->options['pr_number'] => array(),
		);

		$all_comments = $this->fetchPrGenericComments();

		$this->assertEmpty( $all_comments );

		vipgoci_unittests_output_suppress();

		vipgoci_run_scan_total_comments_max_warning_post(
			$this->options,
			$prs_comments_maxed
		);

		vipgoci_unittests_output_unsuppress();

		sleep( 5 ); // Give API time to catch up.

		$all_comments = $this->fetchPrGenericComments();

		$this->assertNotEmpty( $all_comments );

		$i = 0;

		foreach ( $all_comments as $comment_item ) {
			$this->assertTrue( $this->verifyIsOurComment( $comment_item ) );
			$i++;
		}

		$this->assertTrue( $i > 0 );

		$this->assertTrue(
			vipgoci_report_feedback_to_github_was_submitted(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $this->options['pr_number']
			)
		);
	}

	/**
	 * Test function. Comments are not expected to reach maximum.
	 *
	 * @covers ::vipgoci_run_scan_total_comments_max_warning_post
	 *
	 * @return void
	 */
	public function testTotalCommentsDidNotReachMax(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['review-comments-total-max'] = 0;

		$prs_comments_maxed = array(
			$this->options['pr_number'] => array(),
		);

		$all_comments = $this->fetchPrGenericComments();

		$this->assertEmpty( $all_comments );

		vipgoci_unittests_output_suppress();

		vipgoci_run_scan_total_comments_max_warning_post(
			$this->options,
			$prs_comments_maxed
		);

		vipgoci_unittests_output_unsuppress();

		sleep( 5 ); // Give API time to catch up.

		$all_comments = $this->fetchPrGenericComments();

		$this->assertEmpty( $all_comments );

		$this->assertFalse(
			vipgoci_report_feedback_to_github_was_submitted(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $this->options['pr_number']
			)
		);
	}
}
