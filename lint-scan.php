<?php

/*
 * Execute PHP linter, get results and
 * return them to caller as an array of
 * lines.
 */

function vipgoci_lint_do_scan(
	$php_path,
	$temp_file_name
) {
	/*
	 * Prepare command to use, make sure
	 * to grab all the output, also
	 * the output to STDERR.
	 *
	 * Further, make sure PHP error-reporting is set to
	 * E_ALL & ~E_DEPRECATED via configuration-option.
	 */

	$cmd = sprintf(
		'( %s -d %s -d %s -d %s -l %s 2>&1 )',
		escapeshellcmd( $php_path ),
		escapeshellarg( 'error_reporting=24575' ),
		escapeshellarg( 'error_log=null' ),
		escapeshellarg( 'display_errors=off' ),
		escapeshellarg( $temp_file_name )
	);


	$file_issues_arr = array();

	/*
	 * Execute linter, grab issues in array,
	 * measure how long time it took
	 */

	vipgoci_runtime_measure( 'start', 'php_lint_cli' );

	exec( $cmd, $file_issues_arr );

	vipgoci_runtime_measure( 'stop', 'php_lint_cli' );


	vipgoci_log(
		'PHP linting execution details',
		array(
			'cmd'			=> $cmd,
			'file_issues_arr'	=> $file_issues_arr,
		),
		2
	);

	return $file_issues_arr;
}


/*
 * Parse array of results, extract the problems
 * and return as a well-structed array.
 */

function vipgoci_lint_get_issues(
	$file_name,
	$temp_file_name,
	$file_issues_arr
) {

	$file_issues_arr_new = array();

	// Loop through everything we got from the command
	foreach( $file_issues_arr as $index => $message ) {
		if (
			( 0 === strpos(
				$message,
				'No syntax errors detected'
			) )
		) {
			// Skip non-errors we do not care about
			continue;
		}


		/*
		 * Catch any syntax-error problems
		 */

		if (
			( false !== strpos( $message, ' on line ' ) ) &&
			( false !== strpos( $message, 'PHP Parse error:' ) )
		) {
			/*
			 * Get rid of 'PHP Parse...' which is not helpful
			 * for users when seen on GitHub
			 */

			$message = str_replace(
				'PHP Parse error:',
				'',
				$message
			);


			/*
			 * Figure out on what line the problem is
			 */
			$pos = strpos(
				$message,
				' on line '
			) + strlen( ' on line ' );


			$file_line = substr(
				$message,
				$pos,
				strlen( $message ) - $pos
			);

			unset( $pos );
			unset( $pos2 );


			/*
			 * Get rid of name of the file, and
			 * the rest of the message, too.
			 */
			$pos3 = strpos( $message, ' in ' . $temp_file_name );

			$message = substr( $message, 0, $pos3 );
			$message = ltrim( rtrim( $message ) );

			$file_issues_arr_new[ $file_line ][] = array(
				'message' => $message,
				'level' => 'ERROR'
			);
		}
	}

	return $file_issues_arr_new;
}


/**
 * Run PHP lint on all files in a path
 */
function vipgoci_lint_scan_commit(
	$options,
	&$commit_issues_submit,
	&$commit_issues_stats
) {
	$repo_owner = $options['repo-owner'];
	$repo_name  = $options['repo-name'];
	$commit_id  = $options['commit'];
	$github_token = $options['token'];

	vipgoci_runtime_measure( 'start', 'lint_scan_commit' );

	vipgoci_log(
		'About to lint PHP-files',

		array(
			'repo_owner'	=> $repo_owner,
			'repo_name'	=> $repo_name,
			'commit_id'	=> $commit_id,
		)
	);


	// Ask for information about the commit
	$commit_info = vipgoci_github_fetch_commit_info(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		array(
			'file_extensions'
				=> array( 'php' ),

			'status'
				=> array( 'added', 'modified' ),
		)
	);

	// Fetch list of files that exist in the commit
	$commit_tree = vipgoci_gitrepo_fetch_tree(
		$options,
		$commit_id,
		array(
			'file_extensions'
				=> array( 'php' ),
			'skip_folders'
				=> $options['skip-folders'],
		)
	);

	// Ask for all Pull-Requests that this commit is part of
	$prs_implicated = vipgoci_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		$options['branches-ignore']
	);


	/*
	 * Lint every PHP file existing in the commit
	 */

	foreach( $commit_tree as $filename ) {
		vipgoci_runtime_measure( 'start', 'lint_scan_single_file' );

		$file_contents = vipgoci_gitrepo_fetch_committed_file(
			$repo_owner,
			$repo_name,
			$github_token,
			$commit_id,
			$filename,
			$options['local-git-repo']
                );

		// Save the file-contents in a temporary-file
		$temp_file_name = vipgoci_save_temp_file(
			'lint-scan-',
			null,
			$file_contents
		);

		/*
		 * Actually lint the file
		 */

		vipgoci_log(
			'About to PHP-lint file',

			array(
				'repo_owner' => $repo_owner,
				'repo_name' => $repo_name,
				'commit_id' => $commit_id,
				'filename' => $filename,
				'temp_file_name' => $temp_file_name,
			)
		);

		$file_issues_arr_raw = vipgoci_lint_do_scan(
			$options['php-path'],
			$temp_file_name
		);

		/* Get rid of temporary file */
		unlink( $temp_file_name );


		/*
		 * Process the results, get them in an array format
		 */

		$file_issues_arr = vipgoci_lint_get_issues(
			$filename,
			$temp_file_name,
			$file_issues_arr_raw
		);

		vipgoci_log(
			'Linting issues details',
			array(
				'repo_owner'		=> $repo_owner,
				'repo_name'		=> $repo_name,
				'commit_id'		=> $commit_id,
				'filename'		=> $filename,
				'temp_file_name'	=> $temp_file_name,
				'file_issues_arr'	=> $file_issues_arr,
				'file_issues_arr_raw'	=> $file_issues_arr_raw,
			),
			2
		);

		/* Get rid of the raw version of issues */
		unset( $file_issues_arr_raw );

		// If there are no new issues, just leave it at that
		if ( empty( $file_issues_arr ) ) {
			vipgoci_runtime_measure( 'stop', 'lint_scan_single_file' );
			continue;
		}

		/*
		 * Process results of linting
		 * for each Pull-Request -- actually
		 * queue issues for submission.
		 */
		foreach( $prs_implicated as $pr_item ) {
			vipgoci_log(
				'Linting issues found',
				array(
					'repo_owner'		=> $repo_owner,
					'repo_name'		=> $repo_name,
					'commit_id'		=> $commit_id,

					'filename'
						=> $filename,

					'pr_number'
						=> $pr_item->number,

					'file_issues_arr'
						=> $file_issues_arr,
				),
				2
			);


			/*
			 * Loop through array of lines in which
			 * issues exist.
			 */

			foreach (
				$file_issues_arr as
					$file_issue_line => $file_issue_values
			) {
				/*
				 * Loop through each issue for the particular
				 * line.
				 */

				foreach (
					$file_issue_values as
						$file_issue_val_item
				) {
					$commit_issues_submit[
						$pr_item->number
					][] = array(
						'type'		=> 'lint',

						'file_name'	=>
							$filename,

						'file_line'	=> intval(
							$file_issue_line
						),

						'issue'		=>
							$file_issue_val_item
					);

					$commit_issues_stats[
						$pr_item->number
					]['error']++;
				}
			}
		}

		vipgoci_runtime_measure( 'stop', 'lint_scan_single_file' );
	}


	/*
	 * Reduce memory-usage, as possible.
	 */
	unset( $file_contents );
	unset( $file_issues_arr );
	unset( $file_issues_arr_raw );
	unset( $prs_implicated );
	unset( $file_issue_values );
	unset( $commit_tree );
	unset( $commit_info );

	gc_collect_cycles();

	vipgoci_runtime_measure( 'stop', 'lint_scan_commit' );
}
