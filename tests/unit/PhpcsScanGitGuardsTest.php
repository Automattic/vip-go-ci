<?php
/**
 * Skip report-only Git work when PHPCS has no findings to attribute.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/** Exercise real scanning, Git, filtering and reporting with offline boundaries. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_phpcs_scan_commit' )]
final class PhpcsScanGitGuardsTest extends TestCase {
	/**
	 * Temporary fixture directory owned by this test.
	 *
	 * @var string
	 */
	private string $directory;

	/**
	 * Local Git repository inside the fixture.
	 *
	 * @var string
	 */
	private string $repo;

	/**
	 * PATH restored after the test.
	 *
	 * @var string|false
	 */
	private string|false $original_path;

	/**
	 * Base commit used for the pull request fixture.
	 *
	 * @var string
	 */
	private string $base;

	/**
	 * Head commit used for the pull request fixture.
	 *
	 * @var string
	 */
	private string $head;

	/** Load production code and create a local repository without user hooks. */
	protected function setUp(): void {
		foreach ( array( 'defines', 'cache', 'log', 'misc', 'statistics', 'git-repo', 'github-api', 'github-misc', 'phpcs-scan', 'results', 'reports', 'skip-file', 'file-validation', 'output-security', 'other-web-services' ) as $module ) {
			require_once __DIR__ . '/../../' . $module . '.php';
		}
		require_once __DIR__ . '/helper/GitHubRequestGuards.php';
		$GLOBALS['vipgoci_debug_level']         = -1;
		$GLOBALS['vipgoci_test_http_requests']  = array();
		$GLOBALS['vipgoci_test_http_responses'] = array();
		$this->original_path                    = getenv( 'PATH' );
		$this->directory                        = tempnam( sys_get_temp_dir(), 'vipgoci-phpcs-git-' );
		unlink( $this->directory );
		mkdir( $this->directory );
		$this->repo = $this->directory . '/repo';
		mkdir( $this->repo );
		$this->git( 'init -q' );
		$this->git( 'config diff.renames false' );
		// Keep patch-position expectations independent of global Git settings.
		$this->git( 'config diff.context 3' );
	}

	/** Restore the environment and remove only this test's fixture. */
	protected function tearDown(): void {
		putenv( false === $this->original_path ? 'PATH' : 'PATH=' . $this->original_path );
		$entries = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->directory, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $entries as $entry ) {
			if ( $entry->isDir() && ! $entry->isLink() ) {
				rmdir( $entry->getPathname() );
			} else {
				unlink( $entry->getPathname() );
			}
		}
		rmdir( $this->directory );
	}

	/**
	 * Run fixture setup commands, separate from measured production commands.
	 *
	 * @param string $arguments Trusted test arguments.
	 * @return string Git output.
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

	/**
	 * Commit synthetic files and return the resulting object ID.
	 *
	 * @param array $files Filenames mapped to contents.
	 * @return string Commit ID.
	 */
	private function commit( array $files ): string {
		foreach ( $files as $name => $contents ) {
			file_put_contents( $this->repo . '/' . $name, $contents );
		}
		$this->git( 'add -A' );
		$this->git( 'commit -qm fixture --allow-empty' );
		return $this->git( 'rev-parse HEAD' );
	}

	/**
	 * Scan real files, recording Git subprocesses and intercepting HTTP only.
	 *
	 * @param array $pr_numbers Pull requests sharing this comparison.
	 * @param array $overrides  Scanner option overrides.
	 * @return array Issues, statistics and skipped-file results.
	 */
	private function scan( array $pr_numbers = array( 42 ), array $overrides = array() ): array {
		$prs     = array();
		$issues  = array();
		$stats   = array();
		$skipped = array();
		foreach ( $pr_numbers as $number ) {
			$prs[ $number ]     = (object) array(
				'number' => $number,
				'head'   => (object) array(
					'sha' => $this->head,
					'ref' => 'fixture',
				),
				'base'   => (object) array( 'sha' => $this->base ),
			);
			$issues[ $number ]  = array();
			$stats[ $number ]   = array(
				'error'   => 0,
				'warning' => 0,
				'info'    => 0,
			);
			$skipped[ $number ] = array(
				'issues' => array(),
				'total'  => 0,
			);
			$GLOBALS['vipgoci_test_http_responses'][ 'GET /repos/owner/repo/pulls/' . $number . '/commits' ] = array( array( 'sha' => $this->head ) );
		}
		// Seed PR discovery only; local diff discovery and commit filtering stay real.
		vipgoci_cache( array( 'vipgoci_github_prs_implicated', 'owner', 'repo', $this->head, 'test-token', array() ), $prs );
		$options  = array_merge(
			array(
				'repo-owner'                             => 'owner',
				'repo-name'                              => 'repo',
				'token'                                  => 'test-token',
				'commit'                                 => $this->head,
				'local-git-repo'                         => $this->repo,
				'branches-ignore'                        => array(),
				'skip-draft-prs'                         => false,
				'phpcs'                                  => true,
				'phpcs-path'                             => __DIR__ . '/helper/PhpcsScanFixture.php',
				'phpcs-php-path'                         => PHP_BINARY,
				'phpcs-standard'                         => array(),
				'phpcs-sniffs-exclude'                   => array(),
				'phpcs-severity'                         => 1,
				'phpcs-runtime-set'                      => array(),
				'phpcs-file-extensions'                  => array( 'php' ),
				'phpcs-skip-folders'                     => array(),
				'phpcs-skip-scanning-via-labels-allowed' => false,
				'svg-checks'                             => false,
				'svg-file-extensions'                    => array( 'svg' ),
				'skip-large-files'                       => false,
				'skip-large-files-limit'                 => 100,
			),
			$overrides
		);
		$real_git = trim( (string) shell_exec( 'command -v git' ) );
		$this->assertNotSame( '', $real_git );
		mkdir( $this->directory . '/bin' );
		file_put_contents(
			$this->directory . '/bin/git',
			"#!/bin/sh\nprintf '%s\\n' \"\$*\" >> " . escapeshellarg( $this->directory . '/git.log' ) .
				"\nexec " . escapeshellarg( $real_git ) . ' "$@"' . "\n"
		);
		chmod( $this->directory . '/bin/git', 0755 );
		putenv( 'PATH=' . $this->directory . '/bin:' . $this->original_path );
		vipgoci_phpcs_scan_commit( $options, $issues, $stats, $skipped );
		return array(
			'issues'  => $issues,
			'stats'   => $stats,
			'skipped' => $skipped,
		);
	}

	/**
	 * Return actual Git invocations matching a command fragment.
	 *
	 * @param string $fragment Command fragment.
	 * @return array Matching command lines.
	 */
	private function commands( string $fragment ): array {
		return array_values(
			array_filter(
				file( $this->directory . '/git.log', FILE_IGNORE_NEW_LINES ),
				static fn( $line ) => str_contains( $line, $fragment )
			)
		);
	}

	/** Clean scans must not run blame or populate the full-patch cache. */
	public function testCleanScanSkipsReportOnlyGitWork(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit( array( 'clean.php' => "<?php\n// clean\n" ) );
		$result     = $this->scan();
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		$this->assertSame(
			array(
				42 => array(
					'error'   => 0,
					'warning' => 0,
					'info'    => 0,
				),
			),
			$result['stats']
		);
		$this->assertSame( 1, vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP )['github_pr_files_phpcs_scanned'] );
		$this->assertSame( 3, vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP )['github_pr_lines_phpcs_scanned'] );
		$this->assertSame( array(), $this->commands( ' blame ' ) );
		// Reading and counting the scanned file still validate HEAD; reporting must not.
		$this->assertCount( 2, $this->commands( ' log ' ) );
		$this->assertFalse( vipgoci_cache( array( 'vipgoci_gitrepo_diffs_fetch_unfiltered', $this->repo, $this->base, $this->head ) ) );
	}

	/** Nonempty findings still use blame/patch filtering and reach every relevant PR. */
	public function testMixedScanPreservesFindingsAndCountersAcrossPullRequests(): void {
		$this->base = $this->commit( array( 'finding.php' => "<?php\n// fixture-warning\n// unchanged\n" ) );
		$this->head = $this->commit(
			array(
				'clean.php'   => "<?php\n// clean\n",
				'finding.php' => "<?php\n// fixture-warning\n// unchanged\n// fixture-error\n",
			)
		);
		$result     = $this->scan( array( 42, 43 ) );
		$expected   = array(
			array(
				'type'      => 'phpcs',
				'file_name' => 'finding.php',
				'file_line' => 4,
				'issue'     => array(
					'message'  => 'Fixture finding.',
					'source'   => 'Fixture.Test.Finding',
					'severity' => 5,
					'fixable'  => false,
					'line'     => 4,
					'column'   => 1,
					'level'    => 'ERROR',
				),
			),
		);
		foreach ( array( 42, 43 ) as $number ) {
			$this->assertSame( $expected, $result['issues'][ $number ] );
			$this->assertSame(
				array(
					'error'   => 1,
					'warning' => 0,
					'info'    => 0,
				),
				$result['stats'][ $number ]
			);
		}
		$this->assertSame( 2, vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP )['github_pr_files_phpcs_scanned'] );
		$this->assertSame( 8, vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP )['github_pr_lines_phpcs_scanned'] );
		$this->assertCount( 2, $this->commands( ' blame ' ) );
		foreach ( $this->commands( ' blame ' ) as $command ) {
			$this->assertStringEndsWith( ' finding.php', $command );
		}
		$this->assertCount( 6, $this->commands( ' log ' ) );
	}

	/** Malformed scanner output must still produce a failure notice, not a clean bill. */
	public function testFailedScanStillReportsFailureWithoutGitAttribution(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit( array( 'failed.php' => "<?php\n// fixture-invalid-json\n" ) );
		$result     = $this->scan();
		$posts      = array_values(
			array_filter( $GLOBALS['vipgoci_test_http_requests'], static fn( $request ) => str_starts_with( $request['request'], 'POST ' ) )
		);
		$this->assertCount( 1, $posts );
		$this->assertSame( 'POST /repos/owner/repo/issues/42/comments', $posts[0]['request'] );
		$this->assertStringContainsString( '* failed.php', $posts[0]['body']['body'] );
		$this->assertStringContainsString( VIPGOCI_PHPCS_SCAN_FAILED_MSG_START, $posts[0]['body']['body'] );
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		$this->assertSame( 1, vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP )['github_pr_files_phpcs_scanned'] );
		$this->assertSame( array(), $this->commands( ' blame ' ) );
		$this->assertCount( 2, $this->commands( ' log ' ) );
		$this->assertFalse( vipgoci_cache( array( 'vipgoci_gitrepo_diffs_fetch_unfiltered', $this->repo, $this->base, $this->head ) ) );
	}

	/** The existing large-file skip must retain its separate reporting state. */
	public function testLargeFileSkipPreservesValidationResult(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit( array( 'large.php' => "<?php\n// fixture-error\n// too long\n" ) );
		$result     = $this->scan(
			array( 42 ),
			array(
				'skip-large-files'       => true,
				'skip-large-files-limit' => 2,
			)
		);
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		$this->assertSame(
			array(
				42 => array(
					'issues' => array( VIPGOCI_VALIDATION_MAXIMUM_LINES => array( 'large.php' ) ),
					'total'  => 1,
				),
			),
			$result['skipped']
		);
		$this->assertArrayNotHasKey( 'github_pr_files_phpcs_scanned', vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP ) );
		$this->assertSame( array(), $this->commands( ' blame ' ) );
		$this->assertFalse( vipgoci_cache( array( 'vipgoci_gitrepo_diffs_fetch_unfiltered', $this->repo, $this->base, $this->head ) ) );
	}
}
