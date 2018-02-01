<?php

/*
 * Run PHPCS for the file specified, using the
 * appropriate standards. Return the results.
 */

function vipgoci_phpcs_do_scan(
	$filename_tmp,
	$phpcs_path,
	$phpcs_standard
) {
	/*
	 * Run PHPCS from the shell, making sure we escape everything.
	 *
	 * Feed PHPCS the temporary file specified by our caller.
	 *
	 * Make sure to use wide enough output, so we can catch all of it.
	 */

	$cmd = sprintf(
		'%s %s --standard=%s --report-width=%s -p %s 2>&1',
		escapeshellcmd( 'php' ),
		escapeshellcmd( $phpcs_path ),
		escapeshellarg( $phpcs_standard ),
		escapeshellarg( 500 ),
		escapeshellarg( $filename_tmp )
	);

	vipgoci_runtime_measure( 'start', 'phpcs_cli' );

	$result = shell_exec( $cmd );

	vipgoci_runtime_measure( 'stop', 'phpcs_cli' );


	/*
	 * Do simple checks to see if we can find any signature marks
	 * of PHPCS having run -- this should be in what
	 * PHPCS returns.
	 */
	if (
		( false === strpos( $result, 'Time: ') )
	) {
		$result = null;
	}

	/* Catch errors */
	if ( null === $result ) {
		vipgoci_log(
			'Failed to execute PHPCS. Cannot continue execution.',
			array(
				'command' => $cmd,
				'result' => $result,
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


	// Get commit-info
	$commit_info = vipgoci_github_fetch_commit_info(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		array(
			'file_extensions'
				=> array( 'php', 'js', 'twig' ),

			'status'
				=> array( 'added', 'modified' ),
		)
	);

	// Fetch list of all Pull-Requests which the commit is a part of
	$prs_implicated = vipgoci_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		$options['branches-ignore']
	);

	/*
	 * Fetch all comments made in relation to that commit
	 * and associated with any Pull-Requests that are open.
	 */
	$prs_comments = vipgoci_github_pr_reviews_comments_get(
		$repo_owner,
		$repo_name,
		$commit_id,
		$commit_info->commit->committer->date,
		$github_token
	);


	/*
	 * Loop through each file affected by
	 * the commit.
	 */
	foreach( $commit_info->files as $file_info ) {
		vipgoci_runtime_measure( 'start', 'phpcs_scan_single_file' );

		$file_contents = vipgoci_gitrepo_fetch_committed_file(
			$repo_owner,
			$repo_name,
			$github_token,
			$commit_id,
			$file_info->filename,
			$options['local-git-repo']
		);

		$file_extension = pathinfo(
			$file_info->filename,
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
				'filename' => $file_info->filename,
				'temp_file_name' => $temp_file_name,
			)
		);


		$file_issues_str = vipgoci_phpcs_do_scan(
			$temp_file_name,
			$options['phpcs-path'],
			$options['phpcs-standard']
		);

		/* Get rid of temporary file */
		unlink( $temp_file_name );


		$file_issues_arr_master = vipgoci_phpcs_parse_results(
			$file_issues_str
		);


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
					'filename'	=> $file_info->filename,
					'issues'	=> $file_issues_arr_master,
				)
			);
		}


		/*
		 * Loop through each Pull-Request,
		 * and detect problems that apply to
		 * each and every one, while skipping
		 * those that do not apply.
		 */

		foreach ( $prs_implicated as $pr_item ) {
			$file_changed_lines = vipgoci_patch_changed_lines(
				$repo_owner,
				$repo_name,
				$github_token,
				$pr_item->base->sha,
				$commit_id,
				$file_info->filename
			);

			$file_relevant_lines = @array_flip(
				$file_changed_lines
			);


			/*
			 * Filter out any issues that affect the file, but are not
			 * due to the commit made -- so any existing issues are left
			 * out and not commented on by us.
			 */
			$file_issues_arr = $file_issues_arr_master;

			$file_issues_arr = vipgoci_issues_filter_irrellevant(
				$file_issues_arr,
				$file_changed_lines
			);


			/*
			 * Loop through array of lines in which
			 * issues exist.
			 */
			foreach (
				$file_issues_arr as
					$file_issue_line => $file_issue_values
			) {
				/*
				 * Loop through each issue for the particular
				 * line.
				 */

				foreach (
					$file_issue_values as $file_issue_val_item
				) {

					/*
					 * Figure out if the comment has been
					 * submitted before, and if so, do not submit
					 * it again. This needs to be done because
					 * we might run more than once per commit.
					 */

					if (
						vipgoci_github_comment_match(
							$file_info->filename,
							$file_relevant_lines[ $file_issue_line ],
							$file_issue_val_item['message'],
							$prs_comments
						)
					) {
						vipgoci_log(
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

					$commit_issues_submit[
						$pr_item->number
					][] = array(
						'type'		=> 'phpcs',

						'file_name'	=>
							$file_info->filename,

						'file_line'	=>
							$file_relevant_lines[
								$file_issue_line
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
		}

		vipgoci_log(
			'Cleaning up...',
			array()
		);


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

		vipgoci_runtime_measure( 'stop', 'phpcs_scan_single_file' );
	}


	/*
	 * Clean up a bit
	 */

	unset( $prs_comments );
	unset( $prs_implicated );

	gc_collect_cycles();

        vipgoci_runtime_measure( 'stop', 'phpcs_scan_commit' );
}
