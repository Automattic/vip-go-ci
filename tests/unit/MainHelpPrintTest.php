<?php
/**
 * Test function vipgoci_help_print().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Check if help message is printed.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainHelpPrintTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../defines.php';
	}

	/**
	 * Checks if help message is printed.
	 *
	 * @covers ::vipgoci_help_print
	 *
	 * @return void
	 */
	public function testHelpPrint() :void {
		/*
		 * Call function and get output
		 * in variable.
		 */
		ob_start();

		vipgoci_help_print();

		$help_str = ob_get_clean();

		/*
		 * Ensure help message is in
		 * variable.
		 */
		$tmp_pos = strpos(
			$help_str,
			'--local-git-repo=FILE'
		);

		if ( false === $tmp_pos ) {
			$tmp_pos = false;
		} elseif ( 0 > $tmp_pos ) {
			$tmp_pos = false;
		} elseif ( 0 < $tmp_pos ) {
			$tmp_pos = true;
		}

		$this->assertTrue( $tmp_pos, 'vipgoci_help_print() does not print help message correctly' );
	}
}
