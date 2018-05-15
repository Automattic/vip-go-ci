<?php

function vipgoci_auto_approval( $options ) {

	vipgoci_runtime_measure( 'start', 'auto_approve_commit' );

	vipgoci_log(
		'Doing auto-approval',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit_id'	=> $options['commit'],
			'autoapprove'	=> $options['autoapprove'],

			'autoapprove-filetypes' =>
				$options['autoapprove-filetypes'],
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

		foreach( $pr_diff as
			$pr_diff_file_name => $pr_diff_contents
		) {

			$did_foreach = true;
			$files_seen[] = $pr_diff_file_name;


			$pr_diff_file_extension = pathinfo(
				$pr_diff_file_name,
				PATHINFO_EXTENSION
			);


			if ( ! in_array(
				strtolower(
					$pr_diff_file_extension
				),
				$options['autoapprove-filetypes'],
				true
			) ) {
				$can_auto_approve = false;
				break;
			}
		}


		if ( false == $did_foreach ) {
			vipgoci_log(
				'No action taken with Pull-Request #' .
					(int) $pr_item->number . ' ' .
					'since no files were found',
					array(
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
					'file-types which are not ' .
					'automatically approvable',
					array(
						'autoapprove-filetypes' =>
							$options['autoapprove-filetypes'],

						'files_seen' => $files_seen,
				)
			);
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
					'only file-types that can be ' .
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

						'files_seen' => $files_seen,
				)
			);


			/*
			 * Actually approve, if not in dry-mode.
			 * Also add a label to the Pull-Request.
			 */
			vipgoci_github_approve_pr(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				$options['commit'],
				$options['autoapprove-filetypes'],
				$options['dry-run']
			);

			vipgoci_github_label_add_to_pr(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				'[Status] VIP Auto Approved',
				$options['dry-run']
			);
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

