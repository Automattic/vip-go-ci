<?php

declare( strict_types=1 );

/**
 * Clone a git-repository and check out a
 * particular revision.
 *
 * @param array $options Array of options.
 *
 * @return string|bool
 */
function vipgoci_unittests_setup_git_repo(
	array $options
) {
	$temp_dir = tempnam(
		sys_get_temp_dir(),
		'git-repo-dir-'
	);

	if ( false === $temp_dir ) {
		return false;
	}

	$res = unlink( $temp_dir );

	if ( false === $res ) {
		return false;
	}

	$res = mkdir( $temp_dir );

	if ( false === $res ) {
		return false;
	}

	$cmd = sprintf(
		'%s clone %s %s 2>&1',
		escapeshellcmd( $options['git-path'] ),
		escapeshellarg( $options['github-repo-url'] ),
		escapeshellarg( $temp_dir )
	);

	$cmd_output = '';
	$cmd_status = 0;

	$res = exec( $cmd, $cmd_output, $cmd_status );

	$cmd_output = implode( PHP_EOL, $cmd_output );

	if (
		( null === $cmd_output ) ||
		( false !== strpos( $cmd_output, 'fatal' ) ) ||
		( 0 !== $cmd_status )
	) {
		return false;
	}

	unset( $cmd );
	unset( $cmd_output );
	unset( $cmd_status );

	$cmd = sprintf(
		'%s -C %s checkout %s 2>&1',
		escapeshellcmd( $options['git-path'] ),
		escapeshellarg( $temp_dir ),
		escapeshellarg( $options['commit'] )
	);

	$cmd_output = '';
	$cmd_status = 0;

	$res = exec( $cmd, $cmd_output, $cmd_status );

	$cmd_output = implode( PHP_EOL, $cmd_output );

	if (
		( null === $cmd_output ) ||
		( false !== strpos( $cmd_output, 'fatal:' ) ) ||
		( 0 !== $cmd_status )
	) {
		return false;
	}

	unset( $cmd );
	unset( $cmd_output );
	unset( $cmd_status );

	return $temp_dir;
}

/**
 * Remove temporary git-repository folder
 * created by vipgoci_unittests_setup_git_repo()
 *
 * @param string $repo_path Path to repository.
 *
 * @return bool
 */
function vipgoci_unittests_remove_git_repo( string $repo_path ) :bool {
	$temp_dir = sys_get_temp_dir();

	/*
	 * If not a string, do not do anything.
	 */
	if ( ! is_string( $repo_path ) ) {
		return false;
	}

	/*
	 * If this does not look like
	 * a path to a temporary directory,
	 * do not do anything.
	 */
	if ( false === strstr(
		$repo_path,
		$temp_dir
	) ) {
		return false;
	}

	/*
	 * If not a directory, do not do anything.
	 */

	if ( ! is_dir( $repo_path ) ) {
		return false;
	}

	/*
	 * Check if it is really a git-repository.
	 */
	if ( ! is_dir( $repo_path . '/.git' ) ) {
		return false;
	}

	/*
	 * Prepare to run the rm -rf command.
	 */

	$cmd = sprintf(
		'%s -rf %s',
		escapeshellcmd( 'rm' ),
		escapeshellarg( $repo_path )
	);

	$cmd_output = '';
	$cmd_status = 0;

	/*
	 * Run it and check results.
	 */
	$res = exec( $cmd, $cmd_output, $cmd_status );

	if ( 0 === $cmd_status ) {
		return true;
	} else {
		printf(
			'Warning: Not able to remove temporary directory successfully; %i, %s',
			$cmd_status,
			$cmd_output
		);

		return false;
	}
}

