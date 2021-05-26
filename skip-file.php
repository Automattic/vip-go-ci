<?php
/**
 * Logic related to skip files
 */
/**
 * @param array $skipped
 * @param array $validation
 * @return array
 */
function vipgoci_get_skipped_files( $skipped, $validation ) {
	$skipped[ 'issues' ] = array_merge_recursive( $skipped[ 'issues' ], $validation[ 'issues' ] );
	$skipped[ 'total' ] += $validation[ 'total' ];

	return $skipped;
}

/**
 * @param array $commit_skipped_files
 * @param array $validation
 * @param int $pr_number
 */
function vipgoci_set_skipped_file( &$commit_skipped_files, $validation, $pr_number )
{
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
function vipgoci_set_prs_implicated_skipped_files( $prs_implicated, &$commit_skipped_files, $validation )
{
	foreach ( $prs_implicated as $pr_item ) {
		vipgoci_set_skipped_file( $commit_skipped_files, $validation, $pr_item->number );
	}
}

/**
 * @param $skipped
 *
 * @return string
 */
function vipgoci_get_skipped_files_message( $skipped ) {
	$body = '****' . PHP_EOL . '**' . VIPGOCI_SKIPPED_FILES . '**' . PHP_EOL;
	foreach ( $skipped[ 'issues' ] as $issue => $file ) {
		$body .= vipgoci_get_skipped_files_issue_message(
			$skipped[ 'issues' ][ $issue ],
			$issue
		);
	}

	return $body;
}

/**
 * @param string $affected_files
 * @param string $issue_type
 *
 * Get Markdown Skipped File error message
 *
 * @return string
 */
function vipgoci_get_skipped_files_issue_message( $affected_files, $issue_type ) {
	$affected_files = implode( PHP_EOL . ' -', $affected_files );

	return sprintf(
		'%s:%s -%s',
		VIPGOCI_VALIDATION[ $issue_type ],
		PHP_EOL,
		$affected_files
	);
}
