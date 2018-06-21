#!/usr/bin/php
<?php

require_once( __DIR__ . '/github-api.php' );
require_once( __DIR__ . '/git-repo.php' );
require_once( __DIR__ . '/misc.php' );
require_once( __DIR__ . '/phpcs-scan.php' );
require_once( __DIR__ . '/lint-scan.php' );
require_once( __DIR__ . '/auto-approval.php' );

/*
 * Handle boolean parameters given on the command-line.
 *
 * Will set a default value for the given parameter name,
 * if no value is set. Will then proceed to check if the
 * value given is a boolean and will then convert the value
 * to a boolean-type, and finally set it in $options.
 */

function vipgoci_option_bool_handle(
	&$options,
	$parameter_name,
	$default_value
) {

	/* If no default is given, set it */
	if ( ! isset( $options[ $parameter_name ] ) ) {
		$options[ $parameter_name ] = $default_value;
	}

	/* Check if the gien value is a false or true */
	if (
		( $options[ $parameter_name ] !== 'false' ) &&
		( $options[ $parameter_name ] !== 'true' )
	) {
		vipgoci_sysexit(
			'Parameter --' . $parameter_name .
				' has to be either false or true',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/* Convert the given value to a boolean type value */
	if ( $options[ $parameter_name ] === 'false' ) {
		$options[ $parameter_name ] = false;
	}

	else {
		$options[ $parameter_name ] = true;
	}
}

/*
 * Handle integer parameters given on the command-line.
 *
 * Will set a default value for the given parameter name,
 * if no value is set. Will then proceed to check if the
 * value given is an integer-value, then forcibly convert
 * it to integer-value to make sure it is of that type,
 * then check if it is in a list of allowable values.
 * If any of these fail, it will exit the program with an error.
 */

function vipgoci_option_integer_handle(
	&$options,
	$parameter_name,
	$default_value,
	$allowed_values = null
) {
	/* If no value is set, set the default value */
	if ( ! isset( $options[ $parameter_name ] ) ) {
		$options[ $parameter_name ] = $default_value;
	}

	/* Make sure it is a numeric */
	if ( ! is_numeric( $options[ $parameter_name ] ) ) {
		vipgoci_sysexit(
			'Usage: Parameter --' . $parameter_name . ' is not ' .
				'an integer-value.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/* Forcibly convert to integer-value */
	$options[ $parameter_name ] =
		(int) $options[ $parameter_name ];

	/*
	 * Check if value is in range
	 */

	if (
		( null !== $allowed_values )
		&&
		( ! in_array(
			$options[ $parameter_name ],
			$allowed_values,
			true
		) )
	) {
		vipgoci_sysexit(
			'Parameter --' . $parameter_name . ' is out ' .
				'of allowable range.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

}

/*
 * Handle array-like option parameters given on the command line
 *
 * Parses the parameter, turns it into a real array,
 * makes sure forbidden values are not contained in it.
 * Does not return the result, but rather alters
 * $options directly.
 */
function vipgoci_option_array_handle(
	&$options,
	$option_name,
	$default_value = array(),
	$forbidden_value = null
) {
	if ( ! isset( $options[ $option_name ] ) ) {
		$options[ $option_name ] = array();
	}

	else {
		$options[ $option_name ] = explode(
			',',
			strtolower(
				$options[ $option_name ]
			)
		);

		if ( ! empty( $forbidden_value ) ) {
			if ( in_array(
				$forbidden_value,
				$options[ $option_name ],
				true
			) ) {
				vipgoci_sysexit(
					'Parameter --' .
						$option_name . ' ' .
						'can not contain \'' .
						$forbidden_value .
						'\' as one of ' .
						'the values',
					array(),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}
		}
	}
}


/*
 * Handle parameter that expects the value
 * of it to be a file. Allow a default value
 * to be set if none is set.
 */

function vipgoci_option_file_handle(
	&$options,
	$option_name,
	$default_value = null
) {

	if (
		( ! isset( $options[ $option_name ] ) ) &&
		( null !== $default_value )
	) {
		$options[ $option_name ] = $default_value;
	}

	else if (
		( ! isset( $options[ $option_name ] ) ) ||
		( ! is_file( $options[ $option_name ] ) )
	) {
		vipgoci_sysexit(
			'Parameter --' . $option_name .
				' has to be a valid path',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}


/*
 * Determine exit status.
 *
 * If any 'error'-type issues were submitted to
 * GitHub we announce a failure to our parent-process
 * by returning with a non-zero exit-code.
 *
 * If we only submitted warnings, we do not announce failure.
 */

function vipgoci_exit_status( $results ) {
	foreach (
		array_keys(
			$results['stats']
		)
		as $stats_type
	) {
		if (
			! isset( $results['stats'][ $stats_type ] ) ||
			null === $results['stats'][ $stats_type ]
		) {
			/* In case the type of scan was not performed, skip */
			continue;
		}

		foreach (
			array_keys(
				$results['stats'][ $stats_type ]
			)
			as $pr_number
		) {
			if (
				0 !== $results['stats']
					[ $stats_type ]
					[ $pr_number ]
					['error']
			) {
				// Some errors were found, return non-zero
				return 250;
			}
		}

	}

	return 0;
}


/*
 * Main invocation function.
 */
function vipgoci_run() {
	global $argv;
	global $vipgoci_debug_level;

	vipgoci_log(
		'Initializing...',
		array()
	);

	/*
	 * Refuse to run as root.
	 */
	if ( 0 === posix_getuid() ) {
		vipgoci_sysexit(
			'Will not run as root. Please run as non-privileged user.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Set how to deal with errors:
	 * Report all errors, and display them.
	 */
	ini_set( 'error_log', '' );

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );


	// Set with a temp value for now, user value set later
	$vipgoci_debug_level = 0;

	$startup_time = time();

	$options = getopt(
		null,
		array(
			'repo-owner:',
			'repo-name:',
			'commit:',
			'token:',
			'review-comments-max:',
			'branches-ignore:',
			'output:',
			'dry-run:',
			'phpcs-path:',
			'phpcs-standard:',
			'phpcs-severity:',
			'php-path:',
			'local-git-repo:',
			'skip-folders:',
			'lint:',
			'phpcs:',
			'autoapprove:',
			'autoapprove-filetypes:',
			'autoapprove-label:',
			'help',
			'debug-level:',
		)
	);

	// Validate args
	if (
		! isset( $options['repo-owner'] ) ||
		! isset( $options['repo-name'] ) ||
		! isset( $options['commit'] ) ||
		! isset( $options['token'] ) ||
		! isset( $options['local-git-repo']) ||
		isset( $options['help'] )
	) {
		print 'Usage: ' . $argv[0] . PHP_EOL .
			"\t" . 'Options --repo-owner, --repo-name, --commit, --token, --local-git-repo are ' . PHP_EOL .
			"\t" . 'mandatory, while others are optional.' . PHP_EOL .
			PHP_EOL .
			"\t" . 'Note that if option --autoapprove is specified, --autoapprove-filetypes and ' . PHP_EOL .
			"\t" . '--autoapprove-label need to be specified as well.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--repo-owner=STRING            Specify repository owner, can be an organization' . PHP_EOL .
			"\t" . '--repo-name=STRING             Specify name of the repository' . PHP_EOL .
			"\t" . '--commit=STRING                Specify the exact commit to scan (SHA)' . PHP_EOL .
			"\t" . '--token=STRING                 The access-token to use to communicate with GitHub' . PHP_EOL .
			"\t" . '--review-comments-max=NUMBER   Maximum number of inline comments to submit' . PHP_EOL .
			"\t" . '                               to GitHub in one review. If the number of ' . PHP_EOL .
			"\t" . '                               comments exceed this number, additional reviews ' . PHP_EOL .
			"\t" . '                               will be submitted.' . PHP_EOL .
			"\t" . '--phpcs=BOOL                   Whether to run PHPCS (true/false)' . PHP_EOL .
			"\t" . '--phpcs-path=FILE              Full path to PHPCS script' . PHP_EOL .
			"\t" . '--phpcs-standard=STRING        Specify which PHPCS standard to use' . PHP_EOL .
			"\t" . '--phpcs-severity=NUMBER        Specify severity for PHPCS' . PHP_EOL .
			"\t" . '--autoapprove=BOOL             Whether to auto-approve Pull-Requests' . PHP_EOL .
			"\t" . '                               altering only files of certain types' . PHP_EOL .
			"\t" . '--autoapprove-filetypes=STRING Specify what file-types can be auto-' . PHP_EOL .
			"\t" . '                               approved. PHP files cannot be specified' . PHP_EOL .
			"\t" . '--autoapprove-label=STRING     String to use for labels when auto-approving' . PHP_EOL .
			"\t" . '--php-path=FILE                Full path to PHP, if not specified the' . PHP_EOL .
			"\t" . '                               default in $PATH will be used instead' . PHP_EOL .
			"\t" . '--branches-ignore=STRING,...   What branches to ignore -- useful to make sure' . PHP_EOL .
			"\t" . '                               some branches never get scanned. Separate branches' . PHP_EOL .
			"\t" . '                               with commas' . PHP_EOL .
			"\t" . '--local-git-repo=FILE          The local git repository to use for direct access to code' . PHP_EOL .
			"\t" . '--skip-folders=STRING          Specify folders relative to the git repository in which not ' . PHP_EOL .
			"\t" . '                               to look into for files to PHP lint or scan using PHPCS. ' . PHP_EOL .
			"\t" . '                               Note that this argument is not employed with auto-approvals. ' . PHP_EOL .
			"\t" . '                               Values are comma separated' . PHP_EOL .
			"\t" . '--dry-run=BOOL                 If set to true, will not make any changes to any data' . PHP_EOL .
			"\t" . '                               on GitHub -- no comments will be submitted, etc.' . PHP_EOL .
			"\t" . '--output=FILE                  Where to save output made from running PHPCS' . PHP_EOL .
			"\t" . '--lint=BOOL                    Whether to do PHP linting (true/false)' . PHP_EOL .
			"\t" . '--help                         Displays this message' . PHP_EOL .
			"\t" . '--debug-level=NUMBER           Specify minimum debug-level of messages to print' . PHP_EOL .
			"\t" . '                                -- higher number indicates more detailed debugging-messages.' . PHP_EOL .
			"\t" . '                               Default is zero' . PHP_EOL;

		exit( VIPGOCI_EXIT_USAGE_ERROR );
	}


	/*
	 * Process the --branches-ignore parameter,
	 * -- expected to be an array
	 */

	vipgoci_option_array_handle(
		$options,
		'branches-ignore',
		array()
	);


	/*
	 * Process --phpcs-path -- expected to
	 * be a file
	 */

	vipgoci_option_file_handle(
		$options,
		'phpcs-path',
		null
	);

	/*
	 * Process --phpcs-standard -- expected to be
	 * a string
	 */

	if ( empty( $options['phpcs-standard'] ) ) {
		$options['phpcs-standard'] = 'WordPress-VIP-Go';
	}

	$options['phpcs-standard'] = trim(
		$options['phpcs-standard']
	);


	/*
	 * Process --phpcs-severity -- expected to be
	 * an integer-value.
	 */

	vipgoci_option_integer_handle(
		$options,
		'phpcs-severity',
		1,
		array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 )
	);

	/*
	 * Process --php-path -- expected to be a file,
	 * default value is 'php' (then relies on $PATH)
	 */

	vipgoci_option_file_handle(
		$options,
		'php-path',
		'php'
	);


	/*
	 * Handle --local-git-repo parameter
	 */

	$options['local-git-repo'] = rtrim(
		$options['local-git-repo'],
		'/'
	);


	vipgoci_gitrepo_ok(
		$options['commit'],
		$options['local-git-repo']
	);


	/*
	 * Handle --skip-folders parameter
	 */
	vipgoci_option_array_handle(
		$options,
		'skip-folders',
		array()
	);

	/*
	 * Handle optional --debug-level parameter
	 */

	vipgoci_option_integer_handle(
		$options,
		'debug-level',
		0,
		array( 0, 1, 2 )
	);

	// Set the value to global
	$vipgoci_debug_level = $options['debug-level'];


	/*
	 * Handle boolean parameters
	 */

	vipgoci_option_bool_handle( $options, 'dry-run', 'false' );

	vipgoci_option_bool_handle( $options, 'phpcs', 'true' );

	vipgoci_option_bool_handle( $options, 'lint', 'true' );


	if (
		( false === $options['lint'] ) &&
		( false === $options['phpcs'] )
	) {
		vipgoci_sysexit(
			'Both --lint and --phpcs set to false, nothing to do!',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}


	/*
	 * Should we auto-approve Pull-Requests when
	 * only altering certain file-types?
	 */

	vipgoci_option_bool_handle( $options, 'autoapprove', 'false' );

	vipgoci_option_array_handle(
		$options,
		'autoapprove-filetypes',
		array(),
		'php'
	);

	/*
	 * Do some sanity-checking on the parameters
	 */

	$options['autoapprove-filetypes'] = array_map(
		'strtolower',
		$options['autoapprove-filetypes']
	);

	if ( empty( $options['autoapprove-label'] ) ) {
		$options['autoapprove-label'] = false;
	}

	else {
		$options['autoapprove-label'] = trim(
			$options['autoapprove-label']
		);
	}


	if (
		( true === $options['autoapprove'] ) &&
		(
			( empty( $options['autoapprove-filetypes'] ) ) ||
			( false === $options['autoapprove-label'] )
		)
	) {
		vipgoci_sysexit(
			'To be able to auto-approve, file-types to approve ' .
			'must be specified, as well as a label; see --help ' .
			'for information',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}


	if (
		( true === $options['autoapprove'] ) &&
		( in_array( 'php', $options['autoapprove-filetypes'], true ) )
	) {
		vipgoci_sysexit(
			'PHP files cannot be auto-approved, as they can' .
				'contain serious problems for execution',
			array(
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}


	/*
	 * Ask GitHub about information about
	 * the user the token belongs to
	 */
	$current_user_info = vipgoci_github_authenticated_user_get(
		$options['token']
	);

	if (
		( ! isset( $current_user_info->login ) ) ||
		( empty( $current_user_info->login ) )
	) {
		vipgoci_sysexit(
			'Unable to get information about token-holder user from GitHub',
			array(),
			VIPGOCI_EXIT_GITHUB_PROBLEM
		);
	}

	else {
		vipgoci_log(
			'Got information about token-holder user from GitHub',
			array(
				'login' => $current_user_info->login,
				'html_url' => $current_user_info->html_url,
			)
		);
	}


	/*
	 * Maximum number of inline comments posted to
	 * Github with one review -- from 5 to 100.
	 */

	vipgoci_option_integer_handle(
		$options,
		'review-comments-max',
		10,
		range( 5, 100, 1 )
	);


	/*
	 * Log that we started working,
	 * and the arguments provided as well.
	 *
	 * Make sure not to print out any secrets.
	 */

	$options_clean = $options;
	$options_clean['token'] = '***';

	vipgoci_log(
		'Starting up...',
		array(
			'options' => $options_clean
		)
	);

	$results = array(
		'issues'	=> array(),

		'stats'		=> array(
			'phpcs'	=> null,
			'lint'	=> null,
		),
	);

	unset( $options_clean );



	/*
	 * If no Pull-Requests are implicated by this commit,
	 * bail now, as there is no point in continuing running.
	 */

	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore']
	);

	if ( empty( $prs_implicated ) ) {
		vipgoci_sysexit(
			'Skipping scanning entirely, as the commit ' .
				'is not a part of any Pull-Request',
			array(),
			VIPGOCI_EXIT_NORMAL
		);
	}


	/*
	 * Make sure we are working with the latest
	 * commit to each implicated PR.
	 *
	 * If we detect that we are doing linting,
	 * and the commit is not the latest, skip linting
	 * as it becomes useless if this is not the
	 * latest commit: There is no use in linting
	 * an obsolete commit.
	 */
	foreach ( $prs_implicated as $pr_item ) {
		$commits_list = vipgoci_github_prs_commits_list(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token']
		);

		// If no commits, skip checks
		if ( empty( $commits_list ) ) {
			continue;
		}

		// Reverse array, so we get the last commit first
		$commits_list = array_reverse( $commits_list );


		// If latest commit to the PR, we do not care at all
		if ( $commits_list[0] === $options['commit'] ) {
			continue;
		}

		/*
		 * At this point, we have found an inconsistency;
		 * the commit we are working with is not the latest
		 * to the Pull-Request, and we have to deal with that.
		 */

		if (
			( true === $options['lint'] ) &&
			( false === $options['phpcs'] )
		) {
			vipgoci_sysexit(
				'The current commit is not the latest one ' .
					'to the Pull-Request, skipping ' .
					'linting, and not doing PHPCS ' .
					'-- nothing to do',
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name' => $options['repo-name'],
					'pr_number' => $pr_item->number,
				),
				VIPGOCI_EXIT_NORMAL
			);
		}

		else if (
			( true === $options['lint'] ) &&
			( true === $options['phpcs'] )
		) {
			// Skip linting, useless if not latest commit
			$options['lint'] = false;

			vipgoci_log(
				'The current commit is not the latest ' .
					'one to the Pull-Request, ' .
					'skipping linting',
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name' => $options['repo-name'],
					'pr_number' => $pr_item->number,
				)
			);
		}

		/*
		 * As for lint === false && true === phpcs,
		 * we do not care, as then we will not be linting.
		 */

		unset( $commits_list );
	}


	/*
	 * Init stats
	 */
	vipgoci_stats_init(
		$options,
		$prs_implicated,
		$results
	);


	/*
	 * Clean up old comments made by us previously
	 */
	vipgoci_github_pr_comments_cleanup(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore'],
		$options['dry-run']
	);

	/*
	 * Run all checks requested and store the
	 * results in an array
	 */

	if ( true === $options['lint'] ) {
		vipgoci_lint_scan_commit(
			$options,
			$results['issues'],
			$results['stats']['lint']
		);
	}

	/*
	 * Note: We run this, even if linting fails, to make sure
	 * to catch all errors incrementally.
	 */

	if ( true === $options['phpcs'] ) {
		vipgoci_phpcs_scan_commit(
			$options,
			$results['issues'],
			$results['stats']['phpcs']
		);
	}

	/*
	 * If to auto-approve, then do so.
	 */

	if ( true === $options['autoapprove'] ) {
		// FIXME: Do not auto-approve if there are
		// any linting or PHPCS-issues.
		vipgoci_auto_approval(
			$options
		);
	}


	/*
	 * Submit any issues to GitHub
	 */

	vipgoci_github_pr_generic_comment_submit(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$results,
		$options['dry-run']
	);


	vipgoci_github_pr_review_submit(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$results,
		$options['dry-run'],
		$options['review-comments-max']
	);

	$github_api_rate_limit_usage =
		vipgoci_github_rate_limit_usage(
			$options['token']
		);

	vipgoci_log(
		'Shutting down',
		array(
			'run_time_seconds'	=> time() - $startup_time,
			'run_time_measurements'	=>
				vipgoci_runtime_measure(
					'dump',
					null
				),
			'counters_report'	=>
				vipgoci_counter_report(
					'dump',
					null,
					null
				),
			'github_api_rate_limit' =>
				$github_api_rate_limit_usage->resources->core,

			'results'		=> $results,
		)
	);


	return vipgoci_exit_status(
		$results
	);
}

$ret = vipgoci_run();

exit( $ret );
