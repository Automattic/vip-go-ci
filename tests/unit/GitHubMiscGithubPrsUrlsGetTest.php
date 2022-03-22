<?php
/**
 * Test vipgoci_github_prs_urls_get().
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
final class GitHubMiscGithubPrsUrlsGetTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../github-misc.php';
		require_once __DIR__ . '/../../defines.php';
	}

	/**
	 * Create and return mock pull requests.
	 *
	 * @return array Array of pull requests.
	 */
	private function createMockPullRequests() :array {
		$pull_requests                          = array();
		$pull_requests[0]                       = new \stdClass();
		$pull_requests[0]->head                 = new \stdClass();
		$pull_requests[0]->head->repo           = new \stdClass();
		$pull_requests[0]->head->repo->html_url = 'https://github.com/test-owner/test-name';
		$pull_requests[0]->number               = 100;

		$pull_requests[1]         = clone $pull_requests[0];
		$pull_requests[1]->number = 200;

		$pull_requests[2]         = clone $pull_requests[0];
		$pull_requests[2]->number = 300;

		return $pull_requests;
	}

	/**
	 * Test if function returns expected URLs to
	 * pull requests.
	 *
	 * @covers ::vipgoci_github_prs_urls_get
	 */
	public function testPrsUrls(): void {
		$pull_requests = $this->createMockPullRequests();

		$prs_urls = vipgoci_github_prs_urls_get(
			$pull_requests,
			' -- '
		);

		$this->assertSame(
			'https://github.com/test-owner/test-name/pull/100 --' .
				' https://github.com/test-owner/test-name/pull/200 --' .
				' https://github.com/test-owner/test-name/pull/300',
			$prs_urls
		);
	}
}
