<?php

/**
 * Run PHP lint on all files in a path
 */
function vipgoci_lint_do_scan( $path ) {
	$path = realpath( $path );

	if ( ! $path ) {
		return null;
	}

	// Could run in parallel with something like xargs -0 -n1 -P8 php -l
	// but the output gets fubared b/c all output for one file
	// can appear incongrously
	$cmd = sprintf(
		'find %s -type f -name "*.php" -exec php -l {} \;',
		escapeshellarg( $path )
	);

	$lines = array();

	exec( $cmd, $lines );

	// Strip out lines we don't care about
	foreach( $lines as $index => $line ) {
		if ( ! $line ||
			0 === strpos( $line, 'No syntax errors detected' ) ||
			0 === strpos( $line, 'Errors parsing' ) ) {
			unset( $lines[ $index ] );
		}
	}


	return $lines;
}
