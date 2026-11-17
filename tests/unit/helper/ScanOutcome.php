<?php
/**
 * Exercise scan completion and early exits in a real subprocess without APIs.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../defines.php';
require_once __DIR__ . '/../../../main.php';
require_once __DIR__ . '/../../../results.php';
require_once __DIR__ . '/../../../log.php';
require_once __DIR__ . '/../../../options.php';
require_once __DIR__ . '/../../../statistics.php';

/** Replace commit access verification at the GitHub boundary. */
function vipgoci_github_fetch_commit_info( ...$args ): object {
	return (object) array( 'commit' => (object) array(), 'url' => 'https://example.invalid/commit' );
}

/** Any unexpected IRC alert fails the fixture. */
function vipgoci_irc_api_alert_queue( string $message ): void {
	fwrite( STDERR, $message );
	exit( 99 );
}

/** Replace the GitHub boundary. */
function vipgoci_github_prs_implicated_with_retries( ...$args ): array {
	return 'no-pull-request' === $GLOBALS['argv'][1] ? array() : array( (object) array( 'number' => 7 ) );
}

/** Replace the GitHub boundary. */
function vipgoci_github_prs_commits_list( ...$args ): array {
	return match ( $GLOBALS['argv'][1] ) {
		'empty-commits'   => array(),
		'invalid-commit'  => array( null ),
		default          => array( str_repeat( 'b', 40 ) ),
	};
}

$options = array(
	'output'          => $argv[2],
	'repo-owner'      => 'fixture',
	'repo-name'       => 'repository',
	'commit'          => str_repeat( 'a', 40 ),
	'token'           => 'fixture',
	'branches-ignore' => array(),
	'skip-draft-prs'  => false,
	'skip-execution'  => 'disabled' === $argv[1],
	'max-exec-time'   => 0,
);
$results = array( 'issues' => array(), 'stats' => array() );

// Simulate a destination becoming unwritable after successful initialization.
if ( 'write-failure' !== $argv[1] ) {
	vipgoci_run_init_options_output( $options );
}

switch ( $argv[1] ) {
	case 'no-pull-request':
	case 'superseded':
	case 'empty-commits':
	case 'invalid-commit':
	case 'disabled':
		$prs = null;
		vipgoci_run_scan( $options, $results, $prs, time() );
		break;
	case 'findings':
		$results['stats']['lint'][7] = array( 'error' => 1 );
		break;
	case 'warnings':
		$results['stats']['lint'][7] = array( 'error' => 0, 'warning' => 1 );
		break;
	case 'oversized':
		$results['skipped-files'][7] = array( 'total' => 1 );
		break;
	case 'serialization-error':
		$results['issues'][] = "\xB1";
		break;
	case 'operational-error':
		vipgoci_sysexit( 'Fixture API failure', array(), VIPGOCI_EXIT_HTTP_API_ERROR );
		break;
}

exit( vipgoci_run_complete( $options, $results, array() ) );
