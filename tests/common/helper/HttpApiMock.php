<?php
/**
 * Strict HTTP response queue for offline API tests.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Common;

use PHPUnit\Framework\Assert;

/**
 * Match every request exactly and never fall back to a live transport.
 */
final class HttpApiMock {
	/**
	 * Requests still expected, in order.
	 *
	 * @var array
	 */
	private static array $pending = array();

	/**
	 * Clear expectations between tests.
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$pending = array();
	}

	/**
	 * Queue one raw HTTP response, including transport failure sentinels.
	 *
	 * @param string            $method   HTTP method.
	 * @param string            $url      Complete URL, including query string.
	 * @param null|string|array $token    Expected authentication argument.
	 * @param string|int|null   $response Raw response or failure sentinel.
	 * @param array             $body     Expected request payload.
	 *
	 * @return void
	 */
	public static function expect( string $method, string $url, null|string|array $token, string|int|null $response, array $body = array() ): void {
		self::$pending[] = array(
			'request'  => array( $method, $url, $token, $body ),
			'response' => $response,
		);
	}

	/**
	 * Validate a request and consume its response once.
	 *
	 * @param string            $method HTTP method.
	 * @param string            $url    Complete URL.
	 * @param null|string|array $token  Authentication argument.
	 * @param array             $body   Request payload.
	 *
	 * @return string|int|null Configured raw response.
	 */
	public static function request( string $method, string $url, null|string|array $token, array $body = array() ): string|int|null {
		Assert::assertNotEmpty( self::$pending, 'Unexpected HTTP request: ' . $method . ' ' . $url );
		$next = self::$pending[0];
		Assert::assertSame( $next['request'], array( $method, $url, $token, $body ), 'HTTP request did not match its fixture.' );
		array_shift( self::$pending );
		return $next['response'];
	}

	/**
	 * Detect missing requests, including prematurely stopped pagination.
	 *
	 * @return void
	 */
	public static function assert_complete(): void {
		Assert::assertSame( array(), self::$pending, 'Not all expected HTTP requests were made.' );
	}
}
