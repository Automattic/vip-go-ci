<?php

declare( strict_types=1 );

function vipgoci_unittests_debug_mode_on() {
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

function vipgoci_unittests_output_suppress() {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		ob_start();
	}
}

function vipgoci_unittests_output_get() {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		return ob_get_flush();
	}

	return '';
}

function vipgoci_unittests_output_unsuppress() {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		ob_end_clean();
	}
}

