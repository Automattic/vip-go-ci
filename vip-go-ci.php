#!/usr/bin/php
<?php

require_once( __DIR__ . '/github-api.php' );
require_once( __DIR__ . '/misc.php' );
require_once( __DIR__ . '/phpcs-scan.php' );
require_once( __DIR__ . '/lint-scan.php' );

/*
 * Handle boolean parameters given on the command-line.
 *
 * Will set a default value for the given parameter name,
 * if no value is set. Will then proceed to check if the
 * value given is a boolean and will then convert the value
 * to a boolean-type, and finally set it in $options.
 */

function vipgoci_parameter_check_bool(
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
		print 'Usage: Parameter --' . $parameter_name .
			' has to be either false or true' . "\n";

		exit( 253 );
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
 * Main invocation function.
 */
function vipgoci_run() {
	global $argv;

	$startup_time = time();

	$options = getopt(
		null,
		array(
			'repo-owner:',
			'repo-name:',
			'commit:',
			'token:',
			'output:',
			'dry-run:',
			'local-git-repo:',
			'help',
		)
	);

	// Validate args
	if (
		! isset( $options['repo-owner'] ) ||
		! isset( $options['repo-name'] ) ||
		! isset( $options['commit'] ) ||
		! isset( $options['token'] ) ||
		isset( $options['help'] )
	) {
		print 'Usage: ' . $argv[0] . "\n" .
			"\t" . '--repo-owner=owner --repo-name=name --commit=SHA --token=string' . "\n" .
			"\t" . '[ --local-git-repo=path ] [ --dry-run=boolean ] [ --output=file-path ]' . "\n" .
			"\t" . '[ --phpcs=true ] [ --lint=true ]' . "\n" .
			"\n" .
			"\t" . '--repo-owner        Specify repository owner, can be an organization' . "\n" .
			"\t" . '--repo-name         Specify name of the repository' . "\n" .
			"\t" . '--commit            Specify the exact commit to scan' . "\n" .
			"\t" . '--token             The access-token to use to communicate with GitHub' . "\n" .
			"\t" . '--local-git-repo    The local git repository to use for raw-data' . "\n" .
                        "\t" . '                    -- this will save requests to GitHub, speeding up the' . "\n" .
                        "\t" . '                    whole process' . "\n" .
			"\t" . '--dry-run           If set to true, will not make any changes to any data' . "\n" .
			"\t" . '                    on GitHub -- no comments will be submitted, etc.' . "\n" .
			"\t" . '--output            Where to save output made from running PHPCS' . "\n" .
			"\t" . '                    -- this should be a filename' . "\n" .
			"\t" . '--phpcs             Whether to run PHPCS' . "\n" .
			"\t" . '--lint              Whether to do PHP linting' . "\n" .
			"\t" . '--help              Displays this message' . "\n";

		exit( 253 );
	}


	/*
	 * Handle optional --local-git-repo parameter
	 */

	if ( isset( $options['local-git-repo'] ) ) {
		$options['local-git-repo'] = rtrim(
			$options['local-git-repo'],
			'/'
		);

		if ( false === file_exists(
			$options['local-git-repo'] . '/.git'
		) ) {
			vipgoci_phpcs_log(
				'Local git repository was not found',
				array(
					'local_git_repo' =>
						$options['local-git-repo'],
				)
			);

			$options['local-git-repo'] = null;
		}
	}


	/*
	 * Handle boolean parameters parameter
	 */

	vipgoci_parameter_check_bool( $options, 'dry-run', 'false' );

	vipgoci_parameter_check_bool( $options, 'phpcs', 'true' );

	vipgoci_parameter_check_bool( $options, 'lint', 'true' );


	if (
		( false === $options[ 'lint' ] ) &&
		( false === $options[ 'phpcs' ] )
	) {
		vipgoci_phpcs_log(
			'Both --lint and --phpcs set to false, nothing to do!',
			array()
		);

		exit( 253 );
	}

	// Run all checks and store the results in an array
	$results = array();

	if ( true === $options[ 'lint' ] ) {
		// FIXME: what is the path?
		$results[ 'lint' ] = vipgoci_lint_do_scan(
			'.'
		);
	}

	// FIXME: If there are fatal errors with linting,
	// do not continue with the next step

	// FIXME: Instead of submitting to GitHub in this function, we
	// should do that in a generic function, so the linting
	// results can be submitted as well
	if ( true === $options[ 'phpcs' ] ) {
		$results[ 'phpcs' ] = vipgoci_phpcs_scan_commit(
			$options
		);
	}


	vipgoci_phpcs_log(
		'Shutting down',
		array(
			'run_time_seconds'	=> time() - $startup_time,
			'results'		=> $results,
		)
	);

	/*
	 * If any 'error'-type issues  were submitted to
	 * GitHub we announce a failure to our parent-process
	 * by returning with a non-zero exit-code.
	 *
	 * If we only submitted warnings, we do not announce failure.
	 */

	if ( empty( $commit_issues_stats['error'] ) ) {
		return 0;
	}

	else {
		return 250;
	}
}

$ret = vipgoci_run();

exit( $ret );
