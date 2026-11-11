<?php
/**
 * Exercise approval provenance and race handling with real Git and fake HTTP.
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

/** No live requests or approval mutations are made by these tests. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_rebase_approval_process' )]
#[CoversFunction( 'vipgoci_rebase_api_read' )]
#[CoversFunction( 'vipgoci_rebase_human_reviews' )]
#[CoversFunction( 'vipgoci_rebase_baseline_read' )]
#[CoversFunction( 'vipgoci_rebase_pr_matches' )]
#[CoversFunction( 'vipgoci_rebase_scan_clear' )]
#[CoversFunction( 'vipgoci_rebase_review_write' )]
#[CoversFunction( 'vipgoci_rebase_approval_revoked' )]
#[CoversFunction( 'vipgoci_rebase_approval_verify' )]
final class RebaseApprovalProcessTest extends TestCase {
	/** Fixture.
	 *
	 * @var array
	 */
	private array $fixture;
	/** Options.
	 *
	 * @var array
	 */
	private array $options;
	/** Baseline.
	 *
	 * @var array
	 */
	private array $baseline;
	/** Pr.
	 *
	 * @var array
	 */
	private array $pr;
	/** Reviews.
	 *
	 * @var array
	 */
	private array $reviews;
	/** Events.
	 *
	 * @var array
	 */
	private array $events;
	/** Results.
	 *
	 * @var array
	 */
	private array $results;
	/** Root.
	 *
	 * @var string
	 */
	private string $root = '/repos/owner/repo/';

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		foreach ( array( 'defines.php', 'cache.php', 'github-api.php', 'rebase-approval.php', 'log.php', 'misc.php', 'other-web-services.php' ) as $file ) {
			require_once __DIR__ . '/../../' . $file;
		}
		require_once __DIR__ . '/helper/RebaseApprovalFixtures.php';
		require_once __DIR__ . '/helper/RebaseApprovalHttp.php';
		$GLOBALS['vipgoci_debug_level'] = -1;
		$GLOBALS['rebase_posts']        = array();
		$GLOBALS['rebase_puts']         = array();
		$GLOBALS['rebase_responses']    = array();
		unset( $GLOBALS['rebase_post_response'] );
		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		vipgoci_cache(
			array( 'vipgoci_github_authenticated_user_get', 'test-token' ),
			(object) array(
				'id'    => 999,
				'login' => 'scanner',
			)
		);
		$this->fixture  = vipgoci_unittests_rebase_repository();
		$this->options  = array(
			'lint'                              => true,
			'autoapprove-rebases'               => true,
			'autoapprove-rebases-reviewer-ids'  => array( '42' ),
			'autoapprove-rebases-base-branches' => array( 'trunk' ),
			'local-git-repo'                    => $this->fixture['directory'],
			'commit'                            => $this->fixture['head'],
			'repo-owner'                        => 'owner',
			'repo-name'                         => 'repo',
			'token'                             => 'test-token',
		);
		$this->baseline = array(
			'head'      => $this->fixture['reviewed'],
			'base'      => $this->fixture['base'],
			'fork'      => $this->fixture['base'],
			'review'    => 101,
			'reviewer'  => 42,
			'base_repo' => 1,
			'head_repo' => 2,
			'pr'        => 7,
			'base_ref'  => 'trunk',
		);
		$this->pr       = array(
			'state'  => 'open',
			'draft'  => false,
			'number' => 7,
			'user'   => array( 'id' => 123 ),
			'head'   => array(
				'sha'  => $this->fixture['head'],
				'repo' => array( 'id' => 2 ),
			),
			'base'   => array(
				'sha'  => $this->fixture['upstream'],
				'ref'  => 'trunk',
				'repo' => array( 'id' => 1 ),
			),
		);
		$this->reviews  = array(
			array(
				'id'        => 101,
				'user'      => array(
					'id'   => 42,
					'type' => 'User',
				),
				'state'     => 'DISMISSED',
				'commit_id' => $this->fixture['reviewed'],
				'body'      => 'Approved',
			),
			array(
				'id'        => 102,
				'user'      => array(
					'id'   => 999,
					'type' => 'Bot',
				),
				'state'     => 'COMMENTED',
				'commit_id' => $this->fixture['reviewed'],
				'body'      => 'vip-go-ci rebase baseline v1: ' . json_encode( $this->baseline ),
			),
		);
		$this->events   = array(
			array(
				'event'            => 'review_dismissed',
				'dismissed_review' => array(
					'review_id'           => 101,
					'state'               => 'approved',
					'dismissal_commit_id' => $this->fixture['head'],
				),
			),
		);
		$this->results  = array(
			'scan-completion' => array(
				'commit' => $this->fixture['head'],
				'lint'   => array( 7 => true ),
			),
			'issues'          => array( 7 => array() ),
			'stats'           => array(
				'lint' => array(
					7 => array(
						'error'   => 0,
						'warning' => 0,
						'info'    => 0,
					),
				),
			),
			'skipped-files'   => array( 7 => array( 'total' => 0 ) ),
		);
	}

	/**
	 * Tear down.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		vipgoci_unittests_rebase_remove( $this->fixture['directory'] );
	}

	/**
	 * Responses.
	 *
	 * @return void
	 */
	private function responses(): void {
		$GLOBALS['rebase_responses'] = array(
			$this->root . 'pulls/7' => $this->pr,
			$this->root . 'pulls/7/reviews?per_page=100&page=1' => $this->reviews,
			$this->root . 'issues/7/timeline?per_page=100&page=1' => $this->events,
		);
	}

	/**
	 * Run process.
	 *
	 * @return array PRs whose approvals were verified.
	 */
	private function runProcess(): array {
		return vipgoci_rebase_approval_process( $this->options, $this->results, array( (object) array( 'number' => 7 ) ) );
	}

	/**
	 * Test approves only the scanned head with provenance.
	 *
	 * @return void
	 */
	public function testApprovesOnlyTheScannedHeadWithProvenance(): void {
		$this->responses();
		$this->assertSame( array( 7 ), $this->runProcess() );
		$this->assertCount( 1, $GLOBALS['rebase_posts'] );
		$post = $GLOBALS['rebase_posts'][0];
		$this->assertSame( 'APPROVE', $post['body']['event'] );
		$this->assertSame( $this->fixture['head'], $post['body']['commit_id'] );
		$this->assertStringContainsString( '#101', $post['body']['body'] );
		$this->assertSame( array(), $GLOBALS['rebase_puts'] );
		$this->assertSame( array(), $this->results['issues'][7] );
	}

	/**
	 * Test records baseline while human approval is current.
	 *
	 * @return void
	 */
	public function testRecordsBaselineWhileHumanApprovalIsCurrent(): void {
		$this->options['commit']                    = $this->fixture['reviewed'];
		$this->results['scan-completion']['commit'] = $this->fixture['reviewed'];
		$this->pr['head']['sha']                    = $this->fixture['reviewed'];
		$this->pr['base']['sha']                    = $this->fixture['base'];
		$this->reviews                              = array( $this->reviews[0] );
		$this->reviews[0]['state']                  = 'APPROVED';
		$this->events                               = array();
		$this->responses();
		$this->runProcess();
		$this->assertCount( 1, $GLOBALS['rebase_posts'] );
		$this->assertSame( 'COMMENT', $GLOBALS['rebase_posts'][0]['body']['event'] );
		$body = $GLOBALS['rebase_posts'][0]['body']['body'];
		$this->assertSame( $this->baseline, json_decode( substr( $body, strlen( 'vip-go-ci rebase baseline v1: ' ) ), true ) );
	}

	/**
	 * Rejected evidence.
	 *
	 * @return array Test cases.
	 */
	public static function rejectedEvidence(): array {
		return array_map(
			static fn( string $scenario ): array => array( $scenario ),
			array(
				'disabled',
				'untrusted reviewer',
				'author approval',
				'bot approval',
				'manual dismissal',
				'wrong dismissal commit',
				'changes requested',
				'missing baseline',
				'forged baseline',
				'wrong reviewer',
				'wrong PR',
				'wrong fork repository',
				'retargeted',
				'retargeted back',
				'draft',
				'closed',
				'stale head',
				'issues',
				'error count',
				'skipped file',
				'incomplete results',
				'malformed review',
				'API failure',
				'missing history',
				'dismissed baseline',
				'already approved',
				'manually revoked bot approval',
			)
		);
	}

	/**
	 * Test untrusted or incomplete evidence never approves.
	 *
	 * @param string $scenario Case.
	 * @return void
	 */
	#[DataProvider( 'rejectedEvidence' )]
	public function testUntrustedOrIncompleteEvidenceNeverApproves( string $scenario ): void {
		switch ( $scenario ) {
			case 'disabled':
				$this->options['autoapprove-rebases'] = false;
				break;
			case 'untrusted reviewer':
				$this->options['autoapprove-rebases-reviewer-ids'] = array( '43' );
				break;
			case 'author approval':
				$this->pr['user']['id'] = 42;
				break;
			case 'bot approval':
				$this->reviews[0]['user']['type'] = 'Bot';
				break;
			case 'manual dismissal':
				unset( $this->events[0]['dismissed_review']['dismissal_commit_id'] );
				break;
			case 'wrong dismissal commit':
				$this->events[0]['dismissed_review']['dismissal_commit_id'] = $this->fixture['base'];
				break;
			case 'changes requested':
				$this->reviews[] = array(
					'id'    => 103,
					'state' => 'CHANGES_REQUESTED',
					'user'  => array( 'id' => 43 ),
				);
				break;
			case 'missing baseline':
				array_pop( $this->reviews );
				break;
			case 'forged baseline':
				$this->reviews[1]['user']['id'] = 43;
				break;
			case 'wrong reviewer':
				$this->baseline['reviewer'] = 43;
				$this->reviews[1]['body']   = 'vip-go-ci rebase baseline v1: ' . json_encode( $this->baseline );
				break;
			case 'wrong PR':
				$this->baseline['pr']     = 8;
				$this->reviews[1]['body'] = 'vip-go-ci rebase baseline v1: ' . json_encode( $this->baseline );
				break;
			case 'wrong fork repository':
				$this->pr['head']['repo']['id'] = 3;
				break;
			case 'retargeted':
				$this->pr['base']['ref'] = 'other';
				break;
			case 'retargeted back':
				$this->events[] = array( 'event' => 'base_ref_changed' );
				break;
			case 'draft':
				$this->pr['draft'] = true;
				break;
			case 'closed':
				$this->pr['state'] = 'closed';
				break;
			case 'stale head':
				$this->pr['head']['sha'] = $this->fixture['base'];
				break;
			case 'issues':
				$this->results['issues'][7][] = array( 'message' => 'new issue' );
				break;
			case 'error count':
				$this->results['stats']['lint'][7]['error'] = 1;
				break;
			case 'skipped file':
				$this->results['skipped-files'][7]['total'] = 1;
				break;
			case 'incomplete results':
				unset( $this->results['skipped-files'] );
				break;
			case 'malformed review':
				unset( $this->reviews[0]['user'] );
				break;
			case 'missing history':
				$this->baseline['head']        = str_repeat( 'a', 40 );
				$this->reviews[0]['commit_id'] = $this->baseline['head'];
				$this->reviews[1]['commit_id'] = $this->baseline['head'];
				$this->reviews[1]['body']      = 'vip-go-ci rebase baseline v1: ' . json_encode( $this->baseline );
				break;
			case 'dismissed baseline':
				$this->reviews[1]['state'] = 'DISMISSED';
				break;
			case 'manually revoked bot approval':
				$this->reviews[] = array(
					'id'    => 103,
					'state' => 'DISMISSED',
					'user'  => array( 'id' => 999 ),
					'body'  => 'Auto-approved conflict-free rebase of human review #101; exact integrated tree verified.',
				);
				$this->events[]  = array(
					'event'            => 'review_dismissed',
					'dismissed_review' => array(
						'review_id' => 103,
						'state'     => 'approved',
					),
				);
				break;
			case 'already approved':
				$this->reviews[] = array(
					'id'        => 103,
					'state'     => 'APPROVED',
					'user'      => array( 'id' => 999 ),
					'commit_id' => $this->fixture['head'],
				);
				break;
		}
		$this->responses();
		if ( 'API failure' === $scenario ) {
			$GLOBALS['rebase_responses'][ $this->root . 'issues/7/timeline?per_page=100&page=1' ] = null;
		}
		$this->runProcess();
		$this->assertSame( array(), $GLOBALS['rebase_posts'] );
	}

	/**
	 * Changed during proof.
	 *
	 * @return array Test cases.
	 */
	public static function changedDuringProof(): array {
		return array( array( 'head' ), array( 'base' ), array( 'reviews' ), array( 'events' ) );
	}

	/**
	 * Test refuses changed evidence during proof.
	 *
	 * @param string $change Change.
	 * @return void
	 */
	#[DataProvider( 'changedDuringProof' )]
	public function testRefusesChangedEvidenceDuringProof( string $change ): void {
		$this->responses();
		$calls = 0;
		if ( in_array( $change, array( 'head', 'base' ), true ) ) {
			$pr                        = $this->pr;
			$changed                   = $pr;
			$changed[ $change ]['sha'] = $this->fixture['base'];
			$GLOBALS['rebase_responses'][ $this->root . 'pulls/7' ] = static function () use ( &$calls, $pr, $changed ): array {
				return 1 === ++$calls ? $pr : $changed;
			};
		} else {
			$values = 'reviews' === $change ? $this->reviews : $this->events;
			$key    = 'reviews' === $change ? 'pulls/7/reviews' : 'issues/7/timeline';
			$GLOBALS['rebase_responses'][ $this->root . $key . '?per_page=100&page=1' ] = static function () use ( &$calls, $values ): array {
				return 1 === ++$calls ? $values : array();
			};
		}
		$this->runProcess();
		$this->assertSame( array(), $GLOBALS['rebase_posts'] );
	}

	/**
	 * Test dismisses the new approval if the base moves during submission.
	 *
	 * @return void
	 */
	public function testDismissesTheNewApprovalIfTheBaseMovesDuringSubmission(): void {
		$this->responses();
		$pr                     = $this->pr;
		$changed                = $pr;
		$changed['base']['sha'] = $this->fixture['base'];
		$calls                  = 0;
		$GLOBALS['rebase_responses'][ $this->root . 'pulls/7' ] = static function () use ( &$calls, $pr, $changed ): array {
			return ++$calls < 3 ? $pr : $changed;
		};
		$this->assertSame( array(), $this->runProcess() );
		$this->assertCount( 1, $GLOBALS['rebase_posts'] );
		$this->assertCount( 1, $GLOBALS['rebase_puts'] );
		$this->assertStringEndsWith( '/reviews/900/dismissals', $GLOBALS['rebase_puts'][0]['url'] );
	}

	/**
	 * Test partial pagination cannot authorize approval.
	 *
	 * @return void
	 */
	public function testPartialPaginationCannotAuthorizeApproval(): void {
		$this->responses();
		$GLOBALS['rebase_responses'][ $this->root . 'pulls/7/reviews?per_page=100&page=1' ] = array_merge( $this->reviews, array_fill( 0, 98, $this->reviews[0] ) );
		// The unconfigured second page is a transport failure, not an empty page.
		$this->runProcess();
		$this->assertSame( array(), $GLOBALS['rebase_posts'] );
	}

	/** A lost POST response still requires checking the actual server-side review. */
	public function testRecoversApprovalAfterInconclusivePostResponse(): void {
		$this->responses();
		$GLOBALS['rebase_post_response'] = '{}';
		$this->runProcess();
		$this->assertCount( 1, $GLOBALS['rebase_posts'] );
		$this->assertSame( array(), $GLOBALS['rebase_puts'] );
	}

	/** Recover the created ID and roll back even when the POST response was lost. */
	public function testInconclusivePostAndMovedHeadStillDismissesCreatedApproval(): void {
		$this->responses();
		$GLOBALS['rebase_post_response'] = '{}';
		$pr                              = $this->pr;
		$changed                         = $pr;
		$changed['head']['sha']          = $this->fixture['base'];
		$calls                           = 0;
		$GLOBALS['rebase_responses'][ $this->root . 'pulls/7' ] = static function () use ( &$calls, $pr, $changed ): array {
			return ++$calls < 3 ? $pr : $changed;
		};
		$this->runProcess();
		$this->assertCount( 1, $GLOBALS['rebase_puts'] );
		$this->assertStringEndsWith( '/reviews/900/dismissals', $GLOBALS['rebase_puts'][0]['url'] );
	}

	/** New change requests arriving during POST invalidate the carried approval. */
	public function testChangeRequestDuringPostDismissesApproval(): void {
		$this->responses();
		$reviews = $this->reviews;
		$changed = array_merge(
			$reviews,
			array(
				array(
					'id'    => 103,
					'state' => 'CHANGES_REQUESTED',
					'user'  => array( 'id' => 43 ),
				),
			)
		);
		$calls   = 0;
		$GLOBALS['rebase_responses'][ $this->root . 'pulls/7/reviews?per_page=100&page=1' ] = static function () use ( &$calls, $reviews, $changed ): array {
			return ++$calls < 3 ? $reviews : $changed;
		};
		$this->runProcess();
		$this->assertCount( 1, $GLOBALS['rebase_puts'] );
	}

	/** Avoid duplicate baseline reviews when rerunning the approved head. */
	public function testExistingBaselineIsNotRecordedAgain(): void {
		$this->options['commit']                    = $this->fixture['reviewed'];
		$this->results['scan-completion']['commit'] = $this->fixture['reviewed'];
		$this->pr['head']['sha']                    = $this->fixture['reviewed'];
		$this->pr['base']['sha']                    = $this->fixture['base'];
		$this->reviews[0]['state']                  = 'APPROVED';
		$this->events                               = array();
		$this->responses();
		$this->runProcess();
		$this->assertSame( array(), $GLOBALS['rebase_posts'] );
	}

	/** A current approval can be carried even where stale-review dismissal is off. */
	public function testUndismissedHumanApprovalCanBeCarried(): void {
		$this->reviews[0]['state'] = 'APPROVED';
		$this->events              = array();
		$this->responses();
		$this->runProcess();
		$this->assertCount( 1, $GLOBALS['rebase_posts'] );
	}

	/** Both policies must preserve the carried approval on a same-head rerun. */
	public function testSameHeadRerunPreservesApproval(): void {
		require_once __DIR__ . '/../../auto-approval.php';
		require_once __DIR__ . '/../../statistics.php';
		$this->options['autoapprove']           = true;
		$this->options['autoapprove-filetypes'] = array( 'css' );
		$this->options['autoapprove-label']     = 'auto-approved';
		$this->options['wpscan-api']            = false;
		$GLOBALS['rebase_file_policy_calls']    = 0;
		$this->responses();
		for ( $run = 0; $run < 2; ++$run ) {
			$verified = $this->runProcess();
			$this->assertSame( array( 7 ), $verified );
			// The changed PHP file does not qualify under the file-type policy.
			$approved_files = array();
			vipgoci_auto_approval_scan_commit( $this->options, $approved_files, $this->results, $verified );
		}
		$this->assertSame( 2, $GLOBALS['rebase_file_policy_calls'] );
		$this->assertCount( 1, $GLOBALS['rebase_posts'] );
		$this->assertSame( array(), $GLOBALS['rebase_puts'] );
	}

	/** Incomplete scanning must prevent both baseline capture and carry-forward. */
	public function testIncompleteScanCannotRecordBaselineOrApprove(): void {
		$this->results['scan-completion']['lint'][7] = false;
		$this->responses();
		$this->assertSame( array(), $this->runProcess() );
		$this->options['commit']                    = $this->fixture['reviewed'];
		$this->results['scan-completion']['commit'] = $this->fixture['reviewed'];
		$this->pr['head']['sha']                    = $this->fixture['reviewed'];
		$this->pr['base']['sha']                    = $this->fixture['base'];
		$this->reviews                              = array( $this->reviews[0] );
		$this->reviews[0]['state']                  = 'APPROVED';
		$this->events                               = array();
		$this->responses();
		$this->assertSame( array(), $this->runProcess() );
		$this->assertSame( array(), $GLOBALS['rebase_posts'] );
	}
	/**
	 * Authenticated rollbacks can be retried; copied or altered markers cannot.
	 *
	 * @return array Timeline mutation and expected retry authorization.
	 */
	public static function rollbackCases(): array {
		return array( array( 'scanner rollback', true ), array( 'human actor', false ), array( 'missing actor', false ), array( 'wrong review', false ), array( 'wrong head', false ), array( 'wrong human review', false ), array( 'unmarked dismissal', false ) );
	}

	/**
	 * Persist a rollback and retry against its actual dismissed review/timeline.
	 *
	 * @param string $scenario Timeline evidence to alter after the rollback.
	 * @param bool   $allowed Whether the retry should approve.
	 */
	#[DataProvider( 'rollbackCases' )]
	public function testPersistedRollbackCanBeRetriedOnlyWithAuthenticatedEvidence( string $scenario, bool $allowed ): void {
		$this->responses();
		$pr    = $this->pr;
		$calls = 0;
		$GLOBALS['rebase_responses'][ $this->root . 'pulls/7' ] = static function () use ( &$calls, $pr ): ?array {
			// Simulate an inconclusive post-write read, then restore fresh evidence.
			return 3 === ++$calls ? null : $pr;
		};
		$this->assertSame( array(), $this->runProcess() );
		$this->assertCount( 1, $GLOBALS['rebase_puts'] );
		$review = $GLOBALS['rebase_responses'][ $this->root . 'pulls/7/reviews?per_page=100&page=1' ][2];
		$this->assertSame( 'DISMISSED', $review['state'] );
		$timeline  = &$GLOBALS['rebase_responses'][ $this->root . 'issues/7/timeline?per_page=100&page=1' ];
		$dismissal = &$timeline[ array_key_last( $timeline ) ];
		$this->assertSame( 900, $dismissal['dismissed_review']['review_id'] );
		if ( 'human actor' === $scenario ) {
			$dismissal['actor']['id'] = 42;
		} elseif ( 'missing actor' === $scenario ) {
			unset( $dismissal['actor'] );
		} elseif ( 'wrong review' === $scenario ) {
			$dismissal['dismissed_review']['dismissal_message'] = vipgoci_rebase_rollback_message( 901, $this->fixture['head'], 101 );
		} elseif ( 'wrong head' === $scenario ) {
			$dismissal['dismissed_review']['dismissal_message'] = vipgoci_rebase_rollback_message( 900, $this->fixture['base'], 101 );
		} elseif ( 'wrong human review' === $scenario ) {
			$dismissal['dismissed_review']['dismissal_message'] = vipgoci_rebase_rollback_message( 900, $this->fixture['head'], 102 );
		} elseif ( 'unmarked dismissal' === $scenario ) {
			$dismissal['dismissed_review']['dismissal_message'] = 'Manual revocation';
		}
		$this->assertSame( $allowed ? array( 7 ) : array(), $this->runProcess() );
		$this->assertCount( $allowed ? 2 : 1, $GLOBALS['rebase_posts'] );
		$this->assertCount( 1, $GLOBALS['rebase_puts'] );
	}
}
