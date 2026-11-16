<?php
/**
 * Keep auto-approval decisions scoped to their pull request and base.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/** Exercise real Git comparisons and approval consumers without network access. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
final class AutoApprovalPrIsolationTest extends TestCase {
	/** @var string Fixture repository. */
	private string $repo;

	/** @var array Scanner options. */
	private array $options;

	/** Load production modules and construct two different bases for one head. */
	protected function setUp(): void {
		foreach ( array( 'defines', 'cache', 'log', 'misc', 'statistics', 'git-repo', 'github-api', 'github-misc', 'ap-file-types', 'ap-nonfunctional-changes', 'ap-svg-files', 'auto-approval', 'results', 'output-security', 'other-web-services', 'reports' ) as $module ) {
			require_once __DIR__ . '/../../' . $module . '.php';
		}
		require_once __DIR__ . '/helper/GitHubRequestGuards.php';
		$GLOBALS['vipgoci_debug_level'] = -1;
		$GLOBALS['vipgoci_test_http_requests'] = array();
		$GLOBALS['vipgoci_test_http_responses'] = array();
		$this->repo = tempnam( sys_get_temp_dir(), 'vipgoci-approval-' );
		unlink( $this->repo );
		mkdir( $this->repo );
		$this->git( 'init -q' );
		file_put_contents( $this->repo . '/shared.php', "<?php return 1;\n" );
		$root = $this->commit();
		file_put_contents( $this->repo . '/shared.php', "<?php return 2;\n" );
		$base = $this->commit();
		file_put_contents( $this->repo . '/shared.php', "<?php /* comment */return 2;\n" );
		$head = $this->commit();
		$this->options = array(
			'repo-owner' => 'fixture',
			'repo-name' => 'fixture',
			'token' => 'unused',
			'commit' => $head,
			'local-git-repo' => $this->repo,
			'branches-ignore' => array(),
			'skip-draft-prs' => false,
			'autoapprove' => true,
			'autoapprove-filetypes' => array(),
			'autoapprove-php-nonfunctional-changes' => true,
			'autoapprove-php-nonfunctional-changes-file-extensions' => array( 'php' ),
			'autoapprove-label' => 'Approved',
			'wpscan-api' => false,
			'dry-run' => false,
		);
		$this->setPrs( array( 101 => $base, 202 => $root ) );
		vipgoci_cache(
			array( 'vipgoci_github_authenticated_user_get', 'unused' ),
			(object) array( 'login' => 'bot', 'id' => 123 )
		);
	}

	/** Remove only this test's repository without following links. */
	protected function tearDown(): void {
		$entries = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->repo, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $entries as $entry ) {
			if ( $entry->isDir() && ! $entry->isLink() ) {
				rmdir( $entry->getPathname() );
			} else {
				unlink( $entry->getPathname() );
			}
		}
		rmdir( $this->repo );
	}

	/**
	 * Run only fixture-owned Git arguments, with signing and hooks disabled.
	 *
	 * @param string $arguments Fixture command arguments.
	 * @return string Output.
	 */
	private function git( string $arguments ): string {
		exec(
			'git -c user.name=Fixture -c user.email=fixture@example.invalid -c commit.gpgsign=false -c core.hooksPath=/dev/null -C ' .
			escapeshellarg( $this->repo ) . ' ' . $arguments . ' 2>&1',
			$output,
			$code
		);
		$this->assertSame( 0, $code, implode( "\n", $output ) );
		return implode( "\n", $output );
	}

	/** Commit fixture contents and return their object ID. */
	private function commit(): string {
		$this->git( 'add -A' );
		$this->git( 'commit -qm fixture' );
		return $this->git( 'rev-parse HEAD' );
	}

	/**
	 * Seed only external PR discovery; all Git work remains real.
	 *
	 * @param array $bases Base commits keyed by PR number.
	 */
	private function setPrs( array $bases ): void {
		$prs = array();
		foreach ( $bases as $number => $base ) {
			$prs[] = (object) array( 'number' => $number, 'base' => (object) array( 'sha' => $base ) );
		}
		vipgoci_cache(
			array( 'vipgoci_github_prs_implicated', 'fixture', 'fixture', $this->options['commit'], 'unused', array() ),
			$prs
		);
	}

	/** Different bases must not share approvals, regardless of processing order. */
	public function testDifferentBasesKeepApprovalAndSuppressionSeparate(): void {
		$cache_key = array( 'vipgoci_github_prs_implicated', 'fixture', 'fixture', $this->options['commit'], 'unused', array() );
		$prs = vipgoci_cache( $cache_key );
		foreach ( array( $prs, array_reverse( $prs ) ) as $order ) {
			vipgoci_cache( $cache_key, $order );
			$approved = array();
			vipgoci_ap_nonfunctional_changes( $this->options, $approved );
			$this->assertSame( array( 101 => array( 'shared.php' => 'autoapprove-nonfunctional-changes' ) ), $approved );

			$issue = array(
				'type' => VIPGOCI_STATS_PHPCS,
				'file_name' => 'shared.php',
				'issue' => array( 'level' => 'ERROR', 'message' => 'A finding' ),
			);
			$results = array(
				'issues' => array( 101 => array( $issue ), 202 => array( $issue ) ),
				'stats' => array( VIPGOCI_STATS_PHPCS => array( 101 => array( 'error' => 1 ), 202 => array( 'error' => 1 ) ) ),
			);
			vipgoci_auto_approval_scan_commit( $this->options, $approved, $results );
			vipgoci_results_approved_files_comments_remove( $this->options, $results, $approved );
			$this->assertSame( array(), $results['issues'][101] );
			$this->assertSame( array( $issue ), $results['issues'][202] );
			$this->assertSame( 0, $results['stats'][VIPGOCI_STATS_PHPCS][101]['error'] );
			$this->assertSame( 1, $results['stats'][VIPGOCI_STATS_PHPCS][202]['error'] );
			$posts = array_values( array_filter(
				$GLOBALS['vipgoci_test_http_requests'],
				static fn( $request ) => 'POST' === substr( $request['request'], 0, 4 )
			) );
			$this->assertSame(
				array( 'POST /repos/fixture/fixture/pulls/101/reviews', 'POST /repos/fixture/fixture/issues/101/labels' ),
				array_column( $posts, 'request' )
			);
			$this->assertSame( 'APPROVE', $posts[0]['body']['event'] );
			$GLOBALS['vipgoci_test_http_requests'] = array();
		}
	}

	/** Intrinsic filetype approval must populate each PR, with no union bucket. */
	public function testFileTypesAndIdenticalBasesApproveEachPr(): void {
		$base = $this->git( 'rev-parse HEAD^' );
		$this->setPrs( array( 101 => $base, 202 => $base ) );
		$this->options['autoapprove-filetypes'] = array( 'php' );
		$approved = array();
		vipgoci_ap_file_types( $this->options, $approved );
		$this->assertSame(
			array( 101 => array( 'shared.php' => 'autoapprove-filetypes' ), 202 => array( 'shared.php' => 'autoapprove-filetypes' ) ),
			$approved
		);
		$approved = array();
		vipgoci_ap_nonfunctional_changes( $this->options, $approved );
		$this->assertSame(
			array( 101 => array( 'shared.php' => 'autoapprove-nonfunctional-changes' ), 202 => array( 'shared.php' => 'autoapprove-nonfunctional-changes' ) ),
			$approved
		);
	}

	/** Removed SVGs are independently approved in each PR's own bucket. */
	public function testSvgApprovalsBelongToEachPr(): void {
		file_put_contents( $this->repo . '/image.svg', '<svg></svg>' );
		$base = $this->commit();
		unlink( $this->repo . '/image.svg' );
		$this->options['commit'] = $this->commit();
		$this->options['svg-file-extensions'] = array( 'svg' );
		$this->setPrs( array( 101 => $base, 202 => $base ) );
		$approved = array();
		vipgoci_ap_svg_files( $this->options, $approved );
		$this->assertSame(
			array( 101 => array( 'image.svg' => 'ap-svg-files' ), 202 => array( 'image.svg' => 'ap-svg-files' ) ),
			$approved
		);
	}

	/** Missing PR state fails closed, and INFO/WPScan findings remain visible. */
	public function testSuppressionPreservesExceptionsAndRejectsFlatState(): void {
		$issues = array(
			array( 'type' => VIPGOCI_STATS_PHPCS, 'file_name' => 'shared.php', 'issue' => array( 'level' => 'ERROR' ) ),
			array( 'type' => VIPGOCI_STATS_PHPCS, 'file_name' => 'shared.php', 'issue' => array( 'level' => 'INFO' ) ),
			array( 'type' => VIPGOCI_STATS_WPSCAN_API, 'file_name' => 'shared.php', 'issue' => array( 'level' => 'ERROR' ) ),
		);
		$results = array(
			'issues' => array( 101 => $issues, 202 => $issues ),
			'stats' => array( VIPGOCI_STATS_PHPCS => array( 101 => array( 'error' => 1, 'info' => 1 ), 202 => array( 'error' => 1, 'info' => 1 ) ) ),
		);
		$original = $results;
		$flat = array( 'shared.php' => 'legacy' );
		vipgoci_auto_approval_scan_commit( $this->options, $flat, $results );
		foreach ( $GLOBALS['vipgoci_test_http_requests'] as $request ) {
			$this->assertStringStartsWith( 'GET ', $request['request'] );
		}
		vipgoci_results_approved_files_comments_remove( $this->options, $results, array( 'shared.php' => 'legacy' ) );
		$this->assertSame( $original, $results );
		vipgoci_results_approved_files_comments_remove( $this->options, $results, array( 101 => array( 'shared.php' => 'approved' ) ) );
		$this->assertSame( array_slice( $issues, 1 ), $results['issues'][101] );
		$this->assertSame( $issues, $results['issues'][202] );
	}
}
