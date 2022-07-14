<?php
/**
 * Test vipgoci_wpscan_do_scan_via_api() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WpscanApiDoScanViaApiTest extends TestCase {
	/**
	 * WPScan API options.
	 *
	 * @var $wpscan_options
	 */
	private array $wpscan_options = array(
		'plugin-slug' => null,
		'theme-slug'  => null,
	);

	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'wpscan-api-scan',
			$this->wpscan_options
		);

		$this->options = $this->wpscan_options;

		$this->options['wpscan-api-token'] =
			vipgoci_unittests_get_config_value(
				'wpscan-api-scan',
				'access-token',
				true // Fetch from secrets file.
			);
	}

	/**
	 * Tear down function. Clear variables.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->wpscan_options );
		unset( $this->options );
	}

	/**
	 * Ask WPScan API for information about a plugin.
	 *
	 * @covers ::vipgoci_wpscan_do_scan_via_api
	 *
	 * @return void
	 */
	public function testWpscanDoScanPluginViaApi(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$actual_results = vipgoci_wpscan_do_scan_via_api(
			$this->options['plugin-slug'],
			VIPGOCI_WPSCAN_PLUGIN,
			VIPGOCI_WPSCAN_API_BASE_URL,
			$this->options['wpscan-api-token']
		);

		$this->assertNotEmpty(
			$actual_results
		);

		$this->assertNotEmpty(
			$actual_results[ $this->options['plugin-slug'] ]
		);

		$this->assertNotEmpty(
			$actual_results[ $this->options['plugin-slug'] ]['friendly_name']
		);

		$this->assertNotEmpty(
			$actual_results[ $this->options['plugin-slug'] ]['latest_version']
		);

		$this->assertNotEmpty(
			$actual_results[ $this->options['plugin-slug'] ]['last_updated']
		);

		$this->assertTrue(
			isset( $actual_results[ $this->options['plugin-slug'] ]['vulnerabilities'] )
		);
	}

	/**
	 * Ask WPScan API for information about a theme.
	 *
	 * @covers ::vipgoci_wpscan_do_scan_via_api
	 *
	 * @return void
	 */
	public function testWpscanDoScanThemeViaApi(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$actual_results = vipgoci_wpscan_do_scan_via_api(
			$this->options['theme-slug'],
			VIPGOCI_WPSCAN_THEME,
			VIPGOCI_WPSCAN_API_BASE_URL,
			$this->options['wpscan-api-token']
		);

		$this->assertNotEmpty(
			$actual_results
		);

		$this->assertNotEmpty(
			$actual_results[ $this->options['theme-slug'] ]
		);

		$this->assertNotEmpty(
			$actual_results[ $this->options['theme-slug'] ]['friendly_name']
		);

		$this->assertNotEmpty(
			$actual_results[ $this->options['theme-slug'] ]['latest_version']
		);

		$this->assertNotEmpty(
			$actual_results[ $this->options['theme-slug'] ]['last_updated']
		);

		$this->assertTrue(
			isset( $actual_results[ $this->options['theme-slug'] ]['vulnerabilities'] )
		);
	}

	/**
	 * Make an invalid request to WPScan API.
	 *
	 * @covers ::vipgoci_wpscan_do_scan_via_api
	 *
	 * @return void
	 */
	public function testWpscanDoScanViaApiInvalid(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'wpscan-api-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$actual_results = vipgoci_wpscan_do_scan_via_api(
			'my-invalid-theme',
			VIPGOCI_WPSCAN_THEME,
			VIPGOCI_WPSCAN_API_BASE_URL,
			'invalid-token'
		);

		$this->assertNull(
			$actual_results
		);
	}
}
