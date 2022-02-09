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
 * @param string $validation_message
 *
 * @return string
 */
function vipgoci_get_skipped_files_message( array $skipped, string $validation_message ): string {
	$body = PHP_EOL . '**' . VIPGOCI_SKIPPED_FILES . '**' . PHP_EOL . PHP_EOL;
	foreach ( $skipped['issues'] as $issue => $file ) {

		$body .= vipgoci_get_skipped_files_issue_message(
			$skipped['issues'][ $issue ],
			$validation_message
		);
	}

	$body .= PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG;

	return $body;
}

/**
 * @param string $issue_type
 * @param int $skip_files_lines_limit
 * Maximum number of lines exceeded (15000)
 *
 * @return string
 */
function vipgoci_skip_file_get_validation_message_prefix( string $issue_type, int $skip_files_lines_limit ): string {
	return sprintf(
		VIPGOCI_VALIDATION[ $issue_type ] . ':%s - ',
		$skip_files_lines_limit,
		PHP_EOL
	);
}

/**
 * @param array $affected_files
 * @param string $validation_message
 *
 * @return string
 */
function vipgoci_get_skipped_files_issue_message(
	array $affected_files,
	string $validation_message
): string {
	$affected_files = implode( PHP_EOL . ' - ', $affected_files );

	return sprintf(
		'%s%s',
		$validation_message,
		$affected_files
	);
}

/**
 * @param array $pr_issues_results
 * @param array $comments
 * @param string $validation_message
 *
 * Removes skipped files from the results list
 * when there are previous comments
 * preventing duplicated comments
 *
 * @return array $pr_issues_result
 */
function vipgoci_skip_file_check_previous_pr_comments( array $pr_issues_results, array $comments, string $validation_message ): array {
	/**
	 * If there is no previous comments in this PR, return
	 */
	if ( 0 === count( $comments ) || 0 === $pr_issues_results['total'] ) {
		return $pr_issues_results;
	}

	$skipped_files = vipgoci_get_skipped_files_from_pr_comments( $comments, $validation_message );

	$result = [ 'issues' => [ VIPGOCI_VALIDATION_MAXIMUM_LINES => array() ], 'total' => 0 ];

	/**
	 * Iterates the list of files that reached the lines limit in this scan
	 * For each file, verifies if there's a previous comment about it
	 * If so, prevent a new comment about the same file
	 */
	foreach ( $pr_issues_results['issues'][ VIPGOCI_VALIDATION_MAXIMUM_LINES ] as $file ) {
		if ( in_array( $file, $skipped_files, true ) ) {
			continue;
		}

		$result['issues'][ VIPGOCI_VALIDATION_MAXIMUM_LINES ][] = $file;
		$result['total'] ++;
	}

	return $result;
}

/**
 * @param array $comments
 * @param string $validation_message
 * Iterates all the comments to check the files affected by the skip files due max lines limit reached
 * returns array of files
 *
 * @return array
 */
function vipgoci_get_skipped_files_from_pr_comments( array $comments, string $validation_message ): array {
	$skipped_files = array();

	foreach ( $comments as $comment ) {
		$files         = vipgoci_get_skipped_files_from_comment( $comment, $validation_message );
		$skipped_files = array_merge( $skipped_files, $files );
	}

	return $skipped_files;
}

/**
 * @param stdClass $comment
 * @param string $validation_message_prefix
 *
 * @return string[]
 */
function vipgoci_get_skipped_files_from_comment( stdClass $comment, string $validation_message_prefix ): array {
	/**
	 * Checks if the comment contains skipped-files
	 * if it is not, ignore
	 */
	if ( false === strpos( $comment->body, 'skipped-files' ) ) {
		return array();
	}

	$skipped_files_comment = vipgoci_get_skipped_files_message_from_comment( $comment->body, $validation_message_prefix );

	if ( '' === $skipped_files_comment ) {
		return array();
	}

	$files = explode( PHP_EOL . ' - ', $skipped_files_comment );

	// This return is to be compatible with php 8.0
	return empty( $files[0] ) ? array() : $files;
}

/**
 * @param string $comment
 * @param string $validation_message_prefix
 * Removes any extra noise before/after the skipped files message in a comment
 * This function implementation tends to bee defensive to avoid Errors in php 8.0
 *
 * @return string
 */
function vipgoci_get_skipped_files_message_from_comment( string $comment, string $validation_message_prefix ): string {

	if ( false === $prefix_pos = strpos( $comment, $validation_message_prefix ) ) {
		return '';
	}
	$message_start_pos = $prefix_pos + strlen( $validation_message_prefix );

	if ( false === $message_end_pos = strpos( $comment, PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG, $message_start_pos ) ) {
		return '';
	}

	return ( false === $message = substr( $comment, $message_start_pos, $message_end_pos - $message_start_pos ) ) ? '' : $message;
}
