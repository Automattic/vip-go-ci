<?php
/**
 * Test vipgoci_github_pr_remove_drafts().
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
final class GitHubMiscGitHubPrRemoveDraftsTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../github-misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_github_pr_remove_drafts
	 *
	 * @return void
	 */
	public function testRemoveDraftPrs() :void {
		$prs_array = array(
			(object) array(
				'url'     => 'https://myapi.mydomain.is',
				'id'      => 123,
				'node_id' => 'testing',
				'state'   => 'open',
				'draft'   => true,
			),

			(object) array(
				'url'     => 'https://myapi2.mydomain.is',
				'id'      => 999,
				'node_id' => 'testing2',
				'state'   => 'open',
				'draft'   => false,
			),
		);

		$prs_array = vipgoci_github_pr_remove_drafts(
			$prs_array
		);

		if ( isset( $prs_array[1] ) ) {
			$prs_array[1] = (array) $prs_array[1];
		}

		$this->assertSame(
			array(
				1 => array(
					'url'     => 'https://myapi2.mydomain.is',
					'id'      => 999,
					'node_id' => 'testing2',
					'state'   => 'open',
					'draft'   => false,
				),
			),
			$prs_array
		);
	}
}
