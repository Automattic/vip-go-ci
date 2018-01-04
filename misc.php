<?php

/*
 * Log information to the console.
 * Include timestamp, and any debug-data
 * our caller might pass us.
 */

function vipgoci_log( $str, $debug_data = array(), $debug_level = 0 ) {
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

	echo '[ ' . date( 'c' ) . ' -- ' . (int) $debug_level . ' ]  ' .
		$str .
		'; ' .
		print_r(
			json_encode(
				$debug_data,
				JSON_PRETTY_PRINT
			),
			true
		) .
		PHP_EOL;
}


/*
 * Given a patch-file, function will return an
 * associative array, mapping the patch-file
 * to the raw committed file.
 *
 * In the resulting array, the keys represent every
 * line in the patch (except for the "@@" lines),
 * while the values represent line-number in the
 * raw committed line. Some keys might point
 * to empty values, in which case there is no
 * relation between the two.
 */

function vipgoci_patch_changed_lines(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_base_sha,
	$commit_id,
	$file_name
) {

	/*
	 * Fetch patch for all files of the Pull-Request
	 */
	$patch_arr = vipgoci_github_diffs_fetch(
		$repo_owner,
		$repo_name,
		$github_token,
		$pr_base_sha,
		$commit_id
	);

	/*
	 * Get patch for the relevant file
	 * our caller is interested in
	 */
	// FIXME: Detect if file is not part of the patch
	$lines_arr = explode(
		"\n",
		$patch_arr[ $file_name ]
	);

	$lines_changed = array();

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
			if ( empty( $line[0] ) ) {
				// Do nothing
			}

			else if (
				( $line[0] == '-' ) ||
				( $line[0] == '\\' )
			) {
				$lines_changed[] = null;
			}

			else if (
				( $line[0] == '+' ) ||
				( $line[0] == " " ) ||
				( $line[0] == "\t" )
			) {
				$lines_changed[] = $i++;
			}
		}
	}

	return $lines_changed;
}


/*
 * Filter out any issues in the code that were not
 * touched up on by the changed lines -- i.e., any issues
 * that existed prior to the change.
 *
 * The argument $fuzziness indicates that issues should
 * not be filtered out if they are only one line-number
 * out of range -- they should be kept and their line-numbers
 * adjusted so that they are included.
 */
function vipgoci_issues_filter_irrellevant(
	$file_issues_arr,
	$file_changed_lines,
	$fuzziness = false
) {
	foreach (
		$file_issues_arr as
			$file_issue_line => $file_issue_val
	) {
		if ( ! in_array(
				$file_issue_line,
				$file_changed_lines
		) ) {
			$exists = false;
		}

		else {
			$exists = true;
		}


		// Issue exists, do not remove or alter.
		if ( $exists === true ) {
			continue;
		}


		/*
		 * Issue is out of range, and no fuzzy-checking
		 * requested, so delete it.
		 */
		else if (
			( false === $exists ) &&
			( false === $fuzziness )
		) {
			/*
			 * No fuzziness-check, and the
			 * issue is out of range, delete it,
			 * and continue.
			 */
			unset(
				$file_issues_arr[
					$file_issue_line
				]
			);

			continue;
		}


		else if (
			( false === $exists ) &&
			( true === $fuzziness )
		) {
			/*
			 * Issue out of range, but fuzziness
			 * is requested, act on that.
			 */

			$tmp_minus = in_array(
				$file_issue_line - 1,
				$file_changed_lines
			);


			$tmp_plus = in_array(
				$file_issue_line + 1,
				$file_changed_lines
			);


			if (
				( $tmp_minus === true ) ||
				( $tmp_plus === true )
			) {
				/*
				 * Copy the instance, delete
				 * the original, and add again
				 * but with the line-number altered.
				 */

				$tmp_num = $tmp_minus === true ? -1 : +1;

				// Add a new one
				$file_issues_arr[
					$file_issue_line + $tmp_num
				] = $file_issues_arr[
					$file_issue_line
				];

				// Remove the old one
				unset(
					$file_issues_arr[
						$file_issue_line
					]
				);

				continue;
			}
		}
	}

	return $file_issues_arr;
}


/*
 * Get a specific item from in-memory cache based on
 * $cache_id_arr if $data is null, or if $data is not null,
 * add a specific item to cache.
 *
 * The data is stored in an associative array, with
 * key being an array (or anything else) -- $cache_id_arr --,
 * and used to identify the data up on retrieval.
 *
 * If the data being cached is an object, we make a copy of it,
 * and then store it. When the cached data is being retrieved,
 * we return a copy of the cached data.
 */

function vipgoci_cache( $cache_id_arr, $data = null ) {
	global $vipgoci_cache_buffer;

	$cache_id = json_encode(
		$cache_id_arr
	);


	if ( null === $data ) {
		if ( isset( $vipgoci_cache_buffer[ $cache_id ] ) ) {
			$ret = $vipgoci_cache_buffer[ $cache_id ];

			// If an object, copy and return the copy
			if ( is_object( $ret ) ) {
				$ret = clone $ret;
			}

			return $ret;
		}

		else {
			return false;
		}
	}

	// If an object, copy, save it, and return the copy
	if ( is_object( $data ) ) {
		$data = clone $data;
	}

	$vipgoci_cache_buffer[ $cache_id ] = $data;

	return $data;
}


/*
 * Create a temporary file, and return the
 * full-path to the file.
 */

function vipgoci_save_temp_file( $file_name_prefix, $file_contents ) {
	// Determine name for temporary-file
	$temp_file_name = $temp_file_save_status = tempnam(
		sys_get_temp_dir(),
		$file_name_prefix
	);

	if ( false !== $temp_file_name ) {
		vipgoci_runtime_measure( 'start', 'save_temp_file' );

		$temp_file_save_status = file_put_contents(
			$temp_file_name,
			$file_contents
		);

		vipgoci_runtime_measure( 'stop', 'save_temp_file' );
	}

	// Detect possible errors when saving the temporary file
	if ( false === $temp_file_save_status ) {
		vipgoci_log(
			'Could not save file to disk, got ' .
			'an error. Exiting...',

			array(
				'temp_file_name' => $temp_file_name,
			)
		);

		exit( 254 );
	}

	return $temp_file_name;
}


/*
 * Return ASCII-art for GitHub, which will then
 * be turned into something more fancy. This is
 * intended to be called when preparing messages/comments
 * to be submitted to GitHub.
 */
function vipgoci_github_labels( $text_string ) {
	switch( strtolower( $text_string ) ) {
		case 'warning':
			return ':exclamation:';

		case 'error':
			return ':no_entry_sign:';
	}

	return '';
}


/*
 * Determine if the presented file has an
 * allowable file-ending
 */
function vipgoci_filter_file_endings(
	$filename,
	$file_extensions_arr
) {
	$file_info_extension = pathinfo(
		$filename,
		PATHINFO_EXTENSION
	);

	/*
	 * If the file does not have an acceptable
	 * file-extension, flag it.
	 */

	if ( ! in_array(
		strtolower( $file_info_extension ),
			$file_extensions_arr,
			true
	) ) {
		vipgoci_log(
			'Skipping file that does not seem ' .
				'to be a file matching ' .
				'filter-criteria',

			array(
				'filename' =>
					$filename,

				'allowable_file_extensions' =>
					$file_extensions_arr,
			),
			2
		);

		return false;
	}

	return true;
}


/*
 * Recursively scan the git repository,
 * returning list of files that exist in
 * it, making sure to filter the result
 */
function vipgoci_scandir_git_repo( $path, $filter ) {
	$result = array();

	vipgoci_log(
		'Fetching git-tree using scandir()',

		array(
			'path' => $path,
			'filter' => $filter,
		),
		3
	);


	vipgoci_runtime_measure( 'start', 'git_repo_scandir' );

	$cdir = scandir( $path );

	vipgoci_runtime_measure( 'stop', 'git_repo_scandir' );


	foreach ( $cdir as $key => $value ) {
		if ( in_array(
			$value,
			array( '.', '..', '.git' )
		) ) {
			// Skip '.' and '..'
			continue;
		}


		if ( is_dir(
			$path . DIRECTORY_SEPARATOR . $value
		) ) {
			/*
			 * A directory, traverse into, get files,
			 * amend the results
			 */
			$tmp_result = vipgoci_scandir_git_repo(
				$path . DIRECTORY_SEPARATOR . $value,
				$filter
			);

			foreach ( $tmp_result as $tmp_result_item ) {
				$result[] = $value .
					DIRECTORY_SEPARATOR .
					$tmp_result_item;
			}

			continue;
		}

		// Filter out files not with desired line-ending
		if ( null !== $filter ) {
			if ( false === vipgoci_filter_file_endings(
				$value,
				$filter['file_extensions']
			) ) {
				continue;
			}
		}

		// Not a directory, passed filter, save in array
		$result[] = $value;
	}

	return $result;
}


/*
 * Initialize statistics array
 */
function vipgoci_stats_init( $options, $prs_implicated, &$results ) {
	/*
	 * Init stats
	 */

	foreach ( $prs_implicated as $pr_item ) {
		/*
		 * Initialize array for stats and
		 * results of scanning, if needed.
		 */

		if ( empty( $results['issues'][ $pr_item->number ] ) ) {
			$results['issues'][ $pr_item->number ] = array(
			);
		}

		foreach ( array( 'phpcs', 'lint' ) as $stats_type ) {
			/*
			 * Initialize stats for the stats-types only when
			 * supposed to run them
			 */
			if (
				( true !== $options[ $stats_type ] ) ||
				( ! empty( $results['stats'][ $stats_type ][ $pr_item->number ] ) )
			) {
				continue;
			}

			$results['stats'][ $stats_type ]
				[ $pr_item->number ] = array(
					'error'         => 0,
					'warning'       => 0
				);
		}
	}
}


/*
 * A simple function to keep record of how
 * much a time a particular action takes to execute.
 * Allows multiple records to be kept at the same time.
 *
 * Allows specifying 'start' acton, which indicates that
 * keeping record of measurement should start, 'stop'
 * which indicates that recording should be stopped,
 * and 'dump' which will return with an associative
 * array of all measurements collected henceforth.
 *
 */
function vipgoci_runtime_measure( $action = null, $type = null ) {
	static $runtime = array();
	static $timers = array();

	/*
	 * Check usage.
	 */
	if (
		( 'start' !== $action ) &&
		( 'stop' !== $action ) &&
		( 'dump' !== $action )
	) {
		return false;
	}

	// Dump all runtimes we have
	if ( 'dump' === $action ) {
		return $runtime;
	}


	/*
	 * Being asked to either start
	 * or stop collecting, act on that.
	 */

	if ( ! isset( $runtime[ $type ] ) ) {
		$runtime[ $type ] = 0;
	}


	if ( 'start' === $action ) {
		$timers[ $type ] = time();

		return true;
	}

	else if ( 'stop' === $action ) {
		if ( ! isset( $timers[ $type ] ) ) {
			return false;
		}

		$tmp_time = time() - $timers[ $type ];

		$runtime[ $type ] += $tmp_time;

		unset( $timers[ $type ] );

		return $tmp_time;
	}
}
