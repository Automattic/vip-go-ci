<?php
/**
 * Local Git regressions for diff memory usage and file-discovery semantics.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Use real temporary Git repositories, without network access or customer code.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
final class GitDiffMemoryTest extends TestCase {
	/**
	 * Temporary repository owned by this test.
	 *
	 * @var string
	 */
	private string $repo;

	/** Load production code and initialize an isolated local fixture. */
	protected function setUp(): void {
		foreach ( array( 'defines', 'cache', 'log', 'misc', 'statistics', 'git-repo', 'github-api', 'github-misc' ) as $module ) {
			require_once __DIR__ . '/../../' . $module . '.php';
		}
		$GLOBALS['vipgoci_debug_level'] = -1;
		$this->repo                     = tempnam( sys_get_temp_dir(), 'vipgoci-diff-test-' );
		unlink( $this->repo );
		mkdir( $this->repo );
		$this->git( 'init -q' );
		$this->git( 'config core.fileMode true' );
		$this->git( 'config diff.renames true' );
	}

	/** Remove only the repository created by this test. */
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
	 * Run a fixture command without user hooks or commit signing.
	 *
	 * @param string $arguments Git arguments, constructed only by these tests.
	 * @return string Command output.
	 */
	private function git( string $arguments ): string {
		$output = array();
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
	 * Write synthetic contents into the fixture repository.
	 *
	 * @param string $path     Relative path.
	 * @param string $contents File contents.
	 */
	private function write( string $path, string $contents ): void {
		$path = $this->repo . '/' . $path;
		if ( ! is_dir( dirname( $path ) ) ) {
			mkdir( dirname( $path ), 0700, true );
		}
		file_put_contents( $path, $contents );
	}

	/** Commit the fixture and return its object ID. */
	private function commit(): string {
		$this->git( 'add -A' );
		$this->git( 'commit -qm fixture --allow-empty' );
		return $this->git( 'rev-parse HEAD' );
	}

	/** File discovery must not retain patches or change rename/mode rules. */
	public function testMetadataPreservesFilteringAndDoesNotPopulatePatchCache(): void {
		$this->write( 'removed.php', "removed\n" );
		$this->write( 'mode.php', "mode\n" );
		$this->write( 'rename.php', "rename only\n" );
		$this->write( 'edited.php', str_repeat( "original content\n", 20 ) );
		$base = $this->commit();
		unlink( $this->repo . '/removed.php' );
		chmod( $this->repo . '/mode.php', 0755 );
		rename( $this->repo . '/rename.php', $this->repo . '/renamed.php' );
		rename( $this->repo . '/edited.php', $this->repo . '/renamed-edited.php' );
		$this->write( 'renamed-edited.php', str_repeat( "original content\n", 20 ) . "added\n" );
		$this->write( 'added.php', "new\n" );
		$this->write( 'ignored/file.php', "ignore folder\n" );
		$this->write( 'ignored.txt', "ignore extension\n" );
		$head   = $this->commit();
		$filter = array(
			'file_extensions' => array( 'php' ),
			'skip_folders'    => array( 'ignored' ),
		);
		$result = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $head, false, false, false, $filter, false );
		$this->assertSame(
			array(
				'added.php'          => null,
				'renamed-edited.php' => null,
			),
			$result['files']
		);
		$this->assertSame(
			array(
				'added.php'          => 'added',
				'renamed-edited.php' => 'modified',
			),
			$result['files_status']
		);
		$this->assertSame(
			array(
				'additions' => 2,
				'deletions' => 0,
				'changes'   => 2,
			),
			$result['statistics']
		);
		$this->assertFalse( vipgoci_cache( array( 'vipgoci_gitrepo_diffs_fetch_unfiltered', $this->repo, $base, $head ) ) );
		$included = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $head, true, true, true, $filter, false );
		$this->assertSame( array( 'added.php', 'mode.php', 'removed.php', 'renamed-edited.php', 'renamed.php' ), array_keys( $included['files'] ) );
		$patches = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $head, false, false, false, $filter );
		$this->assertSame( "@@ -0,0 +1 @@\n+new", $patches['files']['added.php'] );
		$this->assertSame( $result['files_status'], $patches['files_status'] );
	}

	/** A large dependency file must not exhaust PHP just to obtain its name. */
	public function testLargeDiffDiscoveryWithin64MiB(): void {
		$this->largeDiffProbe( 'discovery' );
	}

	/** Patch consumers must not allocate a whole-diff array of lines. */
	public function testLargeDiffParsingWithin64MiB(): void {
		$this->largeDiffProbe( 'patches' );
	}

	/** NUL-delimited metadata must preserve whitespace and special path bytes. */
	public function testMetadataPreservesUnusualPathsAndCacheIsolation(): void {
		$base  = $this->commit();
		$paths = array( 'a/space name.php', 'quote".php', "tab\tname.php", "new\nline.php", 'unicode-é.php' );
		foreach ( $paths as $path ) {
			$this->write( $path, "content\n" );
		}
		$head   = $this->commit();
		$result = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $head, false, false, false, null, false );
		$this->assertEqualsCanonicalizing( $paths, array_keys( $result['files'] ) );
		$this->assertSame( array_fill_keys( array_keys( $result['files'] ), null ), $result['files'] );
		$empty = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $head, $head, false, false, false, null, false );
		$this->assertSame( array(), $empty['files'] );
		$this->assertSame( $result, vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $head, false, false, false, null, false ) );
	}

	/** Keep the existing treatment of binary, empty and mode-only changes. */
	public function testMetadataPreservesZeroLineChangeAndRenamedModeRules(): void {
		$this->write( 'deleted.bin', "binary\0data" );
		$this->write( 'modified.bin', "binary\0data" );
		$this->write( 'deleted-empty.php', '' );
		$this->write( 'rename-mode.php', "rename and chmod\n" );
		$base = $this->commit();
		unlink( $this->repo . '/deleted.bin' );
		unlink( $this->repo . '/deleted-empty.php' );
		$this->write( 'added.bin', "new\0binary" );
		$this->write( 'modified.bin', "changed\0binary" );
		$this->write( 'added-empty.php', '' );
		rename( $this->repo . '/rename-mode.php', $this->repo . '/renamed-mode.php' );
		chmod( $this->repo . '/renamed-mode.php', 0755 );
		$head = $this->commit();
		foreach ( array( false, true ) as $include_patches ) {
			$result = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $head, true, true, false, null, $include_patches );
			// Git recognizes the identical empty files as a pure rename.
			$this->assertSame( array( 'added-empty.php' => $include_patches ? '' : null ), $result['files'] );
			$result = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $head, true, true, true, null, $include_patches );
			$this->assertEquals(
				array(
					'added.bin'        => 'modified',
					'added-empty.php'  => 'renamed',
					'deleted.bin'      => 'modified',
					'modified.bin'     => 'modified',
					'renamed-mode.php' => 'modified',
				),
				$result['files_status']
			);
		}
	}

	/** Missing local commits must keep the established GitHub fallback. */
	public function testMetadataFailureFallsBackWithoutReturningPatches(): void {
		$base    = $this->commit();
		$missing = str_repeat( '1', 40 );
		vipgoci_cache(
			array( 'vipgoci_github_diffs_fetch_unfiltered', 'fixture', 'fixture', $base, $missing ),
			array(
				'files' => array(
					array(
						'filename'  => 'fallback.php',
						'status'    => 'added',
						'patch'     => "@@ -0,0 +1 @@\n+new",
						'additions' => 1,
						'deletions' => 0,
						'changes'   => 1,
					),
				),
			)
		);
		$result = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $missing, false, false, false, null, false );
		$this->assertSame( array( 'fallback.php' => null ), $result['files'] );
		$this->assertSame( VIPGOCI_GIT_DIFF_DATA_SOURCE_GITHUB_API, $result['data_source'] );
		$patches = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $missing );
		$this->assertSame( "@@ -0,0 +1 @@\n+new", $patches['files']['fallback.php'] );
	}

	/** Distinct PR bases and previously skipped files must keep their mappings. */
	public function testDiscoveryKeepsPrMappingsAndSkips(): void {
		$base = $this->commit();
		$this->write( 'first.php', "first\n" );
		$second_base = $this->commit();
		$this->write( 'second.php', "second\n" );
		$head = $this->commit();
		vipgoci_cache(
			array( 'vipgoci_github_prs_implicated', 'fixture', 'fixture', $head, 'unused', array() ),
			array(
				(object) array(
					'number' => 1,
					'base'   => (object) array( 'sha' => $base ),
				),
				(object) array(
					'number' => 2,
					'base'   => (object) array( 'sha' => $second_base ),
				),
			)
		);
		$options = array(
			'local-git-repo'  => $this->repo,
			'repo-owner'      => 'fixture',
			'repo-name'       => 'fixture',
			'token'           => 'unused',
			'commit'          => $head,
			'branches-ignore' => array(),
			'skip-draft-prs'  => false,
		);
		$skipped = array( 1 => array( 'issues' => array( VIPGOCI_VALIDATION_MAXIMUM_LINES => array( 'second.php' ) ) ) );
		$this->assertSame(
			array(
				'all' => array( 'first.php', 'second.php' ),
				1     => array( 'first.php' ),
				2     => array( 'second.php' ),
			),
			vipgoci_github_files_affected_by_commit( $options, $head, $skipped )
		);
		$skipped[2] = $skipped[1];
		$this->assertSame(
			array(
				'all' => array( 'first.php' ),
				1     => array( 'first.php' ),
				2     => array(),
			),
			vipgoci_github_files_affected_by_commit( $options, $head, $skipped )
		);
	}

	/** Warnings from successful commands must not force a GitHub fallback. */
	public function testMetadataKeepsSuccessfulDiffsWithMergeBaseWarningsLocal(): void {
		$this->write( 'file.php', "base\n" );
		$a  = $this->commit();
		$ta = $this->git( 'rev-parse HEAD^{tree}' );
		$this->write( 'file.php', "changed\n" );
		$this->git( 'add -A' );
		$tb = $this->git( 'write-tree' );
		$b  = $this->git( "commit-tree $ta -p $a -m B" );
		$c  = $this->git( "commit-tree $tb -p $a -m C" );
		$d  = $this->git( "commit-tree $ta -p $b -p $c -m D" );
		$this->write( 'file.php', "final\n" );
		$this->git( 'add -A' );
		$te     = $this->git( 'write-tree' );
		$e      = $this->git( "commit-tree $te -p $c -p $b -m E" );
		$result = vipgoci_gitrepo_diffs_fetch_metadata( $this->repo, $d, $e );
		$this->assertNotNull( $result );
		$this->assertSame( array( 'file.php' ), array_keys( $result['files'] ) );
		$this->assertSame( 2, $result['files']['file.php']['changes'] );
		$filtered = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $d, $e, false, false, false, null, false );
		$this->assertSame( VIPGOCI_GIT_DIFF_DATA_SOURCE_GIT_REPO, $filtered['data_source'] );
	}

	/** Numstat ignores textconv; configured drivers must keep legacy scanning. */
	public function testMetadataPreservesTextconvChangesUsingLocalFallback(): void {
		$this->git( 'config diff.fixture.textconv cat' );
		$this->write( '.gitattributes', "*.php diff=fixture\n" );
		$this->write( 'file.php', "old\0binary\n" );
		$base = $this->commit();
		$this->write( 'file.php', "new\0binary\n" );
		$head   = $this->commit();
		$result = vipgoci_git_diffs_fetch( $this->repo, 'fixture', 'fixture', 'unused', $base, $head, false, false, false, null, false );
		$this->assertSame( array( 'file.php' => null ), $result['files'] );
		$this->assertSame(
			array(
				'additions' => 1,
				'deletions' => 1,
				'changes'   => 2,
			),
			$result['statistics']
		);
		$this->assertSame( VIPGOCI_GIT_DIFF_DATA_SOURCE_GIT_REPO, $result['data_source'] );
	}

	/** Streaming must preserve exact hunk contents, counts and missing newlines. */
	public function testStreamingPreservesPatchBytes(): void {
		$this->write( 'file.php', "one\r\ntwo\nthree" );
		$base = $this->commit();
		$this->write( 'file.php', "one\r\nnew two\nthree\n" );
		$head   = $this->commit();
		$result = vipgoci_gitrepo_diffs_fetch_unfiltered( $this->repo, $base, $head );
		$this->assertSame( "@@ -1,3 +1,3 @@\n one\r\n-two\n-three\n\\ No newline at end of file\n+new two\n+three", $result['files']['file.php']['patch'] );
		$this->assertSame(
			array(
				'additions' => 2,
				'deletions' => 2,
				'changes'   => 4,
			),
			$result['statistics']
		);
		$this->assertSame( $result, vipgoci_gitrepo_diffs_fetch_unfiltered( $this->repo, $base, $head ) );
		$this->assertNull( vipgoci_gitrepo_diffs_fetch_unfiltered( $this->repo, $base, str_repeat( '1', 40 ) ) );
	}

	/**
	 * Assert a million-line diff succeeds in a memory-limited process.
	 *
	 * @param string $mode Whether to discover files or collect patches.
	 */
	private function largeDiffProbe( string $mode ): void {
		$base = $this->commit();
		$this->write( 'vendor/dependency.php', '' );
		$stream = fopen( $this->repo . '/vendor/dependency.php', 'wb' );
		$chunk  = str_repeat( "// dependency line\n", 1000 );
		for ( $i = 0; $i < 1000; $i++ ) {
			fwrite( $stream, $chunk );
		}
		fclose( $stream );
		$head   = $this->commit();
		$output = array();
		exec(
			escapeshellarg( PHP_BINARY ) . ' -d memory_limit=64M ' .
				escapeshellarg( __DIR__ . '/helper/GitDiffMemoryProbe.php' ) . ' ' .
				escapeshellarg( $this->repo ) . ' ' . escapeshellarg( $base ) . ' ' . escapeshellarg( $head ) . ' ' . escapeshellarg( $mode ) . ' 2>&1',
			$output,
			$code
		);
		$this->assertSame( 0, $code, implode( "\n", $output ) );
		$result = json_decode( implode( "\n", $output ), true, 512, JSON_THROW_ON_ERROR );
		$this->assertSame( array( 'vendor/dependency.php' ), $result['files'] );
		$this->assertLessThan( 64 * 1024 * 1024, $result['peak'] );
		if ( 'patches' === $mode ) {
			$this->assertSame( 1000000, $result['changes'] );
		}
	}
}
