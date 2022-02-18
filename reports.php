<?php
/**
 * Reporting logic for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Generate HTML-style list for the report from an array,
 * allow various options to ease the generation and
 * to make it more generic.
 *
 * @param string $left                 String to the left of each entry.
 * @param string $right                String to the right of each entry.
 * @param array  $items_arr            Array to process.
 * @param string $when_items_arr_empty When array is empty, return this string.
 * @param string $when_key_values      String to use as separator between key and value.
 *
 * @return string HTML for list.
 */
function vipgoci_report_create_scan_details_list(
	string $left,
	string $right,
	array $items_arr,
	string $when_items_arr_empty,
	string $when_key_values = ''
) :string {
	$return_string = '';

	if ( empty( $items_arr ) ) {
		$return_string .= $when_items_arr_empty;
	} else {
		foreach ( $items_arr as $arr_item_key => $arr_item_value ) {
			$return_string .= $left;

			if ( is_numeric( $arr_item_key ) ) {
				$return_string .= vipgoci_output_html_escape( $arr_item_value );
			} else {
				$return_string .= vipgoci_output_html_escape( (string) $arr_item_key );
				$return_string .= $when_key_values;
				$return_string .= vipgoci_output_html_escape( (string) $arr_item_value );
			}

			$return_string .= $right;
		}
	}

	return $return_string;
}

/**
 * Create scan report detail message.
 *
 * Information is either gathered or
 * based on $options and $results.
 *
 * @param array $options Options needed.
 *
 * @return string Detail message.
 */
function vipgoci_report_create_scan_details(
	array $options
) :string {
	$details  = '<details>' . PHP_EOL;
	$details .= '<hr />' . PHP_EOL;
	$details .= '<summary>Scan run detail</summary>' . PHP_EOL;

	$details .= '<table>' . PHP_EOL;
	$details .= '<tr>' . PHP_EOL;

	$details .= '<td valign="top" width="33%">';
	$details .= '<h4>Software versions</h4>' . PHP_EOL;

	$details .= '<ul>' . PHP_EOL;

	$details .= '<li>vip-go-ci version: <code>' . vipgoci_output_sanitize_version_number( VIPGOCI_VERSION ) . '</code></li>' . PHP_EOL;

	$php_runtime_version = phpversion();

	if ( ! empty( $php_runtime_version ) ) {
		$details .= '<li>PHP runtime version for vip-go-ci: <code>' . vipgoci_output_sanitize_version_number( $php_runtime_version ) . '</code></li>' . PHP_EOL;
	}

	$php_linting_version = vipgoci_util_php_interpreter_get_version(
		$options['lint-php-path']
	);

	if ( ! empty( $php_linting_version ) ) {
		$details .= '<li>PHP runtime for PHP linting: <code>' . vipgoci_output_sanitize_version_number( $php_linting_version ) . '</code></li>' . PHP_EOL;
	}

	$phpcs_php_version = vipgoci_util_php_interpreter_get_version(
		$options['phpcs-php-path']
	);

	if ( ! empty( $phpcs_php_version ) ) {
		$details .= '<li>PHP runtime for PHPCS: <code>' . vipgoci_output_sanitize_version_number( $phpcs_php_version ) . '</code></li>' . PHP_EOL;
	}

	$phpcs_version = vipgoci_phpcs_get_version(
		$options['phpcs-path'],
		$options['phpcs-php-path']
	);

	if ( ! empty( $phpcs_version ) ) {
		$details .= '<li>PHPCS version: <code>' . vipgoci_output_sanitize_version_number( $phpcs_version ) . '</code></li>' . PHP_EOL;
	}

	$details .= '</ul>' . PHP_EOL;

	$details .= '</td>' . PHP_EOL;

	$details .= '<td valign="top" width="33%">' . PHP_EOL;

	$details .= '<h4>Options altered</h4>' . PHP_EOL;
	$details .= '<ul>' . PHP_EOL;

	$details .= vipgoci_report_create_scan_details_list(
		'<li><code>',
		'</code></li>',
		$options['repo-options-set'],
		'<li>None</li>',
		'</code> set to <code>'
	);

	$details .= '</ul>' . PHP_EOL;

	$details .= '<h4>Directories not scanned</h4>' . PHP_EOL;

	foreach (
		array(
			'lint-skip-folders'  => 'Not PHP linted',
			'phpcs-skip-folders' => 'Not PHPCS scanned',
		) as $key => $value
	) {
		$details .= '<p>' . vipgoci_output_html_escape( $value ) . ':</p>' . PHP_EOL;
		$details .= '<ul>' . PHP_EOL;

		$details .= vipgoci_report_create_scan_details_list(
			'<li><code>',
			'</code></li>',
			$options[ $key ],
			'<li>None</li>'
		);

		$details .= '</ul>' . PHP_EOL;
	}

	$details .= '</td>' . PHP_EOL;

	$details .= '<td valign="top" width="33%">' . PHP_EOL;

	$details .= '<h4>PHPCS configuration</h4>' . PHP_EOL;

	foreach (
		array(
			'phpcs-standard'       => 'Standard(s) used',
			'phpcs-sniffs-include' => 'Custom sniffs included',
			'phpcs-sniffs-exclude' => 'Custom sniffs excluded',
		) as $key => $value
	) {
		$details .= '<p>' . vipgoci_output_html_escape( $value ) . ':</p>' . PHP_EOL;
		$details .= '<ul>' . PHP_EOL;

		$details .= vipgoci_report_create_scan_details_list(
			'<li><code>',
			'</code></li>',
			$options[ $key ],
			'<li>None</li>'
		);

		$details .= '</ul>' . PHP_EOL;
	}

	$details .= '</tr>' . PHP_EOL;
	$details .= '</table>' . PHP_EOL;

	$details .= '</details>' . PHP_EOL;

	return $details;
}

/**
 * Submit generic PR comment to GitHub, reporting any
 * issues found within $results. Selectively report
 * issues that we are supposed to report on, ignore
 * others. Attempts to format the comment to GitHub.
 */
function vipgoci_report_submit_pr_generic_comment_from_results(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$results,
	$informational_msg,
	$scan_details_msg
) {
	$stats_types_to_process = array(
		VIPGOCI_STATS_LINT,
	);

	vipgoci_log(
		'About to ' .
		'submit generic PR comment to GitHub about issues',
		array(
			'repo_owner' => $repo_owner,
			'repo_name'  => $repo_name,
			'commit_id'  => $commit_id,
			'results'    => $results,
		)
	);

	foreach (
		// The $results['issues'] array is keyed by Pull-Request number
		array_keys(
			$results['issues']
		) as $pr_number
	) {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( $pr_number ) . '/' .
			'comments';

		$github_postfields = array(
			'body' => '',
		);

		$tmp_linebreak = false;

		foreach (
			$results['issues'][ $pr_number ]
				as $commit_issue
		) {
			if ( ! in_array(
				strtolower(
					$commit_issue['type']
				),
				$stats_types_to_process,
				true
			) ) {
				// Not an issue we process, ignore
				continue;
			}

			/*
			 * Put in linebreaks
			 */

			if ( false === $tmp_linebreak ) {
				$tmp_linebreak = true;
			} else {
				$github_postfields['body'] .= "\n\r";

				vipgoci_markdown_comment_add_pagebreak(
					$github_postfields['body']
				);
			}

			/*
			 * Construct comment -- (start or continue)
			 */
			$github_postfields['body'] .=
				'**' .

				// First in: level (error, warning)
				ucfirst(
					strtolower(
						$commit_issue['issue']['level']
					)
				) .

				'**' .

				': ' .

				// Then the message
				str_replace(
					'\'',
					'`',
					$commit_issue['issue']['message']
				) .

				"\n\r\n\r" .

				// And finally a URL to the issue is
				VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
					$repo_owner . '/' .
					$repo_name . '/' .
					'blob/' .
					$commit_id . '/' .
					$commit_issue['file_name'] .
					'#L' . $commit_issue['file_line'] .

				"\n\r";
		}

		if ( $github_postfields['body'] === '' ) {
			/*
			 * No issues? Nothing to report to GitHub.
			 */

			continue;
		}

		/*
		 * There are issues, report them.
		 *
		 * Put togather a comment to be posted to GitHub
		 * -- splice a header to the message we currently have.
		 */

		$tmp_postfields_body =
			'**' . VIPGOCI_SYNTAX_ERROR_STR . '**' .
			"\n\r\n\r" .

			'Scan performed on the code at commit ' . $commit_id .
				' ([view code](' .
				VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'tree/' .
				rawurlencode( $commit_id ) .
				')).' .
				"\n\r";

		vipgoci_markdown_comment_add_pagebreak(
			$tmp_postfields_body
		);

		/*
		 * If we have informational URL, append that
		 * and a generic message.
		 */
		if ( ! empty( $informational_msg ) ) {
			$tmp_postfields_body .=
				$informational_msg .
				"\n\r";

			vipgoci_markdown_comment_add_pagebreak(
				$tmp_postfields_body
			);
		}

		/*
		 * Splice the two messages together,
		 * remove temporary variable.
		 */
		$github_postfields['body'] =
			$tmp_postfields_body .
			$github_postfields['body'];

		unset( $tmp_postfields_body );

		/*
		 * Append scan details
		 * message if we have that.
		 */
		if ( ! empty( $scan_details_msg ) ) {
			$github_postfields['body'] .= $scan_details_msg;
		}

		vipgoci_github_post_url(
			$github_url,
			$github_postfields,
			$github_token
		);
	}
}

/**
 * Submit a review on GitHub for a particular commit,
 * and pull-request using the access-token provided.
 */
function vipgoci_report_submit_pr_review_from_results(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$results,
	$informational_msg,
	$scan_details_msg,
	$github_review_comments_max,
	$github_review_comments_include_severity,
	int $skip_large_files_limit
) {

	$stats_types_to_process = array(
		VIPGOCI_STATS_PHPCS,
		VIPGOCI_STATS_HASHES_API,
	);

	vipgoci_log(
		'About to submit comment(s) to GitHub about issue(s)',
		array(
			'repo_owner' => $repo_owner,
			'repo_name'  => $repo_name,
			'commit_id'  => $commit_id,
			'results'    => $results,
		)
	);

	/*
	 * Reverse results before starting processing,
	 * so that results are shown in correct order
	 * after posting.
	 */

	foreach (
		array_keys(
			$results['issues']
		) as $pr_number
	) {
		$results['issues'][ $pr_number ] = array_reverse(
			$results['issues'][ $pr_number ]
		);
	}

	foreach (
		// The $results array is keyed by Pull-Request number
		array_keys(
			$results['issues']
		) as $pr_number
	) {

		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls/' .
			rawurlencode( $pr_number ) . '/' .
			'reviews';

		$github_postfields = array(
			'commit_id' => $commit_id,
			'body'      => '',
			'event'     => '',
			'comments'  => array(),
		);

		/*
		 * For each issue reported, format
		 * and prepare to be published on
		 * GitHub -- ignore those issues
		 * that we should not process.
		 */
		foreach (
			$results['issues'][ $pr_number ]
				as $commit_issue
		) {
			if ( ! in_array(
				strtolower(
					$commit_issue['type']
				),
				$stats_types_to_process,
				true
			) ) {
				// Not an issue we process, ignore
				continue;
			}

			/*
			 * Construct comment, append to array of comments.
			 */

			$github_postfields['comments'][] = array(
				'body'     =>

					// Add nice label
					vipgoci_github_transform_to_emojis(
						$commit_issue['issue']['level']
					) . ' ' .

					'**' .

					// Level -- error, warning
					ucfirst(
						strtolower(
							$commit_issue['issue']['level']
						)
					) .

					(
						true === $github_review_comments_include_severity ?
							(
								'( severity ' .
								$commit_issue['issue']['severity'] .
								' )'
							)
							:
							( '' )
					) .

					'**: ' .

					// Then the message it self
					htmlentities(
						rtrim(
							$commit_issue['issue']['message'],
							'.'
						)
					)

					. ' (*' .
					htmlentities(
						$commit_issue['issue']['source']
					)
					. '*).',

				'position' => $commit_issue['file_line'],
				'path'     => $commit_issue['file_name'],
			);
		}

		/*
		 * Figure out what to report to GitHub.
		 *
		 * If there are any 'error'-level issues, make sure the submission
		 * asks for changes to be made, otherwise only comment.
		 *
		 * If there are no issues at all -- warning, error, info -- do not
		 * submit anything.
		 */

		$github_postfields['event'] = 'COMMENT';

		$github_errors   = false;
		$github_warnings = false;
		$github_info     = false;

		foreach (
			$stats_types_to_process as
				$stats_type
		) {
			if ( ! empty(
				$results['stats']
					[ $stats_type ][ $pr_number ]['error']
			) ) {
				$github_postfields['event'] = 'REQUEST_CHANGES';
				$github_errors              = true;
			}

			if ( ! empty(
				$results['stats']
					[ $stats_type ][ $pr_number ]['warning']
			) ) {
				$github_warnings = true;
			}

			if ( ! empty(
				$results['stats']
					[ $stats_type ][ $pr_number ]['info']
			) ) {
				$github_info = true;
			}
		}

		/*
		 * If there are no issues to report to GitHub,
		 * do not continue processing the Pull-Request.
		 * Our exit signal will indicate if anything is wrong.
		 */
		if (
			( false === $github_errors ) &&
			( false === $github_warnings ) &&
			( false === $github_info ) &&
			empty( $results[ VIPGOCI_SKIPPED_FILES ][ $pr_number ]['issues'] )
		) {
			continue;
		}

		unset( $github_errors );
		unset( $github_warnings );

		/*
		 * Compose the number of warnings/errors for the
		 * review-submission to GitHub.
		 */

		foreach (
			$stats_types_to_process as
				$stats_type
		) {
			/*
			 * Add page-breaking, if needed.
			 */
			if ( ! empty( $github_postfields['body'] ) ) {
				vipgoci_markdown_comment_add_pagebreak(
					$github_postfields['body']
				);
			}

			/*
			 * Check if this type of scanning
			 * was skipped, and if so, note it.
			 */

			if ( empty(
				$results
					['stats']
					[ strtolower( $stats_type ) ]
					[ $pr_number ]
			) ) {
				$github_postfields['body'] .=
					'**' . $stats_type . '**' .
						"-scanning skipped\n\r";

				// Skipped
				continue;
			}

			/*
			 * If the current stat-type has no items
			 * to report, do not print out anything for
			 * it saying we found something to report on.
			 */

			$found_stats_to_ignore = true;

			foreach (
				$results
					['stats']
					[ strtolower( $stats_type ) ]
					[ $pr_number ] as

					$commit_issue_stat_key =>
						$commit_issue_stat_value
			) {
				if ( $commit_issue_stat_value > 0 ) {
					$found_stats_to_ignore = false;
				}
			}

			if ( true === $found_stats_to_ignore ) {
				// Skipped
				continue;
			}

			unset( $found_stats_to_ignore );

			$github_postfields['body'] .=
				'**' . $stats_type . '**' .
				" scanning turned up:\n\r";

			foreach (
				$results
					['stats']
					[ strtolower( $stats_type ) ]
					[ $pr_number ] as

					$commit_issue_stat_key =>
						$commit_issue_stat_value
			) {
				/*
				 * Do not include statistic in the
				 * the report if nothing is found.
				 *
				 * Note that if nothing is found at
				 * all, we will not get to this point,
				 * so there is no need to report if
				 * nothing is found at all.
				 */
				if ( 0 === $commit_issue_stat_value ) {
					continue;
				}

				$github_postfields['body'] .=
					vipgoci_github_transform_to_emojis(
						$commit_issue_stat_key
					) . ' ' .

					$commit_issue_stat_value . ' ' .
					$commit_issue_stat_key .
					( ( $commit_issue_stat_value > 1 ) ? 's' : '' ) .
					' ' .
					"\n\r";
			}
		}

		/**
		 * Check if there're previous existent comments about the same files
		 */
		$pr_reviews_commented = vipgoci_github_pr_reviews_get(
			$repo_owner,
			$repo_name,
			$pr_number,
			$github_token,
			array(
				'login' => 'myself',
				'state' => array( 'COMMENTED', 'CHANGES_REQUESTED' ),
			)
		);

		$validation_message                             = vipgoci_skip_file_get_validation_message_prefix( VIPGOCI_VALIDATION_MAXIMUM_LINES, $skip_large_files_limit );
		$results[ VIPGOCI_SKIPPED_FILES ][ $pr_number ] = vipgoci_skip_file_check_previous_pr_comments( $results[ VIPGOCI_SKIPPED_FILES ][ $pr_number ], $pr_reviews_commented, $validation_message );

		/**
		 * Format skipped files message if the validation has issues
		 */
		if ( 0 < $results[ VIPGOCI_SKIPPED_FILES ][ $pr_number ]['total'] ) {
			vipgoci_markdown_comment_add_pagebreak(
				$github_postfields['body']
			);

			$github_postfields['body'] .= vipgoci_get_skipped_files_message(
				$results[ VIPGOCI_SKIPPED_FILES ][ $pr_number ],
				$validation_message
			);
		}

		/*
		 * If we have a informational-URL about
		 * the bot, append it along with a generic
		 * message.
		 */
		if ( ! empty( $informational_msg ) ) {
			$github_postfields['body'] .=
				"\n\r";

			vipgoci_markdown_comment_add_pagebreak(
				$github_postfields['body']
			);

			$github_postfields['body'] .= $informational_msg;
		}

		/*
		 * Append scan details
		 * message if we have that.
		 */
		if ( ! empty( $scan_details_msg ) ) {
			$github_postfields['body'] .= $scan_details_msg;
		}

		/*
		 * Only submit a specific number of comments in one go.
		 *
		 * This hopefully will reduce the likelihood of problems
		 * with the GitHub API. Also, it will avoid excessive number
		 * of comments being posted at once.
		 *
		 * Do this by picking out a few comments at a time,
		 * submit, and repeat.
		 */

		if (
			count( $github_postfields['comments'] ) >
				$github_review_comments_max
		) {
			// Append a comment that there will be more reviews
			$github_postfields['body'] .=
				"\n\r" .
				'Posting will continue in further review(s)';
		}

		do {
			/*
			 * Set temporary variable we use for posting
			 * and remove all comments from it.
			 */
			$github_postfields_tmp = $github_postfields;

			unset( $github_postfields_tmp['comments'] );

			/*
			 * Add in comments.
			 */

			for ( $i = 0; $i < $github_review_comments_max; $i++ ) {
				$y = count( $github_postfields['comments'] );

				if ( 0 === $y ) {
					/* No more items, break out */
					break;
				}

				$y--;

				$github_postfields_tmp['comments'][] =
					$github_postfields['comments'][ $y ];

				unset(
					$github_postfields['comments'][ $y ]
				);
			}

			// Actually send a request to GitHub
			$github_post_res_tmp = vipgoci_github_post_url(
				$github_url,
				$github_postfields_tmp,
				$github_token
			);

			/*
			 * If something goes wrong with any submission,
			 * keep a note on that.
			 */
			if (
				( ! isset( $github_post_res ) ||
				( -1 !== $github_post_res ) )
			) {
				$github_post_res = $github_post_res_tmp;
			}

			// Set a new post-body for future posting.
			$github_postfields['body'] = 'Previous scan continued.';
		} while ( count( $github_postfields['comments'] ) > 0 );

		unset( $github_post_res_tmp );
		unset( $y );
		unset( $i );

		/*
		 * If one or more submissions went wrong,
		 * let humans know that there was a problem.
		 */
		if ( -1 === $github_post_res ) {
			vipgoci_github_pr_comments_generic_submit(
				$repo_owner,
				$repo_name,
				$github_token,
				$pr_number,
				VIPGOCI_GITHUB_ERROR_STR,
				$commit_id
			);
		}
	}

	return;
}

/*
 * Post generic comment to each Pull-Request
 * that has target branch that matches the
 * options given, but only if the same generic
 * comment has not been posted before. Uses a
 * comment given by one of the options.
 */
function vipgoci_github_pr_generic_support_comment_submit(
	$options,
	$prs_implicated
) {

	$log_debugmsg =
		array(
			'post-generic-pr-support-comments'           =>
				$options['post-generic-pr-support-comments'],

			'post-generic-pr-support-comments-on-drafts' =>
				$options['post-generic-pr-support-comments-on-drafts'],

			'post-generic-pr-support-comments-string'    =>
				$options['post-generic-pr-support-comments-string'],

			'post-generic-pr-support-comments-branches'  =>
				$options['post-generic-pr-support-comments-branches'],

			'post-generic-pr-support-comments-repo-meta-match' =>
				$options['post-generic-pr-support-comments-repo-meta-match'],
		);

	/*
	 * Detect if to run, or invalid configuration.
	 */
	if (
		( true !== $options['post-generic-pr-support-comments'] ) ||
		( empty( $options['post-generic-pr-support-comments-string'] ) ) ||
		( empty( $options['post-generic-pr-support-comments-branches'] ) )
	) {
		vipgoci_log(
			'Not posting support-comments on Pull-Requests, as ' .
				'either not configured to do so, or ' .
				'incorrectly configured',
			$log_debugmsg
		);

		return;
	} else {
		vipgoci_log(
			'Posting support-comments on Pull-Requests',
			$log_debugmsg
		);
	}

	/*
	 * Check if a field value in response
	 * from repo-meta API service
	 * matches the field value given here.
	 */
	if ( ! empty( $options['post-generic-pr-support-comments-repo-meta-match'] ) ) {
		$option_key_no_match = null;

		$repo_meta_api_data_match = vipgoci_repo_meta_api_data_match(
			$options,
			'post-generic-pr-support-comments-repo-meta-match',
			$option_key_no_match
		);

		if ( true !== $repo_meta_api_data_match ) {
			vipgoci_log(
				'Not posting generic support comment, as repo-meta API field-value did not match given criteria',
				array()
			);

			return;
		}
	} else {
		/*
		 * If matching is not configured, we post
		 * first message we can find.
		 */

		$tmp_generic_support_msgs_keys = array_keys(
			$options['post-generic-pr-support-comments-string']
		);

		$option_key_no_match = $tmp_generic_support_msgs_keys[0];
	}

	foreach (
		$prs_implicated as $pr_item
	) {
		/*
		 * If not one of the target-branches,
		 * skip this PR.
		 */
		if (
			( in_array(
				'any',
				$options['post-generic-pr-support-comments-branches'][ $option_key_no_match ],
				true
			) === false )
			&&
			( ( in_array(
				$pr_item->base->ref,
				$options['post-generic-pr-support-comments-branches'][ $option_key_no_match ],
				true
			) === false ) )
		) {
			vipgoci_log(
				'Not posting support-comment to PR, not in list of target branches',
				array(
					'repo-owner'  => $options['repo-owner'],
					'repo-name'   => $options['repo-name'],
					'pr_number'   => $pr_item->number,
					'pr_base_ref' => $pr_item->base->ref,
					'post-generic-pr-support-comments-branches' =>
						$options['post-generic-pr-support-comments-branches'][ $option_key_no_match ],
				)
			);

			continue;
		}

		/*
		 * Do not post support comments on drafts when
		 * not configured to do so.
		 */
		if (
			( false === $options['post-generic-pr-support-comments-on-drafts'][ $option_key_no_match ] ) &&
			( true === $pr_item->draft )
		) {
			vipgoci_log(
				'Not posting support-comment to PR, is draft',
				array(
					'repo-owner'  => $options['repo-owner'],
					'repo-name'   => $options['repo-name'],
					'pr_number'   => $pr_item->number,
					'pr_base_ref' => $pr_item->base->ref,
					'post-generic-pr-support-comments-on-drafts' =>
						$options['post-generic-pr-support-comments-on-drafts'][ $option_key_no_match ],
				)
			);

			continue;
		}

		/*
		 * When configured to do so, do not post support comments when a special label
		 * has been added to the Pull-Request.
		 */

		if ( ! empty( $options['post-generic-pr-support-comments-skip-if-label-exists'][ $option_key_no_match ] ) ) {
			$pr_label_support_comment_skip = vipgoci_github_pr_labels_get(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				$options['post-generic-pr-support-comments-skip-if-label-exists'][ $option_key_no_match ]
			);

			if ( false !== $pr_label_support_comment_skip ) {
				vipgoci_log(
					'Not posting support comment to PR, label exists',
					array(
						'repo-owner'  => $options['repo-owner'],
						'repo-name'   => $options['repo-name'],
						'pr_number'   => $pr_item->number,
						'pr_base_ref' => $pr_item->base->ref,
						'post-generic-pr-support-comments-skip-if-label-exists' =>
							$options['post-generic-pr-support-comments-skip-if-label-exists'][ $option_key_no_match ],
					)
				);

				continue;
			}
		}

		/*
		 * Check if the comment we are set to
		 * post already exists, and if so, do
		 * not post anything.
		 */

		$existing_comments = vipgoci_github_pr_generic_comments_get_all(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token']
		);

		$comment_exists_already = false;

		foreach (
			$existing_comments as
				$existing_comment_item
		) {

			if ( strpos(
				$existing_comment_item->body,
				$options['post-generic-pr-support-comments-string'][ $option_key_no_match ]
			) !== false ) {
				$comment_exists_already = true;
			}
		}

		if ( true === $comment_exists_already ) {
			vipgoci_log(
				'Not submitting support-comment to Pull-Request as it already exists',
				array(
					'pr_number' => $pr_item->number,
				)
			);

			continue;
		}

		/*
		 * All checks successful, post comment.
		 */
		vipgoci_github_pr_comments_generic_submit(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->number,
			$options['post-generic-pr-support-comments-string'][ $option_key_no_match ]
		);
	}
}

