<?php
/**
 * Various defines for vip-go-ci
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/*
 * Version number and default name to use.
 */
define( 'VIPGOCI_VERSION', '1.3.9' );
define( 'VIPGOCI_DEFAULT_NAME_TO_USE', 'vip-go-ci' );

/*
 * Define minimum version requirements.
 */
define( 'VIPGOCI_GIT_VERSION_MINIMUM', '2.30' );
define( 'VIPGOCI_PHP_VERSION_MINIMUM', '8.0.0' );

/*
 * Client-ID for HTTP requests.
 */
define( 'VIPGOCI_CLIENT_ID', 'automattic-vip-go-ci' );


/*
 * GitHub defines.
 */
define( 'VIPGOCI_GITHUB_WEB_BASE_URL', 'https://github.com' );

/* Define if not defined. Unit-tests can define this for testing. */
if ( ! defined( 'VIPGOCI_GITHUB_BASE_URL' ) ) {
	define( 'VIPGOCI_GITHUB_BASE_URL', 'https://api.github.com' );
}

// GitHub API version header. If updated, update latest-release.php too.
define( 'VIPGOCI_GITHUB_API_VERSION', '2022-11-28' );

/*
 * Defines for various sizes, such as KB.
 */
define( 'VIPGOCI_KB_IN_BYTES', 1024 );

/*
 * Constants for HTTP API functions.
 */
define( 'VIPGOCI_HTTP_API_LONG_TIMEOUT', 20 );
define( 'VIPGOCI_HTTP_API_SHORT_TIMEOUT', 5 );
define( 'VIPGOCI_HTTP_API_CONTENT_TYPE_APPLICATION_JSON', 'application/json' );
define( 'VIPGOCI_HTTP_API_CONTENT_TYPE_X_WWW_FORM_URLENCODED', 'application/x-www-form-urlencoded' );

/*
 * Define exit-codes
 */
define( 'VIPGOCI_EXIT_NORMAL', 0 );
define( 'VIPGOCI_EXIT_INTERNAL_ERROR', 220 );
define( 'VIPGOCI_EXIT_COMMIT_NOT_PART_OF_PR', 230 );
define( 'VIPGOCI_EXIT_HTTP_API_ERROR', 247 );
define( 'VIPGOCI_EXIT_COMMIT_NOT_LATEST', 248 );
define( 'VIPGOCI_EXIT_EXEC_TIME', 249 );
define( 'VIPGOCI_EXIT_CODE_ISSUES', 250 );
define( 'VIPGOCI_EXIT_SYSTEM_PROBLEM', 251 );
define( 'VIPGOCI_EXIT_GITHUB_PROBLEM', 252 );
define( 'VIPGOCI_EXIT_USAGE_ERROR', 253 );

/*
 * Define statistics-types.
 *
 * Note: These are related to the command-line
 * arguments passed to the program (e.g., --phpcs)
 * -- altering these is not recommended.
 */
define( 'VIPGOCI_STATS_PHPCS', 'phpcs' );
define( 'VIPGOCI_STATS_LINT', 'lint' );
define( 'VIPGOCI_STATS_WPSCAN_API', 'wpscan-api' );

/*
 * Define error/warning/info constants.
 */
define( 'VIPGOCI_ISSUE_TYPE_INFO', 'info' );
define( 'VIPGOCI_ISSUE_TYPE_WARNING', 'warning' );
define( 'VIPGOCI_ISSUE_TYPE_ERROR', 'error' );

/*
 * Defines for auto-approvals.
 */
define( 'VIPGOCI_APPROVAL_AUTOAPPROVE', 'auto-approval' );
define( 'VIPGOCI_APPROVAL_AUTOAPPROVE_NON_FUNCTIONAL_CHANGES_FILE_EXTENSIONS_DEFAULT', array( 'php' ) );

/*
 * Defines for vipgoci_runtime_measure() function.
 */
define( 'VIPGOCI_RUNTIME_START', 'start' );
define( 'VIPGOCI_RUNTIME_STOP', 'stop' );
define( 'VIPGOCI_RUNTIME_DUMP', 'dump' );

/*
 * Defines for vipgoci_counter_report() function.
 */
define( 'VIPGOCI_COUNTERS_DUMP', 'dump' );
define( 'VIPGOCI_COUNTERS_DO', 'do' );

/*
 * Define for vipgoci_run_time_length_determine() function.
 */
define( 'VIPGOCI_RUN_LENGTH_MEDIUM', 120 );
define( 'VIPGOCI_RUN_LENGTH_LONG', 240 );

/*
 * Define for vipgoci_cache() function.
 */
define( 'VIPGOCI_CACHE_CLEAR', '--VIPGOCI-CACHE-CLEAR-0x321--' );

/*
 * Define for vipgoci_http_api_wait() function.
 */

// This can be overridden in programs using vip-go-ci as library.
if ( ! defined( 'VIPGOCI_HTTP_API_WAIT_TIME_SECONDS' ) ) {
	define( 'VIPGOCI_HTTP_API_WAIT_TIME_SECONDS', 3 );
}

define( 'VIPGOCI_HTTP_API_WAIT_APIS_ARRAY', array( VIPGOCI_GITHUB_BASE_URL ) );

/*
 * Defines for files.
 */
define( 'VIPGOCI_OPTIONS_FILE_NAME', '.vipgoci_options' );

/*
 * Define for vipgoci_git_diffs_fetch() function.
 */
define(
	'VIPGOCI_GIT_DIFF_CALC_CHANGES',
	array(
		'+' => 'additions',
		'-' => 'deletions',
	)
);

define( 'VIPGOCI_GIT_DIFF_DATA_SOURCE_GIT_REPO', 'local-git-repo' );
define( 'VIPGOCI_GIT_DIFF_DATA_SOURCE_GITHUB_API', 'github-api' );

/*
 * Define file number of lines limit.
 */
define( 'VIPGOCI_SKIPPED_FILES', 'skipped-files' );

define( 'VIPGOCI_VALIDATION_MAXIMUM_LINES_LIMIT', 15000 );
define( 'VIPGOCI_VALIDATION_MAXIMUM_LINES', 'max-lines' );
define(
	'VIPGOCI_VALIDATION',
	array(
		VIPGOCI_VALIDATION_MAXIMUM_LINES
			=> 'Maximum number of lines exceeded (%d)',
	)
);

define( 'VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG', 'Note that the above file(s) were not analyzed due to their length.' );

/*
 * Indicates which sections of log
 * messages should not be logged to IRC.
 */
define(
	'VIPGOCI_IRC_IGNORE_STRING_START',
	'<!-- vip-go-ci-irc-ignore-start -->'
);

define(
	'VIPGOCI_IRC_IGNORE_STRING_END',
	'<!-- vip-go-ci-irc-ignore-end -->'
);

/*
 * Strings for generic messages.
 */
define( 'VIPGOCI_CODE_ANALYSIS_ISSUES', 'Code analysis identified issues' );
define( 'VIPGOCI_GITHUB_ERROR_STR', 'GitHub API communication error. Please contact a human.' );

/*
 * Various messages.
 */
define(
	'VIPGOCI_REVIEW_COMMENTS_TOTAL_MAX',
	'Total number of active review comments per ' .
					'pull request has been reached and some ' .
					'comments might not appear as a result. ' .
					'Please resolve some issues to see more'
);


define(
	'VIPGOCI_OUT_OF_MEMORY_ERROR',
	'Unable to analyze the pull request due to resource constraints. The pull request may be too large to process. Please try submitting a smaller pull request'
);

define(
	'VIPGOCI_NO_ISSUES_FOUND_MSG_AND_NO_REVIEWS',
	'No issues were found to report when scanning latest commit'
);

define(
	'VIPGOCI_NO_ISSUES_FOUND_MSG_AND_EXISTING_REVIEWS',
	'Scanning latest commit did not yield any new issues. Please have a look at older feedback still existing'
);

/*
 * Defines related to PHP linting.
 */
define( 'VIPGOCI_LINT_FILE_EXTENSIONS_DEFAULT', array( 'php' ) );

define(
	'VIPGOCI_LINT_REPORT_START',
	'%1$s has identified PHP syntax errors during automated linting. ' .
	'We recommend reviewing the issues noted and that they are resolved.' .
	"\n\r\n\r" . 'PHP linting performed at commit %2$s ([view code](%3$s)).'
);

define(
	'VIPGOCI_LINT_FAILED_MSG_START',
	'Unable to PHP lint one or more files due to error running PHP linter: '
);

define(
	'VIPGOCI_LINT_FAILED_MSG_END',
	'The error may be temporary. If the error persists, please contact a human'
);

define( 'VIPGOCI_LINT_ERROR_STR', 'PHP Syntax Errors Found' );

/*
 * Defines relating to PHPCS scanning.
 */
define( 'VIPGOCI_PHPCS_FILE_EXTENSIONS_DEFAULT', array( 'php', 'js', 'twig' ) );

define(
	'VIPGOCI_PHPCS_SCAN_REVIEW_START',
	'%1$s has identified potential problems in this pull request ' .
	'during automated scanning. We recommend reviewing the issues ' .
	'noted and that they are resolved.'
);

define(
	'VIPGOCI_PHPCS_SCAN_FAILED_MSG_START',
	'Unable to PHPCS or SVG scan one or more files due to error running PHPCS/SVG scanner: '
);

define(
	'VIPGOCI_PHPCS_SCAN_FAILED_MSG_END',
	'The error may be temporary. If the error persists, please contact a human'
);

define(
	'VIPGOCI_PHPCS_INVALID_SNIFFS',
	'Invalid PHPCS sniff(s) specified in ' .
					'options or options file. Those have ' .
					'been ignored temporarily. Please ' .
					'update the options so that scanning ' .
					'can continue as expected. '
);

define(
	'VIPGOCI_PHPCS_INVALID_SNIFFS_CONT',
	'<br />' .
					PHP_EOL . PHP_EOL .
					'* Option name: `%s`' . PHP_EOL .
					'* Invalid sniff(s): `%s`' . PHP_EOL
);

define(
	'VIPGOCI_PHPCS_DUPLICATE_SNIFFS',
	'Sniff(s) has been found in duplicate in ' .
					'options or options file. Those have ' .
					'been ignored temporarily. Please ' .
					'update the options so that scanning ' .
					'can continue as expected. ' .
					'<br /> '
);

define(
	'VIPGOCI_PHPCS_DUPLICATE_SNIFFS_CONT',
	'<br />' .
					PHP_EOL . PHP_EOL .
					'* Options: `%s` and `%s`' . PHP_EOL .
					'* Sniff(s) in duplicate: `%s`' . PHP_EOL .
					'<br />'
);

/*
 * Defines for SVG scanning.
 */
define( 'VIPGOCI_SVG_FILE_EXTENSIONS_DEFAULT', array( 'svg' ) );

/*
 * Defines for addons generally.
 */
define( 'VIPGOCI_ADDON_PLUGIN', 'vipgoci-addon-plugin' );
define( 'VIPGOCI_ADDON_THEME', 'vipgoci-addon-theme' );

/*
 * WordPress.org defines.
 */
define( 'VIPGOCI_WORDPRESS_ORG_API_BASE_URL', 'https://api.wordpress.org' );

/*
 * Defines for WPScan API support.
 */
define( 'VIPGOCI_WPSCAN_BASE_URL', 'https://wpscan.com' );
define( 'VIPGOCI_WPSCAN_API_BASE_URL', VIPGOCI_WPSCAN_BASE_URL . '/api/v3' );

define( 'VIPGOCI_WPSCAN_PLUGIN_FILE_EXTENSIONS_DEFAULT', array( 'php' ) );
define( 'VIPGOCI_WPSCAN_THEME_FILE_EXTENSIONS_DEFAULT', array( 'css' ) );

define( 'VIPGOCI_WPSCAN_UPDATEURI_WP_ORG_URLS', array( 'w.org', 'wordpress.org' ) );

define( 'VIPGOCI_WPSCAN_VULNERABLE', 'vulnerable' );
define( 'VIPGOCI_WPSCAN_OBSOLETE', 'obsolete' );

define( 'VIPGOCI_WPSCAN_API_ERROR', 'Vulnerability and Update Scan' );

define(
	'VIPGOCI_WPSCAN_REPORT_START',
	'%1$s has identified one or more insecure or obsolete %2$s(s) being ' .
	'submitted or altered in this pull request. Updating the %2$s(s) before merging ' .
	'into the target branch is strongly recommended.'
);

define( 'VIPGOCI_WPSCAN_SKIP_SCAN_PR_LABEL', 'skip-wpscan' );

define(
	'VIPGOCI_WPSCAN_CVSS_RANKING',
	array(
		array(
			'upper_value' => '10.0',
			'lower_value' => '9.0',
			'ranking'     => 'CRITICAL',
		),
		array(
			'upper_value' => '8.9',
			'lower_value' => '7.0',
			'ranking'     => 'HIGH',
		),
		array(
			'upper_value' => '6.9',
			'lower_value' => '4.0',
			'ranking'     => 'MEDIUM',
		),
		array(
			'upper_value' => '3.9',
			'lower_value' => '0.1',
			'ranking'     => 'LOW',
		),
		array(
			'upper_value' => '0.0',
			'lower_value' => '0.0',
			'ranking'     => 'NONE',
		),
	)
);

