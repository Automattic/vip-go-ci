<?php

/*
 * Log information to the console.
 * Include timestamp, and any debug-data
 * our caller might pass us.
 */

function vipgoci_log(
	$str,
	$debug_data = array(),
	$debug_level = 0,
	$irc = false
) {
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

	/*
	 * Send to IRC API as well if asked
	 * to do so. Include debugging information as well.
	 */
	if ( true === $irc ) {
		vipgoci_irc_api_alert_queue(
			$str .
				'; ' .
				print_r(
					json_encode(
						$debug_data
					),
					true
				)
		);
	}
}

/**
 * Exit program, using vipgoci_log() to print a
 * message before doing so.
 *
 * @codeCoverageIgnore
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
		$commit_id,
		false,
		false,
		false
	);

	/*
	 * Get patch for the relevant file
	 * our caller is interested in
	 */

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
function vipgoci_file_extension( $file_name ) {
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
 * Return ASCII-art for GitHub, which will then
 * be turned into something more fancy. This is
 * intended to be called when preparing messages/comments
 * to be submitted to GitHub.
 */
function vipgoci_github_labels( $text_string ) {
	switch( strtolower( $text_string ) ) {
		case 'warning':
			return ':warning:';

		case 'error':
			return ':no_entry_sign:';

		case 'info':
			return ':information_source:';
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
	$file_info_extension = vipgoci_file_extension(
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
			array("**", "Warning", "Error", "Info", ":no_entry_sign:", ":warning:", ":information_source:"),
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

		/*
		 * The comment might include PHPCS source
		 * of the error at the end (e.g.
		 * "... (*WordPress.WP.AlternativeFunctions.json_encode_json_encode*)."
		 * -- remove the source, the brackets and the ending dot.
		 */
		$comment_made_body = preg_replace(
			'/ \([\*_\.a-zA-Z0-9]+\)\.$/',
			'',
			$comment_made_body
		); 

		/*
		 * Transform string to lowercase,
		 * remove ending '.' just in case if
		 * not removed earlier.
		 */
		$comment_made_body = strtolower(
			$comment_made_body
		);

		$comment_made_body = rtrim(
			$comment_made_body,
			'.'
		);

		/*
		 * Transform the string to lowercase,
		 * and remove potential '.' at the end
		 * of it.
		 */
		$file_issue_comment = strtolower(
			$file_issue_comment
		);

		$file_issue_comment = rtrim(
			$file_issue_comment,
			'.'
		);

		/*
		 * Check if comments match, including
		 * if we need to HTML-encode our new comment
		 * (GitHub encodes their comments when
		 * returning them.
		 */
		if (
			(
				$comment_made_body ==
				$file_issue_comment
			)
			||
			(
				$comment_made_body ==
				htmlentities( $file_issue_comment )
			)
		) {
			/* Comment found, return true. */
			return true;
		}
	}

	return false;
}

/*
 * Remove comments that exist on a GitHub Pull-Request from
 * the results array. Will loop through each Pull-Request
 * affected by the current commit, and remove any comment
 * from the results array if it already exists.
 */
function vipgoci_remove_existing_github_comments_from_results(
	$options,
	$prs_implicated,
	&$results,
	$repost_comments_from_dismissed_reviews = false,
	$prs_events_dismissed_by_team = array()
) {
	vipgoci_log(
		'Removing existing GitHub comments from results' .
			' to be posted to GitHub API',
		array(
			'repo_owner' => $options['repo-owner'],
			'repo_name' => $options['repo-name'],
			'prs_implicated' => array_keys( $prs_implicated ),
			'repost_comments_from_dismissed_reviews' => $repost_comments_from_dismissed_reviews,
			'prs_events_dismissed_by_team' => $prs_events_dismissed_by_team,
		)
	);

	$comments_removed = array();

	foreach ( $prs_implicated as $pr_item ) {
		$prs_comments = array();

		if ( ! isset(
			$comments_removed[ $pr_item->number ]
		) ) {
			$comments_removed[ $pr_item->number ] = array();
		}

		/*
		 * Get all commits related to the current
		 * Pull-Request.
		 */

		$pr_item_commits = vipgoci_github_prs_commits_list(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token']
		);

		/*
		 * Loop through each commit, fetching all comments
		 * made in relation to that commit
		 */

		foreach ( $pr_item_commits as $pr_item_commit_id ) {
			vipgoci_github_pr_reviews_comments_get(
				$options,
				$pr_item_commit_id,
				$pr_item->created_at,
				$prs_comments // pointer used
			);

			unset( $pr_item_commit_id );
		}


		/*
		 * Ignore dismissed reviews, if requested.
		 */
		if ( true === $repost_comments_from_dismissed_reviews ) {
			vipgoci_log(
				'Later on, will make sure comments ' .
					'that are part of dismissed reviews ' .
					'will be submitted again, if the ' .
					'underlying issue was detected ' . 
					'during the run. In case of such a setting' .
					'and such reviews existing, excluding ' .
					'reviews (and thus comments) that are submitted ' .
					'by members of a particular team ' .
					'from this process',
				array(
					'teams' =>
						$options['dismissed-reviews-exclude-reviews-from-team'],

					'pr_number' =>
						$pr_item->number,
				)
			);

			/*
			 * Get dismissed reviews submitted by us
			 * and extract ID of each.
			 */
			$pr_reviews = vipgoci_github_pr_reviews_get(
				$options['repo-owner'],
				$options['repo-name'],
				$pr_item->number,
				$options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'DISMISSED' )
				)
			);

			$dismissed_reviews = array_column(
				$pr_reviews,
				'id'
			);

			unset( $pr_reviews );

			/*
			 * Some reviews (and comments) should not be posted,
			 * again, as per setting determined by our caller;
			 * honor this here.
			 */
			if ( ! empty(
				$prs_events_dismissed_by_team[
					$pr_item->number
				]
			) ) {

				$all_review_ids = $dismissed_reviews;

				$dismissed_reviews = array_diff(
					$all_review_ids,
					$prs_events_dismissed_by_team[
						$pr_item->number
					]
				);
	
				vipgoci_log(
					'Excluding certain reviews from ' .
						'list of previously gathered dismissed reviews; ' .
						'will only keep reviews that were not dismissed by ' .
						'members of a particular team. The comments of ' .
						'the outstanding, kept, reviews might be posted again ' .
						'if the underlying issue was detected',
					array(
						'prs_events_dismissed_by_team_and_pr' =>
							$prs_events_dismissed_by_team[
								$pr_item->number
							],

						'all_review_ids' =>
							$all_review_ids,

						'dismissed_reviews' =>
							$dismissed_reviews,
					)
				);

				unset( $all_review_ids );
			}


			/*
			 * Loop through each file to have comments
			 * submitted against, then look through each
			 * comment, looking for any comment associated
			 * with dismissed reviews.
			 *
			 * If we find a dismissed review, we will act
			 * as if the comment was never there by removing
			 * it from $prs_comments. This will ensure
			 * that our to-be posted review will contain
			 * such comments, even though they could be
			 * considered duplictes. The aim is to make
			 * them more visible and part of a blocking review.
			 *
			 * Note that some comments might be excluded
			 * from this, as per above.
			 */

			$removed_comments = array();

			foreach(
				$prs_comments as
					$pr_comment_key => $pr_comments_items
			) {
				foreach(
					$pr_comments_items as
					$pr_review_key => $pr_review_comment
				) {
					if ( false === in_array(
						$pr_review_comment->pull_request_review_id,
						$dismissed_reviews
					) ) {
						continue;
					}

					$removed_comments[] = array(
						'pr_number' =>
							$pr_item->number,

						'pull_request_review_id' =>
							$pr_review_comment->pull_request_review_id,

						'comment_id' =>
							$pr_review_comment->id,

						'message_body' =>
							$pr_review_comment->body,

						'message_created_at' =>
							$pr_review_comment->created_at,

						'message_updated_at' =>
							$pr_review_comment->updated_at,
					);


					/*
					 * Comment is a part of a dismissed review
					 * (that was not excluded), now get
					 * rid of the comment -- act as if was
					 * never there.
					 */
					unset(
						$prs_comments[
							$pr_comment_key
						][
							$pr_review_key
						]
					);
				}
			}

			vipgoci_log(
				'Removed following comments from list of previously submitted ' .
					'comments to older PR reviews, as they are ' .
					'part of dismissed reviews. Note that some ' .
					'dismissed reviews might have been excluded previously',

				array(
					'removed_comments' =>
						$removed_comments,
				)
			);

			unset( $removed_comments );
			unset( $dismissed_reviews );
		}


		foreach(
			$results['issues'][ $pr_item->number ] as
				$tobe_submitted_cmt_key =>
					$tobe_submitted_cmt
		) {

			/*
			 * Filter out issues that have already been
			 * reported to GitHub.
			 */

			if (
				// Only do check if everything above is looking good
				vipgoci_github_comment_match(
					$tobe_submitted_cmt['file_name'],
					$tobe_submitted_cmt['file_line'],
					$tobe_submitted_cmt['issue']['message'],
					$prs_comments
				)
			) {
				/*
				 * Keep a record of what we remove.
				 */
				$comments_removed[ $pr_item->number ][] =
					$tobe_submitted_cmt;

				/* Remove it */
				unset(
					$results[
						'issues'
					][
						$pr_item->number
					][
						$tobe_submitted_cmt_key
					]
				);

				/*
				 * Update statistics
				 */
				$results[
					'stats'
				][
					$tobe_submitted_cmt['type']
				][
					$pr_item->number
				][
					strtolower(
						$tobe_submitted_cmt['issue']['type']
					)
				]--;
			}
		}

		/*
		 * Re-create the issues
		 * array, so that no array
		 * keys are missing.
		 */
		$results[
			'issues'
		][
			$pr_item->number
		] = array_values(
			$results[
				'issues'
			][
				$pr_item->number
			]
		);
	}

	/*
	 * Report what we removed.
	 */
	vipgoci_log(
		'Removed following comments from array of ' .
		'to be submitted comments to PRs, as they ' .
		'have been submitted already',
		array(
			'comments_removed' => $comments_removed
		)
	);
}


/*
 * For each approved file, remove any issues
 * to be submitted against them. However,
 * do not do this for 'info' type messages,
 * as they are informational, and not problems.
 *
 * We do this, because sometimes Pull-Requests
 * will be opened that contain approved code,
 * and we do not want to clutter them with
 * non-relevant comments.
 *
 * Make sure to update statistics to
 * reflect this.
 */

function vipgoci_approved_files_comments_remove(
	$options,
	&$results,
	$auto_approved_files_arr
) {

	$issues_removed = array(
	);

	vipgoci_log(
		'Removing any potential issues (errors, warnings) ' .
			'found for approved files from internal results',

		array(
			'auto_approved_files_arr' => $auto_approved_files_arr,
		)
	);

	/*
 	 * Loop through each Pull-Request
	 */
	foreach( $results['issues'] as
		$pr_number => $pr_issues
	) {
		/*
		 * Loop through each issue affecting each
		 * Pull-Request.
		 */
		foreach( $pr_issues as
			$issue_number => $issue_item
		) {

			/*
			 * If the file affected is
			 * not found in the auto-approved files,
			 * do not to anything.
			 */
			if ( ! isset(
				$auto_approved_files_arr[
					$issue_item['file_name']
				]
			) ) {
				continue;
			}

			/*
			 * We do not touch on 'info' type,
			 * as that does not report any errors.
			 */

			if ( strtolower(
				$issue_item['issue']['type']
			) === 'info' ) {
				continue;
			}

			/*
			 * We have found an item that is approved,
			 * and has non-info issues -- remove it
			 * from the array of submittable issues.
			 */
			unset(
				$results[
					'issues'
				][
					$pr_number
				][
					$issue_number
				]
			);

			/*
			 * Update statistics accordingly.
			 */
			$results[
				'stats'
			][
				$issue_item['type']
			][
				$pr_number
			][
				strtolower(
					$issue_item['issue']['type']
				)
			]--;

			/*
			 * Update our own information array on
			 * what we did.
			 */
			$issues_removed[
				$pr_number
			][] = $issue_item;
		}

		/*
		 * Re-order the array as
		 * some keys might be missing
		 */
		$results[
			'issues'
		][
			$pr_number
		] = array_values(
			$results[
				'issues'
			][
				$pr_number
			]
		);
	}


	vipgoci_log(
		'Completed cleaning out issues for pre-approved files',
		array(
			'issues_removed' => $issues_removed,
		)
	);
}

/*
 * Limit the number of to-be-submitted comments to
 * the Pull-Requests. We take into account the number
 * to be submitted for each Pull-Request, the number of
 * comments already submitted, and the limit specified
 * on start-up. Comments are removed as needed, and
 * what comments are removed is reported.
 */
function vipgoci_github_results_filter_comments_to_max(
	$options,
	&$results,
	&$prs_comments_maxed
) {

	vipgoci_log(
		'Preparing to remove any excessive number comments from array of ' .
			'issues to be submitted to PRs',
		array(
			'review_comments_total_max'
				=> $options['review-comments-total-max'],
		)
	);


	/*
	 * We might need to remove comments.
	 *
	 * We will begin with lower priority comments
	 * first, remove them, and then progressively
	 * continue removing comments as priority increases
	 * and there is still a need for removal.
	 */

	/*
	 * Keep track of what we remove.
	 */
	$comments_removed = array();

	foreach(
		$results['issues'] as
			$pr_number => $pr_issues_comments
	) {
		/*
		 * Take into account previously submitted comments
		 * by us for the current Pull-Request.
		 */

		$pr_previous_comments_cnt = count(
			vipgoci_github_pr_reviews_comments_get_by_pr(
				$options,
				$pr_number,
				array(
					'login'			=> 'myself',
					'comments_active'	=> true,
				)
			)
		);

		/*
		 * How many comments need
		 * to be removed? Count in
		 * comments in the PR in addition
		 * to possible new ones, substract
		 * from the maximum specified.
		 */

		$comments_to_remove =
			(
				count( $pr_issues_comments )
				+
				$pr_previous_comments_cnt
			)
			-
			$options['review-comments-total-max'];

		/*
		 * If there are no comments to remove,
		 * skip and continue.
		 */
		if ( $comments_to_remove <= 0 ) {
			continue;
		}

		/*
		 * If more are to be removed than are to be
		 * submitted, limit to the number of available ones.
		 */
		else if (
			$comments_to_remove >
				count( $pr_issues_comments )
		) {
			$comments_to_remove = count( $pr_issues_comments );
		}

		/*
		 * Figure out severity, minimum and maximum.
		 */

		$severity_min = 0;
		$severity_max = 0;

		foreach( $pr_issues_comments as $pr_issue ) {
			$severity_min = min(
				$pr_issue['issue']['severity'],
				$severity_min
			);

			$severity_max = max(
				$pr_issue['issue']['severity'],
				$severity_max
			);
		}

		/*
		 * Loop through severity-levels from low to high
		 * and remove comments as needed.
		 */
		for (
			$severity_current = $severity_min;
			$severity_current <= $severity_max &&
				$comments_to_remove > 0;
			$severity_current++
		) {
			foreach(
				$pr_issues_comments as
					$pr_issue_key => $pr_issue
			) {
				/*
				 * If we have removed enough, stop here.
				 */
				if ( $comments_to_remove <= 0 ) {
					break;
				}

				/*
				 * Not correct severity level? Ignore.
				 */
				if (
					$pr_issue['issue']['severity'] !==
					$severity_current
				) {
					continue;
				}

				/*
				 * Actually remove and
				 * keep statistics up to date.
				 */

				unset(
					$results[
						'issues'
					][
						$pr_number
					][
						$pr_issue_key
					]
				);

				$results[
					'stats'
				][
					$pr_issue['type']
				][
					$pr_number
				][
					strtolower(
						$pr_issue['issue']['type']
					)
				]--;

				/*
				 * Keep track of what we remove
				 */
				if ( ! isset(
					$comments_removed[
						$pr_number
					]
				) ) {
					$comments_removed[
						$pr_number
					] = array();
				}

				$comments_removed[
					$pr_number
				][] = $pr_issue;

				$comments_to_remove--;
			}
		}

		/*
		 * Re-create array so to
		 * keep continuous ordering
		 * of index.
		 */
		$results[
			'issues'
		][
			$pr_number
		] = array_values(
			$results[
				'issues'
			][
				$pr_number
			]
		);
	}

	/*
	 * Populate '$prs_comments_maxed' which
	 * indicates which Pull-Requests have
	 * had number of comments posted limited.
	 */
	$prs_comments_maxed = array_map(
		'is_array',
		$comments_removed
	);


	vipgoci_log(
		'Removed issue comments from array of to be submitted ' .
			'comments to PRs due to limit constraints',
		array(
			'review_comments_total_max'	=> $options['review-comments-total-max'],
			'comments_removed'		=> $comments_removed,
		)
	);

	return;
}

/*
 * Filter away issues that we should ignore from the set
 * of results, according to --review-comments-ignore argument.
 * The issues to be ignored are specified as an array of
 * string-messages, all in lower-case.
 */

function vipgoci_results_filter_ignorable(
	$options,
	&$results
) {
	$comments_removed = array();

	vipgoci_log(
		'Removing comments to be ignored from results before submission',
		array(
			'messages-ignore' =>
				$options['review-comments-ignore'],
		)
	);


	foreach(
		$results['issues'] as
			$pr_number => $pr_issues_comments
	) {
		foreach(
			$pr_issues_comments as
				$pr_issue_key =>
				$pr_issue
		) {
			if ( in_array(
				strtolower(
					$pr_issue['issue']['message']
				),
				$options['review-comments-ignore'],
				true
			) ) {
				/*
				 * Found a message to ignore,
				 * remove it from the results-array.
				 */
				unset(
					$results[
						'issues'
					][
						$pr_number
					][
						$pr_issue_key
					]
				);

				/*
				 * Keep track of what we remove
				 */
				if ( ! isset(
					$comments_removed[
						$pr_number
					]
				) ) {
					$comments_removed[
						$pr_number
					] = array();
				}

				$comments_removed[
					$pr_number
				][] = $pr_issue;

	
				/*
				 * Keep statistics up-to-date
				 */
				$results[
					'stats'
				][
					$pr_issue['type']
				][
					$pr_number
				][
					strtolower(
						$pr_issue['issue']['type']
					)
				]--;
			}
		}

		/*
		 * Re-create the array in
		 * case of changes to keys,
		 */

		$results['issues'][ $pr_number ] = array_values(
			$results['issues'][ $pr_number ]
		);
	}

	vipgoci_log(
		'Removed ignorable comments',
		array(
			'comments-removed' => $comments_removed
		)
	);
}

/*
 * Filter out any issues in the code that were not
 * touched up on by the changed lines -- i.e., any issues
 * that existed prior to the change.
 */
function vipgoci_issues_filter_irrellevant(
	$file_name,
	$file_issues_arr,
	$file_blame_log,
	$pr_item_commits,
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

		// Passed all tests, keep this issue
		$file_issues_ret[] = $file_issue_val;
	}

	return $file_issues_ret;
}

/*
 * In case of some issues being reported in duplicate
 * by PHPCS, remove those. Only issues reported
 * twice in the same file on the same line are considered
 * a duplicate.
 */
function vipgoci_issues_filter_duplicate( $file_issues_arr ) {
	$issues_hashes = array();
	$file_issues_arr_new = array();

	foreach(
		$file_issues_arr as
			$issue_item_key => $issue_item_value
	) {
		$issue_item_hash = md5(
			$issue_item_value['message']
		)
		. ':' .
		$issue_item_value['line'];

		if ( in_array( $issue_item_hash, $issues_hashes, true ) ) {
			continue;
		}

		$issues_hashes[] = $issue_item_hash;

		$file_issues_arr_new[] = $issue_item_value;
	}

	return $file_issues_arr_new;
}


/*
 * Add pagebreak to a Markdown-style comment
 * string -- but only if a pagebreak is not
 * already the latest addition to the comment.
 * If whitespacing is present just after the
 * pagebreak, ignore it and act as if it does
 * not exist.
 */
function vipgoci_markdown_comment_add_pagebreak(
	&$comment,
	$pagebreak_style = '***'
) {
	/*
	 * Get rid of any \n\r strings, and other
	 * whitespaces from $comment.
	 */
	$comment_copy = rtrim( $comment );
	$comment_copy = rtrim( $comment_copy, " \n\r" );

	/*	
	 * Find the last pagebreak in the comment.
	 */
	$pagebreak_location = strrpos(
		$comment_copy,
		$pagebreak_style
	);


	/*
	 * If pagebreak is found, and is
	 * at the end of the comment, bail
	 * out and do nothing to the comment.
	 */

	if (
		( false !== $pagebreak_location ) &&
		(
			$pagebreak_location +
			strlen( $pagebreak_style )
		)
		===
		strlen( $comment_copy )
	) {
		return;
	}

	$comment .= $pagebreak_style . "\n\r";
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
