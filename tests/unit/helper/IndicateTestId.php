<?php
/**
 * Helper file.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

define( 'VIPGOCI_UNIT_TESTS_TEST_ID_KEY', 'vipgoci_unittests_test_ids' );

/**
 * Indicate that we are running a particular test.
 *
 * @param string $test_id Test ID to use.
 *
 * @return void Does not return a value.
 */
function vipgoci_unittests_indicate_test_id( string $test_id ): void {
	$GLOBALS[ VIPGOCI_UNIT_TESTS_TEST_ID_KEY ][ $test_id ] = true;
}


/**
 * Determine if we are running a particular test.
 *
 * @param string $test_id Test ID to use.
 *
 * @return bool True if we are running the test indicated in $test_id, false
 * otherwise.
 */
function vipgoci_unittests_check_indication_for_test_id(
	string $test_id
): bool {
	if (
		( ( isset( $GLOBALS[ VIPGOCI_UNIT_TESTS_TEST_ID_KEY ][ $test_id ] ) ) ) &&
		( true === $GLOBALS[ VIPGOCI_UNIT_TESTS_TEST_ID_KEY ][ $test_id ] )
	) {
		return true;
	}

	return false;
}

/**
 * Remove indication of running a particular test.
 *
 * @param string $test_id Test ID to use.
 *
 * @return void Does not return a value.
 */
function vipgoci_unittests_remove_indication_for_test_id(
	string $test_id
): void {
	$GLOBALS[ VIPGOCI_UNIT_TESTS_TEST_ID_KEY ][ $test_id ] = false;

	unset( $GLOBALS[ VIPGOCI_UNIT_TESTS_TEST_ID_KEY ][ $test_id ] );
}
