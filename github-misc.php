<?php
/**
 * Misc functions relating to GitHub API, but
 * not do not submit directly to it nor directly
 * process raw HTTP results.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
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
 *
 * @param string $local_git_repo Path to local git repository.
 * @param string $repo_owner     Owner of GitHub repository.
 * @param string $repo_name      Name of GitHub repository.
 * @param string $github_token   GitHub access token to use.
 * @param string $pr_base_sha    Commit-ID of base of pull request.
 * @param string $commit_id      Commit-ID of current commit.
 * @param string $file_name      File name.
 *
 * @return array Array, keys representing lines in patch,
 *               values line-number in raw committed file.
 */
function vipgoci_patch_changed_lines(
	string $local_git_repo,
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $pr_base_sha,
	string $commit_id,
	string $file_name
): ?array {
	/*
	 * Fetch patch for all files of the pull request
	 */
	$patch_arr = vipgoci_git_diffs_fetch(
		$local_git_repo,
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
	 * No such file found, return with error
	 */
	if ( ! isset(
		$patch_arr['files'][ $file_name ]
	) ) {
		return null;
	}

	/*
	 * Get patch for the relevant file
	 * our caller is interested in
	 */
	$lines_arr = explode(
		"\n",
		$patch_arr['files'][ $file_name ]
	);

	$lines_changed = array();

	$i = 1;

	foreach ( $lines_arr as $line ) {
		preg_match_all(
			'/^@@\s+[-\+]([0-9]+,[0-9]+)\s+[-\+]([0-9]+,[0-9]+)\s+@@/',
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
		} elseif ( empty( $matches[0] ) ) {
			if ( empty( $line[0] ) ) {
				// Do nothing.
				continue;
			} elseif (
				( '-' === $line[0] ) ||
				( '\\' === $line[0] )
			) {
				$lines_changed[] = null;
			} elseif (
				( '+' === $line[0] ) ||
				( ' ' === $line[0] ) ||
				( "\t" === $line[0] )
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
			( null === $lines_changed[1] ) ||
			( 0 === $lines_changed[1] )
		)
		||
		( ! isset( $lines_changed[1] ) )
	) {
		$lines_changed[1] = 1;
	}

	return $lines_changed;
}

/**
 * Remove any draft pull requests from the array
 * provided.
 *
 * @param array $prs_array Array to process.
 *
 * @return array Processed array, without draft pull requests.
 */
function vipgoci_github_pr_remove_drafts(
	array $prs_array
) :array {
	$prs_array = array_filter(
		$prs_array,
		function( $pr_item ) {
			if ( true === (bool) $pr_item->draft ) {
				return false;
			}

			return true;
		}
	);

	return $prs_array;
}

/**
 * Go through the given blame-log, and
 * return only the items from the log that
 * are found in $relevant_commit_ids.
 *
 * @param array $blame_log           Array with blame log.
 * @param array $relevant_commit_ids Array with relevant commit IDs.
 *
 * @return array Items from blame log found in $relevant_commit_ids.
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
	 * part of the current pull request.
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

		$blame_log_filtered[] = $blame_log_item;
	}

	return $blame_log_filtered;
}


/**
 * Return ASCII-art for GitHub, which will then
 * be turned into something more fancy. This is
 * intended to be called when preparing messages/comments
 * to be submitted to GitHub.
 *
 * @param string $text_string String to transform.
 *
 * @return string Transformed string, or empty string if invalid type of string is provided.
 */
function vipgoci_github_transform_to_emojis( $text_string ) {
	switch ( strtolower( $text_string ) ) {
		case 'warning':
			return ':warning:';

		case 'error':
			return ':no_entry_sign:';

		case 'info':
			return ':information_source:';
	}

	return '';
}

/**
 * Add pagebreak to a Markdown-style comment
 * string -- but only if a pagebreak is not
 * already the latest addition to the comment.
 * If whitespacing is present just after the
 * pagebreak, ignore it and act as if it does
 * not exist.
 *
 * @param string $comment Comment to add pagebreak to.
 * @param string $pagebreak_style Style of pagebreak.
 *
 * @return void
 */
function vipgoci_markdown_comment_add_pagebreak(
	string &$comment,
	string $pagebreak_style = '***'
) :void {
	/*
	 * Get rid of any \n\r strings, and other
	 * whitespaces from $comment.
	 */
	$comment_copy = rtrim( $comment );
	$comment_copy = rtrim( $comment_copy, " \n\r" );

	/*
	 * If there is no comment, do not add pagebreak.
	 */
	if ( empty( $comment_copy ) ) {
		return;
	}

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

/**
 * Construct and return URLs to pull requests
 * specified in $prs_arr
 *
 * @param array  $prs_arr    Pull requests.
 * @param string $separator  Separator to use between URLs.
 *
 * @return string URLs to pull requests.
 */
function vipgoci_github_prs_urls_get(
	array $prs_arr,
	string $separator = ', '
) :string {
	$prs_urls = '';

	foreach ( $prs_arr as $pr_item ) {
		if ( ! empty( $prs_urls ) ) {
			$prs_urls .= $separator;
		}

		$prs_urls .= $pr_item->head->repo->html_url .
			'/pull/' .
			rawurlencode( (string) $pr_item->number );
	}

	return $prs_urls;
}

/**
 * Construct array of files affected -- altered, added, deleted -- by
 * each pull request implicated by the commit. Will also include list
 * of all files affected.
 *
 * @param array      $options                 Options array for the program.
 * @param string     $commit_id               Commit-ID of current commit.
 * @param array      $commit_skipped_files    Information about skipped files (reference).
 * @param bool       $renamed_files_also      If to include renamed files in results.
 * @param bool       $removed_files_also      If to include removed files in results.
 * @param bool       $permission_changes_also If to include files whose permissions were changed in results.
 * @param null|array $filter                  Filter to apply.
 * @param bool       $always_define_pr_number When true, will define array-key for a pull request in results even when no files are placed in the value.
 *
 * @return array Returns associative array with key as pull request number and value as array of affected files. Includes special key 'all' which includes all files altered by all pull requests. Example:
 *  Array(
 *    [all] => Array(
 *      [0] => folder1/test.php
 *      [1] => folder2/test2.php
 *      [2] => testing/file.php
 *    ),
 *    [17] => Array(
 *      [0] => folder1/test.php
 *      [1] => testing/file.php
 *    ),
 *    [20] => Array(
 *      [0] => folder1/test.php
 *      [1] => folder2/test2.php
 *    )
 *  )
 */
function vipgoci_github_files_affected_by_commit(
	array $options,
	string $commit_id,
	array &$commit_skipped_files,
	bool $renamed_files_also = false,
	bool $removed_files_also = true,
	bool $permission_changes_also = false,
	array $filter = null,
	bool $always_define_pr_number = true
) :array {
	vipgoci_log(
		'Fetching list of all files affected by each pull request ' .
			'implicated by the commit',
		array(
			'repo_owner' => $options['repo-owner'],
			'repo_name'  => $options['repo-name'],
			'commit_id'  => $options['commit'],
		)
	);

	// Fetch list of all pull requests which the commit is a part of.
	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$commit_id,
		$options['token'],
		$options['branches-ignore'],
		$options['skip-draft-prs']
	);

	$pr_item_files_changed = array(
		'all' => array(),
	);

	foreach ( $prs_implicated as $pr_item ) {
		/*
		 * If requested, ensure that the PR is defined in the array.
		 */
		if (
			( true === $always_define_pr_number ) &&
			( ! isset( $pr_item_files_changed[ $pr_item->number ] ) )
		) {
			$pr_item_files_changed[ $pr_item->number ] = array();
		}

		/*
		 * Get list of all files changed
		 * in this pull request.
		 */
		$pr_item_files_tmp = vipgoci_git_diffs_fetch(
			$options['local-git-repo'],
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->base->sha,
			$options['commit'],
			$renamed_files_also,
			$removed_files_also,
			$permission_changes_also,
			$filter,
		);

		foreach (
			array_keys( $pr_item_files_tmp['files'] ) as
				$pr_item_file_name
		) {
			/*
			 * Check for too long file.
			 */
			if (
				( isset(
					$commit_skipped_files[ $pr_item->number ]['issues'][ VIPGOCI_VALIDATION_MAXIMUM_LINES ]
				) )
				&&
				( true === in_array(
					$pr_item_file_name,
					$commit_skipped_files[ $pr_item->number ]['issues'][ VIPGOCI_VALIDATION_MAXIMUM_LINES ],
					true
				) )
			) {
				continue;
			}

			/*
			 * Add file to arrays, if not already there.
			 */
			vipgoci_array_push_uniquely(
				$pr_item_files_changed['all'],
				$pr_item_file_name
			);

			if ( ! isset( $pr_item_files_changed[ $pr_item->number ] ) ) {
				$pr_item_files_changed[ $pr_item->number ] = array();
			}

			vipgoci_array_push_uniquely(
				$pr_item_files_changed[ $pr_item->number ],
				$pr_item_file_name
			);
		}
	}

	return $pr_item_files_changed;
}

