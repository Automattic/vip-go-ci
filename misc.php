<?php

/*
 * Log information to the console.
 * Include timestamp, and any debug-data
 * our caller might pass us.
 */

function vipgoci_phpcs_log( $str, $debug_data ) {
	echo '[ ' . date( 'c' ) . ' ] ' .
		$str .
		'; ' .
		print_r(
			json_encode(
				$debug_data,
				JSON_PRETTY_PRINT
			),
			true
		) .
		"\n\r";
}


/*
 * Look at a patch given to use by our caller,
 * and figure out what lines of the target-file
 * were affected by the patch.
 */

function vipgoci_phpcs_patch_changed_lines( $patch ) {
	$lines_changed = array();

	$lines_arr = explode( "\n", $patch );

	$i = 1;

	foreach ( $lines_arr as $line ) {
		preg_match_all(
			"/^@@\s+[-\+]([0-9]+,[0-9]+)\s+[-\+]([0-9]+,[0-9]+)\s+@@/",
			$line,
			$matches
		);

		if ( ! empty( $matches[0] ) ) {
			$start_end = explode(
				',',
				$matches[2][0]
			);


			$i = $start_end[0];

			$lines_changed[] = null;
		}

		else if ( empty( $matches[0] ) ) {
			if ( $line[0] == '-' ) {
				$lines_changed[] = null;
			}

			else if (
				( $line[0] == '+' ) ||
				( $line[0] == ' ')
			) {
				$lines_changed[] = $i++;
			}
		}
	}

	return $lines_changed;
}

