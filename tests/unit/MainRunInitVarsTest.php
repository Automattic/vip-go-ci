<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../defines.php';

use PHPUnit\Framework\TestCase;

/**
 * Test if default variables are set correctly.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitVarsTest extends TestCase {
	/**
	 * Test if default variables are set correctly.
	 *
	 * @covers ::vipgoci_run_init_vars
	 */
	public function testRunInitVars() :void {
		list(
			$startup_time,
			$results,
			$options,
			$options_recognized,
			$prs_implicated
		) = vipgoci_run_init_vars();

		$this->assertTrue(
			( is_numeric( $startup_time ) )
			&&
			( 0 < $startup_time ),
			'Invalid value for $startup_time variable'
		);

		$this->assertTrue(
			( isset(
				$results['issues']
			) )
			&&
			( ! empty(
				$results['stats']
			) ),
			'Invalid value for $results variable'
		);

		/*
		 * Cannot alter command-line arguments after execution,
		 * so cannot influence what getopt() returns and thus
		 * cannot alter $options.
		 */
		unset( $options );

		$this->assertTrue(
			in_array( 'help', $options_recognized, true ),
			'\'help\' not found in $options_recognized variable'
		);

		$this->assertNull(
			$prs_implicated,
			'$prs_implicated variable is not null'
		);
	}
}
