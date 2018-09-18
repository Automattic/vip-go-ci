<?php

/*
 * Process auto-approval(s) of the Pull-Request(s)
 * involved with the commit specified.
 *
 * This function will attempt to auto-approve
 * Pull-Request(s) that only alter files with specific
 * file-type endings. If the PR only alters these kinds
 * of files, the function will auto-approve them, and else not.
 *
 * Note that the --skip-folders argument is ignored
 * in this function.
 */

function vipgoci_auto_approval( $options, &$auto_approved_files_arr ) {
	vipgoci_runtime_measure( 'start', 'auto_approve_commit' );

	vipgoci_log(
		'Doing auto-approval',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit_id'	=> $options['commit'],
			'autoapprove'	=> $options['autoapprove'],

			'autoapproved_files_arr' =>
				$auto_approved_files_arr,
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


		$did_foreach = false;
		$can_auto_approve = true;

		$files_seen = array();

		/*
		 * Loop through all files that are
		 * altered by the Pull-Request, look for
		 * files that can be auo-approved.
		 */
		foreach( $pr_diff as
			$pr_diff_file_name => $pr_diff_contents
		) {

			$did_foreach = true;
			$files_seen[] = $pr_diff_file_name;


			$pr_diff_file_extension = pathinfo(
				$pr_diff_file_name,
				PATHINFO_EXTENSION
			);


			/*
			 * Is file in array of files
			 * that can be auto-approved?
			 * If not, we cannot auto-approve.
			 */
			if ( ! isset(
				$auto_approved_files_arr[
					$pr_diff_file_name
				]
			) ) {
				$can_auto_approve = false;
				break;
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

		if ( false == $did_foreach ) {
			vipgoci_log(
				'No action taken with Pull-Request #' .
					(int) $pr_item->number . ' ' .
					'since no files were found',
				array(
					'auto_approved_files_arr' =>
						$auto_approved_files_arr,

					'files_seen' => $files_seen,
				)
			);
		}

		else if (
			( true === $did_foreach ) &&
			( false === $can_auto_approve )
		) {
			vipgoci_log(
				'Will not auto-approve Pull-Request #' .
					(int) $pr_item->number . ' ' .
					'as it contains ' . "\n\t" .
					'files which are not ' .
					'automatically approvable',
				array(
					'autoapprove-filetypes' =>
						$options['autoapprove-filetypes'],

					'auto_approved_files_arr' =>
						$auto_approved_files_arr,

					'files_seen' => $files_seen,
				)
			);


			if ( false === $pr_label ) {
				vipgoci_log(
					'Will not attempt to remove label ' .
						'from issue as it does not ' .
						'exist',
					array(
						'repo_owner' => $options['repo-owner'],
						'repo_name' => $options['repo-name'],
						'pr_number' => $pr_item->number,
						'label_name' => $options['autoapprove-label'],
					)
				);
			}

			else {
				/*
				 * Remove auto-approve label
				 */
				vipgoci_github_label_remove_from_pr(
					$options['repo-owner'],
					$options['repo-name'],
					$options['token'],
					(int) $pr_item->number,
					$pr_label->name,
					$options['dry-run']
				);
			}

			// FIXME: Add comment for each hashes-to-hashes approved PHP and JS file,
			// indicating that it is approved in the DB so human reviewers do not
			// need to look at it again.

			// FIXME: Dismiss any approving reviews from the PR.
		}

		else if (
			( true === $did_foreach ) &&
			( true === $can_auto_approve )
		) {
			vipgoci_log(
				( $options['dry-run'] === true
					? 'Would ' : 'Will ' ) .
					'auto-approve Pull-Request #' .
					(int) $pr_item->number . ' ' .
					'as it alters or creates ' . "\n\t" .
					'only files that can be ' .
					'automatically approved',
				array(
					'repo_owner'
						=> $options['repo-owner'],

					'repo_name'
						=> $options['repo-name'],

					'commit_id'
						=> $options['commit'],

					'dry_run'
						=> $options['dry-run'],

					'autoapprove-filetypes' =>
						$options['autoapprove-filetypes'],

					'auto_approved_files_arr' =>
						$auto_approved_files_arr,

					'files_seen' => $files_seen,
				)
			);


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
				VIPGOCI_APPROVAL_AUTOAPPROVE,
				$options['dry-run']
			);


			/*
			 * Add label to Pull-Request, but
			 * only if it is not associated already.
			 * If it is already associated, just log
			 * that fact.
			 */
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

			// FIXME: Remove any comments indicating that a file is approved.
		}

		unset( $files_seen );
	}

	/*
	 * Reduce memory-usage as possible
	 */
	unset( $prs_implicated );
	unset( $pr_diff );

	gc_collect_cycles();

	vipgoci_runtime_measure( 'stop', 'auto_approve_commit' );
}

