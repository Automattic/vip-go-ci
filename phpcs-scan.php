#!/usr/bin/php
<?php

require_once( __DIR__ . '/github-api.php' );
require_once( __DIR__ . '/misc.php' );


/*
 * Run PHPCS for the file specified, using the
 * appropriate standards. Return the results.
 */

function vipgoci_phpcs_phpcs_run( $filename_tmp, $real_name ) {
	/*
	 * Run PHPCS from the shell, making sure we escape everything.
	 *
	 * Feed PHPCS the temporary file specified by our caller,
	 * forcing the PHPCS output to use the name of this file as
	 * found in the git repository.
	 *
	 * Make sure to use wide enough output, so we can catch all of it.
	 */

	$cmd = sprintf(
		'cat %s | %s %s --standard=%s --report-width=%s --stdin-path=%s',
		escapeshellarg( $filename_tmp ),
		escapeshellcmd( 'php' ),
		'~/' .  escapeshellcmd( 'php-validation/phpcs/scripts/phpcs' ),
		escapeshellarg( 'WordPressVIPminimum' ),
		escapeshellarg( 500 ),
		escapeshellarg( $real_name )
	);

	$result = shell_exec( $cmd );

	/* Catch errors */
	if ( null === $result ) {
		vipgoci_phpcs_log(
			'Failed to execute PHPCS. Cannot continue execution.',
			array(
				'command' => $cmd,
			)
		);

		exit( 254 );
	}

	return $result;
}


/*
 * Parse the PHCS-results provided, making sure the
 * output be an associative array, using line-number
 * as a key.
 */

function vipgoci_phpcs_phpcs_results_parse( $phpcs_results ) {
	$issues = array();

	if ( preg_match_all(
		'/^[\s\t]+(\d+)\s\|[\s\t]+([A-Z]+)[\s|\t]+\|[\s\t]+(.*)$/m',
		$phpcs_results,
		$matches,
		PREG_SET_ORDER
	) ) {
		/*
		 * Look through each result, set key too be
		 * the line number, and value to be an array
		 * which it self is an associative array.
		 */
		foreach( $matches as $match ) {
			$issues[ $match[1] ][] = array(
				'level'		=> $match[2],
				'message' 	=> $match[3],
			);
		}
	}

	return $issues;
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
			array("**", "Warning", "Error"),
			array("", "", ""),
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


/*
 * Scan a particular commit which should live within
 * a particular repository on GitHub, and use the specified
 * access-token to gain access.
 */
function vipgoci_phpcs_phpscan_commit(
	$repo_owner,
	$repo_name,
	$commit_id,
	$github_access_token
) {
	$commit_issues_all = array();

	vipgoci_phpcs_log(
		'About to scan repository',

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);

	$commit_info = vipgoci_phpcs_github_fetch_commit_info(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_access_token
	);

	/* Fetch all comments to the current commit */
	$commit_comments = vipgoci_phpcs_github_comments_get(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_access_token
	);


	/*
	 * Loop through each file affected by
	 * the commit.
	 */
	foreach( $commit_info->files as $file_info ) {
		$file_info_extension = pathinfo( $file_info->filename, PATHINFO_EXTENSION );

		/*
		 * If the file is not a PHP-file, skip
		 */

		if ( 'php' !== strtolower( $file_info_extension ) ) {
			vipgoci_phpcs_log(
				'Skipping file that does not seem to be a PHP-file',
				array(
					'filename' => $file_info->filename
				)
			);

			continue;
		}

		/*
		 * If the file was neither added nor modified, skip
		 */
		if (
			( 'added' !== $file_info->status ) &&
			( 'modified' !== $file_info->status )
		) {
			vipgoci_phpcs_log(
				'Skipping file that was neither added nor modified',
				array(
					'filename'	=> $file_info->filename,
					'status'	=> $file_info->status,
				)
			);

			continue;
		}


		$file_contents = vipgoci_phpcs_github_fetch_committed_file(
			$repo_owner, $repo_name, $github_access_token, $commit_id, $file_info->filename
		);

		/*
		 * Create temporary directory to save
		 * fetched files into
		 */
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'phpcs-scan-'
		);

		file_put_contents(
			$temp_file_name,
			$file_contents
		);

		vipgoci_phpcs_log(
			'About to PHPCS-scan file',
			array(
				'repo_owner' => $repo_owner,
				'repo_name' => $repo_name,
				'commit_id' => $commit_id,
				'filename' => $file_info->filename,
				'temp_file_name' => $temp_file_name,
			)
		);


		$file_issues_str = vipgoci_phpcs_phpcs_run(
			$temp_file_name,
			$file_info->filename
		);

		$file_issues_arr = vipgoci_phpcs_phpcs_results_parse(
			$file_issues_str
		);

		$file_changed_lines = vipgoci_phpcs_patch_changed_lines( $file_info->patch );

		/*
		 * Filter out any issues that affect the file, but are not
		 * due to the commit made -- so any existing issues are left
		 * out and not commented on by us.
		 */
		foreach( $file_issues_arr as $file_issue_line => $file_issue_val ) {
			if ( ! in_array( $file_issue_line, $file_changed_lines ) ) {
				unset( $file_issues_arr[ $file_issue_line ] );
			}
		}

		$file_changed_line_no_to_file_line_no = @array_flip( $file_changed_lines );

		foreach ( $file_issues_arr as $file_issue_line => $file_issue_values ) {
			foreach( $file_issue_values as $file_issue_val_item ) {

				/*
				 * Figure out if the comment has been
				 * submitted before, and if so, do not submit
				 * it again. This needs to be done because
				 * we might run more than once per commit.
				 */

				if (
					vipgoci_github_comment_match(
						$file_info->filename,
						$file_issue_line,
						$file_issue_val_item['message'],
						$commit_comments
					)
				) {
					vipgoci_phpcs_log(
						'Skipping submittion of comment, has already been submitted',
						array(
							'repo_owner'		=> $repo_owner,
							'repo_name'		=> $repo_name,
							'filename'		=> $file_info->filename,
							'file_issue_line'	=> $file_issue_line,
							'commit_id'		=> $commit_id,
						)
					);

					continue;
				}


				vipgoci_phpcs_github_comment_open(
					$repo_owner,
					$repo_name,
					$commit_id,
					$github_access_token,
					$file_info->filename,
					$file_changed_line_no_to_file_line_no[ $file_issue_line ],
					$file_issue_val_item['level'],
					$file_issue_val_item['message']
				);
			}
		}

		$commit_issues_all[ $file_info->filename ] =
			$file_issues_arr;

		vipgoci_phpcs_log(
			'Cleaning up, and sleeping a bit (for GitHub)',
			array()
		);

		/* Get rid of temporary file */
		unlink($temp_file_name);


		/*
		 * Get rid of data, and
		 * attempt to garbage-collect.
		 */

		unset( $commit_info );
		unset( $file_contents );
		unset( $file_issues_str );
		unset( $file_issues_arr );
		unset( $file_changed_lines );

		gc_collect_cycles();
	}

	return $commit_issues_all;
}

/*
 * Main invocation function.
 */
function vipgoci_phpcs_run() {
	global $argv;

	$startup_time = time();


	if ( count( $argv ) != 5 ) {
		print "Usage: " . $argv[0] . " repo-owner repo-name commit-id github-access-token\n";
		exit(-1);
	}


	$commit_issues_all = vipgoci_phpcs_phpscan_commit(
		$argv[1],
		$argv[2],
		$argv[3],
		$argv[4]
	);

	vipgoci_phpcs_log(
		'Shutting down',
		array(
			'run_time_seconds' => time() - $startup_time
		)
	);
}

vipgoci_phpcs_run();

