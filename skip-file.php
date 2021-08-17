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
 * @param $skipped
 *
 * @return string
 */
function vipgoci_get_skipped_files_message( array $skipped ): string
{
	$body = PHP_EOL . '**' . VIPGOCI_SKIPPED_FILES . '**' . PHP_EOL;
	foreach ( $skipped[ 'issues' ] as $issue => $file ) {
		$body .= vipgoci_get_skipped_files_issue_message(
			$skipped[ 'issues' ][ $issue ],
			$issue
		);
	}

	return $body;
}

/**
 * @param array $affected_files
 * @param string $issue_type
 *
 * Get Markdown Skipped File error message
 * @return string
 */
function vipgoci_get_skipped_files_issue_message(
	array $affected_files,
	string $issue_type,
	int $max_lines = 15000
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
