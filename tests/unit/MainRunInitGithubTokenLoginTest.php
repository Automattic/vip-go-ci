<?php
/**
 * Test --github-token-login handling in vipgoci_run_init_github_token_option().
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
 * GitHub App installation tokens require the login to be set, as
 * the token prefix does not identify the app. Uses fake HTTP.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_run_init_github_token_option' )]
final class MainRunInitGithubTokenLoginTest extends TestCase {
	/**
	 * Require files and set indication.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/helper/IndicateTestId.php';

		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../cache.php';
		require_once __DIR__ . '/../../github-api.php';
		require_once __DIR__ . '/../../github-misc.php';
		require_once __DIR__ . '/../../other-web-services.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/helper/GitHubRequestGuards.php';

		$GLOBALS['vipgoci_debug_level']         = 0;
		$GLOBALS['vipgoci_test_http_requests']  = array();
		$GLOBALS['vipgoci_test_http_responses'] = array();

		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		vipgoci_github_token_login_set( null );

		/*
		 * Indicate that this particular test is running,
		 * needed so that vipgoci_sysexit() can return
		 * instead of exiting. See the function itself.
		 */
		vipgoci_unittests_indicate_test_id( 'MainRunInitGithubTokenLoginTest' );
	}

	/**
	 * Remove indication.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		vipgoci_unittests_remove_indication_for_test_id( 'MainRunInitGithubTokenLoginTest' );
	}

	/**
	 * Without the login, an installation token is rejected.
	 *
	 * @return void
	 */
	public function testInstallationTokenRequiresLogin(): void {
		$options = array( 'token' => 'ghs_test-token' );

		ob_start();

		vipgoci_run_init_github_token_option( $options );

		$printed_data = ob_get_clean();

		$this->assertStringContainsString(
			'Usage: Option --github-token-login is required for GitHub App installation tokens',
			$printed_data
		);

		$this->assertStringNotContainsString(
			'Got information about token-holder user from GitHub',
			$printed_data
		);

		$this->assertSame( array(), $GLOBALS['vipgoci_test_http_requests'] );
	}

	/**
	 * With the login set, an installation token is accepted
	 * without querying GitHub.
	 *
	 * @return void
	 */
	public function testInstallationTokenWithLogin(): void {
		$options = array(
			'token'              => 'ghs_test-token',
			'github-token-login' => 'github-actions[bot]',
		);

		ob_start();

		vipgoci_run_init_github_token_option( $options );

		$printed_data = ob_get_clean();

		$this->assertStringNotContainsString( 'Usage: ', $printed_data );

		$this->assertStringContainsString(
			'Got information about token-holder user from GitHub',
			$printed_data
		);

		$this->assertSame( 'github-actions[bot]', vipgoci_github_token_login_get() );
		$this->assertSame( array(), $GLOBALS['vipgoci_test_http_requests'] );
	}

	/**
	 * A login given more than once is rejected, and processing stops.
	 *
	 * @return void
	 */
	public function testRepeatedLoginIsRejected(): void {
		$options = array(
			'token'              => 'ghs_test-token',
			'github-token-login' => array( 'a', 'b' ),
		);

		ob_start();

		vipgoci_run_init_github_token_option( $options );

		$printed_data = ob_get_clean();

		$this->assertStringContainsString(
			'Usage: Option --github-token-login should be a single string',
			$printed_data
		);

		$this->assertNull( vipgoci_github_token_login_get() );
	}
}
