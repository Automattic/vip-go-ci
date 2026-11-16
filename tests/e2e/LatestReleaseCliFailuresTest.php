<?php
/**
 * Exercise release CLI failure paths through offline subprocesses.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\E2E;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Check that release lookup failures never produce a version on stdout.
 */
#[Group( 'mocked-api' )]
final class LatestReleaseCliFailuresTest extends TestCase {
	/**
	 * Skip fixture-only subprocesses before loading their bootstrap in live mode.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		if ( 'true' === getenv( 'VIPGOCI_TESTING_LIVE_API' ) ) {
			$this->markTestSkipped( 'Release CLI fixtures run only in offline mode.' );
		}
		require_once __DIR__ . '/helper/LatestReleaseCliProcess.php';
	}

	/**
	 * Raw release responses that the CLI must reject.
	 *
	 * @return array Fixture arguments keyed by failure type.
	 */
	public static function invalidResponses(): array {
		return array(
			'cURL failure'       => array( array( '--fixture-false' ) ),
			'malformed JSON'     => array( array( '--fixture-body', '{' ) ),
			'empty release list' => array( array( '--fixture-body', '[]' ) ),
			'missing tag'        => array( array( '--fixture-body', '[{}]' ) ),
			'invalid tag'        => array( array( '--fixture-body', '[{"tag_name":"not-a-version"}]' ) ),
		);
	}

	/**
	 * Reject unavailable and invalid responses without printing stdout.
	 *
	 * @param array $arguments Raw fixture arguments.
	 * @return void
	 */
	#[DataProvider( 'invalidResponses' )]
	public function testInvalidResponse( array $arguments ): void {
		$result = LatestReleaseCliProcess::run( __DIR__ . '/helper/LatestReleaseFixtureBootstrap.php', $arguments );
		$this->assertSame( 255, $result['status'], $result['stderr'] );
		$this->assertSame( '', $result['stdout'] );
	}

	/**
	 * Check the same fixture boundary with a valid response.
	 *
	 * @return void
	 */
	public function testValidResponsePrintsOnlyVersion(): void {
		$result = LatestReleaseCliProcess::run(
			__DIR__ . '/helper/LatestReleaseFixtureBootstrap.php',
			array( '--fixture-body', '[{"tag_name":"1.4.2"}]' )
		);
		$this->assertSame( 0, $result['status'], $result['stderr'] );
		$this->assertContains( $result['stdout'], array( '1.4.2', "1.4.2\n" ) );
	}
}
