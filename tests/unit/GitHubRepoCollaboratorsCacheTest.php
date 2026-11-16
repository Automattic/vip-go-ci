<?php
/**
 * Keep collaborator cache entries scoped to the requesting credential.
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

/** Exercise the real cache and response parser with a strict HTTP queue. */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_github_repo_collaborators_get' )]
final class GitHubRepoCollaboratorsCacheTest extends MockedApiTestCase {
	/** Load the production helper behind the offline HTTP boundary. */
	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../../github-api.php';
	}

	/** Credentials and affiliations must retain separate results and cache hits. */
	public function testCredentialsAndAffiliationsHaveIndependentCacheEntries(): void {
		$url = VIPGOCI_GITHUB_BASE_URL . '/repos/fixture/repo/collaborators?page=1&per_page=100&affiliation=';
		HttpApiMock::expect( 'GET', $url . 'all', 'token-a', '[{"login":"alpha"}]' );
		HttpApiMock::expect( 'GET', $url . 'all', 'token-b', '[{"login":"beta"}]' );
		HttpApiMock::expect( 'GET', $url . 'outside', 'token-a', '[{"login":"outside-alpha"}]' );

		$this->assertSame( array( 'alpha' ), array_column( vipgoci_github_repo_collaborators_get( 'fixture', 'repo', 'token-a' ), 'login' ) );
		$this->assertSame( array( 'beta' ), array_column( vipgoci_github_repo_collaborators_get( 'fixture', 'repo', 'token-b' ), 'login' ) );
		$this->assertSame( array( 'alpha' ), array_column( vipgoci_github_repo_collaborators_get( 'fixture', 'repo', 'token-a' ), 'login' ) );
		$this->assertSame( array( 'outside-alpha' ), array_column( vipgoci_github_repo_collaborators_get( 'fixture', 'repo', 'token-a', 'outside' ), 'login' ) );
		$this->assertSame( array( 'alpha' ), array_column( vipgoci_github_repo_collaborators_get( 'fixture', 'repo', 'token-a' ), 'login' ) );
		$this->assertSame( array( 'outside-alpha' ), array_column( vipgoci_github_repo_collaborators_get( 'fixture', 'repo', 'token-a', 'outside' ), 'login' ) );
	}
}
