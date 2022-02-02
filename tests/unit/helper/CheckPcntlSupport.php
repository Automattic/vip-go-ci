<?php

declare( strict_types=1 );

/*
 * Check if there is support for PCNTL functions.
 */
function vipgoci_unittests_pcntl_supported() {
	if ( function_exists( 'pcntl_fork' ) ) {
		return true;
	}

	return false;
}

