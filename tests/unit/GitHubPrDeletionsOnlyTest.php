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
use PHPUnit\Framework\Attributes\DataProvider;
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
#[CoversFunction( 'vipgoci_gitrepo_deletions_only' )]
#[CoversFunction( 'vipgoci_auto_approval_scan_commit' )]
#[CoversFunction( 'vipgoci_report_submit_pr_generic_support_comment' )]
final class GitHubPrDeletionsOnlyTest extends MockedApiTestCase {
	private string $repo;
	private string $base;
	private string $head;

	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../../git-repo.php';
		require_once __DIR__ . '/../../statistics.php';
		$this->repo = tempnam( sys_get_temp_dir(), 'vipgoci-deletions-' );
		unlink( $this->repo );
		mkdir( $this->repo );
		$this->git( 'init -q' );
		$this->git( 'config core.filemode true' );
		file_put_contents( $this->repo . '/code.php', "one\ntwo\nthree\nfour\n" );
		file_put_contents( $this->repo . '/payload.txt', "<?php\nrun();\n" );
		file_put_contents( $this->repo . '/binary.bin', "x\0y" );
		$this->git( 'add .' );
		$this->git( 'commit -qm base' );
		$this->base = $this->git( 'rev-parse HEAD' );
		file_put_contents( $this->repo . '/code.php', '' );
		$this->git( 'commit -qam deletions' );
		$this->head = $this->git( 'rev-parse HEAD' );
	}

	protected function tearDown(): void {
		try {
			parent::tearDown();
		} finally {
			$items = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $this->repo, \FilesystemIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $items as $item ) {
				$item->isDir() && ! $item->isLink() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
			}
			rmdir( $this->repo );
		}
	}

	/** Execute Git only in this test's disposable repository. */
	private function git( string $arguments ): string {
		$output = array();
		$code   = -1;
		exec( 'git -c user.name=Test -c user.email=test@example.com -c commit.gpgsign=false -C ' . escapeshellarg( $this->repo ) . ' ' . $arguments . ' 2>&1', $output, $code );
		$this->assertSame( 0, $code, implode( "\n", $output ) );
		return trim( implode( "\n", $output ) );
	}

	/** Test the bypass with real Git objects despite apparently safe API counts. */
	#[DataProvider( 'mixedChanges' )]
	public function testWholeDiffRejectsMixedChanges( string $change ): void {
		require_once __DIR__ . '/../../github-api.php';
		switch ( $change ) {
			case 'rename':
				$this->git( 'mv payload.txt payload.php' );
				break;
			case 'mode':
				$this->git( 'update-index --chmod=+x payload.txt' );
				break;
			case 'binary':
				file_put_contents( $this->repo . '/binary.bin', "x\0" );
				break;
			case 'binary deletion':
				unlink( $this->repo . '/binary.bin' );
				break;
			case 'symlink':
				unlink( $this->repo . '/payload.txt' );
				symlink( 'code.php', $this->repo . '/payload.txt' );
				break;
			case 'submodule':
				$this->git( 'update-index --add --cacheinfo 160000,' . $this->base . ',module' );
				break;
			case 'addition':
				file_put_contents( $this->repo . '/payload.txt', "added\n" );
				break;
			case 'empty addition':
				file_put_contents( $this->repo . '/empty.txt', '' );
				break;
			case 'missing base':
				$this->base = str_repeat( 'a', 40 );
				break;
			case 'missing head':
				$this->head = str_repeat( 'b', 40 );
				break;
			case 'empty diff':
				$this->head = $this->base;
				break;
			case 'whole file removal':
				unlink( $this->repo . '/payload.txt' );
				$this->git( 'add -A' );
				$this->git( 'commit -qm removal' );
				$this->head = $this->git( 'rev-parse HEAD' );
				break;
			case 'unusual path':
				$path = $this->repo . "/tab\tand\nnewline.txt";
				file_put_contents( $path, "old\nremove\n" );
				$this->git( 'add -A' );
				$this->git( 'commit -qm baseline' );
				$this->base = $this->git( 'rev-parse HEAD' );
				file_put_contents( $path, "old\n" );
				$this->git( 'commit -qam deletion' );
				$this->head = $this->git( 'rev-parse HEAD' );
				break;
			case 'earlier addition':
				file_put_contents( $this->repo . '/payload.txt', "added\nline\n" );
				$this->git( 'commit -qam earlier' );
				file_put_contents( $this->repo . '/payload.txt', "added\n" );
				$this->git( 'commit -qam latest-deletion' );
				$this->head = $this->git( 'rev-parse HEAD' );
				break;
			case 'invalid base':
				$this->base = 'HEAD';
				break;
			case 'valid deletions':
				break;
		}
		if ( in_array( $change, array( 'rename', 'mode', 'binary', 'binary deletion', 'symlink', 'submodule', 'addition', 'empty addition' ), true ) ) {
			// Keep the staged mode change; stage the worktree only for other cases.
			if ( ! in_array( $change, array( 'mode', 'submodule' ), true ) ) {
				$this->git( 'add -A' );
			}
			$this->git( 'commit -qm mixed' );
			$this->head = $this->git( 'rev-parse HEAD' );
		}
		HttpApiMock::expect(
			'GET',
			'https://api.github.com/repos/owner/repo/pulls/1',
			'token',
			json_encode(
				array(
					'head'      => array( 'sha' => $this->head ),
					'base'      => array( 'sha' => $this->base ),
					'additions' => 0,
					'deletions' => 4,
				)
			)
		);
		$options = array(
			'repo-owner'     => 'owner',
			'repo-name'      => 'repo',
			'token'          => 'token',
			'commit'         => $this->head,
			'local-git-repo' => $this->repo,
		);
		$this->assertSame( in_array( $change, array( 'valid deletions', 'whole file removal', 'unusual path' ), true ), vipgoci_github_pr_deletions_only( $options, 1 ) );
	}

	public static function mixedChanges(): array {
		return array_map( static fn( $case ) => array( $case ), array( 'rename', 'mode', 'binary', 'binary deletion', 'symlink', 'submodule', 'addition', 'empty addition', 'missing base', 'missing head', 'empty diff', 'valid deletions', 'whole file removal', 'unusual path', 'earlier addition', 'invalid base' ) );
	}

	/**
	 * Require complete statistics and a matching head, including cached results.
	 *
	 * @return void
	 */
	public function testWholePrClassification(): void {
		require_once __DIR__ . '/../../github-api.php';
		$options = array(
			'repo-owner'     => 'owner',
			'repo-name'      => 'repo',
			'token'          => 'token',
			'commit'         => $this->head,
			'local-git-repo' => $this->repo,
		);
		foreach ( array(
			array(
				array(
					'head'      => array( 'sha' => $this->head ),
					'base'      => array( 'sha' => $this->base ),
					'additions' => 0,
					'deletions' => 4,
				),
				true,
			),
			array(
				array(
					'head'      => array( 'sha' => $this->head ),
					'base'      => array( 'sha' => $this->base ),
					'additions' => 1,
					'deletions' => 4,
				),
				false,
			),
			array(
				array(
					'head'      => array( 'sha' => $this->head ),
					'base'      => array( 'sha' => $this->base ),
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
					'head'      => array( 'sha' => $this->head ),
					'base'      => array( 'sha' => $this->base ),
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
			'commit'                                     => $this->head,
			'local-git-repo'                             => $this->repo,
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
							'head'      => array( 'sha' => $this->head ),
							'base'      => array( 'sha' => $this->base ),
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
			array( true, 0, 4, false, true, false, false ),
			array( false, 0, 4, false, false, false, false ),
			array( true, 1, 4, false, false, false, false ),
			array( true, 0, 0, false, false, false, false ),
			array( true, 0, 4, true, false, false, false ),
			array( true, 1, 4, false, false, true, false ),
			array( true, 0, 4, false, false, false, true ),
		) as [ $enabled, $additions, $deletions, $wpscan_error, $approve, $head_changed, $rename ] ) {
			vipgoci_cache( VIPGOCI_CACHE_CLEAR );
			if ( $rename ) {
				$this->git( 'mv payload.txt payload.php' );
				$this->git( 'commit -qm rename' );
				$this->head = $this->git( 'rev-parse HEAD' );
			}
			$options = array(
				'repo-owner'                            => 'owner',
				'repo-name'                             => 'repo',
				'token'                                 => 'token',
				'commit'                                => $this->head,
				'local-git-repo'                        => $this->repo,
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
				HttpApiMock::expect(
					'GET',
					'https://api.github.com/repos/owner/repo/pulls/1',
					'token',
					json_encode(
						array(
							'head'      => array( 'sha' => $this->head ),
							'base'      => array( 'sha' => $this->base ),
							'additions' => 0,
							'deletions' => 4,
						)
					)
				);
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
							'head'      => array( 'sha' => $head_changed ? 'new-head' : $this->head ),
							'base'      => array( 'sha' => $this->base ),
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
						'commit_id' => $this->head,
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
