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
	 * Try to read file from disk, then
	 * get rid of whitespaces in the file
	 * and calculate SHA1 hash from the whole.
	 */

	$file_contents = file_get_contents(
		$file_path
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

	$file_contents = php_strip_whitespaces(
		$file_contents
	);


	$file_sha1 = sha1( $file_contents );

	unset( $file_contents );

	/*
	 * Ask the API for information about
	 * the specific hash we calculated.
	 */

	$hashes_to_hashes_url =
		$options['hashes-api-url'] .
		'/v1/hashes/id' .
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
			$file_hashes_info
		);
	}


	if (
		( false === $file_hashes_info ) ||
		( null === $file_hashes_info )
	) {
		vipgoci_log(
			'Unable to get information from ' .
				'hashes-to-hashes HTTP API',
			array(
				'hashes_to_hashes_url' => $hashes_to_hashes_url,
				'file_path' => $file_path,
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
		if ( false === $file_hash_info[ 'status' ] ) {
			$file_approved = false;
		}

		if ( true === $file_hash_info[ 'status' ] ) {
			/* If we hit one non-approval,
			 * effectively assume it is not approved.
			 */
			if ( null === $file_approved ) {
				$file_approved = true;
			}
		}
	}


	/*
	 * If no approval is seen, assume it is not.
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
	$commit_issues_submit
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
			$approval_status = vipgoci_hashes_api_approved(
				$options,
				$pr_diff_file_name
			);


			/*
			 * Add the file to a list of approved files
			 * of these affected by the Pull-Request.
			 */
			if ( true === $approval_status ) {
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

	// FIXME: If all seen files are found in approved, simply
	// make a comment to the PR stating that this is approved
	// or even auto-approve

	if (
		count(
			array_diff(
				$files_seen_in_pr,
				$files_approved_in_pr
			)
		) === 0
	) {
		// FIXME: Make a comment on that this can be auto-approved
	}

	else {
		foreach ( $files_approved_in_pr as $file_name ) {
			/*
			 * Make comment for each file, noting
			 * that it is already approved.
			 */
			$commit_issues_submit[
				$pr_item->number
			][] = array(
				'type'          => 'phpcs',
				'file_name'     => $file_name,
				'file_line'     => 1,
                                       'issue'
						=> 'File is approved in ' .
						'hashes-to-hashes database',
			);
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

