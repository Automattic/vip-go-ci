<?php

/*
 * Ask the hashes-to-hashes database API if the
 * specified file is approved.
 */

function vipgoci_hashes_api_file_approved(
	$options,
	$file_path
) {
	vipgoci_runtime_measure( 'start', 'hashes_api_scan_file' );

	/*
	 * Make sure to process only *.php and
	 * *.js files -- others are ignored.
	 *
	 * Cross-reference: These files are not
	 * auto-approved by our own auto-approval
	 * mechanism, as to avoid any conflicts between
	 * hashes-api and the auto-approval mechanism.
	 */

	$file_extensions_approvable = array(
		'php',
		'js',
	);


	$file_info_extension = pathinfo(
		$file_path,
		PATHINFO_EXTENSION
	);


	if ( in_array(
		strtolower( $file_info_extension ),
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

		return null;
	}


	vipgoci_log(
		'Checking if file is already approved in ' .
			'hashes-to-hashes API',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'token'		=> $options['token'],
			'commit'	=> $options['commit'],
			'file_path'	=> $file_path,
		)
	);

	/*
	 * Try to read file from disk, then
	 * get rid of whitespaces in the file
	 * and calculate SHA1 hash from the whole.
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
				'file_path' => $file_path,
			)
		);

		return null;
	}

	vipgoci_log(
		'Saving file from git-repository into temporary file ' .
			'in order to strip any whitespacing from it',
		array(
			'file_path' => $file_path,
		),
		2
	);


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
		),
		2
	);

	$hashes_to_hashes_url =
		$options['hashes-api-url'] .
		'/v1/hashes/id/' .
		rawurlencode( $file_sha1 );

	$file_hashes_info =
		vipgoci_github_fetch_url(
			$hashes_to_hashes_url,
			null
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
		(
			( isset( $file_hashes_info['data']['status'] ) ) &&
			( 404 === $file_hashes_info['data']['status'] )
		)
	) {
		vipgoci_log(
			'Unable to get information from ' .
				'hashes-to-hashes HTTP API',
			array(
				'hashes_to_hashes_url'	=> $hashes_to_hashes_url,
				'file_path'		=> $file_path,
				'http_reply'		=> $file_hashes_info,
			)
		);

		return null;
	}


	$file_approved = null;

	/*
	 * Only approve file if all info-items show
	 * the file to be approved.
	 */

	foreach( $file_hashes_info as $file_hash_info ) {
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

	vipgoci_runtime_measure( 'stop', 'hashes_api_scan_file' );

	return $file_approved;
}


/*
 * Scan a particular commit, look for altered
 * files in the Pull-Request we are associated with
 * and for each of these files, check if they
 * are approved in the hashes-to-hashes API.
 */
function vipgoci_hashes_api_scan_commit(
	$options,
	&$commit_issues_submit,
	&$commit_issues_stats
) {
	vipgoci_runtime_measure( 'start', 'hashes_api_scan' );

	vipgoci_log(
		'Scanning altered or new files affected by Pull-Request(s) ',
			'using hashes-to-hashes database via API',
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
		$pr_diff = vipgoci_github_diffs_fetch(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->base->sha,
			$options['commit']
		);


		$files_seen_in_pr = array();
		$files_approved_in_pr = array();

		foreach( $pr_diff as
			$pr_diff_file_name => $pr_diff_contents
		) {
			$files_seen_in_pr[] = $pr_diff_file_name;

			/*
			 * Check if the hashes-to-hashes database
			 * recognises this file, and check its
			 * status.
			 */

			// FIXME: Take into consideration the review-level of both
			// the target-repo and of the code
			$approval_status = vipgoci_hashes_api_file_approved(
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

				$files_approved_in_pr[] = $pr_diff_file_name;
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
	 * Get label associated, but
	 * only our auto-approved one
	 */

	$pr_label = vipgoci_github_labels_get(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		(int) $pr_item->number,
		$options['autoapprove-label']
	);


	/*
	 * If all seen files are found in approved in hashes-to-hashes,
	 * approve the Pull-Request and add a label.
	 *
	 * If only some files are approved, make a comment on these
	 * saying that the files are approved in hashes-to-hashes.
	 */

	if (
		count(
			array_diff(
				$files_seen_in_pr,
				$files_approved_in_pr
			)
		) === 0
	) {
		/*
		 * Actually approve, if not in dry-mode.
		 * Also add a label to the Pull-Request
		 * if applicable.
		 */

		vipgoci_github_approve_pr(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->number,
			$options['commit'],
			$options['autoapprove-filetypes'],
			VIPGOCI_APPROVAL_HASHES_API,
			$options['dry-run']
		);

		/* Add label, if needed */
		if ( false === $pr_label ) {
			vipgoci_github_label_add_to_pr(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				$options['autoapprove-label'],
				$options['dry-run']
			);
		}

		else {
			vipgoci_log(
				'Will not add label to issue, ' .
					'as it already exists',

				array(
					'repo_owner' =>
						$options['repo-owner'],

					'repo_name' =>
						$options['repo-name'],

					'pr_number' =>
						$pr_item->number,

					'label_name' =>
						$options['autoapprove-label'],
				)
			);
		}
	}

	else {
		/*
		 * Remove auto-approve label
		 */

		if ( false !== $pr_label ) { 
			vipgoci_github_label_remove_from_pr(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				(int) $pr_item->number,
				$pr_label->name,
				$options['dry-run']
			);
		}


		/*
		 * Go through files that are approved,
		 * and add comment for them saying that
		 * they are approved already in the hashes-api
		 * database.
		 */
		foreach ( $files_approved_in_pr as $file_name ) {
			// FIXME: Check if comment has been
			// made before and do not re-post if so.

			/*
			 * Make comment for each file, noting
			 * that it is already approved.
			 */
			$commit_issues_submit[
				$pr_item->number
			][] = array(
				'type'          => VIPGOCI_STATS_HASHES_API,
				'file_name'     => $file_name,
				'file_line'     => 1,
				'issue'
					=> array(
						'message' =>
							'File is approved in ' .
							'hashes-to-hashes ' .
							'database',
						'level' => 'INFO',
					)
			);

			$commit_issues_stats[
				$pr_item->number
			]['info']++;
		}
	}


	/*
	 * Reduce memory-usage as possible
	 */

	unset( $files_seen_in_pr );
	unset( $files_approved_in_pr );
	unset( $prs_implicated );
	unset( $pr_diff );

	gc_collect_cycles();

	vipgoci_runtime_measure( 'stop', 'hashes_api_scan' );
}

