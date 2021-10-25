<?php
declare( strict_types=1 );

/**
 * Logic related to skip files
 */
/**
 * @param array $skipped
 * @param array $validation
 *
 * @return array
 */
function vipgoci_get_skipped_files( array $skipped, array $validation ): array {
	$skipped['issues'] = array_merge_recursive( $skipped['issues'], $validation['issues'] );
	$skipped['total']  += $validation['total'];

	return $skipped;
}

/**
 * @param array $commit_skipped_files
 * @param array $validation
 * @param int $pr_number
 */
function vipgoci_set_skipped_file(
	array &$commit_skipped_files,
	array $validation,
	int $pr_number
): void {
	$commit_skipped_files[ $pr_number ] = vipgoci_get_skipped_files(
		$commit_skipped_files[ $pr_number ],
		$validation
	);
}

/**
 * @param array $prs_implicated
 * @param array $commit_skipped_files
 * @param array $validation
 */
function vipgoci_set_prs_implicated_skipped_files(
	array $prs_implicated,
	array &$commit_skipped_files,
	array $validation
): void {
	foreach ( $prs_implicated as $pr_item ) {
		vipgoci_set_skipped_file( $commit_skipped_files, $validation, $pr_item->number );
	}
}

/**
 * @param array $skipped
 * @param int $skip_large_files_limit
 *
 * @return string
 */
function vipgoci_get_skipped_files_message( array $skipped, int $skip_large_files_limit ): string {
	$body = PHP_EOL . '**' . VIPGOCI_SKIPPED_FILES . '**' . PHP_EOL . PHP_EOL;
	foreach ( $skipped['issues'] as $issue => $file ) {
		$body .= vipgoci_get_skipped_files_issue_message(
			$skipped['issues'][ $issue ],
			$issue,
			$skip_large_files_limit
		);
	}

	$body .= PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG;

	return $body;
}

/**
 * @param array $affected_files
 * @param string $issue_type
 * @param int $max_lines
 *
 * Get Markdown Skipped File error message
 *
 * @return string
 */
function vipgoci_get_skipped_files_issue_message(
	array $affected_files,
	string $issue_type,
	int $max_lines
): string {
	$affected_files     = implode( PHP_EOL . ' - ', $affected_files );
	$validation_message = sprintf(
		VIPGOCI_VALIDATION[ $issue_type ],
		$max_lines
	);

	return sprintf(
		'%s:%s - %s',
		$validation_message,
		PHP_EOL,
		$affected_files
	);
}

/**
 * @param array $pr_issues_results
 * @param array $comments
 *
 * Removes skipped files from the results list
 * when there are previous comments
 * preventing duplicated comments
 *
 * @return array
 */
function vipgo_skip_file_check_previous_pr_comments( array $pr_issues_results = [], array $comments = [] ): array {
	/**
	 * If there is no previous comments in this PR, return
	 */
	if ( 0 === count( $comments ) ) {
		return $pr_issues_results;
	}

	$large_files = vipgo_get_large_files_from_pr_comments( $comments );
	$result      = [ 'issues' => [ 'max-lines' => [] ], 'total' => 0 ];

	/**
	 * Iterates the list of files that reached the lines limit in this scan
	 * For each file, verifies if there's a previous comment about it
	 * If so, prevent a new comment about the same file
	 */
	foreach ( $pr_issues_results['issues']['max-lines'] as $file ) {
		if ( in_array( $file, $large_files, true ) ) {
			continue;
		}

		$result['issues']['max-lines'][] = $file;
		$result['total'] ++;
	}

	return $result;
}

/**
 * @param array $comments
 * Iterates all the comments to check the files affected by the skip files due max lines limit reached
 * returns array of files
 *
 * @return array
 * @todo add unit tests
 */
function vipgo_get_large_files_from_pr_comments( array $comments ): array {
	$large_files = [];

	foreach ( $comments as $comment ) {
		/**
		 * Checks if the comment contains skipped-files
		 * if it is not, ignore
		 */
		if ( false === strpos( $comment->body, 'skipped-files' ) ) {
			continue;
		}
		$files       = vipgo_get_large_files_from_comment( $comment );
		$large_files = array_merge( $large_files, $files );
	}

	return $large_files;
}

/**
 * @param $comment
 *
 * @return string[]
 */
function vipgo_get_large_files_from_comment( $comment ): array {
	$prefix = '):';
	$suffix = strlen( PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG );

	if ( false === $comment = substr(
			$comment->body,
			strpos( $comment->body, $prefix ) + 6,
			- $suffix
		) ) {
		return [];
	}

	$files = explode( "\n - ", $comment );

	// This return is to be compatible with php 8.0
	return empty( $files[0] ) ? array() : $files;
}
