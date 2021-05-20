<?php
/*
 * Validation applied to verify wether the bot should execute or skip the file specified
 */

/**
 * @param $temp_file_name
 * @param $file_name
 *
 * Validates if the file is valid to be scanned by vip-go-ci
 *
 * @return array
 *	[
 *		'issues' =>
 *		[
 *			'max-lines' => [$file_name],
 *		],
 *		'total' => 1
 *	]
 */
function vipgoci_validate( $temp_file_name, $file_name ) {
	$validation_result = array();

	if ( vipgoci_is_maximum_number_of_lines_valid( $temp_file_name ) ) {
		$validation_result[ 'issues' ][ VIPGOCI_VALIDATION_MAXIMUM_LINES ] = [ $file_name ];
	}

	$validation_result[ 'total' ] = ! empty( $validation_result[ 'issues' ] )
		? count( $validation_result[ 'issues' ] )
		: 0 ;

	return $validation_result;
}

/**
 * @param $temp_file_name
 * Validates if the number of lines is lower than the maximun
 *
 * @return bool
 */
function vipgoci_is_maximum_number_of_lines_valid( $temp_file_name ) {
	/**
	 * Calculates the file number of lines
	 *
	 * if it is > than the default limit (defined at defined)
	 *
	 * the bot won't scan it
	 */
	$cmd = sprintf(
		'wc -l %s | awk \'{print $1;}\'',
		escapeshellcmd( $temp_file_name )
	);

	vipgoci_log( 'Validating file number of lines before bot runs', array( 'cmd' => $cmd ), 0 );

	$output = ( int ) vipgoci_runtime_measure_shell_exec(
		$cmd,
		'file_validation'
	);

	vipgoci_log( 'Validation file number of lines output', array( 'output' => $output ), 0 );

	return $output > VIPGOCI_VALIDATION_MAXIMUM_LINES_LIMIT;
}
