<?php

define( 'VIPGOCI_PHPCS_CLIENT_ID', 'automattic-github-review-client' );


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

	if ( null !== $last_request_time ) {
		/*
		 * Only sleep if four or less seconds
		 * have elapsed from last request.
		 */
		if ( ( time() - $last_request_time ) <= 4 ) {
			sleep( 4 );
		}
	}

	$last_request_time = time();
}


/*
 * Send a POST request to GitHub -- attempt
 * to retry if errors were encountered.
 */

function vipgoci_github_post_url(
	$github_url,
	$github_postfields,
	$github_token
) {
	/*
	 * Actually send a request to GitHub -- make sure
	 * to retry if something fails.
	 */
	do {
		/*
		 * By default, do not retry the request,
		 * just assume everything goes well
		 */

		$retry_req = false;

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
			$ch, CURLOPT_USERAGENT,	VIPGOCI_PHPCS_CLIENT_ID
		);

		curl_setopt(
			$ch, CURLOPT_POST, 1
		);

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


		$resp_data = curl_exec( $ch );

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);



		if ( intval( $resp_headers['status'][0] ) !== 200 ) {
			/*
			 * Set default wait period between requests
			 */
			$retry_sleep = 10;


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

			vipgoci_log(
				'GitHub reported an error' .
					( $retry_req === true ?
					' will retry request in ' .
					$retry_sleep . ' seconds' :
					'' ),
				array(
					'http_response_headers'
						=> $resp_headers,

					'http_reponse_body'
						=> $resp_data,
				)
			);

			sleep( $retry_sleep + 1 );
		}

		curl_close( $ch );

	} while ( $retry_req == true );
}


/*
 * Make a GET request to GitHub, for the URL
 * provided, using the access-token specified.
 *
 * Will return the raw-data returned by GitHub,
 * or halt execution on repeated errors.
 */
function vipgoci_github_fetch_url(
	$github_url, $github_token
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
			VIPGOCI_PHPCS_CLIENT_ID
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_token )
		);

		// Make sure to pause between GitHub-requests
		vipgoci_github_wait();

		$resp_data = curl_exec( $ch );

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

		curl_close( $ch );

	} while (
		( false === $resp_data ) &&
		( $curl_retries++ < 2 )
	);


	if ( false === $resp_data ) {
		vipgoci_log(
			'Gave up, cannot continue',
			array()
		);

		exit( 254 );
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
		$commit_id, $github_token, $filter
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


	if ( false !== $cached_data ) {
		return $cached_data;
	}

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
		vipgoci_log(
			'Unable to fetch commit-info from GitHub, the ' .
				'commit does not exist.',
			array(
				'error_data' => $data
			)
		);

		exit( 254 );
	}

	/*
	 * Filter out files based on
	 * parameter
	 */

	if ( null !== $filter ) {
		$files_new = array();

		foreach( $data->files as $file_info ) {
			$file_info_extension = pathinfo(
				$file_info->filename,
				PATHINFO_EXTENSION
			);

			/*
			 * If the file does not have an acceptable
			 * file-extension, skip
			 */

			if (
				( is_array( $file_info_extension ) ) &&
				( ! in_array(
					strtolower( $file_info_extension ),
					$filter['file_extensions'],
					true
				) )
			) {
				vipgoci_log(
					'Skipping file that does not seem ' .
						'to be a file matching ' .
						'filter-criteria',

					array(
						'filename' =>
							$file_info->filename,

						'allowable_file_extensions' =>
							$filter['file_extensions'],
					)
				);

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
					)
				);

				continue;
			}

			$files_new[] = $file_info;
		}

		$data->files = $files_new;
	}

	vipgoci_cache(
		$cached_id,
		$data
	);

	return $data;
}


/*
 * Fetch from GitHub a particular file which is a part of a
 * commit, within a particular repository. Will return
 * the file (raw), or false on error.
 *
 * If possible, the function will first try to use a local repository
 * to do the same thing, bypassing GitHub altogether, but if it fails,
 * reverting to GitHub.
 */

function vipgoci_github_fetch_committed_file(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$file_name,
	$local_git_repo
) {

	static $local_git_repo_failure = false;

	/*
	 * Try a local Git-repository first,
	 * if that fails, ask GitHub.
	 */
	if (
		( null !== $local_git_repo ) &&
		( false == $local_git_repo_failure )
	) {
		vipgoci_log(
			'Fetching file-contents from local Git repository',
			array(
				'repo_owner'		=> $repo_owner,
				'repo_name'		=> $repo_name,
				'commit_id'		=> $commit_id,
				'filename'		=> $file_name,
				'local_git_repo'	=> $local_git_repo,
			)
		);


		/*
		 * Check at what revision the local git repository is.
		 *
		 * We do this to make sure the local repository
		 * is actually checked out at the same commit
		 * as the one we are working with.
		 */
		$lgit_head = @file_get_contents(
			$local_git_repo . '/.git/HEAD'
		);

		$lgit_branch_ref = false;

		$file_contents_tmp = false;

		/*
		 * Check if we successfully got any information
		 */

		if ( false !== $lgit_head ) {
			// We might have gotten a reference, work with that
			if ( strpos( $lgit_head, 'ref: ') === 0 ) {
				$lgit_branch_ref = substr(
					$lgit_head,
					5
				);

				$lgit_branch_ref = rtrim(
					$lgit_branch_ref
				);

				$lgit_head = false;
			}
		}


		/*
		 * If we have not established a head,
		 * but we have a reference, try to get the
		 * head
		 */
		if (
			( false === $lgit_head ) &&
			( false !== $lgit_branch_ref )
		) {
			$lgit_head = @file_get_contents(
				$local_git_repo . '/.git/' . $lgit_branch_ref
			);

			$lgit_head = rtrim(
				$lgit_head
			);

			$lgit_branch_ref = false;
		}


		/*
		 * Check if commit-ID and head are the same, and
		 * only then try to fetch the requested file from the repo
		 */

		if (
			( false !== $commit_id ) &&
			( $commit_id === $lgit_head )
		) {
			$file_contents_tmp = @file_get_contents(
				$local_git_repo . '/' . $file_name
			);
		}


		/*
		 * If either the commit ID and the head are not
		 * the same, or fetching the file failed; make
		 * a note of that, and do not try to use the
		 * repository again for this run
		 */
		if (
			( $commit_id !== $lgit_head ) ||
			( $file_contents_tmp === false )
		) {
			vipgoci_log(
				'Skipping local Git repository, seems not to be in sync with current commit',
				array(
					'repo_owner'		=> $repo_owner,
					'repo_name'		=> $repo_name,
					'commit_id'		=> $commit_id,
					'filename'		=> $file_name,
					'local_git_repo'	=> $local_git_repo,
				)
			);
		}

		/*
		 * If everything seems fine, return the file.
		 */

		if ( false !== $file_contents_tmp ) {
			/*
			 * Non-failure, return the file contents.
			 */
			return $file_contents_tmp;
		}

		$local_git_repo_failure = true;
	}


	/*
	 * Fallback to GitHub.
	 *
	 * Check first for a cached copy of the
	 * file -- if that does not exist, ask
	 * GitHub for a copy and cache it.
	 */

	// Check for cached copy of the file
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name, $commit_id,
		$file_name, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );


	vipgoci_log(
		'Fetching file-contents from GitHub' .
			( $cached_data ? ' (cached)' : '' ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'filename' => $file_name,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}


	// FIXME: Detect if GitHub returned with an error.
	$data = vipgoci_github_fetch_url(
		'https://raw.githubusercontent.com/' .
		rawurlencode( $repo_owner ) .  '/' .
		rawurlencode( $repo_name ) . '/' .
		rawurlencode( $commit_id ) . '/' .
		rawurlencode( $file_name ),
		$github_token
	);

	vipgoci_cache( $cached_id, $data );

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
function vipgoci_github_pull_requests_comments_get(
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
		$commit_id, $commit_made_at, $github_token
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
		return $cached_data;
	}

	$page = 0;
	$prs_comments = array();


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
			'page=' . rawurlencode( $page );

		// FIXME: Detect when GitHub returned with an error
		$prs_comments_tmp = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);


		/*
		 * Look through each comment, create an associative array
		 * of file:position out of all the comments, so any comment
		 * can easily be found.
		 */

		foreach ( $prs_comments_tmp as $pr_comment ) {
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

			$prs_comments[
				$pr_comment->path . ':' .
				$pr_comment->position
			][] = $pr_comment;
		}

		$page++;

	} while ( count( $prs_comments_tmp ) >= 30 );


	vipgoci_cache( $cached_id, $prs_comments );

	return $prs_comments;
}


/*
 * Submit a review on GitHub for a particular commit,
 * and pull-request using the access-token provided.
 */
function vipgoci_github_review_submit(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$results,
	$dry_run
) {
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


		$commit_issues_rewritten = array();

		foreach (
			$results['issues'][ $pr_number ]
				as $commit_issue
		) {
			$commit_issues_rewritten[] = array(
				'body'		=>
					vipgoci_github_labels(
						$commit_issue['issue']['level']
					) . ' ' .

					'**' .
					ucfirst( strtolower(
						$commit_issue['issue']['level']
						)) .
					'**: ' .
					htmlentities(
						$commit_issue['issue']['message']
					),

				'position'	=> $commit_issue['file_line'],
				'path'		=> $commit_issue['file_name']
			);
		}


		$github_postfields = array(
			'commit_id'	=> $commit_id,
			'body'		=> '',
			'event'		=> '',
			'comments'	=> $commit_issues_rewritten,
		);

		/*
		 * If there are 'error'-level issues, make sure the submission
		 * asks for changes to be made, otherwise only comment.
		 */

		if (
			( ! empty(
				$results['stats'][ 'lint' ][ $pr_number ]['error']
			) )
			||
			( ! empty(
				$results['stats'][ 'phpcs' ][ $pr_number ]['error']
			) )
		) {
			$github_postfields['event'] = 'REQUEST_CHANGES';
		}

		else {
			$github_postfields['event'] = 'COMMENT';
		}


		/*
		 * Compose the number of warnings/errors for the
		 * review-submission to GitHub.
		 */

		foreach (
			array( 'PHPCS', 'lint' ) as
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

		// Actually send a request to GitHub
		vipgoci_github_post_url(
			$github_url,
			$github_postfields,
			$github_token
		);
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
	$filetypes_approve
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

	// FIXME: Ignore PRs that have already been accepted,
	// that way we can avoid strange things happening when
	// we already auto-approved a PR.

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


	$prs_implicated = array();
	$prs_maybe_implicated = array();


	$page = 0;

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
			'page=' . rawurlencode( $page );


		// FIXME: Detect when GitHub sent back an error
		$prs_implicated_unfiltered = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);

		/*
		 * Filter out any Pull-Requests that
		 * have nothing to do with our commit
		 */
		foreach ( $prs_implicated_unfiltered as $pr_item ) {
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

			else {
				/*
				 * No match, might be relevant, so needs
				 * to be checked in more detail.
				 */
				$prs_maybe_implicated[] = $pr_item->number;
			}
		}

		sleep ( 2 );

		$page++;
	} while ( count( $prs_implicated_unfiltered ) >= 30 );


	/*
	 * Look through any Pull-Requests that might be implicated
	 * -- to do this, we have fetch all commits implicated by all
	 * open Pull-Requests to make sure our comments are delivered
	 * successfully.
	 */

	foreach ( $prs_maybe_implicated as $pr_number_tmp ) {
		if ( in_array(
			$commit_id,
			vipgoci_github_prs_commits_list(
				$repo_owner,
				$repo_name,
				$pr_number_tmp,
				$github_token
			),
			true
		) ) {

			$github_url =
				'https://api.github.com/' .
				'repos/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'pulls/' .
				rawurlencode( $pr_number_tmp );


			$prs_implicated[ $pr_number_tmp ] =
				json_decode(
					vipgoci_github_fetch_url(
						$github_url,
						$github_token
					)
				);
		}
	}


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


	$pr_commits = array();


	$page = 0;

	do {
		$github_url =
			'https://api.github.com/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls/' .
			rawurlencode( $pr_number ) . '/' .
			'commits?' .
			'page=' . rawurlencode( $page );


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
	} while ( count( $pr_commits_raw ) >= 30 );

	vipgoci_cache( $cached_id, $pr_commits );

	return $pr_commits;
}

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
 * Check if the specified comment exists
 * within an array of other comments --
 * this is used to understand if the specific
 * comment has already been submitted earlier.
 */
function vipgoci_github_comment_match(
	$file_issue_path,
	$file_issue_line,
	$file_issue_comment,
	$comments_made
) {

	/*
	 * Construct an index-key made of file:line.
	 */
	$comment_index_key =
		$file_issue_path .
		':' .
		$file_issue_line;


	if ( ! isset(
		$comments_made[
			$comment_index_key
		]
	)) {
		/*
		 * No match on index-key within the
		 * associative array -- the comment has
		 * not been made, so return false.
		 */
		return false;
	}


	/*
	 * Some comment matching the file and line-number
	 * was found -- figure out if it is definately the
	 * same comment.
	 */

	foreach (
		$comments_made[ $comment_index_key ] as
		$comment_made
	) {
		/*
		 * The comment might contain formatting, such
		 * as "Warning: ..." -- remove all of that.
		 */
		$comment_made_body = str_replace(
			array("**", "Warning", "Error"),
			array("", "", ""),
			$comment_made->body
		);

		/*
		 * The comment might be prefixed with ': ',
		 * remove that as well.
		 */
		$comment_made_body = ltrim(
			$comment_made_body,
			': '
		);

		if (
			strtolower( $comment_made_body ) ==
			strtolower( $file_issue_comment )
		) {
			/* Comment found, return true. */
			return true;
		}
	}

	return false;
}

