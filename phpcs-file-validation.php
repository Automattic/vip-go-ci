<?php

/*
 * Validates if PHPCS should execute or skip the file specified
 * Should be called before the scan itself
 */
function vipgoci_phpcs_validate_file(
	$filename_tmp
) {
    return vipgoci_phpcs_validate_number_of_lines($filename_tmp);
}

function vipgoci_phpcs_validate_number_of_lines($filename_tmp): bool
{
    /*
	 * Calculates the file number of lines
	 *
     * if it is > than the default limit
     *
     * phpcs won't scan it
	 */
    $cmd = sprintf(
        'wc -l %s | awk \'{print $1;}\'',
        escapeshellcmd( $filename_tmp )
    );

    vipgoci_log('Validating file size before PHPCS runs', array( 'cmd' => $cmd, ),0 );

    $output = vipgoci_runtime_measure_shell_exec(
        $cmd,
        'phpcs_validation'
    );

    vipgoci_log('Validation file size output', array( 'output' => $output ),0 );

    if ( intval( $output ) > 5 )
    {
        return false;
    }

    return true;
}
