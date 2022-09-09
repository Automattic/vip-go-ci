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
 * @param string $issue_type  Type of result being processed; VIPGOCI_WPSCAN_PLUGIN or VIPGOCI_WPSCAN_THEME.
 * @param string $name_to_use Name to use in reports to identify the bot.
 *
 * @return string Returns beginning of comment.
 */
function vipgoci_wpscan_report_start(
	string $issue_type,
	string $name_to_use
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
		sprintf(
			VIPGOCI_WPSCAN_REPORT_START,
			vipgoci_output_html_escape( $name_to_use ),
			$comment_type
		) .
		"\n\r";

	vipgoci_markdown_comment_add_pagebreak(
		$comment_start
	);

	return $comment_start;
}

/**
 * Returns end of a WPScan API report comment.
 *
 * @param string $issue_type                Type of result being processed; VIPGOCI_WPSCAN_PLUGIN or VIPGOCI_WPSCAN_THEME.
 * @param string $wpscan_api_report_end_msg Message to append to end of WPScan API report.
 *
 * @return string Returns end of comment.
 */
function vipgoci_wpscan_report_end(
	string $issue_type,
	string $wpscan_api_report_end_msg
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

	return vipgoci_output_html_escape(
		str_replace(
			'%addon_type%',
			$comment_type,
			$wpscan_api_report_end_msg
		)
	) . "\n\r";
}

/**
 * Formats WPScan API results to submit to pull request.
 *
 * @param string $repo_owner Repository owner.
 * @param string $repo_name  Repository name.
 * @param string $commit_id  Commit-ID of current commit.
 * @param array  $issue      Array with issue details to report.
 * @param string $issue_type Type of result being processed; VIPGOCI_WPSCAN_PLUGIN or VIPGOCI_WPSCAN_THEME.
 * @param bool   $dry_mode   If WPScan API dry mode is enabled.
 *
 * @return string Formatted result.
 */
function vipgoci_wpscan_report_comment_format_result(
	string $repo_owner,
	string $repo_name,
	string $commit_id,
	array $issue,
	string $issue_type,
	bool $dry_mode = false
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
			'**Plugin URI**: ' . vipgoci_output_html_escape( $issue['details']['url'] ) . "\n";
	} elseif ( VIPGOCI_WPSCAN_THEME === $issue_type ) {
		$res .= ' Theme information' . "\n" .
			'**Theme Name**: ' . vipgoci_output_html_escape( $issue['message'] ) . "\n" .
			'**Theme URI**: ' . vipgoci_output_html_escape( $issue['details']['url'] ) . "\n";
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

		foreach ( $issue['details']['vulnerabilities'] as $vuln_item ) {
			$res .= '### &#x1f512; Security information' . "\n" . // Header markup and lock sign.
			'**Title**: ' . vipgoci_output_html_escape( $vuln_item['title'] ) . "\n" .
			'**Details**: ' . vipgoci_output_html_escape( VIPGOCI_WPSCAN_BASE_URL . '/vulnerability/' . $vuln_item['id'] ) . "\n";
		}
	}

	vipgoci_markdown_comment_add_pagebreak(
		$res
	);

	// If dry-run mode is enabled, report to IRC only. Temporary feature.
	if ( true === $dry_mode ) {
		vipgoci_log(
			'WPScan API found issues with addon',
			array(
				'msg'     => $issue['message'],
				'level'   => $issue['security'],
				'version' => $issue['details']['version_detected'],
				'latest'  => $issue['details']['latest_version'],
			),
			0,
			true
		);

		return '';
	}

	return $res;
}

