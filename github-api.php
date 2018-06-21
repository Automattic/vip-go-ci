<?php

/*
 * Client-ID for Github.
 */

define( 'VIPGOCI_CLIENT_ID', 'automattic-vip-go-ci' );
define( 'VIPGOCI_SYNTAX_ERROR_STR', 'PHP Syntax Errors Found' );
define( 'VIPGOCI_GITHUB_ERROR_STR', 'GitHub API communication error');

/*
 * This function works both to collect headers
 + when called as a callback function, and to return
 * the headers collected when called standalone.
 *
 * The difference is that the '$ch' argument is non-null
 * when called as a callback.
 */
function vipgoci_curl_headers( $ch, $header ) {
	static $resp_headers = array();

	if ( null === $ch ) {
		/*
		 * If $ch is null, we are being called to
		 * return whatever headers we have collected.
		 *
		 * Make sure to empty the headers collected.
		 */
		$ret = $resp_headers;
		$resp_headers = array();

		/*
		 * 'Fix' the status header before returning;
		 * we want the value to be an array such as:
		 * array(
		 *	0 => 201, // Status-code
		 *	1 => 'Created' // Status-string
		 * )
		 */
		if ( isset( $ret['status'] ) ) {
			$ret['status'] = explode(
				' ',
				$ret['status'][0]
			);
		}

		return $ret;
	}


	/*
	 * Turn the header into an array
	 */
	$header_len = strlen( $header );
	$header = explode( ':', $header, 2 );

	if ( count( $header ) < 2 ) {
		/*
		 * Should there be less than two values
		 * in the array, simply return, as the header is
		 * invalid.
		 */
		return $header_len;
	}


	/*
	 * Save the header as a key => value
	 * in our associative array.
	 */
	$key = strtolower( trim( $header[0] ) );

	if ( ! array_key_exists( $key, $resp_headers ) ) {
		$resp_headers[ $key ] = array();
	}

	$resp_headers[ $key ][] = trim(
		$header[1]
	);

	return $header_len;
}


/*
 * Detect if we exceeded the GitHub rate-limits,
 * and if so, exit with error.
 */

function vipgoci_github_rate_limits_check(
	$github_url,
	$resp_headers
) {
	if (
		( isset( $resp_headers['x-ratelimit-remaining'][0] ) ) &&
		( $resp_headers['x-ratelimit-remaining'][0] <= 1 )
	) {
		vipgoci_sysexit(
			'Ran out of request limits for GitHub, ' .
				'cannot continue without making ' .
				'making further requests.',
			array(
				'github_url' => $github_url,

				'x-ratelimit-remaining' =>
					$resp_headers['x-ratelimit-remaining'][0],

				'x-ratelimit-limit' =>
					$resp_headers['x-ratelimit-limit'][0],
			),
			VIPGOCI_EXIT_GITHUB_PROBLEM
		);
	}
}


/*
 * Ask GitHub for API rate-limit information and
 * report that back to the user.
 *
 * The results are not cached, as we want fresh data
 * every time.
 */

function vipgoci_github_rate_limit_usage(
	$github_token
) {
	$rate_limit = vipgoci_github_fetch_url(
		'https://api.github.com/rate_limit',
		$github_token
	);

	return json_decode(
		$rate_limit
	);
}

/*
 * Make sure to wait in between requests to
 * GitHub. Only waits if it is really needed.
 *
 * This function should only be called just before
 * sending a request to GitHub -- that is the most
 * effective usage.
 *
 * See here for background:
 * https://developer.github.com/v3/guides/best-practices-for-integrators/#dealing-with-abuse-rate-limits
 */

function vipgoci_github_wait() {
	static $last_request_time = null;

	vipgoci_runtime_measure( 'start', 'github_forced_wait' );

	if ( null !== $last_request_time ) {
		/*
		 * Only sleep if less than one second
		 * has elapsed from last request.
		 */
		if ( ( time() - $last_request_time ) < 1 ) {
			sleep( 1 );
		}
	}

	$last_request_time = time();

	vipgoci_runtime_measure( 'stop', 'github_forced_wait' );
}


/*
 * Send a POST/DELETE request to GitHub -- attempt
 * to retry if errors were encountered.
 *
 * Note that the '$http_delete' parameter will determine
 * if a POST or DELETE request will be sent.
 */

function vipgoci_github_post_url(
	$github_url,
	$github_postfields,
	$github_token,
	$http_delete = false
) {
	/*
	 * Actually send a request to GitHub -- make sure
	 * to retry if something fails.
	 */
	do {
		/*
		 * By default, assume request went through okay.
		 */

		$ret_val = 0;

		/*
		 * By default, do not retry the request,
		 * just assume everything goes well
		 */

		$retry_req = false;

		/*
		 * Initialize and send request.
		 */

		$ch = curl_init();

		curl_setopt(
			$ch, CURLOPT_URL, $github_url
		);

		curl_setopt(
			$ch, CURLOPT_RETURNTRANSFER, 1
		);

		curl_setopt(
			$ch, CURLOPT_CONNECTTIMEOUT, 20
		);

		curl_setopt(
			$ch, CURLOPT_USERAGENT,	VIPGOCI_CLIENT_ID
		);

		if ( false === $http_delete ) {
			curl_setopt(
				$ch, CURLOPT_POST, 1
			);
		}

		else {
			curl_setopt(
				$ch, CURLOPT_CUSTOMREQUEST, 'DELETE'
			);
		}

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $github_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_token )
		);

		// Make sure to pause between GitHub-requests
		vipgoci_github_wait();

		/*
		 * Execute query to GitHub, keep
		 * record of how long time it took,
		 * and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( 'start', 'github_api' );

		vipgoci_counter_report( 'do', 'github_api_request', 1 );

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( 'stop', 'github_api' );


		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);


		/*
		 * Allow certain statuses, depending on type of request
		 */
		if (
			(
				( false === $http_delete ) &&
				( intval( $resp_headers['status'][0] ) !== 200 ) &&
				( intval( $resp_headers['status'][0] ) !== 201 )
			)

			||

			(
				( true === $http_delete ) &&
				( intval( $resp_headers['status'][0] ) !== 204 ) &&
				( intval( $resp_headers['status'][0] ) !== 200 )
			)
		) {
			/*
			 * Set default wait period between requests
			 */
			$retry_sleep = 10;

			/*
			 * Set error-return value
			 */
			$ret_val = -1;

			/*
			 * Figure out if to retry...
			 */

			// Decode JSON
			$resp_data = json_decode( $resp_data );

			if (
				( isset(
					$resp_headers['retry-after']
				) ) &&
				( intval(
					$resp_headers['retry-after']
				) > 0 )
			) {
				$retry_req = true;
				$retry_sleep = intval(
					$resp_headers['retry-after']
				);
			}

			else if (
				( $resp_data->message ==
					'Validation Failed' ) &&

				( $resp_data->errors[0] ==
					'was submitted too quickly ' .
					'after a previous comment' )
			) {
				/*
				 * These messages are due to the
				 * submission being categorized
				 * as a spam by GitHub -- no good
				 * reason to retry, really.
				 */
				$retry_req = false;
				$retry_sleep = 20;
			}

			else if (
				( $resp_data->message ==
					'Validation Failed' )
			) {
				$retry_req = false;
			}

			else if (
				( $resp_data->message ==
					'Server Error' )
			) {
				$retry_req = false;
			}

			vipgoci_log(
				'GitHub reported an error' .
					( $retry_req === true ?
					' will retry request in ' .
					$retry_sleep . ' seconds' :
					'' ),
				array(
					'http_url'
						=> $github_url,

					'http_response_headers'
						=> $resp_headers,

					'http_reponse_body'
						=> $resp_data,
				)
			);

			sleep( $retry_sleep + 1 );
		}

		vipgoci_github_rate_limits_check(
			$github_url,
			$resp_headers
		);


		curl_close( $ch );

	} while ( $retry_req == true );

	return $ret_val;
}


/*
 * Make a GET request to GitHub, for the URL
 * provided, using the access-token specified.
 *
 * Will return the raw-data returned by GitHub,
 * or halt execution on repeated errors.
 */
function vipgoci_github_fetch_url(
	$github_url,
	$github_token
) {

	$curl_retries = 0;

	/*
	 * Attempt to send request -- retry if
	 * it fails.
	 */
	do {
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL,			$github_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER,	1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT,	20 );

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_token )
		);

		// Make sure to pause between GitHub-requests
		vipgoci_github_wait();


		/*
		 * Execute query to GitHub, keep
		 * record of how long time it took,
		 + and also keep count of how many we do.
		 */
		vipgoci_runtime_measure( 'start', 'github_api' );

		vipgoci_counter_report( 'do', 'github_api_request', 1 );

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( 'stop', 'github_api' );


		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);


		/*
		 * Detect and process possible errors
		 */
		if (
			( false === $resp_data ) ||
			( curl_errno( $ch ) )
		) {
			vipgoci_log(
				'Sending request to GitHub failed, will ' .
					'retry in a bit... ',

				array(
					'github_url' => $github_url,
					'curl_retries' => $curl_retries,

					'curl_errno' => curl_errno(
						$ch
					),

					'curl_errormsg' => curl_strerror(
						curl_errno( $ch )
					),
				)
			);

			sleep( 10 );
		}


		vipgoci_github_rate_limits_check(
			$github_url,
			$resp_headers
		);

		curl_close( $ch );

	} while (
		( false === $resp_data ) &&
		( $curl_retries++ < 2 )
	);


	if ( false === $resp_data ) {
		vipgoci_sysexit(
			'Gave up retrying request to GitHub, cannot continue',
			array(),
			VIPGOCI_EXIT_GITHUB_PROBLEM
		);
	}

	return $resp_data;
}

/*
 * Fetch information from GitHub on a particular
 * commit within a particular repository, using
 * the access-token given.
 *
 * Will return the JSON-decoded data provided
 * by GitHub on success.
 */
function vipgoci_github_fetch_commit_info(
	$repo_owner,
	$repo_name,
	$commit_id,
	$github_token,
	$filter = null
) {
	/* Check for cached version */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_id, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );


	vipgoci_log(
		'Fetching commit info from GitHub' .
			( $cached_data ? ' (cached)' : '' ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'filter' => $filter,
		)
	);


	if ( false === $cached_data ) {

		/*
		 * Nothing cached, attempt to
		 * fetch from GitHub.
		 */

		$github_url =
			'https://api.github.com/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'commits/' .
			rawurlencode( $commit_id );

		$data = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);


		if (
			( isset( $data->message ) ) &&
			( 'Not Found' === $data->message )
		) {
			vipgoci_sysexit(
				'Unable to fetch commit-info from GitHub, ' .
					'the commit does not exist.',
				array(
					'error_data' => $data
				),
				VIPGOCI_EXIT_GITHUB_PROBLEM
			);
		}

		// Cache the results
		vipgoci_cache(
			$cached_id,
			$data
		);
	}

	else {
		$data = $cached_data;
	}

	/*
	 * Filter array of files based on
	 * parameter -- i.e., files
	 * that the commit implicates, and
	 * GitHub hands over to us.
	 */

	if ( null !== $filter ) {
		$files_new = array();

		foreach( $data->files as $file_info ) {
			/*
			 * If the file does not have an acceptable
			 * file-extension, skip
			 */

			if ( false === vipgoci_filter_file_path(
				$file_info->filename,
				$filter
			) ) {
				continue;
			}


			/*
			 * Process status based on filter.
			 */

			if (
				! in_array(
					$file_info->status,
					$filter['status']
				)
			) {

				vipgoci_log(
					'Skipping file that does not have a  ' .
						'matching modification status',

					array(
						'filename'	=>
							$file_info->filename,

						'status'	=>
							$file_info->status,

						'filter_status' =>
							$filter['status'],
					),
					1
				);

				continue;
			}

			$files_new[] = $file_info;
		}

		$data->files = $files_new;
	}

	return $data;
}


/*
 * Fetch all comments made on GitHub for the
 * repository and commit specified -- but are
 * still associated with a Pull Request.
 *
 * Will return an associative array of comments,
 * with file-name and file-line number as keys. Will
 * return false on an error.
 */
function vipgoci_github_pr_reviews_comments_get(
	&$prs_comments,
	$repo_owner,
	$repo_name,
	$commit_id,
	$commit_made_at,
	$github_token
) {

	/*
	 * Try to get comments from cache
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_made_at, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching Pull-Requests comments info from GitHub' .
			( $cached_data ? ' (cached)' : '' ),

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'commit_made_at' => $commit_made_at,
		)
	);


	if ( false !== $cached_data ) {
		$prs_comments_cache = $cached_data;
	}

	else {
		/*
		 * Nothing in cache, ask GitHub.
		 */

		$page = 1;
		$per_page = 100;
		$prs_comments_cache = array();

		/*
		 * FIXME:
		 *
		 * Asking for all the pages from GitHub
		 * might get expensive as we process more
		 * commits/hour -- maybe cache this in memcache,
		 * making it possible to share data between processes.
		 */

		do {
			$github_url =
				'https://api.github.com/' .
				'repos/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'pulls/' .
				'comments?' .
				'sort=created&' .
				'direction=asc&' .
				'since=' . rawurlencode( $commit_made_at ) . '&' .
				'page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );

			// FIXME: Detect when GitHub returned with an error
			$prs_comments_tmp = json_decode(
				vipgoci_github_fetch_url(
					$github_url,
					$github_token
				)
			);

			foreach ( $prs_comments_tmp as $pr_comment ) {
				$prs_comments_cache[] = $pr_comment;
			}

			$page++;
		} while ( count( $prs_comments_tmp ) >= $per_page );

		vipgoci_cache( $cached_id, $prs_comments_cache );
	}


	foreach ( $prs_comments_cache as $pr_comment ) {
		if ( null === $pr_comment->position ) {
			/*
			 * If no line-number was provided,
			 * ignore the comment.
			 */
			continue;
		}

		if ( $commit_id !== $pr_comment->original_commit_id ) {
			/*
			 * If commit_id on comment does not match
			 * current one, skip the comment.
			 */
			continue;
		}

		/*
		 * Look through each comment, create an associative array
		 * of file:position out of all the comments, so any comment
		 * can easily be found.
		 */

		$prs_comments[
			$pr_comment->path . ':' .
			$pr_comment->position
		][] = $pr_comment;
	}
}


/*
 * Get all generic comments made to a Pull-Request from Github.
 */

function vipgoci_github_pr_generic_comments_get(
	$repo_owner,
	$repo_name,
	$pr_number,
	$github_token
) {
	/*
	 * Try to get comments from cache
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$pr_number, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching Pull-Requests generic comments from GitHub' .
			( $cached_data ? ' (cached)' : '' ),

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}


	/*
	 * Nothing in cache, ask GitHub.
	 */

	$pr_comments_ret = array();

	$page = 1;
	$per_page = 100;

	do {
		$github_url =
			'https://api.github.com/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( $pr_number ) . '/' .
			'comments' .
			'?page=' . rawurlencode( $page ) . '&' .
			'per_page=' . rawurlencode( $per_page );


		$pr_comments_raw = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);

		foreach ( $pr_comments_raw as $pr_comment ) {
			$pr_comments_ret[] = $pr_comment;
		}

		$page++;
	} while ( count( $pr_comments_raw ) >= $per_page );


	vipgoci_cache(
		$cached_id,
		$pr_comments_ret
	);

	return $pr_comments_ret;
}

/*
 * Submit generic PR comment to GitHub, reporting any
 * issues found within $results. Selectively report
 * issues that we are supposed to report on, ignore
 * others. Attempts to format the comment to GitHub.
 */

function vipgoci_github_pr_generic_comment_submit(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$results,
	$dry_run
) {
	$stats_types_to_process = array(
		'lint',
	);


	vipgoci_log(
		( $dry_run == true ? 'Would ' : 'About to ' ) .
		'submit generic PR comment to GitHub about issues',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'results' => $results,
			'dry_run' => $dry_run,
		)
	);


	/* If dry-run is enabled, do nothing further. */
	if ( $dry_run == true ) {
		return;
	}


	foreach (
		// The $results['issues'] array is keyed by Pull-Request number
		array_keys(
			$results['issues']
		) as $pr_number
	) {
		$github_url =
			'https://api.github.com/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( $pr_number ) . '/' .
			'comments';


		$github_postfields = array(
			'body' => ''
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
			}

			else {
				$github_postfields['body'] .= "\n\r***\n\r";
			}


			/*
			 * Construct comment -- (start or continue)
			 */
			$github_postfields['body'] .=
				'**' .

				// First in: level (error, warning)
				ucfirst( strtolower(
					$commit_issue['issue']['level']
				) ) .

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
				'https://github.com/' .
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

		$github_postfields['body'] =
			'**' . VIPGOCI_SYNTAX_ERROR_STR . '**' .
			"\n\r\n\r" .

			"Scan performed on the code at commit " . $commit_id .
				" ([view code](https://github.com/" .
				rawurlencode( $repo_owner ) . "/" .
				rawurlencode( $repo_name ) . "/" .
				"tree/" .
				rawurlencode( $commit_id ) .
				"))." .
				"\n\r***\n\r" .

			// Splice the body constructed earlier
			$github_postfields['body'];

		vipgoci_github_post_url(
			$github_url,
			$github_postfields,
			$github_token
		);
	}
}

/*
 * Post a generic PR comment to GitHub, reporting
 * an error.
 */
function vipgoci_github_pr_comments_error_msg(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$pr_number,
	$message
) {
	vipgoci_log(
		'GitHub reported a failure, posting a ' .
			'comment about this to the Pull-Request',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'pr_number' => $pr_number,
			'message' => $message,
		)
	);

	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( $pr_number ) . '/' .
		'comments';


	$github_postfields = array();
	$github_postfields['body'] =
		'**' . VIPGOCI_GITHUB_ERROR_STR . '**' .
		"\n\r\n\r" .

		$message .
			" (commit-ID: " . $commit_id . ")" .
			"\n\r***\n\r";

	vipgoci_github_post_url(
		$github_url,
		$github_postfields,
		$github_token
	);
}

/*
 * Remove any comments made by us earlier.
 */

function vipgoci_github_pr_comments_cleanup(
	$repo_owner,
	$repo_name,
	$commit_id,
	$github_token,
	$branches_ignore,
	$dry_run
) {
	vipgoci_log(
		( $dry_run == true ? 'Would ' : 'About to ' ) .
		'clean up generic PR comments on Github',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'branches_ignore' => $branches_ignore,
			'dry_run' => $dry_run,
		)
	);

	/* Get info about token-holder */
	$current_user_info = vipgoci_github_authenticated_user_get(
		$github_token
	);


	/* If dry-run is enabled, do nothing further. */
	if ( $dry_run == true ) {
		return;
	}

	$prs_implicated = vipgoci_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		$branches_ignore
	);

	foreach ( $prs_implicated as $pr_item ) {
		$pr_comments = vipgoci_github_pr_generic_comments_get(
			$repo_owner,
			$repo_name,
			$pr_item->number,
			$github_token
		);

		foreach ( $pr_comments as $pr_comment ) {

			if ( $pr_comment->user->login !== $current_user_info->login ) {
				// Do not delete other person's comment
				continue;
			}


			/*
			 * Check if the comment is actually
			 * a feedback generated by vip-go-ci -- we might
			 * be run as on a shared account, with comments
			 * being generated by other programs, and we do
			 * not want to remove those. Avoid that.
			 */

			if (
				( strpos(
					$pr_comment->body,
					VIPGOCI_SYNTAX_ERROR_STR
				) === false )
				&&
				( strpos(
					$pr_comment->body,
					VIPGOCI_GITHUB_ERROR_STR
				) === false )
			) {
				continue;
			}

			// Actually delete the comment
			vipgoci_github_pr_generic_comment_delete(
				$repo_owner,
				$repo_name,
				$github_token,
				$pr_comment->id
			);
		}
	}
}


/*
 * Delete generic comment made to Pull-Request.
 */

function vipgoci_github_pr_generic_comment_delete(
	$repo_owner,
	$repo_name,
	$github_token,
	$comment_id
) {
	vipgoci_log(
		'About to remove generic PR comment on Github',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'comment_id' => $comment_id,
		),
		1
	);


	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		'comments/' .
		rawurlencode( $comment_id );

	/*
	 * Send DELETE request to GitHub.
	 */
	vipgoci_github_post_url(
		$github_url,
		array(),
		$github_token,
		true
	);
}


/*
 * Submit a review on GitHub for a particular commit,
 * and pull-request using the access-token provided.
 */
function vipgoci_github_pr_review_submit(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$results,
	$dry_run,
	$github_review_comments_max
) {

	$stats_types_to_process = array(
		'phpcs',
	);

	vipgoci_log(
		( $dry_run == true ? 'Would ' : 'About to ' ) .
		'submit comment(s) to GitHub about issue(s)',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'results' => $results,
			'dry_run' => $dry_run,
		)
	);


	/* If dry-run is enabled, do nothing further. */
	if ( $dry_run == true ) {
		return;
	}

	foreach (
		// The $results array is keyed by Pull-Request number
		array_keys(
			$results['issues']
		) as $pr_number
	) {

		$github_url =
			'https://api.github.com/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls/' .
			rawurlencode( $pr_number ) . '/' .
			'reviews';


		$github_postfields = array(
			'commit_id'	=> $commit_id,
			'body'		=> '',
			'event'		=> '',
			'comments'	=> array(),
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
				'body'		=>

					// Add nice label
					vipgoci_github_labels(
						$commit_issue['issue']['level']
					) . ' ' .


					'**' .

					// Level -- error, warning
					ucfirst( strtolower(
						$commit_issue['issue']['level']
						)) .
					'**: ' .

					// Then the message it self
					htmlentities(
						$commit_issue['issue']['message']
					),

				'position'	=> $commit_issue['file_line'],
				'path'		=> $commit_issue['file_name']
			);
		}


		/*
		 * Figure out what to report to GitHub.
		 *
		 * If there are any 'error'-level issues, make sure the submission
		 * asks for changes to be made, otherwise only comment.
		 *
		 * If there are no issues at all -- warning or error -- do not
		 * submit anything.
		 */

		$github_postfields['event'] = 'COMMENT';

		$github_errors = false;
		$github_warnings = false;

		foreach (
			$stats_types_to_process as
				$stats_type
		) {
			if ( ! empty(
				$results['stats']
					[ $stats_type ][ $pr_number ]['error']
			) ) {
				$github_postfields['event'] = 'REQUEST_CHANGES';
				$github_errors = true;
			}

			if ( ! empty(
				$results['stats']
					[ $stats_type ][ $pr_number ]['warning']
			) ) {
				$github_warnings = true;
			}
		}


		/*
		 * If there are no issues to report to GitHub,
		 * do not continue processing the Pull-Request.
		 * Our exit signal will indicate if anything is wrong.
		 */
		if (
			( false === $github_errors ) &&
			( false === $github_warnings )
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
				$github_postfields['body'] .= '***' . "\n\r";
			}


			/*
			 * Check if this type of scanning
			 * was skipped, and if so, note it.
			 */

			if ( empty(
				$results
					['stats']
					[ strtolower( $stats_type ) ]
			) ) {
				$github_postfields['body'] .=
					'**' . $stats_type . '**' .
						"-scanning skipped\n\r";

				// Skipped
				continue;
			}


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
				$github_postfields['body'] .=
					vipgoci_github_labels(
						$commit_issue_stat_key
					) . ' ' .

					$commit_issue_stat_value . ' ' .
					$commit_issue_stat_key . '(s) ' .
					"\n\r";
			}
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
			vipgoci_github_pr_comments_error_msg(
				$repo_owner,
				$repo_name,
				$github_token,
				$commit_id,
				$pr_number,
				'Error while communicating to the GitHub ' .
					'API. Please contact a human.'
			);
		}
	}

	return;
}


/*
 * Approve a Pull-Request, and afterwards
 * make sure to verify that the latest commit
 * added to the Pull-Request is commit with
 * commit-ID $latest_commit_id -- this is to avoid
 * race-conditions.
 *
 * The race-conditions can occur when a Pull-Request
 * is approved, but it is approved after a new commit
 * was added, but that has not been scanned.
 */

function vipgoci_github_approve_pr(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$latest_commit_id,
	$filetypes_approve,
	$dry_run
) {


	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'pulls/' .
		rawurlencode( $pr_number ) . '/' .
		'reviews';

	$github_postfields = array(
		'commit_id' => $latest_commit_id,
		'body' => 'Auto-approved Pull-Request #' .
			(int) $pr_number . ' as it ' .
			'contains only allowable file-types ' .
			'(' . implode( ', ', $filetypes_approve ) . ')',
		'event' => 'APPROVE',
		'comments' => array()
	);

	if ( true === $dry_run ) {
		return;
	}

	// Actually approve
	vipgoci_github_post_url(
		$github_url,
		$github_postfields,
		$github_token
	);

	// FIXME: Approve PR, then make sure
	// the latest commit in the PR is actually
	// the one provided in $latest_commit_id
}


/*
 * Get Pull Requests which are open currently
 * and the commit is a part of. Make sure to ignore
 * certain branches specified in a parameter.
 */

function vipgoci_github_prs_implicated(
	$repo_owner,
	$repo_name,
	$commit_id,
	$github_token,
	$branches_ignore
) {

	/*
	 * Check for cached copy
	 */

	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_id, $github_token, $branches_ignore
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching all open Pull-Requests from GitHub' .
			( $cached_data ? ' (cached)' : '' ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'branches_ignore' => $branches_ignore,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}


	/*
	 * Nothing cached; ask GitHub.
	 */

	$prs_implicated = array();


	$page = 1;
	$per_page = 100;

	/*
	 * Fetch all open Pull-Requests, store
	 * PR IDs that have a commit-head that matches
	 * the one we are working on.
	 */
	do {
		$github_url =
			'https://api.github.com/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls' .
			'?state=open&' .
			'page=' . rawurlencode( $page ) . '&' .
			'per_page=' . rawurlencode( $per_page );


		// FIXME: Detect when GitHub sent back an error
		$prs_implicated_unfiltered = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);


		foreach ( $prs_implicated_unfiltered as $pr_item ) {
			if ( ! isset( $pr_item->head->ref ) ) {
				continue;
			}

			/*
			 * If the branch this Pull-Request is associated
			 * with is one of those we are supposed to ignore,
			 * then ignore it.
			 */
			if ( in_array(
				$pr_item->head->ref,
				$branches_ignore
			) ) {
				continue;
			}


			/*
			 * If the commit we are processing currently
			 * matches the head-commit of the Pull-Request,
			 * then the Pull-Request should be considered to
			 * be relevant.
			 */
			if ( $commit_id === $pr_item->head->sha ) {
				$prs_implicated[ $pr_item->number ] = $pr_item;
			}
		}

		sleep ( 2 );

		$page++;
	} while ( count( $prs_implicated_unfiltered ) >= $per_page );


	/*
	 * Convert number parameter of each object
	 * saved to an integer.
	 */

	foreach(
		array_keys( $prs_implicated ) as
			$pr_implicated
	) {
		if ( isset( $pr_implicated->number ) ) {
			$prs_implicated[ $pr_implicated->number ]->number =
				(int) $pr_implicated->number;
		}
	}

	vipgoci_cache( $cached_id, $prs_implicated );

	return $prs_implicated;
}


/*
 * Get all commits that are a part of a Pull-Request.
 */

function vipgoci_github_prs_commits_list(
	$repo_owner,
	$repo_name,
	$pr_number,
	$github_token
) {

	/*
	 * Check for cached copy
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$pr_number, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );


	vipgoci_log(
		'Fetching information about all commits made' .
			' to Pull-Request #' .
			(int) $pr_number . ' from GitHub' .
			( $cached_data ? ' (cached)' : '' ),

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	/*
	 * Nothing in cache; ask GitHub.
	 */

	$pr_commits = array();


	$page = 1;
	$per_page = 100;

	do {
		$github_url =
			'https://api.github.com/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls/' .
			rawurlencode( $pr_number ) . '/' .
			'commits?' .
			'page=' . rawurlencode( $page ) . '&' .
			'per_page=' . rawurlencode( $per_page );


		// FIXME: Detect when GitHub sent back an error
		$pr_commits_raw = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);

		foreach ( $pr_commits_raw as $pr_commit ) {
			$pr_commits[] = $pr_commit->sha;
		}

		$page++;
	} while ( count( $pr_commits_raw ) >= $per_page );

	vipgoci_cache( $cached_id, $pr_commits );

	return $pr_commits;
}

/*
 * Fetch all files that were changed as a part
 * of a particular Pull-Request. Allow filtering
 * of file-endings according to $filter.
 */
function vipgoci_github_pr_files_changed(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_base_sha,
	$current_commit_id,
	$filter
) {

	$files_changed = vipgoci_github_diffs_fetch(
		$repo_owner,
		$repo_name,
		$github_token,
		$pr_base_sha,
		$current_commit_id
	);

	$files_changed_ret = array();

	foreach ( $files_changed as $file_name => $tmp_patch ) {
		if (
			( null !== $filter ) &&
			( false === vipgoci_filter_file_path(
				$file_name,
				$filter
			) )
		) {
			continue;
		}

		$files_changed_ret[] = $file_name;
	}

	return $files_changed_ret;
}

/*
 * Fetch diffs between two commits.
 */
function vipgoci_github_diffs_fetch(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id_a,
	$commit_id_b
) {

	/*
	 * Check for a cached copy of the diffs
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$github_token, $commit_id_a, $commit_id_b
	);

	$cached_data = vipgoci_cache( $cached_id );


	vipgoci_log(
		'Fetching diffs between two commits ' .
			'from GitHub' .
			( $cached_data ? ' (cached)' : '' ),

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id_a' => $commit_id_a,
			'commit_id_b' => $commit_id_b,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}


	/*
	 * Nothing cached; ask GitHub.
	 */

	// FIXME: Use local git-repo for this, if possible.
	$diffs = array();

	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'compare/' .
		rawurlencode( $commit_id_a ) .
			'...' .
			rawurlencode( $commit_id_b );

	// FIXME: Error-handling
	$resp_raw = json_decode(
		vipgoci_github_fetch_url(
			$github_url,
			$github_token
		)
	);

	/*
	 * Loop through all files, save patch in an array
	 */
	foreach( $resp_raw->files as $file_item ) {
		if ( ! isset( $file_item->patch ) ) {
			continue;
		}

		$diffs[ $file_item->filename ] = $file_item->patch;
	}

	vipgoci_cache( $cached_id, $diffs );

	return $diffs;
}


/*
 * Get information from GitHub on the user
 * authenticated.
 */
function vipgoci_github_authenticated_user_get( $github_token ) {
	$cached_id = array(
		__FUNCTION__, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Trying to get information about the user the GitHub-token belongs to' .
			( $cached_data ? ' (cached)' : '' ),
		array(
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}


	$github_url =
		'https://api.github.com/' .
		'user';

	$current_user_info = json_decode(
		vipgoci_github_fetch_url(
			$github_url,
			$github_token
		)
	);

	vipgoci_cache( $cached_id, $current_user_info );

	return $current_user_info;
}


/*
 * Add a particular label to a specific
 * Pull-Request (or issue).
 */
function vipgoci_github_label_add_to_pr(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_name,
	$dry_run
) {
	vipgoci_log(
		( $dry_run === true ? 'Would add ' : 'Adding ' ) .
		'label to GitHub issue',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'label_name' => $label_name,
		)
	);

	if ( true === $dry_run ) {
		return;
	}

	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( $pr_number ) . '/' .
		'labels';

	$github_postfields = array(
		$label_name
	);

	vipgoci_github_post_url(
		$github_url,
		$github_postfields,
		$github_token
	);
}

/*
 * Fetch labels associated with a
 * particular issue/Pull-Request.
 */
function vipgoci_github_labels_get(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_to_look_for = null
) {
	vipgoci_log(
		'Getting labels associated with GitHub issue',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
		)
	);
	/*
	 * Check first if we have
	 * got the information cached
	 */
	$cache_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
                $github_token, $pr_number, $label_to_look_for
	);

	$cached_data = vipgoci_cache( $cache_id );


	/*
	 * If there is nothing cached, fetch it
	 * from GitHub.
	 */
	if ( false === $cached_data ) {
		$github_url =
			'https://api.github.com/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( $pr_number ) . '/' .
			'labels';

		$data = vipgoci_github_fetch_url(
			$github_url,
			$github_token
		);

		$data = json_decode( $data );

		vipgoci_cache( $cache_id, $data );
	}

	else {
		$data = $cached_data;
	}

	/*
	 * We got something -- validate it.
	 */

	if ( empty( $data ) ) {
		return false;
	}

	else if ( ( ! empty( $data ) ) && ( null !== $label_to_look_for ) ) {
		/*
		 * Decoding of data succeeded,
		 * look for any labels and return
		 * them specifically
		 */
		foreach( $data as $data_item ) {
			if ( $data_item->name === $label_to_look_for ) {
				return $data_item;
			}
		}

		return false;
	}

	return $data;
}


/*
 * Remove a particular label from a specific
 * Pull-Request (or issue).
 */
function vipgoci_github_label_remove_from_pr(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_name,
	$dry_run
) {
	vipgoci_log(
		( $dry_run === true ? 'Would remove ' : 'Removing ' ) .
		'label from GitHub issue',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'label_name' => $label_name,
		)
	);

	if ( true === $dry_run ) {
		return;
	}

	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( $pr_number ) . '/' .
		'labels/' .
		rawurlencode( $label_name );

	vipgoci_github_post_url(
		$github_url,
		array(),
		$github_token,
		true // DELETE request will be sent
	);
}
