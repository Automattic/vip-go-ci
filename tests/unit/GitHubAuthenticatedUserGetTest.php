<?php
/**
 * Test vipgoci_github_authenticated_user_get with offline HTTP responses.
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
#[CoversFunction( 'vipgoci_github_authenticated_user_get' )]
final class GitHubAuthenticatedUserGetTest extends MockedApiTestCase {
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
	 * Cache the decoded user for one token without sharing another token's user.
	 *
	 * @return void
	 */
	public function testUsersAreCachedPerToken(): void {
		HttpApiMock::expect( 'GET', 'https://api.github.com/user', 'first-token', '{"login":"first-bot","id":10}' );
		$first = vipgoci_github_authenticated_user_get( 'first-token' );
		$this->assertSame( 'first-bot', $first->login );
		$this->assertSame( 10, $first->id );
		$this->assertEquals( $first, vipgoci_github_authenticated_user_get( 'first-token' ) );

		HttpApiMock::expect( 'GET', 'https://api.github.com/user', 'second-token', '{"login":"second-bot","id":20}' );
		$this->assertSame( 'second-bot', vipgoci_github_authenticated_user_get( 'second-token' )->login );
	}
}
