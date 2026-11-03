<?php
/**
 * Verify token-holder login override and hidden comment marker handling.
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
	 * Unmarked inline comments by any login count as already posted, foreign markers do not.
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
				$comment( 'some-user', 'Unmarked', 3 ),
				$comment( 'github-actions[bot]', "Other instance\n<!-- vip-go-ci-marker:other -->", 4 ),
			),
		);

		$comments = array();

		vipgoci_github_pr_reviews_comments_get( $this->options, 'abc', '2026-01-01T00:00:00Z', $comments, 42 );

		$this->assertSame(
			array( 'test.php:1', 'test.php:2', 'test.php:3' ),
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
}
