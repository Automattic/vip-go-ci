<?php

/*
 * This function works both to collect headers
 + when called as a callback function, and to return
 * the headers collected when called standalone.
 *
 * The difference is that the '$ch' argument is non-null
 * when called as a callback.
 */
function vipgoci_phpcs_curl_headers( $ch, $header ) {
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
		if ( isset( $ret[ 'status' ] ) ) {
			$ret[ 'status' ] = explode(
				' ',
				$ret[ 'status' ][0]
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
 * Make a GET request to GitHub, for the URL
 * provided, using the access-token specified.
 *
 * Will return the raw-data returned by GitHub,
 * or halt execution on repeated errors.
 */
function vipgoci_phpcs_github_fetch_url(
	$github_url, $github_access_token
) {

	$curl_retries = 0;

	/*
	 * Attempt to send request -- retry if
	 * it fails.
	 */
	do {
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, 			$github_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 	1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 	20 );
		curl_setopt( $ch, CURLOPT_USERAGENT, 		'automattic-github-review-client' );

		curl_setopt( $ch, CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_access_token )
		);

		$resp_data = curl_exec( $ch );

		/*
		 * Detect and process possible errors
		 */
		if (
			( false === $resp_data ) ||
			( curl_errno( $ch ) )
		) {
			vipgoci_phpcs_log(
				'Sending request to GitHub failed, will retry in a bit... ',
				array(
					'github_url' => $github_url,
					'curl_retries' => $curl_retries,
					'curl_errno' => curl_errno( $ch ),
					'curl_errormsg' => curl_strerror( curl_errno( $ch ) ),
				)
			);

			sleep( 60 );
		}

		else {
			/*
			 * Request seems to have been successful, in that it
			 * was processed by GitHub.
			 *
			 * However, GitHub asks that requests are made with at least
			 * one second interval. Guarantee that.
			 *
			 * https://developer.github.com/v3/guides/best-practices-for-integrators/#dealing-with-abuse-rate-limits
			 */
			sleep( 1 );
		}

		curl_close( $ch );

	} while (
		( false === $resp_data ) &&
		( $curl_retries++ < 2 )
	);


	if ( false === $resp_data ) {
		vipgoci_phpcs_log(
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
function vipgoci_phpcs_github_fetch_commit_info(
		$repo_owner, $repo_name, $commit_id, $github_access_token
) {
	vipgoci_phpcs_log(
		'Fetching commit info from GitHub',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);

	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'commits/' .
		rawurlencode( $commit_id );

	// FIXME: Detect when GitHub sent back an error
	return json_decode(
		vipgoci_phpcs_github_fetch_url(
			$github_url,
			$github_access_token
		)
	);
}


/*
 * Fetch from GitHub a particular file which is a part of a
 * commit, within a particular repository. Will return
 * the file (raw), or false on error.
 */

function vipgoci_phpcs_github_fetch_committed_file(
	$repo_owner,
	$repo_name,
	$github_access_token,
	$commit_id,
	$file_name
) {
	vipgoci_phpcs_log(
		'Fetching file-information from GitHub',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'filename' => $file_name,
		)
	);

	// FIXME: Detect if GitHub returned with an error.
	return vipgoci_phpcs_github_fetch_url(
		'https://raw.githubusercontent.com/' .
		rawurlencode( $repo_owner ) .  '/' .
		rawurlencode( $repo_name ) . '/' .
		rawurlencode( $commit_id ) . '/' .
		rawurlencode( $file_name ),
		$github_access_token
	);
}


/*
 * Fetch all comments made on GitHub for the
 * repository and commits specified.
 *
 * Will return an associative array of comments,
 * with file-name and file-line number as keys. Will
 * return false on an error.
 */
function vipgoci_phpcs_github_comments_get(
	$repo_owner,
	$repo_name,
	$commit_id,
	$github_access_token
) {
	vipgoci_phpcs_log(
		'Fetching comments info from GitHub',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);

	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'commits/' .
		rawurlencode( $commit_id ) . '/' .
		'comments';

	// FIXME: Detect when GitHub returned with an error

	$commit_comments_tmp = json_decode(
		vipgoci_phpcs_github_fetch_url(
			$github_url,
			$github_access_token
		)
	);


	/*
 	 * Look through each comment, create an associative array
	 * of file:position out of all the comments, so any comment
	 * can easily be found.
	 */

	$commit_comments = array();

	foreach ( $commit_comments_tmp as $commit_comment ) {
		$commit_comments[
			$commit_comment->path . ':' . $commit_comment->position
		][] = $commit_comment;
	}

	return $commit_comments;
}


/*
 * Submit a comment on GitHub for a particular file,
 * line, and commit, using the access-token provided.
 */
function vipgoci_phpcs_github_comment_open(
	$repo_owner,
	$repo_name,
	$commit_id,
 	$github_access_token,
	$path,
	$position,
	$severity,
	$comment_str
) {

	vipgoci_phpcs_log(
		'About submit a comment to GitHub about an issue',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'filename' => $path,
			'position' => $position,
			'level' => $severity,
			'message' => $comment_str,
		)
	);

	$github_url =
		'https://api.github.com/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'commits/' .
		rawurlencode( $commit_id ) . '/' .
		'comments';


	$github_postfields = json_encode(
		array(
			'body'		=>
				'**' .
				ucfirst( strtolower(
					$severity
				)) .
				'**: ' .
				$comment_str,

			'path'		=> $path,
			'position'	=> $position,
		)
	);


	$ch = curl_init();

	curl_setopt( $ch, CURLOPT_URL, 			$github_url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 	1 );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 	20) ;
	curl_setopt( $ch, CURLOPT_USERAGENT, 		'automattic-github-review-client' );
	curl_setopt( $ch, CURLOPT_POST,			1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS,		$github_postfields );
	curl_setopt( $ch, CURLOPT_HEADERFUNCTION, 	'vipgoci_phpcs_curl_headers' );

	curl_setopt( $ch, CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_access_token )
	);

	$resp_data = curl_exec( $ch );

	$resp_headers = vipgoci_phpcs_curl_headers( null, null );

	if ( intval( $resp_headers[ 'status' ][0] ) !== 201 ) {
		if (
			( isset( $resp_headers[ 'retry-after' ] ) ) &&
			( intval( $resp_headers[ 'retry-after' ] ) > 0 )
		) {
			vipgoci_phpcs_log(
				'GitHub asked us to retry in ' .
				intval( $resp_headers[ 'retry-after' ] ) .
				' seconds -- waiting ... ',
				array()
			);

			sleep( intval( $resp_headers[ 'retry-after' ] ) + 1 );
		}

		else {
			vipgoci_phpcs_log(
				'GitHub reported an unknown error',
				array(
					'http_response_headers' => $resp_headers,
					'http_reponse_body'	=> $resp_data,
				)
			);
		}

	}

	curl_close( $ch );

	// FIXME: Detect errors

	/*
	 * GitHub asks that requests are made with at least one
	 * second wait in between -- guarantee that.
	 */
	sleep( 1 );

	return $resp_data;
}

