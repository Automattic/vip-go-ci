<?php

/*
 * Run PHPCS for the file specified, using the
 * appropriate standards. Return the results.
 */

function vipgoci_phpcs_do_scan(
	$filename_tmp,
	$phpcs_path,
	$phpcs_standard,
	$phpcs_severity
) {
	/*
	 * Run PHPCS from the shell, making sure we escape everything.
	 *
	 * Feed PHPCS the temporary file specified by our caller.
	 *
	 * Make sure to use wide enough output, so we can catch all of it.
	 */

	$cmd = sprintf(
		'%s %s --standard=%s --severity=%s --report=%s %s 2>&1',
		escapeshellcmd( 'php' ),
		escapeshellcmd( $phpcs_path ),
		escapeshellarg( $phpcs_standard ),
		escapeshellarg( $phpcs_severity ),
		escapeshellarg( 'json' ),
		escapeshellarg( $filename_tmp )
	);

	vipgoci_runtime_measure( 'start', 'phpcs_cli' );

	$result = shell_exec( $cmd );

	vipgoci_runtime_measure( 'stop', 'phpcs_cli' );

	return $result;
}


/*
 * Dump output of scan-analysis to a file,
 * if possible.
 */

function vipgoci_phpcs_scan_output_dump( $output_file, $data ) {
	if (
		( is_file( $output_file ) ) &&
		( ! is_writeable( $output_file ) )
	) {
		vipgoci_log(
			'File ' .
				$output_file .
				' is not writeable',
			array()
		);
	} else {
		file_put_contents(
			$output_file,
			json_encode(
				$data,
				JSON_PRETTY_PRINT
			),
			FILE_APPEND
		);
	}
}


/*
 * Filter out any issues in the code that were not
 * touched up on by the changed lines -- i.e., any issues
 * that existed prior to the change.
 */
function vipgoci_issues_filter_irrellevant(
	$repo_owner,
	$repo_name,
	$commit_id,
	$file_name,
	$file_issues_arr,
	$file_blame_log,
	$pr_item_commits,
	$comments_existing,
	$file_relative_lines
) {
	/*
	 * Filter out any issues
	 * that are due to commits outside
	 * of the Pull-Request
	 */

	$file_blame_log_filtered =
		vipgoci_blame_filter_commits(
			$file_blame_log,
			$pr_item_commits
		);


	$file_issues_ret = array();

	/*
	 * Loop through all the issues affecting
	 * this particular file
	 */
	foreach (
		$file_issues_arr[ $file_name ] as
			$file_issue_key =>
			$file_issue_val
	) {
		$keep_issue = false;

		/*
		 * Filter out issues outside of the blame log
		 */

		foreach ( $file_blame_log_filtered as $blame_log_item ) {
			if (
				$blame_log_item['line_no'] ===
					$file_issue_val['line']
			) {
				$keep_issue = true;
			}
		}

		if ( false === $keep_issue ) {
			continue;
		}

		unset( $keep_issue );

		/*
		 * Filter out any issues that are outside
		 * of the current patch
		 */

		if ( ! isset(
			$file_relative_lines[ $file_issue_val['line'] ]
		) ) {
			continue;
		}

		/*
		 * Filter out issues that have already been
		 * reported got GitHub.
		 */

		if (
			// Only do check if everything above is looking good
			vipgoci_github_comment_match(
				$file_name,
				$file_relative_lines[
					$file_issue_val['line']
				],
				$file_issue_val['message'],
				$comments_existing
			)
		) {
			vipgoci_log(
				'Skipping submission of ' .
				'comment, has already been ' .
				'submitted',
				array(
					'repo_owner'		=> $repo_owner,
					'repo_name'		=> $repo_name,
					'filename'		=> $file_name,
					'file_issue_line'	=> $file_issue_val['line'],
					'file_issue_msg'	=> $file_issue_val['message'],
					'commit_id'		=> $commit_id,
				)
			);

			/* Skip */
			continue;
		}

		// Passed all tests, keep this issue
		$file_issues_ret[] = $file_issue_val;
	}

	return $file_issues_ret;
}


/*
 * Scan a particular commit which should live within
 * a particular repository on GitHub, and use the specified
 * access-token to gain access.
 */
function vipgoci_phpcs_scan_commit(
	$options,
	&$commit_issues_submit,
	&$commit_issues_stats
) {
	$repo_owner = $options['repo-owner'];
	$repo_name  = $options['repo-name'];
	$commit_id  = $options['commit'];
	$github_token = $options['token'];

        vipgoci_runtime_measure( 'start', 'phpcs_scan_commit' );

	vipgoci_log(
		'About to PHPCS-scan repository',

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);


	/*
	 * First, figure out if a .gitmodules
	 * file was added or modified; if so,
	 * we need to scan the relevant sub-module(s)
	 * specifically.
	 */

	$commit_info = vipgoci_github_fetch_commit_info(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		array(
			'file_extensions'
				=> array( 'gitmodules' ),

			'status'
				=> array( 'added', 'modified' ),
		)
	);


	if ( ! empty( $commit_info->files ) ) {
		// FIXME: Do something about the .gitmodule file
	}



	// Fetch list of all Pull-Requests which the commit is a part of
	$prs_implicated = vipgoci_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		$options['branches-ignore']
	);


	/*
	 * Get list of all files affected by
	 * each Pull-Request implicated by the commit.
	 */

	vipgoci_log(
		'Fetching list of all files affected by each Pull-Request ' .
			'implicated by the commit',

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);

	$pr_item_files_changed = array();
	$pr_item_files_changed['all'] = array();

	foreach ( $prs_implicated as $pr_item ) {
		/*
		 * Make sure that the PR is defined in the array
		 */
		if ( ! isset( $pr_item_files_changed[ $pr_item->number ] ) ) {
			$pr_item_files_changed[ $pr_item->number ] = [];
		}

		/*
		 * Get list of all files changed
		 * in this Pull-Request.
		 */

		$pr_item_files_tmp = vipgoci_github_pr_files_changed(
			$repo_owner,
			$repo_name,
			$github_token,
			$pr_item->base->sha,
			$commit_id,
			array(
				'file_extensions' =>
					array( 'php', 'js', 'twig' ),
				'skip_folders' =>
					$options['skip-folders'],
			)
		);

		foreach ( $pr_item_files_tmp as $pr_item_file_name ) {
			if ( in_array(
				$pr_item_file_name,
				$pr_item_files_changed['all'],
				true
			) ) {
				continue;
			}

			$pr_item_files_changed['all'][] =
				$pr_item_file_name;

			$pr_item_files_changed[
				$pr_item->number
			][] = $pr_item_file_name;
		}
	}


	$files_issues_arr = array();

	/*
	 * Loop through each altered file in all the Pull-Requests,
	 * use PHPCS to scan for issues, save the issues; they will
	 * be processed in the next step.
	 */

	vipgoci_log(
		'About to PHPCS-scan all files affected by any of the ' .
			'Pull-Requests',

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);


	foreach ( $pr_item_files_changed['all'] as $file_name ) {
		/*
		 * Loop through each file affected by
		 * the commit.
		 */
		vipgoci_runtime_measure( 'start', 'phpcs_scan_single_file' );

		$file_contents = vipgoci_gitrepo_fetch_committed_file(
			$repo_owner,
			$repo_name,
			$github_token,
			$commit_id,
			$file_name,
			$options['local-git-repo']
		);

		$file_extension = pathinfo(
			$file_name,
			PATHINFO_EXTENSION
		);

		if ( empty( $file_extension ) ) {
			$file_extension = null;
		}

		$temp_file_name = vipgoci_save_temp_file(
			'phpcs-scan-',
			$file_extension,
			$file_contents
		);

		vipgoci_log(
			'About to PHPCS-scan file',
			array(
				'repo_owner' => $repo_owner,
				'repo_name' => $repo_name,
				'commit_id' => $commit_id,
				'filename' => $file_name,
				'temp_file_name' => $temp_file_name,
			)
		);


		$file_issues_str = vipgoci_phpcs_do_scan(
			$temp_file_name,
			$options['phpcs-path'],
			$options['phpcs-standard'],
			$options['phpcs-severity']
		);

		/* Get rid of temporary file */
		unlink( $temp_file_name );

		$file_issues_arr_master = json_decode(
			$file_issues_str,
			true
		);


		/*
		 * Do sanity-checking
		 */

		if (
			( null === $file_issues_arr_master ) ||
			( ! isset( $file_issues_arr_master['totals'] ) ) ||
			( ! isset( $file_issues_arr_master['files'] ) )
		) {
			vipgoci_log(
				'Failed parsing output from PHPCS',
				array(
					'repo_owner' => $repo_owner,
					'repo_name' => $repo_name,
					'commit_id' => $commit_id,
					'file_issues_arr_master' =>
						$file_issues_arr_master,
					'file_issues_str' =>
						$file_issues_str,
				)
			);
		}

		unset( $file_issues_str );

		/*
		 * Make sure items in $file_issues_arr_master have
		 * 'level' key and value.
		 */
		$file_issues_arr_master = array_map(
			function( $item ) {
				$item['level'] = $item['type'];

				return $item;
			},
			$file_issues_arr_master
				['files']
				[ $temp_file_name ]
				['messages']
		);

		$files_issues_arr[ $file_name ] = $file_issues_arr_master;

		/*
		 * Output scanning-results if requested
		 */

		if ( ! empty( $options['output'] ) ) {
			vipgoci_phpcs_scan_output_dump(
				$options['output'],
				array(
					'repo_owner'	=> $repo_owner,
					'repo_name'	=> $repo_name,
					'commit_id'	=> $commit_id,
					'filename'	=> $file_name,
					'issues'	=> $file_issues_arr_master,
				)
			);
		}

		/*
		 * Get rid of data, and
		 * attempt to garbage-collect.
		 */
		vipgoci_log(
			'Cleaning up after scanning of file...',
			array()
		);

		unset( $file_contents );
		unset( $file_extension );
		unset( $temp_file_name );
		unset( $file_issues_arr_master );
		unset( $file_issues_str );

		gc_collect_cycles();

		vipgoci_runtime_measure( 'stop', 'phpcs_scan_single_file' );
	}


	/*
	 * Loop through each Pull-Request implicated,
	 * get comments made on GitHub already,
	 * then filter out any PHPCS-issues irrelevant
	 * as they are not due to any commit that is part
	 * of the Pull-Request, and skip any PHPCS-issue
	 * already reported. Report the rest, if any.
	 */

	vipgoci_log(
		'Figuring out which comment(s) to submit to GitHub, if any',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);


	foreach ( $prs_implicated as $pr_item ) {
		/*
		 * Loop through each commit, fetching all comments
		 * made in relation to that commit
		 */

		$prs_comments = array();

		/*
		 * Get all commits related to the current
		 * Pull-Request.
		 */
		$pr_item_commits = vipgoci_github_prs_commits_list(
			$repo_owner,
			$repo_name,
			$pr_item->number,
			$github_token
		);

		foreach ( $pr_item_commits as $pr_item_commit_id ) {
			vipgoci_github_pr_reviews_comments_get(
				$prs_comments,
				$repo_owner,
				$repo_name,
				$pr_item_commit_id,
				$pr_item->created_at,
				$github_token
			);

			unset( $pr_item_commit_id );
		}


		/*
		 * Loop through each file, get a
		 * 'git blame' log for the file, then
		 * filter out issues stemming
		 * from commits that are not a
		 * part of the current Pull-Request.
		 */

		foreach (
			$pr_item_files_changed[ $pr_item->number ] as
				$_tmp => $file_name
			) {

			/*
			 * Get blame log for file
			 */
			$file_blame_log = vipgoci_gitrepo_blame_for_file(
				$commit_id,
				$file_name,
				$options['local-git-repo']
			);

			$file_changed_lines = vipgoci_patch_changed_lines(
				$repo_owner,
				$repo_name,
				$github_token,
				$pr_item->base->sha,
				$commit_id,
				$file_name
			);

			$file_relative_lines = @array_flip(
				$file_changed_lines
			);


			/*
			 * Filter the issues we found
			 * previously in this file; remove
			 * the ones that the are not found
			 * in the blame-log (meaning that
			 * they are due to commits outside of
			 * the Pull-Request), and remove
			 * those which have already been submitted.
			 */

			$file_issues_arr_filtered = vipgoci_issues_filter_irrellevant(
				$repo_owner,
				$repo_name,
				$commit_id,
				$file_name,
				$files_issues_arr,
				$file_blame_log,
				$pr_item_commits,
				$prs_comments,
				$file_relative_lines
			);

			/*
			 * Collect all the issues that
			 * we need to submit about
			 */

			foreach( $file_issues_arr_filtered as
				$file_issue_val_key =>
				$file_issue_val_item
			) {
				$commit_issues_submit[
					$pr_item->number
				][] = array(
					'type'		=> 'phpcs',

					'file_name'	=>
						$file_name,

					'file_line'	=>
						$file_relative_lines[
							$file_issue_val_item[
								'line'
						]
					],

					'issue'		=>
						$file_issue_val_item,
				);

				/*
				 * Collect statistics on
				 * number of warnings/errors
				 */

				$commit_issues_stats[
					$pr_item->number
				][
					strtolower(
						$file_issue_val_item[
							'level'
						]
					)
				]++;
			}
		}

		unset( $prs_comments );
		unset( $pr_item_commits );
		unset( $pr_item_files_changed );
		unset( $file_blame_log );
		unset( $file_changed_lines );
		unset( $file_relative_lines );
		unset( $file_issues_arr_filtered );

		gc_collect_cycles();
	}


	/*
	 * Clean up a bit
	 */
	vipgoci_log(
		'Cleaning up after PHPCS-scanning...',
		array()
	);

	unset( $prs_implicated );

	gc_collect_cycles();

        vipgoci_runtime_measure( 'stop', 'phpcs_scan_commit' );
}

