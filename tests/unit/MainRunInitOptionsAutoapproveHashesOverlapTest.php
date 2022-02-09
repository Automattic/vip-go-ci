<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/helper/IndicateTestId.php';

require_once __DIR__ . '/../../defines.php';
require_once __DIR__ . '/../../misc.php';
require_once __DIR__ . '/../../main.php';


use PHPUnit\Framework\TestCase;

/**
 * Check if overlapping hashes options lead to error.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsAutoapproveHashesOverlapTest extends TestCase {
	/**
	 * Set up variables and indication.
	 */
	protected function setUp(): void {
		vipgoci_unittests_indicate_test_id( 'MainRunInitOptionsAutoapproveHashesOverlapTest' );

		$this->options = array(
			'hashes-api-url' => 'https://127.0.0.1:1234',
		);
	}

	/**
	 * Clear variables and remove indication.
	 */
	protected function tearDown(): void {
		vipgoci_unittests_remove_indication_for_test_id( 'MainRunInitOptionsAutoapproveHashesOverlapTest' );

		unset( $this->options );
	}

	/**
	 * Check if correct message is printed, emulating exit.
	 *
	 * @covers ::vipgoci_run_init_options_autoapprove_hashes_overlap
	 */
	public function testAutoapproveOverLap() :void {
		$this->options['autoapprove'] = false;

		ob_start();

		vipgoci_run_init_options_autoapprove_hashes_overlap(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		/*
		 * Check if expected string was printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Asking to use --hashes-api-url without --autoapproval set to true, but for hashes-to-hashes functionality to be useful, --autoapprove must be enabled. Otherwise the functionality will not really be used'
		);

		$this->assertNotFalse(
			$printed_data_found
		);
	}


	/**
	 * Check if no message is printed, emulating non-exit.
	 *
	 * @covers ::vipgoci_run_init_options_autoapprove_hashes_overlap
	 */
	public function testAutoapproveNotOverLap() :void {
		$this->options['autoapprove'] = true;

		ob_start();

		vipgoci_run_init_options_autoapprove_hashes_overlap(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		/*
		 * Check if expected string was printed.
		 */
		$printed_data_found = strpos(
			$printed_data,
			'Asking to use --hashes-api-url without --autoapproval set to true, but for hashes-to-hashes functionality to be useful, --autoapprove must be enabled. Otherwise the functionality will not really be used'
		);

		$this->assertFalse(
			$printed_data_found
		);
	}
}
