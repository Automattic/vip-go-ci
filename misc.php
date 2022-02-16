<?php

/*
 * Set how to deal with errors:
 * Report all errors, and display them.
*/

function vipgoci_set_php_error_reporting() {
	ini_set( 'error_log', '' );

	error_reporting( E_ALL );

	ini_set( 'display_errors', 'on' );
}

/**
 * Set up to alarm when maximum execution time of
 * vip-go-ci is reached. Will call exit() when
 * alarm goes off.
 *
 * @param int    $max_exec_time     Maximum execution time in seconds.
 * @param string $commit_identifier Identifier for the commit.
 *
 * @return void
 */
function vipgoci_set_maximum_exec_time(
	int $max_exec_time = 600,
	string $commit_identifier = ''
) :void {
	static $has_been_invoked = false;

	/*
	 * Ensure the function is only called
	 * once per run.
	 */
	if ( true === $has_been_invoked ) {
		vipgoci_log(
			'Cannot set maximum execution time twice, ignoring'
		);

		return;
	}

	$has_been_invoked = true;

	/*
	 * Enable async signal handlers
	 */
	pcntl_async_signals( true );

	vipgoci_log(
		'Setting maximum execution time',
		array(
			'max_exec_time'	=> $max_exec_time,
		)
	);

	/*
	 * Set up signal handler.
	 */
	vipgoci_log(
		'Setting up alarm signal handler and setting up alarm',
	);

	/*
	 * Handle signals for SIGALRM only;
	 * log and call exit().
	 */
	pcntl_signal(
		SIGALRM,
		function ( $signo ) use ( $commit_identifier ) {
			if ( SIGALRM === $signo ) {
				vipgoci_sysexit(
					'Maximum execution time reached ' .
						( empty( $commit_identifier ) ?
						'' :
						'(' . $commit_identifier . ').' ),
					array(),
					VIPGOCI_EXIT_EXEC_TIME,
					true // log to IRC.
				);
			}
		}
	);

	/*
	 * Send alarm after max-exec-time
	 */
	pcntl_alarm( $max_exec_time );
}

/*
 * Check if a particular set of fields exist
 * in a target array and if their values match a set
 * given. Will return an array describing
 * which items of the array contain all the fields
 * and the matching values.
 *
 * Example:
 *	$fields_arr = array(
 *		'a'	=> 920,
 *		'b'	=> 700,
 *	);
 *
 *	$data_arr = array(
 *		array(
 *			'a'	=> 920,
 *			'b'	=> 500,
 *			'c'	=> 0,
 *			'd'	=> 1,
 *			...
 *		),
 *		array(
 *			'a'	=> 920,
 *			'b'	=> 700,
 *			'c'	=> 0,
 *			'd'	=> 2,
 *			...
 *		),
 *	);
 *
 *	$res = vipgoci_find_fields_in_array(
 *		$fields_arr, $data_arr
 *	);
 *
 *	$res will be:
 *	array(
 *		0 => false,
 *		1 => true,
 *	);
 */
function vipgoci_find_fields_in_array( $fields_arr, $data_arr ) {
	$res_arr = array();

	for(
		$data_item_cnt = 0;
		$data_item_cnt < count( $data_arr );
		$data_item_cnt++
	) {
		$res_arr[ $data_item_cnt ] = 0;

		foreach( $fields_arr as $field_name => $field_values ) {
			if ( ! array_key_exists( $field_name, $data_arr[ $data_item_cnt ] ) ) {
				continue;
			}

			foreach( $field_values as $field_value_item ) {
				if ( $data_arr[ $data_item_cnt ][ $field_name ] === $field_value_item ) {
					$res_arr[ $data_item_cnt ]++;

					/*
					 * Once we find a match, stop searching.
					 * This is to safeguard against any kind of
					 * multiple matches (which though are nearly
					 * impossible).
					 */
					break;
				}
			}
		}

		$res_arr[
			$data_item_cnt
		] = (
			$res_arr[ $data_item_cnt ]
			===
			count( array_keys( $fields_arr ) )
		);
	}

	return $res_arr;
}

/*
 * Convert a string that contains "true", "false" or
 * "null" to a variable of that type.
 */
function vipgoci_convert_string_to_type( $str ) {
	switch( $str ) {
		case 'true':
			$ret = true;
			break;

		case 'false':
			$ret = false;
			break;

		case 'null':
			$ret = null;
			break;

		default:
			$ret = $str;
			break;
	}

	return $ret;
}

/*
 * Round items in an array to a certain precision, return
 * new array with results. Essentially a wrapper around the
 * PHP round() function.
 */
function vipgoci_round_array_items(
	array $arr,
	int $precision = 0,
	int $mode = PHP_ROUND_HALF_UP
): array {
	return array_map(
		function( $item ) use ( $precision, $mode ) {
			return round( $item, $precision, $mode );
		},
		$arr
	);
}

/*
 * Create a temporary file, and return the
 * full-path to the file.
 */

function vipgoci_save_temp_file(
	$file_name_prefix,
	$file_name_extension = null,
	$file_contents = ''
) {
	// Determine name for temporary-file
	$temp_file_name = $temp_file_save_status = tempnam(
		sys_get_temp_dir(),
		$file_name_prefix
	);

	/*
	 * If temporary file should have an extension,
	 * make that happen by renaming the currently existing
	 * file.
	 */

	if (
		( null !== $file_name_extension ) &&
		( false !== $temp_file_name )
	) {
		$temp_file_name_old = $temp_file_name;
		$temp_file_name .= '.' . $file_name_extension;

		if ( true !== rename(
			$temp_file_name_old,
			$temp_file_name
		) ) {
			vipgoci_sysexit(
				'Unable to rename temporary file',
				array(
					'temp_file_name_old' => $temp_file_name_old,
					'temp_file_name_new' => $temp_file_name,
				),
				VIPGOCI_EXIT_SYSTEM_PROBLEM
			);
		}

		unset( $temp_file_name_old );
	}

	if ( false !== $temp_file_name ) {
		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'save_temp_file' );

		$temp_file_save_status = file_put_contents(
			$temp_file_name,
			$file_contents
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'save_temp_file' );
	}

	// Detect possible errors when saving the temporary file
	if ( false === $temp_file_save_status ) {
		vipgoci_sysexit(
			'Could not save file to disk, got ' .
			'an error. Exiting...',

			array(
				'temp_file_name' => $temp_file_name,
			),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	return $temp_file_name;
}

/*
 * Determine file-extension of a particular file,
 * and return it in lowercase. If it can not be
 * determined, return null.
 */
function vipgoci_file_extension_get( $file_name ) {
	$file_extension = pathinfo(
		$file_name,
		PATHINFO_EXTENSION
	);

	if ( empty( $file_extension ) ) {
		return null;
	}

	$file_extension = strtolower(
		$file_extension
	);

	return $file_extension;
}

/*
 * Determine if the presented file has an
 * allowable file-ending, and if the file presented
 * is in a directory that is can be scanned.
 *
 * Note: $filename is expected to be a relative
 * path to the git-repository root.
 */
function vipgoci_filter_file_path(
	$filename,
	$filter
) {
	$file_info_extension = vipgoci_file_extension_get(
		$filename
	);

	$file_dirs = pathinfo(
		$filename,
		PATHINFO_DIRNAME
	);

	/*
	 * If the file does not have an acceptable
	 * file-extension, flag it.
	 */

	$file_ext_match =
		( null !== $filter ) &&
		( isset( $filter['file_extensions'] ) ) &&
		( ! in_array(
			$file_info_extension,
			$filter['file_extensions'],
			true
		) );

	/*
	 * If path to the file contains any non-acceptable
	 * directory-names, skip it.
	 */

	$file_folders_match = false;

	if (
		( null !== $filter ) &&
		( isset( $filter['skip_folders'] ) )
	) {
		/*
		 * Loop through all skip-folders.
		 */
		foreach(
			$filter['skip_folders'] as $tmp_skip_folder_item
		) {
			/*
			 * Note: All 'skip_folder' options should lack '/' at the
			 * end and beginning.
			 *
			 * $filename we expect to be a relative path.
			 */

			$file_folders_match = strpos(
				$filename,
				$tmp_skip_folder_item . '/'
			);

			/*
			 * Check if the file matches any of the folders
			 * that are to be skipped -- note that we enforce
			 * that the folder has to be at the root of the
			 * path to be a match.
			 */
			if (
				( false !== $file_folders_match ) &&
				( is_numeric( $file_folders_match ) ) &&
				( 0 === $file_folders_match )
			) {
				$file_folders_match = true;
				break;
			}
		}
	}

	/*
	 * Do the actual skipping of file,
	 * if either of the conditions are fulfiled.
	 */

	if (
		( true === $file_ext_match ) ||
		( true === $file_folders_match )
	) {
		vipgoci_log(
			'Skipping file that does not seem ' .
				'to be a file matching ' .
				'filter-criteria',

			array(
				'filename' =>
					$filename,

				'filter' =>
					$filter,

				'matches' => array(
					'file_ext_match' => $file_ext_match,
					'file_folders_match' => $file_folders_match,
				),
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
 *
 * Note: Do not call with $base_path parameter,
 * that is reserved for internal use only.
 */
function vipgoci_scandir_git_repo( $path, $filter, $base_path = null ) {
	$result = array();

	vipgoci_log(
		'Fetching git-tree using scandir()',

		array(
			'path' => $path,
			'filter' => $filter,
			'base_path' => $base_path,
		),
		2
	);

	/*
	 * If no base path is given,
	 * use $path. This will be used
	 * when making sure we do not
	 * accidentally filter by the filesystem
	 * outside of the git-repository (see below).
 	 */
	if ( null === $base_path ) {
		$base_path = $path;
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'git_repo_scandir' );

	$cdir = scandir( $path );

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'git_repo_scandir' );


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
				$filter,
				$base_path
			);

			foreach ( $tmp_result as $tmp_result_item ) {
				$result[] = $value .
					DIRECTORY_SEPARATOR .
					$tmp_result_item;
			}

			continue;
		}

		/*
		 * Filter out files not with desired line-ending
		 * or are located in directories that should be
		 * ignored.
		 */
		if ( null !== $filter ) {
			/*
			 * Remove the portion of the path
			 * that leads to the git repository,
			 * as we only want to filter by files in the
			 * git repository it self here. This is to
			 * make sure "skip_folders" filtering works
			 * correctly and does not accidentally take into
			 * consideration the path leading to the git repository.
			 */
			$file_path_without_git_repo = substr(
				$path . DIRECTORY_SEPARATOR . $value,
				strlen( $base_path ) + 1 // Send in what looks like a relative path
			);

			if ( false === vipgoci_filter_file_path(
				$file_path_without_git_repo,
				$filter
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
 * Sort results to be submitted to GitHub according to
 * severity of issues -- if configured to do so:
 */
function vipgoci_results_sort_by_severity(
	$options,
	&$results
) {

	if ( true !== $options['review-comments-sort'] ) {
		return;
	}

	vipgoci_log(
		'Sorting issues in results according to severity before submission',
		array(
		)
	);


	foreach(
		array_keys(
			$results['issues']
		) as $pr_number
	) {
		$current_pr_results = &$results['issues'][ $pr_number ];

		/*
		 * Temporarily add severity
		 * column so we can sort using that.
		 */
		foreach(
			array_keys( $current_pr_results ) as
				$current_pr_result_item_key
		) {
			$current_pr_results[ $current_pr_result_item_key ][ 'severity'] =
				$current_pr_results[ $current_pr_result_item_key ]['issue']['severity'];
		}

		/*
		 * Do the actual sorting.
		 */
		$severity_column  = array_column(
			$current_pr_results,
			'severity'
		);

		array_multisort(
		        $severity_column,
		        SORT_DESC,
		        $current_pr_results
		);

		/*
		 * Remove severity column
		 * afterwards.
		 */
		foreach(
			array_keys( $current_pr_results ) as
				$current_pr_result_item_key
		) {
			unset(
				$current_pr_results[ $current_pr_result_item_key ][ 'severity']
			);
		}
	}
}

/*
 * Sanitize a string, removing any whitespace-characters
 * from the beginning and end, and transform to lowercase.
 */
function vipgoci_sanitize_string( $str ) {
	return strtolower( ltrim( rtrim(
		$str
	) ) );
}

/*
 * Sanitize path, remove any of the specified prefixes
 * if exist.
 */
function vipgoci_sanitize_path_prefix( string $path, array $prefixes ): string {
	foreach( $prefixes as $prefix ) {
		if ( 0 === strpos( $path, $prefix ) ) {
			$path = substr(
				$path,
				strlen( $prefix )
			);

			break;
		}
	}

	return $path;
}
