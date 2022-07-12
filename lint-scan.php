<?php
/**
 * PHP linting logic for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Execute PHP linter, get results and
 * return them to caller as an array of
 * lines.
 *
 * @param string $php_path       Path to PHP.
 * @param string $temp_file_name Path to file to PHP lint.
 *
 * @return array|null Array with results of PHP linting on success, null on failure.
 */
function vipgoci_lint_do_scan_file(
	string $php_path,
	string $temp_file_name
) :array|null {
	/*
	 * Prepare command to use, make sure
	 * to grab all the output, also
	 * the output to STDERR.
	 *
	 * Further, make sure PHP error-reporting is set to
	 * E_ALL & ~E_DEPRECATED via configuration-option.
	 */

	$cmd = sprintf(
		'%s -d %s -d %s -d %s -l %s',
		escapeshellcmd( $php_path ),
		escapeshellarg( 'error_reporting=24575' ),
		escapeshellarg( 'error_log=null' ),
		escapeshellarg( 'display_errors=on' ),
		escapeshellarg( $temp_file_name )
	);

	$file_issues_arr = array();

	/*
	 * Execute linter, grab issues in array,
	 * measure how long time it took.
	 */
	$tmp_output      = '';
	$tmp_result_code = -255;

	$file_issues_str = vipgoci_runtime_measure_exec_with_retry(
		$cmd,
		array( 0, 255 ),
		$tmp_output,
		$tmp_result_code,
		'php_lint_cli',
		true
	);

	/*
	 * Detect failure.
	 */
	if ( null === $file_issues_str ) {
		return null;
	}

	$file_issues_arr = explode(
		PHP_EOL,
		$file_issues_str
	);

	/*
	 * Some PHP versions output empty lines
	 * when linting PHP files, remove those.
	 *
	 */
	$file_issues_arr =
		array_filter(
			$file_issues_arr,
			function ( $array_item ) {
				return '' !== $array_item;
			}
		);

	/*
	 * Some PHP versions use slightly
	 * different output when linting PHP files,
	 * make the output compatibile.
	 */

	$file_issues_arr = array_map(
		function ( $str ) {
			if ( strpos(
				$str,
				'Parse error: '
			) === 0 ) {
				$str = str_replace(
					'Parse error: ',
					'PHP Parse error:  ',
					$str
				);
			}

			return $str;
		},
		$file_issues_arr
	);

	/*
	 * For some reason some PHP versions
	 * output the same errors two times, remove
	 * any duplicates.
	 */

	$file_issues_arr =
		array_values(
			array_unique(
				$file_issues_arr
			)
		);

	vipgoci_log(
		'PHP linting execution details',
		array(
			'cmd'             => $cmd,
			'file_issues_arr' => $file_issues_arr,
		),
		2
	);

	return $file_issues_arr;
}

/**
 * Parse array of results, extract the problems
 * and return as a well-structed array.
 *
 * @param string $file_name       Name of PHP linted file.
 * @param string $temp_file_name  Temporary file used.
 * @param array  $file_issues_arr Array with results.
 *
 * @return array Array with parsed results.
 */
function vipgoci_lint_parse_results(
	$file_name,
	$temp_file_name,
	$file_issues_arr
) :array {
	$file_issues_arr_new = array();

	// Loop through everything we got from the command.
	foreach ( $file_issues_arr as $message ) {
		if ( 0 === strpos( $message, 'No syntax errors detected' ) ) {
			// Skip non-errors we do not care about.
			continue;
		}

		/*
		 * Catch any syntax-error problems.
		 */

		if (
			( false !== strpos( $message, ' on line ' ) ) &&
			( false !== strpos( $message, 'PHP Parse error:' ) )
		) {
			/*
			 * Get rid of 'PHP Parse...' which is not helpful
			 * for users when seen on GitHub.
			 */

			$message = str_replace(
				'PHP Parse error:',
				'',
				$message
			);

			/*
			 * Figure out on what line the problem is.
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

			if ( false === $pos3 ) {
				vipgoci_sysexit(
					'Temporary file name not found in PHPCS output, cannot continue',
					array(
						'file_name'      => $file_name,
						'temp_file_name' => $temp_file_name,
					),
					VIPGOCI_EXIT_SYSTEM_PROBLEM,
					true // Log to IRC.
				);
			}

			$message = substr( $message, 0, $pos3 );
			$message = ltrim( rtrim( $message ) );

			$file_issues_arr_new[ $file_line ][] = array(
				'message'  => $message,
				'level'    => 'ERROR',
				'severity' => 5,
			);
		}
	}

	return $file_issues_arr_new;
}

/**
 * Process results from scanning a file, ensure we
 * can merge the results by PHP version later.
 *
 * @param array  $current_file_intermediary_results Collected results of PHP
 *                                                  linting current file.
 * @param string $php_version_number                PHP version used for linting.
 * @param array  $current_iteration_file_issues_arr Results of linting current
 *                                                  file, to be processed this round.
 *
 * @return void
 */
function vipgoci_lint_scan_multiple_files_process_intermediate_results(
	array &$current_file_intermediary_results,
	string $php_version_number,
	array $current_iteration_file_issues_arr
) :void {
	foreach (
		$current_iteration_file_issues_arr as
			$line_no => $file_issue_line_arr
	) {
		foreach (
			$file_issue_line_arr as
				$file_issue_item
		) {
			/*
			 * Message, level and severity
			 * consitute uniqueness.
			 */
			$message_hash = hash(
				'sha256',
				$file_issue_item['message'] .
					'_' .
					$file_issue_item['level'] .
					'_' .
					(string) $file_issue_item['severity'],
				false
			);

			if ( ! isset(
				$current_file_intermediary_results[ $line_no ][ $message_hash ]
			) ) {
				$current_file_intermediary_results[ $line_no ][ $message_hash ] =
					array(
						'versions' => array(),
					);

				$current_file_intermediary_results[ $line_no ][ $message_hash ]['item'] =
					$file_issue_item;
			}

			// Add PHP version number.
			$current_file_intermediary_results[ $line_no ][ $message_hash ]['versions'][] =
				$php_version_number;
		}
	}
}

/**
 * Merge results by PHP version and file.
 *
 * @param array $current_file_intermediary_results Results of scanning current file, will
 *                                                 be merged as possible.
 *
 * @return array Results of scanning using all PHP versions.
 */
function vipgoci_lint_scan_multiple_files_merge_results_by_php_version(
	array $current_file_intermediary_results
) :array {
	$file_issues_arr = array();

	foreach (
		$current_file_intermediary_results as
			$line_no => $issues_found
	) {
		foreach (
			$issues_found as
				$file_issue_item
		) {
			$file_issue_item['item']['message'] =
			'Linting with PHP ' .
				join(
					', ',
					$file_issue_item['versions']
				)
				. ' turned up: ' .
				'<code>' .
				vipgoci_output_html_escape( $file_issue_item['item']['message'] ) .
				'</code>';

			$file_issues_arr[ $line_no ][] =
				$file_issue_item['item'];
		}
	}

	return $file_issues_arr;
}

/**
 * Scan multiple files and return the results.
 *
 * @param array $options              Options array for the program.
 * @param array $prs_implicated       Pull requests implicated.
 * @param array $commit_skipped_files Information about skipped files (reference).
 * @param array $files_to_be_scanned  Files to be scanned.
 *
 * @return array Results of linting, with results merged by PHP version as possible.
 */
function vipgoci_lint_scan_multiple_files(
	array $options,
	array $prs_implicated,
	array &$commit_skipped_files,
	array $files_to_be_scanned
) :array {
	$scanning_results = array();

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'lint_scan_single_file' );

	/*
	 * Lint every PHP file existing in the commit.
	 */

	// To keep account of files that could not be PHP linted.
	$files_failed_linting = array();

	foreach ( $files_to_be_scanned as $filename ) {
		$file_contents = vipgoci_gitrepo_fetch_committed_file(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$options['commit'],
			$filename,
			$options['local-git-repo']
		);

		// Save file contents in a temporary-file.
		$temp_file_name = vipgoci_save_temp_file(
			'vipgoci-lint-scan-',
			null,
			$file_contents
		);

		/**
		 * Validates the file.
		 * If it is not valid, skip it.
		 */
		if ( true === $options['skip-large-files'] ) {
			$validation = vipgoci_validate(
				$temp_file_name,
				$filename,
				$options['commit'],
				$options['skip-large-files-limit']
			);

			if ( 0 !== $validation['total'] ) {
				unlink( $temp_file_name );

				vipgoci_set_prs_implicated_skipped_files(
					$prs_implicated,
					$commit_skipped_files,
					$validation
				);

				continue;
			}
		}

		/*
		 * Keep statistics of what we do.
		 */
		vipgoci_stats_per_file(
			$options,
			$filename,
			'linted'
		);

		vipgoci_log(
			'About to PHP-lint file',
			array(
				'repo_owner'     => $options['repo-owner'],
				'repo_name'      => $options['repo-name'],
				'commit_id'      => $options['commit'],
				'filename'       => $filename,
				'temp_file_name' => $temp_file_name,
				'php-versions'   => $options['lint-php-versions'],
				'php-paths'      => $options['lint-php-version-paths'],
			)
		);

		$current_file_intermediary_results = array();

		foreach (
			$options['lint-php-versions'] as
				$tmp_lint_version_number
		) {
			/*
			 * Actually lint the file.
			 */
			$temp_linting_results_arr_raw = vipgoci_lint_do_scan_file(
				$options['lint-php-version-paths'][ $tmp_lint_version_number ],
				$temp_file_name
			);

			/*
			 * Process the results, get them in an array format.
			 * Skip in case of an error.
			 */
			if ( null !== $temp_linting_results_arr_raw ) {
				$temp_linting_results_arr = vipgoci_lint_parse_results(
					$filename,
					$temp_file_name,
					$temp_linting_results_arr_raw
				);
			} else {
				$temp_linting_results_arr = null;

				// Avoid duplicate entries.
				vipgoci_array_push_uniquely(
					$files_failed_linting,
					$filename,
				);
			}

			vipgoci_log(
				( null === $temp_linting_results_arr_raw ) ?
					'Failed PHP linting file' : 'Linting issues details',
				array(
					'repo_owner'          => $options['repo-owner'],
					'repo_name'           => $options['repo-name'],
					'commit_id'           => $options['commit'],
					'filename'            => $filename,
					'php-version'         => $tmp_lint_version_number,
					'php-path'            => $options['lint-php-version-paths'][ $tmp_lint_version_number ],
					'temp_file_name'      => $temp_file_name,
					'file_issues_arr'     => $temp_linting_results_arr,
					'file_issues_arr_raw' => $temp_linting_results_arr_raw,
				),
				( null === $temp_linting_results_arr_raw ) ? 0 : 2,
				( null === $temp_linting_results_arr_raw ) ? true : false
			);

			if ( null === $temp_linting_results_arr_raw ) {
				continue; // Skip due to failure.
			}

			vipgoci_lint_scan_multiple_files_process_intermediate_results(
				$current_file_intermediary_results,
				$tmp_lint_version_number,
				$temp_linting_results_arr
			);

			unset( $temp_linting_results_arr );
		}

		/*
		 * Process results of linting using all PHP versions
		 * and merge results by PHP version as possible.
		 */
		$file_issues_arr = vipgoci_lint_scan_multiple_files_merge_results_by_php_version(
			$current_file_intermediary_results
		);

		// Get rid of temporary file.
		unlink( $temp_file_name );

		// If there are no new issues, just leave it at that.
		if ( empty( $file_issues_arr ) ) {
			continue;
		}

		$scanning_results[ $filename ] = $file_issues_arr;
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'lint_scan_single_file' );

	/*
	 * Send generic message to each pull request
	 * on GitHub when there were problems linting
	 * notifying users about the problems and which
	 * files were not linted.
	 */
	if ( ! empty( $files_failed_linting ) ) {
		vipgoci_report_submit_scanning_files_failed(
			$options,
			$prs_implicated,
			$files_failed_linting,
			VIPGOCI_LINT_FAILED_MSG_START,
			VIPGOCI_LINT_FAILED_MSG_END
		);
	}

	return $scanning_results;
}

/**
 * Loop through each issue for the particular
 * line.
 *
 * @param array|null $commit_issues_submit_pr Results for the PR.
 * @param int        $error                   Error counter.
 * @param string     $file_name               File name.
 * @param array      $file_scanning_results   Results of file scanning.
 *
 * @return void
 */
function vipgoci_lint_set_file_issues_result(
	?array &$commit_issues_submit_pr,
	int &$error,
	string $file_name,
	array $file_scanning_results
): void {
	foreach ( $file_scanning_results as $file_issue_line => $file_issue_values ) {
		foreach ( $file_issue_values as $file_issue_val_item ) {
			$commit_issues_submit_pr[] = array(
				'type'      => VIPGOCI_STATS_LINT,
				'file_name' => $file_name,
				'file_line' => intval( $file_issue_line ),
				'issue'     => $file_issue_val_item,
			);
			$error ++;
		}
	}
}

/**
 * Run PHP lint on all files in a path. May skip files if
 * they are too large and/or they were not modified by the
 * pull requests implicated by the current commit.
 *
 * @param array $options              Options array for the program.
 * @param array $commit_issues_submit Results for PHP linting (reference).
 * @param array $commit_issues_stats  Result statistics, focussed on PHP linting (reference).
 * @param array $commit_skipped_files Information about skipped files (reference).
 *
 * @return void
 */
function vipgoci_lint_scan_commit(
	array $options,
	array &$commit_issues_submit,
	array &$commit_issues_stats,
	array &$commit_skipped_files
) :void {
	$repo_owner   = $options['repo-owner'];
	$repo_name    = $options['repo-name'];
	$commit_id    = $options['commit'];
	$github_token = $options['token'];

	if ( false === $options['lint'] ) {
		vipgoci_log(
			'Will not lint PHP files, not configured to do so',
			array(
				'repo_owner' => $repo_owner,
				'repo_name'  => $repo_name,
				'commit_id'  => $commit_id,
			)
		);
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'lint_scan_commit' );

	vipgoci_log(
		'About to lint PHP-files',
		array(
			'repo_owner' => $repo_owner,
			'repo_name'  => $repo_name,
			'commit_id'  => $commit_id,
		)
	);

	$prs_implicated = vipgoci_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		$options['branches-ignore'],
		$options['skip-draft-prs']
	);

	vipgoci_log(
		( false === $options['lint-modified-files-only'] ) ?
			'PHP lint scanning all PHP files' :
			'PHP lint scanning modified files only',
		array(
			'repo_owner' => $repo_owner,
			'repo_name'  => $repo_name,
			'commit_id'  => $commit_id,
		)
	);

	if ( true === $options['lint-modified-files-only'] ) {
		/*
		 * Fetch list of all files altered and by pull request.
		 */

		$commit_skipped_files_empty = array();

		$pr_item_files_changed = vipgoci_github_files_affected_by_commit(
			$options,
			$options['commit'],
			$commit_skipped_files_empty,
			false, // Exclude renamed files.
			false, // Exclude removed files.
			false, // Exclude permission changes.
			array(
				'file_extensions' => array( 'php' ),
				'skip_folders'    => $options['lint-skip-folders'],
			),
			true
		);

		$files_to_be_scanned = $pr_item_files_changed['all'];

		unset( $pr_item_files_changed['all'] );

		$files_changed_in_pr = $pr_item_files_changed;
	} else {
		// Fetch list of files that exist in the repository.
		$files_to_be_scanned = vipgoci_gitrepo_fetch_tree(
			$options,
			$commit_id,
			array(
				'file_extensions' => array( 'php' ),
				'skip_folders'    => $options['lint-skip-folders'],
			)
		);
	}

	/*
	 * Scan multiple files, collect results.
	 */
	$scanning_results = vipgoci_lint_scan_multiple_files(
		$options,
		$prs_implicated,
		$commit_skipped_files,
		$files_to_be_scanned
	);

	/*
	 * Process results of linting
	 * for each pull request -- actually
	 * queue issues for submission.
	 */
	$file_names = array_keys( $scanning_results );

	foreach ( $prs_implicated as $pr_item ) {
		$pr_number = $pr_item->number;

		$files_with_issues = false === $options['lint-modified-files-only']
			? $file_names
			: array_intersect_key( $file_names, $files_changed_in_pr[ $pr_number ] );

		foreach ( $files_with_issues as $file_name ) {
			vipgoci_log(
				'Linting issues found',
				array(
					'repo_owner'      => $repo_owner,
					'repo_name'       => $repo_name,
					'commit_id'       => $commit_id,
					'filename'        => $file_name,
					'pr_number'       => $pr_number,
					'file_issues_arr' => $scanning_results[ $file_name ],
				),
				2
			);

			vipgoci_lint_set_file_issues_result(
				$commit_issues_submit[ $pr_number ],
				$commit_issues_stats[ $pr_number ]['error'],
				$file_name,
				$scanning_results[ $file_name ]
			);
		}
	}

	vipgoci_log(
		'PHP linting complete',
		array()
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'lint_scan_commit' );
}

