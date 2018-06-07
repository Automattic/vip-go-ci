<?php

/*
 * Define exit-codes
 */


define( 'VIPGOCI_EXIT_NORMAL',		0 );
define( 'VIPGOCI_EXIT_CODE_ISSUES',	250 );
define( 'VIPGOCI_EXIT_SYSTEM_PROBLEM',	251 );
define( 'VIPGOCI_EXIT_GITHUB_PROBLEM',	252 );
define( 'VIPGOCI_EXIT_USAGE_ERROR',	253 );

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
 * Exit program, using vipgoci_log() to print a
 * message before doing so.
 */

function vipgoci_sysexit(
	$str,
	$debug_data = array(),
	$exit_status = VIPGOCI_EXIT_USAGE_ERROR
) {
	if ( $exit_status === VIPGOCI_EXIT_USAGE_ERROR ) {
		$str = 'Usage: ' . $str;
	}

	vipgoci_log(
		$str,
		$debug_data,
		0
	);

	exit( $exit_status );
}

/*
 * Given a patch-file, the function will return an
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

	/*
	 * In certain edge-cases, line 1 in the patch
	 * will refer to line 0 in the code, which
	 * is not what we want. In these cases, we
	 * simply hard-code line 1 in the patch to match
	 * with line 1 in the code.
	 */
	if (
		( isset( $lines_changed[1] ) ) &&
		(
			( $lines_changed[1] === null ) ||
			( $lines_changed[1] === 0 )
		)
		||
		( ! isset( $lines_changed[1] ) )
	) {
		$lines_changed[1] = 1;
	}

	return $lines_changed;
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

function vipgoci_save_temp_file(
	$file_name_prefix,
	$file_name_extension = null,
	$file_contents
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
		vipgoci_runtime_measure( 'start', 'save_temp_file' );

		$temp_file_save_status = file_put_contents(
			$temp_file_name,
			$file_contents
		);

		vipgoci_runtime_measure( 'stop', 'save_temp_file' );
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
 * allowable file-ending, and if the file presented
 * is in a directory that is can be scanned.
 */
function vipgoci_filter_file_path(
	$filename,
	$filter
) {
	$file_info_extension = pathinfo(
		$filename,
		PATHINFO_EXTENSION
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
			strtolower( $file_info_extension ),
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
		( isset( $filter['skip_folders' ] ) )
	) {
		$file_dirs_arr = explode( '/', $file_dirs );

		foreach ( $file_dirs_arr as $file_dir_item ) {
			if ( in_array(
				$file_dir_item,
				$filter['skip_folders']
			) ) {
				$file_folders_match = true;
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
 */
function vipgoci_scandir_git_repo( $path, $filter ) {
	$result = array();

	vipgoci_log(
		'Fetching git-tree using scandir()',

		array(
			'path' => $path,
			'filter' => $filter,
		),
		2
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
			if ( false === vipgoci_filter_file_path(
				$path . DIRECTORY_SEPARATOR . $value,
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
					'error'		=> 0,
					'warning'	=> 0
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
		$timers[ $type ] = microtime( true );

		return true;
	}

	else if ( 'stop' === $action ) {
		if ( ! isset( $timers[ $type ] ) ) {
			return false;
		}

		$tmp_time = microtime( true ) - $timers[ $type ];

		$runtime[ $type ] += $tmp_time;

		unset( $timers[ $type ] );

		return $tmp_time;
	}
}


/*
 * Keep a counter for stuff we do. For instance,
 * number of GitHub API requests.
 */

function vipgoci_counter_report( $action = null, $type = null, $amount = 1 ) {
	static $counters = array();

	/*
	 * Check usage.
	 */
	if (
		( 'do' !== $action ) &&
		( 'dump' !== $action )
	) {
		return false;
	}

	// Dump all runtimes we have
	if ( 'dump' === $action ) {
		return $counters;
	}


	/*
	 * Being asked to start
	 * collecting, act on that.
	 */

	if ( 'do' === $action ) {
		if ( ! isset( $counters[ $type ] ) ) {
			$counters[ $type ] = 0;
		}

		$counters[ $type ] += $amount;

		return true;
	}
}


/*
 * Go through the given blame-log, and
 * return only the items from the log that
 * are found in $relevant_commit_ids.
 */

function vipgoci_blame_filter_commits(
	$blame_log,
	$relevant_commit_ids
) {

	/*
	 * Loop through each file, get a
	 * 'git blame' log for the file, so
	 * so we can filter out issues not
	 * stemming from commits that are a
	 * part of the current Pull-Request.
	 */

	$blame_log_filtered = array();

	foreach ( $blame_log as $blame_log_item ) {
		if ( ! in_array(
			$blame_log_item['commit_id'],
			$relevant_commit_ids,
			true
		) ) {
			continue;
		}

		$blame_log_filtered[] =
			$blame_log_item;
	}

	return $blame_log_filtered;
}


/*
 * Check if the specified comment exists
 * within an array of other comments --
 * this is used to understand if the specific
 * comment has already been submitted earlier.
 */
function vipgoci_github_comment_match(
	$file_issue_path,
	$file_issue_line,
	$file_issue_comment,
	$comments_made
) {

	/*
	 * Construct an index-key made of file:line.
	 */
	$comment_index_key =
		$file_issue_path .
		':' .
		$file_issue_line;


	if ( ! isset(
		$comments_made[
			$comment_index_key
		]
	)) {
		/*
		 * No match on index-key within the
		 * associative array -- the comment has
		 * not been made, so return false.
		 */
		return false;
	}


	/*
	 * Some comment matching the file and line-number
	 * was found -- figure out if it is definately the
	 * same comment.
	 */

	foreach (
		$comments_made[ $comment_index_key ] as
		$comment_made
	) {
		/*
		 * The comment might contain formatting, such
		 * as "Warning: ..." -- remove all of that.
		 */
		$comment_made_body = str_replace(
			array("**", "Warning", "Error", ":no_entry_sign:", ":exclamation:"),
			array("", "", "", "", ""),
			$comment_made->body
		);

		/*
		 * The comment might be prefixed with ': ',
		 * remove that as well.
		 */
		$comment_made_body = ltrim(
			$comment_made_body,
			': '
		);

		if (
			strtolower( $comment_made_body ) ==
			strtolower( $file_issue_comment )
		) {
			/* Comment found, return true. */
			return true;
		}
	}

	return false;
}

