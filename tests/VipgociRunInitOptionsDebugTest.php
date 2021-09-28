<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunInitOptionsDebugTest extends TestCase {
	protected function setUp() :void {
		$this->options = array(
			'debug-level' => '1',
		);

		global $vipgoci_debug_level;

		$vipgoci_debug_level = -1;
	}

	protected function tearDown() :void {
		global $vipgoci_debug_level;

		unset( $this->options );

		unset( $vipgoci_debug_level );
	}

	/**
	 * @covers ::vipgoci_run_init_options_debug
	 */
	public function testRunInitOptionsDebugDefault() {
		vipgoci_run_init_options_debug(
			$this->options
		);

		$this->assertSame(
			array(
				'debug-level'         => 1,
			),
			$this->options
		);

		global $vipgoci_debug_level;

		$this->assertSame(
			1,
			$vipgoci_debug_level
		);
	}
}
