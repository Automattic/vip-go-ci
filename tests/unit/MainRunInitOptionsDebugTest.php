<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';

use PHPUnit\Framework\TestCase;

/**
 * Test if debug level is correctly set.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsDebugTest extends TestCase {
	/**
	 * Set up variables.
	 */
	protected function setUp() :void {
		$this->options = array(
			'debug-level' => '1',
		);

		global $vipgoci_debug_level;

		$vipgoci_debug_level = -1;
	}

	/**
	 * Clear variables.
	 */
	protected function tearDown() :void {
		global $vipgoci_debug_level;

		unset( $this->options );

		unset( $vipgoci_debug_level );
	}

	/**
	 * Test if debug level is set correctly.
	 *
	 * @covers ::vipgoci_run_init_options_debug
	 */
	public function testRunInitOptionsDebugDefault() :void {
		vipgoci_run_init_options_debug(
			$this->options
		);

		$this->assertSame(
			array(
				'debug-level' => 1,
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
