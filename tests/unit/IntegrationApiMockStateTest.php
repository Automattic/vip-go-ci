<?php
/**
 * Verify mutations preserve fixture identity and reject unknown targets.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vipgoci\Tests\Common\IntegrationApiMock;

/**
 * Exercise fixture state independently of the HTTP transport.
 */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
final class IntegrationApiMockStateTest extends TestCase {
	/** Load the fixture service in a fresh isolated worker. */
	protected function setUp(): void {
		require_once __DIR__ . '/../common/helper/IntegrationApiMock.php';
	}

	/** IDs remain unique across PRs and after deleting and replacing comments. */
	public function testCommentIdentitySurvivesDeletionAndCreation(): void {
		$base   = 'https://api.github.com/repos/automattic/vip-go-ci-testing';
		$first  = json_decode( IntegrationApiMock::request( 'POST', $base . '/issues/32/comments', null, array( 'body' => 'first' ) ), true );
		$second = json_decode( IntegrationApiMock::request( 'POST', $base . '/issues/44/comments', null, array( 'body' => 'second' ) ), true );
		$this->assertGreaterThan( $first['id'], $second['id'] );

		IntegrationApiMock::request( 'DELETE', $base . '/issues/comments/' . $first['id'], null );
		$this->assertSame( array(), json_decode( IntegrationApiMock::request( 'GET', $base . '/issues/32/comments?page=1&per_page=100', null ), true ) );
		$this->assertSame( array( $second ), json_decode( IntegrationApiMock::request( 'GET', $base . '/issues/44/comments?page=1&per_page=100', null ), true ) );

		$replacement = json_decode( IntegrationApiMock::request( 'POST', $base . '/issues/32/comments', null, array( 'body' => 'replacement' ) ), true );
		$this->assertGreaterThan( $second['id'], $replacement['id'] );
	}

	/** An unregistered comment must never be reported as successfully deleted. */
	public function testUnknownCommentDeletionFails(): void {
		$this->expectException( AssertionFailedError::class );
		$this->expectExceptionMessage( 'Unknown fixture comment ID.' );
		IntegrationApiMock::request( 'DELETE', 'https://api.github.com/repos/automattic/vip-go-ci-testing/issues/comments/999999999', null );
	}

	/** A known PR does not make an unknown review a valid dismissal target. */
	public function testUnknownReviewDismissalFails(): void {
		$this->expectException( AssertionFailedError::class );
		$this->expectExceptionMessage( 'Unknown fixture review ID.' );
		IntegrationApiMock::request( 'PUT', 'https://api.github.com/repos/automattic/vip-go-ci-testing/pulls/46/reviews/999999999/dismissals', null );
	}

	/** A registered review can still be dismissed and subsequently read. */
	public function testKnownReviewDismissalUpdatesState(): void {
		$url    = 'https://api.github.com/repos/automattic/vip-go-ci-testing/pulls/46/reviews';
		$review = json_decode(
			IntegrationApiMock::request(
				'POST',
				$url,
				null,
				array(
					'event'     => 'APPROVE',
					'body'      => 'approved',
					'commit_id' => 'fixture-commit',
				)
			),
			true
		);
		IntegrationApiMock::request( 'PUT', $url . '/' . $review['id'] . '/dismissals', null );
		$reviews = json_decode( IntegrationApiMock::request( 'GET', $url . '?per_page=100&page=1', null ), true );
		$this->assertCount( 1, $reviews );
		$this->assertSame( $review['id'], $reviews[0]['id'] );
		$this->assertSame( 'DISMISSED', $reviews[0]['state'] );
	}
}
