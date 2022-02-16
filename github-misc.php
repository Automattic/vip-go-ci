<?php

/**
 * Misc functions relating to GitHub API, but
 * not do not submit directly to it.
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
	 * Fetch patch for all files of the Pull-Request
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
 * Remove any draft Pull-Requests from the array
 * provided.
 */
function vipgoci_github_pr_remove_drafts( $prs_array ) {
	$prs_array = array_filter(
		$prs_array,
		function( $pr_item ) {
			if ( (bool) $pr_item->draft === true ) {
				return false;
			}

			return true;
		}
	);

	return $prs_array;
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
		 * The comment might include severity level
		 * -- remove that.
		 */
		$comment_made_body = preg_replace(
			'/\( severity \d{1,2} \)/',
			'',
			$comment_made_body
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
 * Return ASCII-art for GitHub, which will then
 * be turned into something more fancy. This is
 * intended to be called when preparing messages/comments
 * to be submitted to GitHub.
 */
function vipgoci_github_transform_to_emojis( $text_string ) {
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

