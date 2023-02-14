<?php
/**
 * Helper file.
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

/**
 * Check if there is support for PCNTL functions.
 *
 * @return bool
 */
function vipgoci_unittests_pcntl_supported() :bool {
	if ( function_exists( 'pcntl_fork' ) ) {
		return true;
	}

	return false;
}

