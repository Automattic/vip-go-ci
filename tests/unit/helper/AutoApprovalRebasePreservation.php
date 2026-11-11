<?php
/**
 * Isolate existing file-policy mutations when testing verified rebase preservation.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Return changed PHP files for two PRs; neither qualifies under file-type rules.
 *
 * @param array  $options Unused fixture argument.
 * @param string $commit Unused fixture argument.
 * @param array  $skipped Unused fixture argument.
 * @param mixed  ...$flags Unused fixture argument.
 * @return array Fixture response.
 */
function vipgoci_github_files_affected_by_commit( array $options, string $commit, array &$skipped, mixed ...$flags ): array {
	return array(
		7 => array( 'file.php' ),
		8 => array( 'file.php' ),
	);
}

/**
 * No auto-approval labels are present.
 *
 * @param mixed ...$arguments Unused fixture argument.
 * @return bool Fixture response.
 */
function vipgoci_github_pr_labels_get( mixed ...$arguments ): bool {
	return false;
}

/**
 * Each PR has an existing scanner approval that file policy would dismiss.
 *
 * @param string $owner Unused fixture argument.
 * @param string $repo Unused fixture argument.
 * @param int    $pr Pull request number.
 * @param mixed  ...$arguments Unused fixture argument.
 * @return array Fixture response.
 */
function vipgoci_github_pr_reviews_get( string $owner, string $repo, int $pr, mixed ...$arguments ): array {
	return array( (object) array( 'id' => $pr ) );
}

/**
 * Record attempted dismissals instead of sending them.
 *
 * @param string $owner Unused fixture argument.
 * @param string $repo Unused fixture argument.
 * @param int    $pr Pull request number.
 * @param mixed  ...$arguments Unused fixture argument.
 * @return void
 */
function vipgoci_github_pr_review_dismiss( string $owner, string $repo, int $pr, mixed ...$arguments ): void {
	$GLOBALS['rebase_policy_dismissals'][] = $pr;
}

/**
 * Suppress counters and diagnostic output in this isolated policy test.
 *
 * @param mixed ...$arguments Unused fixture argument.
 * @return void
 */
function vipgoci_counter_report( mixed ...$arguments ): void {}

/**
 * Suppress diagnostic output without loading other runtime dependencies.
 *
 * @param mixed ...$arguments Unused fixture argument.
 * @return void
 */
function vipgoci_log( mixed ...$arguments ): void {}
