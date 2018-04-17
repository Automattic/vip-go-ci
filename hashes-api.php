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

	$file_contents = file_get_contents(
		$file_path
	);

	$file_contents = php_strip_whitespaces(
		$file_contents
	);

	$file_sha1 = sha1( $file_contents );

	$github_url =
		'https://' .
		'hashes-to-hashes.go-vip.co' . // FIXME: From option
		'/wp-json/viphash/v1/hashes/id' .
		rawurlencode( $file_sha1 );

	$file_hashes_info = json_decode(
		vipgoci_github_fetch_url(
			$github_url,
			$github_token
		),
		true
	);

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

function vipgoci_hashes_api_scan_commit( $options ) {
	vipgoci_runtime_measure( 'start', 'hashes_api_scan' );

	vipgoci_log(
		'Scanning altered or new files affected by Pull-Request(s) ',
			'using hashes-to-hashes database via API',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit_id'	=> $options['commit'],
			// FIXME: relevant options should follow
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
		// FIXME: Make separate comment for each file approved,
		// noting that it does not need a review
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

