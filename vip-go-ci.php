#!/usr/bin/php
<?php

require_once( __DIR__ . '/github-api.php' );
require_once( __DIR__ . '/misc.php' );

require_once( __DIR__ . '/phpcs-scan.php' );
require_once( __DIR__ . '/lint-scan.php' );

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
		)
	);

	// Validate args
	if (
		! isset( $options['repo-owner'] ) ||
		! isset( $options['repo-name'] ) ||
		! isset( $options['commit'] ) ||
		! isset( $options['token'] )
	) {
		print 'Usage: ' . $argv[0] .
			' --repo-owner=repo-owner --repo-name=repo-name ' .
			'--commit=SHA --token=github-access-token' . "\n";
		exit( 253 );
	}


	/*
	 * Handle optional --dry-run parameter
	 */

	if ( ! isset( $options['dry-run'] ) ) {
		$options['dry-run'] = 'false';
	}

	if (
		( $options['dry-run'] !== 'false' ) &&
		( $options['dry-run'] !== 'true' )
	) {
		print 'Usage: Parameter --dry-run has to be either false or true' . "\n";
		exit( 253 );
	}

	else {
		if ( $options['dry-run'] === 'false' ) {
			$options['dry-run'] = false;
		}

		else {
			$options['dry-run'] = true;
		}
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

	// Run all checks and store the results in an array
	$results = array();

	// TODO what is the path?
	$results[ 'lint' ] = vipgoci_lint_do_scan( '.' );

	// TODO - Run PHPCS and return the results, then post them to GH

	vipgoci_phpcs_log(
		'Shutting down',
		array(
			'run_time_seconds'	=> time() - $startup_time,
			'issues_stats'		=> $commit_issues_stats,
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
