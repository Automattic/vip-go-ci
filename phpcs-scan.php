#!/usr/bin/php
<?php

require_once( __DIR__ . '/github-api.php' );
require_once( __DIR__ . '/misc.php' );


/*
 * Run PHPCS for the file specified, using the
 * appropriate standards. Return the results.
 */

function vipgoci_phpcs_do_scan( $filename_tmp, $real_name ) {
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
		'~/' .  escapeshellcmd( 'phpcs-scan/phpcs/scripts/phpcs' ),
		escapeshellarg( 'WordPressVIPminimum' ),
		escapeshellarg( 500 ),
		escapeshellarg( $real_name )
	);

	$result = shell_exec( $cmd );

	/*
	 * Do simple checks to see if we can find any signature marks
	 * of PHPCS having run -- these two strings should be in what
	 * PHPCS returns.
	 */
	if (
		( false === strpos( $result, 'FILE: ' ) ) &&
		( false === strpos( $result, 'Time: ') )
	) {
		$result = null;
	}

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

function vipgoci_phpcs_parse_results( $phpcs_results ) {
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
 * Scan a particular commit which should live within
 * a particular repository on GitHub, and use the specified
 * access-token to gain access.
 */
function vipgoci_phpcs_scan_commit( $options ) {
	$repo_owner = $options['repo-owner'];
	$repo_name  = $options['repo-name'];
	$commit_id  = $options['commit'];
	$github_access_token = $options['token'];

	$commit_issues_submit = array();
	$commit_issues_stats = array(
		'error' => 0,
		'warning' => 0
	);

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


	$prs_implicated = vipgoci_phpcs_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_access_token
	);


	/*
	 * If no Pull-Requests are implicated by this commit,
	 * bail now, as there is no point in continuing running.
	 */
	if ( empty( $prs_implicated ) ) {
		vipgoci_phpcs_log(
			'Skipping scanning entirely, as the commit ' .
				'is not a part of any Pull-Request',

			array()
		);

		return $commit_issues_stats;
	}


	/*
	 * Fetch all comments made in relation to that commit
	 * and associated with any Pull-Requests that are open.
	 */
	$prs_comments = vipgoci_phpcs_github_pull_requests_comments_get(
		$repo_owner,
		$repo_name,
		$commit_id,
		$commit_info->commit->committer->date,
		$github_access_token
	);


	/*
	 * Loop through each file affected by
	 * the commit.
	 */
	foreach( $commit_info->files as $file_info ) {
		$file_info_extension = pathinfo(
			$file_info->filename,
			PATHINFO_EXTENSION
		);

		/*
		 * If the file is not a PHP-file, skip
		 */

		if ( 'php' !== strtolower( $file_info_extension ) ) {
			vipgoci_phpcs_log(
				'Skipping file that does not seem ' .
					'to be a PHP-file',

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
				'Skipping file that was neither ' .
					'added nor modified',

				array(
					'filename'	=> $file_info->filename,
					'status'	=> $file_info->status,
				)
			);

			continue;
		}

		$file_contents = vipgoci_phpcs_github_fetch_committed_file(
			$repo_owner,
			$repo_name,
			$github_access_token,
			$commit_id,
			$file_info->filename,
			$options['local-git-repo']
		);

		/*
		 * Create temporary directory to save
		 * fetched files into
		 */
		$temp_file_name = $temp_file_save_status = tempnam(
			sys_get_temp_dir(),
			'phpcs-scan-'
		);

		if ( false !== $temp_file_name ) {
			$temp_file_save_status = file_put_contents(
				$temp_file_name,
				$file_contents
			);
		}

		// Detect possible errors when saving the temporary file
		if ( false === $temp_file_save_status ) {
			vipgoci_phpcs_log(
				'Could not save file to disk, got ' .
					'an error. Exiting...',
				array(
					'temp_file_name' => $temp_file_name,
				)
			);

			exit( 254 );
		}

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


		$file_issues_str = vipgoci_phpcs_do_scan(
			$temp_file_name,
			$file_info->filename
		);

		$file_issues_arr = vipgoci_phpcs_parse_results(
			$file_issues_str
		);


		/*
		 * Output scanning-results if requested
		 */

		if ( ! empty( $options[ 'output'] ) ) {
			if (
				( is_file( $options['output'] ) ) &&
				( ! is_writeable( $options['output'] ) )
			) {
				vipgoci_phpcs_log(
					'File ' .
						$options['output'] .
						' is not writeable',
					array()
				);
			} else {
				file_put_contents(
					$options['output'],
					json_encode(
						$file_issues_arr,
						JSON_PRETTY_PRINT
					)
				);
			}
		}

		$file_changed_lines = vipgoci_phpcs_patch_changed_lines(
			$file_info->patch
		);

		/*
		 * Filter out any issues that affect the file, but are not
		 * due to the commit made -- so any existing issues are left
		 * out and not commented on by us.
		 */
		foreach (
			$file_issues_arr as
				$file_issue_line => $file_issue_val
		) {
			if ( ! in_array(
				$file_issue_line,
				$file_changed_lines
			) ) {
				unset( $file_issues_arr[ $file_issue_line ] );
			}
		}

		$file_changed_line_no_to_file_line_no = @array_flip(
			$file_changed_lines
		);

		foreach (
			$file_issues_arr as
				$file_issue_line => $file_issue_values
		) {
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
						$file_changed_line_no_to_file_line_no[ $file_issue_line ],
						$file_issue_val_item['message'],
						$prs_comments
					)
				) {
					vipgoci_phpcs_log(
						'Skipping submission of ' .
						'comment, has already been ' .
						'submitted',
						array(
							'repo_owner'		=> $repo_owner,
							'repo_name'		=> $repo_name,
							'filename'		=> $file_info->filename,
							'file_issue_line'	=> $file_issue_line,
							'file_issue_msg'	=> $file_issue_val_item['message'],
							'commit_id'		=> $commit_id,
						)
					);

					/* Skip */
					continue;
				}

				/*
				 * Collect all the issues that
				 * we need to submit about
				 */

				$commit_issues_submit[] = array(
					'file_name'	=> $file_info->filename,
					'file_line'	=> $file_changed_line_no_to_file_line_no[ $file_issue_line ],
					'issue'		=> $file_issue_val_item
				);

				/*
				 * Collect statistics on
				 * number of warnings/errors
				 */

				$commit_issues_stats[
					strtolower(
						$file_issue_val_item[ 'level' ]
					)
				]++;

			}
		}

		vipgoci_phpcs_log(
			'Cleaning up...',
			array()
		);

		/* Get rid of temporary file */
		unlink( $temp_file_name );


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


	/*
	 * Submit a review of what we found
	 * for each implicated Pull-Request
	 */

	foreach ( $prs_implicated as $pr_number ) {
		vipgoci_phpcs_github_review_submit(
			$repo_owner,
			$repo_name,
			$github_access_token,
			$pr_number,
			$commit_id,
			$commit_issues_submit,
			$commit_issues_stats,
			$options['dry-run']
		);
	}


	/*
	 * Clean up a bit
	 */

	unset( $prs_comments );
	unset( $prs_implicated );
	unset( $commit_issues_submit );

	gc_collect_cycles();

	return $commit_issues_stats;
}

/*
 * Main invocation function.
 */
function vipgoci_phpcs_run() {
	global $argv;

	$startup_time = time();

	$options = getopt(
		null,
		array(
			'repo-owner:',
			'repo-name:',
			'commit:',
			'token:',
			'output:',
			'dry-run:',
			'local-git-repo:',
		)
	);

	// Validate args
	if (
		! isset( $options['repo-owner'] ) ||
		! isset( $options['repo-name'] ) ||
		! isset( $options['commit'] ) ||
		! isset( $options['token'] )
	) {
		print 'Usage: ' . $argv[0] .
			' --repo-owner=repo-owner --repo-name=repo-name ' .
			'--commit=SHA --token=github-access-token' . "\n";
		exit(-1);
	}


	/*
	 * Handle optional --dry-run parameter
	 */

	if ( ! isset( $options['dry-run'] ) ) {
		$options['dry-run'] = 'false';
	}

	if (
		( $options['dry-run'] !== 'false' ) &&
		( $options['dry-run'] !== 'true' )
	) {
		print 'Usage: Parameter --dry-run has to be either false or true' . "\n";
		exit(-1);
	}

	else {
		if ( $options['dry-run'] === 'false' ) {
			$options['dry-run'] = false;
		}

		else {
			$options['dry-run'] = true;
		}
	}


	/*
	 * Handle optional --local-git-repo parameter
	 */

	if ( isset( $options['local-git-repo'] ) ) {
		$options['local-git-repo'] = rtrim(
			$options['local-git-repo'],
			'/'
		);

		if ( false === file_exists(
			$options['local-git-repo'] . '/.git'
		) ) {
			vipgoci_phpcs_log(
				'Local git repository was not found',
				array(
					'local_git_repo' =>
						$options['local-git-repo'],
				)
			);

			$options['local-git-repo'] = null;
		}
	}


	$commit_issues_stats = vipgoci_phpcs_scan_commit(
		$options
	);

	vipgoci_phpcs_log(
		'Shutting down',
		array(
			'run_time_seconds'	=> time() - $startup_time,
			'issues_stats'		=> $commit_issues_stats,
		)
	);


	/*
	 * If any 'error'-type issues  were submitted to
	 * GitHub we announce a failure to our parent-process
	 * by returning with a non-zero exit-code.
	 *
	 * If we only submitted warnings, we do not announce failure.
	 */

	if ( empty( $commit_issues_stats['error'] ) ) {
		return 0;
	}

	else {
		return 250;
	}
}

$ret = vipgoci_phpcs_run();

exit( $ret );

