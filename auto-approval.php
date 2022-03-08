<?php
/**
 * Auto-approval functionality.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * pull request is not approved,
 * remove label if needed, leave messages
 * on files that are approved, dismiss
 * any previously approving PRs.
 *
 * Note that the testing of this function is
 * covered by tests of vipgoci_auto_approval_scan_commit().
 *
 * @codeCoverageIgnore
 *
 * @param array       $options                 Options needed.
 * @param array       $results                 Results of scanning.
 * @param object      $pr_item                 Pull request item.
 * @param object|bool $pr_label                Pull request label found, false when none is found.
 * @param array       $auto_approved_files_arr Array of auto-approved files.
 * @param array       $files_seen              Files processed during auto-approval.
 * @param array       $pr_files_changed        Files changed by pull request.
 *
 * @return void
 */
function vipgoci_auto_approval_non_approval(
	array $options,
	array &$results,
	object $pr_item,
	object|bool $pr_label,
	array &$auto_approved_files_arr,
	array $files_seen,
	array $pr_files_changed
) :void {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'vipgoci_auto_approval_non_approval' );

	vipgoci_counter_report(
		VIPGOCI_COUNTERS_DO,
		'github_pr_non_approval',
		1
	);

	$tmp_github_url =
		VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
		rawurlencode( $options['repo-owner'] ) . '/' .
		rawurlencode( $options['repo-name'] ) . '/' .
		'pull/' . (int) $pr_item->number;

	vipgoci_log(
		'Will not auto-approve pull request #' .
			(int) $pr_item->number . ' ' .
			'as it contains ' .
			'files which are not ' .
			'automatically approvable' .
			' -- PR URL: ' . $tmp_github_url,
		array(
			'repo_owner'              => $options['repo-owner'],
			'repo_name'               => $options['repo-name'],
			'pr_number'               => $pr_item->number,
			'autoapprove-filetypes'   => $options['autoapprove-filetypes'],
			'auto_approved_files_arr' => $auto_approved_files_arr,
			'files_seen'              => $files_seen,
			'pr_files_changed'        => $pr_files_changed,
		),
		0
	);

	if ( false === $pr_label ) {
		vipgoci_log(
			'Will not attempt to remove label ' .
				'from issue as it does not ' .
				'exist',
			array(
				'repo_owner' => $options['repo-owner'],
				'repo_name'  => $options['repo-name'],
				'pr_number'  => $pr_item->number,
				'label_name' => $options['autoapprove-label'],
			)
		);
	} else {
		/*
		 * Remove auto-approve label
		 */
		vipgoci_github_pr_label_remove(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			(int) $pr_item->number,
			$pr_label->name
		);
	}

	/*
	 * Loop through approved PHP and JS files,
	 * adding comment for each about it
	 * being approved in the hashes-to-hashes API.
	 */
	foreach (
		$auto_approved_files_arr as
			$approved_file => $approved_file_system
	) {
		/*
		 * Make sure that the file was
		 * really altered in the pull request;
		 * this is to avoid any errors when
		 * submitting inline comments.
		 */
		if ( in_array(
			$approved_file,
			$pr_files_changed,
			true
		) === false ) {
			vipgoci_log(
				'Not adding auto-approved in hashes-api ' .
				'database to results for file, as it ' .
				'was not altered by the pull request',
				array(
					'pr_number'        => $pr_item->number,
					'file_name'        => $approved_file,
					'pr_files_changed' => $pr_files_changed,
				)
			);

			continue;
		}

		if (
			'autoapprove-hashes-to-hashes' !==
				$approved_file_system
		) {
			/*
			 * If not autoapproved by hashes-to-hashes,
			 * do not comment on it. Only PHP and JS files
			 * are auto-approved by hashes-to-hashes.
			 */
			continue;
		}

		$results['issues'][ (int) $pr_item->number ][] = array(
			'type'      => VIPGOCI_STATS_HASHES_API,
			'file_name' => $approved_file,
			'file_line' => 1,
			'issue'     => array(
				'message'  => VIPGOCI_FILE_IS_APPROVED_MSG,
				'source'   => 'VipgociInternal.Info.ApprovedHashesToHashesAPI',
				'severity' => 1,
				'fixable'  => false,
				'type'     => 'INFO',
				'line'     => 1,
				'column'   => 1,
				'level'    => 'INFO',
			),
		);

		$results['stats'][ VIPGOCI_STATS_HASHES_API ][ (int) $pr_item->number ]['info']++;
	}

	/*
	 * Remove any 'file is approved in ...' comments,
	 * but only for files that are no longer approved.
	 */
	vipgoci_log(
		'Removing any comments indicating a file is approved ' .
			'for files that are not approved anymore',
		array(
			'pr_number' => $pr_item->number,
		)
	);

	$pr_comments = vipgoci_github_pr_reviews_comments_get_by_pr(
		$options,
		$pr_item->number,
		array(
			'login' => 'myself',
		)
	);

	foreach ( $pr_comments as $pr_comment_item ) {
		/*
		 * Skip approved files.
		 */
		if ( isset(
			$auto_approved_files_arr[ $pr_comment_item->path ]
		) ) {
			continue;
		}

		/*
		 * If we find the 'approved in hashes-to-hashes ...'
		 * message, we can safely remove the comment.
		 */
		if ( false !== strpos(
			$pr_comment_item->body,
			VIPGOCI_FILE_IS_APPROVED_MSG
		) ) {
			vipgoci_github_pr_reviews_comments_delete(
				$options,
				$pr_comment_item->id
			);
		}
	}

	/*
	 * Get any approving reviews for the pull request
	 * submitted by us. Then dismiss them.
	 */
	vipgoci_log(
		'Dismissing any approving reviews for ' .
			'the pull request, as it is not ' .
			'approved anymore',
		array(
			'pr_number' => $pr_item->number,
		)
	);

	$pr_item_reviews = vipgoci_github_pr_reviews_get(
		$options['repo-owner'],
		$options['repo-name'],
		(int) $pr_item->number,
		$options['token'],
		array(
			'login' => 'myself',
			'state' => array( 'APPROVED' ),
		),
		true // Bypass caching.
	);

	/*
	 * Dismiss any approving reviews.
	 */
	foreach ( $pr_item_reviews as $pr_item_review ) {
		vipgoci_github_pr_review_dismiss(
			$options['repo-owner'],
			$options['repo-name'],
			(int) $pr_item->number,
			(int) $pr_item_review->id,
			'Dismissing obsolete review; not approved any longer',
			$options['token']
		);
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'vipgoci_auto_approval_non_approval' );
}

/**
 * Approve a particular pull request,
 * alter label for the PR if needed,
 * remove old comments, and log everything
 * we do.
 *
 * Note that the testing of this function is
 * covered by tests of vipgoci_auto_approval_scan_commit().
 *
 * @codeCoverageIgnore
 *
 * @param array       $options                 Options needed.
 * @param object      $pr_item                 Pull request item.
 * @param object|bool $pr_label                Pull request label found, false when none is found.
 * @param array       $auto_approved_files_arr Array of auto-approved files.
 * @param array       $files_seen              Files processed during auto-approval.
 *
 * @return void
 */
function vipgoci_autoapproval_do_approve(
	array $options,
	object $pr_item,
	object|bool $pr_label,
	array &$auto_approved_files_arr,
	array $files_seen
) :void {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'vipgoci_autoapproval_do_approve' );

	$pr_item_approval_reviews =
		vipgoci_github_pr_reviews_get(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token'],
			array(
				'login' => 'myself',
				'state' => array( 'APPROVED' ),
			),
			true
		);

	if ( empty( $pr_item_approval_reviews ) ) {
		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'github_pr_approval',
			1
		);

		$tmp_github_url =
			VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
			rawurlencode( $options['repo-owner'] ) . '/' .
			rawurlencode( $options['repo-name'] ) . '/' .
			'pull/' . (int) $pr_item->number;

		vipgoci_log(
			'Will auto-approve pull request #' .
				(int) $pr_item->number . ' ' .
				'as it alters or creates ' .
				'only files that can be ' .
				'automatically approved' .
				' -- PR URL: ' . $tmp_github_url,
			array(
				'repo_owner'              => $options['repo-owner'],
				'repo_name'               => $options['repo-name'],
				'pr_number'               => (int) $pr_item->number,
				'commit_id'               => $options['commit'],
				'autoapprove-filetypes'   => $options['autoapprove-filetypes'],
				'auto_approved_files_arr' => $auto_approved_files_arr,
				'files_seen'              => $files_seen,
			),
			0
		);

		/*
		 * Actually approve, if not in dry-mode.
		 * Also add a label to the pull request
		 * if applicable.
		 */
		vipgoci_github_approve_pr(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->number,
			$options['commit'],
			'Auto-approved pull request #' .
				(int) $pr_item->number . ' as it ' .
				'contains only auto-approvable files' .
				' -- either pre-approved files' .
				(
					true === $options['autoapprove-php-nonfunctional-changes'] ?
					', non-functional changes to PHP files ' : ''
				) .
				' _or_ file-types that are ' .
				'auto-approvable (' .
				implode(
					', ',
					array_map(
						function( $type ) {
							return '`' . $type . '`';
						},
						$options['autoapprove-filetypes']
					)
				) .
				').'
		);

		// Record that we submitted feedback to GitHub.
		vipgoci_report_feedback_to_github_was_submitted(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			true
		);
	} else {
		vipgoci_log(
			'Will not actually approve pull request #' .
				(int) $pr_item->number .
				', as it is already approved by us',
			array(
				'repo_owner'              => $options['repo-owner'],
				'repo_name'               => $options['repo-name'],
				'pr_number'               => $pr_item->number,
				'commit_id'               => $options['commit'],
				'autoapprove-filetypes'   => $options['autoapprove-filetypes'],
				'auto_approved_files_arr' => $auto_approved_files_arr,
				'files_seen'              => $files_seen,
			),
			0
		);
	}

	/*
	 * Add label to pull request, but
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
			$options['autoapprove-label']
		);
	} else {
		vipgoci_log(
			'Will not add label to issue, ' .
				'as it already exists',
			array(
				'repo_owner' => $options['repo-owner'],
				'repo_name'  => $options['repo-name'],
				'pr_number'  => $pr_item->number,
				'label_name' => $options['autoapprove-label'],
			)
		);
	}

	/*
	 * Remove any comments indicating that a file is
	 * approved -- we want to get rid of these, as they
	 * are useless to reviewers at this point. The PR is
	 * approved anyway.
	 */
	vipgoci_log(
		'Removing any previously submitted comments ' .
			'indicating that a particular file ' .
			'is approved as the whole ' .
			'Pull-Request is approved',
		array(
			'pr_number' => $pr_item->number,
		)
	);

	$pr_comments = vipgoci_github_pr_reviews_comments_get_by_pr(
		$options,
		$pr_item->number,
		array(
			'login' => 'myself',
		)
	);

	foreach ( $pr_comments as $pr_comment_item ) {
		/*
		 * If we find the 'approved in hashes-to-hashes ...'
		 * message, we can safely remove the comment.
		 */
		if ( false !== strpos(
			$pr_comment_item->body,
			rtrim( VIPGOCI_FILE_IS_APPROVED_MSG, '.' )
		) ) {
			vipgoci_github_pr_reviews_comments_delete(
				$options,
				$pr_comment_item->id
			);
		}
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'vipgoci_autoapproval_do_approve' );
}

/**
 * Process auto-approval(s) of the pull request(s)
 * involved with the commit specified.
 *
 * @param array $options                 Array of options.
 * @param array $auto_approved_files_arr Array of auto-approved files.
 * @param array $results                 Results of scanning.
 *
 * @return void
 */
function vipgoci_auto_approval_scan_commit(
	array $options,
	array &$auto_approved_files_arr,
	array &$results
) :void {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'auto_approve_commit' );

	vipgoci_log(
		'Performing auto-approval',
		array(
			'repo_owner'             => $options['repo-owner'],
			'repo_name'              => $options['repo-name'],
			'commit_id'              => $options['commit'],
			'autoapprove'            => $options['autoapprove'],
			'autoapproved_files_arr' => $auto_approved_files_arr,
		)
	);

	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore'],
		$options['skip-draft-prs']
	);

	foreach ( $prs_implicated as $pr_item ) {
		$pr_diff = vipgoci_git_diffs_fetch(
			$options['local-git-repo'],
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->base->sha,
			$options['commit'],
			true, // Include renamed files.
			true, // Include removed files.
			true  // Include permission changes.
		);

		$did_foreach      = false;
		$can_auto_approve = true;

		$files_seen = array();

		/*
		 * Loop through all files that are
		 * altered by the pull request, look for
		 * files that can be auto-approved.
		 */
		foreach ( $pr_diff['files'] as
			$pr_diff_file_name => $pr_diff_contents
		) {
			$did_foreach  = true;
			$files_seen[] = $pr_diff_file_name;

			/*
			 * Is file in array of files
			 * that can be auto-approved?
			 * If not, we cannot auto-approve.
			 */
			if ( ! isset(
				$auto_approved_files_arr[ $pr_diff_file_name ]
			) ) {
				$can_auto_approve = false;
				break;
			}
		}

		/*
		 * Get label associated, but
		 * only our auto-approved one
		 */
		$pr_label = vipgoci_github_pr_labels_get(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			(int) $pr_item->number,
			$options['autoapprove-label'],
			true
		);

		if ( false === $did_foreach ) {
			$tmp_github_url =
				VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
				rawurlencode( $options['repo-owner'] ) . '/' .
				rawurlencode( $options['repo-name'] ) . '/' .
				'pull/' . (int) $pr_item->number;

			vipgoci_log(
				'No action taken with pull request #' .
					(int) $pr_item->number . ' ' .
					'since no files were found' .
					' -- PR URL: ' . $tmp_github_url,
				array(
					'auto_approved_files_arr' => $auto_approved_files_arr,
					'files_seen'              => $files_seen,
					'pr_number'               => (int) $pr_item->number,
					'pr_diff'                 => $pr_diff,
				),
				0
			);
		} elseif (
			( true === $did_foreach ) &&
			( false === $can_auto_approve )
		) {
			vipgoci_auto_approval_non_approval(
				$options,
				$results,
				$pr_item,
				$pr_label,
				$auto_approved_files_arr,
				$files_seen,
				array_keys( $pr_diff['files'] )
			);
		} elseif (
			( true === $did_foreach ) &&
			( true === $can_auto_approve )
		) {
			vipgoci_autoapproval_do_approve(
				$options,
				$pr_item,
				$pr_label,
				$auto_approved_files_arr,
				$files_seen
			);
		}

		unset( $files_seen );
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'auto_approve_commit' );
}

/**
 * Perform auto-approvals.
 *
 * Will first ask all 'auto-approval modules'
 * to do their scanning, collecting all files that
 * can be auto-approved, and then actually do the
 * auto-approval if possible.
 *
 * @param array $options Array of options.
 * @param array $results Array of results from scanning.
 *
 * @return void
 */
function vipgoci_auto_approval_process(
	array &$options,
	array &$results
) :void {
	if ( false === $options['autoapprove'] ) {
		vipgoci_log(
			'Not performing auto-approvals, as not configured to do so',
			array(
				'autoapprove' => $options['autoapprove'],
			)
		);

		return;
	}

	// Start with empty array of approved files.
	$auto_approved_files_arr = array();

	/*
	 * If to auto-approve based on file-types,
	 * scan through the files in the PR, and
	 * register which can be auto-approved.
	 */

	if ( ! empty( $options['autoapprove-filetypes'] ) ) {
		vipgoci_ap_file_types(
			$options,
			$auto_approved_files_arr
		);
	}

	/*
	 * Check if any of the files changed
	 * contain any non-functional changes --
	 * i.e., only whitespacing changes and
	 * commenting changes -- and if so,
	 * approve those files.
	 */
	if ( true === $options['autoapprove-php-nonfunctional-changes'] ) {
		vipgoci_ap_nonfunctional_changes(
			$options,
			$auto_approved_files_arr
		);
	}

	/*
	 * Do scanning of all altered files, using
	 * the hashes-to-hashes database API, collecting
	 * which files can be auto-approved.
	 */

	if ( true === $options['hashes-api'] ) {
		vipgoci_ap_hashes_api_scan_commit(
			$options,
			$results['issues'],
			$results['stats'][ VIPGOCI_STATS_HASHES_API ],
			$auto_approved_files_arr
		);
	}

	// If set to true, any SVG files without issues is auto-approved.
	if ( true === $options['svg-checks'] ) {
		vipgoci_ap_svg_files(
			$options,
			$auto_approved_files_arr
		);
	}

	// Actually perform auto-approvals (if possible).
	vipgoci_auto_approval_scan_commit(
		$options,
		$auto_approved_files_arr,
		$results
	);

	/*
	 * Remove issues from $results for files
	 * that are approved in hashes-to-hashes API.
	 */
	vipgoci_results_approved_files_comments_remove(
		$options,
		$results,
		$auto_approved_files_arr
	);

	vipgoci_log(
		'Auto-approval process complete',
		array()
	);
}

