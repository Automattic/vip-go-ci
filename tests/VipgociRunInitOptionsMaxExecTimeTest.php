<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunInitOptionsMaxExecTimeTest extends TestCase {
	protected function setUp() :void {
		$this->options = array(
			'max-exec-time' => 100,
		);
	}

	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_run_init_options_max_exec_time
	 */
	public function testRunInitOptionsMaxExecTime() {
		vipgoci_run_init_options_max_exec_time(
			$this->options
		);

		$this->assertSame(
			array(
				'max-exec-time' => 100,
			),
			$this->options
		);
	}
}
