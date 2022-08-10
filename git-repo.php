<?php
/**
 * Local git repo functions.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Get version of git we are using
 *
 * @return string Git version number.
 */
function vipgoci_git_version(): string {
	$cached_id = array(
		__FUNCTION__,
	);

	$cached_data = vipgoci_cache( $cached_id );

	$git_version_cmd = sprintf(
		'%s %s',
		escapeshellcmd( 'git' ),
		escapeshellarg( '--version' )
	);

	vipgoci_log(
		'Getting git version...' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'cmd' => $git_version_cmd,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	/*
	 * Actually execute
	 */
	$git_output      = '';
	$git_result_code = -255;

	$git_version_results = vipgoci_runtime_measure_exec_with_retry(
		$git_version_cmd,
		array( 0 ),
		$git_output,
		$git_result_code,
		'git_cli',
		false
	);

	$log_detail = array(
		'cmd'             => $git_version_cmd,
		'cmd_result_code' => $git_result_code,
		'output'          => $git_output,
	);

	if ( null === $git_version_results ) {
		vipgoci_sysexit(
			'Unable to run git due to error',
			$log_detail,
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	} elseif ( false === str_contains( $git_output, 'git version' ) ) {
		vipgoci_sysexit(
			'Unable to retrieve git version',
			$log_detail,
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	} else {
		$git_version_results = $git_output;
	}

	$git_version_results = str_replace(
		array( 'git', 'version', ' ', PHP_EOL ),
		array( '', '', '', '' ),
		$git_version_results
	);

	vipgoci_cache( $cached_id, $git_version_results );

	return $git_version_results;
}

/**
 * Determine if repository specified is in
 * sync with the commit-ID specified.
 *
 * If it is not in sync, exit with error.
 *
 * @param string $commit_id      Current commit-ID.
 * @param string $local_git_repo Path to local git repository.
 *
 * @return void Exits with error if repository does not exist or is not in sync.
 */
function vipgoci_gitrepo_ok(
	string $commit_id,
	string $local_git_repo
) :void {
	/*
	 * Check at what revision the local git repository is.
	 *
	 * We do this to make sure the local repository
	 * is actually checked out at the same commit
	 * as the one we are working with.
	 */

	$lgit_head = vipgoci_gitrepo_get_head(
		$local_git_repo
	);

	/*
	 * Check if commit-ID and head are the same, and
	 * return with a status accordingly.
	 */

	if (
		( false !== $commit_id ) &&
		( $commit_id !== $lgit_head )
	) {
		vipgoci_sysexit(
			'Can not use local Git repository, seems not to be in ' .
			'sync with current commit or does not exist',
			array(
				'commit_id'           => $commit_id,
				'local_git_repo'      => $local_git_repo,
				'local_git_repo_head' => $lgit_head,
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}

/**
 * Verify that given string is of valid commit-ID format.
 * Does not verify that it exists.
 *
 * @param string $commit_id Git commit-ID.
 *
 * @return bool True when it is of valid format, else false.
 */
function vipgoci_gitrepo_commit_id_validate_format(
	string $commit_id
) :bool {
	return (
		( ctype_xdigit( $commit_id ) ) &&
		( 40 === strlen( $commit_id ) )
	);
}

/**
 * Get latest commit HEAD in the specified repository.
 * Will return a commit-hash if successful. Note that
 * this function will execute git.
 *
 * @param string $local_git_repo Path to local git repository.
 *
 * @return string Latest commit-ID in the repository's current branch.
 */
function vipgoci_gitrepo_get_head(
	string $local_git_repo
) :string {
	/*
	 * Prepare to execute git; ask git to
	 * operate within a certain path ( -C param ),
	 * to fetch log (one line), and print only
	 * the hash-ID. Catch anything returned to STDERR.
	 */

	$cmd = sprintf(
		'%s -C %s log -n %s --pretty=format:"%s"',
		escapeshellcmd( 'git' ),
		escapeshellarg( $local_git_repo ),
		escapeshellarg( (string) 1 ),
		escapeshellarg( '%H' )
	);

	/*
	 * Actually execute
	 */
	$result_output = '';
	$result_code   = -255;

	$result = vipgoci_runtime_measure_exec_with_retry(
		$cmd,
		array( 0 ),
		$result_output,
		$result_code,
		'git_cli',
		false
	);

	$log_detail = array(
		'cmd'             => $cmd,
		'cmd_result_code' => $result_code,
		'output'          => $result,
		'result_output'   => $result_output,
	);

	if ( null === $result ) {
		vipgoci_sysexit(
			'Unable to run git due to error',
			$log_detail,
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	/*
	 * If string, trim any whitespace characters away.
	 * Validation is then performed.
	 */
	if ( is_string( $result_output ) ) {
		$result_output = trim(
			$result_output
		);

		$result_output = trim(
			$result_output,
			"'\""
		);
	}

	/*
	 * Validate the outputted string. Exit if it does not validate.
	 */
	if (
		( ! is_string( $result_output ) ) ||
		( false === vipgoci_gitrepo_commit_id_validate_format( $result_output ) )
	) {
		vipgoci_sysexit(
			'Unexpected git commit-ID retrieved',
			$log_detail,
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	return $result_output;
}

/**
 * Get the current branch of git repository specified.
 *
 * @param string $local_git_repo Path to local git repository.
 *
 * @return null|string Current branch as string on success, null on failure.
 */
function vipgoci_gitrepo_branch_current_get(
	string $local_git_repo
) :null|string {
	$cmd = sprintf(
		'%s -C %s branch',
		escapeshellcmd( 'git' ),
		escapeshellarg( $local_git_repo ),
	);

	/*
	 * Actually execute
	 */
	$results_output      = '';
	$results_result_code = -255;

	$results = vipgoci_runtime_measure_exec_with_retry(
		$cmd,
		array( 0 ),
		$results_output,
		$results_result_code,
		'git_cli',
		true
	);

	if ( null === $results ) {
		vipgoci_sysexit(
			'Unable to run git due to error',
			array(
				'cmd'    => $cmd,
				'output' => $results,
			),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	/*
	 * Split results into array
	 */
	$results = explode(
		"\n",
		$results
	);

	/*
	 * Filter away any branch-names that are not active.
	 */
	$results = array_filter(
		$results,
		function( $line ) {
			if ( false === strpos(
				$line,
				'*'
			) ) {
				return false;
			}

			return true;
		}
	);

	/*
	 * Remove spaces and "*" from
	 * the last branch-name, which is the
	 * active one if there at all.
	 */
	$results = array_map(
		function( $line ) {
			return str_replace(
				array(
					' ',
					'*',
				),
				array(
					'',
					'',
				),
				$line
			);
		},
		$results
	);

	/*
	 * Recreate array with
	 * new index keys.
	 */
	$results = array_values(
		$results
	);

	if ( ! empty(
		$results
	) ) {
		return $results[0];
	} else {
		return null;
	}
}

/**
 * Fetch "tree" of the repository; a tree
 * of files that are part of the commit
 * specified.
 *
 * Allows filtering out files that the
 * caller does only want to see.
 *
 * @param array      $options   Options array for the program.
 * @param string     $commit_id Commit-ID of current commit.
 * @param null|array $filter    Filter to apply.
 *
 * @return array Array with files in repository as items.
 */
function vipgoci_gitrepo_fetch_tree(
	array $options,
	string $commit_id,
	null|array $filter = null
) :array {
	// Check for cached version.
	$cached_id = array(
		__FUNCTION__,
		$options['repo-owner'],
		$options['repo-name'],
		$commit_id,
		$options['token'],
		$filter,
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching tree info' .
			( $cached_data ? ' (cached)' : '' ),
		array(
			'repo_owner' => $options['repo-owner'],
			'repo_name'  => $options['repo-name'],
			'commit_id'  => $commit_id,
			'filter'     => $filter,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	vipgoci_gitrepo_ok(
		$commit_id,
		$options['local-git-repo']
	);

	// Actually get files.
	$files_arr = vipgoci_scandir_git_repo(
		$options['local-git-repo'],
		$filter
	);

	/*
	 * Cache the results and return
	 */
	vipgoci_cache(
		$cached_id,
		$files_arr
	);

	return $files_arr;
}

/**
 * Fetch from the local git repository a particular file
 * which is a part of a commit. Will return the file (raw),
 * or false on error.
 *
 * @param string $repo_owner     Repository owner.
 * @param string $repo_name      Repository name.
 * @param string $github_token   GitHub token to use to make GitHub API requests.
 * @param string $commit_id      Current commit-ID.
 * @param string $file_name      File name whose content to fetch.
 * @param string $local_git_repo Path to local git repository.
 *
 * @return false|string String with file contents on success, false on failure.
 */
function vipgoci_gitrepo_fetch_committed_file(
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id,
	string $file_name,
	string $local_git_repo
) :false|string {
	vipgoci_gitrepo_ok(
		$commit_id,
		$local_git_repo
	);

	vipgoci_log(
		'Fetching file-contents from local Git repository',
		array(
			'repo_owner'     => $repo_owner,
			'repo_name'      => $repo_name,
			'commit_id'      => $commit_id,
			'filename'       => $file_name,
			'local_git_repo' => $local_git_repo,
		)
	);

	/*
	 * If everything seems fine, return the file.
	 */
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'git_repo_fetch_file' );

	$file_contents_tmp = @file_get_contents( // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$local_git_repo . '/' . $file_name
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'git_repo_fetch_file' );

	return $file_contents_tmp;
}

/**
 * Get 'git blame' log for a particular file,
 * using a local Git repository.
 *
 * @param string $commit_id      Commit-ID to use.
 * @param string $file_name      File name whose 'git blame' to get.
 * @param string $local_git_repo Path to local git repository.
 *
 * @return array Blame log entries, one line per array item.
 */
function vipgoci_gitrepo_blame_for_file(
	string $commit_id,
	string $file_name,
	string $local_git_repo
): array {
	vipgoci_gitrepo_ok(
		$commit_id,
		$local_git_repo
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'git_repo_blame_for_file' );

	vipgoci_log(
		'Fetching \'git blame\' log from Git repository for file',
		array(
			'commit_id'      => $commit_id,
			'file_name'      => $file_name,
			'local_git_repo' => $local_git_repo,
		)
	);

	/*
	 * Compose command to get blame-log
	 */

	$cmd = sprintf(
		'%s -C %s blame --line-porcelain %s',
		escapeshellcmd( 'git' ),
		escapeshellarg( $local_git_repo ),
		escapeshellarg( $file_name )
	);

	/*
	 * Actually execute
	 */
	$result_output = '';
	$result_code   = -255;

	$result = vipgoci_runtime_measure_exec_with_retry(
		$cmd,
		array( 0 ),
		$result_output,
		$result_code,
		'git_cli',
		true
	);

	if ( null === $result ) {
		vipgoci_sysexit(
			'Unable to run git due to error',
			array(
				'cmd'    => $cmd,
				'output' => $result,
			),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	/*
	 * Process the output from git,
	 * split each line into an array.
	 */

	$blame_log = array();

	$result = explode(
		"\n",
		$result
	);

	$current_commit = array();

	foreach ( $result as $result_line ) {
		/*
		 * First split the line into an array
		 */
		$result_line_arr = explode(
			' ',
			$result_line
		);

		/*
		 * Try to figure out if the line is contains
		 * a commit-ID and line-number, such as this:
		 *
		 * 6c85fe619e39cc7beefb1faf0102d9d872bc7bb2 3 3
		 *
		 * and if so, store them.
		 */
		if (
			( count( $result_line_arr ) >= 3 ) &&
			( true === vipgoci_gitrepo_commit_id_validate_format( $result_line_arr[0] ) )
		) {
			$current_commit = array(
				'commit_id' => $result_line_arr[0], // Commit-ID field.
				'number'    => $result_line_arr[2], // Line number field.
			);
		} elseif (
			( count( $result_line_arr ) >= 2 ) &&
			( 'filename' === $result_line_arr[0] )
		) {
			/*
			 * When the first string on the line is 'filename',
			 * store the filename it self. Do so using
			 * a method that will save spaces and so forth in the
			 * filename.
			 */
			$tmp_file_arr = $result_line_arr;
			unset( $tmp_file_arr[0] );

			$current_commit['filename'] = implode( ' ', $tmp_file_arr );

			unset( $tmp_file_arr );
		} elseif (
			( count( $result_line_arr ) >= 1 ) &&
			( in_array(
				$result_line_arr[0],
				array(
					'author',
					'author-mail',
					'author-time',
					'author-tz',
					'boundary',
					'committer',
					'committer-mail',
					'committer-time',
					'committer-tz',
					'summary',
					'previous',
				),
				true
			) )
		) {
			/*
			 * If we see any of these keywords,
			 * ignore them.
			 */
			continue;
		} elseif (
			( isset( $result_line[0] ) ) &&
			( ord( $result_line[0] ) === 9 )
		) {
			/*
			 * When line starts with a tab,
			 * this is our code -- save that.
			 */

			$tmp_content = substr(
				$result_line,
				1
			);

			$current_commit['content'] = $tmp_content;
		}

		/*
		 * If we have got commit-ID, line-number, content
		 * and filename, we can construct a return array
		 */

		if (
			( isset( $current_commit['commit_id'] ) ) &&
			( isset( $current_commit['number'] ) ) &&
			( isset( $current_commit['filename'] ) ) &&
			( isset( $current_commit['content'] ) )
		) {
			$blame_log[] = array(
				'commit_id' => $current_commit['commit_id'],
				'file_name' => $current_commit['filename'],
				'line_no'   => (int) $current_commit['number'],
				'content'   => $current_commit['content'],
			);

			$current_commit = array();
		}
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'git_repo_blame_for_file' );

	return $blame_log;
}

/**
 * Get contents of a particular file at a
 * particular commit-ID from the local git
 * repository.
 *
 * @param string $commit_id                     Commit-ID to use.
 * @param string $file_name                     File name whose content to get.
 * @param string $local_git_repo                Path to local git repository.
 * @param string $local_git_repo_head_commit_id Commit-ID of HEAD of local git repository (for verification).
 *
 * @return null|string File contents as string on success, null on failure.
 */
function vipgoci_gitrepo_get_file_at_commit(
	string $commit_id,
	string $file_name,
	string $local_git_repo,
	string $local_git_repo_head_commit_id
) :null|string {
	/*
	 * Check if repository is looking good.
	 */
	vipgoci_gitrepo_ok(
		$local_git_repo_head_commit_id,
		$local_git_repo
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'git_repo_get_file_at_commit' );

	vipgoci_log(
		'Fetching contents of a particular file from the local git repository',
		array(
			'commit_id'                     => $commit_id,
			'file_name'                     => $file_name,
			'local_git_repo'                => $local_git_repo,
			'local_git_repo_head_commit_id' => $local_git_repo_head_commit_id,
		)
	);

	/*
	 * Compose command to get the file contents.
	 */

	$cmd = sprintf(
		'%s -C %s show %s:%s',
		escapeshellcmd( 'git' ),
		escapeshellarg( $local_git_repo ),
		escapeshellarg( $commit_id ),
		escapeshellarg( $file_name )
	);

	/*
	 * Actually execute
	 */
	$result_output = '';
	$result_code   = -255;

	$result = vipgoci_runtime_measure_exec_with_retry(
		$cmd,
		array( 0, 128 ),
		$result_output,
		$result_code,
		'git_cli',
		true
	);

	if ( null === $result ) {
		vipgoci_sysexit(
			'Unable to run git due to error',
			array(
				'cmd'    => $cmd,
				'output' => $result,
			),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'git_repo_get_file_at_commit' );

	/*
	 * If the file did not exist in
	 * this revision, return null.
	 */
	if ( strpos(
		strtolower( $result ),
		'fatal: path '
	) === 0 ) {
		return null;
	}

	return $result;
}

/**
 * Initialise submodules for the given local git repository.
 *
 * @param string $local_git_repo Path to local git repository.
 *
 * @return string Result of running "submodule init" and
 *                "submodule update" on success. Will exit
 *                on failure.
 */
function vipgoci_gitrepo_submodules_setup(
	string $local_git_repo
) :string {
	$cmd = sprintf(
		'( %s -C %s submodule init && %s -C %s submodule update )',
		escapeshellcmd( 'git' ),
		escapeshellarg( $local_git_repo ),
		escapeshellcmd( 'git' ),
		escapeshellarg( $local_git_repo )
	);

	/*
	 * Actually execute
	 */
	$result_output = '';
	$result_code   = -255;

	$result = vipgoci_runtime_measure_exec_with_retry(
		$cmd,
		array( 0 ),
		$result_output,
		$result_code,
		'git_cli',
		true
	);

	if ( null === $result ) {
		vipgoci_sysexit(
			'Unable to run git due to error',
			array(
				'cmd'    => $cmd,
				'output' => $result,
			),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	return $result;
}

/**
 * Get submodules for the given local git repository.
 *
 * @param string $local_git_repo Path to local git repository.
 *
 * @return array Submodules present as array, each submodule as associative array.
 */
function vipgoci_gitrepo_submodules_list(
	string $local_git_repo
) :array {
	/* Check for cached version */
	$cached_id = array(
		__FUNCTION__,
		$local_git_repo,
	);

	$cached_data = vipgoci_cache( $cached_id );

	if ( false !== $cached_data ) {
		/* Found cached version, return it. */
		return $cached_data;
	}

	/*
	 * No cached version found, get submodule list,
	 * process and return.
	 */

	$cmd = sprintf(
		'%s -C %s submodule',
		escapeshellcmd( 'git' ),
		escapeshellarg( $local_git_repo ),
	);

	/*
	 * Actually execute
	 */
	$result_output = '';
	$result_code   = -255;

	$result = vipgoci_runtime_measure_exec_with_retry(
		$cmd,
		array( 0 ),
		$result_output,
		$result_code,
		'git_cli',
		true
	);

	if ( null === $result ) {
		vipgoci_sysexit(
			'Unable to run git due to error',
			array(
				'cmd'    => $cmd,
				'output' => $result,
			),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	$result = explode(
		"\n",
		$result
	);

	/*
	 * Clean up results, remove whitespace etc.
	 */
	$result = array_map(
		function( $str ) {
			$str = trim(
				$str
			);

			$arr = explode(
				' ',
				$str
			);

			if ( count( $arr ) !== 3 ) {
				return array();
			}

			$arr[2] = trim(
				$arr[2],
				'()'
			);

			return array(
				'commit_id'      => $arr[0],
				'submodule_path' => $arr[1],
				'submodule_tag'  => $arr[2],
			);
		},
		$result
	);

	/*
	 * Remove any array items that seem invalid.
	 */
	$result = array_filter(
		$result,
		function( $arr_item ) {
			if ( count( $arr_item ) === 3 ) {
				return true;
			}

			return false;
		}
	);

	/*
	 * Cache result.
	 */
	vipgoci_cache(
		$cached_id,
		$result
	);

	return $result;
}

/**
 * Get submodule for the given path,
 * if is a submodule.
 *
 * @param string $local_git_repo Path to local git repository.
 * @param string $file_path      Relative path to file.
 *
 * @return null|array Submodule array on success, null on failure.
 */
function vipgoci_gitrepo_submodule_file_path_get(
	string $local_git_repo,
	string $file_path
) :null|array {
	$submodules_list = vipgoci_gitrepo_submodules_list(
		$local_git_repo
	);

	foreach (
		$submodules_list as $submodule_item
	) {
		if ( strpos(
			$file_path,
			$submodule_item['submodule_path'] . '/'
		) === 0 ) {
			return $submodule_item;
		}
	}

	return null;
}

/**
 * Get URL for submodule from repository config.
 *
 * @param string $local_git_repo Path to local git repository.
 * @param string $submodule_path Relative path to submodule.
 *
 * @return null|string Submodule URL as string on success, null on failure.
 */
function vipgoci_gitrepo_submodule_get_url(
	string $local_git_repo,
	string $submodule_path
) :null|string {
	/* Check for cached version */
	$cached_id = array(
		__FUNCTION__,
		$local_git_repo,
		$submodule_path,
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching GitHub repository URL for submodule' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'local-git-repo' => $local_git_repo,
			'submodule_path' => $submodule_path,
		)
	);

	if ( false !== $cached_data ) {
		/* Found cached version, return it. */
		return $cached_data;
	}

	$git_modules_parsed = parse_ini_file(
		$local_git_repo . '/.gitmodules',
		true
	);

	if ( false === $git_modules_parsed ) {
		return null;
	}

	$ret_val = null;

	foreach (
		$git_modules_parsed as
			$git_module_folder => $git_module_info
	) {
		if ( $git_module_info['path'] === $submodule_path ) {
			$dot_git_pos = strrpos(
				$git_module_info['url'],
				'.git'
			);

			$ret_val = $git_module_info['url'];

			if ( false !== $dot_git_pos ) {
				$ret_val = substr(
					$ret_val,
					0,
					$dot_git_pos
				);
			}

			break;
		}
	}

	vipgoci_cache(
		$cached_id,
		$ret_val
	);

	vipgoci_log(
		'Fetched Github repository URL',
		array(
			'submodule_path'    => $submodule_path,
			'submodule_git_url' => $ret_val,
		)
	);

	return $ret_val;
}

/**
 * Fetch diff from git repository, unprocessed.
 * Results are not cached.
 *
 * @param string $local_git_repo  Path to local git repository.
 * @param string $commit_id_a     Baseline commit.
 * @param string $commit_id_b     Commit for comparison.
 *
 * @return null|array Null on failure, array with results on success. Example array:
 *  array(
 *    'statistics'  => array(
 *      'additions' => 25,
 *      'deletions' => 100,
 *      'changes'   => 125,
 *    ),
 *    'files'       => array(
 *      'file-1.php' => array(
 *        'filename'  => 'file-1.php',
 *        'patch'     => '--- a/file-1.php... +++ b/file-1.php ...',
 *        'status'    => 'added',
 *        'additions' => 25,
 *        'deletions' => 100,
 *        'changes'   => 125,
 *      ),
 *      'file-2.php' => ...,
 *    ),
 *  );
 */
function vipgoci_gitrepo_diffs_fetch_unfiltered(
	string $local_git_repo,
	string $commit_id_a,
	string $commit_id_b
): null|array {
	/*
	 * Check for a cached copy of the diffs
	 */
	$cached_id = array(
		__FUNCTION__,
		$local_git_repo,
		$commit_id_a,
		$commit_id_b,
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching diffs between two commits ' .
			'from git repository' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'local_git_repo' => $local_git_repo,
			'commit_id_a'    => $commit_id_a,
			'commit_id_b'    => $commit_id_b,
		)
	);

	/*
	 * If cached, return the data.
	 */

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	/*
	 * No cached data. Run git to get the data.
	 *
	 * Make sure we use a git diff branch1...branch2
	 * as that is what GitHub uses: https://docs.github.com/en/github/collaborating-with-issues-and-pull-requests/about-comparing-branches-in-pull-requests#three-dot-and-two-dot-git-diff-comparisons
	 */
	$git_diff_cmd = sprintf(
		'%s --no-pager -C %s diff --no-color %s',
		escapeshellcmd( 'git' ),
		escapeshellarg( $local_git_repo ),
		escapeshellarg( $commit_id_a . '...' . $commit_id_b )
	);

	vipgoci_log(
		'Running git...',
		array(
			'cmd' => $git_diff_cmd,
		)
	);

	/*
	 * Actually execute.
	 */
	$git_diff_results_output = '';
	$git_diff_results_code   = -255;

	$git_diff_results = vipgoci_runtime_measure_exec_with_retry(
		$git_diff_cmd,
		array( 0 ),
		$git_diff_results_output,
		$git_diff_results_code,
		'git_cli',
		true
	);

	if ( null === $git_diff_results ) {
		vipgoci_log(
			'Unable to run git due to error',
			array(
				'cmd'    => $git_diff_cmd,
				'output' => $git_diff_results,
			),
		);

		return null;
	}

	/*
	 * Check if there are any problems,
	 * return with error if there are any.
	 */
	if ( strpos(
		$git_diff_results,
		'fatal: '
	) === 0 ) {
		vipgoci_log(
			'Unexpected problem while running git',
			array(
				'cmd'    => $git_diff_cmd,
				'output' => $git_diff_results,
			),
		);

		return null;
	}

	/*
	 * Prepare results array.
	 */
	$diff_results = array(
		'files'      => array(),
		'statistics' => array(
			VIPGOCI_GIT_DIFF_CALC_CHANGES['+'] => 0,
			VIPGOCI_GIT_DIFF_CALC_CHANGES['-'] => 0,
			'changes'                          => 0,
		),
	);

	/*
	 * Split results into array
	 */

	$git_diff_results = explode(
		PHP_EOL,
		$git_diff_results
	);

	/*
	 * Initialize stateful variables.
	 */

	$cur_file              = null;
	$cur_file_path_cleaned = false;

	$cur_mode                  = 'info'; // Other mode is 'patch'.
	$cur_file_first_patch_line = true;
	$cur_file_status           = null;

	$cur_file_minus              = null;
	$cur_file_minus_path_cleaned = false;

	$cur_file_plus              = null;
	$cur_file_plus_path_cleaned = false;

	$cur_file_previous_filename = null;

	$cur_file_patch_buffer = '';

	/*
	 * Process the output of git diff, one line
	 * at a time. Update state-variables as we go
	 * so we can keep track of what is happening.
	 *
	 * The output looks like this:
	 *
	 *  diff --git a/renamed-file2.txt b/renamed-file2.txt
	 *  deleted file mode 100644
	 *  index 55ab87b..0000000
	 *  --- a/renamed-file2.txt
	 *  +++ /dev/null
	 *  @@ -1,2 +0,0 @@
	 *  -# vip-go-ci-testing
	 *  -Pull-Requests, commits a
	 *  diff --git ...
	 *  ...
	 */

	foreach ( $git_diff_results as $git_result_item ) {
		/*
		 * Split each line into array at spaces,
		 * making it easy to process each line of results.
		 */
		$git_result_item_arr = explode(
			' ',
			$git_result_item
		);

		/*
		 * Check if we are seeing 'diff --git ...
		 * and if so switch to info mode, get file names
		 * and set other state variables.
		 */
		if (
			( ! empty( $git_result_item_arr ) ) &&
			( 'diff' === $git_result_item_arr[0] ) &&
			( '--git' === $git_result_item_arr[1] )
		) {
			$cur_mode = 'info';

			$cur_file              = null;
			$cur_file_path_cleaned = false;

			$cur_file_status = null;

			if ( 4 === count( $git_result_item_arr ) ) {
				$cur_file_minus = $git_result_item_arr[2];
			} else {
				$cur_file_minus = null;
			}

			$cur_file_minus_path_cleaned = false;

			if ( 4 === count( $git_result_item_arr ) ) {
				$cur_file_plus = $git_result_item_arr[3];
			} else {
				$cur_file_plus = null;
			}

			$cur_file_plus_path_cleaned = false;

			$cur_file_previous_filename = null;

			$cur_file_patch_buffer = '';
		} elseif (
			( 'info' === $cur_mode ) &&
			( ! empty( $git_result_item_arr ) )
		) {
			if (
				( 'new' === $git_result_item_arr[0] ) &&
				( 'mode' === $git_result_item_arr[1] )
			) {
				$cur_file_status = 'modified';
			} elseif (
				( 'rename' === $git_result_item_arr[0] ) &&
				( 'from' === $git_result_item_arr[1] )
			) {
				$cur_file_previous_filename = $git_result_item_arr[2];
			} elseif ( '---' === $git_result_item_arr[0] ) {
				$cur_file_minus = vipgoci_gitrepo_get_file_path_from_diff(
					vipgoci_gitrepo_diffs_clean_extra_whitespace(
						$git_result_item_arr
					),
					1
				);

				$cur_file_minus_path_cleaned = false;
			} elseif ( '+++' === $git_result_item_arr[0] ) {
				$cur_file_plus = vipgoci_gitrepo_get_file_path_from_diff(
					vipgoci_gitrepo_diffs_clean_extra_whitespace(
						$git_result_item_arr
					),
					1
				);

				$cur_file_plus_path_cleaned = false;
			} elseif (
				'@@' === $git_result_item_arr[0]
			) {
				$cur_mode = 'patch';

				$cur_file_first_patch_line = true;
				// Continue processing. Data may be collected below.
			}
		}

		/*
		 * Remove possible git stuff in paths.
		 * Avoid cleaning the same path twice.
		 */
		if (
			( false === $cur_file_path_cleaned ) &&
			( null !== $cur_file )
		) {
			$cur_file = vipgoci_sanitize_path_prefix(
				$cur_file,
				array( 'a/', 'b/' )
			);

			$cur_file_path_cleaned = true;
		}

		if (
			( false === $cur_file_minus_path_cleaned ) &&
			( null !== $cur_file_minus )
		) {
			$cur_file_minus = vipgoci_sanitize_path_prefix(
				$cur_file_minus,
				array( 'a/' )
			);

			$cur_file_minus_path_cleaned = true;
		}

		if (
			( false === $cur_file_plus_path_cleaned ) &&
			( null !== $cur_file_plus )
		) {
			$cur_file_plus = vipgoci_sanitize_path_prefix(
				$cur_file_plus,
				array( 'b/' )
			);

			$cur_file_plus_path_cleaned = true;
		}

		/*
		 * Logic to handle file names.
		 */
		if (
			( ! empty( $cur_file_minus ) ) ||
			( ! empty( $cur_file_plus ) )
		) {
			if ( $cur_file_minus === $cur_file_plus ) {
				$cur_file        = $cur_file_plus;
				$cur_file_status = 'modified';
			} else {
				/*
				 * If the file names do not match,
				 * and either is /dev/null, then
				 * it is a new file or a removed file.
				 */
				if ( '/dev/null' === $cur_file_minus ) {
					$cur_file        = $cur_file_plus;
					$cur_file_status = 'added';
				} elseif ( '/dev/null' === $cur_file_plus ) {
					$cur_file        = $cur_file_minus;
					$cur_file_status = 'removed';
				} else {
					/*
					 * No match and no mention of /dev/null,
					 * so the file must have been renamed.
					 */

					$cur_file        = $cur_file_plus;

					/*
					 * Avoid resetting status if determined
					 * that content has been modified elsewhere.
					 */
					if ( 'modified' !== $cur_file_status ) {
						$cur_file_status = 'renamed';
					}
				}
			}
		}

		/*
		 * If the file is not yet registered in the
		 * 'diff' array, do so now. This will include
		 * files that only have permission changes.
		 */
		if (
			( null !== $cur_file ) &&
			( null !== $cur_file_status ) &&
			( ! isset( $diff_results['files'][ $cur_file ] ) )
		) {
			$diff_results['files'][ $cur_file ] = array(
				'filename'  => $cur_file,
				'patch'     => '',
				'status'    => $cur_file_status,
				'additions' => 0,
				'deletions' => 0,
				'changes'   => 0,
			);
		}

		if (
			( null !== $cur_file ) &&
			( null !== $cur_file_status )
		) {
			/*
			 * Update file-status each time we loop
			 * as the calculated status might change.
			 */
			$diff_results['files'][ $cur_file ]['status'] =
				$cur_file_status;
		}

		if (
			( null !== $cur_file ) &&
			( null !== $cur_file_previous_filename )
		) {
			$diff_results['files'][ $cur_file ]['previous_filename'] =
				$cur_file_previous_filename;
		}

		if ( 'patch' !== $cur_mode ) {
			/* Not in patch mode, so do not collect patch */
			continue;
		}

		/*
		 * Sanity check.
		 */
		if ( empty( $cur_file ) ) {
			vipgoci_log(
				'Problem when getting git diff, no file name found in patch',
				array(
					'local_git_repo' => $local_git_repo,
					'commit_id_a'    => $commit_id_a,
					'commit_id_b'    => $commit_id_b,
					'cur_file'       => $cur_file,
					'cur_file_minus' => $cur_file_minus,
					'cur_file_plus'  => $cur_file_plus,
				)
			);

			continue;
		}

		/*
		 * Keep statistics on lines changed (if any).
		 */
		if (
			( strlen( $git_result_item ) > 0 ) &&
			( isset( VIPGOCI_GIT_DIFF_CALC_CHANGES[ $git_result_item[0] ] ) )
		) {
			/*
			 * Statistics specific for a file
			 */
			$diff_results
				['files']
				[ $cur_file ]
				[ VIPGOCI_GIT_DIFF_CALC_CHANGES[ $git_result_item[0] ] ]++;

			$diff_results['files'][ $cur_file ]['changes']++;

			/*
			 * Overall statistics
			 */
			$diff_results['statistics'][ VIPGOCI_GIT_DIFF_CALC_CHANGES[ $git_result_item[0] ] ]++;

			$diff_results['statistics']['changes']++;

			/*
			 * Avoid incorrect status for file when file has been
			 * renamed and modified.
			 */
			if (
				( $diff_results['files'][ $cur_file ]['changes'] > 0 ) &&
				( 'renamed' === $cur_file_status )
			) {
				$cur_file_status = 'modified';

				$diff_results['files'][ $cur_file ]['status'] = $cur_file_status;
			}
		}

		/*
		 * If on first line of patch, do not
		 * add PHP_EOL. We do that on future lines
		 * though.
		 */
		if ( true === $cur_file_first_patch_line ) {
			$cur_file_first_patch_line = false;
		} elseif ( false === $cur_file_first_patch_line ) {
			/*
			 * If not on first line of patch,
			 * and the current line is an empty
			 * string, do not add a newline. However,
			 * keep it in a buffer to be added on the
			 * next round.
			 */
			if ( ! empty( $cur_file_patch_buffer ) ) {
				$diff_results['files'][ $cur_file ]['patch'] .=
					$cur_file_patch_buffer;

				$cur_file_patch_buffer = '';
			}

			if ( '' === $git_result_item ) {
				$cur_file_patch_buffer .= PHP_EOL;
			} else {
				$diff_results['files'][ $cur_file ]['patch'] .= PHP_EOL;
			}
		}

		$diff_results['files'][ $cur_file ]['patch'] .= $git_result_item;
	}

	vipgoci_log(
		'Fetched git diff from local git repository',
		array(
			'statistics'           => $diff_results['statistics'],
			'files_partial_20_max' => array_slice(
				array_keys(
					$diff_results['files']
				),
				0,
				20
			),
		)
	);

	vipgoci_cache( $cached_id, $diff_results );

	return $diff_results;
}

/**
 * Join path array into string and return. Remove first array
 * items if specified.
 *
 * @param array $path_arr       Path as array.
 * @param int   $index_to_start From what element in the array to start the join.
 *
 * @return string File path.
 */
function vipgoci_gitrepo_get_file_path_from_diff(
	array $path_arr,
	int $index_to_start = 0,
) :string {
	$removed_items = false;

	$path_arr_length = count( $path_arr );

	for (
		$i = 0;
		$i < $index_to_start && $index_to_start < $path_arr_length;
		$i++
	) {
		unset( $path_arr[ $i ] );

		$removed_items = true;
	}

	if ( true === $removed_items ) {
		$path_arr = array_values( $path_arr );
	}

	$path = join( ' ', $path_arr );

	return $path;
}

/**
 * Git and/or a pager may leave a "\t" at the end of a file path,
 * even though we request no such pager, but only when spaces are in
 * file names. The source of this issue is unknown despite
 * experimentation and research.
 *
 * Here we remove the extra "\t".
 *
 * @param array $path_arr Path to file to clean as array.
 *
 * @return array Cleaned path.
 */
function vipgoci_gitrepo_diffs_clean_extra_whitespace(
	array $path_arr
) :array {
	$path_arr_length = count( $path_arr );

	if ( $path_arr_length <= 1 ) {
		return $path_arr;
	}

	$last_string = $path_arr[ $path_arr_length - 1 ];

	if ( true === str_ends_with( $last_string, "\t" ) ) {
		$last_string = rtrim( $last_string, "\t" );

		$path_arr[ $path_arr_length - 1 ] = $last_string;
	}

	return $path_arr;
}

/**
 * Fetch diffs between two commits,
 * filter the results if requested.
 *
 * Needs arguments both for local git
 * repo and GitHub API as fallback.
 *
 * @param string     $local_git_repo          Path to local git repository.
 * @param string     $repo_owner              Repository owner.
 * @param string     $repo_name               Repository name.
 * @param string     $github_token            GitHub token to use to make GitHub API requests.
 * @param string     $commit_id_a             Baseline commit.
 * @param string     $commit_id_b             Commit for comparison.
 * @param bool       $renamed_files_also      If to include renamed files.
 * @param bool       $removed_files_also      If to include removed files.
 * @param bool       $permission_changes_also If to include files whose permissions were changed.
 * @param null|array $filter                  Filter to apply to results.
 *
 * @return array Array with results. For example:
 *  array(
 *    'statistics'  => array(
 *      'additions' => 25,
 *      'deletions' => 100,
 *      'changes'   => 125,
 *    ),
 *    'files'       => array(
 *      'file-1.php' => array(
 *        'filename'  => 'file-1.php',
 *        'patch'     => '--- a/file-1.php... +++ b/file-1.php ...',
 *        'status'    => 'added',
 *        'additions' => 25,
 *        'deletions' => 100,
 *        'changes'   => 125,
 *      ),
 *      'file-2.php' => ...,
 *    ),
 *    'data_source' => 'local-git-repo',
 *  );
 */
function vipgoci_git_diffs_fetch(
	string $local_git_repo,
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id_a,
	string $commit_id_b,
	bool $renamed_files_also = false,
	bool $removed_files_also = true,
	bool $permission_changes_also = false,
	null|array $filter = null
): array {
	/*
	 * Check if we have a preference whether to
	 * use local git repository or GitHub API.
	 */
	$cached_id = array(
		__FUNCTION__,
		$local_git_repo,
		$repo_owner,
		$repo_name,
		$commit_id_a,
		$commit_id_b,
	);

	$github_api_preferred = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching diffs between two commits',
		array(
			'local_git_repo'       => $local_git_repo,
			'repo_owner'           => $repo_owner,
			'repo_name'            => $repo_name,
			'commit_id_a'          => $commit_id_a,
			'commit_id_b'          => $commit_id_b,
			'github_api_preferred' => $github_api_preferred,
		)
	);

	$diff_results = null;

	/*
	 * If there is no preference, use local git repository
	 * to fetch diff.
	 */
	if ( false === $github_api_preferred ) {
		$diff_results = vipgoci_gitrepo_diffs_fetch_unfiltered(
			$local_git_repo,
			$commit_id_a,
			$commit_id_b
		);

		$diff_results_data_source = VIPGOCI_GIT_DIFF_DATA_SOURCE_GIT_REPO;
	}

	if ( null === $diff_results ) {
		/*
		 * Problems with getting diff from local git repo -- or preference
		 * for GitHub API, so use that.
		 *
		 * This can happen for example:
		 * - When only part of the repository was fetched
		 * - When the commit-ID refers to a repository
		 *   outside of this one, for example when a pull request
		 *   refers to a forked repository.
		 * - When there is an I/O error with the local filesystem.
		 * - Previously, we had a problem with local git repo and
		 *   and saved info in cache that GitHub API should be used.
		 */

		if ( false === $github_api_preferred ) {
			vipgoci_log(
				'Requesting diff from GitHub API, ' .
					'issues with local git repo',
				array(
					'repo-owner'  => $repo_owner,
					'repo-name'   => $repo_name,
					'commit_id_a' => $commit_id_a,
					'commit_id_b' => $commit_id_b,
				),
				0,
				true
			);
		}

		$diff_results = vipgoci_github_diffs_fetch_unfiltered(
			$repo_owner,
			$repo_name,
			$github_token,
			$commit_id_a,
			$commit_id_b
		);

		$diff_results_data_source =
			VIPGOCI_GIT_DIFF_DATA_SOURCE_GITHUB_API;

		vipgoci_cache(
			$cached_id,
			true
		);
	}

	/*
	 * Loop through all files, save patch in an array,
	 * along with statistics, note where the data came from.
	 */

	$results = array(
		'statistics'   => array(
			VIPGOCI_GIT_DIFF_CALC_CHANGES['+'] => 0,
			VIPGOCI_GIT_DIFF_CALC_CHANGES['-'] => 0,
			'changes'                          => 0,
		),

		'files'        => array(),
		'files_status' => array(),
		'data_source'  => $diff_results_data_source,
	);

	foreach ( $diff_results['files'] as $file_item ) {
		/*
		 * Skip removed files if so requested.
		 */

		if (
			( false === $removed_files_also ) &&
			( 'removed' === $file_item['status'] )
		) {
			continue;
		}

		/*
		 * Skip renamed files if so requested.
		 */
		if (
			( false === $renamed_files_also ) &&
			( 'renamed' === $file_item['status'] )
		) {
			continue;
		}

		/*
		 * If file is modified, but there are no changed lines,
		 * the file likely had only permission-changes made to
		 * it. Skip these, if so requested.
		 */
		if (
			( false === $permission_changes_also ) &&
			( 'modified' === $file_item['status'] ) &&
			( 0 === $file_item['changes'] )
		) {
			continue;
		}

		/*
		 * Allow filtering of files returned.
		 */

		if (
			( null !== $filter ) &&
			( false === vipgoci_filter_file_path(
				$file_item['filename'], // Relative path.
				$filter
			) )
		) {
			continue;
		}

		/*
		 * In case of no patch specified by
		 * GitHub, we add it.
		 */
		if ( ! isset( $file_item['patch'] ) ) {
			$file_item['patch'] = null;
		}

		$results['files'][ $file_item['filename'] ] =
			$file_item['patch'];

		/*
		 * Add status information for file.
		 */
		$results['files_status'][ $file_item['filename'] ] =
			$file_item['status'];

		/*
		 * Add this file to statistics
		 */
		$results['statistics'][ VIPGOCI_GIT_DIFF_CALC_CHANGES['+'] ] +=
			$file_item[ VIPGOCI_GIT_DIFF_CALC_CHANGES['+'] ];

		$results['statistics'][ VIPGOCI_GIT_DIFF_CALC_CHANGES['-'] ] +=
			$file_item[ VIPGOCI_GIT_DIFF_CALC_CHANGES['-'] ];

		$results['statistics']['changes'] +=
			$file_item['changes'];
	}

	return $results;
}

