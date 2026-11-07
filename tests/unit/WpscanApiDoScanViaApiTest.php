<?php
/**
 * Test vipgoci_wpscan_do_scan_via_api with offline HTTP responses.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Vipgoci\Tests\Common\HttpApiMock;
use Vipgoci\Tests\Common\MockedApiTestCase;

require_once __DIR__ . '/../common/MockedApiTestCase.php';

/**
 * Exercise the production client without credentials or network access.
 */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_wpscan_do_scan_via_api' )]
final class WpscanApiDoScanViaApiTest extends MockedApiTestCase {
	/**
	 * Load the WPScan client with the mocked transport.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../../wpscan-api.php';
	}

	/**
	 * Both addon types must send the token and preserve the vulnerability report.
	 *
	 * @return void
	 */
	public function testPluginAndThemeResponses(): void {
		$slug   = 'example-addon';
		$report = array(
			$slug => array(
				'friendly_name'   => 'Example addon',
				'latest_version'  => '2.0',
				'last_updated'    => '2026-01-01T00:00:00Z',
				'vulnerabilities' => array(
					array(
						'id'       => 'fixture-vulnerability',
						'fixed_in' => '2.0',
					),
				),
			),
		);
		foreach ( array(
			VIPGOCI_ADDON_PLUGIN => 'plugins',
			VIPGOCI_ADDON_THEME  => 'themes',
		) as $type => $path ) {
			HttpApiMock::expect( 'GET', VIPGOCI_WPSCAN_API_BASE_URL . '/' . $path . '/' . $slug, array( 'wpscan_token' => 'test-token' ), json_encode( $report ) );
			$this->assertSame( $report, vipgoci_wpscan_do_scan_via_api( $slug, $type, 'test-token' ) );
		}
	}

	/**
	 * Transport failures, malformed JSON and incomplete reports must be rejected.
	 *
	 * @return void
	 */
	public function testUnusableResponses(): void {
		foreach ( array( null, 'not json', '{}', '{"example-addon":{"friendly_name":"Example addon"}}' ) as $response ) {
			HttpApiMock::expect( 'GET', VIPGOCI_WPSCAN_API_BASE_URL . '/plugins/example-addon', array( 'wpscan_token' => 'test-token' ), $response );
			$this->assertNull( vipgoci_wpscan_do_scan_via_api( 'example-addon', VIPGOCI_ADDON_PLUGIN, 'test-token' ) );
		}
	}
}
