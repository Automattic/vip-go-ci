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
 * @param string $issue_type  Type of result being processed; VIPGOCI_ADDON_PLUGIN or VIPGOCI_ADDON_THEME.
 * @param string $name_to_use Name to use in reports to identify the bot.
 *
 * @return string Returns beginning of comment.
 */
function vipgoci_wpscan_report_start(
	string $issue_type,
	string $name_to_use
) :string {
	if ( VIPGOCI_ADDON_PLUGIN === $issue_type ) {
		$comment_type = 'plugin';
	} elseif ( VIPGOCI_ADDON_THEME === $issue_type ) {
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
			vipgoci_output_markdown_escape( $name_to_use ),
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
 * @param string $issue_type                Type of result being processed; VIPGOCI_ADDON_PLUGIN or VIPGOCI_ADDON_THEME.
 * @param string $wpscan_api_report_end_msg Message to append to end of WPScan API report.
 *
 * @return string Returns end of comment.
 */
function vipgoci_wpscan_report_end(
	string $issue_type,
	string $wpscan_api_report_end_msg
) :string {
	if ( VIPGOCI_ADDON_PLUGIN === $issue_type ) {
		$comment_type = 'plugin';
	} elseif ( VIPGOCI_ADDON_THEME === $issue_type ) {
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
		vipgoci_output_markdown_escape(
			str_replace(
				'%addon_type%',
				$comment_type,
				$wpscan_api_report_end_msg
			),
			array( '*', '(', ')', '[', ']' )
		)
	) . "\n\r";
}

/**
 * Returns human-readable CVSS severity ranking.
 *
 * @param float|int $cvss_score CVSS score.
 *
 * @return string Severity ranking.
 */
function vipgoci_wpscan_report_format_cvss_score(
	float|int $cvss_score
) :string {
	// Round to one precision point.
	$cvss_score = round( (float) $cvss_score, 1 );

	/*
	 * Get CVSS ranking array, sort reverse by key.
	 */
	$cvss_ranking = VIPGOCI_WPSCAN_CVSS_RANKING;

	krsort( $cvss_ranking );

	/*
	 * Loop through CVSS ranking, try
	 * to determine ranking for value.
	 */
	foreach (
		$cvss_ranking as $ranking_item
	) {
		if (
			( $cvss_score >= (float) $ranking_item['lower_value'] ) &&
			( $cvss_score <= (float) $ranking_item['upper_value'] )
		) {
			return $ranking_item['ranking'];
		}
	}

	// Failure.
	return 'UNKNOWN';
}

/**
 * Formats WPScan API results to submit to pull request.
 *
 * @param string $repo_owner Repository owner.
 * @param string $repo_name  Repository name.
 * @param string $commit_id  Commit-ID of current commit.
 * @param array  $issue      Array with issue details to report.
 * @param string $issue_type Type of result being processed; VIPGOCI_ADDON_PLUGIN or VIPGOCI_ADDON_THEME.
 * @param array  $pr_ids     IDs of pull requests implicated.
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
	array $pr_ids,
	bool $dry_mode = false
) :string {
	/*
	 * Determine addon-type and construct string.
	 */
	$issue_type_string = '';

	if ( VIPGOCI_ADDON_PLUGIN === $issue_type ) {
		$issue_type_string = 'Plugin';
	} elseif ( VIPGOCI_ADDON_THEME === $issue_type ) {
		$issue_type_string = 'Theme';
	} else {
		vipgoci_sysexit(
			'Internal error: Invalid $issue_type in ' . __FUNCTION__,
			array(
				'$issue_type' => $issue_type,
			)
		);
	}

	/*
	 * Start markup for header and determine text.
	 */
	$res = '## '; // Header markup.

	if ( VIPGOCI_WPSCAN_OBSOLETE === $issue['security'] ) {
		$res .= vipgoci_github_transform_to_emojis( VIPGOCI_ISSUE_TYPE_INFO ) . // Information sign.
			' ' . $issue_type_string .
			' with update available' . "\n";
	} elseif ( VIPGOCI_WPSCAN_VULNERABLE === $issue['security'] ) {
		$res .= vipgoci_github_transform_to_emojis( VIPGOCI_ISSUE_TYPE_WARNING ) . // Exclamation mark.
			' ' . $issue_type_string .
			' with known vulnerability' . "\n";
	} else {
		vipgoci_sysexit(
			'Internal error: Invalid $issue[security] in ' . __FUNCTION__,
			array(
				'$issue[security]' => $issue['security'],
			)
		);
	}

	/*
	 * Sanitize URL and escape -- do not escape "." and "-" as
	 * these can exist in URLs and are ignored in this
	 * Markdown context.
	 */
	$addon_url_escaped = vipgoci_output_markdown_escape(
		vipgoci_output_sanitize_url( $issue['details']['url'] ),
		array( '.', '-' )
	);

	// Type of addon.
	if ( VIPGOCI_ADDON_PLUGIN === $issue_type ) {
		$res .= '**Plugin name**: ' . vipgoci_output_markdown_escape( $issue['message'] ) . "\n" .
			'**Plugin URI**: ' . $addon_url_escaped . "\n";
	} elseif ( VIPGOCI_ADDON_THEME === $issue_type ) {
		$res .= '**Theme name**: ' . vipgoci_output_markdown_escape( $issue['message'] ) . "\n" .
			'**Theme URI**: ' . $addon_url_escaped . "\n";
	}

	// Sanitize URL.
	$installed_location_sanitized = vipgoci_output_sanitize_url(
		$issue['details']['installed_location']
	);

	// Construct URL to the relevant file.
	$view_code_link = '([view code](' .
		VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'tree/' .
		rawurlencode( $commit_id ) . '/' .
		$installed_location_sanitized .
		'))';

	/*
	 * Only escape backticks; this is safe, as in this Markdown
	 * context every other Markdown is outputted as literal.
	 */
	$res .= '**Installed location**: `' . vipgoci_output_markdown_escape(
		$issue['details']['installed_location'],
		array(),
		array( '`' => '\`' )
	) . '` ' . $view_code_link . "\n";

	/*
	 * No need for further Markdown sanitization here;
	 * version numbers are safe to output as they are
	 * interpreted as literal.
	 */
	$res .=
		'**Version observed**: ' . vipgoci_output_sanitize_version_number( $issue['details']['version_detected'] ) . "\n" .
		'**Latest version available**: ' . vipgoci_output_sanitize_version_number( $issue['details']['latest_version'] ) . "\n";

	// Sanitize URL and escape -- do not escape "." and "-", see above.
	$res .= '**Latest version download URI**: ' . vipgoci_output_markdown_escape(
		vipgoci_output_sanitize_url( $issue['details']['latest_download_uri'] ),
		array( '.', '-' )
	) . "\n";

	if ( ! empty( $issue['details']['vulnerabilities'] ) ) {
		$res .= "\n\r";

		foreach ( $issue['details']['vulnerabilities'] as $vuln_item ) {
			if (
				( ! isset( $vuln_item['id'] ) ) ||
				( ! isset( $vuln_item['title'] ) )
			) {
				vipgoci_log(
					'Vulnerability detail item from WPScan API is invalid, missing fields',
					array(
						'vuln_item' => $vuln_item,
					)
				);

				continue;
			}

			$res .= '### &#x1f512; Security information' . "\n"; // Header markup and lock sign.

			/*
			 * Escape except for "#" and "&" -- these are sometimes
			 * seen in "titles" and are ignored in this Markdown context.
			 */
			$res .= '**Title**: ' . vipgoci_output_markdown_escape(
				$vuln_item['title'],
				array( '#', '&' )
			) . "\n";

			/*
			 * Escape URL. Do not escape "." and "-", see above.
			 */
			$res .= '**Details**: ' . vipgoci_output_markdown_escape(
				vipgoci_output_sanitize_url( VIPGOCI_WPSCAN_BASE_URL . '/vulnerability/' . rawurlencode( $vuln_item['id'] ) ),
				array( '.', '-' )
			) . "\n";

			// May not be included, enterprise only feature.
			if (
				( isset( $vuln_item['cvss']['score'] ) ) &&
				( is_numeric( $vuln_item['cvss']['score'] ) )
			) {
				// Output severity as float.
				$res .= '**Severity**: ';
				$res .= sprintf( '%.1f', (float) $vuln_item['cvss']['score'] );
				$res .= '/10 (';

				// Escape output string.
				$res .= vipgoci_output_markdown_escape(
					vipgoci_wpscan_report_format_cvss_score(
						(float) $vuln_item['cvss']['score']
					)
				);

				$res .= ')' . "\n";
			}
		}
	}

	vipgoci_markdown_comment_add_pagebreak(
		$res
	);

	// Report to IRC.
	$commit_url = VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/commit/' .
		rawurlencode( $commit_id );

	vipgoci_log(
		'WPScan API results',
		array(
			'commit_url' => $commit_url,
			'pr_ids'     => $pr_ids,
			'slug'       => $issue['details']['slug'],
			'msg'        => $issue['message'],
			'level'      => $issue['security'],
			'version'    => $issue['details']['version_detected'],
			'latest'     => $issue['details']['latest_version'],
		),
		0,
		true
	);

	// If dry-run mode is enabled, do not post any generic comment. Temporary feature.
	if ( true === $dry_mode ) {
		return '';
	}

	return $res;
}

