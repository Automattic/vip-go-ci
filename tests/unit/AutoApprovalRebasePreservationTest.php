<?php
/**
 * Preserve verified rebase approvals while enforcing ordinary policy on other PRs.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/** Compare real approval-policy behavior with and without the verified PR set. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_auto_approval_scan_commit' )]
final class AutoApprovalRebasePreservationTest extends TestCase {
	/** Load the production file-policy coordinator with intercepted dependencies. */
	protected function setUp(): void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../auto-approval.php';
		require_once __DIR__ . '/helper/RuntimeMeasure.php';
		require_once __DIR__ . '/helper/AutoApprovalRebasePreservation.php';
		$GLOBALS['rebase_policy_dismissals'] = array();
	}

	/** Ordinary policy must preserve only the PR authorized by the rebase proof. */
	public function testPreservesOnlyTheVerifiedPr(): void {
		$this->scan( array( 7 ) );
		$this->assertSame( array( 8 ), $GLOBALS['rebase_policy_dismissals'] );
	}

	/** Without a verified rebase, both existing approvals remain subject to policy. */
	public function testNormalPolicyIsUnchangedWithoutVerifiedRebases(): void {
		$this->scan( array() );
		$this->assertSame( array( 7, 8 ), $GLOBALS['rebase_policy_dismissals'] );
	}

	/**
	 * Run the real policy against the two changed-file fixtures.
	 *
	 * @param array $preserved PRs authorized by the rebase verifier.
	 * @return void
	 */
	private function scan( array $preserved ): void {
		$options        = array(
			'repo-owner'            => 'owner',
			'repo-name'             => 'repo',
			'commit'                => 'commit',
			'token'                 => 'test-token',
			'autoapprove'           => true,
			'autoapprove-filetypes' => array( 'css' ),
			'autoapprove-label'     => 'auto-approved',
			'wpscan-api'            => false,
		);
		$approved_files = array();
		$results        = array();
		vipgoci_auto_approval_scan_commit( $options, $approved_files, $results, $preserved );
	}
}
