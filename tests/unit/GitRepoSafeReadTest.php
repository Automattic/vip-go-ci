<?php
/**
 * Constrain repository file reads before scanner staging.
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
final class GitRepoSafeReadTest extends TestCase {
	/**
	 * Temporary repository owned by this test.
	 *
	 * @var string
	 */
	private string $repo;

	/** @var string Fixture head commit. */
	private string $head;

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
		$this->write( 'normal.php', "<?php return 1;\n" );
		symlink( 'normal.php', $this->repo . '/internal.php' );
		symlink( '/dev/zero', $this->repo . '/device.php' );
		symlink( 'normal.php', $this->repo . '/internal.svg' );
		$this->head = $this->commit();
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


	/**
	 * Fetch through the production helper.
	 *
	 * @param string $path Repository-relative filename.
	 * @param bool $reject Whether to reject a terminal symlink.
	 * @return false|string File contents or failure.
	 */
	private function fetch( string $path, bool $reject = true ): false|string {
		return vipgoci_gitrepo_fetch_committed_file( 'fixture', 'fixture', 'unused', $this->head, $path, $this->repo, $reject );
	}

	/** Preserve byte contents; symlink reading requires an explicit opt-in. */
	public function testContainedRegularFilesPreserveContentsAndSymlinkPolicy(): void {
		$this->write( 'binary.php', "a\0b\n \n" );
		$this->write( 'empty.php', '' );
		$this->assertSame( "a\0b\n \n", $this->fetch( 'binary.php' ) );
		$this->assertSame( '', $this->fetch( 'empty.php' ) );
		$this->assertSame( "<?php return 1;\n", $this->fetch( 'normal.php' ) );
		$this->assertFalse( $this->fetch( 'internal.php' ) );
		$this->assertSame( "<?php return 1;\n", $this->fetch( 'internal.php', false ) );
		mkdir( $this->repo . '/nested' );
		symlink( '../normal.php', $this->repo . '/nested/relative.php' );
		symlink( 'nested', $this->repo . '/parent' );
		$this->assertFalse( $this->fetch( 'parent/relative.php' ) );
		$this->assertSame( "<?php return 1;\n", $this->fetch( 'parent/relative.php', false ) );
	}

	/** External regular files and parent-directory symlinks cannot escape. */
	public function testExternalPathsAndPrefixSiblingsAreRejected(): void {
		$sibling = $this->repo . '-sibling';
		mkdir( $sibling );
		file_put_contents( $sibling . '/host.php', 'host contents' );
		symlink( $sibling . '/host.php', $this->repo . '/external.php' );
		symlink( $sibling, $this->repo . '/external-dir' );
		try {
			$this->assertFalse( $this->fetch( 'external.php' ) );
			$this->assertFalse( $this->fetch( 'external.php', false ) );
			$this->assertFalse( $this->fetch( 'external-dir/host.php' ) );
			$this->assertFalse( $this->fetch( '../' . basename( $sibling ) . '/host.php' ) );
		} finally {
			unlink( $sibling . '/host.php' );
			rmdir( $sibling );
		}
		symlink( 'missing.php', $this->repo . '/broken.php' );
		symlink( 'cycle.php', $this->repo . '/cycle.php' );
		$this->assertFalse( $this->fetch( 'broken.php' ) );
		$this->assertFalse( $this->fetch( 'cycle.php' ) );
		$this->assertFalse( $this->fetch( '.' ) );
	}

	/** Canonical repository aliases remain usable. */
	public function testRepositoryRootAliasIsAccepted(): void {
		$alias = $this->repo . '-alias';
		symlink( $this->repo, $alias );
		try {
			$this->assertSame(
				"<?php return 1;\n",
				vipgoci_gitrepo_fetch_committed_file( 'fixture', 'fixture', 'unused', $this->head, 'normal.php', $alias )
			);
		} finally {
			unlink( $alias );
		}
	}

	/**
	 * Bound regression probes even if a broken guard opens a device or FIFO.
	 *
	 * @param string $path Filename to read.
	 * @param string $mode Read helper or scanner entry point.
	 * @return array Exit code and output.
	 */
	private function probe( string $path, string $mode = 'read' ): array {
		$command = array(
			PHP_BINARY, '-d', 'memory_limit=32M', __DIR__ . '/helper/GitRepoSafeRead.php',
			$this->repo, $this->head, $path, $mode,
		);
		$process = proc_open( $command, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
		$this->assertIsResource( $process );
		fclose( $pipes[0] );
		stream_set_blocking( $pipes[1], false );
		stream_set_blocking( $pipes[2], false );
		$deadline = microtime( true ) + 5;
		do {
			$status = proc_get_status( $process );
			if ( ! $status['running'] ) {
				break;
			}
			usleep( 10000 );
		} while ( microtime( true ) < $deadline );
		if ( $status['running'] ) {
			proc_terminate( $process, 9 );
		}
		$output = stream_get_contents( $pipes[1] ) . stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		$code = proc_close( $process );
		$this->assertFalse( $status['running'], 'Unsafe read timed out: ' . $path . ' ' . $mode );
		return array( $status['exitcode'] >= 0 ? $status['exitcode'] : $code, $output );
	}

	/** Unsafe streams fail before staging in each scanner and never turn clean. */
	public function testDevicesAndFifosFailBeforeScannerExecution(): void {
		if ( ! function_exists( 'posix_mkfifo' ) ) {
			$this->markTestSkipped( 'POSIX FIFO support is required.' );
		}
		$this->assertTrue( posix_mkfifo( $this->repo . '/fifo.php', 0600 ) );
		foreach ( array( 'device.php', 'fifo.php' ) as $path ) {
			$this->assertSame( array( 0, 'false' ), $this->probe( $path ) );
		}
		foreach ( array( 'phpcs', 'lint', 'svg' ) as $mode ) {
			foreach ( array( 'device.php', 'svg' === $mode ? 'internal.svg' : 'internal.php' ) as $path ) {
				list( $code, $output ) = $this->probe( $path, $mode );
				$this->assertSame( VIPGOCI_EXIT_SYSTEM_PROBLEM, $code, $output );
			}
		}
	}
	/** Size checks reject finite single-line and multiline files before allocation. */
	public function testByteLimitAppliesBeforeStagingIndependentlyOfLineCount(): void {
		foreach ( array( 'single' => str_repeat( 'x', 65536 ), 'multi' => str_repeat( "x\n", 32768 ) ) as $kind => $chunk ) {
			$path = $kind . '.php';
			$handle = fopen( $this->repo . '/' . $path, 'wb' );
			for ( $written = 0; $written < VIPGOCI_GIT_REPO_MAX_FILE_BYTES; $written += strlen( $chunk ) ) {
				fwrite( $handle, $chunk );
			}
			fwrite( $handle, 'x' );
			fclose( $handle );
			$this->assertSame( array( 0, 'false' ), $this->probe( $path ) );
			foreach ( array( 'phpcs', 'lint', 'svg' ) as $mode ) {
				list( $code, $output ) = $this->probe( $path, $mode );
				$this->assertSame( VIPGOCI_EXIT_SYSTEM_PROBLEM, $code, $output );
			}
		}
		$handle = fopen( $this->repo . '/boundary.php', 'wb' );
		ftruncate( $handle, VIPGOCI_GIT_REPO_MAX_FILE_BYTES );
		fclose( $handle );
		$this->assertSame( VIPGOCI_GIT_REPO_MAX_FILE_BYTES, strlen( $this->fetch( 'boundary.php' ) ) );
		$this->assertSame( "<?php return 1;\n", vipgoci_gitrepo_get_file_at_commit( $this->head, 'normal.php', $this->repo, $this->head ) );
	}

	/** Recursive discovery cannot follow external links or ancestor cycles. */
	public function testDiscoveryDoesNotTraverseDirectorySymlinks(): void {
		mkdir( $this->repo . '/nested' );
		$this->write( 'nested/child.php', '<?php return 2;' );
		symlink( '..', $this->repo . '/nested/cycle' );
		$sibling = $this->repo . '-sibling';
		mkdir( $sibling );
		file_put_contents( $sibling . '/host.php', 'external' );
		symlink( $sibling, $this->repo . '/external-dir' );
		try {
			list( $code, $output ) = $this->probe( '.', 'discovery' );
			$this->assertSame( 0, $code, $output );
			$this->assertSame(
				array( 'device.php', 'internal.php', 'internal.svg', 'nested/child.php', 'normal.php' ),
				json_decode( $output, true )
			);
		} finally {
			unlink( $sibling . '/host.php' );
			rmdir( $sibling );
		}
	}

}
