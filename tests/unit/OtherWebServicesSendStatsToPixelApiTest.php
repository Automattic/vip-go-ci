<?php
/**
 * Test vipgoci_send_stats_to_pixel_api().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OtherWebServicesSendStatsToPixelApiTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../other-web-services.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/helper/OtherWebServicesSendStatsToPixelApi.php';
	}

	/**
	 * Emulate sending statistics to Pixel API.
	 *
	 * @covers ::vipgoci_send_stats_to_pixel_api
	 *
	 * @return void
	 */
	public function testSendStatistics(): void {
		$counter_report = array(
			'stat1' => 50,
			'stat2' => 30,
			'stat3' => 10,
			'stat4' => 2,
			'stat5' => 0,
			'stat6' => 1,
			'stat7' => -100,
		);


		ob_start();

		vipgoci_send_stats_to_pixel_api(
			'https://127.0.0.1',
			array(
				'mygroup1' => array(
					'stat1',
					'stat2',
					'stat3',
				),
				'mygroup2' => array(
					'stat3',
					'stat4',
					'stat5',
					'stat7',
					'statinvalid',
				),
			),
			$counter_report
		);

		$printed_data = ob_get_contents();
		ob_end_clean();

		$this->assertStringContainsString(
			'"Sending statistics to pixel API service"',
			$printed_data
		);

		$this->assertStringContainsString(
			'"Finished sending statistics to pixel API service"',
			$printed_data
		);

		$this->assertStringContainsString(
			'"https:\/\/127.0.0.1?v=wpcom-no-pv&x_mygroup1\/stat1=50"',
			$printed_data
		);

		$this->assertStringContainsString(
			'"https:\/\/127.0.0.1?v=wpcom-no-pv&x_mygroup1\/stat2=30"',
			$printed_data
		);

		$this->assertStringContainsString(
			'"https:\/\/127.0.0.1?v=wpcom-no-pv&x_mygroup1\/stat3=10"',
			$printed_data
		);

		$this->assertStringContainsString(
			'"https:\/\/127.0.0.1?v=wpcom-no-pv&x_mygroup2\/stat3=10"',
			$printed_data
		);

		$this->assertStringContainsString(
			'"https:\/\/127.0.0.1?v=wpcom-no-pv&x_mygroup2\/stat4=2"',
			$printed_data
		);

		$this->assertStringNotContainsString(
			'stat5=',
			$printed_data
		);

		$this->assertStringNotContainsString(
			'stat6=',
			$printed_data
		);

		$this->assertStringNotContainsString(
			'stat7=',
			$printed_data
		);

		$this->assertStringNotContainsString(
			'statinvalid=',
			$printed_data
		);
	}
}
