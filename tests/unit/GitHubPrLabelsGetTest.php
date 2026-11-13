<?php
/**
 * Test vipgoci_github_pr_labels_get with offline HTTP responses.
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
#[CoversFunction( 'vipgoci_github_pr_labels_get' )]
final class GitHubPrLabelsGetTest extends MockedApiTestCase {
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
	 * Filter cached labels and refresh only when explicitly requested.
	 *
	 * @return void
	 */
	public function testFilteringCacheAndRefresh(): void {
		$url = 'https://api.github.com/repos/owner/repo/issues/42/labels';
		HttpApiMock::expect( 'GET', $url, 'test-token', '[{"name":"enhancement","color":"a2eeef"},{"name":"skip-scan","color":"ffffff"}]' );
		$labels = vipgoci_github_pr_labels_get( 'owner', 'repo', 'test-token', 42 );
		$this->assertSame( array( 'enhancement', 'skip-scan' ), array_column( $labels, 'name' ) );
		$this->assertSame( 'a2eeef', $labels[0]->color );
		$this->assertSame( $labels[1], vipgoci_github_pr_labels_get( 'owner', 'repo', 'test-token', 42, 'skip-scan' ) );
		$this->assertFalse( vipgoci_github_pr_labels_get( 'owner', 'repo', 'test-token', 42, 'missing' ) );

		HttpApiMock::expect( 'GET', $url, 'test-token', '[]' );
		$this->assertFalse( vipgoci_github_pr_labels_get( 'owner', 'repo', 'test-token', 42, null, true ) );
		$this->assertFalse( vipgoci_github_pr_labels_get( 'owner', 'repo', 'test-token', 42 ) );
	}
}
