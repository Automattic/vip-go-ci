<?php

/*
 * Get and return SHA1-sum for file passed, after having removed
 * whitespacing (using a specific function).
 */
function vipgoci_ap_hashes_calculate_sha1sum_for_file(
	$options,
	$file_path
) {
	/*
	 * Get file content.
	 */	
	$file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$file_path,
		$options['local-git-repo']
	);

	if ( false === $file_contents ) {
		vipgoci_log(
			'Unable to read file',
			array(
				'local-git-repo' => $options['local-git-repo'],
				'file_path' => $file_path,
			)
		);

		return null;
	}


	/*
	 * Save temporary copy of the file passed
	 * to us, strip whitespace from the file
	 * and calculate SHA1-sum of the result.
	 */
	$file_temp_path = vipgoci_save_temp_file(
		$file_path,
		null,
		$file_contents
	);

	$file_contents_stripped = php_strip_whitespace(
		$file_temp_path
	);


	$file_sha1 = sha1( $file_contents_stripped );

	unlink( $file_temp_path );
	unset( $file_contents );
	unset( $file_contents_stripped );

	return $file_sha1;
}


/*
 * Ask the hashes-to-hashes database API if the
 * specified file is approved.
 */

function vipgoci_ap_hashes_api_file_approved(
	$options,
	$file_path
) {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'hashes_api_scan_file' );

	/*
	 * Make sure to process only *.php and
	 * *.js files -- others are ignored.
	 *
	 * Cross-reference: These file types are not
	 * auto-approved by the auto-approval mechanism --
	 * see vip-go-ci.php.
	 */

	$file_extensions_approvable = array(
		'php',
		'js',
	);


	$file_info_extension = vipgoci_file_extension(
		$file_path
	);


	if ( in_array(
		$file_info_extension,
		$file_extensions_approvable
	) === false ) {
		vipgoci_log(
			'Not checking file for approval in hashes-to-hashes ' .
				'API, as it is not a file-type that is ' .
				'to be checked using it',

			array(
				'file_path'
					=> $file_path,

				'file_extension'
					=> $file_info_extension,

				'file_extensions_approvable'
					=> $file_extensions_approvable,
			)
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan_file' );

		return null;
	}


	vipgoci_log(
		'Checking if file is already approved in ' .
			'hashes-to-hashes API',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit'	=> $options['commit'],
			'file_path'	=> $file_path,
		)
	);

	$file_sha1 = vipgoci_ap_hashes_calculate_sha1sum_for_file(
		$options,
		$file_path
	);

	if ( null === $file_sha1 ) {
		vipgoci_log(
			'Unable to get SHA1-sum for file, not able to ' .
				'check if file is approved',
			array(
				'file_path' => $file_path,
				'file_sha1' => $file_sha1,
			)
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan_file' );

		return null;
	}


	/*
	 * Ask the API for information about
	 * the specific hash we calculated.
	 */

	vipgoci_log(
		'Asking hashes-to-hashes HTTP API if hash of file is ' .
			'known',
		array(
			'file_path'	=> $file_path,
			'file_sha1'	=> $file_sha1,
		)
	);

	$hashes_to_hashes_url =
		$options['hashes-api-url'] .
		'/v1/hashes/id/' .
		rawurlencode( $file_sha1 );

	/*
	 * Not really asking GitHub here,
	 * but we can re-use the function
	 * for this purpose.
	 */

	$file_hashes_info =
		vipgoci_github_fetch_url(
			$hashes_to_hashes_url,
			array(
				'oauth_consumer_key' =>
					$options['hashes-oauth-consumer-key'],

				'oauth_consumer_secret' =>
					$options['hashes-oauth-consumer-secret'],

				'oauth_token' =>
					$options['hashes-oauth-token'],

				'oauth_token_secret' =>
					$options['hashes-oauth-token-secret'],
			)
		);


	/*
	 * Try to parse, and check for errors.
	 */

	if ( false !== $file_hashes_info ) {
		$file_hashes_info = json_decode(
			$file_hashes_info,
			true
		);
	}


	if (
		( false === $file_hashes_info ) ||
		( null === $file_hashes_info ) ||
		( isset( $file_hashes_info['data']['status'] ) )
	) {
		vipgoci_log(
			'Unable to get information from ' .
				'hashes-to-hashes HTTP API',
			array(
				'hashes_to_hashes_url'	=> $hashes_to_hashes_url,
				'file_path'		=> $file_path,
				'http_reply'		=> $file_hashes_info,
			),
			0,
			true // log to IRC
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan_file' );

		return null;
	}

	$file_approved = null;

	/*
	 * Only approve file if all info-items show
	 * the file to be approved.
	 */

	foreach( $file_hashes_info as $file_hash_info ) {
		if ( ! isset( $file_hash_info[ 'status' ] ) ) {
			$file_approved = false;
		}

		if (
			( 'false' === $file_hash_info[ 'status' ] ) ||
			( false === $file_hash_info[ 'status' ] )
		) {
			$file_approved = false;
		}

		else if (
			( 'true' === $file_hash_info[ 'status' ] ) ||
			( true === $file_hash_info[ 'status' ] )
		) {
			/*
			 * Only update approval-flag if we have not
			 * seen any other approvals, and if we have
			 * not seen any rejections.
			 */
			if ( null === $file_approved ) {
				$file_approved = true;
			}
		}
	}


	/*
	 * If no approval is seen, assume it is not
	 * approved at all.
	 */

	if ( null === $file_approved ) {
		$file_approved = false;
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan_file' );

	return $file_approved;
}


/*
 * Scan a particular commit, look for altered
 * files in the Pull-Request we are associated with
 * and for each of these files, check if they
 * are approved in the hashes-to-hashes API.
 */
function vipgoci_ap_hashes_api_scan_commit(
	$options,
	&$commit_issues_submit,
	&$commit_issues_stats,
	&$auto_approved_files_arr
) {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'hashes_api_scan' );

	vipgoci_log(
		'Scanning altered or new files affected by Pull-Request(s) ' .
			'using hashes-to-hashes API',
		array(
			'repo_owner'		=> $options['repo-owner'],
			'repo_name'		=> $options['repo-name'],
			'commit_id'		=> $options['commit'],
			'hashes-api'		=> $options['hashes-api'],
			'hashes-api-url'	=> $options['hashes-api-url'],
		)
	);


	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore']
	);


	foreach ( $prs_implicated as $pr_item ) {
		/*
		 * Do not auto-approve renamed,
		 * removed or permission-changed files,
		 * even if they might be auto-approved: the
		 * reason is that renaming might be harmful to
		 * stability, there could be removal of vital
		 * files, and permission changes might be dangerous.
		 */
		$pr_diff = vipgoci_github_diffs_fetch(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->base->sha,
			$options['commit'],
			false, // exclude renamed files
			false, // exclude removed files
			false // exclude permission changes
		);


		foreach( $pr_diff as
			$pr_diff_file_name => $pr_diff_contents
		) {
			/*
			 * If it is already approved,
			 * do not do anything.
			 */

			if ( isset(
				$auto_approved_files_arr[
					$pr_diff_file_name
				]
			) ) {
				continue;
			}

			/*
			 * Check if the hashes-to-hashes database
			 * recognises this file, and check its
			 * status.
			 */

			$approval_status = vipgoci_ap_hashes_api_file_approved(
				$options,
				$pr_diff_file_name
			);


			/*
			 * Add the file to a list of approved files
			 * of these affected by the Pull-Request.
			 */
			if ( true === $approval_status ) {
				vipgoci_log(
					'File is approved in ' .
						'hashes-to-hashes API',
					array(
						'file_name' => $pr_diff_file_name,
					)
				);

				$auto_approved_files_arr[
					$pr_diff_file_name
				] = 'autoapprove-hashes-to-hashes';
			}

			else if ( false === $approval_status ) {
				vipgoci_log(
					'File is not approved in ' .
						'hashes-to-hashes API',
					array(
						'file_name' => $pr_diff_file_name,
					)
				);
			}

			else if ( null === $approval_status ) {
				vipgoci_log(
					'Could not determine if file is approved ' .
						'in hashes-to-hashes API',
					array(
						'file_name' => $pr_diff_file_name,
					)
				);
			}
		}
	}


	/*
	 * Reduce memory-usage as possible
	 */

	unset( $prs_implicated );
	unset( $pr_item );
	unset( $pr_diff );
	unset( $pr_diff_contents );
	unset( $approval_status );

	gc_collect_cycles();

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan' );
}


/*
 * Submit a single file to hashes-to-hashes API, file
 * that has been indicated as approved by an earlier process.
 */

function vipgoci_ap_hashes_api_submit_single_approved_file(
	$options,
	$file_path,
	$pr_number,
	$pr_comment_id,
	$submitter_github_username = null
) {
	vipgoci_runtime_measure(
		VIPGOCI_RUNTIME_START,
		'hashes_api_submit_single_file'
	);

	vipgoci_log(
		'Submitting approved file to hashes-to-hashes API',
		array(
			'hashes-api-url' =>
				$options['hashes-api-url'],

			'file_path' =>
				$file_path,

			'submitter_github_username' =>
				$submitter_github_username
		)
	);

	if ( null === $submitter_github_username ) {
		vipgoci_log(
			'Unable to submit file to hashes-to-hashes API, as ' .
			'the commenting user is invalid',
			array(
				'file_path'
					=> $file_path,

				'submitter_github_username'
					=> $submitter_github_username
			)
		);

		vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_STOP,
			'hashes_api_submit_single_file'
		);

		return false;
	}

	/*
	 * Determine SHA1-sum for file, after having
	 * processed it specifically.
	 */
	$file_sha1 = vipgoci_ap_hashes_calculate_sha1sum_for_file(
		$options,
		$file_path
	);

	if ( null === $file_sha1 ) {
		vipgoci_log(
			'Unable to submit approved file to ' .
				'hashes-to-hashes API as SHA1 for file ' .
				'could not be determined',
			array(
				'file_path' => $file_path,
				'file_sha1' => $file_sha1,
			)
		);

		vipgoci_runtime_measure(
			VIPGOCI_RUNTIME_STOP,
			'hashes_api_submit_single_file'
		);

		return false;
	}

	/* Get info about token-holder */
	$current_user_info = vipgoci_github_authenticated_user_get(
		$options['token']
	);


	/*
	 * Construct information for hashes-to-hashes
	 * submission.
	 */

        $hashes_to_hashes_url =
                $options['hashes-api-url'] .
                '/v1/create_item';

	$hashes_to_hashes_data = array(
		array(
			'hash'		=> $file_sha1,
			'user'		=> $current_user_info->login,
			'status'	=> true,
			'notes'		=> null,
			'date'		=> time(),
			'human_note'	=> null,
		)
	);

	if ( null !== $submitter_github_username ) {
		$hashes_to_hashes_data['human_note'] =
			'Submitted via vip-go-ci, by GitHub instruction, ' .
			'repo_owner=' . rawurlencode( $options['repo-owner'] ) . ', ' .
			'repo_name=' . rawurlencode( $options['repo-name'] ) . ', ';
			'commit_id=' . rawurlencode( $options['commit'] ) . ', ' .
			'pr_number=' . rawurlencode( $pr_number ) . ', ' .
			'comment_id' . rawurlencode( $pr_comment_id ) . ', ' .
			'submitting_comment_user=' . rawurlencode( $submitter_github_username ) . ', ';
	}

	
	// FIXME: Implement submission logic

	if ( $submission ) {
		return true;
	}

	else {
		return false;
	}

	vipgoci_runtime_measure(
		VIPGOCI_RUNTIME_STOP,
		'hashes_api_submit_single_file'
	);
}


/*
 * Look for comments to Pull-Requests indicating approval of
 * file for hashes-to-hashes API.
 *
 * Look through all comments that are part of the Pull-Requests.
 * If we find particular comments (e.g., "MyTeam: Approved file")
 * that are submitted by member of a particular team (e.g., "myteam"),
 * and if we find any, submit the file that the comment is made against
 * to the hashes-to-hashes API. Note that we do this only for newly
 * created files, and not files that are only modified.
 */

function vipgoci_ap_hashes_api_submit_approved_files(
	$options,
	$prs_implicated
) {
	vipgoci_runtime_measure(
		VIPGOCI_RUNTIME_START,
		'hashes_api_submit_approved_files'
	);

	vipgoci_log(
		'Looking for comments submitted to Pull-Requests ' .
			'indicating that files are approved. ' .
			'Comments submitted by a member of a ' .
			'particular team are acknowledged',
		array(
			'repo_owner' =>
				$options['repo-owner'],

			'repo_name' =>
				$options['repo-name'],

			'pr_numbers' =>
				array_keys( $prs_implicated ),

			'submission_string_used' =>
				$options['hashes-submit-approved-file-comment-string'],

			'hashes_submission_team_members_allowed' =>
				$options['hashes-submission-team-members-allowed']
		)
	);

	$files_looked_at = array();

	foreach ( $prs_implicated as $pr_item ) {
		$pr_comments = vipgoci_github_pr_reviews_comments_get_by_pr(
			$options,
			$pr_item->number
		);

		foreach ( $pr_comments as $pr_comment ) {
			/*
			 * Keep this debug-detail array ready
			 * for use, so we don't have to keep
			 * repeating the same code again and again.
			 */
			$log_detail_arr = array(
				'repo_owner' =>
					$options['repo-owner'],

				'repo_name' =>
					$options['repo-name'],

				'pr_comment_id' =>
					$pr_comment->id,

				'pull_request_review_id' =>
					$pr_comment->pull_request_review_id,

				'pr_comment_body' =>
					$pr_comment->body,

				'user_submitting' =>
					$pr_comment->user->login,

				'hashes_submission_team_members_allowed' =>
					$options['hashes-submission-team-members-allowed'],

				'submission_string_used' =>
					$options['hashes-submit-approved-file-comment-string'],

				'file_path' =>
					$pr_comment->path,
			);


			/*
			 * Search for a specific string in comment
			 * indicating that a file is approved.
			 */

			if ( strpos(
				$pr_comment->body,
				$options['hashes-submit-approved-file-comment-string']
			) === false ) {
				continue;
			}


			/*
			 * Avoid looking at the same file twice.
			 */
			if ( isset(
				$files_looked_at[
					$pr_item->number
				][
					$pr_comment->path
				]
			) ) {
				vipgoci_log(
					'Not looking at the same file again',
					$log_detail_arr
				);

				continue;
			}


			$files_looked_at[
				$pr_item->number
			][
				$pr_comment->path
			] = true;


			/*
			 * Skip if the comment seems to contain alot of other
			 * content -- we don't want to accidentally approve a file.
			 */

			if (
				strlen( $pr_comment->body )
				>
				( strlen(
					$options['hashes-submit-approved-file-comment-string']
				) * 1.75 )
			) {
				vipgoci_log(
					'Skipping comment, as it is much ' .
						'longer than anticipated ' .
						'than if a simple approving' .
						'comment',
					$log_detail_arr
				);

				continue;
			}


			/*
			 * Only consider comments made by users who can
			 * approve files, by being members of a
			 * particular team.
			 */
			if ( in_array(
				$pr_comment->user->login,
				$options['hashes-submission-team-members-allowed']
			) !== true ) {
				vipgoci_log(
					'Skipping comment, as submitting ' .
						'user does not have ' .
						'permission to approve files',
					$log_detail_arr
				);

				continue;
			}


			/*
			 * Skip comment if the file is not new,
			 * or if file is not PHP/JS/TWIG.
			 */

			$pr_altered_files = vipgoci_github_diffs_fetch(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->base->sha,
				$options['commit'],
				false,
				false,
				false,
				array(
					'file_extensions' => 
						array(
							'php', 'js', 'twig'
						),

					'skip_folders' => 
						$options['skip-folders'],
				),
				true // include more details about each file
			);
	
			/*
			 * Make sure the comment the file is made
			 * against is a new file.
			 */	

			if (
				( ! isset(
					$pr_altered_files[ $pr_comment->path ]
				) )
				||
				( 'added' !==
					$pr_altered_files[ $pr_comment->path ]->status
				)
			) {
				vipgoci_log(
					'Skipping comment, as it referes ' .
						'to file not newly added',
					$log_detail_arr
				);

				continue;
			}


			/*
			 * Skip files that are approved already or
			 * whose approval-status cannot be determined.
			 */
			$file_approved = vipgoci_ap_hashes_api_file_approved(
				$options,
				$pr_comment->path
			);

			if (
				( true === $file_approved ) ||
				( null === $file_approved )
			) {

				if ( true === $file_approved ) {
					$file_approval_status_comment = 'Skipping comment, as file is already approved';
				}

				else if ( null === $file_approved ) {
					$file_approval_status_comment = 'Skipping comment, as unable to determine approval status';
				}

				vipgoci_log(
					$file_approval_status_comment,
					$log_detail_arr
				);

				continue;
			}


			/*
			 * Check if we have already posted an emoji.
			 */

			$my_comment_reactions = vipgoci_github_pr_review_reactions_get(
				$options['repo-owner'],
				$options['repo-name'],
				$pr_comment->id,
				$options['token'],
				array(
					'login'		=> 'myself',
					'content'	=> '+1',
				)
			);

			if ( ! empty( $my_comment_reactions ) ) {
				vipgoci_log(
					'Found an earlier indication of us ' .
						'acknowledging a comment ' .
						'using an emjoi; not ' .
						'continuing',
					$log_detail_arr
				);

				continue;
			}


			/*
			 * All checks passed, actually send to
			 * hashes-to-hashes API for approval.
			 */

			vipgoci_log(
				'Found comment from user who is allowed to ' .
					'indicate approved file, the file ' .
					'passed other checks as well. ' .
					'Now processing for submission ' .
					'to hashes-to-hashes database',
				$log_detail_arr
			);

			vipgoci_ap_hashes_api_submit_single_approved_file(
				$options,
				$pr_comment->path,
				$pr_comment->id,
				$pr_comment->user->login
			);
			

			/*
			 * Submit emoji indicating that we submitted the file.
			 */

			vipgoci_github_pr_review_reaction_add(
				$options['repo-owner'],
				$options['repo-name'],
				$pr_comment->id,
				'+1',
				$options['token']
			);
		}
	}


	/*
	 * Clean up variables and
	 * free memory (as possible).
	 */
	unset( $pr_comments );
	unset( $pr_comment );
	unset( $pr_altered_files );
	unset( $file_approved );
	unset( $file_approval_status_comment );
	unset( $log_detail_arr );
	unset( $all_comment_reactions );

	gc_collect_cycles();

	vipgoci_runtime_measure(
		VIPGOCI_RUNTIME_STOP,
		'hashes_api_submit_approved_files'
	);
}

