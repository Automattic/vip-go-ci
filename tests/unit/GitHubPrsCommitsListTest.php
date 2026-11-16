<?php
/**
 * Test vipgoci_github_prs_commits_list with offline HTTP responses.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Vipgoci\Tests\Common\HttpApiMock;
use Vipgoci\Tests\Common\MockedApiTestCase;

require_once __DIR__ . '/../common/MockedApiTestCase.php';

/**
 * Exercise the production client without credentials or network access.
 */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_github_prs_commits_list' )]
final class GitHubPrsCommitsListTest extends MockedApiTestCase {
	/**
	 * Load the GitHub client with the mocked transport.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../../github-api.php';
	}

	/**
	 * Fetch all pages and reuse the complete cached list.
	 *
	 * @return void
	 */
	public function testPaginationAndCache(): void {
		$page = array();
		for ( $i = 1; $i <= 100; $i++ ) {
			$page[] = array( 'sha' => 'commit-' . $i );
		}
		$url = 'https://api.github.com/repos/owner/repo/pulls/42/commits';
		HttpApiMock::expect( 'GET', $url . '?page=1&per_page=100', 'test-token', json_encode( $page ) );
		HttpApiMock::expect( 'GET', $url . '?page=2&per_page=100', 'test-token', '[{"sha":"last-commit"}]' );

		$expected   = array_column( $page, 'sha' );
		$expected[] = 'last-commit';
		$this->assertSame( $expected, vipgoci_github_prs_commits_list( 'owner', 'repo', 42, 'test-token' ) );
		$this->assertSame( $expected, vipgoci_github_prs_commits_list( 'owner', 'repo', 42, 'test-token' ) );
	}

	/**
	 * An empty PR must stop after the first page and cache the empty result.
	 *
	 * @return void
	 */
	public function testEmptyCommitList(): void {
		HttpApiMock::expect( 'GET', 'https://api.github.com/repos/owner/repo/pulls/42/commits?page=1&per_page=100', '', '[]' );
		$this->assertSame( array(), vipgoci_github_prs_commits_list( 'owner', 'repo', 42, '' ) );
		$this->assertSame( array(), vipgoci_github_prs_commits_list( 'owner', 'repo', 42, '' ) );
	}
}
