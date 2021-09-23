<?php

/**
 * @todo convert this into a test helper
 */

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


/*
 * Check for expected data in IRC queue.
 *
 * @param string $str_expected String to look for in the IRC queue.
 *
 * @return bool True if something was found, false if not.
 */
function vipgoci_unittests_check_irc_api_alert_queue(
	string $str_expected
): bool {
	$found = false;

	$irc_msg_queue = vipgoci_irc_api_alert_queue( null, true );

	foreach( $irc_msg_queue as $irc_msg_queue_item ) {
		if ( false !== strpos(
				$irc_msg_queue_item,
				$str_expected
			) ) {
			$found = true;
		}
	}

	return $found;
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
