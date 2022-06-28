<?php
/**
 * WPScan reporting logic for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Returns beginning of a WPScan API report comment.
 *
 * @param string $issue_type Type of result being processed; VIPGOCI_WPSCAN_PLUGIN or VIPGOCI_WPSCAN_THEME.
 *
 * @return string Returns beginning of comment.
 */
function vipgoci_wpscan_report_start(
	string $issue_type
) :string {
	if ( VIPGOCI_WPSCAN_PLUGIN === $issue_type ) {
		$comment_type = 'plugin';
	} elseif ( VIPGOCI_WPSCAN_THEME === $issue_type ) {
		$comment_type = 'theme';
	}

	$comment_start =
		'# ' . VIPGOCI_WPSCAN_API_ERROR .
		"\n\r" .
		'Automated scanning has identified one or more insecure or obsolete ' . $comment_type . 's being submitted in this pull request. Updating the ' . $comment_type . 's before merging into the target branch is strongly recommended.' .
		"\n\r";

	vipgoci_markdown_comment_add_pagebreak(
		$comment_start
	);

	return $comment_start;
}

/**
 * Returns end of a WPScan API report comment.
 *
 * @param string $issue_type Type of result being processed; VIPGOCI_WPSCAN_PLUGIN or VIPGOCI_WPSCAN_THEME.
 *
 * @return string Returns end of comment.
 */
function vipgoci_wpscan_report_end(
	string $issue_type
) :string {
	if ( VIPGOCI_WPSCAN_PLUGIN === $issue_type ) {
		$comment_type = 'plugin';
	} elseif ( VIPGOCI_WPSCAN_THEME === $issue_type ) {
		$comment_type = 'theme';
	}

	return '##### Incorrect ' . $comment_type . 's? [Learn how to prevent false-positive matches.](https://docs.wpvip.com/technical-references/codebase-manager/#h-preventing-false-positive-plugin-matches)' . // @todo: Should be configurable.
		"\n\r";
}

/**
 * Formats WPScan API results to submit to pull request.
 *
 * @param string $repo_owner Repository owner.
 * @param string $repo_name  Repository name.
 * @param string $commit_id  Commit-ID of current commit.
 * @param string $file_name  Name of file.
 * @param int    $file_line  Line number in file.
 * @param array  $issue      Array with issue details to report.
 * @param string $issue_type Type of result being processed; VIPGOCI_WPSCAN_PLUGIN or VIPGOCI_WPSCAN_THEME.
 *
 * @return string Formatted result.
 */
function vipgoci_wpscan_report_comment_format_result(
	string $repo_owner,
	string $repo_name,
	string $commit_id,
	string $file_name,
	int $file_line,
	array $issue,
	string $issue_type
) :string {
	$res = '## &#x2139;&#xfe0f;&#x20; '; // Header markup and information sign.

	// Determine if obsolete or vulnerable.
	if ( VIPGOCI_WPSCAN_OBSOLETE === $issue['security'] ) {
		$res .= 'Obsolete';
	} elseif ( VIPGOCI_WPSCAN_VULNERABLE === $issue['security'] ) {
		$res .= 'Vulnerable';
	}

	// Type of addon.
	if ( VIPGOCI_WPSCAN_PLUGIN === $issue_type ) {
		$res .= ' Plugin information' . "\n" .
			'**Plugin Name**: ' . vipgoci_output_html_escape( $issue['message'] ) . "\n" .
			'**Plugin URI**: ' . vipgoci_output_html_escape( $issue['details']['plugin_uri'] ) . "\n";
	} elseif ( VIPGOCI_WPSCAN_THEME === $issue_type ) {
		$res .= ' Theme information' . "\n" .
			'**Theme Name**: ' . vipgoci_output_html_escape( $issue['message'] ) . "\n" .
			'**Theme URI**: ' . vipgoci_output_html_escape( $issue['details']['theme_uri'] ) . "\n";
	}

	$res .=
		// @todo: Add link to file and line.
		'**Installed location**: `' . vipgoci_output_html_escape( $issue['details']['installed_location'] ) . "`\n" .
		'**Version observed**: ' . vipgoci_output_sanitize_version_number( $issue['details']['version_detected'] ) . "\n" .
		'**Latest version available**: ' . vipgoci_output_sanitize_version_number( $issue['details']['latest_version'] ) . "\n" .
		'**Latest version download URI**: ' . vipgoci_output_html_escape( $issue['details']['latest_download_uri'] ) . "\n";

	if ( ! empty( $issue['details']['vulnerabilities'] ) ) {
		$res .= "\n\r";

		foreach ( $issue['details']['vulnerabilities'] as $vuln_item ) {
			$res .= '### &#x1f512; Security information' . "\n" . // Header markup and lock sign.
			'**Title**: ' . vipgoci_output_html_escape( $vuln_item['title'] ) . "\n" .
			'**Details**: ' . vipgoci_output_html_escape( VIPGOCI_WPSCAN_BASE_URL . '/vulnerability/' . $vuln_item['id'] ) . "\n" .
			'**Severity**:  6.1/10 (MEDIUM)' . "\n"; // @todo: Format severity, available in $issue['severity'].
		}
	}

	vipgoci_markdown_comment_add_pagebreak(
		$res
	);

	return $res;
}

