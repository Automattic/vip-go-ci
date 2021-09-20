<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociHelpPrintTest extends TestCase {
	/**
	 * @covers ::vipgoci_help_print
	 */
	public function testHelpPrint() {
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
		}

		else if ( 0 > $tmp_pos ) {
			$tmp_pos = false;
		}

		else if ( 0 < $tmp_pos ) {
			$tmp_pos = true;
		}

		$this->assertTrue( $tmp_pos, 'vipgoci_help_print() does not print help message correctly' );
	}
}
