<?php
/**
 * Test vipgoci_gitrepo_commit_id_validate() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class GitRepoCommitIdVerifyTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../git-repo.php';
	}

	/**
	 * Ensure a few valid commit IDs are validated correctly.
	 *
	 * @covers ::vipgoci_gitrepo_commit_id_validate
	 *
	 * @return void
	 */
	public function testValidCommitId(): void {
		$this->assertTrue(
			vipgoci_gitrepo_commit_id_validate(
				'8a855ba648d74d732ae15f9030ab1b1f395f1aee'
			),
			'Unable to verify valid git commit ID'
		);

		$this->assertTrue(
			vipgoci_gitrepo_commit_id_validate(
				'2e693aec0ff7e99d603c261f56090d88c1c563c7'
			),
			'Unable to verify valid git commit ID'
		);

		$this->assertTrue(
			vipgoci_gitrepo_commit_id_validate(
				'387ce743d36618fcf33d5695fb0645e766317341'
			),
			'Unable to verify valid git commit ID'
		);
	}

	/**
	 * Ensure that invalid commits are not validated.
	 *
	 * @covers ::vipgoci_gitrepo_commit_id_validate
	 *
	 * @return void
	 */
	public function testInvalidCommitId(): void {
		$this->assertFalse(
			vipgoci_gitrepo_commit_id_validate(
				'387ce743d36618fcf33d5695fb0645e7663' // Too short.
			),
			'Commit ID validated when it should not have'
		);

		$this->assertFalse(
			vipgoci_gitrepo_commit_id_validate(
				'XYZce743d36618fcf33d5695fb0645e766317341' // Invalid characters.
			),
			'Commit ID validated when it should not have'
		);

		$this->assertFalse(
			vipgoci_gitrepo_commit_id_validate(
				'XYZ66317341' // Invalid characters and too short.
			),
			'Commit ID validated when it should not have'
		);
	}
}
