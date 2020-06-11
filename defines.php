<?php

/*
 * Version number.
 */

define( 'VIPGOCI_VERSION',		'0.39' );

/*
 * Client-ID for curl-requests, etc.
 */

define( 'VIPGOCI_CLIENT_ID',		'automattic-vip-go-ci' );
define( 'VIPGOCI_SYNTAX_ERROR_STR',	'PHP Syntax Errors Found' );
define( 'VIPGOCI_GITHUB_ERROR_STR',	'GitHub API communication error. ' .
						'Please contact a human.' );

define( 'VIPGOCI_GITHUB_BASE_URL',	'https://api.github.com' );

define( 'VIPGOCI_INFORMATIONAL_MESSAGE',
					'This bot provides automated ' .
					'PHP Linting and PHPCS scanning, ' .
					'read more [here](%s).'
);

define( 'VIPGOCI_FILE_IS_APPROVED_MSG', 'File is approved in review database ' .
					'(hashes-to-hashes).' );

define( 'VIPGOCI_REVIEW_COMMENTS_TOTAL_MAX',
					'Total number of active review comments per ' .
					'Pull-Request has been reached and some ' .
					'comments might not appear as a result. ' .
					'Please resolve some issues to see more' );

define( 'VIPGOCI_PHPCS_INVALID_SNIFFS',
					'Invalid PHPCS sniff(s) specified in ' .
					'options or options file (option ' .
					'`phpcs-sniffs-exclude`). Those have ' .
					'been ignored temporarily. Please ' .
					'update the options so that scanning ' .
					' can continue as expected. ' .
					'<br /><br /> ' .
					'The sniffs are: ' );

/*
 * Define exit-codes
 */

define( 'VIPGOCI_EXIT_NORMAL',		0 );
define( 'VIPGOCI_EXIT_CODE_ISSUES',	250 );
define( 'VIPGOCI_EXIT_SYSTEM_PROBLEM',	251 );
define( 'VIPGOCI_EXIT_GITHUB_PROBLEM',	252 );
define( 'VIPGOCI_EXIT_USAGE_ERROR',	253 );


/*
 * Define statistics-types.
 *
 * Note: These are related to the command-line
 * arguments passed to the program (e.g., --phpcs)
 * -- altering these is not recommended.
 */

define( 'VIPGOCI_STATS_PHPCS',		'phpcs'		);
define( 'VIPGOCI_STATS_LINT',		'lint'		);
define( 'VIPGOCI_STATS_HASHES_API',	'hashes-api'	);

/*
 * Define auto-approval types
 */

define( 'VIPGOCI_APPROVAL_AUTOAPPROVE',		'auto-approval' );
define( 'VIPGOCI_APPROVAL_HASHES_API',		'hashes-api' );


/*
 * Defines for vipgoci_runtime_measure()
 */

define( 'VIPGOCI_RUNTIME_START', 'start' );
define( 'VIPGOCI_RUNTIME_STOP', 'stop' );
define( 'VIPGOCI_RUNTIME_DUMP', 'dump' );

/*
 * Defines for vipgoci_counter_report()
 */

define( 'VIPGOCI_COUNTERS_DUMP',	'dump' );
define( 'VIPGOCI_COUNTERS_DO',		'do' );

/*
 * Define for vipgoci_cache()
 */

define( 'VIPGOCI_CACHE_CLEAR',		'--VIPGOCI-CACHE-CLEAR-0x321--' );

/*
 * Defines for files.
 */

define( 'VIPGOCI_OPTIONS_FILE_NAME',	'.vipgoci_options' );
