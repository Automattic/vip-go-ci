<?php
/*
 * Validates if bot should execute or skip the file specified
 *
 */
function vipgoci_validate(
	$filename_tmp
) {
	return vipgoci_validate_number_of_lines($filename_tmp);
}

function vipgoci_validate_number_of_lines($filename_tmp): bool
{
	/**
	 * Calculates the file number of lines
	 *
	 * if it is > than the default limit (defined at defined)
	 *
	 * the bot won't scan it
	 */
	$cmd = sprintf(
		'wc -l %s | awk \'{print $1;}\'',
		escapeshellcmd( $filename_tmp )
	);

	vipgoci_log('Validating file size before bot runs', array( 'cmd' => $cmd, ),0 );

	$output = ( int ) vipgoci_runtime_measure_shell_exec(
		$cmd,
		'phpcs_validation'
	);

	vipgoci_log('Validation file size output', array( 'output' => $output ),0 );

	return ( $output < VIPGOCI_VALIDATION_LIMIT_LINES );
}
