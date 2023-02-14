<?php
/**
 * Test vipgoci_run_init_options_debug().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test if debug level is correctly set.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsDebugTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set up variables.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../options.php';

		$this->options = array(
			'debug-level' => '1',
		);

		global $vipgoci_debug_level;

		$vipgoci_debug_level = -1;
	}

	/**
	 * Clear variables.
	 *
	 * @return void
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
	 *
	 * @return void
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
