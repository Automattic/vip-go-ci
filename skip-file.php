<?php
declare(strict_types=1);

/**
 * Logic related to skip files
 */
/**
 * @param array $skipped
 * @param array $validation
 * @return array
 */
function vipgoci_get_skipped_files( array $skipped, array $validation ): array
{
	$skipped[ 'issues' ] = array_merge_recursive( $skipped[ 'issues' ], $validation[ 'issues' ] );
	$skipped[ 'total' ] += $validation[ 'total' ];

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
function vipgoci_get_skipped_files_message( array $skipped, int $skip_large_files_limit ): string
{
	$body = PHP_EOL . '**' . VIPGOCI_SKIPPED_FILES . '**' . PHP_EOL . PHP_EOL;
	foreach ( $skipped[ 'issues' ] as $issue => $file ) {
		$body .= vipgoci_get_skipped_files_issue_message(
			$skipped[ 'issues' ][ $issue ],
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
	$affected_files = implode( PHP_EOL . ' - ', $affected_files );
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
  * IF the file
 *  Is modified in THIS very commit
 *  Or New in THIS very commit
 *  Or Doesn't exist in the previous comments $reached_limit_files
 *      Let the bot post the comment
 * ELSE:
 *     REMOVE FROM the $results
 *
 * @param array $pr_issues_results
 * @param array $comments
 * @param array $modified_files
 */
function vipgo_skip_file_check_pr_comments(array $pr_issues_results = [], array $comments = [], array $modified_files = [])
{
	// git diff-tree --no-commit-id --name-only -r 773b2bc0b46d0cf6fde031af79e957d3a0cc9172
	/**
	 * If there is no PR previous comments, return
	 */
	if ( 0 === count( $comments ) ) {
		return $pr_issues_results;
	}

	/**
	 * List of modified files in this very COMMIT
	 * @todo get_list
	 */
	$reached_limit_files = [];

	/**
	 * Iterates all the comments to check the files affected by the skip files due max lines limit reached
	 * @todo: move the comment iteration logic to its own function
	 */
	foreach ( $comments as $comment ) {
		/**
		 * @todo use options limit values and constant
		 *  sprintf(VIPGOCI_VALIDATION[VIPGOCI_VALIDATION_MAXIMUM_LINES], $pr_number, $option  limit)
		 * $prefix = Maximum number of lines exceeded (15000):
		 * $suffix = PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG
		 * @todo: move the getting files logic to its own function
		 */
		$prefix = '):';
		$suffix = strlen( PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG );
		$files = explode(
			"\n - ",
			substr(
				$comment->body,
				strpos( $comment->body, $prefix ) + 6,
				-$suffix
			),
		);

		$reached_limit_files = array_merge( $reached_limit_files, $files );
	}

	/**
	 * This could also be a foreach
	 * @todo cleanup: this is the only bit that will stay in this function
	 *
	 * Existe na lista novo e nao tem na lista de comentarios anterioes!
	 * Existe na lista nova e existe na lista de comentarios anteriores
	 *      Faz parte de modificado?
	 *          SIM: mantem
     *          NAO: REMOVE
	 */
	$total_issues = $pr_issues_results['total'];
	for ( $i = 0; $i < $total_issues; $i++ ) {
		$file = $pr_issues_results['issues']['max-lines'][ $i ];

		/**
		 * Verifies if there's a message about skipped files in previous PR comments.
		 * If there's not, keep the message to be posted
		 */
		if ( ! in_array( $file, $reached_limit_files ) ) {
			continue;
		}

		/**
		 * If there is: Verify if it's modified in this very commit
		 *
		 * If it is modified, keep the message to be posted
		 * @todo: get $modified_files
		 */
		if ( in_array( $file, $modified_files ) ) {
			continue;
		}
		/**
		 * Otherwise, remove it from the issues
		 */
		/**
		 *  @todo implement removing file from the $results
		 */
		unset( $pr_issues_results['issues']['max-lines'][ $i ] );
		$pr_issues_results['total']--;
	}

	$pr_issues_results['issues']['max-lines'] = array_values( $pr_issues_results['issues']['max-lines'] );

	return $pr_issues_results;
}
