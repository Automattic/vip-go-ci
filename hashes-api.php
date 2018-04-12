<?php

function vipgoci_hashes_api_scan_commit( $options ) {
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

	/*
	 * Reduce memory-usage as possible
	 */

	unset( $files_seen_in_pr );
	unset( $files_approved_in_pr );
	unset( $prs_implicated );
	unset( $pr_diff );

	gc_collect_cycles();
}
