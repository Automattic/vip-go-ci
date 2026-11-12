<?php
/**
 * Verify token-holder login override and hidden comment marker handling.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Exercise the login override, marker helpers and marker-aware filtering with fake HTTP.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_github_authenticated_user_get' )]
#[CoversFunction( 'vipgoci_github_comment_marker_get' )]
#[CoversFunction( 'vipgoci_github_comment_marker_add' )]
#[CoversFunction( 'vipgoci_github_comment_marker_remove' )]
#[CoversFunction( 'vipgoci_github_comment_marker_legacy_login_get' )]
#[CoversFunction( 'vipgoci_github_item_has_any_marker' )]
#[CoversFunction( 'vipgoci_github_item_submitted_by_us' )]
#[CoversFunction( 'vipgoci_github_pr_reviews_get' )]
#[CoversFunction( 'vipgoci_github_pr_reviews_comments_get' )]
#[CoversFunction( 'vipgoci_github_pr_reviews_comments_get_by_pr' )]
#[CoversFunction( 'vipgoci_github_pr_reviews_dismiss_with_non_active_comments' )]
#[CoversFunction( 'vipgoci_github_pr_comments_generic_submit' )]
#[CoversFunction( 'vipgoci_github_pr_comments_cleanup' )]
#[CoversFunction( 'vipgoci_github_prs_implicated' )]
#[CoversFunction( 'vipgoci_report_submit_pr_generic_support_comment' )]
#[CoversFunction( 'vipgoci_results_remove_existing_github_comments' )]
final class GitHubCommentMarkerTest extends TestCase {
	/**
	 * Options for the fixture repository.
	 *
	 * @var array
	 */
	private array $options = array(
		'repo-owner' => 'owner',
		'repo-name'  => 'repo',
		'token'      => 'ghs_test-token',
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
		require_once __DIR__ . '/../../other-web-services.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/helper/GitHubRequestGuards.php';

		$GLOBALS['vipgoci_debug_level']         = -1;
		$GLOBALS['vipgoci_test_http_requests']  = array();
		$GLOBALS['vipgoci_test_http_responses'] = array();

		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		vipgoci_github_token_login_set( null );
		vipgoci_github_comment_marker_set( null );
		vipgoci_github_comment_marker_legacy_login_set( null );
	}

	/**
	 * Return a comment or review fixture.
	 *
	 * @param string $login Author login.
	 * @param string $body  Body.
	 * @param string $state Review state.
	 *
	 * @return array Fixture.
	 */
	private function item( string $login, string $body, string $state = 'CHANGES_REQUESTED' ): array {
		return array(
			'id'       => 7,
			'state'    => $state,
			'position' => 3,
			'line'     => 3,
			'user'     => array( 'login' => $login ),
			'body'     => $body,
		);
	}

	/**
	 * The configured login is used without querying GitHub.
	 *
	 * @return void
	 */
	public function testTokenLoginOverrideSkipsApi(): void {
		vipgoci_github_token_login_set( VIPGOCI_GITHUB_ACTIONS_BOT_LOGIN );

		$user_info = vipgoci_github_authenticated_user_get( $this->options['token'] );

		$this->assertSame( 'github-actions[bot]', $user_info->login );
		$this->assertSame( 'https://github.com/apps/github-actions', $user_info->html_url );
		$this->assertSame( array(), $GLOBALS['vipgoci_test_http_requests'] );
	}

	/**
	 * Markers are only added when configured, once, and can be removed.
	 *
	 * @return void
	 */
	public function testMarkerAddAndRemove(): void {
		$this->assertSame( '', vipgoci_github_comment_marker_get() );
		$this->assertSame( 'Body', vipgoci_github_comment_marker_add( 'Body' ) );

		vipgoci_github_comment_marker_set( 'actions' );

		$marked = vipgoci_github_comment_marker_add( 'Body' );

		$this->assertSame( "Body\n<!-- vip-go-ci-marker:actions -->", $marked );
		$this->assertSame( $marked, vipgoci_github_comment_marker_add( $marked ) );
		$this->assertSame( 'Body', vipgoci_github_comment_marker_remove( $marked ) );
	}

	/**
	 * A marker ID of "0" is valid and not treated as unset.
	 *
	 * @return void
	 */
	public function testMarkerZeroIsEnabled(): void {
		vipgoci_github_comment_marker_set( '0' );

		$this->assertSame( '<!-- vip-go-ci-marker:0 -->', vipgoci_github_comment_marker_get() );
		$this->assertSame( "Body\n<!-- vip-go-ci-marker:0 -->", vipgoci_github_comment_marker_add( 'Body' ) );

		$unmarked = json_decode( json_encode( $this->item( 'github-actions[bot]', 'Another workflow' ) ) );

		$this->assertFalse( vipgoci_github_item_submitted_by_us( $unmarked, 'github-actions[bot]' ) );
	}

	/**
	 * Initialization keeps a marker ID of "0".
	 *
	 * @return void
	 */
	public function testInitKeepsMarkerZero(): void {
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/../../main.php';

		$options = array(
			'token'              => 'ghs_test-token',
			'github-token-login' => 'github-actions[bot]',
			'comment-marker'     => '0',
		);

		ob_start();

		vipgoci_run_init_github_token_option( $options );

		ob_end_clean();

		$this->assertSame( '0', $options['comment-marker'] );
		$this->assertSame( '<!-- vip-go-ci-marker:0 -->', vipgoci_github_comment_marker_get() );
		$this->assertSame( array(), $GLOBALS['vipgoci_test_http_requests'] );
	}

	/**
	 * Ownership requires matching login, and the marker when configured.
	 *
	 * @return void
	 */
	public function testItemSubmittedByUs(): void {
		$plain  = json_decode( json_encode( $this->item( 'bot', 'Body' ) ) );
		$marked = json_decode( json_encode( $this->item( 'bot', "Body\n<!-- vip-go-ci-marker:actions -->" ) ) );
		$other  = json_decode( json_encode( $this->item( 'other', "Body\n<!-- vip-go-ci-marker:actions -->" ) ) );

		$this->assertTrue( vipgoci_github_item_submitted_by_us( $plain, 'bot' ) );
		$this->assertFalse( vipgoci_github_item_submitted_by_us( $other, 'bot' ) );

		vipgoci_github_comment_marker_set( 'actions' );

		$this->assertFalse( vipgoci_github_item_submitted_by_us( $plain, 'bot' ) );
		$this->assertTrue( vipgoci_github_item_submitted_by_us( $marked, 'bot' ) );
		$this->assertFalse( vipgoci_github_item_submitted_by_us( $other, 'bot' ) );
	}

	/**
	 * Reviews and review comments by the same login without our marker are not ours.
	 *
	 * @return void
	 */
	public function testMyselfFiltersRequireMarker(): void {
		vipgoci_github_token_login_set( VIPGOCI_GITHUB_ACTIONS_BOT_LOGIN );
		vipgoci_github_comment_marker_set( 'actions' );

		$ours   = $this->item( 'github-actions[bot]', "Ours\n<!-- vip-go-ci-marker:actions -->" );
		$theirs = $this->item( 'github-actions[bot]', 'Another workflow' );

		$GLOBALS['vipgoci_test_http_responses'] = array(
			'GET /repos/owner/repo/pulls/42/reviews'  => array( $ours, $theirs ),
			'GET /repos/owner/repo/pulls/42/comments' => array( $ours, $theirs ),
		);

		$reviews = vipgoci_github_pr_reviews_get(
			'owner',
			'repo',
			42,
			$this->options['token'],
			array(
				'login' => 'myself',
				'state' => array( 'CHANGES_REQUESTED' ),
			)
		);

		$comments = vipgoci_github_pr_reviews_comments_get_by_pr(
			$this->options,
			42,
			array( 'login' => 'myself' )
		);

		$this->assertCount( 1, $reviews );
		$this->assertSame( $ours['body'], $reviews[0]->body );
		$this->assertCount( 1, $comments );
		$this->assertSame( $ours['body'], $comments[0]->body );
	}

	/**
	 * Unmarked feedback by the legacy login is ours, only when a marker is configured.
	 *
	 * @return void
	 */
	public function testLegacyLoginItemsAreOursWhenUnmarked(): void {
		$legacy         = json_decode( json_encode( $this->item( 'legacy-bot', 'Body' ) ) );
		$legacy_foreign = json_decode( json_encode( $this->item( 'legacy-bot', "Body\n<!-- vip-go-ci-marker:other -->" ) ) );
		$other          = json_decode( json_encode( $this->item( 'other', 'Body' ) ) );

		vipgoci_github_comment_marker_legacy_login_set( 'legacy-bot' );

		$this->assertFalse( vipgoci_github_item_submitted_by_us( $legacy, 'bot' ) );

		vipgoci_github_comment_marker_set( 'actions' );

		$this->assertTrue( vipgoci_github_item_submitted_by_us( $legacy, 'bot' ) );
		$this->assertFalse( vipgoci_github_item_submitted_by_us( $legacy_foreign, 'bot' ) );
		$this->assertFalse( vipgoci_github_item_submitted_by_us( $other, 'bot' ) );
	}

	/**
	 * The default legacy login is the account vip-go-ci posted as earlier.
	 *
	 * @return void
	 */
	public function testLegacyLoginDefault(): void {
		$this->assertSame( 'wpcomvip-vipgoci-bot', VIPGOCI_GITHUB_COMMENT_MARKER_LEGACY_LOGIN_DEFAULT );
	}

	/**
	 * Only our marked inline comments, and unmarked ones by the legacy login, count
	 * as already posted. Unmarked ones by the token-holder, which may be shared with
	 * other workflows, others' comments and foreign markers do not.
	 *
	 * @return void
	 */
	public function testExistingCommentsKeepUnmarkedAndSkipForeignMarkers(): void {
		vipgoci_github_token_login_set( VIPGOCI_GITHUB_ACTIONS_BOT_LOGIN );
		vipgoci_github_comment_marker_set( 'actions' );
		vipgoci_github_comment_marker_legacy_login_set( 'legacy-bot' );

		$comment = function ( string $login, string $body, int $position ): array {
			return array_merge(
				$this->item( $login, $body ),
				array(
					'path'               => 'test.php',
					'position'           => $position,
					'original_commit_id' => 'abc',
				)
			);
		};

		$GLOBALS['vipgoci_test_http_responses'] = array(
			'GET /repos/owner/repo/pulls/42/comments' => array(
				$comment( 'github-actions[bot]', "Ours\n<!-- vip-go-ci-marker:actions -->", 1 ),
				$comment( 'legacy-bot', 'Legacy', 2 ),
				$comment( 'github-actions[bot]', 'Before marker', 3 ),
				$comment( 'github-actions[bot]', "Other instance\n<!-- vip-go-ci-marker:other -->", 4 ),
				$comment( 'some-user', 'Unmarked', 5 ),
				$comment( 'some-user', "Copied\n<!-- vip-go-ci-marker:actions -->", 6 ),
			),
		);

		$comments = array();

		vipgoci_github_pr_reviews_comments_get( $this->options, 'abc', '2026-01-01T00:00:00Z', $comments, 42 );

		$this->assertSame(
			array( 'test.php:1', 'test.php:2' ),
			array_keys( $comments )
		);
	}

	/**
	 * Legacy reviews are recognized and dismissed, unmarked reviews by other logins are not.
	 *
	 * @return void
	 */
	public function testLegacyReviewDismissedOthersKept(): void {
		vipgoci_github_token_login_set( VIPGOCI_GITHUB_ACTIONS_BOT_LOGIN );
		vipgoci_github_comment_marker_set( 'actions' );
		vipgoci_github_comment_marker_legacy_login_set( 'legacy-bot' );

		$legacy       = array_merge( $this->item( 'legacy-bot', 'Legacy' ), array( 'id' => 7 ) );
		$other        = array_merge( $this->item( 'github-actions[bot]', 'Another workflow' ), array( 'id' => 8 ) );
		$obsolete_cmt = function ( string $login, int $review_id ): array {
			return array_merge(
				$this->item( $login, 'Comment' ),
				array(
					'position'               => null,
					'line'                   => null,
					'pull_request_review_id' => $review_id,
				)
			);
		};

		$GLOBALS['vipgoci_test_http_responses'] = array(
			'GET /repos/owner/repo/pulls/42/reviews'  => array( $legacy, $other ),
			'GET /repos/owner/repo/pulls/42/comments' => array(
				$obsolete_cmt( 'legacy-bot', 7 ),
				$obsolete_cmt( 'github-actions[bot]', 8 ),
			),
		);

		vipgoci_github_pr_reviews_dismiss_with_non_active_comments( $this->options, 42 );

		$dismissals = array_values(
			array_filter(
				array_column( $GLOBALS['vipgoci_test_http_requests'], 'request' ),
				static fn( string $request ): bool => str_starts_with( $request, 'PUT ' )
			)
		);

		$this->assertSame(
			array( 'PUT /repos/owner/repo/pulls/42/reviews/7/dismissals' ),
			$dismissals
		);
	}

	/**
	 * Generic comments carry the marker.
	 *
	 * @return void
	 */
	public function testGenericCommentIncludesMarker(): void {
		vipgoci_github_comment_marker_set( 'actions' );

		vipgoci_github_pr_comments_generic_submit(
			'owner',
			'repo',
			$this->options['token'],
			42,
			'Hello'
		);

		$this->assertStringEndsWith(
			'<!-- vip-go-ci-marker:actions -->',
			$GLOBALS['vipgoci_test_http_requests'][0]['body']['body']
		);
	}

	/**
	 * A comment matching several items to remove is only deleted once.
	 *
	 * @return void
	 */
	public function testCommentMatchingSeveralItemsDeletedOnce(): void {
		vipgoci_github_token_login_set( 'bot' );

		$GLOBALS['vipgoci_test_http_responses'] = array(
			'GET /repos/owner/repo/pulls'             => array(
				array(
					'number' => 42,
					'head'   => array(
						'ref' => 'branch',
						'sha' => 'abc',
					),
				),
			),
			'GET /repos/owner/repo/issues/42/comments' => array(
				$this->item( 'bot', 'No issues found. See Update Scan.' ),
			),
		);

		vipgoci_github_pr_comments_cleanup(
			'owner',
			'repo',
			'abc',
			$this->options['token'],
			array(),
			false,
			array( 'No issues found', 'Update Scan' )
		);

		$deletes = array_filter(
			$GLOBALS['vipgoci_test_http_requests'],
			static fn( array $request ): bool => str_starts_with( $request['request'], 'DELETE ' )
		);

		$this->assertSame(
			array( 'DELETE /repos/owner/repo/issues/comments/7' ),
			array_values( array_column( $deletes, 'request' ) )
		);
	}

	/**
	 * Does not sleep after fetching the last page of pull requests.
	 *
	 * @return void
	 */
	public function testPrsImplicatedDoesNotSleepAfterLastPage(): void {
		$time_start = microtime( true );

		vipgoci_github_prs_implicated(
			'owner',
			'repo',
			'abc',
			$this->options['token'],
			array()
		);

		$this->assertLessThan( 1, microtime( true ) - $time_start );
	}

	/**
	 * Existing comments, marker configuration and expected number of POSTs.
	 *
	 * @return array Test cases.
	 */
	public static function dataSupportCommentDeduplication(): array {
		$msg = 'Welcome to the project';

		return array(
			'no existing comment'       => array( array(), 'actions', 1 ),
			'our marked comment'        => array(
				array( 'github-actions[bot]', $msg . "\n<!-- vip-go-ci-marker:actions -->" ),
				'actions',
				0,
			),
			'foreign marker'            => array(
				array( 'github-actions[bot]', $msg . "\n<!-- vip-go-ci-marker:other -->" ),
				'actions',
				1,
			),
			'foreign author'            => array(
				array( 'some-user', $msg . "\n<!-- vip-go-ci-marker:actions -->" ),
				'actions',
				1,
			),
			'unmarked legacy login'     => array( array( 'legacy-bot', $msg ), 'actions', 0 ),
			'marker disabled, any user' => array( array( 'some-user', $msg ), null, 0 ),
		);
	}

	/**
	 * The generic support comment is deduplicated only against our
	 * own comments when a marker is configured, and against any
	 * comment otherwise.
	 *
	 * @param array       $existing       Login and body of an existing comment, if any.
	 * @param null|string $marker         Comment marker ID.
	 * @param int         $expected_posts Expected number of comments posted.
	 *
	 * @return void
	 */
	#[DataProvider( 'dataSupportCommentDeduplication' )]
	public function testSupportCommentDeduplication( array $existing, null|string $marker, int $expected_posts ): void {
		require_once __DIR__ . '/../../reports.php';

		vipgoci_github_token_login_set( VIPGOCI_GITHUB_ACTIONS_BOT_LOGIN );
		vipgoci_github_comment_marker_set( $marker );
		vipgoci_github_comment_marker_legacy_login_set( 'legacy-bot' );

		$GLOBALS['vipgoci_test_http_responses'] = array(
			'GET /repos/owner/repo/issues/42/comments' => empty( $existing ) ?
				array() :
				array( $this->item( $existing[0], $existing[1] ) ),
		);

		$options = array_merge(
			$this->options,
			array(
				'post-generic-pr-support-comments'           => true,
				'post-generic-pr-support-comments-on-drafts' => array( 1 => false ),
				'post-generic-pr-support-comments-string'    => array( 1 => 'Welcome to the project' ),
				'post-generic-pr-support-comments-branches'  => array( 1 => array( 'any' ) ),
				'post-generic-pr-support-comments-repo-meta-match' => array(),
				'post-generic-pr-support-comments-skip-if-label-exists' => array(),
			)
		);

		$pr_item = json_decode(
			json_encode(
				array(
					'number' => 42,
					'draft'  => false,
					'base'   => array( 'ref' => 'trunk' ),
				)
			)
		);

		vipgoci_report_submit_pr_generic_support_comment( $options, array( $pr_item ) );

		$posts = array_filter(
			$GLOBALS['vipgoci_test_http_requests'],
			static fn( array $request ): bool => 'POST /repos/owner/repo/issues/42/comments' === $request['request']
		);

		$this->assertCount( $expected_posts, $posts );
	}

	/**
	 * A comment made obsolete by a later commit, with a position but
	 * no line, does not count as already posted: the matching finding
	 * is still reported and counted.
	 *
	 * @return void
	 */
	public function testObsoleteCommentFromEarlierCommitDoesNotRemoveFinding(): void {
		require_once __DIR__ . '/../../results.php';

		vipgoci_github_token_login_set( VIPGOCI_GITHUB_ACTIONS_BOT_LOGIN );
		vipgoci_github_comment_marker_set( 'actions' );

		$comment = function ( string $commit_id, null|int $line ): array {
			return array(
				'id'                     => 1,
				'pull_request_review_id' => 1,
				'user'                   => array( 'login' => 'github-actions[bot]' ),
				'body'                   => "**Error**: Some issue (*WordPress.Foo.Bar*).\n<!-- vip-go-ci-marker:actions -->",
				'path'                   => 'test.php',
				'position'               => 3,
				'line'                   => $line,
				'original_commit_id'     => $commit_id,
				'created_at'             => '2026-01-01T00:00:00Z',
				'updated_at'             => '2026-01-01T00:00:00Z',
			);
		};

		$pr_item = (object) array(
			'number'     => 42,
			'created_at' => '2026-01-01T00:00:00Z',
		);

		foreach ( array( 'obsolete' => array( null, 1 ), 'active' => array( 3, 0 ) ) as $case => list( $line, $expected_issues ) ) {
			vipgoci_cache( VIPGOCI_CACHE_CLEAR );

			$GLOBALS['vipgoci_test_http_responses'] = array(
				'GET /repos/owner/repo/pulls/42/commits'  => array(
					array( 'sha' => 'commit-a' ),
					array( 'sha' => 'commit-b' ),
				),
				'GET /repos/owner/repo/pulls/42/comments' => array(
					$comment( 'commit-a', $line ),
				),
			);

			$results = array(
				'issues' => array(
					42 => array(
						array(
							'type'      => VIPGOCI_STATS_PHPCS,
							'file_name' => 'test.php',
							'file_line' => 3,
							'issue'     => array(
								'message' => 'Some issue',
								'source'  => 'WordPress.Foo.Bar',
								'level'   => 'ERROR',
							),
						),
					),
				),
				'stats'  => array(
					VIPGOCI_STATS_PHPCS => array(
						42 => array( 'error' => 1 ),
					),
				),
			);

			vipgoci_results_remove_existing_github_comments( $this->options, array( $pr_item ), $results );

			$this->assertCount( $expected_issues, $results['issues'][42], $case );
			$this->assertSame( $expected_issues, $results['stats'][ VIPGOCI_STATS_PHPCS ][42]['error'], $case );
		}
	}
}
