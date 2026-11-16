<?php
/**
 * Fixture checks for restoration of one test PR's state.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Vipgoci\Tests\Common\IntegrationApiMock;

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
		$this->approve( 'existing approval' );
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
