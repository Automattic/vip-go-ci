<?php
/**
 * Skip report-only Git work when PHPCS has no findings to attribute.
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
	 * Restore any caller-provided fixture log destination after the test.
	 *
	 * @var string|false
	 */
	private string|false $original_phpcs_log;

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
		foreach ( array( 'defines', 'cache', 'log', 'misc', 'statistics', 'git-repo', 'github-api', 'github-misc', 'phpcs-scan', 'svg-scan', 'results', 'reports', 'skip-file', 'file-validation', 'output-security', 'other-web-services' ) as $module ) {
			require_once __DIR__ . '/../../' . $module . '.php';
		}
		require_once __DIR__ . '/helper/GitHubRequestGuards.php';
		$GLOBALS['vipgoci_debug_level']         = -1;
		$GLOBALS['vipgoci_test_http_requests']  = array();
		$GLOBALS['vipgoci_test_http_responses'] = array();
		$this->original_path                    = getenv( 'PATH' );
		$this->original_phpcs_log               = getenv( 'VIPGOCI_TEST_PHPCS_INVOCATIONS' );
		$this->directory                        = tempnam( sys_get_temp_dir(), 'vipgoci-phpcs-git-' );
		unlink( $this->directory );
		mkdir( $this->directory );
		putenv( 'VIPGOCI_TEST_PHPCS_INVOCATIONS=' . $this->directory . '/phpcs.jsonl' );
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
		putenv( false === $this->original_phpcs_log ? 'VIPGOCI_TEST_PHPCS_INVOCATIONS' : 'VIPGOCI_TEST_PHPCS_INVOCATIONS=' . $this->original_phpcs_log );
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

	/**
	 * Inspect real scanner invocations and ensure all their staged files were removed.
	 *
	 * @return array Recorded invocation arguments.
	 */
	private function phpcsInvocations(): array {
		$calls = array_map(
			static fn( $line ) => json_decode( $line, true, 512, JSON_THROW_ON_ERROR ),
			file( $this->directory . '/phpcs.jsonl', FILE_IGNORE_NEW_LINES )
		);
		foreach ( $calls as $call ) {
			foreach ( $call['files'] as $filename ) {
				$this->assertFileDoesNotExist( $filename );
			}
		}
		return $calls;
	}

	/** @return array Inline annotations requiring process isolation. */
	public static function inlineSettings(): array {
		return array(
			array( 'phpcs:set Generic.Files.LineLength lineLimit 1000' ),
			array( '@PHPCS:SET Generic.Files.LineLength lineLimit 1000' ),
			array( '@codingStandardsChangeSetting Generic.Files.LineLength lineLimit 1000' ),
			array( 'phpcs:ignoreFile' ),
			array( '@codingStandardsIgnoreFile' ),
		);
	}

	/** Inline settings and potentially omitted files must not affect batch neighbours. */
	#[DataProvider( 'inlineSettings' )]
	public function testInlineSettingsUseIsolatedProcesses( string $directive ): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit(
			array(
				'a.php' => "<?php\n// $directive\n// fixture-error\n",
				'b.php' => "<?php\n// fixture-error\n",
				'c.php' => "<?php\n// fixture-error\n",
			)
		);
		$result     = $this->scan();
		$calls      = $this->phpcsInvocations();
		$this->assertSame( array( 1, 2 ), array_map( static fn( $call ) => count( $call['files'] ), $calls ) );
		$this->assertSame( array( 'a.php', 'b.php', 'c.php' ), array_column( $result['issues'][42], 'file_name' ) );
	}

	/** The production commit scanner must group files, not just expose an unused batch helper. */
	public function testCommitScanUsesBoundedBatches(): void {
		$this->base = $this->commit( array() );
		$files      = array();
		for ( $index = 0; $index < 51; $index++ ) {
			$files[ sprintf( 'file-%02d.php', $index ) ] = "<?php\n// clean\n";
		}
		$this->head = $this->commit( $files );
		$result     = $this->scan();
		$calls      = $this->phpcsInvocations();
		$this->assertSame( array( 25, 25, 1 ), array_map( static fn( $call ) => count( $call['files'] ), $calls ) );
		$this->assertContains( '--parallel=1', $calls[0]['argv'] );
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		$this->assertSame( 51, vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP )['github_pr_files_phpcs_scanned'] );
	}

	/**
	 * Faults that must invalidate an entire batch before individual recovery.
	 *
	 * @return array Scanner failure markers.
	 */
	public static function batchFailures(): array {
		return array(
			'fatal exit'      => array( 'fixture-batch-fatal' ),
			'missing file'    => array( 'fixture-batch-missing' ),
			'invalid entry'   => array( 'fixture-batch-invalid-entry' ),
			'wrong totals'    => array( 'fixture-batch-count-mismatch' ),
			'unexpected file' => array( 'fixture-batch-unknown-file' ),
		);
	}

	/**
	 * Failed/partial batches must retry every file and preserve each finding once.
	 *
	 * @param string $marker Fixture fault to inject only for multi-file execution.
	 */
	#[DataProvider( 'batchFailures' )]
	public function testBatchFailureRetriesFilesIndividually( string $marker ): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit(
			array(
				'a.php' => "<?php\n// $marker\n// fixture-error\n",
				'b.php' => "<?php\n// fixture-error\n",
			)
		);

		list( $result, $output ) = $this->scanWithLogs( 0 );
		$calls                   = $this->phpcsInvocations();
		$this->assertSame( array( 2, 1, 1 ), array_map( static fn( $call ) => count( $call['files'] ), $calls ) );
		$this->assertSame( array( 'a.php', 'b.php' ), array_column( $result['issues'][42], 'file_name' ) );
		$this->assertSame( 2, $result['stats'][42]['error'] );
		$this->assertStringContainsString( 'Retrying PHPCS batch files individually', $output );
		$this->assertStringContainsString( '"files_failed": 0', $output );
		$this->assertSame( 2, vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP )['github_pr_files_phpcs_scanned'] );
	}

	/** A persistent failure must not discard its healthy batch neighbour's finding. */
	public function testBatchRecoveryReportsOnlyPersistentlyFailedFiles(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit(
			array(
				'a.php' => "<?php\n// fixture-invalid-json\n",
				'b.php' => "<?php\n// fixture-error\n",
			)
		);
		$result     = $this->scan();
		$calls      = $this->phpcsInvocations();
		$this->assertSame( array( 2, 1, 1 ), array_map( static fn( $call ) => count( $call['files'] ), $calls ) );
		$this->assertSame( array( 'b.php' ), array_column( $result['issues'][42], 'file_name' ) );
		$posts = array_values( array_filter( $GLOBALS['vipgoci_test_http_requests'], static fn( $request ) => str_starts_with( $request['request'], 'POST ' ) ) );
		$this->assertCount( 1, $posts );
		$this->assertStringContainsString( '* a.php', $posts[0]['body']['body'] );
		$this->assertStringNotContainsString( '* b.php', $posts[0]['body']['body'] );
	}

	/** Validation must exclude large files before invoking PHPCS. */
	public function testBatchesExcludeLargeFiles(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit(
			array(
				'a.php' => "<?php\n// clean\n",
				'b.php' => "<?php\n// clean\n",
				'c.php' => "<?php\n// long\n// long\n// long\n",
			)
		);
		$result     = $this->scan( array( 42 ), array(
'skip-large-files'       => true,
'skip-large-files-limit' => 4
) );
		$calls      = $this->phpcsInvocations();
		$this->assertSame( array( 2 ), array_map( static fn( $call ) => count( $call['files'] ), $calls ) );
		$this->assertSame( array( 'c.php' ), $result['skipped'][42]['issues'][ VIPGOCI_VALIDATION_MAXIMUM_LINES ] );
		$this->assertSame( 2, vipgoci_counter_report( VIPGOCI_COUNTERS_DUMP )['github_pr_files_phpcs_scanned'] );
	}

	/** SVG files retain their separate scanner and internal forbidden-tag checks. */
	public function testBatchingKeepsSvgScanningSeparate(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit(
			array(
				'a.php'    => "<?php\n// fixture-error\n",
				'icon.svg' => "<svg>\n<?php\n</svg>\n",
				'z.php'    => "<?php\n// fixture-error\n",
			)
		);
		$result     = $this->scan(
			array( 42 ),
			array(
				'svg-checks'       => true,
				'svg-scanner-path' => __DIR__ . '/helper/PhpcsScanFixture.php',
				'svg-php-path'     => PHP_BINARY,
			)
		);
		$calls      = $this->phpcsInvocations();
		$this->assertSame( array( 2, 1 ), array_map( static fn( $call ) => count( $call['files'] ), $calls ) );
		$this->assertStringEndsWith( '.php', $calls[0]['files'][0] );
		$this->assertStringEndsWith( '.php', $calls[0]['files'][1] );
		$this->assertStringEndsWith( '.svg', $calls[1]['files'][0] );
		$this->assertSame( array( 'a.php', 'icon.svg', 'z.php' ), array_column( $result['issues'][42], 'file_name' ) );
		$this->assertSame( 'VipgociInternal.SVG.DisallowedTags', $result['issues'][42][1]['issue']['source'] );
	}

	/** Both absolute and slash-trimmed PHPCS report keys must map back to the right file. */
	public function testBatchAcceptsSlashTrimmedReportPaths(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit(
			array(
				'a.php' => "<?php\n// fixture-leading-slash\n// fixture-error\n",
				'b.php' => "<?php\n// fixture-error\n",
			)
		);
		$result     = $this->scan();
		$this->assertCount( 1, $this->phpcsInvocations() );
		$this->assertSame( array( 'a.php', 'b.php' ), array_column( $result['issues'][42], 'file_name' ) );
	}

	/** Every batch path is a separate escaped shell argument. */
	public function testBatchCommandQuotesEachPath(): void {
		$paths = array( $this->directory . "/one ' quoted.php", $this->directory . '/two spaced.php' );
		foreach ( $paths as $path ) {
			file_put_contents( $path, "<?php\n// fixture-error\n" );
		}
		$output = vipgoci_phpcs_do_scan( $paths, __DIR__ . '/helper/PhpcsScanFixture.php', PHP_BINARY, array(), array(), 1, array() );
		$report = json_decode( $output, true, 512, JSON_THROW_ON_ERROR );
		$this->assertSame( $paths, array_keys( $report['files'] ) );
		$this->assertSame( 2, $report['totals']['errors'] );
	}

	/** An empty filename array must not accidentally scan the working directory. */
	public function testEmptyBatchCommandDoesNotRunPhpcs(): void {
		$output = vipgoci_phpcs_do_scan( array(), __DIR__ . '/helper/PhpcsScanFixture.php', PHP_BINARY, array(), array(), 1, array() );
		$this->assertNull( $output );
		$this->assertFileDoesNotExist( $this->directory . '/phpcs.jsonl' );
	}

	/** An omitted file is only clean when both its directive and zero report agree. */
	public function testIntentionalOmissionDoesNotMaskMissingReports(): void {
		$this->base              = $this->commit( array() );
		$this->head              = $this->commit(
			array(
				'ignored.php'    => "<?php\n// phpcs:ignoreFile\n// fixture-empty-report\n",
				'legacy.php'     => "<?php\n// @codingStandardsIgnoreFile\n// fixture-empty-report\n",
				'missing.php'    => "<?php\n// fixture-empty-report\n",
				'broken.php'     => "<?php\n// phpcs:ignoreFile\n// fixture-invalid-json\n",
				'wrong-case.php' => "<?php\n// @CODINGSTANDARDSIGNOREFILE\n// fixture-empty-report\n",
			)
		);
		list( $result, $output ) = $this->scanWithLogs( 0 );
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		$this->assertStringContainsString( '"files_scanned": 2', $output );
		$this->assertStringContainsString( '"files_failed": 3', $output );
		$posts = array_values( array_filter( $GLOBALS['vipgoci_test_http_requests'], static fn( $request ) => str_starts_with( $request['request'], 'POST ' ) ) );
		$this->assertCount( 1, $posts );
		$this->assertStringContainsString( '* missing.php', $posts[0]['body']['body'] );
		$this->assertStringContainsString( '* broken.php', $posts[0]['body']['body'] );
		$this->assertStringContainsString( '* wrong-case.php', $posts[0]['body']['body'] );
		$this->assertStringNotContainsString( '* ignored.php', $posts[0]['body']['body'] );
		$this->assertStringNotContainsString( '* legacy.php', $posts[0]['body']['body'] );
		$this->phpcsInvocations();
	}

	/** @return array Ways a tracked temporary file can already have been removed. */
	public static function missingTempFiles(): array {
		return array(
			'external removal' => array( true ),
			'repeated cleanup' => array( false ),
		);
	}

	/**
	 * Cleanup must silently forget files that are no longer present.
	 *
	 * @param bool $removed_externally Whether another operation removed the file.
	 */
	#[DataProvider( 'missingTempFiles' )]
	public function testTempFileCleanupToleratesMissingFiles( bool $removed_externally ): void {
		$path = $this->directory . '/staged.php';
		file_put_contents( $path, "<?php\n// synthetic source\n" );
		vipgoci_phpcs_temp_file( $path );
		if ( $removed_externally ) {
			unlink( $path );
		} else {
			vipgoci_phpcs_temp_file( $path, true );
		}
		$this->assertFileDoesNotExist( $path );
		$warnings = array();
		set_error_handler(
			static function ( $severity, $message ) use ( &$warnings ) {
				$warnings[] = $message;
				return true;
			}
		);
		try {
			vipgoci_phpcs_temp_file( $path, true );
		} finally {
			restore_error_handler();
		}
		$this->assertSame( array(), $warnings );
	}

	/** exit() bypasses finally; a later staging failure must still remove earlier files. */
	public function testStagingFailureCleansEarlierFilesAtShutdown(): void {
		$this->head = $this->commit( array( 'a.php' => "<?php\n// staged source\n" ) );
		$staging    = $this->directory . '/staging';
		mkdir( $staging );
		exec(
			escapeshellarg( PHP_BINARY ) . ' -d ' . escapeshellarg( 'sys_temp_dir=' . $staging ) . ' ' .
			escapeshellarg( __DIR__ . '/helper/PhpcsBatchStagingFailure.php' ) . ' ' .
			escapeshellarg( $this->repo ) . ' ' . escapeshellarg( $this->head ) . ' 2>&1',
			$output,
			$code
		);
		$this->assertSame( VIPGOCI_EXIT_SYSTEM_PROBLEM, $code, implode( "\n", $output ) );
		$this->assertStringContainsString( 'About to PHPCS-scan file', implode( "\n", $output ) );
		$this->assertStringContainsString( 'Unable to fetch file from repository', implode( "\n", $output ) );
		$this->assertSame( array( '.', '..' ), scandir( $staging ) );
	}

	/**
	 * Capture the real logger without changing scanner or report behavior.
	 *
	 * @param int   $level     Console verbosity.
	 * @param array $overrides Scanner option overrides.
	 * @return array Scanner results and captured output.
	 */
	private function scanWithLogs( int $level, array $overrides = array() ): array {
		$GLOBALS['vipgoci_debug_level'] = $level;
		ob_start();
		try {
			$result = $this->scan( array( 42 ), $overrides );
			return array( $result, ob_get_contents() );
		} finally {
			ob_end_clean();
			$GLOBALS['vipgoci_debug_level'] = -1;
		}
	}

	/**
	 * Supported verbosity levels must retain diagnostics only when requested.
	 *
	 * @return array Levels with expected per-file and raw output visibility.
	 */
	public static function verbosityLevels(): array {
		return array(
			'default' => array( 0, false, false ),
			'verbose' => array( 1, true, false ),
			'debug'   => array( 2, true, true ),
		);
	}

	/**
	 * Routine details must not leak at level zero or disappear at higher levels.
	 *
	 * @param int  $level   Console verbosity.
	 * @param bool $details Whether per-file details should be visible.
	 * @param bool $raw     Whether commands and raw reports should be visible.
	 */
	#[DataProvider( 'verbosityLevels' )]
	public function testRoutineScanLogsRespectVerbosity( int $level, bool $details, bool $raw ): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit( array( 'clean.php' => "<?php\n// clean\n" ) );

		list( $result, $output ) = $this->scanWithLogs( $level, array( 'skip-large-files' => true ) );
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		foreach ( array( 'About to PHPCS-scan file', 'Fetching file-contents from local Git repository', 'Validating number of lines', 'Validated number of lines', 'Cleaning up after scanning of file...', '"all_files_changed_by_prs"', '"files_changed"' ) as $message ) {
			$this->assertSame( $details, str_contains( $output, $message ), $message );
		}
		foreach ( array( 'Running PHPCS now', 'PHPCS returned results', 'file_issues_str' ) as $message ) {
			$this->assertSame( $raw, str_contains( $output, $message ), $message );
		}
		$this->assertStringContainsString( 'PHPCS-scanning complete', $output );
	}

	/**
	 * Summary counts describe outcomes, not just attempted scanner invocations.
	 */
	public function testDefaultLogsSummarizeSuccessfulFailedAndSkippedFiles(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit(
			array(
				'clean.php'   => "<?php\n// clean\n",
				'finding.php' => "<?php\n// fixture-error\n",
				'failed.php'  => "<?php\n// fixture-invalid-json\n",
				'large.php'   => "<?php\n// too long\n// too long\n// too long\n",
			)
		);

		list( $result, $output ) = $this->scanWithLogs(
			0,
			array(
				'skip-large-files'       => true,
				'skip-large-files-limit' => 4,
			)
		);
		$this->assertCount( 1, $result['issues'][42] );
		$this->assertSame( 'finding.php', $result['issues'][42][0]['file_name'] );
		$this->assertStringContainsString( 'Error when running PHPCS', $output );
		$this->assertStringContainsString( 'Invalid scanner output', $output );
		$this->assertStringContainsString( 'Failed parsing output from PHPCS', $output );
		$this->assertStringContainsString( VIPGOCI_SKIPPED_FILES, $output );
		$this->assertSame( 1, preg_match( '/"PHPCS-scanning complete"; (\{.*?\n\})/s', $output, $matches ) );
		$summary = json_decode( $matches[1], true, 512, JSON_THROW_ON_ERROR );
		$this->assertSame( 4, $summary['files_considered'] );
		$this->assertSame( 2, $summary['files_scanned'] );
		$this->assertSame( 1, $summary['files_failed'] );
		$this->assertSame( 1, $summary['files_skipped'] );
		$this->assertGreaterThanOrEqual( 0, $summary['duration_seconds'] );
	}

	/** No changed files must yield a zero summary without a scanner invocation. */
	public function testEmptyScanSummary(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit( array() );

		list( $result, $output ) = $this->scanWithLogs( 0 );
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		$this->assertSame( 1, preg_match( '/"PHPCS-scanning complete"; (\{.*?\n\})/s', $output, $matches ) );
		$summary = json_decode( $matches[1], true, 512, JSON_THROW_ON_ERROR );
		foreach ( array( 'files_considered', 'files_scanned', 'files_failed', 'files_skipped' ) as $key ) {
			$this->assertSame( 0, $summary[ $key ] );
		}
		$this->assertGreaterThanOrEqual( 0, $summary['duration_seconds'] );
		$this->assertStringNotContainsString( 'Running PHPCS now', $output );
	}

	/** A subprocess failure remains visible even when routine logs are hidden. */
	public function testFailedCommandRemainsVisibleAtDefaultLevel(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit( array( 'failed.php' => "<?php\n// clean\n" ) );

		list( $result, $output ) = $this->scanWithLogs( 0, array( 'phpcs-path' => $this->directory . '/missing-scanner.php' ) );
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		$this->assertStringContainsString( 'Error when running PHPCS', $output );
		$this->assertStringContainsString( 'Failed parsing output from PHPCS', $output );
		$this->assertStringContainsString( 'failed.php', $output );
		$this->assertStringContainsString( '"files_failed": 1', $output );
	}

	/** Valid JSON for a different file is a failure and must retain its diagnostics. */
	public function testMissingReportFileKeepsFailureDiagnostics(): void {
		$this->base = $this->commit( array() );
		$this->head = $this->commit( array( 'wrong-path.php' => "<?php\n// fixture-missing-file\n// fixture-error\n" ) );

		list( $result, $output ) = $this->scanWithLogs( 0 );
		$this->assertSame( array( 42 => array() ), $result['issues'] );
		$this->assertStringContainsString( '"files_failed": 1', $output );
		$this->assertSame( 1, preg_match( '/"Error when running PHPCS"; (\{.*?\n\})/s', $output, $matches ) );
		$failure = json_decode( $matches[1], true, 512, JSON_THROW_ON_ERROR );
		$this->assertArrayHasKey( 'filename', $failure );
		$this->assertSame( 'wrong-path.php', $failure['filename'] );
		$this->assertArrayHasKey( 'file_issues_str', $failure );
		$report = json_decode( $failure['file_issues_str'], true, 512, JSON_THROW_ON_ERROR );
		$this->assertArrayHasKey( '/unexpected.php', $report['files'] );
		$this->assertSame( 'Fixture finding.', $report['files']['/unexpected.php']['messages'][0]['message'] );
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
