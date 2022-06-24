<?php
/**
 * PHP lint reporting logic for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Returns beginning of a PHP lint report comment.
 *
 * @param string $repo_owner Repository owner.
 * @param string $repo_name  Repository name.
 * @param string $commit_id  Current commit-ID.
 *
 * @return string Beginning of comment.
 */
function vipgoci_lint_report_comment_start(
	string $repo_owner,
	string $repo_name,
	string $commit_id
) :string {
	$comment_start =
		'**' . VIPGOCI_SYNTAX_ERROR_STR . '**' .
		"\n\r\n\r" .
		'PHP linting performed at commit ' . vipgoci_output_html_escape( $commit_id ) .
		' ([view code](' .
		VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'tree/' .
		rawurlencode( $commit_id ) .
		')).' .
		"\n\r";

	vipgoci_markdown_comment_add_pagebreak(
		$comment_start
	);

	return $comment_start;
}

/**
 * Formats PHP lint results for submission to pull request.
 *
 * @param string $repo_owner    Repository owner.
 * @param string $repo_name     Repository name.
 * @param string $commit_id     Commit-ID of current commit.
 * @param string $file_name     Name of file which result belongs to.
 * @param int    $file_line     Line number in file which result belongs to.
 * @param string $issue_level   Issue level; error, warning or info.
 * @param string $issue_message Details of issue.
 *
 * @return string Formatted result.
 */
function vipgoci_lint_report_comment_format_result(
	string $repo_owner,
	string $repo_name,
	string $commit_id,
	string $file_name,
	int $file_line,
	string $issue_level,
	string $issue_message
) :string {
	return '**' .
		// Add level (error, warning).
		vipgoci_output_html_escape(
			ucfirst(
				strtolower( $issue_level )
			)
		) .
		'**' .
		': ' .
		// Then the message.
		str_replace(
			'\'',
			'`',
			$issue_message // Is escaped lint-scan.php.
		) .
		"\n\r\n\r" .
		// And finally an URL to the issue.
		VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'blob/' .
			rawurlencode( $commit_id ) . '/' .
			rawurlencode( $file_name ) . '#L' .
			rawurlencode( (string) $file_line ) .
			"\n\r";
}


