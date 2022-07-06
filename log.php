<?php
/**
 * Functions relating to logging for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Log information to the console.
 * Will include timestamp, and any debug-data
 * our caller might pass us.
 *
 * @param string $str         Log message.
 * @param array  $debug_data  Debug data accompanying the log message.
 * @param int    $debug_level Debug level of the message.
 * @param bool   $irc         If to log to IRC.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_log(
	string $str,
	array $debug_data = array(),
	int $debug_level = 0,
	bool $irc = false
) :void {
	global $vipgoci_debug_level;

	/*
	 * Determine if to log the message; if
	 * debug-level of the message is not high
	 * enough compared to the debug-level specified
	 * to be the threshold, do not print it, but
	 * otherwise, do print it,
	 */

	if ( $debug_level > $vipgoci_debug_level ) {
		return;
	}

	echo '[ ' . gmdate( 'c' ) . ' GMT -- ' . (int) $debug_level . ' ]  ' .
		json_encode(
			$str,
			JSON_PRETTY_PRINT
		) .
		'; ' .
		print_r(
			json_encode(
				$debug_data,
				JSON_PRETTY_PRINT
			),
			true
		) .
		PHP_EOL;

	/*
	 * Send to IRC API as well if asked
	 * to do so. Include debugging information as well.
	 */
	if ( true === $irc ) {
		vipgoci_irc_api_alert_queue(
			$str .
				'; ' .
				print_r(
					json_encode(
						$debug_data
					),
					true
				)
		);
	}
}

/**
 * Exit program, using vipgoci_log() to print a
 * message before doing so.
 *
 * @param string $str         Log message to print before exiting.
 * @param array  $debug_data  Debug data to print along with log message.
 * @param int    $exit_status Exit status of program to use.
 * @param bool   $irc         Whether to send log message to IRC or not.
 *
 * @return void|int Does not return when running normally, will return
 *                  an integer value when running in unit-test mode.
 */
function vipgoci_sysexit(
	string $str,
	array $debug_data = array(),
	int $exit_status = VIPGOCI_EXIT_USAGE_ERROR,
	bool $irc = false
) {
	if ( VIPGOCI_EXIT_USAGE_ERROR === $exit_status ) {
		$str = 'Usage: ' . $str;
	}

	vipgoci_log(
		$str,
		$debug_data,
		0,
		$irc
	);

	/*
	 * If running certain unit-tests, return
	 * with exit status.
	 */
	if (
		( function_exists( 'vipgoci_unittests_check_indication_for_test_id' ) ) &&
		(
			( vipgoci_unittests_check_indication_for_test_id( 'LogSysExitTest' ) ) ||
			( vipgoci_unittests_check_indication_for_test_id( 'MiscSetMaximumExecTimeTest' ) ) ||
			( vipgoci_unittests_check_indication_for_test_id( 'MainRunScanSkipExecutionTest' ) ) ||
			( vipgoci_unittests_check_indication_for_test_id( 'MainRunScanMaxExecTimeTest' ) ) ||
			( vipgoci_unittests_check_indication_for_test_id( 'MainRunInitGithubTokenOptionTest' ) ) ||
			( vipgoci_unittests_check_indication_for_test_id( 'MainRunInitOptionsPhpcsTest' ) ) ||
			( vipgoci_unittests_check_indication_for_test_id( 'OtherWebServicesIrcApiFilterIgnorableStringsTest' ) ) ||
			( vipgoci_unittests_check_indication_for_test_id( 'GitRepoRepoOkTest' ) )
		)
	) {
		return $exit_status;
	}

	exit( $exit_status );
}

