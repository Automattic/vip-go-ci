<?php
/**
 * Skip-file functionality.
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

/**
 * Get skipped files.
 *
 * @param array $skipped    Array of skipped items.
 * @param array $validation Message used to indicate file was skipped.
 *
 * @return array Skipped items.
 */
function vipgoci_get_skipped_files(
	array $skipped,
	array $validation
): array {
	$skipped['issues'] = array_merge_recursive(
		$skipped['issues'],
		$validation['issues']
	);

	$skipped['total'] += $validation['total'];

	return $skipped;
}

/**
 * Add indication that specified files are skipped.
 *
 * @param array $commit_skipped_files Skipped files.
 * @param array $validation           Message used to indicate file was skipped.
 * @param int   $pr_number            Pull request number.
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
 * Skip files for pull requests implicated by commit.
 *
 * @param array $prs_implicated       Pull requests implicated.
 * @param array $commit_skipped_files Files skipped.
 * @param array $validation           Message used to indicate file was skipped.
 */
function vipgoci_set_prs_implicated_skipped_files(
	array $prs_implicated,
	array &$commit_skipped_files,
	array $validation
): void {
	foreach ( $prs_implicated as $pr_item ) {
		vipgoci_set_skipped_file(
			$commit_skipped_files,
			$validation,
			$pr_item->number
		);
	}
}

/**
 * Generate skipped messages for files specified.
 *
 * @param array  $skipped            Skipped files array.
 * @param string $validation_message Message used to indicate file was skipped.
 *
 * @return string Message indicating skipped files.
 */
function vipgoci_get_skipped_files_message(
	array $skipped,
	string $validation_message
): string {
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
 * Return with a message explaining why a file is skipped.
 *
 * @param string $issue_type             The type of issue.
 * @param int    $skip_files_lines_limit The maximum number of lines allowed.
 *
 * @return string Message indicating file was skipped.
 */
function vipgoci_skip_file_get_validation_message_prefix(
	string $issue_type,
	int $skip_files_lines_limit
): string {
	return sprintf(
		VIPGOCI_VALIDATION[ $issue_type ] . ':%s - ',
		$skip_files_lines_limit,
		PHP_EOL
	);
}

/**
 * Print string indicating files were skipped.
 *
 * @param array  $affected_files      Files skipped.
 * @param string $validation_message  Message used to indicate file was skipped.
 *
 * @return string Message and files skipped.
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
 * Removes skipped files from the results list
 * when there are previous comments
 * preventing duplicated comments
 *
 * @param array  $pr_issues_results  Results list.
 * @param array  $comments           Previous comments to iterate.
 * @param string $validation_message Message used to indicate file was skipped.
 *
 * @return array $pr_issues_result   Array of results after processing.
 */
function vipgoci_skip_file_check_previous_pr_comments(
	array $pr_issues_results,
	array $comments,
	string $validation_message
): array {
	/**
	 * If there is no previous comments in this PR, return
	 */
	if (
		( 0 === count( $comments ) ) ||
		( 0 === $pr_issues_results['total'] )
	) {
		return $pr_issues_results;
	}

	$skipped_files = vipgoci_get_skipped_files_from_pr_comments(
		$comments,
		$validation_message
	);

	$result = array(
		'issues' => array(
			VIPGOCI_VALIDATION_MAXIMUM_LINES => array(),
		),
		'total'  => 0,
	);

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
 * Iterates all the comments to check the files affected by the skip files
 * due to max lines limit reached.
 *
 * @param array  $comments           Comments to iterate.
 * @param string $validation_message Message used to indicate file was skipped.
 *
 * @return array Array of files.
 */
function vipgoci_get_skipped_files_from_pr_comments( array $comments, string $validation_message ): array {
	$skipped_files = array();

	foreach ( $comments as $comment_body ) {
		$files = vipgoci_get_skipped_files_from_comment(
			$comment_body,
			$validation_message
		);

		$skipped_files = array_merge( $skipped_files, $files );
	}

	return $skipped_files;
}

/**
 * Extract files that were skipped from comment.
 *
 * @param string $comment_body              Comment to process.
 * @param string $validation_message_prefix Message used to indicate file was skipped.
 *
 * @return array Files skipped.
 */
function vipgoci_get_skipped_files_from_comment(
	string $comment_body,
	string $validation_message_prefix
): array {
	/**
	 * Checks if the comment contains skipped-files
	 * if it is not, ignore.
	 */
	if ( false === strpos( $comment_body, 'skipped-files' ) ) {
		return array();
	}

	$skipped_files_comment = vipgoci_get_skipped_files_message_from_comment(
		$comment_body,
		$validation_message_prefix
	);

	if ( '' === $skipped_files_comment ) {
		return array();
	}

	$files = explode(
		PHP_EOL . ' - ',
		$skipped_files_comment
	);

	// This return is to be compatible with PHP 8.0.
	return empty( $files[0] ) ? array() : $files;
}

/**
 * Removes any extra noise before/after the skipped files message in a comment.
 *
 * @param string $comment                   Comment to process.
 * @param string $validation_message_prefix Message used to indicate file was skipped.
 *
 * @return string Comment after processing.
 */
function vipgoci_get_skipped_files_message_from_comment(
	string $comment,
	string $validation_message_prefix
): string {
	$prefix_pos = strpos(
		$comment,
		$validation_message_prefix
	);

	if ( false === $prefix_pos ) {
		return '';
	}

	$message_start_pos = $prefix_pos + strlen( $validation_message_prefix );

	$message_end_pos = strpos(
		$comment,
		PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG,
		$message_start_pos
	);

	if ( false === $message_end_pos ) {
		return '';
	}

	$message = substr(
		$comment,
		$message_start_pos,
		$message_end_pos - $message_start_pos
	);

	if ( '' === $message ) {
		$message = '';
	}

	return $message;
}
