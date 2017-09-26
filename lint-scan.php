<?php

/**
 * Run PHP lint on all files in a path
 */
function vipgoci_lint_do_scan(
	$path,
	$options,
	&$commit_issues_submit,
	&$commit_issues_stats
) {
	// FIXME: Walk through each file in the
	// commit, get a copy, then run lint on it

	vipgoci_log(
		'About to lint PHP-files',

		array(
			'path'	=> $path
		)
	);


	$path = realpath( $path );

	if ( ! $path ) {
		return null;
	}

	// Could run in parallel with something like xargs -0 -n1 -P8 php -l
	// but the output gets fubared b/c all output for one file
	// can appear incongrously

	$cmd = sprintf(
		'find %s -type f -name "*.php" -exec php -l {} \; 2>&1',
		escapeshellarg( $path )
	);


	exec( $cmd, $issues );

	// FIXME: Correct PR number
	$pr_number = 1;


	/*
	 * Initialize array for stats and
	 * results of scanning, if needed.
	 */

	if ( empty( $commit_issues_submit[ $pr_number ] ) ) {
		$commit_issues_submit[ $pr_number ] = array(
		);
	}

	if ( empty( $commit_issues_stats[ $pr_number ] ) ) {
		$commit_issues_stats[ $pr_number ] = array(
			'error'         => 0,
			'warning'       => 0
		);
	}

	// Loop through everything we got from the command
	foreach( $issues as $index => $line ) {
		if (
			( 0 === strpos( $line, 'No syntax errors detected' ) )
		) {
			// Skip non-errors we do not care about
			continue;
		}


		/*
		 * Catch any syntax-error problems
		 */

		$pos = strpos( $line, '.php on line ' );

		if ( false !== $pos ) {
			$pos2 = strpos( $line, ' ', $pos );

			$file_line = substr(
				$line,
				$pos + strlen('.php on line '),
				$pos2
			);

			$line_new = substr( $line, 0, $pos + strlen('.php') );


			$pos3 = strpos( $line_new, ' in /' );

			$file_name = substr(
				$line_new,
				$pos3 + strlen(' in '),
				$pos2
			);


			$file_name = str_replace(
				$path . '/',
				'',
				$file_name
			);


			$commit_issues_submit[ $pr_number ][] = array(
				'type'		=> 'lint',
				'file_name'	=> $file_name,
				'file_line'	=> intval(
					$file_line
				),

				'issue'		=> array(
					'message'	=> 'Syntax error detected',
					'level'		=> 'ERROR'
				),
			);

			$commit_issues_stats[ $pr_number ]['error']++;
		}
	}
}
