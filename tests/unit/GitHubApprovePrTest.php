<?php
/**
 * Test vipgoci_github_approve_pr with offline HTTP responses.
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
#[CoversFunction( 'vipgoci_github_approve_pr' )]
final class GitHubApprovePrTest extends MockedApiTestCase {
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
	 * An approval must target the requested PR and commit with the correct body.
	 *
	 * @return void
	 */
	public function testApprovalPayload(): void {
		require_once __DIR__ . '/../../other-web-services.php';
		HttpApiMock::expect(
			'POST',
			'https://api.github.com/repos/owner/repo/pulls/42/reviews',
			'test-token',
			'{"id":7}',
			array(
				'commit_id' => 'head-commit',
				'body'      => 'Approved by policy.',
				'event'     => 'APPROVE',
				'comments'  => array(),
			)
		);
		vipgoci_github_approve_pr( 'owner', 'repo', 'test-token', 42, 'head-commit', 'Approved by policy.' );
	}
}
