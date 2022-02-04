<?php

declare( strict_types=1 );

/**
 * Check if debug mode is on, returns true if so,
 * else returns false.
 *
 * @return bool
 */
function vipgoci_unittests_debug_mode_on() :bool {
	/*
	 * Detect if phpunit was started with
	 * debug-mode on.
	 */

	if (
		( in_array( '-v', $_SERVER['argv'] ) ) ||
		( in_array( '-vv', $_SERVER['argv'] ) ) ||
		( in_array( '-vvv', $_SERVER['argv'] ) ) ||
		( in_array( '--debug', $_SERVER['argv'] ) )
	) {
		return true;
	}

	return false;
}

/**
 * Start suppressing output if in debug mode.
 * 
 * @return void
 */
function vipgoci_unittests_output_suppress() :void {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		ob_start();
	}
}

/**
 * Get and return output if in debug mode. Else
 * return empty string.
 * 
 * @return string
 */
function vipgoci_unittests_output_get() :string {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		return ob_get_flush();
	}

	return '';
}

/**
 * Stop suppressing output if in debug mode.
 * 
 * @return void
 */
function vipgoci_unittests_output_unsuppress() :void {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		ob_end_clean();
	}
}
