<?php
/**
 * Test dry-run handling of write requests to the GitHub API.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Check that write requests are skipped only in dry-run mode and only for GitHub.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_http_api_dry_run_set' )]
#[CoversFunction( 'vipgoci_http_api_dry_run_skip' )]
#[CoversFunction( 'vipgoci_http_api_post_url' )]
#[CoversFunction( 'vipgoci_http_api_put_url' )]
final class HttpFunctionsHttpApiDryRunTest extends TestCase {
	/**
	 * Require files. Requests that are not skipped go to a closed
	 * local port, never to GitHub, should the guard fail.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		if ( ! defined( 'VIPGOCI_GITHUB_BASE_URL' ) ) {
			define( 'VIPGOCI_GITHUB_BASE_URL', 'https://127.0.0.1:9' );
		}

		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../http-functions.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/../../output-security.php';

		require_once __DIR__ . '/helper/IrcApiAlertQueue.php';
		require_once __DIR__ . '/helper/RuntimeMeasure.php';
	}

	/**
	 * Check which requests are skipped.
	 *
	 * @return void
	 */
	public function testSkipOnlyGitHubWhenEnabled() :void {
		$github_url = VIPGOCI_GITHUB_BASE_URL . '/repos/owner/repo/issues/1/comments';

		$this->assertFalse( vipgoci_http_api_dry_run_skip( $github_url ), 'Should be off by default' );

		vipgoci_http_api_dry_run_set( true );

		$this->assertTrue( vipgoci_http_api_dry_run_skip( $github_url ) );
		$this->assertFalse( vipgoci_http_api_dry_run_skip( 'https://irc.example.org/api' ) );
		$this->assertFalse( vipgoci_http_api_dry_run_skip( VIPGOCI_GITHUB_BASE_URL . '.example.org/repos' ) );

		vipgoci_http_api_dry_run_set( false );

		$this->assertFalse( vipgoci_http_api_dry_run_skip( $github_url ) );
	}

	/**
	 * Check that POST, DELETE and PUT are logged and report success without sending.
	 *
	 * @return void
	 */
	public function testWriteRequestsAreLoggedNotSent() :void {
		vipgoci_http_api_dry_run_set( true );

		$github_url = VIPGOCI_GITHUB_BASE_URL . '/repos/owner/repo/issues/1/comments';

		$this->expectOutputRegex( '/"http_method": "POST".*"body": "test comment".*"http_method": "DELETE".*"http_method": "PUT"/s' );

		$this->assertSame(
			'',
			vipgoci_http_api_post_url( $github_url, array( 'body' => 'test comment' ), 'test-token' )
		);

		$this->assertSame(
			'',
			vipgoci_http_api_post_url( $github_url, array(), 'test-token', true )
		);

		$this->assertSame(
			0,
			vipgoci_http_api_put_url( $github_url, array( 'message' => 'test' ), 'test-token' )
		);
	}
}
