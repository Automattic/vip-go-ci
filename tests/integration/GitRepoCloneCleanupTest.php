<?php
/**
 * Check that integration clones release their owned temporary paths.
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use PHPUnit\Framework\AssertionFailedError;

final class GitRepoCloneCleanupTest extends TestCase {
	/**
	 * @return void
	 */
	public function testCloneHelperReleasesOnlyItsAllocatedPath(): void {
		foreach ( array( 'success', 'clone_fail', 'checkout_fail' ) as $mode ) {
			$root = tempnam( sys_get_temp_dir(), 'clone-cleanup-test-' );
			$this->assertIsString( $root );
			unlink( $root );
			mkdir( $root );
			$sentinel = $root . '/unrelated';
			mkdir( $sentinel );
			file_put_contents( $sentinel . '/keep', 'keep' );
			$log = $root . '/allocated-path';
			copy( __DIR__ . '/helper/CloneCleanupFakeGit.sh', $root . '/vipgoci-clone-fixture-git' );
			chmod( $root . '/vipgoci-clone-fixture-git', 0700 );

			try {
				$environment = array_merge(
					getenv(),
					array(
						'PATH'                   => $root . PATH_SEPARATOR . getenv( 'PATH' ),
						'TMPDIR'                 => $root,
						'VIPGOCI_FAKE_GIT_MODE'  => $mode,
						'VIPGOCI_FAKE_GIT_LOG'   => $log,
						'VIPGOCI_FAKE_GIT_SENTINEL' => $sentinel,
					)
				);
				$process = proc_open(
					array( PHP_BINARY, __DIR__ . '/helper/CloneCleanupScenario.php' ),
					array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ),
					$pipes,
					__DIR__ . '/../..',
					$environment
				);
				$this->assertIsResource( $process );
				$output = stream_get_contents( $pipes[1] );
				$error = stream_get_contents( $pipes[2] );
				fclose( $pipes[1] );
				fclose( $pipes[2] );
				$this->assertSame( 0, proc_close( $process ), $error );
				$result = json_decode( $output, true );
				$allocated = file_get_contents( $log );

				$this->assertStringStartsWith( $root . '/git-repo-dir-', $allocated );
				$this->assertFileDoesNotExist( $allocated );
				$this->assertFileExists( $sentinel . '/keep' );
				if ( 'success' === $mode ) {
					$this->assertSame( $allocated, $result['result'] );
					$this->assertTrue( $result['removed'] );
				} else {
					$this->assertFalse( $result['result'] );
				}
			} finally {
				$this->removeOwnedFixture( $root );
			}
		}
	}

	/**
	 * @return void
	 */
	public function testNamedTestsReleaseCloneAfterFailureAndIgnoreNoClone(): void {
		require_once __DIR__ . '/IncludesForTestsRepo.php';

		foreach (
			array(
				GitHubMiscPatchChangedLinesTest::class,
				ResultsRemoveExistingGithubCommentsTest::class,
				MainRunInitOptionsRepoOptionsTest::class,
			) as $test_class
		) {
			require_once __DIR__ . '/' . substr( strrchr( $test_class, '\\' ), 1 ) . '.php';
			$test = new $test_class( 'test' );
			$reflection = new ReflectionClass( $test );
			$path = tempnam( sys_get_temp_dir(), 'git-repo-dir-' );
			$this->assertIsString( $path );
			unlink( $path );
			mkdir( $path );
			mkdir( $path . '/.git' );
			$reflection->getProperty( 'owned_repo_path' )->setValue( $test, $path );

			try {
				try {
					$this->fail( 'Failure after clone creation' );
				} catch ( AssertionFailedError $error ) {
					$this->assertSame( 'Failure after clone creation', $error->getMessage() );
				} finally {
					$reflection->getMethod( 'tearDown' )->invoke( $test );
				}
				$this->assertFileDoesNotExist( $path );
				$reflection->getMethod( 'tearDown' )->invoke( $test );
			} finally {
				$this->removeOwnedFixture( $path );
			}
		}
	}

	/** Remove this test's own fixture even when its leak assertion fails. */
	private function removeOwnedFixture( string $path ): void {
		if ( is_link( $path ) ) {
			unlink( $path );
			return;
		}
		if ( ! is_dir( $path ) ) {
			return;
		}
		$entries = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $entries as $entry ) {
			if ( $entry->isDir() && ! $entry->isLink() ) {
				rmdir( $entry->getPathname() );
			} else {
				unlink( $entry->getPathname() );
			}
		}
		rmdir( $path );
	}
}
