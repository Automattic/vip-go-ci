<?php
/**
 * GitHub API related functions.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

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
	$rate_limit = vipgoci_http_api_fetch_url(
		VIPGOCI_GITHUB_BASE_URL . '/rate_limit',
		$github_token
	);

	return json_decode(
		$rate_limit
	);
}

/*
 * Fetch diffs between two commits from GitHub API,
 * cache results.
 */
function vipgoci_github_diffs_fetch_unfiltered(
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id_a,
	string $commit_id_b
): ?array {

	/*
	 * Check for a cached copy of the diffs
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_id_a, $commit_id_b
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching diffs between two commits ' .
			'from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),

		array(
			'repo_owner'	=> $repo_owner,
			'repo_name'	=> $repo_name,
			'commit_id_a'	=> $commit_id_a,
			'commit_id_b'	=> $commit_id_b,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	/*
	 * Nothing cached; ask GitHub.
	 */

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'compare/' .
		rawurlencode( $commit_id_a ) .
		'...' .
		rawurlencode( $commit_id_b );

	// FIXME: Error-handling
	$resp_raw = json_decode(
		vipgoci_http_api_fetch_url(
			$github_url,
			$github_token
		),
		true
	);

	/*
	 * If no "files" in array, return with error.
	 */
	if ( ! isset( $resp_raw['files'] ) ) {
		return null;
	}

	/*
	 * Prepare results array.
	 */
	$diff_results = array(
		'files'         => array(),
		'statistics'    => array(
			VIPGOCI_GIT_DIFF_CALC_CHANGES['+']      => 0,
			VIPGOCI_GIT_DIFF_CALC_CHANGES['-']      => 0,
			'changes'                               => 0,
		),
	);

	foreach( array_values( $resp_raw['files'] ) as $file_item ) {
		$diff_results['files'][
			$file_item['filename']
		] = array(
			'filename'	=> $file_item['filename'],
			'patch'		=> (
				isset( $file_item['patch'] ) ?
				$file_item['patch'] :
				''
			),
			'status'	=> $file_item['status'],
			'additions'	=> $file_item['additions'],
			'deletions'	=> $file_item['deletions'],
			'changes'	=> $file_item['changes'],
		);

		if ( isset( $file_item['previous_filename'] ) ) {
			$diff_results['files'][
				$file_item['filename']
			]['previous_filename'] =
				$file_item['previous_filename'];
		}

		$diff_results['statistics']
			[ VIPGOCI_GIT_DIFF_CALC_CHANGES['+'] ] +=
				$file_item[ VIPGOCI_GIT_DIFF_CALC_CHANGES['+'] ];

		$diff_results['statistics']
			[ VIPGOCI_GIT_DIFF_CALC_CHANGES['-'] ] +=
				$file_item[ VIPGOCI_GIT_DIFF_CALC_CHANGES['-'] ];

		$diff_results['statistics']['changes'] +=
			$file_item['changes'];
	}

	/*
	 * Save a copy in cache.
	 */
	vipgoci_cache( $cached_id, $diff_results );

	vipgoci_log(
		'Fetched git diff from GitHub API',
		array(
			'statistics'            => $diff_results['statistics'],
			'files_partial_20_max'  => array_slice(
				array_keys(
					$diff_results['files']
				),
				0,
				20
			)
		)
	);

	return $diff_results;
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
			vipgoci_cached_indication_str( $cached_data ),
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
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'commits/' .
			rawurlencode( $commit_id );

		$data = json_decode(
			vipgoci_http_api_fetch_url(
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
	$options,
	$commit_id,
	$commit_made_at,
	&$prs_comments
) {
	$repo_owner = $options['repo-owner'];
	$repo_name = $options['repo-name'];
	$github_token = $options['token'];

	/*
	 * Try to get comments from cache
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_made_at, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching pull requests comments info from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),

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

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'pulls/' .
				'comments?' .
				'sort=created&' .
				'direction=asc&' .
				'since=' . rawurlencode( $commit_made_at ) . '&' .
				'page=' . rawurlencode( (string) $page ) . '&' .
				'per_page=' . rawurlencode( (string) $per_page );

			/*
			 * Fetch results from GitHub, but do not stop
			 * execution on failure. This is because in some edge
			 * cases the GitHub API consistently cannot process these
			 * kinds of requests, returning HTTP 500 errors, probably
			 * due to a bug in the API. We want to continue processing
			 * despite this and return partial results, as it will not
			 * have a great impact on the final output.
			 */
			$prs_comments_tmp = vipgoci_http_api_fetch_url(
				$github_url,
				$github_token,
				false // Do not stop execution on failure.
			);

			if ( null !== $prs_comments_tmp ) {
				$prs_comments_tmp = json_decode(
					$prs_comments_tmp
				);
			}

			if (
				( null === $prs_comments_tmp ) || 
				( false === is_array( $prs_comments_tmp ) )
			) {
				vipgoci_log(
					'Unable to fetch data from GitHub, returning partial results',
					array(
						'request_response' => $prs_comments_tmp,
					)
				);

				$page++;
				$prs_comments_tmp = array();

				continue;
			}

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
 * Get all review-comments submitted to a
 * particular pull request.
 * Supports filtering by:
 * - User submitted (parameter: login)
 * - Comment state (parameter: comments_active, true/false)
 *
 * Note that parameter login can be assigned a magic
 * value, 'myself', in which case the actual username
 * will be assumed to be that of the token-holder.

 */
function vipgoci_github_pr_reviews_comments_get_by_pr(
	$options,
	$pr_number,
	$filter = array()
) {

	/*
	 * Calculate caching ID.
	 *
	 * Note that $filter should be used here and not its
	 * individual components, to enable new data to be fetched
	 * (i.e. avoiding of caching by callers).
	 */
	$cache_id = array(
		__FUNCTION__, $options['repo-owner'], $options['repo-name'],
		$pr_number, $filter
	);

	/*
	 * Try to get cached data
	 */
	$cached_data = vipgoci_cache( $cache_id );

	vipgoci_log(
		'Fetching all review comments submitted to a pull request' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'pr_number'	=> $pr_number,
			'filter'	=> $filter,
		)
	);

	/*
	 * If we have the information cached,
	 * return that.
	 */
	if ( false !== $cached_data ) {
		return $cached_data;
	}

	if (
		( isset( $filter['login'] ) ) &&
		( 'myself' === $filter['login'] )
	) {
		/* Get info about token-holder */
		$current_user_info = vipgoci_github_authenticated_user_get(
			$options['token']
		);

		$filter['login'] = $current_user_info->login;
	}

	$page = 1;
	$per_page = 100;

	$all_comments = array();

	do {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $options['repo-owner'] ) . '/' .
			rawurlencode( $options['repo-name'] ) . '/' .
			'pulls/' .
			rawurlencode( (string) $pr_number ) . '/' .
			'comments?' .
			'page=' . rawurlencode( (string) $page ) . '&' .
			'per_page=' . rawurlencode( (string) $per_page );

		$comments = json_decode(
			vipgoci_http_api_fetch_url(
				$github_url,
				$options['token']
			)
		);

		foreach( $comments as $comment ) {
			if (
				( isset( $filter['login'] ) ) &&
				( $comment->user->login !== $filter['login'] )
			) {
				continue;
			}

			if ( isset( $filter['comments_active'] ) ) {
				if (
					( ( $comment->position !== null ) &&
					( $filter['comments_active'] === false ) )
					||
					( ( $comment->position === null ) &&
					( $filter['comments_active'] === true ) )
				) {
					continue;
				}
			}

			$all_comments[] = $comment;
		}

		$page++;
	} while( count( $comments ) >= $per_page );

	/*
	 * Cache the results and return
	 */
	vipgoci_cache( $cache_id, $all_comments );

	return $all_comments;
}


/*
 * Remove a particular PR review comment.
 */

function vipgoci_github_pr_reviews_comments_delete(
	$options,
	$comment_id
) {
	vipgoci_log(
		'Deleting an inline comment from a pull request ' .
			'review',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'comment_id'	=> $comment_id,
		)
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $options['repo-owner'] ) . '/' .
		rawurlencode( $options['repo-name'] ) . '/' .
		'pulls/' .
		'comments/' .
		rawurlencode( $comment_id );

	vipgoci_http_api_post_url(
		$github_url,
		array(),
		$options['token'],
		true // Indicates a 'DELETE' request
	);
}

/*
 * Get all generic comments made to a pull request from Github.
 */

function vipgoci_github_pr_generic_comments_get_all(
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
		'Fetching pull requests generic comments from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),

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
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( (string) $pr_number ) . '/' .
			'comments' .
			'?page=' . rawurlencode( (string) $page ) . '&' .
			'per_page=' . rawurlencode( (string) $per_page );


		$pr_comments_raw = json_decode(
			vipgoci_http_api_fetch_url(
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
 * Post a generic PR comment to GitHub. Will
 * include a commit_id in the comment if provided.
 */
function vipgoci_github_pr_comments_generic_submit(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$message,
	$commit_id = null
) {
	vipgoci_log(
		'Posting a comment to a pull request',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'commit_id' => $commit_id,
			'message' => $message,
		),
		0,
		true // Log to IRC as well
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( (string) $pr_number ) . '/' .
		'comments';


	$github_postfields = array();
	$github_postfields['body'] =
		$message;

	if ( ! empty( $commit_id ) ) {
		$github_postfields['body'] .=
			' (commit-ID: ' . $commit_id . ').';
	}

	$github_postfields['body'] .=
		"\n\r";

	vipgoci_http_api_post_url(
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
	$skip_draft_prs,
	$comments_remove
) {
	vipgoci_log(
		'About to clean up generic PR comments on Github',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'branches_ignore' => $branches_ignore,
			'comments_remove' => $comments_remove,
			'skip_draft_prs' => $skip_draft_prs,
		)
	);

	/* Get info about token-holder */
	$current_user_info = vipgoci_github_authenticated_user_get(
		$github_token
	);


	$prs_implicated = vipgoci_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		$branches_ignore,
		$skip_draft_prs
	);

	foreach ( $prs_implicated as $pr_item ) {
		$pr_comments = vipgoci_github_pr_generic_comments_get_all(
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

			foreach( $comments_remove as $comments_remove_item ) {
				if ( strpos(
					$pr_comment->body,
					$comments_remove_item
				) !== false ) {
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
	}
}


/*
 * Delete generic comment made to pull request.
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
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		'comments/' .
		rawurlencode( (string) $comment_id );

	/*
	 * Send DELETE request to GitHub.
	 */
	vipgoci_http_api_post_url(
		$github_url,
		array(),
		$github_token,
		true
	);
}

/*
 * Get all reviews for a particular pull request,
 * and allow filtering by:
 * - User submitted (parameter: login)
 * - State of review (parameter: state,
 *	values are an array of: CHANGES_REQUESTED,
 *	COMMENTED, APPROVED)
 *
 * Note that parameter login can be assigned a magic
 * value, 'myself', in which case the actual username
 * will be assumed to be that of the token-holder.
 */
function vipgoci_github_pr_reviews_get(
	$repo_owner,
	$repo_name,
	$pr_number,
	$github_token,
	$filter = array(),
	$skip_cache = false
) {

	$cache_id = array(
		__FUNCTION__, $repo_owner, $repo_name, $pr_number,
		$github_token,
	);

	$cached_data = vipgoci_cache( $cache_id );

	if ( true === $skip_cache ) {
		$cached_data = false;
	}

	vipgoci_log(
		'Fetching reviews for pull request ' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'filter' => $filter,
			'skip_cache' => $skip_cache,
		)
	);


	if ( false === $cached_data ) {
		/*
		 * Fetch reviews, paged, from GitHub.
		 */

		$ret_reviews = array();

		$page = 1;
		$per_page = 100;

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'pulls/' .
				rawurlencode( (string) $pr_number ) . '/' .
				'reviews' .
				'?per_page=' . rawurlencode( (string) $per_page ) . '&' .
				'page=' . rawurlencode( (string) $page );


			/*
			 * Fetch reviews, decode result.
			 */
			$pr_reviews = json_decode(
				vipgoci_http_api_fetch_url(
					$github_url,
					$github_token
				)
			);

			foreach( $pr_reviews as $pr_review ) {
				$ret_reviews[] = $pr_review;
			}

			unset( $pr_review );

			$page++;
		} while( count( $pr_reviews ) >= $per_page );


		vipgoci_cache(
			$cache_id,
			$ret_reviews
		);
	}

	else {
		$ret_reviews = $cached_data;
	}


	/*
	 * Figure out login name.
	 */
	if (
		( ! empty( $filter['login'] ) ) &&
		( $filter['login'] === 'myself' )
	) {
		$current_user_info = vipgoci_github_authenticated_user_get(
			$github_token
		);

		$filter['login'] = $current_user_info->login;
	}

	/*
	 * Loop through each review-item,
	 * do filtering and save the ones
	 * we want to keep.
	 */

	$ret_reviews_filtered = array();

	foreach( $ret_reviews as $pr_review ) {
		if ( ! empty( $filter['login'] ) ) {
			if (
				$pr_review->user->login !==
				$filter['login']
			) {
				continue;
			}
		}

		if ( ! empty( $filter['state'] ) ) {
			$match = false;

			foreach(
				$filter['state'] as
					$allowed_state
			) {
				if (
					$pr_review->state ===
					$allowed_state
				) {
					$match = true;
				}
			}

			if ( false === $match ) {
				continue;
			}
		}

		$ret_reviews_filtered[] = $pr_review;
	}


	return $ret_reviews_filtered;
}

/*
 * Dismiss a particular review
 * previously submitted to a pull request.
 */

function vipgoci_github_pr_review_dismiss(
	$repo_owner,
	$repo_name,
	$pr_number,
	$review_id,
	$dismiss_message,
	$github_token
) {

	vipgoci_log(
		'Dismissing a pull request review',
		array(
			'repo_owner'		=> $repo_owner,
			'repo_name'		=> $repo_name,
			'pr_number'		=> $pr_number,
			'review_id'		=> $review_id,
			'dismiss_message'	=> $dismiss_message,
		)
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'pulls/' .
		rawurlencode( (string) $pr_number ) . '/' .
		'reviews/' .
		rawurlencode( (string) $review_id ) . '/' .
		'dismissals';

	vipgoci_http_api_put_url(
		$github_url,
		array(
			'message' => $dismiss_message
		),
		$github_token
	);
}


/*
 * Dismiss all pull request reviews that have no
 * active comments attached to them.
 */
function vipgoci_github_pr_reviews_dismiss_with_non_active_comments(
	$options,
	$pr_number
) {
	vipgoci_log(
		'Dismissing any pull request reviews submitted by ' .
			'us and contain no active inline comments any more',
		array(
			'repo_owner'		=> $options['repo-owner'],
			'repo_name'		=> $options['repo-name'],
			'pr_number'		=> $pr_number,
		)
	);

	/*
	 * Get any pull request reviews with changes
 	 * required status, and submitted by us.
	 */
	$pr_reviews = vipgoci_github_pr_reviews_get(
		$options['repo-owner'],
		$options['repo-name'],
		$pr_number,
		$options['token'],
		array(
			'login' => 'myself',
			'state' => array( 'CHANGES_REQUESTED' )
		)
	);

	/*
	 * Get all comments to the current pull request.
	 *
	 * Note that we must bypass cache here,
	 */
	$all_comments = vipgoci_github_pr_reviews_comments_get_by_pr(
		$options,
		$pr_number,
		array(
			'login' => 'myself',
			'timestamp' => time() // To bypass caching
		)
	);

	if ( count( $all_comments ) === 0 ) {
		/*
		 * In case we receive no comments at all
		 * from GitHub, do not do anything, as a precaution.
		 * Receiving no comments might indicate a
		 * failure (communication error or something else),
		 * and if we dismiss reviews that seem not to
		 * contain any comments, we might risk dismissing
		 * all reviews when there is a failure. By
		 * doing this, we take much less risk.
		 */
		vipgoci_log(
			'Not dismissing any reviews, as no inactive ' .
				'comments submitted to the pull request ' .
				'were found',
			array(
				'repo_owner'	=> $options['repo-owner'],
				'repo_name'	=> $options['repo-name'],
				'pr_number'	=> $pr_number,
			)
		);

		return;
	}

	$reviews_status = array();

	foreach( $all_comments as $comment_item ) {
		/*
		 * Not associated with a review? Ignore then.
		 */
		if ( ! isset( $comment_item->pull_request_review_id ) ) {
			continue;
		}

		/*
		 * If the review ID is not found in
		 * the array of reviews, put in 'null'.
		 */
		if ( ! isset( $reviews_status[
			$comment_item->pull_request_review_id
		] ) ) {
			$reviews_status[
				$comment_item->pull_request_review_id
			] = null;
		}

		/*
		 * In case position (relative line number)
		 * is at null, this means that the comment
		 * is no longer 'active': It has become obsolete
		 * as the code has changed. If we have not so far
		 * found any instance of the review associated
		 * with the comment having other active comments,
		 * mark it as 'safe to dismiss'.
		 */
		if ( null === $comment_item->position ) {
			if (
				$reviews_status[
					$comment_item->pull_request_review_id
				] !== false
			) {
				$reviews_status[
					$comment_item->pull_request_review_id
				] = true;
			}
		}

		else {
			$reviews_status[
				$comment_item->pull_request_review_id
			] = false;
		}
	}

	/*
	 * Loop through each review we
	 * found matching the specific criteria.
	 *
	 * Note that implicit in this logic is that
	 * there must be some comments attached to a
	 * review so it becomes dismissable at all.
	 */
	foreach( $pr_reviews as $pr_review ) {
		/*
		 * If no active comments were found,
		 * it should be safe to dismiss the review.
		 */
		if (
			( isset( $reviews_status[ $pr_review->id ] ) ) &&
			( true === $reviews_status[ $pr_review->id ] )
		) {
			vipgoci_github_pr_review_dismiss(
				$options['repo-owner'],
				$options['repo-name'],
				$pr_number,
				$pr_review->id,
				'Dismissing review as all inline comments ' .
					'are obsolete by now',
				$options['token']
			);
		}
	}
}

/*
 * Approve a pull request, and afterwards
 * make sure to verify that the latest commit
 * added to the pull request is commit with
 * commit-ID $latest_commit_id -- this is to avoid
 * race-conditions.
 *
 * The race-conditions can occur when a pull request
 * is approved, but it is approved after a new commit
 * was added which has not been scanned.
 */

function vipgoci_github_approve_pr(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$latest_commit_id,
	$message
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
		'commit_id' => $latest_commit_id,
		'body' => null,
		'event' => 'APPROVE',
		'comments' => array()
	);

	$github_postfields['body'] = $message;

	vipgoci_log(
		'Sending request to GitHub to approve pull request',
		array(
			'repo_owner'		=> $repo_owner,
			'repo_name'		=> $repo_name,
			'pr_number'		=> $pr_number,
			'latest_commit_id'	=> $latest_commit_id,
			'github_url'		=> $github_url,
			'github_postfields'	=> $github_postfields,
		),
		2
	);

	// Actually approve
	vipgoci_http_api_post_url(
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
	$branches_ignore,
	$skip_draft_prs = false,
	$bypass_cache = false
) {
	/*
	 * Check for data in cache if
	 * not asked to bypass cache.
	 * Otherwise simply bypass.
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_id, $github_token, $branches_ignore
	);

	if ( false === $bypass_cache ) {
		$cached_data = vipgoci_cache( $cached_id );
	}

	else {
		$cached_data = false;
	}

	vipgoci_log(
		'Fetching all open pull requests from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner'		=> $repo_owner,
			'repo_name'		=> $repo_name,
			'commit_id'		=> $commit_id,
			'branches_ignore'	=> $branches_ignore,
			'skip_draft_prs'	=> $skip_draft_prs,
			'bypass_cache'		=> $bypass_cache,
		)
	);

	if ( false !== $cached_data ) {
		/*
		 * Filter away draft pull requests if requested.
		 */
		if ( true === $skip_draft_prs ) {
			$cached_data = vipgoci_github_pr_remove_drafts(
				$cached_data
			);
		}

		return $cached_data;
	}

	/*
	 * Nothing cached; ask GitHub.
	 */

	$prs_implicated = array();


	$page = 1;
	$per_page = 100;

	/*
	 * Fetch all open pull requests, store
	 * PR IDs that have a commit-head that matches
	 * the one we are working on.
	 */
	do {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls' .
			'?state=open&' .
			'page=' . rawurlencode( (string) $page ) . '&' .
			'per_page=' . rawurlencode( (string) $per_page );


		// FIXME: Detect when GitHub sent back an error
		$prs_implicated_unfiltered = json_decode(
			vipgoci_http_api_fetch_url(
				$github_url,
				$github_token
			)
		);


		foreach ( $prs_implicated_unfiltered as $pr_item ) {
			if ( ! isset( $pr_item->head->ref ) ) {
				continue;
			}

			/*
			 * If the branch this pull request is associated
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
			 * matches the head-commit of the pull request,
			 * then the pull request should be considered to
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

	/*
	 * Filter away draft pull requests if requested.
	 */
	if ( true === $skip_draft_prs ) {
		$prs_implicated = vipgoci_github_pr_remove_drafts(
			$prs_implicated
		);
	}

	return $prs_implicated;
}

/**
 * @param string $repo_owner 
 * @param string $repo_name
 * @param string $commit_id
 * @param string $github_token
 * @param array $branches_ignore
 * @param bool $skip_draft_prs
 * @param int $try_total
 * @param int $sleep_time
 *
 * Get pull requests currently open, but retry if
 * nothing is found. Uses vipgoci_github_prs_implicated().
 *
 * @return array
 *
 * @codeCoverageIgnore
 */
function vipgoci_github_prs_implicated_with_retries(
	string $repo_owner,
	string $repo_name,
	string $commit_id,
	string $github_token,
	array $branches_ignore,
	bool $skip_draft_prs = false,
	int $try_total = 2,
	int $sleep_time = 10
) : ?array {
	$prs_implicated_retries = 0;

	do {
		if ( $prs_implicated_retries > 0 ) {
			vipgoci_log(
				'No PR found, retrying...',
				array(
					'sleep_time'	=> $sleep_time,
				)
			);

			sleep( $sleep_time );
		}

		$prs_implicated = vipgoci_github_prs_implicated(
			$repo_owner,
			$repo_name,
			$commit_id,
			$github_token,
			$branches_ignore,
			$skip_draft_prs,
			( $prs_implicated_retries === 0 ) ? false : true
		);
		
		$prs_implicated_retries++;
	} while (
		( empty( $prs_implicated ) ) &&
		( $prs_implicated_retries < $try_total )
	);

	return $prs_implicated;
}

/*
 * Get all commits that are a part of a pull request.
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
			' to pull request #' .
			(int) $pr_number . ' from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),

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
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls/' .
			rawurlencode( (string) $pr_number ) . '/' .
			'commits?' .
			'page=' . rawurlencode( (string) $page ) . '&' .
			'per_page=' . rawurlencode( (string) $per_page );


		// FIXME: Detect when GitHub sent back an error
		$pr_commits_raw = json_decode(
			vipgoci_http_api_fetch_url(
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

/**
 * Get information from GitHub on the user
 * authenticated.
 *
 * @codeCoverageIgnore
 */
function vipgoci_github_authenticated_user_get( $github_token ) {
	$cached_id = array(
		__FUNCTION__, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Trying to get information about the user the GitHub-token belongs to' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}


	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'user';

	$current_user_info_json = vipgoci_http_api_fetch_url(
		$github_url,
		$github_token
	);

	$current_user_info = null;

	if ( false !== $current_user_info_json ) {
		$current_user_info = json_decode(
			$current_user_info_json
		);
	}

	if (
		( false === $current_user_info_json ) ||
		( null === $current_user_info )
	) {
		vipgoci_log(
			'Unable to get information about token-holder from' .
				'GitHub due to error',
			array(
				'current_user_info_json' => $current_user_info_json,
				'current_user_info' => $current_user_info,
			)
		);

		return false;
	}


	vipgoci_cache( $cached_id, $current_user_info );

	return $current_user_info;
}


/*
 * Add a particular label to a specific
 * pull request (or issue).
 */
function vipgoci_github_label_add_to_pr(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_name
) {
	vipgoci_log(
		'Adding label to GitHub issue',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'label_name' => $label_name,
		)
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( (string) $pr_number ) . '/' .
		'labels';

	$github_postfields = array(
		$label_name
	);

	vipgoci_http_api_post_url(
		$github_url,
		$github_postfields,
		$github_token
	);
}

/*
 * Fetch labels associated with a
 * particular issue/pull request.
 */
function vipgoci_github_pr_labels_get(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_to_look_for = null,
	$skip_cache = false
) {
	/*
	 * Check first if we have
	 * got the information cached
	 */
	$cache_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$github_token, $pr_number
	);

	$cached_data = vipgoci_cache( $cache_id );

	/*
	 * If asked to skip cache, imitate no cached
	 * data available.
	 */
	if (
		( false !== $cached_data ) &&
		( true === $skip_cache )
	) {
		$cached_data = false;
	}

	vipgoci_log(
		'Getting labels associated with GitHub issue' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'label_to_look_for' => $label_to_look_for,
			'skip_cache' => $skip_cache,
		)
	);

	/*
	 * If there is nothing cached, fetch it
	 * from GitHub.
	 */
	if ( false === $cached_data ) {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( (string) $pr_number ) . '/' .
			'labels';

		$data = vipgoci_http_api_fetch_url(
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
 * pull request (or issue).
 */
function vipgoci_github_pr_label_remove(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_name
) {
	vipgoci_log(
		'Removing label from GitHub issue',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'label_name' => $label_name,
		)
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( (string) $pr_number ) . '/' .
		'labels/' .
		rawurlencode( $label_name );

	vipgoci_http_api_post_url(
		$github_url,
		array(),
		$github_token,
		true // DELETE request will be sent
	);
}


/*
 * Get all events issues related to a pull request
 * from the GitHub API, and filter away any items that
 * do not match a given criteria (if applicable).
 *
 * Note: Using $review_ids_only = true will imply
 * selecting only certain types of events (i.e. dismissed_review).
 */
function vipgoci_github_pr_review_events_get(
	$options,
	$pr_number,
	$filter = null,
	$review_ids_only = false
) {
	$cached_id = array(
		__FUNCTION__, $options['repo-owner'], $options['repo-name'],
		$options['token'], $pr_number
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Getting issue events for pull request from GitHub API' .
		vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner' => $options['repo-owner'],
			'repo_name' => $options['repo-name'],
			'pr_number' => $pr_number,
			'filter' => $filter,
			'review_ids_only' => $review_ids_only,
		)
	);

	if ( false === $cached_data ) {
		$page = 1;
		$per_page = 100;

		$all_issue_events = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $options['repo-owner'] ) . '/' .
				rawurlencode( $options['repo-name'] ) . '/' .
				'issues/' .
				rawurlencode( (string) $pr_number ) . '/' .
				'events?' .
				'page=' . rawurlencode( (string) $page ) . '&' .
				'per_page=' . rawurlencode( (string) $per_page );


			$issue_events = vipgoci_http_api_fetch_url(
				$github_url,
				$options['token']
			);

			$issue_events = json_decode(
				$issue_events
			);

			foreach( $issue_events as $issue_event ) {
				$all_issue_events[] = $issue_event;
			}

			unset( $issue_event );

			$page++;
		} while ( count( $issue_events ) >= $per_page );

		$issue_events = $all_issue_events;
		unset( $all_issue_events );

		vipgoci_cache(
			$cached_id,
			$issue_events
		);
	}

	else {
		$issue_events = $cached_data;
	}

	/*
	 * Filter results if requested. We can filter
	 * by type of event and/or by actors that initiated
	 * the event.
	 */
	if ( null !== $filter ) {
		$filtered_issue_events = array();

		foreach( $issue_events as $issue_event ) {
			if (
				( ! empty( $filter['event_type'] ) ) &&
				( is_string( $filter['event_type'] ) ) &&
				(
					$issue_event->event !==
					$filter['event_type']
				)
			) {
				continue;
			}

			if (
				( ! empty( $filter['actors_logins'] ) ) &&
				( is_array( $filter['actors_logins'] ) ) &&
				( false === in_array(
					$issue_event->actor->login,
					$filter['actors_logins']
				) )
			) {
				continue;
			}

			if (
				( ! empty( $filter['actors_ids'] ) ) &&
				( is_array( $filter['actors_ids'] ) ) &&
				( false === in_array(
					$issue_event->actor->id,
					$filter['actors_ids']
				) )
			) {
				continue;
			}


			$filtered_issue_events[] = $issue_event;
		}

		$issue_events = $filtered_issue_events;
	}

	if ( true === $review_ids_only ) {
		$issue_events_ret = array();

		foreach( $issue_events as $issue_event ) {
			if ( ! isset(
				$issue_event->dismissed_review->review_id
			) ) {
				continue;
			}

			$issue_events_ret[] =
				$issue_event->dismissed_review->review_id;
		}

		$issue_events = $issue_events_ret;
	}

	return $issue_events;
}


/**
 * Get members for a team.
 *
 * @param string      $github_token       GitHub token to use to make GitHub API requests.
 * @param string      $org_slug           Organization slug for the organization that the team belongs to.
 * @param string      $team_slug          Slug of team to get members for.
 * @param string|null $return_values_only If specified, returns value of a particular field only from results.
 *
 * @return array Array with results.
 */
function vipgoci_github_team_members_get(
	string $github_token,
	string $org_slug,
	string $team_slug,
	$return_values_only = null
): array {
	$cached_id = array(
		__FUNCTION__,
		$github_token,
		$org_slug,
		$team_slug,
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Getting members for organization team' .
		vipgoci_cached_indication_str( $cached_data ),
		array(
			'org_slug'           => $org_slug,
			'team_slug'          => $team_slug,
			'return_values_only' => $return_values_only,
		)
	);

	if ( false === $cached_data ) {
		$page = 1;
		$per_page = 100;

		$team_members_all = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'orgs/' .
				rawurlencode( $org_slug ) . '/' .
				'teams/' .
				rawurlencode( $team_slug ) . '/' .
				'members?' .
				'page=' . rawurlencode( (string) $page ) . '&' .
				'per_page=' . rawurlencode( (string) $per_page );


			$team_members = vipgoci_http_api_fetch_url(
				$github_url,
				$github_token
			);

			$team_members = json_decode(
				$team_members
			);

			foreach( $team_members as $team_member ) {
				$team_members_all[] = $team_member;
			}

			$page++;
		} while ( count( $team_members ) >= $per_page );

		$team_members = $team_members_all;
		unset( $team_members_all );
		unset( $team_member );

		vipgoci_cache(
			$cached_id,
			$team_members
		);
	}

	else {
		$team_members = $cached_data;
	}

	/*
	 * If caller specified only certain value from
	 * each item to be return, honor that.
	 */
	if ( null !== $return_values_only ) {
		$team_members = array_column(
			(array) $team_members,
			$return_values_only
		);
	}

	return $team_members;
}


/**
 * Get team member IDs for one or more teams,
 * return members as a merged array.
 *
 * @param string $github_token   GitHub token to use to make GitHub API requests.
 * @param string $org_slug       Organization slug for the organization that the team belongs to.
 * @param array  $team_slugs_arr Array of team slugs to get team members for.
 *
 * @return array Array with team member IDs.
 */
function vipgoci_github_team_members_many_get(
	string $github_token,
	string $org_slug,
	array $team_slugs_arr = array()
): array {
	vipgoci_log(
		'Getting members of teams specified by caller',
		array(
			'org_slug'    => $org_slug,
			'teams_slugs' => $team_slugs_arr,
		)
	);

	$team_members_slugs_arr = array();

	foreach ( $team_slugs_arr as $team_slug_item ) {
		$team_slug_members = vipgoci_github_team_members_get(
			$github_token,
			$org_slug,
			$team_slug_item,
			'id'
		);

		$team_members_slugs_arr = array_merge(
			$team_members_slugs_arr,
			$team_slug_members
		);
	}

	$team_members_slugs_arr = array_unique(
		$team_members_slugs_arr
	);

	return $team_members_slugs_arr;
}


/**
 * Get organization teams available to the calling
 * user from the GitHub API.
 *
 * @param string $github_token GitHub token to use to make GitHub API requests.
 * @param string $org_slug     Organization slug which to get teams for.
 * @param mixed  $filter       If specified, will apply filter to results.
 * @param mixed  $keyed_by     If specified, will key results according to this parameter.
 *
 * @return array Array of teams.
 */
function vipgoci_github_org_teams_get(
	string $github_token,
	string $org_slug,
	$filter = null,
	$keyed_by = null
): array {
	$cached_id = array(
		__FUNCTION__,
		$github_token,
		$org_slug,
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Getting organization teams from GitHub API' .
		vipgoci_cached_indication_str( $cached_data ),
		array(
			'org_slug' => $org_slug,
			'filter'   => $filter,
			'keyed_by' => $keyed_by,
		)
	);

	if ( false === $cached_data ) {
		$page = 1;
		$per_page = 100;

		$org_teams_all = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'orgs/' .
				rawurlencode( $org_slug ) . '/' .
				'teams?' .
				'page=' . rawurlencode( (string) $page ) . '&' .
				'per_page=' . rawurlencode( (string) $per_page );


			$org_teams = vipgoci_http_api_fetch_url(
				$github_url,
				$github_token
			);

			$org_teams = json_decode(
				$org_teams
			);

			foreach( $org_teams as $org_team ) {
				$org_teams_all[] = $org_team;
			}

			$page++;
		} while ( count( (array) $org_teams ) >= $per_page );

		$org_teams = $org_teams_all;
		unset( $org_teams_all );

		vipgoci_cache(
			$cached_id,
			$org_teams
		);
	}

	else {
		$org_teams = $cached_data;
	}


	/*
	 * Filter the results according to criteria.
	 */
	if (
		( null !== $filter ) &&
		( ! empty( $filter['slug'] ) ) &&
		( is_string( $filter['slug'] ) )
	) {
		$org_teams_filtered = array();

		foreach( $org_teams as $org_team ) {
			if ( $filter['slug'] === $org_team->slug ) {
				$org_teams_filtered[] = $org_team;
			}
		}

		$org_teams = $org_teams_filtered;
	}


	/*
	 * If asked for, let the resulting
	 * array be keyed with a certain field.
	 */
	if ( null !== $keyed_by ) {
		$org_teams_keyed = array();

		foreach( $org_teams as $org_team ) {
			$org_team_arr = (array) $org_team;

			/*
			 * In case of invalid response,
			 * ignore item.
			 */
			if ( ! isset( $org_team_arr[ $keyed_by ] ) ) {
				continue;
			}

			$org_teams_keyed[
				$org_team_arr[
					$keyed_by
				]
			][] = $org_team;
		}

		$org_teams = $org_teams_keyed;
	}

	return $org_teams;
}

/*
 * Get repository collaborators.
 *
 * $affiliation can be:
 *  * outside, direct and all
 *
 * $filter works for permissions property, and removes
 * any items that do not match.
 */
function vipgoci_github_repo_collaborators_get(
	$repo_owner,
	$repo_name,
	$github_token,
	$affiliation = 'all',
	$filter = array()
) {
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name, $affiliation
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Getting collaborators for repository from GitHub API' .
		vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner'	=> $repo_owner,
			'repo_name'	=> $repo_name,
			'affiliation'	=> $affiliation,
			'filter'	=> $filter,
		)
	);

	if ( false === $cached_data ) {
		$page = 1;
		$per_page = 100;

		$repo_users_all = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'collaborators?' .
				'page=' . rawurlencode( (string) $page ) . '&' .
				'per_page=' . rawurlencode( (string) $per_page );

			if ( null !== $affiliation ) {
				$github_url .= '&affiliation=' . rawurlencode( $affiliation );
			}

			$repo_users = vipgoci_http_api_fetch_url(
				$github_url,
				$github_token
			);

			$repo_users = json_decode(
				$repo_users
			);

			foreach( $repo_users as $repo_user_item ) {
				$repo_users_all[] = $repo_user_item;
			}

			$page++;
		} while ( count( (array) $repo_users ) >= $per_page );

		unset( $repo_users );

		vipgoci_cache(
			$cached_id,
			$repo_users_all
		);
	}

	else {
		$repo_users_all = $cached_data;
	}

	/*
	 * Filter results.
	 */

	$repo_users_all_new = array();

	foreach (
		$repo_users_all as $repo_user_item
	) {
		foreach( array( 'admin', 'push', 'pull' ) as $_prop ) {
			$repo_user_item_tmp = (array) $repo_user_item;

			if ( isset( $repo_user_item_tmp['permissions'] ) ) {
				$repo_user_item_tmp['permissions'] = (array) $repo_user_item_tmp['permissions'];
			}

			if (
				( isset( $filter[ $_prop ] ) ) &&
				( isset( $repo_user_item_tmp['permissions'][ $_prop ] ) ) &&
				( (bool) $filter[ $_prop ] !== $repo_user_item_tmp['permissions'][ $_prop ] )
			) {
				continue 2;
			}
		}

		$repo_users_all_new[] = $repo_user_item;
	}

	$repo_users_all = $repo_users_all_new;
	unset( $repo_users_all_new );

	return $repo_users_all;
}

/*
 * Create commit status using the
 * Status API.
 */
function vipgoci_github_status_create(
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id,
	string $state,
	string $target_url,
	string $description,
	string $context
) {
	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'statuses/' .
		rawurlencode( $commit_id );

	$github_postfields = array(
		'state'		=> $state,
		'description'	=> $description,
		'context'	=> $context,
	);

	if ( ! empty( $target_url ) ) {
		$github_postfields[ 'target_url' ] =
			$target_url;
	}

	vipgoci_log(
		'Setting GitHub commit status for a particular commit-ID',
		array(
			'repo_owner'	=> $repo_owner,
			'repo_name'	=> $repo_name,
			'commit_id'	=> $commit_id,
			'status'	=> $github_postfields,
		)
	);

	vipgoci_http_api_post_url(
		$github_url,
		$github_postfields,
		$github_token
	);
}
