<?php
/**
 * Fixture checks for restoration of one test PR's state.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Vipgoci\Tests\Common\IntegrationApiMock;
use Vipgoci\Tests\Common\HttpApiMock;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[Group( 'mocked-api' )]
#[CoversClass( LivePrState::class )]
final class LivePrStateTest extends TestCase {
	private const OWNER = 'automattic';
	private const REPO = 'vip-go-ci-testing';
	private const TOKEN = 'fixture-token';
	private const PR = 46;
	private const COMMIT = 'test-commit';

	protected function setUp(): void {
		if ( 'true' === getenv( 'VIPGOCI_TESTING_LIVE_API' ) ) {
			$this->markTestSkipped( 'This restoration check uses only the offline API fixture' );
		}
		require_once __DIR__ . '/IncludesForTests.php';
		require_once __DIR__ . '/helper/LivePrState.php';
	}

	private function labels(): array {
		$url = VIPGOCI_GITHUB_BASE_URL . '/repos/automattic/vip-go-ci-testing/issues/46/labels';
		return array_column( json_decode( vipgoci_http_api_fetch_url( $url, self::TOKEN ), true ), 'name' );
	}

	private function reviews(): array {
		$url = VIPGOCI_GITHUB_BASE_URL . '/repos/automattic/vip-go-ci-testing/pulls/46/reviews?per_page=100&page=1';
		return json_decode( vipgoci_http_api_fetch_url( $url, self::TOKEN ), true );
	}

	private function add_label( string $name ): void {
		vipgoci_github_label_add_to_pr( self::OWNER, self::REPO, self::TOKEN, self::PR, $name );
	}

	private function approve( string $body ): void {
		vipgoci_github_approve_pr( self::OWNER, self::REPO, self::TOKEN, self::PR, self::COMMIT, $body );
	}

	public function testRestoresOnlyNewLabelAndMarkedApproval(): void {
		$this->add_label( 'unrelated' );
		// Even an identical marker and commit cannot make a baseline review ours.
		$this->approve( 'existing approval with test-marker' );
		$before = $this->reviews();
		$state = new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array( 'Label for testing' ), 'test-marker', self::COMMIT );
		$this->assertTrue( $state->has_own_approval() );

		$this->add_label( 'Label for testing' );
		$this->approve( 'created by test-marker' );
		$state->restore();

		$this->assertSame( array( 'unrelated' ), $this->labels() );
		$after = $this->reviews();
		$this->assertSame( $before[0], $after[0] );
		$this->assertSame( 'DISMISSED', $after[1]['state'] );
	}

	public function testPreservesNewApprovalsThatFailAnyOwnershipCheck(): void {
		$state = new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array(), 'test-marker', self::COMMIT );
		$this->assertFalse( $state->has_own_approval() );
		$this->approve( 'created by test-marker' );
		$owned = $this->reviews()[0];

		// Each unrelated approval differs from the owned review in only one field.
		$other_account = $owned;
		$other_account['id'] = $owned['id'] + 1;
		$other_account['user'] = array( 'id' => 101, 'login' => 'another-reviewer' );
		$other_marker = $owned;
		$other_marker['id'] = $owned['id'] + 2;
		$other_marker['body'] = 'created by another-invocation';
		$other_commit = $owned;
		$other_commit['id'] = $owned['id'] + 3;
		$other_commit['commit_id'] = 'another-commit';
		IntegrationApiMock::replaceResponse(
			VIPGOCI_GITHUB_BASE_URL . '/repos/automattic/vip-go-ci-testing/pulls/46/reviews?per_page=100&page=1',
			array( $owned, $other_account, $other_marker, $other_commit )
		);

		$error_message = null;
		try {
			$state->restore();
		} catch ( RuntimeException $error ) {
			$error_message = $error->getMessage();
		}
		$this->assertSame(
			'Could not restore test PR automattic/vip-go-ci-testing#46: ' .
			'new approval ' . $other_marker['id'] . ' lacks this invocation marker or commit; left untouched; ' .
			'new approval ' . $other_commit['id'] . ' lacks this invocation marker or commit; left untouched; ' .
			'new approvals remain: ' . $other_marker['id'] . ', ' . $other_commit['id'],
			$error_message
		);
		$owned['state'] = 'DISMISSED';
		$this->assertSame(
			array( $owned, $other_account, $other_marker, $other_commit ),
			$this->reviews(),
			'Only the new approval matching the account, invocation marker and commit may be dismissed.'
		);
	}

	public function testFinallyRestoresAfterFailureAndReaddsRemovedLabel(): void {
		$this->add_label( 'unrelated' );
		$this->add_label( 'skip-wpscan' );
		$state = new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array( 'skip-wpscan' ), 'failure-marker', self::COMMIT );
		try {
			vipgoci_github_pr_label_remove( self::OWNER, self::REPO, self::TOKEN, self::PR, 'skip-wpscan' );
			$this->approve( 'created by failure-marker' );
			throw new RuntimeException( 'forced subject failure' );
		} catch ( RuntimeException $error ) {
			$this->assertSame( 'forced subject failure', $error->getMessage() );
		} finally {
			$state->restore();
		}
		$this->assertSame( array( 'skip-wpscan', 'unrelated' ), $this->labels() );
		$this->assertSame( 'DISMISSED', $this->reviews()[0]['state'] );
	}

	public static function cleanupReadFailures(): array {
		return array( 'labels' => array( true ), 'reviews' => array( false ) );
	}

	#[DataProvider( 'cleanupReadFailures' )]
	public function testExhaustedReadRetriesReportFailureAndAllowFinally( bool $fail_labels ): void {
		$state = new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array(), 'test-marker', self::COMMIT );
		$this->approve( 'created by test-marker' );
		$owned = $this->reviews()[0];
		$base = VIPGOCI_GITHUB_BASE_URL . '/repos/automattic/vip-go-ci-testing';
		$labels_url = $base . '/issues/46/labels';
		$reviews_url = $base . '/pulls/46/reviews?per_page=100&page=1';
		$headers = array( 'Authorization: token ' . self::TOKEN, 'X-GitHub-Api-Version: ' . VIPGOCI_GITHUB_API_VERSION );
		if ( $fail_labels ) {
			for ( $attempt = 0; $attempt < 5; $attempt++ ) {
				IntegrationApiMock::expect( 'GET', $labels_url, $headers, array( 'body' => false ) );
			}
			IntegrationApiMock::expect( 'GET', $reviews_url, $headers, array( 'body' => json_encode( array( $owned ) ) ) );
			IntegrationApiMock::expect(
				'PUT', $base . '/pulls/46/reviews/' . $owned['id'] . '/dismissals',
				$headers,
				array( 'body' => '{}' ),
				array( 'message' => 'Dismissing approval created by integration test test-marker' )
			);
			$owned['state'] = 'DISMISSED';
			IntegrationApiMock::expect( 'GET', $reviews_url, $headers, array( 'body' => json_encode( array( $owned ) ) ) );
		} else {
			IntegrationApiMock::expect( 'GET', $labels_url, $headers, array( 'body' => '[]' ) );
			IntegrationApiMock::expect( 'GET', $labels_url, $headers, array( 'body' => '[]' ) );
			for ( $attempt = 0; $attempt < 5; $attempt++ ) {
				IntegrationApiMock::expect( 'GET', $reviews_url, $headers, array( 'body' => false ) );
			}
		}

		$finally_ran = false;
		$error_message = null;
		try {
			try {
				$state->restore();
			} finally {
				$finally_ran = true;
			}
		} catch ( RuntimeException $error ) {
			$error_message = $error->getMessage();
		}
		$resource = $fail_labels ? 'label' : 'review';
		$plural = $fail_labels ? 'labels' : 'reviews';
		$this->assertSame(
			'Could not restore test PR automattic/vip-go-ci-testing#46: ' . $resource .
			' readback: Invalid ' . $plural . ' response for PR #46 page 1',
			$error_message
		);
		$this->assertTrue( $finally_ran );
		HttpApiMock::assert_complete();
	}

	private function expect_api( string $method, string $path, array $response, ?int $remaining = null, array $body = array() ): void {
		$headers = array( 'Authorization: token ' . self::TOKEN );
		if ( 'POST' === $method || 'DELETE' === $method ) {
			$headers[] = 'Content-Type: application/json';
		}
		$headers[] = 'X-GitHub-Api-Version: ' . VIPGOCI_GITHUB_API_VERSION;
		IntegrationApiMock::expect(
			$method, VIPGOCI_GITHUB_BASE_URL . $path, $headers,
			array(
				'status' => 'DELETE' === $method ? 204 : ( 'POST' === $method ? 201 : 200 ),
				'headers' => null === $remaining ? array() : array( 'X-RateLimit-Remaining' => (string) $remaining ),
				'body' => json_encode( $response ),
			),
			$body
		);
	}

	public static function rateLimitedCleanupRequests(): array {
		$cases = array();
		foreach ( array( 'labels', 'reviews', 'add-label', 'remove-label', 'dismiss-review' ) as $request ) {
			foreach ( array( 0, 1 ) as $remaining ) {
				$cases[ $request . '-' . $remaining ] = array( $request, $remaining );
			}
		}
		return $cases;
	}

	#[DataProvider( 'rateLimitedCleanupRequests' )]
	public function testRateLimitedCleanupRequestsAllowFinally( string $request, int $remaining ): void {
		if ( 'add-label' === $request ) {
			$this->add_label( 'tracked-label' );
		}
		$state = new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array( 'tracked-label' ), 'test-marker', self::COMMIT );
		$this->approve( 'created by test-marker' );
		$owned = $this->reviews()[0];
		$base = '/repos/automattic/vip-go-ci-testing';
		$labels = $base . '/issues/46/labels';
		$reviews = $base . '/pulls/46/reviews?per_page=100&page=1';
		$current = 'remove-label' === $request ? array( array( 'name' => 'tracked-label' ) ) : array();
		$this->expect_api( 'GET', $labels, $current, 'labels' === $request ? $remaining : null );
		if ( 'labels' !== $request ) {
			if ( 'add-label' === $request ) {
				$this->expect_api( 'POST', $labels, array(), $remaining, array( 'tracked-label' ) );
			} elseif ( 'remove-label' === $request ) {
				$this->expect_api( 'DELETE', $labels . '/tracked-label', array(), $remaining );
			}
			$baseline = 'add-label' === $request ? array( array( 'name' => 'tracked-label' ) ) : array();
			$this->expect_api( 'GET', $labels, $baseline );
		}
		$this->expect_api( 'GET', $reviews, array( $owned ), 'reviews' === $request ? $remaining : null );
		if ( 'reviews' !== $request ) {
			$this->expect_api(
				'PUT', $base . '/pulls/46/reviews/' . $owned['id'] . '/dismissals', array(),
				'dismiss-review' === $request ? $remaining : null,
				array( 'message' => 'Dismissing approval created by integration test test-marker' )
			);
			$owned['state'] = 'DISMISSED';
			$this->expect_api( 'GET', $reviews, array( $owned ) );
		}

		$error_message = null;
		$finally_ran = false;
		try {
			try {
				$state->restore();
			} finally {
				$finally_ran = true;
			}
		} catch ( RuntimeException $error ) {
			$error_message = $error->getMessage();
		}
		$this->assertTrue( $finally_ran );
		$this->assertNotNull( $error_message );
		$this->assertStringContainsString( 'Could not restore test PR automattic/vip-go-ci-testing#46:', $error_message );
		$this->assertStringContainsString( 'HTTP API rate limit reached', $error_message );
		$this->assertStringContainsString( 'remaining: ' . $remaining, $error_message );
		HttpApiMock::assert_complete();
	}

	public static function rateLimitedSnapshotRequests(): array {
		$cases = array();
		foreach ( array( 'labels', 'user', 'reviews' ) as $request ) {
			foreach ( array( 0, 1 ) as $remaining ) {
				$cases[ $request . '-' . $remaining ] = array( $request, $remaining );
			}
		}
		return $cases;
	}

	#[DataProvider( 'rateLimitedSnapshotRequests' )]
	public function testRateLimitedSnapshotThrowsBeforeMutation( string $request, int $remaining ): void {
		$base = '/repos/automattic/vip-go-ci-testing';
		$this->expect_api( 'GET', $base . '/issues/46/labels', array(), 'labels' === $request ? $remaining : null );
		if ( 'labels' !== $request ) {
			$this->expect_api( 'GET', '/user', array( 'id' => 100, 'login' => 'fixture-bot' ), 'user' === $request ? $remaining : null );
		}
		if ( 'reviews' === $request ) {
			$this->expect_api( 'GET', $base . '/pulls/46/reviews?per_page=100&page=1', array(), $remaining );
		}
		try {
			new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array(), 'test-marker', self::COMMIT );
			$this->fail( 'A rate-limited snapshot must stop setup before any mutation.' );
		} catch ( RuntimeException $error ) {
			$this->assertStringContainsString( 'HTTP API rate limit reached', $error->getMessage() );
			$this->assertStringContainsString( 'remaining: ' . $remaining, $error->getMessage() );
		}
		HttpApiMock::assert_complete();
	}

	public function testFindsTrackedLabelOnSecondPage(): void {
		$base_url = VIPGOCI_GITHUB_BASE_URL . '/repos/automattic/vip-go-ci-testing/issues/46/labels';
		IntegrationApiMock::replaceResponse(
			$base_url,
			array_map( static fn( int $number ): array => array( 'name' => 'unrelated-' . $number ), range( 1, 30 ) )
		);
		IntegrationApiMock::replaceResponse( $base_url . '?page=2', array( array( 'name' => 'skip-wpscan' ) ), false );
		$state = new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array( 'skip-wpscan' ) );
		$this->assertTrue( $state->had_label( 'skip-wpscan' ) );
		$state->restore();
	}

	public function testMalformedReadbackReportsPrAndMutation(): void {
		$state = new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array( 'skip-wpscan' ) );
		IntegrationApiMock::replaceResponse(
			VIPGOCI_GITHUB_BASE_URL . '/repos/automattic/vip-go-ci-testing/issues/46/labels',
			array( 'message' => 'service unavailable' )
		);
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Could not restore test PR automattic/vip-go-ci-testing#46: label readback' );
		$state->restore();
	}

	public function testMalformedReviewReadbackReportsPr(): void {
		$state = new LivePrState( self::OWNER, self::REPO, self::TOKEN, self::PR, array(), 'review-marker', self::COMMIT );
		IntegrationApiMock::replaceResponse(
			VIPGOCI_GITHUB_BASE_URL . '/repos/automattic/vip-go-ci-testing/pulls/46/reviews?per_page=100&page=1',
			array( 'message' => 'service unavailable' )
		);
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Could not restore test PR automattic/vip-go-ci-testing#46: review readback' );
		$state->restore();
	}
}
