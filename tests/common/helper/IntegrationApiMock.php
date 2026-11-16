<?php
/**
 * Deterministic service responses for the existing integration suite.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Common;

use PHPUnit\Framework\Assert;

/**
 * Replay checked-in responses and simulate writes within one isolated test.
 */
final class IntegrationApiMock {
	/**
	 * Fixture responses and per-test service state.
	 *
	 * @var array|null
	 */
	private static ?array $responses = null;

	/**
	 * Next comment ID, shared across all PRs in this isolated fixture state.
	 *
	 * @var int
	 */
	private static int $next_comment_id = 900000;

	/**
	 * Whether requests must match an explicit per-test queue.
	 *
	 * @var bool
	 */
	private static bool $strict = false;

	/**
	 * Queue a low-level HTTP response for transport and failure-path tests.
	 *
	 * @param string $method HTTP method.
	 * @param string $url Full URL.
	 * @param array  $headers Expected request headers.
	 * @param array  $response HTTP response descriptor (status, headers, body).
	 * @param array  $body Expected request body.
	 * @return void
	 */
	public static function expect( string $method, string $url, array $headers, array $response, array $body = array() ): void {
		require_once __DIR__ . '/HttpApiMock.php';
		self::$strict = true;
		HttpApiMock::expect( $method, $url, $headers, json_encode( array( '__http' => $response ), JSON_THROW_ON_ERROR ), $body );
	}

	/**
	 * Return a response without opening a socket or using real credentials.
	 *
	 * @param string            $method HTTP method.
	 * @param string            $url    Complete URL.
	 * @param null|string|array $token  Synthetic credentials.
	 * @param array             $body   Write payload.
	 *
	 * @return string|int|null Raw response.
	 */
	public static function request( string $method, string $url, null|string|array $token, array $body = array() ): string|int|null {
		if ( self::$strict ) {
			return HttpApiMock::request( $method, $url, $token, $body );
		}
		if ( null === self::$responses ) {
			self::$responses = json_decode( file_get_contents( __DIR__ . '/../../fixtures/integration-api.json' ), true, 512, JSON_THROW_ON_ERROR );
			foreach ( self::$responses as $response_key => $comments ) {
				if ( preg_match( '#^GET https://api.github.com/repos/automattic/vip-go-ci-testing/issues/\d+/comments\?#', $response_key ) ) {
					foreach ( $comments as $comment ) {
						self::$next_comment_id = max( self::$next_comment_id, $comment['id'] + 1 );
					}
				}
			}
		}
		$parts = parse_url( $url );
		$path  = $parts['path'] ?? '';
		$key   = $method . ' ' . $url;

		if ( '/user' === $path && array_filter( (array) $token, static fn( $header ) => str_contains( $header, 'invalid-token-' ) ) ) {
			return '{"message":"Bad credentials"}';
		}

		if ( 'GET' !== $method && 'api.github.com' === ( $parts['host'] ?? '' ) ) {
			if ( preg_match( '#^(/repos/automattic/vip-go-ci-testing/issues/\d+/labels)(?:/(.+))?$#', $path, $matches ) ) {
				$get_key = 'GET https://api.github.com' . $matches[1];
				Assert::assertArrayHasKey( $get_key, self::$responses, 'Unknown fixture label resource.' );
				$labels = self::$responses[ $get_key ];
				if ( 'POST' === $method && ! isset( $matches[2] ) ) {
					Assert::assertIsString( $body[0] ?? null );
					$labels = array_values( array_filter( $labels, static fn( $label ) => $body[0] !== $label['name'] ) );
					array_unshift(
						$labels,
						array(
							'name'  => $body[0],
							'color' => 'ffffff',
						)
					);
				} elseif ( 'DELETE' === $method && isset( $matches[2] ) ) {
					$labels = array_values( array_filter( $labels, static fn( $label ) => rawurldecode( $matches[2] ) !== $label['name'] ) );
				} else {
					Assert::fail( 'Unsupported fixture label mutation: ' . $key );
				}
				self::$responses[ $get_key ] = $labels;
				return json_encode( $labels, JSON_THROW_ON_ERROR );
			}
			if ( 'POST' === $method && preg_match( '#^(/repos/automattic/vip-go-ci-testing/issues/\d+)/comments$#', $path, $matches ) ) {
				Assert::assertIsString( $body['body'] ?? null );
				$get_key = 'GET https://api.github.com' . $path . '?page=1&per_page=100';
				Assert::assertArrayHasKey( $get_key, self::$responses, 'Unknown fixture comment resource.' );
				$comments                    = self::$responses[ $get_key ];
				$comment                     = array(
					'id'   => self::$next_comment_id++,
					'body' => $body['body'],
					'user' => array(
						'login' => 'fixture-bot',
						'id'    => 100,
					),
				);
				$comments[]                  = $comment;
				self::$responses[ $get_key ] = $comments;
				return json_encode( $comment, JSON_THROW_ON_ERROR );
			}
			if ( 'DELETE' === $method && preg_match( '#^/repos/automattic/vip-go-ci-testing/issues/comments/(\d+)$#', $path, $matches ) ) {
				$found = false;
				foreach ( self::$responses as $response_key => $comments ) {
					if ( preg_match( '#^GET https://api.github.com/repos/automattic/vip-go-ci-testing/issues/\d+/comments\?#', $response_key ) ) {
						self::$responses[ $response_key ] = array_values( array_filter( $comments, static fn( $comment ) => (string) $comment['id'] !== $matches[1] ) );
						$found                            = $found || count( self::$responses[ $response_key ] ) !== count( $comments );
					}
				}
				Assert::assertTrue( $found, 'Unknown fixture comment ID.' );
				return '{}';
			}
			if ( preg_match( '#^(/repos/automattic/vip-go-ci-testing/pulls/\d+)/reviews(?:/(\d+)/dismissals)?$#', $path, $matches ) ) {
				$get_key = 'GET https://api.github.com' . $matches[1] . '/reviews?per_page=100&page=1';
				Assert::assertArrayHasKey( $get_key, self::$responses, 'Unknown fixture review resource.' );
				$reviews = self::$responses[ $get_key ];
				if ( 'POST' === $method && ! isset( $matches[2] ) ) {
					Assert::assertContains( $body['event'] ?? null, array( 'APPROVE', 'COMMENT', 'REQUEST_CHANGES' ) );
					$review                      = array(
						'id'        => 800000 + count( $reviews ),
						'state'     => array(
							'APPROVE'         => 'APPROVED',
							'COMMENT'         => 'COMMENTED',
							'REQUEST_CHANGES' => 'CHANGES_REQUESTED',
						)[ $body['event'] ],
						'body'      => $body['body'],
						'commit_id' => $body['commit_id'],
						'user'      => array(
							'login' => 'fixture-bot',
							'id'    => 100,
						),
					);
					$reviews[]                   = $review;
					self::$responses[ $get_key ] = $reviews;
					return json_encode( $review, JSON_THROW_ON_ERROR );
				}
				if ( 'PUT' === $method && isset( $matches[2] ) ) {
					$found = false;
					foreach ( $reviews as $index => $review ) {
						if ( (string) $review['id'] === $matches[2] ) {
							$reviews[ $index ]['state'] = 'DISMISSED';
							$found                      = true;
						}
					}
					Assert::assertTrue( $found, 'Unknown fixture review ID.' );
					self::$responses[ $get_key ] = $reviews;
					return '{}';
				}
				Assert::fail( 'Unsupported fixture review mutation: ' . $key );
			}
			if ( 'POST' === $method && preg_match( '#^/repos/automattic/vip-go-ci-testing/statuses/([^/]+)$#', $path, $matches ) ) {
				Assert::assertContains( $body['state'] ?? null, array( 'pending', 'failure', 'success', 'error' ) );
				Assert::assertArrayHasKey( 'GET https://api.github.com/repos/automattic/vip-go-ci-testing/commits/' . $matches[1] . '/status', self::$responses, 'Unknown fixture status resource.' );
				self::$responses[ 'GET https://api.github.com/repos/automattic/vip-go-ci-testing/commits/' . $matches[1] . '/status' ] = array( 'statuses' => array( $body ) );
				return json_encode( $body, JSON_THROW_ON_ERROR );
			}
		}

		if ( ! array_key_exists( $key, self::$responses ) ) {
			$missing_log = getenv( 'VIPGOCI_TESTING_MISSING_REQUESTS' );
			if ( false !== $missing_log && '' !== $missing_log ) {
				file_put_contents(
					$missing_log,
					json_encode(
						array(
							'method' => $method,
							'url'    => $url,
							'body'   => $body,
						)
					) . PHP_EOL,
					FILE_APPEND | LOCK_EX
				);
			}
			Assert::fail( 'No API fixture for ' . $key );
		}
		$response = self::$responses[ $key ];
		if ( isset( $response['__variants'] ) ) {
			foreach ( $response['__variants'] as $variant ) {
				if ( $body === $variant['body'] ) {
					return json_encode( $variant['response'], JSON_THROW_ON_ERROR );
				}
			}
			$missing_log = getenv( 'VIPGOCI_TESTING_MISSING_REQUESTS' );
			if ( false !== $missing_log && '' !== $missing_log ) {
				file_put_contents(
					$missing_log,
					json_encode(
						array(
							'method' => $method,
							'url'    => $url,
							'body'   => $body,
						)
					) . PHP_EOL,
					FILE_APPEND | LOCK_EX
				);
			}
			Assert::fail( 'No API request-body fixture for ' . $key );
		}
		return json_encode( $response, JSON_THROW_ON_ERROR );
	}
}
