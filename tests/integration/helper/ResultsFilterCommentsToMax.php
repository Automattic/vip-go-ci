<?php
/**
 * Helper file for ResultsFilterCommentsToMaxTest.php
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * Mock function by returning data imitating review-comments
 * submitted to a particular pull request.
 *
 * Note that this function is itself tested by another test.
 *
 * @param array      $options    Options needed.
 * @param int        $pr_number  Pull request number.
 * @param null|array $filter     Filter to apply.
 *
 * @return array Mocked review comments, simplified values.
 */
function vipgoci_github_pr_reviews_comments_get_by_pr(
	array $options,
	int $pr_number,
	null|array $filter = array()
) :array {
	/*
	 * Safe to return empty array, see
	 * vipgoci_results_filter_comments_to_max().
	 */
	return array(
		array(),
		array(),
		array(),
	);
}

// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

