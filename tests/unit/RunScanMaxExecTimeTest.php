<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . '/helper/IndicateTestId.php' );
require_once( __DIR__ . '/helper/CheckPcntlSupport.php' );

require_once( __DIR__ . '/../../defines.php' );
require_once( __DIR__ . '/../../misc.php' );
require_once( __DIR__ . '/../../main.php' );
require_once( __DIR__ . '/../../other-web-services.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class RunScanMaxExecTimeTest extends TestCase {
	var $options = array(
		'repo-owner'    => 'test-owner',
		'repo-name'     => 'test-repo',
		'commit'        => '0000123ABCDE',
	);

	protected function setUp(): void {
		/*
		 * Indicate that this particular test is running,
		 * needed so that vipgoci_sysexit() can return
		 * instead of exiting. See the function itself.
		 */
		vipgoci_unittests_indicate_test_id( 'RunScanMaxExecTimeTest' );
	}

	protected function tearDown(): void {
		/*
	 	 * We are no longer running the test,
		 * remove the indication.
		 */
		vipgoci_unittests_remove_indication_for_test_id( 'RunScanMaxExecTimeTest' );
	}

	/**
	 * Check if a particular string was outputted
	 * indicating that alarm was raised.
	 * 
	 * @covers ::vipgoci_run_scan_max_exec_time
	 */
	public function testRunScanMaxExecTimeLargerThanZero() {
		if ( ! vipgoci_unittests_pcntl_supported() ) {
			$this->markTestSkipped(
				'PCNTL support is missing'
			);

			return;
		}

		$this->options['max-exec-time'] = 5;

		ob_start();

		vipgoci_run_scan_max_exec_time( $this->options, time() );

		// Wait for 2 seconds, alarm should not trigger meanwhile
		sleep( 2 );

		// String should not have been printed
		$printed_data = ob_get_contents();

		ob_end_clean();

		// Check if expected string was not printed.
		$printed_data_found = strpos(
			$printed_data,
			$this->options['commit']
		);

		$this->assertFalse(
			$printed_data_found
		);

		ob_start();

		// Wait for 10 seconds, alarm should trigger meanwhile
		sleep( 10 );

		// String should have been printed
		$printed_data = ob_get_contents();

		ob_end_clean();

		// Check if expected string was printed.
		$printed_data_found = strpos(
			$printed_data,
			$this->options['commit']
		);

		$this->assertTrue(
			$printed_data_found !== false
		);
	}
	/**
	 * Check if a particular string was not outputted
	 * indicating that alarm was not raised.
	 * 
	 * @covers ::vipgoci_run_scan_max_exec_time
	 */
	public function testRunScanMaxExecTimeIsZero() {
		if ( ! vipgoci_unittests_pcntl_supported() ) {
			$this->markTestSkipped(
				'PCNTL support is missing'
			);

			return;
		}

		$this->options['max-exec-time'] = 0;

		ob_start();

		vipgoci_run_scan_max_exec_time( $this->options, time() );

		// Wait for 10 seconds, alarm should not trigger meanwhile
		sleep( 10 );

		// String should not have been printed
		$printed_data = ob_get_contents();

		ob_end_clean();

		// Check if expected string was printed.
		$printed_data_found = strpos(
			$printed_data,
			$this->options['commit']
		);

		$this->assertFalse(
			$printed_data_found
		);
	}
}
