<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';
require_once __DIR__ . '/../../misc.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if maximum execution time option is
 * parsed correctly.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsMaxExecTimeTest extends TestCase {
	/**
	 * Set up variable.
	 */
	protected function setUp() :void {
		$this->options = array(
			'max-exec-time' => 100,
		);
	}

	/**
	 * Clear variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Check if maximum execution time option is
	 * parsed correctly.
	 *
	 * @covers ::vipgoci_run_init_options_max_exec_time
	 */
	public function testRunInitOptionsMaxExecTime() :void {
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
