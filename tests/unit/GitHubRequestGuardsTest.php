<?php
/**
 * Verify empty-work guards and preserve useful GitHub processing.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Exercise real result processing, API filtering and caching with fake HTTP.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_results_remove_existing_github_comments' )]
#[CoversFunction( 'vipgoci_results_filter_comments_to_max' )]
#[CoversFunction( 'vipgoci_github_pr_reviews_dismiss_with_non_active_comments' )]
#[CoversFunction( 'vipgoci_report_submit_pr_review_from_results' )]
final class GitHubRequestGuardsTest extends TestCase {
	/**
	 * Options for the fixture repository.
	 *
	 * @var array
	 */
	private array $options = array(
		'repo-owner'                                  => 'owner',
		'repo-name'                                   => 'repo',
		'token'                                       => 'test-token',
		'review-comments-total-max'                   => 1,
		'dismissed-reviews-exclude-reviews-from-team' => array(),
	);

	/**
	 * Load production functions and isolate their HTTP boundary.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../cache.php';
		require_once __DIR__ . '/../../github-api.php';
		require_once __DIR__ . '/../../github-misc.php';
		require_once __DIR__ . '/../../results.php';
		require_once __DIR__ . '/../../reports.php';
		require_once __DIR__ . '/../../skip-file.php';
		require_once __DIR__ . '/../../output-security.php';
		require_once __DIR__ . '/../../other-web-services.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/helper/GitHubRequestGuards.php';

		$GLOBALS['vipgoci_debug_level']         = -1;
		$GLOBALS['vipgoci_test_http_requests']  = array();
		$GLOBALS['vipgoci_test_http_responses'] = array(
			'GET /repos/owner/repo/pulls/42/commits' => array( array( 'sha' => 'abc' ) ),
		);

		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		vipgoci_cache(
			array( 'vipgoci_github_authenticated_user_get', 'test-token' ),
			(object) array( 'login' => 'bot' )
		);
	}

	/**
	 * Return initialized, empty scan results for a pull request.
	 *
	 * @return array Results fixture.
	 */
	private function emptyResults(): array {
		return array(
			'issues'        => array( 42 => array() ),
			'stats'         => array(
				'phpcs' => array(
					42 => array(
						'error'   => 0,
						'warning' => 0,
						'info'    => 0,
					),
				),
			),
			'skipped-files' => array(
				42 => array(
					'issues' => array(),
					'total'  => 0,
				),
			),
		);
	}

	/**
	 * Return one issue with a known matching comment.
	 *
	 * @return array Issue fixture.
	 */
	private function issue(): array {
		return array(
			'type'      => 'phpcs',
			'file_name' => 'test.php',
			'file_line' => 3,
			'issue'     => array(
				'message'  => 'Escape output.',
				'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
				'level'    => 'ERROR',
				'severity' => 5,
			),
		);
	}

	/**
	 * Return a review fixture, defaulting to our changes-requested review.
	 *
	 * @param string $state Review state.
	 * @param string $login Review author.
	 * @param string $body  Review body.
	 *
	 * @return array Review fixture.
	 */
	private function review( string $state = 'CHANGES_REQUESTED', string $login = 'bot', string $body = '' ): array {
		return array(
			'id'    => 7,
			'state' => $state,
			'user'  => array( 'login' => $login ),
			'body'  => $body,
		);
	}

	/**
	 * Return an inline comment fixture.
	 *
	 * @param int|null $position Active diff position, or null for an obsolete comment.
	 *
	 * @return array Comment fixture.
	 */
	private function comment( ?int $position = 3 ): array {
		return array(
			'id'                     => 10,
			'pull_request_review_id' => 7,
			'pull_request_url'       => 'https://api.github.com/repos/owner/repo/pulls/42',
			'original_commit_id'     => 'abc',
			'commit_id'              => 'abc',
			'path'                   => 'test.php',
			'position'               => $position,
			'body'                   => 'Escape output.',
			'user'                   => array( 'login' => 'bot' ),
			'created_at'             => '2026-01-02T00:00:00Z',
			'updated_at'             => '2026-01-02T00:00:00Z',
		);
	}

	/**
	 * Assert the complete sequence of external requests.
	 *
	 * @param array $expected Expected methods and paths.
	 *
	 * @return void
	 */
	private function assertRequests( array $expected ): void {
		$this->assertSame( $expected, array_column( $GLOBALS['vipgoci_test_http_requests'], 'request' ) );
	}

	/**
	 * Empty PRs must not fetch history even when dismissed comments can be reposted.
	 *
	 * @return void
	 */
	public function testEmptyDeduplicationMakesNoRequests(): void {
		$results  = $this->emptyResults();
		$expected = $results;

		vipgoci_results_remove_existing_github_comments(
			$this->options,
			array(
				(object) array(
					'number'     => 42,
					'created_at' => '2026-01-01T00:00:00Z',
				),
			),
			$results,
			true
		);

		$this->assertSame( $expected, $results );
		$this->assertRequests( array() );
	}

	/**
	 * An empty PR must not prevent deduplication for another PR.
	 *
	 * @return void
	 */
	public function testDeduplicationStillRemovesExistingIssuesInMixedRun(): void {
		$results                                = $this->emptyResults();
		$results['issues'][41]                  = array();
		$results['issues'][42]                  = array( $this->issue() );
		$results['stats']['phpcs'][42]['error'] = 1;
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/comments'] = array( $this->comment() );

		vipgoci_results_remove_existing_github_comments(
			$this->options,
			array(
				(object) array(
					'number'     => 41,
					'created_at' => '2025-01-01T00:00:00Z',
				),
				(object) array(
					'number'     => 42,
					'created_at' => '2026-01-01T00:00:00Z',
				),
			),
			$results
		);

		$this->assertSame(
			array(
				42 => array(),
				41 => array(),
			),
			$results['issues']
		);
		$this->assertSame( 0, $results['stats']['phpcs'][42]['error'] );
		$this->assertRequests( array( 'GET /repos/owner/repo/pulls/42/commits', 'GET /repos/owner/repo/pulls/comments' ) );
	}

	/**
	 * Without new comments, the cap cannot remove or warn about anything.
	 *
	 * @return void
	 */
	public function testEmptyCommentLimitMakesNoRequests(): void {
		$results  = $this->emptyResults();
		$expected = $results;
		$maxed    = array();

		vipgoci_results_filter_comments_to_max( $this->options, $results, $maxed );

		$this->assertSame( $expected, $results );
		$this->assertSame( array(), $maxed );
		$this->assertRequests( array() );
	}

	/**
	 * Nonempty results must still honor the dismissed-review repost setting.
	 *
	 * @return void
	 */
	public function testDismissedReviewCommentsCanStillBeReposted(): void {
		$results                                = $this->emptyResults();
		$results['issues'][42]                  = array( $this->issue() );
		$results['stats']['phpcs'][42]['error'] = 1;
		$expected                               = $results;
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/comments']   = array( $this->comment() );
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/reviews'] = array( $this->review( 'DISMISSED' ) );

		vipgoci_results_remove_existing_github_comments(
			$this->options,
			array(
				(object) array(
					'number'     => 42,
					'created_at' => '2026-01-01T00:00:00Z',
				),
			),
			$results,
			true
		);

		$this->assertSame( $expected, $results );
		$this->assertRequests(
			array(
				'GET /repos/owner/repo/pulls/42/commits',
				'GET /repos/owner/repo/pulls/comments',
				'GET /repos/owner/repo/pulls/42/reviews',
			)
		);
	}

	/**
	 * Existing active comments must still count toward the cap.
	 *
	 * @return void
	 */
	public function testCommentLimitStillRemovesExcessIssuesInMixedRun(): void {
		$results                                = $this->emptyResults();
		$results['issues']                      = array(
			41 => array(),
			42 => array( $this->issue() ),
		);
		$results['stats']['phpcs'][42]['error'] = 1;
		$maxed                                  = array();
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/comments'] = array( $this->comment() );

		vipgoci_results_filter_comments_to_max( $this->options, $results, $maxed );

		$this->assertSame(
			array(
				41 => array(),
				42 => array(),
			),
			$results['issues']
		);
		$this->assertSame( 0, $results['stats']['phpcs'][42]['error'] );
		$this->assertSame( array( 42 => true ), $maxed );
		$this->assertRequests( array( 'GET /repos/owner/repo/pulls/42/comments' ) );
	}

	/**
	 * Reviews in other states or by other people do not need dismissal comments.
	 *
	 * @return void
	 */
	public function testNoEligibleReviewsSkipsCommentFetch(): void {
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/reviews'] = array(
			$this->review( 'APPROVED' ),
			$this->review( 'CHANGES_REQUESTED', 'someone-else' ),
		);

		vipgoci_github_pr_reviews_dismiss_with_non_active_comments( $this->options, 42 );

		$this->assertRequests( array( 'GET /repos/owner/repo/pulls/42/reviews' ) );
	}

	/**
	 * A PR with no reviews has no comments relevant to dismissal.
	 *
	 * @return void
	 */
	public function testNoReviewsSkipsCommentFetch(): void {
		vipgoci_github_pr_reviews_dismiss_with_non_active_comments( $this->options, 42 );

		$this->assertRequests( array( 'GET /repos/owner/repo/pulls/42/reviews' ) );
	}

	/**
	 * An empty comment response must never cause an eligible review to be dismissed.
	 *
	 * @return void
	 */
	public function testNoCommentsDoesNotDismissEligibleReview(): void {
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/reviews'] = array( $this->review() );

		vipgoci_github_pr_reviews_dismiss_with_non_active_comments( $this->options, 42 );

		$this->assertRequests( array( 'GET /repos/owner/repo/pulls/42/reviews', 'GET /repos/owner/repo/pulls/42/comments' ) );
	}

	/**
	 * Eligible reviews still require a fresh comment snapshot before dismissal.
	 *
	 * @return void
	 */
	public function testEligibleReviewRefreshesCommentsAndDismissesObsoleteReview(): void {
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/reviews']  = array( $this->review() );
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/comments'] = array( $this->comment() );
		vipgoci_github_pr_reviews_comments_get_by_pr( $this->options, 42, array( 'login' => 'myself' ) );
		$GLOBALS['vipgoci_test_http_requests'] = array();
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/comments'] = array( $this->comment( null ) );

		vipgoci_github_pr_reviews_dismiss_with_non_active_comments( $this->options, 42 );

		$this->assertRequests(
			array(
				'GET /repos/owner/repo/pulls/42/reviews',
				'GET /repos/owner/repo/pulls/42/comments',
				'PUT /repos/owner/repo/pulls/42/reviews/7/dismissals',
			)
		);
	}

	/**
	 * Active inline comments must keep a blocking review in place.
	 *
	 * @return void
	 */
	public function testActiveCommentPreventsDismissal(): void {
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/reviews']  = array( $this->review() );
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/comments'] = array( $this->comment( null ), $this->comment() );

		vipgoci_github_pr_reviews_dismiss_with_non_active_comments( $this->options, 42 );

		$this->assertRequests( array( 'GET /repos/owner/repo/pulls/42/reviews', 'GET /repos/owner/repo/pulls/42/comments' ) );
	}

	/**
	 * Submit fixture results through the production report builder.
	 *
	 * @param array $results Scan results.
	 *
	 * @return void
	 */
	private function submitReport( array $results ): void {
		vipgoci_report_submit_pr_review_from_results(
			'owner',
			'repo',
			'test-token',
			'abc',
			$results,
			'',
			'',
			20,
			false,
			1000,
			'Test bot'
		);
	}

	/**
	 * A clean scan has no skipped-file history to compare.
	 *
	 * @return void
	 */
	public function testCleanReportMakesNoRequests(): void {
		$this->submitReport( $this->emptyResults() );
		$this->assertRequests( array() );
	}

	/**
	 * Reporting real issues does not require skipped-file history either.
	 *
	 * @return void
	 */
	public function testIssuesWithoutSkippedFilesStillSubmitReviewWithoutHistoryFetch(): void {
		$results                                = $this->emptyResults();
		$results['issues'][42]                  = array( $this->issue() );
		$results['stats']['phpcs'][42]['error'] = 1;

		$this->submitReport( $results );

		$this->assertRequests( array( 'POST /repos/owner/repo/pulls/42/reviews' ) );
		$body = $GLOBALS['vipgoci_test_http_requests'][0]['body'];
		$this->assertSame( 'REQUEST_CHANGES', $body['event'] );
		$this->assertSame( 'abc', $body['commit_id'] );
		$this->assertCount( 1, $body['comments'] );
		$this->assertSame( 'test.php', $body['comments'][0]['path'] );
		$this->assertSame( 3, $body['comments'][0]['position'] );
		$this->assertSame(
			':no_entry_sign: **Error**: Escape output (*WordPress.Security.EscapeOutput.OutputNotEscaped*).',
			$body['comments'][0]['body']
		);
	}

	/**
	 * A first review fetch after reporting must not dismiss the new active review.
	 *
	 * @return void
	 */
	public function testNewlySubmittedActiveReviewIsNotDismissed(): void {
		$results                                = $this->emptyResults();
		$results['issues'][42]                  = array( $this->issue() );
		$results['stats']['phpcs'][42]['error'] = 1;

		$this->submitReport( $results );
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/reviews']  = array( $this->review() );
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/comments'] = array( $this->comment() );

		vipgoci_github_pr_reviews_dismiss_with_non_active_comments( $this->options, 42 );

		$this->assertRequests(
			array(
				'POST /repos/owner/repo/pulls/42/reviews',
				'GET /repos/owner/repo/pulls/42/reviews',
				'GET /repos/owner/repo/pulls/42/comments',
			)
		);
	}

	/**
	 * Skipped files must still be compared with prior reports to avoid duplicates.
	 *
	 * @return void
	 */
	public function testPreviouslyReportedSkippedFileIsNotReposted(): void {
		$results                      = $this->emptyResults();
		$results['skipped-files'][42] = array(
			'issues' => array( 'max-lines' => array( 'large.php' ) ),
			'total'  => 1,
		);
		$body                         = '**skipped-files**' . PHP_EOL . PHP_EOL .
			'Maximum number of lines exceeded (1000):' . PHP_EOL .
			' - large.php' . PHP_EOL . PHP_EOL .
			'Note that the above file(s) were not analyzed due to their length.';
		$GLOBALS['vipgoci_test_http_responses']['GET /repos/owner/repo/pulls/42/reviews'] = array( $this->review( 'COMMENTED', 'bot', $body ) );

		$this->submitReport( $results );

		$this->assertRequests( array( 'GET /repos/owner/repo/pulls/42/reviews' ) );
	}

	/**
	 * Newly skipped files must still generate a review without code issues.
	 *
	 * @return void
	 */
	public function testNewSkippedFileIsStillReported(): void {
		$results                      = $this->emptyResults();
		$results['skipped-files'][42] = array(
			'issues' => array( 'max-lines' => array( 'large.php' ) ),
			'total'  => 1,
		);

		$this->submitReport( $results );

		$this->assertRequests( array( 'GET /repos/owner/repo/pulls/42/reviews', 'POST /repos/owner/repo/pulls/42/reviews' ) );
		$body = $GLOBALS['vipgoci_test_http_requests'][1]['body'];
		$this->assertSame( 'COMMENT', $body['event'] );
		$this->assertStringContainsString( 'large.php', $body['body'] );
	}
}
