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
	} else {
		vipgoci_sysexit(
			'Internal error: Invalid $issue_type in ' . __FUNCTION__,
			array(
				'issue_type' => $issue_type,
			)
		);

		return ''; // For unit test.
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
	} else {
		vipgoci_sysexit(
			'Internal error: Invalid $issue_type in ' . __FUNCTION__,
			array(
				'issue_type' => $issue_type,
			)
		);

		return ''; // For unit-test.
	}

	return '##### Incorrect ' . $comment_type . 's? [Learn how to prevent false-positive matches.](https://docs.wpvip.com/technical-references/codebase-manager/#h-preventing-false-positive-plugin-matches)' . // @todo: Should be configurable.
		"\n\r";
}

/**
 * Returns severity number as readable string, such as 'MEDIUM'.
 *
 * @param float $severity Severity rating number to be rendered as a string.
 *
 * @todo: Missing test.
 *
 * @return string
 */
function vipgoci_wpscan_report_format_severity(
	float $severity
) :string {

	foreach ( VIPGOCI_WPSCAN_SEVERITY_RATING as $severity_info ) {
		if (
			( $severity >= $severity_info['value_low'] ) &&
			( $severity <= $severity_info['value_high'] )
		) {
			return $severity_info['severity'];
		}
	}

	return 'UNKNOWN';
}

/**
 * Formats WPScan API results to submit to pull request.
 *
 * @param string $repo_owner Repository owner.
 * @param string $repo_name  Repository name.
 * @param string $commit_id  Commit-ID of current commit.
 * @param array  $issue      Array with issue details to report.
 * @param string $issue_type Type of result being processed; VIPGOCI_WPSCAN_PLUGIN or VIPGOCI_WPSCAN_THEME.
 *
 * @return string Formatted result.
 */
function vipgoci_wpscan_report_comment_format_result(
	string $repo_owner,
	string $repo_name,
	string $commit_id,
	array $issue,
	string $issue_type
) :string {
	$res = '## &#x2139;&#xfe0f;&#x20; '; // Header markup and information sign.

	// Determine if obsolete or vulnerable.
	if ( VIPGOCI_WPSCAN_OBSOLETE === $issue['security'] ) {
		$res .= 'Obsolete';
	} elseif ( VIPGOCI_WPSCAN_VULNERABLE === $issue['security'] ) {
		$res .= 'Vulnerable';
	} else {
		vipgoci_sysexit(
			'Internal error: Invalid $issue[security] in ' . __FUNCTION__,
			array(
				'$issue[security]' => $issue['security'],
			)
		);
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
	} else {
		vipgoci_sysexit(
			'Internal error: Invalid $issue_type in ' . __FUNCTION__,
			array(
				'$issue_type' => $issue_type,
			)
		);
	}

	// Construct URL to the relevant file.
	$view_code_link = '([view code](' .
		VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'tree/' .
		rawurlencode( $commit_id ) . '/' .
		$issue['details']['installed_location'] . // Internally constructed.
		')).';

	$res .=
		'**Installed location**: `' . vipgoci_output_html_escape( $issue['details']['installed_location'] ) . '` ' . $view_code_link . "\n" .
		'**Version observed**: ' . vipgoci_output_sanitize_version_number( $issue['details']['version_detected'] ) . "\n" .
		'**Latest version available**: ' . vipgoci_output_sanitize_version_number( $issue['details']['latest_version'] ) . "\n" .
		'**Latest version download URI**: ' . vipgoci_output_html_escape( $issue['details']['latest_download_uri'] ) . "\n";

	if ( ! empty( $issue['details']['vulnerabilities'] ) ) {
		$res .= "\n\r";

		$severity = 4.5; // @todo

		foreach ( $issue['details']['vulnerabilities'] as $vuln_item ) {
			$res .= '### &#x1f512; Security information' . "\n" . // Header markup and lock sign.
			'**Title**: ' . vipgoci_output_html_escape( $vuln_item['title'] ) . "\n" .
			'**Details**: ' . vipgoci_output_html_escape( VIPGOCI_WPSCAN_BASE_URL . '/vulnerability/' . $vuln_item['id'] ) . "\n" .
			'**Severity**:  ' . vipgoci_output_html_escape( (string) $severity ) . '/10 (' . vipgoci_output_html_escape( vipgoci_wpscan_report_format_severity( $severity ) ) . ") \n";
		}
	}

	vipgoci_markdown_comment_add_pagebreak(
		$res
	);

	return $res;
}

