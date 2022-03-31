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
 * @param string         $left            String to the left of each entry.
 * @param string         $right           String to the right of each entry.
 * @param array|bool|int $data            Data to process.
 * @param string         $when_data_empty When $data is empty, return this string.
 * @param string         $when_key_values String to use as separator between key and value.
 *
 * @return string HTML for list.
 *
 * @codeCoverageIgnore
 */
function vipgoci_report_create_scan_details_list(
	string $left,
	string $right,
	array|bool|int $data,
	string $when_data_empty,
	string $when_key_values = ''
) :string {
	/*
	 * If a boolean or numeric, process and return immediately.
	 */
	if ( is_bool( $data ) ) {
		if ( true === $data ) {
			$tmp_output = 'true';
		} else {
			$tmp_output = 'false';
		}

		return $left . $tmp_output . $right;
	} elseif ( is_numeric( $data ) ) {
		return $left . (string) $data . $right;
	}

	/*
	 * When an array, process further.
	 */
	if ( empty( $data ) ) {
		// Empty array - return.
		return $when_data_empty;
	}

	/*
	 * Not an empty array, continue processing.
	 */
	$return_string = '';

	foreach ( $data as $arr_item_key => $arr_item_value ) {
		if ( is_array( $arr_item_value ) ) {
			$arr_item_value = join( ', ', $arr_item_value );
		}

		$return_string .= $left;

		if ( is_numeric( $arr_item_key ) ) {
			$return_string .= vipgoci_output_html_escape(
				$arr_item_value
			);
		} else {
			$return_string .= vipgoci_output_html_escape(
				(string) $arr_item_key
			);

			$return_string .= $when_key_values;

			$return_string .= vipgoci_output_html_escape(
				(string) $arr_item_value
			);
		}

		$return_string .= $right;
	}

	return $return_string;
}

/**
 * Create scan report detail message for
 * software versions.
 *
 * @param array $options_copy Options needed.
 *
 * @return string Detail message for section.
 */
function vipgoci_report_create_scan_details_software_versions(
	array $options_copy
) :string {
	$details = '<h4>Software versions</h4>' . PHP_EOL;

	$details .= '<ul>' . PHP_EOL;

	$details .= '<li>vip-go-ci version: <code>' . vipgoci_output_sanitize_version_number( VIPGOCI_VERSION ) . '</code></li>' . PHP_EOL;

	$php_runtime_version = phpversion();

	if ( ! empty( $php_runtime_version ) ) {
		$details .= '<li>PHP runtime version for vip-go-ci: <code>' . vipgoci_output_sanitize_version_number( $php_runtime_version ) . '</code></li>' . PHP_EOL;
	}

	if ( true === $options_copy['lint'] ) {
		$details .= '<li>PHP runtime for linting: ' . PHP_EOL;
		$details .= '<ul>' . PHP_EOL;

		foreach ( $options_copy['lint-php-versions'] as $lint_php_version ) {
			$php_interpreter_version = vipgoci_util_php_interpreter_get_version(
				$options_copy['lint-php-version-paths'][ $lint_php_version ]
			);

			if ( ! empty( $php_interpreter_version ) ) {
				$details .= '<li>PHP ' .
					vipgoci_output_sanitize_version_number( $lint_php_version ) .
					': <code>' .
					vipgoci_output_sanitize_version_number( $php_interpreter_version ) .
					'</code></li>' .
					PHP_EOL;
			}
		}

		$details .= '</ul>';
		$details .= '</li>';
	}

	if ( true === $options_copy['phpcs'] ) {
		$phpcs_php_version = vipgoci_util_php_interpreter_get_version(
			$options_copy['phpcs-php-path']
		);

		if ( ! empty( $phpcs_php_version ) ) {
			$details .= '<li>PHP runtime version for PHPCS: <code>' . vipgoci_output_sanitize_version_number( $phpcs_php_version ) . '</code></li>' . PHP_EOL;
		}

		$phpcs_version = vipgoci_phpcs_get_version(
			$options_copy['phpcs-path'],
			$options_copy['phpcs-php-path']
		);

		if ( ! empty( $phpcs_version ) ) {
			$details .= '<li>PHPCS version: <code>' . vipgoci_output_sanitize_version_number( $phpcs_version ) . '</code></li>' . PHP_EOL;
		}
	}

	if ( true === $options_copy['svg-checks'] ) {
		$svg_php_version = vipgoci_util_php_interpreter_get_version(
			$options_copy['svg-php-path']
		);

		if ( ! empty( $svg_php_version ) ) {
			$details .= '<li>PHP runtime version for SVG scanner: <code>' . vipgoci_output_sanitize_version_number( $svg_php_version ) . '</code></li>' . PHP_EOL;
		}
	}

	$details .= '</ul>' . PHP_EOL;

	$details .= '<h4>Options file (<code>' . vipgoci_output_html_escape( VIPGOCI_OPTIONS_FILE_NAME ) . '</code>)</h4>' . PHP_EOL;

	$details .= '<p>Options file enabled: ' . PHP_EOL;

	$details .= vipgoci_report_create_scan_details_list(
		'<code>',
		'</code>',
		$options_copy['repo-options'],
		'None'
	);

	$details .= '</p>';

	if ( true === $options_copy['repo-options'] ) {
		// Clean repo-options-set array of anything sensitive.
		$options_copy['repo-options-set'] =
			vipgoci_options_sensitive_clean(
				$options_copy['repo-options-set']
			);

		foreach (
			array(
				'repo-options-allowed' => 'Configurable options',
				'repo-options-set'     => 'Options altered',
			) as $key => $value
		) {
			$details .= '<p>' . vipgoci_output_html_escape( $value ) . ':</p>' . PHP_EOL;
			$details .= '<ul>' . PHP_EOL;

			$details .= vipgoci_report_create_scan_details_list(
				'<li><code>',
				'</code></li>',
				$options_copy[ $key ],
				'<li>None</li>',
				'</code>set to<code>'
			);

			$details .= '</ul>' . PHP_EOL;
		}
	}

	return $details;
}

/**
 * Create scan report detail message for
 * PHP lint options section.
 *
 * @param array $options_copy Options needed.
 *
 * @return string Detail message for section.
 */
function vipgoci_report_create_scan_details_php_lint_options(
	array $options_copy
) :string {
	$details = '<h4>PHP lint options</h4>' . PHP_EOL;

	foreach (
		array(
			'lint'                     => 'PHP lint files enabled',
			'lint-modified-files-only' => 'Lint modified files only',
		) as $key => $value
	) {
		if ( ( false === $options_copy['lint'] ) && ( 'lint' !== $key ) ) {
			continue;
		}

		$details .= '<p>' . vipgoci_output_html_escape( $value ) . ': ' . PHP_EOL;
		$details .= vipgoci_report_create_scan_details_list(
			'<code>',
			'</code>',
			$options_copy[ $key ],
			'None'
		);
		$details .= '</p>';
	}

	if ( true === $options_copy['lint'] ) {
		foreach (
			array(
				'lint-skip-folders' => 'Directories not PHP linted',
			) as $key => $value
		) {
			$details .= '<p>' . vipgoci_output_html_escape( $value ) . ':</p>' . PHP_EOL;
			$details .= '<ul>' . PHP_EOL;

			$details .= vipgoci_report_create_scan_details_list(
				'<li><code>',
				'</code></li>',
				$options_copy[ $key ],
				'<li>None</li>'
			);

			$details .= '</ul>' . PHP_EOL;
		}
	}

	return $details;
}

/**
 * Create scan report detail message for
 * PHPCS configuration section.
 *
 * @param array $options_copy Options needed.
 *
 * @return string Detail message for section.
 */
function vipgoci_report_create_scan_details_phpcs_configuration(
	array $options_copy
) :string {
	$details = '<h4>PHPCS configuration</h4>' . PHP_EOL;

	$details .= '<p>PHPCS scanning enabled: ' . PHP_EOL;

	$details .= vipgoci_report_create_scan_details_list(
		'<code>',
		'</code>',
		$options_copy['phpcs'],
		'None'
	);

	$details .= '</p>';

	if ( true === $options_copy['phpcs'] ) {
		$details .= '<p>PHPCS severity level: ' . PHP_EOL;

		$details .= vipgoci_report_create_scan_details_list(
			'<code>',
			'</code>',
			$options_copy['phpcs-severity'],
			'None'
		);

		$details .= '</p>';

		$options_copy['phpcs-runtime-set-tmp'] = array_map(
			function ( $array_item ) {
				return join( ' ', $array_item );
			},
			$options_copy['phpcs-runtime-set']
		);

		if ( true === $options_copy['phpcs-standard-file'] ) {
			$options_copy['phpcs-standard'] =
				$options_copy['phpcs-standard-original'];
		}

		foreach (
			array(
				'phpcs-standard'        => 'Standard(s) used',
				'phpcs-runtime-set-tmp' => 'Runtime set',
				'phpcs-sniffs-include'  => 'Custom sniffs included',
				'phpcs-sniffs-exclude'  => 'Custom sniffs excluded',
				'phpcs-skip-folders'    => 'Directories not PHPCS scanned',
			) as $key => $value
		) {
			$details .= '<p>' . vipgoci_output_html_escape( $value ) . ':</p>' . PHP_EOL;
			$details .= '<ul>' . PHP_EOL;

			$details .= vipgoci_report_create_scan_details_list(
				'<li><code>',
				'</code></li>',
				$options_copy[ $key ],
				'<li>None</li>'
			);

			$details .= '</ul>' . PHP_EOL;
		}
	}

	return $details;
}

/**
 * Create scan report detail message for
 * SVG configuration section.
 *
 * @param array $options_copy Options needed.
 *
 * @return string Detail message for section.
 */
function vipgoci_report_create_scan_details_svg_configuration(
	array $options_copy
) :string {
	$details = '<h4>SVG configuration</h4>' . PHP_EOL;

	$details .= '<p>SVG scanning enabled: ' . PHP_EOL;

	$details .= vipgoci_report_create_scan_details_list(
		'<code>',
		'</code>',
		$options_copy['svg-checks'],
		'None'
	);

	$details .= '</p>';

	return $details;
}

/**
 * Create scan report detail message for
 * auto-approval configuration section.
 *
 * @param array $options_copy Options needed.
 *
 * @return string Detail message for section.
 */
function vipgoci_report_create_scan_details_auto_approve_configuration(
	array $options_copy
) :string {
	$details = '<h4>Auto-approval configuration</h4>' . PHP_EOL;

	foreach (
		array(
			'autoapprove'                           => 'Auto-approvals enabled',
			'autoapprove-php-nonfunctional-changes' => 'Non-functional changes auto-approved',
			'hashes-api'                            => 'Auto-approval DB enabled',
		) as $key => $value
	) {
		if (
			( false === $options_copy['autoapprove'] ) &&
			( 'autoapprove' !== $key )
		) {
			continue;
		}

		$details .= '<p>' . vipgoci_output_html_escape( $value ) . ':' . PHP_EOL;

		$details .= vipgoci_report_create_scan_details_list(
			'<code>',
			'</code>',
			$options_copy[ $key ],
			'None'
		);

		$details .= '</p>';
	}

	if ( true === $options_copy['autoapprove'] ) {
		$details .= '<p>Auto-approved file-types:</p>' . PHP_EOL;
		$details .= '<ul>' . PHP_EOL;

		$details .= vipgoci_report_create_scan_details_list(
			'<li><code>',
			'</code></li>',
			$options_copy['autoapprove-filetypes'],
			'<li>None</li>',
			''
		);

		$details .= '</ul>' . PHP_EOL;
	}

	return $details;
}

/**
 * Create scan report detail message.
 *
 * Information is either gathered or
 * based on $options_copy.
 *
 * @param array $options_copy Options needed.
 *
 * @return string Detail message.
 *
 * @codeCoverageIgnore
 */
function vipgoci_report_create_scan_details(
	array $options_copy
) :string {
	$details .= VIPGOCI_IRC_IGNORE_STRING_START . PHP_EOL;

	$details .= '<details>' . PHP_EOL;
	$details .= '<hr />' . PHP_EOL;
	$details .= '<summary>Scan run detail</summary>' . PHP_EOL;

	$details .= '<table>' . PHP_EOL;
	$details .= '<tr>' . PHP_EOL;

	$details .= '<td valign="top" width="40%">';
	$details .= vipgoci_report_create_scan_details_software_versions( $options_copy );
	$details .= '</td>' . PHP_EOL;

	$details .= '<td valign="top" width="30%">' . PHP_EOL;
	$details .= vipgoci_report_create_scan_details_php_lint_options( $options_copy );
	$details .= vipgoci_report_create_scan_details_svg_configuration( $options_copy );
	$details .= vipgoci_report_create_scan_details_auto_approve_configuration( $options_copy );
	$details .= '</td>' . PHP_EOL;

	$details .= '<td valign="top" width="30%">' . PHP_EOL;
	$details .= vipgoci_report_create_scan_details_phpcs_configuration( $options_copy );
	$details .= '</td>' . PHP_EOL;

	$details .= '</tr>' . PHP_EOL;
	$details .= '</table>' . PHP_EOL;

	$details .= '</details>' . PHP_EOL;

	$details .= VIPGOCI_IRC_IGNORE_STRING_END . PHP_EOL;

	return $details;
}

/**
 * Record if GitHub results have been submitted to a
 * pull request. Recording once that it has been done
 * will ensure it stays like that even if set to false later.
 * Will return the current state when called with $feedback_submitted
 * parameter as null. Default state, when nothing has
 * been recorded, is that nothing has been submitted.
 *
 * @param string    $repo_owner         Repository owner.
 * @param string    $repo_name          Repository name.
 * @param int       $pr_number          Pull request number.
 * @param null|bool $feedback_submitted Feedback submitted.
 *
 * @return bool True if feedback has been submitted at any time, else false.
 */
function vipgoci_report_feedback_to_github_was_submitted(
	string $repo_owner,
	string $repo_name,
	int $pr_number,
	null|bool $feedback_submitted = null
) {
	static $data_has_been_submitted = array();

	switch ( $feedback_submitted ) {
		case true:
			$data_has_been_submitted[ $repo_owner ][ $repo_name ][ $pr_number ] = true;
			break;

		case false:
		case null:
			if ( ! isset( $data_has_been_submitted[ $repo_owner ][ $repo_name ][ $pr_number ] ) ) {
				$data_has_been_submitted[ $repo_owner ][ $repo_name ][ $pr_number ] = false;
			}

			break;
	}

	return $data_has_been_submitted[ $repo_owner ][ $repo_name ][ $pr_number ];
}

/**
 * Checks if any results have been submitted, and if not,
 * submits a message about no issues having been found.
 *
 * @param string $repo_owner        Repository owner.
 * @param string $repo_name         Repository name.
 * @param string $github_token      GitHub access token to use.
 * @param string $commit_id         Commit-ID of current commit.
 * @param array  $prs_implicated    Pull requests implicated.
 * @param string $informational_msg Informational message for end-users.
 * @param string $scan_details_msg  Details of scan message for end-users.
 *
 * @return void
 */
function vipgoci_report_maybe_no_issues_found(
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id,
	array $prs_implicated,
	string $informational_msg,
	string $scan_details_msg
) :void {
	vipgoci_log(
		'Maybe posting a generic comment to PRs implicated about no issues found',
		array(
			'pr_numbers' => array_column(
				$prs_implicated,
				'number'
			),
		)
	);

	foreach ( $prs_implicated as $pr_item ) {
		if ( true === vipgoci_report_feedback_to_github_was_submitted(
			$repo_owner,
			$repo_name,
			$pr_item->number
		) ) {
			// Results were submitted, so do nothing.
			continue;
		}

		$pr_reviews_commented = vipgoci_github_pr_reviews_get(
			$repo_owner,
			$repo_name,
			$pr_item->number,
			$github_token,
			array(
				'login' => 'myself',
				'state' => array( 'COMMENTED', 'CHANGES_REQUESTED' ),
			)
		);

		if ( empty( $pr_reviews_commented ) ) {
			$no_issues_msg = VIPGOCI_NO_ISSUES_FOUND_MSG_AND_NO_REVIEWS;
		} else {
			$no_issues_msg = VIPGOCI_NO_ISSUES_FOUND_MSG_AND_EXISTING_REVIEWS;
		}

		$no_issues_msg .= ' (commit-ID: ' . $commit_id . ')';

		/*
		 * If we have informational message, append it.
		 */
		if ( ! empty( $informational_msg ) ) {
			$no_issues_msg .= PHP_EOL . PHP_EOL;

			vipgoci_markdown_comment_add_pagebreak(
				$no_issues_msg
			);

			$no_issues_msg .= $informational_msg . "\n\r";
		}

		/*
		 * Append scan details message if we have that
		 * along with a pagebreak.
		 */
		if ( ! empty( $scan_details_msg ) ) {
			$no_issues_msg .= PHP_EOL . PHP_EOL;

			vipgoci_markdown_comment_add_pagebreak(
				$no_issues_msg
			);

			$no_issues_msg .= $scan_details_msg;
		}

		vipgoci_github_pr_comments_generic_submit(
			$repo_owner,
			$repo_name,
			$github_token,
			$pr_item->number,
			$no_issues_msg,
			null // We include commit-ID manually.
		);
	}
}

/**
 * Submit generic PR comment to GitHub, reporting any
 * issues found within $results. Selectively report
 * issues that we are supposed to report on, ignore
 * others. Attempts to format the comment to GitHub.
 *
 * @param string $repo_owner        Repository owner.
 * @param string $repo_name         Repository name.
 * @param string $github_token      GitHub token to use to make GitHub API requests.
 * @param string $commit_id         Commit-ID of current commit.
 * @param array  $results           Results of scanning.
 * @param string $informational_msg Informational message for end-users.
 * @param string $scan_details_msg  Details of scan message for end-users.
 *
 * @return void
 */
function vipgoci_report_submit_pr_generic_comment_from_results(
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id,
	array $results,
	string $informational_msg,
	string $scan_details_msg
) :void {
	$stats_types_to_process = array(
		VIPGOCI_STATS_LINT,
	);

	vipgoci_log(
		'About to submit generic PR comment to GitHub about issues',
		array(
			'repo_owner' => $repo_owner,
			'repo_name'  => $repo_name,
			'commit_id'  => $commit_id,
			'results'    => $results,
		)
	);

	foreach (
		// The $results['issues'] array is keyed by pull request number.
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
			rawurlencode( (string) $pr_number ) . '/' .
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
				// Not an issue we process, ignore.
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

				// First in: level (error, warning).
				ucfirst(
					strtolower(
						$commit_issue['issue']['level']
					)
				) .

				'**' .

				': ' .

				// Then the message.
				str_replace(
					'\'',
					'`',
					$commit_issue['issue']['message']
				) .

				"\n\r\n\r" .

				// And finally an URL to the issue.
				VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
					$repo_owner . '/' .
					$repo_name . '/' .
					'blob/' .
					$commit_id . '/' .
					$commit_issue['file_name'] .
					'#L' . $commit_issue['file_line'] .

				"\n\r";
		}

		if ( '' === $github_postfields['body'] ) {
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
		 * Splice the two messages together,
		 * remove temporary variable.
		 */
		$github_postfields['body'] =
			$tmp_postfields_body .
			$github_postfields['body'];

		unset( $tmp_postfields_body );

		vipgoci_markdown_comment_add_pagebreak(
			$github_postfields['body']
		);

		/*
		 * If we have informational URL, append that
		 * and a generic message.
		 */
		if ( ! empty( $informational_msg ) ) {
			$github_postfields['body'] .=
				$informational_msg .
				"\n\r";
		}

		/*
		 * Append scan details
		 * message if we have that.
		 */
		if ( ! empty( $scan_details_msg ) ) {
			$github_postfields['body'] .= $scan_details_msg;
		}

		vipgoci_http_api_post_url(
			$github_url,
			$github_postfields,
			$github_token
		);

		vipgoci_report_feedback_to_github_was_submitted(
			$repo_owner,
			$repo_name,
			$pr_number,
			true
		);
	}
}

/**
 * Submit a review on GitHub for a particular commit,
 * and pull-request using the access-token provided.
 *
 * @param string $repo_owner                               Repository owner.
 * @param string $repo_name                                Repository name.
 * @param string $github_token                             GitHub token to use to make GitHub API requests.
 * @param string $commit_id                                Commit-ID of current commit.
 * @param array  $results                                  Results of scanning.
 * @param string $informational_msg                        Informational message for end-users.
 * @param string $scan_details_msg                         Details of scan message for end-users.
 * @param int    $github_review_comments_max               How many comments to submit in each GitHub review.
 * @param bool   $github_review_comments_include_severity  If to include severity in GitHub review comments.
 * @param int    $skip_large_files_limit                   The maximum number of lines of files we scan.
 *
 * @return void
 */
function vipgoci_report_submit_pr_review_from_results(
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id,
	array $results,
	string $informational_msg,
	string $scan_details_msg,
	int $github_review_comments_max,
	bool $github_review_comments_include_severity,
	int $skip_large_files_limit
) :void {
	$stats_types_to_process = array(
		VIPGOCI_STATS_PHPCS,
		VIPGOCI_STATS_HASHES_API,
	);

	vipgoci_log(
		'About to submit review and comment(s) to GitHub about issue(s)',
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
		// The $results array is keyed by pull request number.
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
			rawurlencode( (string) $pr_number ) . '/' .
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
				// Not an issue we process, ignore.
				continue;
			}

			/*
			 * Construct comment, append to array of comments.
			 */

			$github_postfields['comments'][] = array(
				'body'     =>

					// Add nice label.
					vipgoci_github_transform_to_emojis(
						$commit_issue['issue']['level']
					) . ' ' .

					'**' .

					// Level -- error, warning.
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

					// Then the message it self.
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
		 * Check if there are any previous comments about skipped files.
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

		$pr_reviews_commented = array_column(
			$pr_reviews_commented,
			'body'
		);

		$validation_message = vipgoci_skip_file_get_validation_message_prefix(
			VIPGOCI_VALIDATION_MAXIMUM_LINES,
			$skip_large_files_limit
		);

		$results[ VIPGOCI_SKIPPED_FILES ][ $pr_number ] = vipgoci_skip_file_check_previous_pr_comments(
			$results[ VIPGOCI_SKIPPED_FILES ][ $pr_number ],
			$pr_reviews_commented,
			$validation_message
		);

		/*
		 * If there are no issues to report to GitHub,
		 * do not continue processing the pull request.
		 * Our exit signal will indicate if anything is wrong.
		 */
		if (
			( false === $github_errors ) &&
			( false === $github_warnings ) &&
			( false === $github_info ) &&
			( empty( $results[ VIPGOCI_SKIPPED_FILES ][ $pr_number ]['issues']['max-lines'] ) )
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

				// Skipped.
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
				// Skipped.
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

		/*
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

		// Remove IRC constants from postfields body before submitting.
		$github_postfields['body'] = vipgoci_irc_api_clean_ignorable_constants(
			$github_postfields['body']
		);

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
			// Append a comment that there will be more reviews.
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

			// Actually send a request to GitHub.
			$github_post_res_tmp = vipgoci_http_api_post_url(
				$github_url,
				$github_postfields_tmp,
				$github_token
			);

			vipgoci_report_feedback_to_github_was_submitted(
				$repo_owner,
				$repo_name,
				$pr_number,
				true
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

			$tmp_comments_cnt = count( $github_postfields['comments'] );
		} while ( $tmp_comments_cnt > 0 );

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
}

/**
 * Post generic comment to each pull request
 * that has target branch that matches the
 * options given, but only if the same generic
 * comment has not been posted before. Uses a
 * comment given by one of the options.
 *
 * @param array $options        Options array for the program.
 * @param array $prs_implicated Pull requests implicated by current commit.
 *
 * @return void
 */
function vipgoci_report_submit_pr_generic_support_comment(
	array $options,
	array $prs_implicated
) :void {

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
			'Not posting support-comments on pull requests, as ' .
				'either not configured to do so, or ' .
				'incorrectly configured',
			$log_debugmsg
		);

		return;
	} else {
		vipgoci_log(
			'Posting support-comments on pull requests',
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
		 * has been added to the pull request.
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
				'Not submitting support-comment to pull request as it already exists',
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

/**
 * Submit generic comment to GitHub that scanning of certain files
 * failed.
 *
 * @param array  $options        Options array for the program.
 * @param array  $prs_implicated Pull requests implicated.
 * @param array  $files_failed   Files that could not be scanned.
 * @param string $message_start  Start of message to be submitted.
 * @param string $message_end    End of message to be submitted.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_report_submit_scanning_files_failed(
	array $options,
	array $prs_implicated,
	array $files_failed,
	string $message_start,
	string $message_end
) :void {
	$files_failed_linting_message =
		$message_start . PHP_EOL;

	foreach ( $files_failed as $failed_file_name ) {
		$files_failed_linting_message .=
			'* ' .
			vipgoci_output_html_escape( $failed_file_name ) .
			PHP_EOL;
	}

	$files_failed_linting_message .=
		PHP_EOL . $message_end;

	foreach ( $prs_implicated as $pr_item ) {
		vipgoci_github_pr_comments_generic_submit(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->number,
			$files_failed_linting_message,
			$options['commit']
		);
	}
}
