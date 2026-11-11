<?php
/**
 * Verify rebase content proofs against real local Git objects.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Test byte-exact proofs without touching the source checkout. */
#[CoversFunction( 'vipgoci_rebase_tree_matches' )]
#[CoversFunction( 'vipgoci_rebase_with_objects' )]
#[CoversFunction( 'vipgoci_rebase_git' )]
#[CoversFunction( 'vipgoci_rebase_sha' )]
final class RebaseApprovalTreeTest extends TestCase {
	/** Fixture.
	 *
	 * @var array
	 */
	private array $fixture;
	/** Baseline.
	 *
	 * @var array
	 */
	private array $baseline;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../rebase-approval.php';
		require_once __DIR__ . '/helper/RebaseApprovalFixtures.php';
		$this->fixture  = vipgoci_unittests_rebase_repository();
		$this->baseline = array(
			'head' => $this->fixture['reviewed'],
			'base' => $this->fixture['base'],
			'fork' => $this->fixture['base'],
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
	 * Test exact rebase leaves checkout and index untouched.
	 *
	 * @return void
	 */
	public function testExactRebaseLeavesCheckoutAndIndexUntouched(): void {
		$directory = $this->fixture['directory'];
		file_put_contents( $directory . '/file.php', 'uncommitted contents' );
		file_put_contents( $directory . '/untracked', 'keep me' );
		$before = vipgoci_rebase_git( array( '-C', $directory, 'status', '--porcelain' ) );
		$this->assertTrue( $this->treeMatches( $this->fixture['head'] ) );
		$this->assertSame( $before, vipgoci_rebase_git( array( '-C', $directory, 'status', '--porcelain' ) ) );
		$this->assertSame( 'uncommitted contents', file_get_contents( $directory . '/file.php' ) );
	}

	/**
	 * Changed content.
	 *
	 * @return array Test cases.
	 */
	public static function changedContent(): array {
		return array(
			'whitespace changes program output' => array( 'file.php', "<?php\necho 'reviewed ';\n" ),
			'extra file'                        => array( 'extra.php', '<?php unexpected();' ),
			'binary content'                    => array( 'binary', "\x00\xff\x01" ),
		);
	}

	/**
	 * Test changed content is not arebase.
	 *
	 * @param string $path Path.
	 * @param string $content Content.
	 * @return void
	 */
	#[DataProvider( 'changedContent' )]
	public function testChangedContentIsNotARebase( string $path, string $content ): void {
		file_put_contents( $this->fixture['directory'] . '/' . $path, $content );
		$this->assertFalse( $this->treeMatches( vipgoci_unittests_rebase_commit( $this->fixture['directory'] ) ) );
	}

	/**
	 * Test modes and symlinks are part of the proof.
	 *
	 * @return void
	 */
	public function testModesAndSymlinksArePartOfTheProof(): void {
		$directory = $this->fixture['directory'];
		chmod( $directory . '/file.php', 0755 );
		$this->assertFalse( $this->treeMatches( vipgoci_unittests_rebase_commit( $directory ) ) );
		unlink( $directory . '/file.php' );
		symlink( 'upstream.txt', $directory . '/file.php' );
		$this->assertFalse( $this->treeMatches( vipgoci_unittests_rebase_commit( $directory ) ) );
	}

	/**
	 * Test conflicting upstream change refuses approval.
	 *
	 * @return void
	 */
	public function testConflictingUpstreamChangeRefusesApproval(): void {
		$directory = $this->fixture['directory'];
		vipgoci_rebase_git( array( '-C', $directory, 'checkout', '--quiet', '--detach', $this->fixture['base'] ) );
		file_put_contents( $directory . '/file.php', "<?php\necho 'conflicting upstream';\n" );
		$base = vipgoci_unittests_rebase_commit( $directory );
		file_put_contents( $directory . '/file.php', "<?php\necho 'reviewed';\n" );
		$head = vipgoci_unittests_rebase_commit( $directory );
		$this->assertFalse( vipgoci_rebase_tree_matches( $directory, $this->baseline, $base, $head ) );
	}

	/**
	 * Test missing objects and invalid baseline fail closed.
	 *
	 * @return void
	 */
	public function testMissingObjectsAndInvalidBaselineFailClosed(): void {
		$this->assertFalse( $this->treeMatches( str_repeat( 'a', 40 ) ) );
		$this->baseline['fork'] = $this->fixture['reviewed'];
		$this->assertFalse( $this->treeMatches( $this->fixture['head'] ) );
		$this->baseline['head'] = '--help';
		$this->assertFalse( $this->treeMatches( $this->fixture['head'] ) );
	}

	/**
	 * Test rewritten base history fails closed.
	 *
	 * @return void
	 */
	public function testRewrittenBaseHistoryFailsClosed(): void {
		$this->baseline['base'] = $this->fixture['reviewed'];
		$this->assertFalse( $this->treeMatches( $this->fixture['head'] ) );
	}

	/**
	 * Test worktree and user configuration cannot run amerge driver.
	 *
	 * @return void
	 */
	public function testWorktreeAndUserConfigurationCannotRunAMergeDriver(): void {
		$directory = $this->fixture['directory'];
		file_put_contents( $directory . '/.gitattributes', '* merge=external' );
		$marker = $directory . '/driver-executed';
		vipgoci_rebase_git( array( '-C', $directory, 'config', 'merge.external.driver', 'touch ' . escapeshellarg( $marker ) ) );
		$this->assertTrue( $this->treeMatches( $this->fixture['head'] ) );
		$this->assertFileDoesNotExist( $marker );
	}

	/**
	 * Tree matches.
	 *
	 * @param string $head Head.
	 * @return bool Result.
	 */
	private function treeMatches( string $head ): bool {
		return vipgoci_rebase_tree_matches( $this->fixture['directory'], $this->baseline, $this->fixture['upstream'], $head );
	}
}
