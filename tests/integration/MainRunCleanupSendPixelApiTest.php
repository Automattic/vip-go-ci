<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

require_once __DIR__ . '/IncludesForTests.php';

require_once __DIR__ . '/../unit/helper/IndicateTestId.php';

use PHPUnit\Framework\TestCase;

/**
 * Test the vipgoci_run_cleanup_send_pixel_api() function.
 *
 * Should not call a HTTP endpoint as no data
 * should be there to send.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunCleanupSendPixelApiTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Counter report array.
	 *
	 * @var $counter_report
	 */
	private array $counter_report = array();

	/**
	 * Set up all variables.
	 */
	protected function setUp(): void {
		$this->options = array(
			'pixel-api-groupprefix' => 'mystatistics',
			'repo-name'             => 'test-repo',
		);

		$this->counter_report = array(
			// Empty, nothing to report.
		);
	}

	/**
	 * Clean up all variables.
	 */
	protected function tearDown(): void {
		unset( $this->options );
		unset( $this->counter_report );
	}

	/**
	 * Check if correct message is printed,
	 * indicating sending statistics was attempted.
	 *
	 * @covers ::vipgoci_run_cleanup_send_pixel_api
	 */
	public function testSendPixelApiSuccess() :void {
		$this->options['pixel-api-url'] = 'https://127.0.0.1:1234';

		ob_start();

		vipgoci_run_cleanup_send_pixel_api(
			$this->options,
			$this->counter_report
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		/*
		 * Check if expected string was printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Sending statistics to pixel API service'
		);

		$this->assertNotFalse(
			$printed_data_found
		);

		/*
		 * Check if non-expected string was not printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Not sending data to pixel API due to missing configuration options'
		);

		$this->assertFalse(
			$printed_data_found
		);
	}

	/**
	 * Check if correct message is printed,
	 * indicating sending statistics was not attempted.
	 *
	 * @covers ::vipgoci_run_cleanup_send_pixel_api
	 */
	public function testSendPixelApiFailure() :void {
		// Skip option, leads to sending stats is not attempted.
		unset( $this->options['pixel-api-url'] );

		ob_start();

		vipgoci_run_cleanup_send_pixel_api(
			$this->options,
			$this->counter_report
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		/*
		 * Check if non-expected string was not printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Sending statistics to pixel API service'
		);

		$this->assertFalse(
			$printed_data_found
		);

		/*
		 * Check if expected string was printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Not sending data to pixel API due to missing configuration options'
		);

		$this->assertNotFalse(
			$printed_data_found
		);
	}
}
