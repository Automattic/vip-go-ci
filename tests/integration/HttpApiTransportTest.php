<?php
/**
 * Exercise production HTTP clients through the isolated cURL fixture boundary.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vipgoci\Tests\Common\HttpApiMock;
use Vipgoci\Tests\Common\IntegrationApiMock;

/**
 * Validate request construction, response handling and retries without a socket.
 */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
final class HttpApiTransportTest extends TestCase {
	/** Load the real clients and initialize the expectation queue. */
	protected function setUp(): void {
		if ( 'true' === getenv( 'VIPGOCI_TESTING_LIVE_API' ) ) {
			$this->markTestSkipped( 'Fixture transport tests are disabled in live mode.' );
		}

		require_once __DIR__ . '/IncludesForTests.php';
		require_once __DIR__ . '/../common/helper/HttpApiMock.php';
		HttpApiMock::reset();
		$GLOBALS['vipgoci_debug_level']     = -1;
		$GLOBALS['vipgoci_api_mock_delays'] = array();
	}

	/** Assert that every response was consumed. */
	protected function tearDown(): void {
		HttpApiMock::assert_complete();
	}

	/** Verify the replacements are present in PHPUnit's isolated worker. */
	public function testNativeTransportIsDisabledInWorker(): void {
		foreach ( array( 'curl_exec', 'curl_setopt', 'curl_setopt_array', 'curl_getinfo', 'sleep' ) as $name ) {
			$this->assertTrue( ( new \ReflectionFunction( $name ) )->isUserDefined(), $name );
			$this->assertContains( $name, explode( ',', ini_get( 'disable_functions' ) ) );
		}
	}

	/** Missing fixtures must fail before any network operation. */
	public function testUnexpectedEndpointFails(): void {
		$this->expectException( AssertionFailedError::class );
		vipgoci_http_api_fetch_url( 'https://unconfigured.fixture.invalid/path', null );
	}

	/** A typo in a write target must not create an unregistered fixture resource. */
	public function testUnexpectedMutationFails(): void {
		$this->expectException( AssertionFailedError::class );
		vipgoci_github_label_add_to_pr( 'automattic', 'vip-go-ci-testing', 'fixture-token', 999999, 'fixture-label' );
	}

	/** Preserve authentication and the raw response through a production GET. */
	public function testGetRequest(): void {
		$url = 'https://transport.fixture.invalid/get?first=1&second=2';
		IntegrationApiMock::expect( 'GET', $url, array( 'Authorization: token fixture-token' ), array( 'body' => '{"ok":true}' ) );
		$this->assertSame( '{"ok":true}', vipgoci_http_api_fetch_url( $url, 'fixture-token' ) );
	}

	/** GitHub requests must include both authentication and the API version. */
	public function testGitHubHeaders(): void {
		$url = 'https://api.github.com/user';
		IntegrationApiMock::expect( 'GET', $url, array( 'Authorization: token fixture-token', 'X-GitHub-Api-Version: ' . VIPGOCI_GITHUB_API_VERSION ), array( 'body' => '{"login":"fixture-bot"}' ) );
		$this->assertSame( '{"login":"fixture-bot"}', vipgoci_http_api_fetch_url( $url, 'fixture-token' ) );
	}

	/** WPScan uses its own token-header format rather than GitHub authentication. */
	public function testWpscanHeaders(): void {
		$url = 'https://wpscan.com/api/v3/plugins/fixture-addon';
		IntegrationApiMock::expect( 'GET', $url, array( 'Authorization: Token token=fixture-token' ), array( 'body' => '{}' ) );
		$this->assertSame( '{}', vipgoci_http_api_fetch_url( $url, array( 'wpscan_token' => 'fixture-token' ) ) );
	}

	/** Direct repo-meta cURL requests must preserve their credentials and cache. */
	public function testRepoMetaHeadersAndCache(): void {
		$url = 'https://meta.fixture.invalid/v1/sites?active=1&page=1&pagesize=20&source_repo=fixture-owner%2Ffixture-repo';
		IntegrationApiMock::expect( 'GET', $url, array( 'API-User-ID: fixture-user', 'Access-Token: fixture-token' ), array( 'body' => '{"status":"ok","data":[]}' ) );
		$expected = array(
			'status' => 'ok',
			'data'   => array(),
		);
		$this->assertSame( $expected, vipgoci_repo_meta_api_data_fetch( 'https://meta.fixture.invalid', 'fixture-user', 'fixture-token', 'fixture-owner', 'fixture-repo' ) );
		$this->assertSame( $expected, vipgoci_repo_meta_api_data_fetch( 'https://meta.fixture.invalid', 'fixture-user', 'fixture-token', 'fixture-owner', 'fixture-repo' ) );
	}

	/** Repo-meta retries both transport failures and malformed response bodies. */
	public function testRepoMetaRetriesFailures(): void {
		$url     = 'https://meta.fixture.invalid/v1/sites?active=1&page=1&pagesize=20&source_repo=fixture-owner%2Ffixture-repo';
		$headers = array( 'API-User-ID: fixture-user', 'Access-Token: fixture-token' );
		foreach ( array( false, 'not JSON', '{"status":"ok","data":[]}' ) as $body ) {
			IntegrationApiMock::expect( 'GET', $url, $headers, array( 'body' => $body ) );
		}
		$this->assertSame(
			array(
				'status' => 'ok',
				'data'   => array(),
			),
			vipgoci_repo_meta_api_data_fetch( 'https://meta.fixture.invalid', 'fixture-user', 'fixture-token', 'fixture-owner', 'fixture-repo' )
		);
	}

	/** Exhausting repo-meta retries must preserve the null failure result. */
	public function testRepoMetaRetryLimit(): void {
		$url = 'https://meta.fixture.invalid/v1/sites?active=1&page=1&pagesize=20&source_repo=fixture-owner%2Ffixture-repo';
		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			IntegrationApiMock::expect( 'GET', $url, array(), array( 'body' => false ) );
		}
		$this->assertNull( vipgoci_repo_meta_api_data_fetch( 'https://meta.fixture.invalid', '', '', 'fixture-owner', 'fixture-repo' ) );
	}

	/** Encode JSON and accept the created response from a production POST. */
	public function testJsonPostRequest(): void {
		$url  = 'https://transport.fixture.invalid/post';
		$body = array(
			'message' => 'Fixture message',
			'values'  => array( 1, 2 ),
		);
		IntegrationApiMock::expect(
			'POST',
			$url,
			array( 'Authorization: token fixture-token', 'Content-Type: application/json' ),
			array(
				'status' => 201,
				'body'   => '{"id":7}',
			),
			$body
		);
		$this->assertSame( '{"id":7}', vipgoci_http_api_post_url( $url, $body, 'fixture-token' ) );
	}

	/** Preserve form fields, bearer authentication and the requested HTTP version. */
	public function testFormPostRequest(): void {
		$url  = 'https://transport.fixture.invalid/form';
		$body = array(
			'name' => 'Fixture with spaces',
			'json' => '{"key":"value"}',
		);
		IntegrationApiMock::expect( 'POST', $url, array( 'Authorization: Bearer fixture-token', 'Content-Type: application/x-www-form-urlencoded' ), array( 'body' => '{}' ), $body );
		$this->assertSame( '{}', vipgoci_http_api_post_url( $url, $body, array( 'bearer' => 'fixture-token' ), false, false, CURL_HTTP_VERSION_1_1, VIPGOCI_HTTP_API_CONTENT_TYPE_X_WWW_FORM_URLENCODED ) );
		$options = $GLOBALS['vipgoci_curl_mock_requests'][0]['options'];
		$this->assertSame( CURL_HTTP_VERSION_1_1, $options[ CURLOPT_HTTP_VERSION ] );
		$this->assertSame( 0, $options[ CURLOPT_MAXREDIRS ] );
		$this->assertFalse( $options[ CURLOPT_FOLLOWLOCATION ] );
	}

	/** The DELETE flag must reach the transport as DELETE. */
	public function testDeleteRequest(): void {
		$url = 'https://transport.fixture.invalid/comments/7';
		IntegrationApiMock::expect(
			'DELETE',
			$url,
			array( 'Authorization: token fixture-token', 'Content-Type: application/json' ),
			array(
				'status' => 204,
				'body'   => '',
			)
		);
		$this->assertSame( '', vipgoci_http_api_post_url( $url, array(), 'fixture-token', true ) );
	}

	/** A successful PUT must return the production success status. */
	public function testPutRequest(): void {
		$url  = 'https://transport.fixture.invalid/dismissal';
		$body = array( 'message' => 'Fixture dismissal' );
		IntegrationApiMock::expect( 'PUT', $url, array( 'Authorization: token fixture-token' ), array( 'body' => '{}' ), $body );
		$this->assertSame( 0, vipgoci_http_api_put_url( $url, $body, 'fixture-token' ) );
	}

	/** A transient server error must consume another response rather than succeed. */
	public function testGetRetriesServerFailure(): void {
		$url = 'https://transport.fixture.invalid/retry';
		IntegrationApiMock::expect(
			'GET',
			$url,
			array(),
			array(
				'status'  => 503,
				'headers' => array( 'Retry-After' => '1' ),
				'body'    => '{"message":"Unavailable"}',
			)
		);
		IntegrationApiMock::expect( 'GET', $url, array(), array( 'body' => '{"recovered":true}' ) );
		$this->assertSame( '{"recovered":true}', vipgoci_http_api_fetch_url( $url, null, false, 1 ) );
		$this->assertSame( array( 2 ), $GLOBALS['vipgoci_api_mock_delays'] );
	}

	/** False transport responses retry with the normal backoff duration. */
	public function testGetRetriesTransportFailure(): void {
		$url = 'https://transport.fixture.invalid/transport-retry';
		IntegrationApiMock::expect( 'GET', $url, array(), array( 'body' => false ) );
		IntegrationApiMock::expect( 'GET', $url, array(), array( 'body' => '{}' ) );
		$this->assertSame( '{}', vipgoci_http_api_fetch_url( $url, null, false, 1 ) );
		$this->assertSame( array( 10 ), $GLOBALS['vipgoci_api_mock_delays'] );
	}

	/** The fixture clock must preserve sleeps outside API clients. */
	public function testOrdinarySleepRetainsRealTime(): void {
		$started = microtime( true );
		sleep( 1 );
		$this->assertGreaterThanOrEqual( 0.9, microtime( true ) - $started );
		$this->assertSame( array(), $GLOBALS['vipgoci_api_mock_delays'] );
	}

	/** Exhausted nonfatal GETs must return null, preserving the failure outcome. */
	public function testGetExhaustionReturnsNull(): void {
		$url = 'https://transport.fixture.invalid/exhausted';
		IntegrationApiMock::expect(
			'GET',
			$url,
			array(),
			array(
				'status'  => 503,
				'headers' => array( 'Retry-After' => '1' ),
				'body'    => '{"message":"Unavailable"}',
			)
		);
		$this->assertNull( vipgoci_http_api_fetch_url( $url, null, false, 0 ) );
		$this->assertSame( array( 2 ), $GLOBALS['vipgoci_api_mock_delays'] );
	}

	/** Failed cURL execution must reach the production POST failure branch. */
	public function testPostTransportFailure(): void {
		$url = 'https://transport.fixture.invalid/failed-post';
		IntegrationApiMock::expect( 'POST', $url, array( 'Content-Type: application/json' ), array( 'body' => false ) );
		$this->assertSame( -1, vipgoci_http_api_post_url( $url, array(), null, false, true, CURL_HTTP_VERSION_NONE, VIPGOCI_HTTP_API_CONTENT_TYPE_APPLICATION_JSON, 0 ) );
	}

	/** HTTP error responses must not become successful PUTs. */
	public function testPutHttpFailure(): void {
		$url = 'https://transport.fixture.invalid/failed-put';
		IntegrationApiMock::expect(
			'PUT',
			$url,
			array(),
			array(
				'status' => 404,
				'body'   => '{"message":"Not Found"}',
			)
		);
		$this->assertSame( -1, vipgoci_http_api_put_url( $url, array(), '', 0 ) );
	}

	/** Exercise the real fatal rate-limit path in its own PHP subprocess. */
	public function testRateLimitTerminates(): void {
		$output = array();
		$status = 0;
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- Verify the real process exit status.
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/helper/HttpApiRateLimitExit.php' ), $output, $status );
		$this->assertSame( VIPGOCI_EXIT_HTTP_API_ERROR, $status );
		$this->assertStringContainsString( 'Exceeded rate limit for HTTP API', implode( "\n", $output ) );
	}
}
