<?php
/**
 * Call vipgoci_run() when not running a
 * unit-test.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

if (
	(
		( ! defined( 'VIPGOCI_UNIT_TESTING' ) ) ||
		( false === VIPGOCI_UNIT_TESTING )
	)
	&&
	(
		( ! defined( 'VIPGOCI_INCLUDED' ) ) ||
		( false === VIPGOCI_INCLUDED )
	)
) {
	/*
	 * 'main()' called
	 */
	$ret = vipgoci_run();

	exit( $ret );
}
