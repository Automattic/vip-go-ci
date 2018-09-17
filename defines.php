<?php

/*
 * Client-ID for curl-requests, etc.
 */

define( 'VIPGOCI_CLIENT_ID',		'automattic-vip-go-ci' );
define( 'VIPGOCI_SYNTAX_ERROR_STR',	'PHP Syntax Errors Found' );
define( 'VIPGOCI_GITHUB_ERROR_STR',	'GitHub API communication error');
define( 'VIPGOCI_GITHUB_BASE_URL',	'https://api.github.com' );

define( 'VIPGOCI_INFORMATIONAL_MESSAGE',
					'This bot provides automated ' .
					'PHP Linting and PHPCS scanning, ' .
					'read more [here](%s).'
);

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
