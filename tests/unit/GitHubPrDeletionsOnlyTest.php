<?php
/**
 * Whole-PR deletion classification and support comments.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Vipgoci\Tests\Common\HttpApiMock;
use Vipgoci\Tests\Common\MockedApiTestCase;

require_once __DIR__ . '/../common/MockedApiTestCase.php';

/** Verify classification fails closed and comments use the selected message. */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_github_pr_deletions_only' )]
#[CoversFunction( 'vipgoci_auto_approval_scan_commit' )]
#[CoversFunction( 'vipgoci_report_submit_pr_generic_support_comment' )]
final class GitHubPrDeletionsOnlyTest extends MockedApiTestCase {
	/**
	 * Require complete statistics and a matching head, including cached results.
	 *
	 * @return void
	 */
	public function testWholePrClassification(): void {
		require_once __DIR__ . '/../../github-api.php';
		$options = array(
			'repo-owner' => 'owner',
			'repo-name'  => 'repo',
			'token'      => 'token',
			'commit'     => 'head',
		);
		foreach ( array(
			array(
				array(
					'head'      => array( 'sha' => 'head' ),
					'additions' => 0,
					'deletions' => 4,
				),
				true,
			),
			array(
				array(
					'head'      => array( 'sha' => 'head' ),
					'additions' => 1,
					'deletions' => 4,
				),
				false,
			),
			array(
				array(
					'head'      => array( 'sha' => 'head' ),
					'additions' => 0,
					'deletions' => 0,
				),
				false,
			),
			array(
				array(
					'head'      => array( 'sha' => 'new-head' ),
					'additions' => 0,
					'deletions' => 4,
				),
				false,
			),
			array( array( 'message' => 'Not Found' ), false ),
			array(
				array(
					'head'      => array( 'sha' => 'head' ),
					'deletions' => 4,
				),
				false,
			),
		) as $case ) {
			vipgoci_cache( VIPGOCI_CACHE_CLEAR );
			HttpApiMock::expect( 'GET', 'https://api.github.com/repos/owner/repo/pulls/1', 'token', json_encode( $case[0] ) );
			$this->assertSame( $case[1], vipgoci_github_pr_deletions_only( $options, 1 ) );
			$this->assertSame( $case[1], vipgoci_github_pr_deletions_only( $options, 1 ), 'Cached classification differs.' );
		}
	}

	/**
	 * Post and deduplicate the appropriate message for each kind of PR.
	 *
	 * @return void
	 */
	public function testSupportMessageAndDuplicateDetection(): void {
		require_once __DIR__ . '/../../github-api.php';
		require_once __DIR__ . '/../../reports.php';
		require_once __DIR__ . '/../../other-web-services.php';
		$options = array(
			'repo-owner'                                 => 'owner',
			'repo-name'                                  => 'repo',
			'token'                                      => 'token',
			'commit'                                     => 'head',
			'post-generic-pr-support-comments'           => true,
			'post-generic-pr-support-comments-string'    => array( 0 => 'Regular support message' ),
			'post-generic-pr-support-comments-branches'  => array( 0 => array( 'any' ) ),
			'post-generic-pr-support-comments-on-drafts' => array( 0 => false ),
			'post-generic-pr-support-comments-repo-meta-match' => array(),
		);
		$message = 'This pull request only removes lines. Please verify that the removed code is no longer needed and that tests pass before merging.';
		$pr      = (object) array(
			'number' => 1,
			'draft'  => false,
			'base'   => (object) array( 'ref' => 'trunk' ),
		);
		foreach ( array( true, false ) as $deletions_only ) {
			$selected = $deletions_only ? $message : 'Regular support message';
			foreach ( array( false, true ) as $exists ) {
				vipgoci_cache( VIPGOCI_CACHE_CLEAR );
				HttpApiMock::expect(
					'GET',
					'https://api.github.com/repos/owner/repo/pulls/1',
					'token',
					json_encode(
						array(
							'head'      => array( 'sha' => 'head' ),
							'additions' => $deletions_only ? 0 : 1,
							'deletions' => 4,
						)
					)
				);
				HttpApiMock::expect( 'GET', 'https://api.github.com/repos/owner/repo/issues/1/comments?page=1&per_page=100', 'token', json_encode( $exists ? array( array( 'body' => $selected ) ) : array() ) );
				if ( ! $exists ) {
					HttpApiMock::expect( 'POST', 'https://api.github.com/repos/owner/repo/issues/1/comments', 'token', '{}', array( 'body' => $selected . "\n\r" ) );
				}
				vipgoci_report_submit_pr_generic_support_comment( $options, array( $pr ) );
				HttpApiMock::assert_complete();
			}
		}
	}
	/**
	 * Approve deletion-only PHP changes only when enabled and WPScan permits it.
	 *
	 * @return void
	 */
	public function testAutoApprovalPolicyAndWpscanVeto(): void {
		require_once __DIR__ . '/helper/DeletionOnlyFilesAffected.php';
		require_once __DIR__ . '/../../github-api.php';
		require_once __DIR__ . '/../../auto-approval.php';
		require_once __DIR__ . '/../../reports.php';
		require_once __DIR__ . '/../../statistics.php';
		require_once __DIR__ . '/../../other-web-services.php';
		foreach ( array(
			array( true, 0, 4, false, true, false ),
			array( false, 0, 4, false, false, false ),
			array( true, 1, 4, false, false, false ),
			array( true, 0, 0, false, false, false ),
			array( true, 0, 4, true, false, false ),
			array( true, 1, 4, false, false, true ),
		) as [ $enabled, $additions, $deletions, $wpscan_error, $approve, $head_changed ] ) {
			vipgoci_cache( VIPGOCI_CACHE_CLEAR );
			$options = array(
				'repo-owner'                            => 'owner',
				'repo-name'                             => 'repo',
				'token'                                 => 'token',
				'commit'                                => 'head',
				'autoapprove'                           => true,
				'autoapprove-deletions-only'            => $enabled,
				'autoapprove-filetypes'                 => array(),
				'autoapprove-label'                     => 'approved',
				'autoapprove-php-nonfunctional-changes' => false,
				'wpscan-api'                            => true,
				'wpscan-api-dry-mode'                   => false,
			);
			$results = array(
				'stats' => array(
					VIPGOCI_STATS_WPSCAN_API => array(
						1 => array(
							VIPGOCI_ISSUE_TYPE_ERROR   => $wpscan_error ? 1 : 0,
							VIPGOCI_ISSUE_TYPE_WARNING => 0,
						),
					),
				),
			);
			if ( $head_changed ) {
				// Support comments run before scanning and cache the old head's result.
				$support_options = $options + array(
					'post-generic-pr-support-comments' => true,
					'post-generic-pr-support-comments-string' => array( 0 => 'Regular support message' ),
					'post-generic-pr-support-comments-branches' => array( 0 => array( 'any' ) ),
					'post-generic-pr-support-comments-on-drafts' => array( 0 => false ),
					'post-generic-pr-support-comments-repo-meta-match' => array(),
				);
				$pr              = (object) array(
					'number' => 1,
					'draft'  => false,
					'base'   => (object) array( 'ref' => 'trunk' ),
				);
				$message         = 'This pull request only removes lines. Please verify that the removed code is no longer needed and that tests pass before merging.';
				HttpApiMock::expect( 'GET', 'https://api.github.com/repos/owner/repo/pulls/1', 'token', '{"head":{"sha":"head"},"additions":0,"deletions":4}' );
				HttpApiMock::expect( 'GET', 'https://api.github.com/repos/owner/repo/issues/1/comments?page=1&per_page=100', 'token', '[]' );
				HttpApiMock::expect( 'POST', 'https://api.github.com/repos/owner/repo/issues/1/comments', 'token', '{}', array( 'body' => $message . "\n\r" ) );
				vipgoci_report_submit_pr_generic_support_comment( $support_options, array( $pr ) );
				HttpApiMock::assert_complete();
			}
			if ( $enabled ) {
				HttpApiMock::expect(
					'GET',
					'https://api.github.com/repos/owner/repo/pulls/1',
					'token',
					json_encode(
						array(
							'head'      => array( 'sha' => $head_changed ? 'new-head' : 'head' ),
							'additions' => $additions,
							'deletions' => $deletions,
						)
					)
				);
			}
			HttpApiMock::expect( 'GET', 'https://api.github.com/repos/owner/repo/issues/1/labels', 'token', '[]' );
			HttpApiMock::expect( 'GET', 'https://api.github.com/repos/owner/repo/pulls/1/reviews?per_page=100&page=1', 'token', '[]' );
			HttpApiMock::expect( 'GET', 'https://api.github.com/user', 'token', '{"login":"bot"}' );
			if ( $approve ) {
				HttpApiMock::expect(
					'POST',
					'https://api.github.com/repos/owner/repo/pulls/1/reviews',
					'token',
					'{}',
					array(
						'commit_id' => 'head',
						'body'      => 'Auto-approved pull request #1 because it only removes lines.',
						'event'     => 'APPROVE',
						'comments'  => array(),
					)
				);
				HttpApiMock::expect( 'POST', 'https://api.github.com/repos/owner/repo/issues/1/labels', 'token', '{}', array( 'approved' ) );
			}
			$approved_files = array();
			vipgoci_auto_approval_scan_commit( $options, $approved_files, $results );
			$this->assertSame( array(), $approved_files, 'PR approval must not mark files as approved in other PRs.' );
			HttpApiMock::assert_complete();
		}
	}
}
