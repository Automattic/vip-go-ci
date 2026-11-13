<?php
/**
 * Verify strict request matching and failure responses in the API test helper.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Vipgoci\Tests\Common\HttpApiMock;
use Vipgoci\Tests\Common\MockedApiTestCase;

require_once __DIR__ . '/../common/MockedApiTestCase.php';

/**
 * A missing or mismatched fixture must fail instead of returning an empty result.
 */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
final class HttpApiMockTest extends MockedApiTestCase {
	/**
	 * Unexpected requests must fail without any transport fallback.
	 *
	 * @return void
	 */
	public function testUnexpectedRequestFails(): void {
		$this->expectException( AssertionFailedError::class );
		vipgoci_http_api_fetch_url( 'https://example.invalid/unexpected', null );
	}

	/**
	 * Method, host, query, token and payload are all part of the expectation.
	 *
	 * @return void
	 */
	public function testMismatchedRequestsFail(): void {
		$expected = array( 'POST', 'https://example.invalid/items?page=1', 'test-token', array( 'body' => 'expected' ) );
		$variants = array(
			array( 'DELETE', $expected[1], $expected[2], $expected[3] ),
			array( 'POST', 'https://other.invalid/items?page=1', $expected[2], $expected[3] ),
			array( 'POST', 'https://example.invalid/items?page=2', $expected[2], $expected[3] ),
			array( 'POST', $expected[1], 'other-token', $expected[3] ),
			array( 'POST', $expected[1], $expected[2], array( 'body' => 'wrong' ) ),
		);
		foreach ( $variants as $request ) {
			HttpApiMock::expect( $expected[0], $expected[1], $expected[2], '{}', $expected[3] );
			try {
				HttpApiMock::request( ...$request );
				$failed = false;
			} catch ( AssertionFailedError $error ) {
				$failed = true;
			} finally {
				HttpApiMock::reset();
			}
			$this->assertTrue( $failed, 'A mismatched request was accepted.' );
		}
	}

	/**
	 * Failure sentinels and raw JSON must reach the production client unchanged.
	 *
	 * @return void
	 */
	public function testTransportResponsesAndMethods(): void {
		$url = 'https://example.invalid/items';
		HttpApiMock::expect( 'GET', $url, null, null );
		$this->assertNull( vipgoci_http_api_fetch_url( $url, null, false ) );
		HttpApiMock::expect( 'POST', $url, 'test-token', -1, array( 'body' => 'test' ) );
		$this->assertSame( -1, vipgoci_http_api_post_url( $url, array( 'body' => 'test' ), 'test-token' ) );
		HttpApiMock::expect( 'DELETE', $url, '', '{}' );
		$this->assertSame( '{}', vipgoci_http_api_post_url( $url, array(), '', true ) );
		HttpApiMock::expect( 'PUT', $url, 'test-token', 0, array( 'message' => 'test' ) );
		$this->assertSame( 0, vipgoci_http_api_put_url( $url, array( 'message' => 'test' ), 'test-token' ) );
	}

	/**
	 * Unused responses must be reported, even when no unexpected request occurred.
	 *
	 * @return void
	 */
	public function testUnusedResponseFails(): void {
		HttpApiMock::expect( 'GET', 'https://example.invalid/items', '', '[]' );
		try {
			HttpApiMock::assert_complete();
			$failed = false;
		} catch ( AssertionFailedError $error ) {
			$failed = true;
		} finally {
			HttpApiMock::reset();
		}
		$this->assertTrue( $failed, 'An unused response was silently ignored.' );
	}
}
