<?php
/**
 * Verify metadata-selected support messages at the outbound HTTP boundary.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Vipgoci\Tests\Common\HttpApiMock;
use Vipgoci\Tests\Common\IntegrationApiMock;

/** Exercise the real matcher, eligibility checks and submission code offline. */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_report_submit_pr_generic_support_comment' )]
#[CoversFunction( 'vipgoci_repo_meta_api_data_match' )]
final class GenericSupportCommentSelectionTest extends TestCase {
	/** Whether this test initialized its offline transport and cache. */
	private bool $initialized = false;

	/** Load the fixture transport without reading secrets or contacting services. */
	protected function setUp(): void {
		if ( 'true' === getenv( 'VIPGOCI_TESTING_LIVE_API' ) ) {
			$this->markTestSkipped( 'Support selection fixtures run only offline.' );
		}
		require_once __DIR__ . '/IncludesForTests.php';
		require_once __DIR__ . '/../common/helper/HttpApiMock.php';
		HttpApiMock::reset();
		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		$GLOBALS['vipgoci_debug_level']        = -1;
		$GLOBALS['vipgoci_curl_mock_requests'] = array();
		$this->initialized                     = true;
	}

	/** Require every queued request to be consumed and clear test-owned state. */
	protected function tearDown(): void {
		if ( ! $this->initialized ) {
			return;
		}
		try {
			HttpApiMock::assert_complete();
		} finally {
			HttpApiMock::reset();
			vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		}
	}

	/** @return array Metadata and selected-message eligibility controls. */
	public static function selectionCases(): array {
		return array(
			'second message selected'           => array( 'enterprise', 'trunk', false, array(), true ),
			'first message is not a duplicate'  => array( 'enterprise', 'trunk', false, array( array( 'body' => 'Basic support' ) ), true ),
			'selected message already exists'   => array( 'enterprise', 'trunk', false, array( array( 'body' => 'Enterprise support' ) ), false ),
			'no metadata match'                 => array( 'unknown', 'trunk', false, array(), false ),
			'selected branch excluded'          => array( 'enterprise', 'other', false, array(), false ),
			'selected draft policy excludes PR' => array( 'enterprise', 'trunk', true, array(), false ),
		);
	}

	/**
	 * Assert the selected string and target, rather than any-message prefixes.
	 *
	 * @param string $tier Metadata value.
	 * @param string $branch Target branch.
	 * @param bool   $draft Whether the PR is a draft.
	 * @param array  $comments Existing comments.
	 * @param bool   $post Whether to submit the selected message.
	 */
	#[DataProvider( 'selectionCases' )]
	public function testMetadataSelectsExactOutboundMessage( string $tier, string $branch, bool $draft, array $comments, bool $post ): void {
		$options = array(
			'repo-owner'                                 => 'fixture-owner',
			'repo-name'                                  => 'fixture-repo',
			'token'                                      => 'fixture-gh-token',
			'commit'                                     => 'fixture-head',
			'repo-meta-api-base-url'                     => 'https://meta.fixture.invalid',
			'repo-meta-api-user-id'                      => 'fixture-user',
			'repo-meta-api-access-token'                 => 'fixture-token',
			'post-generic-pr-support-comments'           => true,
			'post-generic-pr-support-comments-string'    => array(
				1 => 'Basic support',
				2 => 'Enterprise support',
			),
			'post-generic-pr-support-comments-repo-meta-match' => array(
				1 => array( 'support_tier' => array( 'basic' ) ),
				2 => array( 'support_tier' => array( 'enterprise' ) ),
			),
			'post-generic-pr-support-comments-branches'  => array(
				1 => array( 'any' ),
				2 => array( 'trunk' ),
			),
			'post-generic-pr-support-comments-on-drafts' => array(
				1 => true,
				2 => false,
			),
			'post-generic-pr-support-comments-skip-if-label-exists' => array(
				1 => '',
				2 => '',
			),
		);
		IntegrationApiMock::expect(
			'GET',
			'https://meta.fixture.invalid/v1/sites?active=1&page=1&pagesize=20&source_repo=fixture-owner%2Ffixture-repo',
			array( 'API-User-ID: fixture-user', 'Access-Token: fixture-token' ),
			array(
				'body' => json_encode(
					array(
						'status' => 'ok',
						'data'   => array( array( 'support_tier' => $tier ) ),
					)
				),
			)
		);
		$url     = VIPGOCI_GITHUB_BASE_URL . '/repos/fixture-owner/fixture-repo/issues/42/comments';
		$headers = array( 'Authorization: token fixture-gh-token', 'X-GitHub-Api-Version: ' . VIPGOCI_GITHUB_API_VERSION );
		if ( 'enterprise' === $tier && 'trunk' === $branch && ! $draft ) {
			IntegrationApiMock::expect(
				'GET',
				VIPGOCI_GITHUB_BASE_URL . '/repos/fixture-owner/fixture-repo/pulls/42',
				$headers,
				array( 'body' => json_encode( array(
					'head'      => array( 'sha' => 'fixture-head' ),
					'base'      => array( 'sha' => 'fixture-base' ),
					'additions' => 1,
					'deletions' => 0,
				) ) )
			);
			IntegrationApiMock::expect( 'GET', $url . '?page=1&per_page=100', $headers, array( 'body' => json_encode( $comments ) ) );
		}
		if ( $post ) {
			IntegrationApiMock::expect(
				'POST',
				$url,
				array( 'Authorization: token fixture-gh-token', 'Content-Type: application/json', 'X-GitHub-Api-Version: ' . VIPGOCI_GITHUB_API_VERSION ),
				array(
					'status' => 201,
					'body'   => '{"id":7}',
				),
				array( 'body' => "Enterprise support\n\r" )
			);
		}
		$pr = (object) array(
			'number' => 42,
			'base'   => (object) array( 'ref' => $branch ),
			'draft'  => $draft,
		);
		vipgoci_report_submit_pr_generic_support_comment( $options, array( $pr ) );
		$posts = array_values( array_filter( $GLOBALS['vipgoci_curl_mock_requests'], static fn( $request ) => 'POST' === $request['method'] ) );
		$this->assertCount( $post ? 1 : 0, $posts );
		if ( $post ) {
			$this->assertSame( $url, $posts[0]['url'] );
			$this->assertSame( array( 'body' => "Enterprise support\n\r" ), json_decode( $posts[0]['options'][ CURLOPT_POSTFIELDS ], true ) );
		}
	}
}
