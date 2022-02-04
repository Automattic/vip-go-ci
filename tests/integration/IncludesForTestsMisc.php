<?php

declare( strict_types=1 );

/**
 * Some versions of PHP reverse the ',' and ';'
 * in output from PHP linting; deal with that here.
 *
 * Some versions use ' and some use ", deal with
 * that too.
 *
 * @param string $str String to work with.
 *
 * @return string
 */
function vipgoci_unittests_php_syntax_error_compat( string $str ) :string {
	$str = str_replace(
		"syntax error, unexpected end of file, expecting ';' or ','",
		"syntax error, unexpected end of file, expecting ',' or ';'",
		$str
	);

	$str = str_replace(
		'syntax error, unexpected end of file, expecting "," or ";"',
		"syntax error, unexpected end of file, expecting ',' or ';'",
		$str
	);

	return $str;
}

/**
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

